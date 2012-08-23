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
 * public function isValidNagiosServer($securityNagiosServerList = NULL)
 *	Returns TRUE list if valid Nagios servers contains IP address of requesting server (remote address).
 *	In other words: method to check if Nagios server is allowed to retrieve information about TYPO3 instance.
 *
 * public function getTypo3Version()
 *	Returns TYPO3 version
 *
 * public function getPhpVersion()
 *	Returns PHP version (in valid major/minor/release format)
 *
 * public function getExtensionVersions($prefix = 'EXT')
 *	Generates list of currently installed/loaded extensions and extension versions
 *
 * public function getExtensionDependency($prefix = 'EXT', $type = NULL, $ignoreMissing = TRUE)
 *	Generates list of extensions and extension versions, currently installed/loaded
 *
 * private function populateExtensionList()
 *	Generates list of currently installed/loaded extension and stores data in $this->extensionList
 *
 * public function getExtensionDetails($extensionKey = NULL)
 *	Returns details (e.g. title, version, etc.) about an extension
 *	The extension has to be loaded.
 *
 * public function getDeprecationLogSetting()
 *	Returns status of deprecation log settings (only 'file' is relavant).
 *	Deprecation logging was introduced in TYPO3 version 4.3.0.
 *
 * public function getDonationNoticeSetting()
 *	Returns status of donation popup setting.
 *	Donation popup was introduced in TYPO3 version 4.4.0 and abandoned again in TYPO3 version 4.5.1 and above
 *
 * public function getDatabaseDetailsTables()
 *	Returns current amount of DB tables
 *
 * public function getDatabaseDetailsProcesses()
 *	Returns current amount of DB process.
 *	Note: SQL statement 'SHOW PROCESSLIST' is used which requires appropriate access privileges on a database server level.
 *
 * public function getDiskUsage()
 *	Determines the size of current disk usage if possible (requires *nix system)
 *	Note: the disk usage determined by method is not 100% reliable. If the web server user (e.g. 'www-data') does not have access to all subdirectories of PATH_site, not all data can be counted.
 *
 * public getSitename()
 *	Returns TYPO3 site name if available
 *
 * public getServername()
 *	Returns server name (as set in web server configuration) or HTTP host name if available
 *
 * private function isWindowsSystem()
 *	Checks if the operating system PHP is running on is a Microsoft Windows system
 *
 * private function extractVersion($versionString = '', $minMax = 0)
 *	Extract min or max version from string, e.g. '4.5.0' from '4.5.0-4.6.999' if 'min' (first element) is requested
 *
 * public function getNagiosPluginVersion()
 *	Returns version of Nagios plugin (client) if passed by GET/POST argument
 *
 * public function initExtensionConfiguration()
 *	Initializes extension configuration array
 *
 */

// TYPO3 version 4.1.x does not have class t3lib_exec when eID method is used
if(class_exists('t3lib_exec') === FALSE && is_readable(PATH_t3lib.'class.t3lib_exec.php')) {
	t3lib_div::requireOnce(PATH_t3lib.'class.t3lib_exec.php');
}

/**
 * Plugin 'Nagios Monitoring' for the 'nagios' extension.
 *
 * @author		Michael Schams <schams.net>
 * @package		TYPO3
 * @subpackage	tx_nagios
 */
class tx_nagios {

	var $scriptRelPath = 'classes/class.tx_nagios.php';
	var $extKey        = 'nagios';

	/**
	 * Constants
	 *
	 * @var		const
	 */
	const
			TX_NAGIOS_EXTENSION_DEPENDENCY_TYPO3 = 'typo3',
			TX_NAGIOS_EXTENSION_DEPENDENCY_PHP = 'php',

			// keys in array for parameter $minMax in method extractVersion()
			TX_NAGIOS_EXTRACTVERSION_MIN = 0,
			TX_NAGIOS_EXTRACTVERSION_MAX = 1,

			// valid GET/POST parameters for passing Nagios plugin version
			TX_NAGIOS_GETPOST_PARAMETER_NAGIOS_VERSION = 'nv',
			TX_NAGIOS_GETPOST_PARAMETER_NAGIOS_PLUGIN_VERSION = 'npv';

	/**
	 * Array that holds the extension list once retrieved
	 *
	 * @access	public
	 * @var		array
	 */
	public $extensionList = array();

	/**
	 * For each extension a check is performed if the PHP version meets the extension's requirements.
	 * If one (or all) of these checks fails, variable $extensionWarningPHP is set to TRUE.
	 * *TODO* not implemented yet
	 *
	 * @access	private
	 * @var		boolean
	 */
	private $extensionWarningPHP = FALSE;

	/**
	 * For each extension a check is performed if the TYPO3 version meets the extension's requirements.
	 * If one (or all) of these checks fails, variable $extensionWarningTYPO3 is set to TRUE.
	 * *TODO* not implemented yet
	 *
	 * @access	private
	 * @var		boolean
	 */
	private $extensionWarningTYPO3 = FALSE;

	/**
	 * Filesize of deprecation log, if available.
	 *
	 * @access	private
	 * @var		integer
	 */
	private $deprecationLogFilesize = 0;

	/**
	 * Resource link to database DBAL ($GLOBALS['TYPO3_DB']), set in constructor.
	 *
	 * @access	private
	 * @var		resource
	 */
	private $database = NULL;


	/**
	 * Constructor method.
	 *
	 * @access	public
	 * @return	void
	 */
	function __construct() {

		// set database resource link
		$this->database = $GLOBALS['TYPO3_DB'];
	}


	/**
	 * Returns TRUE if list of valid Nagios servers contains IP address of requesting server (remote address).
	 * In other words: method to check if Nagios server is allowed to retrieve information about TYPO3 instance.
	 * Wildcards can be used, e.g. 123.45.*.*
	 *
	 * @access	public
	 * @param	string	Comma-separated list of IP addresses of valid Nagios servers
	 * @return	boolean	TRUE if valid remote server, FALSE if access denied
	 */
	public function isValidNagiosServer($securityNagiosServerList = NULL) {

		if(isset($securityNagiosServerList)
		  && !empty($securityNagiosServerList)
		  && is_string($securityNagiosServerList)) {

			// check remote IP address
			return t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), preg_replace('/[^0-9,\*\.]/', '', $securityNagiosServerList));
		}

		// extension configuration does not restrict remote Nagios servers
		// (same as using "*" as configuration value)
		return TRUE;
	}


	/**
	 * Returns TYPO3 version
	 *
	 * @access	public
	 * @return	string	TYPO3 version
	 */
	public function getTypo3Version() {

		return TYPO3_version;
	}


	/**
	 * Returns PHP version (in valid major/minor/release format)
	 *
	 * @access	public
	 * @return	string	PHP version
	 */
	public function getPhpVersion() {

		// ensure, phpversion is a valid major/minor/release string (e.g. clean up strings such as "5.2.4-2ubuntu5.2")
		return substr(phpversion(), 0, strpos(phpversion().'-', '-'));
	}


	/**
	 * Generates list of extensions and extension versions, currently installed/loaded.
	 *
	 * @access	public
	 * @param	string	Keyword used as prefix in response to client (see locallang.xml)
	 * @return	array	Array (values (string): 'EXT:' + extension key + "-" + extension version)
	 */
	public function getExtensionVersions($prefix = 'EXT') {

		if(!isset($this->extensionList) || !is_array($this->extensionList) || count($this->extensionList) == 0) {
			$this->populateExtensionList();
		}

		$extensionList = array();
		foreach($this->extensionList as $extensionKey => $extensionDetails) {
			if(preg_match('/[a-z0-9_]*/', $extensionKey) && is_array($extensionDetails) && array_key_exists('version', $extensionDetails)) {
				$extensionList[] = $prefix.':'.$extensionKey.'-version-'.preg_replace('/[^0-9\.]* /', '', $extensionDetails['version']);
			}
		}
		return $extensionList;
	}


	/**
	 * Generates list of extensions and extension versions, currently installed/loaded
	 *
	 * @access	public
	 * @param	string	Keyword used as prefix in response to client (see locallang.xml)
	 * @param	string	Type of constraint, e.g. "typo3" or "php", see file "ext_emconf.php"
	 * @param	boolean	if set to FALSE, every extension is included in return array that does *not* show the dependency type
	 * @return	array	Array (values (string): 'EXTDEP' + type + ':' + extension key + "-" + min version + "-" + max version)
	 */
	public function getExtensionDependency($prefix = 'EXTDEP', $type = NULL, $ignoreMissing = TRUE) {

		if(!isset($this->extensionList) || !is_array($this->extensionList) || count($this->extensionList) == 0) {
			$this->populateExtensionList();
		}

		$extensionList = array();
		foreach($this->extensionList as $extensionKey => $extensionDetails) {
			if(preg_match('/[a-z0-9_]*/', $extensionKey) && is_array($extensionDetails)) {
				if(isset($extensionDetails['constraints']['depends'][$type])) {
					if($type === self::TX_NAGIOS_EXTENSION_DEPENDENCY_TYPO3) {

						$minVersion = $this->extractVersion($extensionDetails['constraints']['depends'][$type], self::TX_NAGIOS_EXTRACTVERSION_MIN);
						$maxVersion = $this->extractVersion($extensionDetails['constraints']['depends'][$type], self::TX_NAGIOS_EXTRACTVERSION_MAX);

						if( ($minVersion !== FALSE && $this->convertVersionNumberToInteger(TYPO3_version) < $this->convertVersionNumberToInteger($minVersion))
						 || ($maxVersion !== FALSE && $this->convertVersionNumberToInteger(TYPO3_version) > $this->convertVersionNumberToInteger($maxVersion))) {

							$extensionList[] = $prefix.':'.$extensionKey.'-'.$minVersion.'-'.$maxVersion;
						}
					}
				}
				elseif($ignoreMissing !== TRUE) {
					$extensionList[] = $prefix.':'.$extensionKey.'-unknown-unknown';
				}
			}
		}
		return $extensionList;
	}


	/**
	 * Generates list of currently installed/loaded extension and stores data in $this->extensionList
	 *
	 * @access	private
	 * @return	void
	 */
	private function populateExtensionList() {

		$this->extensionList = array();
		if(isset($GLOBALS['TYPO3_LOADED_EXT']) && is_array($GLOBALS['TYPO3_LOADED_EXT'])) {
			$loadedExtensions = $GLOBALS['TYPO3_LOADED_EXT'];
			foreach($loadedExtensions as $extensionKey => $extension) {
				if(is_array($extension) && $extension['type'] != 'S') {
					$this->extensionList[$extensionKey] = $this->getExtensionDetails($extensionKey);
				}
			}
		}
	}


	/**
	 * Returns details (e.g. title, version, etc.) about an extension
	 * The extension has to be loaded.
	 *
	 * @access	public
	 * @param	string	$extensionKey: Extension key
	 * @return	array	Array with extension details or FALSE if error (e.g. extension key unknown)
	 */
	public function getExtensionDetails($extensionKey = NULL) {

		if($extensionKey !== NULL && is_string($extensionKey) && !empty($extensionKey)) {

			// check if the extension is loaded (if not, t3lib_extMgm::extPath() would throw an exception)
			if(t3lib_extMgm::isLoaded($extensionKey)) {
				$emconfPath = t3lib_extMgm::extPath($extensionKey).'ext_emconf.php';
				if(is_readable($emconfPath)) {
					$_EXTKEY = $extensionKey;
					include($emconfPath);
					if(isset($EM_CONF[$extensionKey]) && is_array($EM_CONF[$extensionKey])) {
						return $EM_CONF[$extensionKey];
					}
				}
			}
		}
		return FALSE;
	}


	/**
	 * Returns status of deprecation log settings (only 'file' is relavant).
	 * Deprecation logging was introduced in TYPO3 version 4.3.0.
	 *
	 * @access	public
	 * @return	string	Status of deprecation log ('enabled', 'disabled', 'unknown' or 'not-supported')
	 */
	public function getDeprecationLogSetting() {

		$statusDeprecationLog = array();

		// deprecation log was introduced in TYPO3 version 4.3.0 (see: http://typo3.org/download/release-notes/typo3-43 )
		// TYPO3 versions before 4.3.0 do not support deprecation logging.
		if($this->convertVersionNumberToInteger(TYPO3_version) < 4003000) {
			return 'not-supported';
		}

		// if $TYPO3_CONF_VARS['SYS']['enableDeprecationLog'] is NOT set,
		// deprecation log is enabled and set to "file" method by default
		if(!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog'])) {
			$statusDeprecationLog = array('file');
		}

		if( $GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog'] === FALSE
		 || $GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog'] === '0'
		 || $GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog'] === 0) {
			return 'disabled';
		}
		elseif(is_string($GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog'])) {

			// 'file': The log file will be written to typo3conf/deprecation_[hash-value].log (default).
			// 'devlog': The log will be written to the development log.
			// 'console': The log will be displayed in the Backend's Debug Console.
			// The logging options can be combined by comma-separating them.

			$statusDeprecationLog = explode(',', $GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog']);
		}

		if(in_array('file', $statusDeprecationLog)) {

			// also see sysext:reports
			// typo3/sysext/reports/reports/status/class.tx_reports_reports_status_configurationstatus.php
			$deprecationLogFilename = t3lib_div::getDeprecationLogFileName();
			if(file_exists($deprecationLogFilename)) {
				$this->deprecationLogFilesize = filesize($deprecationLogFilename);
			}
			return 'enabled';
		}
		return 'unknown';
	}


	/**
	 * Returns status of donation popup setting.
	 * Donation popup was introduced in TYPO3 version 4.4.0 and abandoned again in TYPO3 version 4.5.1 and above
	 *
	 * @access	public
	 * @return	string	Status if deprecation log ('enabled', 'disabled', 'unknown' or 'not-supported')
	 */
	public function getDonationNoticeSetting() {

		// donation popup was introduced in TYPO3 version 4.4.0 (see: http://bugs.typo3.org/view.php?id=13868 )
		// but abandoned again in TYPO3 version 4.5.1 (see: http://bugs.typo3.org/view.php?id=17719 )
		// TYPO3 versions before 4.4.0 and versions 4.5.1 and above do not support deprecation logging.
		if($this->convertVersionNumberToInteger(TYPO3_version) < 4004000) {
			return 'not-supported';
		}
		if($this->convertVersionNumberToInteger(TYPO3_version) >= 4005001) {
			return 'not-supported';
		}

		// if $TYPO3_CONF_VARS['BE']['statusDonationNotice'] is NOT set,
		// donation popup is enabled by default
		if(!isset($GLOBALS['TYPO3_CONF_VARS']['BE']['allowDonateWindow'])) {
			return 'enabled';
		}

		if($GLOBALS['TYPO3_CONF_VARS']['BE']['allowDonateWindow'] === FALSE) {
			return 'disabled';
		}

		// unable to retrieve any information about the donation popup setting
		return 'unknown';
	}


	/**
	 * Returns current amount of DB tables
	 *
	 * @access	public
	 * @return	string	Current amount of DB tables
	 */
	public function getDatabaseDetailsTables() {

		// retrieve the list of tables from the default database, TYPO3_db (quering the DBMS).
		// see: t3lib/class.t3lib_db.php
		return count($this->database->admin_get_tables());
	}


	/**
	 * Returns current amount of DB process
	 * Note: SQL statement 'SHOW PROCESSLIST' is used which requires appropriate access privileges on a database server level.
	 *
	 * @access	public
	 * @return	array	Current amount of DB processes (string) or FALSE (boolean) if an error occured
	 */
	public function getDatabaseDetailsProcesses() {

		$resultPointer = $this->database->sql_query('SHOW PROCESSLIST');
		if(!$resultPointer) {
			return FALSE;
		}
		return $this->database->sql_num_rows($resultPointer);
	}


	/**
	 * Determines the size of current disk usage if possible (requires *nix system)
	 * Note: the real disk usage determined by this method is not 100% reliable. If the web server user (e.g. 'www-data') does not have access to all subdirectories of PATH_site, not all data can be counted.
	 *
	 * @access	public
	 * @return	array	Current amount of DB processes (string) or FALSE (boolean) if an error occured
	 */
	public function getDiskUsage() {

		if ($this->isWindowsSystem() === FALSE) {

			// Note: t3lib_exec::getCommand() return FALSE even if the command was found but is not executable.
			// If open_basedir is enabled, the path to 'du' can be defined in $TYPO3_CONF_VARS[SYS][binSetup] (see Install Tool)
			$diskUsageCommand = t3lib_exec::getCommand('du');

			if (is_string($diskUsageCommand) && !empty($diskUsageCommand)) {

				$diskUsageCommand.= ' --summarize --block-size=1 '.escapeshellarg(PATH_site).' 2>&1';

				if (class_exists(t3lib_utility_Command)) {
					// since TYPO3 version 4.5.0
					$diskUsageResult = t3lib_utility_Command::exec($diskUsageCommand);
				}
				else {
					// TYPO3 versions prior v4.5.0
					t3lib_div::requireOnce(t3lib_extMgm::extPath($this->extKey).'/classes/class.tx_nagios_utility_command.php');
					$objUtilityCommand = t3lib_div::makeInstance('tx_nagios_utility_Command');
					$diskUsageResult = tx_nagios_utility_Command::exec($diskUsageCommand);
				}

				return preg_replace('/^([0-9]*).*$/', '$1', $diskUsageResult);
			}
		}
		// Error: *nix command 'du' not available or not executable
		return 'not-supported';
	}


	/**
	 * Returns TYPO3 site name (urlencoded string) if available
	 *
	 * @access	public
	 * @return	string	TYPO3 site name or 'not-supported' if not available
	 */
	public function getSitename() {

		if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']) && is_string($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']) && !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'])) {
			return urlencode(trim(preg_replace('/[^a-zA-Z0-9\-\. ]/', '', $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'])));
		}
		return 'not-supported';
	}


	/**
	 * Returns server name (as set in web server configuration) or HTTP host name if available
	 * This method tries to get the information via t3lib_div::getIndpEnv() first, then $_SERVER[], because getIndpEnv() offers a limited set of server data only.
	 *
	 * @access	public
	 * @return	string	server name, host name or "not-supported" otherwise
	 */
	public function getServername() {

		$serverVariables = array('SERVER_NAME', 'HTTP_HOST');
		foreach($serverVariables as $variable) {
			$value = t3lib_div::getIndpEnv($variable);
			if (is_string($value) && !empty($value)) {
				return $value;
			}
			else {
				$value = $_SERVER[$variable];
				if (is_string($value) && !empty($value)) {
					return $value;
				}
			}
		}
		return 'not-supported';
	}


	/**
	 * Checks if the operating system PHP is running on is a Microsoft Windows system
	 *
	 * @access	private
	 * @return	boolean		TRUE if system is Microsoft Windows, FALSE otherwise
	 */
	private function isWindowsSystem() {
		return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? TRUE : FALSE;
	}


	/**
	 * Returns an integer from a three part version number, eg '4.12.3' -> 4012003
	 *
	 * @access	public
	 * @param	string		$versionNumber: version number on format x.x.x
	 * @return	integer		integer version of version number (where each part can count to 999)
	 */
	static function convertVersionNumberToInteger($versionNumber) {

		// t3lib_div::int_from_ver() is *deprecated* since TYPO3 4.6 and will be removed in TYPO3 4.9.
		// Use t3lib_utility_VersionNumber::convertVersionNumberToInteger() instead, if possible:
		if(class_exists('t3lib_utility_VersionNumber')) {
			return t3lib_utility_VersionNumber::convertVersionNumberToInteger($versionNumber);
		}
		else {
			return t3lib_div::int_from_ver($versionNumber);
		}
	}


	/**
	 * Extract min or max version from string, e.g. '4.5.0' from '4.5.0-4.6.999' if 'min' (first element) is requested
	 *
	 * @access	private
	 * @param	string		$versionString: version string, e.g. '4.5.0-4.6.999'
	 * @param	integer		$minMax: 0 (or self::TX_NAGIOS_EXTRACTVERSION_MIN) for "minimum" version (default), 1 (or self::TX_NAGIOS_EXTRACTVERSION_MAX) for "maximum" version
	 * @return	mixed		version as 'x.y.z' (string) or FALSE (boolean) if version string could not be extracted
	 */
	private function extractVersion($versionString = '', $minMax = 0) {

		if(isset($versionString) && is_string($versionString) && !empty($versionString)) {
			if(preg_match('/^[0-9\.]{1,}-[0-9\.]{1,}$/', $versionString)) {
				$versionString = explode('-', $versionString);
				if(array_key_exists($minMax, $versionString)) {
					return $versionString[$minMax];
				}
			}
		}
		return FALSE;
	}


	/**
	 * Returns version of Nagios plugin (client) if passed by GET/POST argument
	 *
	 * @access	public
	 * @return	mixed		version as string (e.g. '1.0.0.42' or FALSE if version could not be determined
	 */
	public function getNagiosPluginVersion() {

		$nagiosPluginVersion = t3lib_div::_GP(tx_nagios::TX_NAGIOS_GETPOST_PARAMETER_NAGIOS_PLUGIN_VERSION);
		if(preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $nagiosPluginVersion)) {
			return $nagiosPluginVersion;
		}
		return FALSE;
	}


	/**
	 * Initializes extension configuration array
	 *
	 * @access	public
	 * @return	array		extension configuration array (all values set to FALSE)
	 */
	public function initExtensionConfiguration() {

		return array(
			'featureTYPO3Version'						=> FALSE,
			'featurePHPVersion'							=> FALSE,
			'featureExtensionList'						=> FALSE,
			'featureExtensionDependencyTypo3'			=> FALSE,
			'featureBasicDatabaseDetailsTables'			=> FALSE,
			'featureBasicDatabaseDetailsProcesslist'	=> FALSE,
			'featureDeprecationLog'						=> FALSE,
			'featureDonationNotice'						=> FALSE,
			'featureTimestamp'							=> FALSE,
			'featureCheckDiskUsage'						=> FALSE,
			'securityNagiosServerList'					=> '127.0.0.1',
			'securitySupressHeader'						=> FALSE,

			'featureDebugMode'							=> FALSE
		);
	}
}

/*
if(defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nagios/classes/class.tx_nagios.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nagios/classes/class.tx_nagios.php']);
}
*/

?>
