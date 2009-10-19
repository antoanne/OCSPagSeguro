<?php

/**
 * @file PayPalSettingsForm.inc.php
 *
 * Copyright (c) 2006-2008 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.paymethod.paypal
 * @class PayPalSettingsForm
 *
 * Form for conference managers to edit the PayPal Settings
 * 
 * $Id: PayPalSettingsForm.inc.php,v 1.6 2008/04/04 17:12:16 asmecher Exp $
 */

import('form.Form');

class CreditSettingsForm extends Form {
	/** @var $schedConfId int */
	var $schedConfId;

	/** @var $plugin object */
	var $plugin;

	/** $var $errors string */
	var $errors;

	/**
	 * Constructor
	 * @param $schedConfId int
	 */
	function CreditSettingsForm(&$plugin, $conferenceId, $schedConfId) {
		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidatorPost($this));

		$this->conferenceId = $conferenceId;
		$this->schedConfId = $schedConfId;
		$this->plugin =& $plugin;

	}



	/**
	 * Initialize form data from current group group.
	 */
	function initData( ) {
		$schedConfId = $this->schedConfId;
		$conferenceId = $this->conferenceId;
		$plugin =& $this->plugin;

		/* FIXME: put these defaults somewhere else */
		/*
		$paypalSettings['enabled'] = true;
		$paypalSettings['paypalurl'] = "http://www.sandbox.paypal.com";
		$paypalSettings['selleraccount'] = "seller@ojs.org";
		;
		*/

		$this->_data = array(
			'enabled' => $plugin->getSetting($conferenceId, $schedConfId, 'enabled'),
			'crediturl' => $plugin->getSetting($conferenceId, $schedConfId, 'crediturl')
		);

	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('enabled',
					  'crediturl' 
		));
	}

	/**
	 * Save page - write to content file. 
	 */	 
	function save() {
		$plugin =& $this->plugin;
		$conferenceId = $this->conferenceId;
		$schedConfId = $this->schedConfId;

		$CreditSettings = array();
		$plugin->updateSetting($conferenceId, $schedConfId, 'enabled', $this->getData('enabled'));
		$plugin->updateSetting($conferenceId, $schedConfId, 'crediturl', $this->getData('crediturl'));
	}
}

?>
