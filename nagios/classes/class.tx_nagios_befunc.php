<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2012 Michael Schams <schams.net>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * public function displayWarningMessages_postProcess(&$warning)
 *	Generate warning message to admin users if misconfiguration of "Nagios server list" was detected
 *
 */

/**
 * TYPO3 backend functions for the 'nagios' extension.
 *
 * @author		Michael Schams <schams.net>
 * @package		TYPO3
 * @subpackage	tx_nagios
 */
class tx_nagios_befunc {

	var $scriptRelPath = 'classes/class.tx_nagios_befunc.php';
	var $extKey        = 'nagios';

	/**
	 * List if "dangerous" IP addresses
	 *
	 * var array
	 */
	var $invalidIpAddresses = array('0.0.0.0', '*.*.*.*');

	/**
	 * Generate warning message to admin users if misconfiguration of "Nagios server list" was detected.
	 * This method is triggered by a hook for showing admin error messages in TYPO3 backend
	 *
	 * @param	array		$warning: reference to array with warning messages
	 * @return	void
	 */
	public function displayWarningMessages_postProcess(&$warning) {

		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]) && is_string($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]) && !empty($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]))
			$extConf = @unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
			if (is_array($extConf) && array_key_exists('securityNagiosServerList', $extConf)) {

				// check for an empty list
				$nagiosServerList = trim(preg_replace('/[^0-9\.]/', '', $extConf['securityNagiosServerList']));
				if (empty($nagiosServerList)) {
					$warning[$this->extKey.':'.__CLASS__] = sprintf($GLOBALS['LANG']->sL('LLL:EXT:nagios/locallang_tca.xml:nagios.befunc.adminwarning.test'));
				}

				// check for invalid or "dangerous" IP addresses
				$nagiosServerList = explode(',', $extConf['securityNagiosServerList']);
				foreach($nagiosServerList as $ipAddress) {
					$ipAddress = trim($ipAddress);
					if (!empty($ipAddress)
					 && (preg_match('/^[0-9]{1,3}\.[0-9\*]{1,3}\.[0-9\*]{1,3}\.[0-9\*]{1,3}$/', $ipAddress) == FALSE
					 || in_array($ipAddress, $this->invalidIpAddresses))) {
					$warning[$this->extKey.':'.__CLASS__] = sprintf($GLOBALS['LANG']->sL('LLL:EXT:nagios/locallang_tca.xml:nagios.befunc.adminwarning.test'));
				}
			}
		}
	}
};

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nagios/classes/class.tx_nagios_befunc.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nagios/classes/class.tx_nagios_befunc.php']);
}

?>
