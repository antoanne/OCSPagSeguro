<?php

/**
 * @file PayPalPaymentForm.inc.php
 *
 * Copyright (c) 2006-2008 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.paymethod.paypal
 * @class PayPalPaymentForm
 *
 * Form for conference managers to modify Payment Plugin content
 * 
 * $Id: PayPalPaymentForm.inc.php,v 1.7 2008/04/04 17:12:16 asmecher Exp $
 */

import('form.Form');

class CreditPaymentForm extends Form {
	/** @var $plugin object */
	var $PagSeguroPlugin;

	/** @var $queuedPaymentId int */
	var $queuedPaymentId;

	/** @var $key string */
	var $key;

	/** @var $queuedPayment object */
	var $queuedPayment;

	/**
	 * Constructor
	 * @param $payPalPlugin object
	 * @param $queuedPaymentId int
	 * @param $key string
	 * @param $queuedPayment object
	 */
	function CreditPaymentForm(&$PagSeguroPlugin, $queuedPaymentId, $key, &$queuedPayment) {
		parent::Form($plugin->getTemplatePath() . 'paymentForm.tpl');

		$this->addCheck(new FormValidatorPost($this));

		$this->PagSeguroPlugin =& $PagSeguroPlugin;
		$this->queuedPaymentId = $queuedPaymentId;
		$this->key = $key;
		$this->queuedPayment =& $queuedPayment;
	}


	/**
	 * Initialize form data.
	 */
	function initData() {
		$PagSeguroPlugin =& $this->PagSeguroPlugin;
		$user =& Request::getUser();
		$userId = ($user)?$user->getUserId():null;

		$queuedPayment =& $this->queuedPayment;

		$this->_data = array(
			'email_cobranca' => 'sbe@fgv.br',  
			'item_name' => $queuedPayment->getDescription(),
			'a3' => $queuedPayment->getAmount($args),
			'quantity' => 1,
			'no_note' => 1,
			'no_shipping' => 1,
			'currency_code' => $queuedPayment->getCurrencyCode(),
			'lc' => String::substr(Locale::getLocale(), 3), 
			'custom' => $this->key,
			'notify_url' => Request::url(null, null, 'payment', 'ipn', array($queuedPayment->getQueuedPaymentId())),  
			'return' => Request::url(null, null, 'payment', 'return', array($queuedPayment->getQueuedPaymentId())),
			'cancel_return' => Request::url(null, null, 'payment', 'cancel', array($queuedPayment->getQueuedPaymentId())),
			'first_name' => ($user)?$user->getFirstName():'',  
			'last_name' => ($user)?$user->getLastname():'',
			'city' => '',
			'zip' => '',
			'item_number' => 1
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'email_cobranca',
			'item_name', 
			'quantity',
			'no_note',
			'no_shipping',
			'currency_code',
			'lc',  
			'custom', 
			'notify_url',   
			'return', 
			'cancel_return', 
			'first_name',  
			'last_name',
			'city', 
			'zip',
			'item_number'
		));
	}		

	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('message', $this->message);
		$templateMgr->assign('paymentIsRegistration', $this->isRegistration);
		parent::display();
	}	

}
?>
