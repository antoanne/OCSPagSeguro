<?php

/**
 * @file PayPalPlugin.inc.php
 *
 * Copyright (c) 2006-2008 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.paymethod.paypal
 * @class PayPalPlugin
 *
 * PayPal plugin class
 *
 */

import('classes.plugins.PaymethodPlugin');

class PagSeguroPlugin extends PaymethodPlugin {

	function getName() {
		return Locale::translate('plugins.paymethod.pagseguro.displayName');
	}

	function getDisplayName() {
		return Locale::translate('plugins.paymethod.pagseguro.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.paymethod.pagseguro.description');
	}   

	function register($category, $path) {
		if (parent::register($category, $path)) {			
			$this->addLocaleData();
			$this->import('PagSeguroDAO');
			$CreditDao =& new PagSeguroDAO();
			DAORegistry::registerDAO('PagSeguroDAO', $CreditDao);
			return true;
		}
		return false;
	}

	function getSettingsFormFieldNames() {
		return array('crediturl');
	}

	function isCurlInstalled() {
		return true;
	}

	function isConfigured() {
		$schedConf =& Request::getSchedConf();
		if (!$schedConf) return false;

		// Make sure CURL support is included.
		if (!$this->isCurlInstalled()) return false;

		// Make sure that all settings form fields have been filled in
		foreach ($this->getSettingsFormFieldNames() as $settingName) {
			$setting = $this->getSetting($schedConf->getConferenceId(), $schedConf->getSchedConfId(), $settingName);
			if (empty($setting)) return false;
		}
		return true;
	}

	function displayPaymentSettingsForm(&$params, &$smarty) {
		$smarty->assign('isCurlInstalled', $this->isCurlInstalled());
		return parent::displayPaymentSettingsForm($params, $smarty);
	}

	function displayPaymentForm($queuedPaymentId, &$queuedPayment) {
		if (!$this->isConfigured()) return false;
		$schedConf =& Request::getSchedConf();
		$user =& Request::getUser();
		$params = array(
			'email_cobranca' => $schedConf->getSetting('contactEmail'), 
			'tipo' => 'CP',
			'moeda' => $queuedPayment->getCurrencyCode(),
			'item_id_1' => 'OCS-' . $schedConf->_data['path'] ,
			'item_descr_1' => $queuedPayment->getDescription(),
			'item_quant_1'=> '1',
			'item_valor_1' => $queuedPayment->getAmount() . '00',
			'item_frete_1' => '000',

			'valor' => $queuedPayment->getAmount()
		);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('params', $params);
		$templateMgr->assign('CreditFormUrl', $this->getSetting($schedConf->getConferenceId(), $schedConf->getSchedConfId(), 'crediturl'));
		$templateMgr->display($this->getTemplatePath() . 'paymentForm.tpl');
	}

	/**
	 * Handle incoming requests/notifications
	 */
	function handle($args) {
		$templateMgr =& TemplateManager::getManager();
		$schedConf =& Request::getSchedConf();
		if (!$schedConf) return parent::handle($args);

		// Just in case we need to contact someone
		import('mail.MailTemplate');
		$contactName = $schedConf->getSetting('contactName');
		$contactEmail = $schedConf->getSetting('contactEmail');
		$mail = &new MailTemplate('CREDIT_INVESTIGATE_PAYMENT');
		$mail->setFrom($contactEmail, $contactName);
		$mail->addRecipient($contactEmail, $contactName);

		$paymentStatus = Request::getUserVar('payment_status');

		switch (array_shift($args)) {
			case 'ipn':
				// Build a confirmation transaction.
				$req = 'cmd=_notify-validate';
				foreach ($_POST as $key => $value) $req .= '&' . urlencode($key) . '=' . urlencode($value);

				// Create POST response
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $this->getSetting($schedConf->getConferenceId(), $schedConf->getSchedConfId(), 'crediturl'));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, Array('Content-Type: application/x-www-form-urlencoded', 'Content-Length: ' . strlen($req)));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
				$ret = curl_exec ($ch);
				curl_close ($ch);

				// Check the confirmation response and handle as necessary.
				if (strcmp($ret, 'VERIFIED') == 0) switch ($paymentStatus) {
					case 'Completed':
						$CreditDao =& DAORegistry::getDAO('CreditDAO');
						$transactionId = Request::getUserVar('txn_id');
						if ($CreditDao->transactionExists($transactionId)) {
							// A duplicate transaction was received; notify someone.
							$mail->assignParams(array(
								'schedConfName' => $schedConf->getFullTitle(),
								'postInfo' => print_r($_POST, true),
								'additionalInfo' => "Duplicate transaction ID: $transactionId",
								'serverVars' => print_r($_SERVER, true)
							));
							$mail->send();
							exit();
						} else {
							// New transaction succeeded. Record it.
							$CreditDao->insertTransaction(
								$transactionId,
								Request::getUserVar('txn_type'),
								Request::getUserVar('payer_email'),
								Request::getUserVar('item_number'),
								Request::getUserVar('payment_date'),
								Request::getUserVar('payer_id'),
								Request::getUserVar('receiver_id')
							);
							$queuedPaymentId = Request::getUserVar('custom');

							import('payment.ocs.OCSPaymentManager');
							$ocsPaymentManager =& OCSPaymentManager::getManager();

							// Verify the cost and user details as per PayPal spec.
							$queuedPayment =& $ocsPaymentManager->getQueuedPayment($queuedPaymentId);
							if (!$queuedPayment) {
								// The queued payment entry is missing. Complain.
								$mail->assignParams(array(
									'schedConfName' => $schedConf->getFullTitle(),
									'postInfo' => print_r($_POST, true),
									'additionalInfo' => "Missing queued payment ID: $queuedPaymentId",
									'serverVars' => print_r($_SERVER, true)
								));
								$mail->send();
								exit();
							}

							if (
								($queuedAmount = $queuedPayment->getAmount()) != ($grantedAmount = Request::getUserVar('mc_gross')) ||
								($queuedCurrency = $queuedPayment->getCurrencyCode()) != ($grantedCurrency = Request::getUserVar('mc_currency'))) {
								// The integrity checks for the transaction failed. Complain.
								$mail->assignParams(array(
									'schedConfName' => $schedConf->getFullTitle(),
									'postInfo' => print_r($_POST, true),
									'additionalInfo' =>
										"Granted amount: $grantedAmount\n" .
										"Queued amount: $queuedAmount\n" .
										"Granted currency: $grantedCurrency\n" .
										"Queued currency: $queuedCurrency\n" .
										"Granted to Credit account: $grantedEmail\n" .
										"Configured Credit account: $queuedEmail",
									'serverVars' => print_r($_SERVER, true)
								));
								$mail->send();
								exit();
							}

							// Fulfill the queued payment.
							if ($ocsPaymentManager->fulfillQueuedPayment($queuedPaymentId, $queuedPayment)) exit();

							// If we're still here, it means the payment couldn't be fulfilled.
							$mail->assignParams(array(
								'schedConfName' => $schedConf->getFullTitle(),
								'postInfo' => print_r($_POST, true),
								'additionalInfo' => "Queued payment ID $queuedPaymentId could not be fulfilled.",
								'serverVars' => print_r($_SERVER, true)
							));
							$mail->send();
						}
						exit();
					case 'Pending':
						// Ignore.
						exit();
					default:
						// An unhandled payment status was received; notify someone.
						$mail->assignParams(array(
							'schedConfName' => $schedConf->getFullTitle(),
							'postInfo' => print_r($_POST, true),
							'additionalInfo' => "Payment status: $paymentStatus",
							'serverVars' => print_r($_SERVER, true)
						));
						$mail->send();
						exit();
				} else {
					// An unknown confirmation response was received; notify someone.
					$mail->assignParams(array(
						'schedConfName' => $schedConf->getFullTitle(),
						'postInfo' => print_r($_POST, true),
						'additionalInfo' => "Confirmation return: $ret",
						'serverVars' => print_r($_SERVER, true)
					));
					$mail->send();
					exit();
				}

				break;
			case 'cancel':
				$templateMgr->assign(array(
					'currentUrl' => Request::url(null, null, 'index'),
					'pageTitle' => 'plugins.paymethod.pagseguro.purchase.cancelled.title',
					'message' => 'plugins.paymethod.pagseguro.purchase.cancelled'
				));
				$templateMgr->display('common/message.tpl');
				exit();
				break;
			case 'return':
				Request::redirect(null, null, 'index');
				break;
		}
		parent::handle($args); // Don't know what to do with it
	}

	function getInstallSchemaFile() {
		return ($this->getPluginPath() . DIRECTORY_SEPARATOR . 'schema.xml');
	}

	function getInstallDataFile() {
		return ($this->getPluginPath() . DIRECTORY_SEPARATOR . 'data.xml');
	}
}

?>
