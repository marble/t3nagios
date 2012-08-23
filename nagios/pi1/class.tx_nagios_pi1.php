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
/**
 * public function main($content, $configuration)
 *	The main method of the extension.
 *
 * private function getFlexFormConfiguration()
 *	Returns FlexForm configuration (TYPO3's backend configuration).
 *
 * private function getStaticMarkers()
 *	Returns static markers.
 *
 */

// include frontend class
t3lib_div::requireOnce(PATH_tslib.'class.tslib_pibase.php');

// include class tx_nagios which builds main functions
t3lib_div::requireOnce(t3lib_extMgm::extPath('nagios').'/classes/class.tx_nagios.php');

/**
 * Plugin 'Nagios Monitoring' for the 'nagios' extension.
 *
 * @author		Michael Schams <schams.net>
 * @package		TYPO3
 * @subpackage	tx_nagios
 */
class tx_nagios_pi1 extends tslib_pibase {

	var $prefixId      = 'tx_nagios_pi1';
	var $scriptRelPath = 'pi1/class.tx_nagios_pi1.php';
	var $extKey        = 'nagios';

	/**
	 * page ID (uid) of current page
	 *
	 * @var integer
	 */
	private $pageId;

	/**
	 * Details about THIS extension (e.g. title, version, etc.), set in constructor.
	 *
	 * @var array
	 */
	private $extensionDetails = array();

	/**
	 * String (usually a character) used to separate each element.
	 *
	 * @var string
	 */
	private $elementSeparator = "\n";

	/**
	 * This variable enables/disables debug mode.
	 * This setting can be configured in TYPO3's BE (flexforms).
	 *
	 * @var resource
	 */
	private $debugMode = FALSE;

	/**
	 * Extension configuration defined by flexforms in TYPO3 backend
	 *
	 * @var array
	 */
	private $flexFormConfiguration = array();

	/**
	 * Nagios object (instantiated in Contructor method)
	 *
	 * @var object
	 */
	private $objNagios;


	/**
	 * Constructor method.
	 *
	 * @access	public
	 * @return	void
	 */
	function __construct() {

		$this->objNagios = t3lib_div::makeInstance('tx_nagios');

		// get details about THIS extension
		$this->extensionDetails = $this->objNagios->getExtensionDetails($this->extKey);
		if($this->extensionDetails === FALSE) {
			$this->extensionDetails = array('title' => 'Nagios', 'version' => 'unknown');
		}

		// page ID (uid) of current page, that contains the TYPO3 Nagios extension
		$this->pageId = $GLOBALS['TSFE']->id;
	}


	/**
	 * The main method of the extension.
	 *
	 * @access	public
	 * @param	string	$content: The extension content
	 * @param	array	$configuration: The extension configuration
	 * @return	string	Information about the TYPO3 instance according to extension configuration
	 */
	public function main($content, $configuration) {

		$this->configuration = $configuration;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj = 1;

		$this->flexFormConfiguration = array_merge($this->objNagios->initExtensionConfiguration(), $this->getFlexFormConfiguration());
		$static_markers = $this->getStaticMarkers();

		$data = array();

		// get version of Nagios plugin (client) if passed by GET/POST argument
		$nagiosPluginVersion = $this->objNagios->getNagiosPluginVersion();

		if($this->flexFormConfiguration['featureDebugMode'] === TRUE) {
			$this->debugMode = TRUE;
		}

		if($this->objNagios->isValidNagiosServer($this->flexFormConfiguration['securityNagiosServerList']) === FALSE) {

			// remote (Nagios?) server is not allowed to retrieve information about this TYPO3 server.
			// generate some further explanations (problem description) as comments.
			$data[] = '# '.$this->pi_getLL('access_denied_line01');
			$data[] = '# '.$this->pi_getLL('access_denied_line02');
			$data[] = '# '.$this->pi_getLL('access_denied_line03');
			$data[] = '# (page ID: '.$this->pageId.')';
		}
		else {
			// all security checks successfully passed - Nagios server may retrieve data

			if($this->flexFormConfiguration['featureTYPO3Version'] === TRUE) {
				$data[] = $this->pi_getLL('keyword_typo3').':version-'.$this->objNagios->getTypo3Version();
			}
			if($this->flexFormConfiguration['featurePHPVersion'] === TRUE) {
				$data[] = $this->pi_getLL('keyword_php').':version-'.$this->objNagios->getPhpVersion();
			}
			if($this->flexFormConfiguration['featureExtensionList'] === TRUE) {
				$data[] = implode($this->elementSeparator, $this->objNagios->getExtensionVersions($this->pi_getLL('keyword_extension')));
			}
			if($this->flexFormConfiguration['featureExtensionDependencyTypo3'] === TRUE) {
				$extensionDependencyIssues = $this->objNagios->getExtensionDependency($this->pi_getLL('keyword_extensiondependencytypo3'), tx_nagios::TX_NAGIOS_EXTENSION_DEPENDENCY_TYPO3);
				if(is_array($extensionDependencyIssues) && count($extensionDependencyIssues) > 0) {
					$data[] = implode($this->elementSeparator, $extensionDependencyIssues);
				}
			}
			if($this->flexFormConfiguration['featureSitename'] === TRUE) {
				$data[] = $this->pi_getLL('keyword_sitename').':'.$this->objNagios->getSitename();
			}
			if($this->flexFormConfiguration['featureServername'] === TRUE) {
				$data[] = $this->pi_getLL('keyword_servername').':'.$this->objNagios->getServername();
			}
			if($this->flexFormConfiguration['featureBasicDatabaseDetailsTables'] === TRUE) {
				$data[] = $this->pi_getLL('keyword_databasetables').':'.$this->objNagios->getDatabaseDetailsTables();
			}
			if($this->flexFormConfiguration['featureBasicDatabaseDetailsProcesslist'] === TRUE) {
				$database_processlist = $this->objNagios->getDatabaseDetailsProcesses();
				if($database_processlist !== FALSE && is_numeric($database_processlist)) {
					$data[] = $this->pi_getLL('keyword_databaseprocesslist').':'.$database_processlist;
				}
			}
			if($this->flexFormConfiguration['featureTimestamp'] === TRUE) {
				$data[] = $this->pi_getLL('keyword_timestamp').':'.date('U-T');
			}
			if($this->flexFormConfiguration['featureDeprecationLog'] === TRUE) {
				$data[] = $this->pi_getLL('keyword_deprecationlog').':'.$this->objNagios->getDeprecationLogSetting();
			}
			if($this->flexFormConfiguration['featureDonationNotice'] === TRUE) {
				$data[] = $this->pi_getLL('keyword_donationnotice').':'.$this->objNagios->getDonationNoticeSetting();
			}
			if($this->flexFormConfiguration['featureCheckDiskUsage'] === TRUE) {
				$data[] = $this->pi_getLL('keyword_diskusage').':'.$this->objNagios->getDiskUsage();
			}

			// include a warning message: this extension is still used as a frontend plugin
			$data[] = $this->pi_getLL('keyword_warning').':'.urlencode(str_replace('{PAGEID}', $this->pageId, $this->pi_getLL('nagios_extension_as_frontend_plugin')));
		}

		// glue single elements together, using $this->elementSeparator variable
		$data = implode($this->elementSeparator, $data);

		// add header ("Nagios TYPO3 Monitoring Version x.x.x...") if not permitted in extension configuration
		$header = '';
		if($this->flexFormConfiguration['securitySupressHeader'] !== TRUE) {
			$header.= '# '.$this->extensionDetails['title'].' Version '.$this->extensionDetails['version']." - http://schams.net/nagios\n";

			if($nagiosPluginVersion !== FALSE && is_string($nagiosPluginVersion) && !empty($nagiosPluginVersion)) {
				$header.= '# Nagios Plugin Version '.$nagiosPluginVersion." (IP: ".t3lib_div::getIndpEnv('REMOTE_ADDR').")\n";
			}
			$header.= "\n";
		}

		// final output: header (if available) + data (with static markers replaced)
		return $header.$this->cObj->substituteMarkerArray($data, $static_markers);
	}


	/**
	 * Returns FlexForm configuration (TYPO3's backend configuration).
	 *
	 * @access	private
	 * @return	array	FlexForm configuration (key: FF keyword, value: FF value)
	 */
	private function getFlexFormConfiguration() {

		// initialize variable
		$flexFormConfiguration = array();

		// initialize FlexForm
		$this->pi_initPIflexForm();

		// assign the flexform data to a local variable for easier access
		$piFlexForm = $this->cObj->data['pi_flexform'];

		// traverse the entire array based on the language
		// and assign each configuration option to $this->flexFormConfiguration array
		if(is_array($piFlexForm) && count($piFlexForm) > 0) {
			foreach($piFlexForm['data'] as $sheet => $data) {
				foreach($data as $lang => $value) {
					foreach($value as $key => $val) {
						$configurationValue = $this->pi_getFFvalue($piFlexForm, $key, $sheet);
						if(preg_match('/^(feature|securityEncryptionkey|securitySupressHeader)/', $key)) {
							$flexFormConfiguration[$key] = ($configurationValue == 1 ? TRUE : FALSE);
						}
						else {
							$flexFormConfiguration[$key] = $configurationValue;
						}
					}
				}
			}
		}
		return $flexFormConfiguration;
	}


	/**
	 * Returns static markers.
	 *
	 * @access	private
	 * @return	array	Array with static markers (key: keyword, value: value)
	 */
	private function getStaticMarkers() {

		return array(
			'###PAGEID###' => $this->pageId,
		);
	}
}

if(defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nagios/pi1/class.tx_nagios_pi1.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nagios/pi1/class.tx_nagios_pi1.php']);
}

?>
