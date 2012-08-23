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
 * public function about($PA, $fobj)
 *	[...]
 *
 */

/**
 * Dynamic data generation for flexforms in TYPO3 backend
 *
 * @author		Michael Schams <schams.net>
 * @package		TYPO3
 * @subpackage	tx_nagios
 */
class tx_nagios_flexforms {

	var $scriptRelPath = 'classes/class.tx_nagios_flexforms.php';
	var $extKey        = 'nagios';

	/**
	 * *TODO* [...]
	 *
	 * @param	string		$PA: *TODO* [...]
	 * @param	string		$fobj: *TODO* [...]
	 * @return	string		content (HTML) as shown in TYPO3 backend
	 */
	function deprecated($PA, $fobj) {

		$content = "
			<style type=\"text/css\">
				div#nagiosDeprecatedWrap {
					border: 1px solid;
					background-color: #ececec;
				}
				div#nagiosDeprecatedWrap div.nagiosText {
					text-align: center;
					padding: 10px 20px;
				}

				div#nagiosDeprecatedWrap div.nagiosText p {
					text-align: center;
					padding: 0;
					margin: 0;
				}

				div#nagiosDeprecatedWrap div.nagiosText p span.important {
					text-decoration: underline;
					text-transform: uppercase;
					font-weight: bold;
				}
			</style>
			<div id=\"nagiosDeprecatedWrap\">
				<div class=\"nagiosText\">
					<p><span class=\"important\">Important:</span></p>
					<p>Please note that the use of this plugin as a frontend extension is <strong>deprecated</strong>.</p>
					<p>The version you are using already supports the so called &quot;eID&quot; method.</p>
					<p>We will drop the frontend functionality soon and you should update your configuration.</p>
					<p>Read more at <a href=\"http://schams.net/nagios\" target=\"_blank\" title=\"schams.net\">http://schams.net/nagios</a></p>
				</div>
			</div>";
		return $content;
	}

	/**
	 * *TODO* [...]
	 *
	 * @param	string		$PA: *TODO* [...]
	 * @param	string		$fobj: *TODO* [...]
	 * @return	string		content (HTML) as shown in TYPO3 backend
	 */
	function about($PA, $fobj) {

		$extensionDetails = array();

		// *TODO* redundant code, see: pi1/class.tx_nagios_pi1.php
		$emconfPath = t3lib_extMgm::extPath($this->extKey).'ext_emconf.php';
		if(is_readable($emconfPath)) {
			$_EXTKEY = $this->extKey;
			include($emconfPath);
			if(isset($EM_CONF[$this->extKey]) && is_array($EM_CONF[$this->extKey])) {
				$extensionDetails = $EM_CONF[$this->extKey];
			}
		}

		$content = "
			<style type=\"text/css\">
				div#nagiosAboutWrap {
					border: 1px solid;
				}

				div#nagiosAboutWrap div#imageBackground {
					height: 50px;
					background: #dddddd url(".t3lib_extMgm::extRelPath($this->extKey)."/res/images/schams-net.png) no-repeat right center;
					border-bottom: 1px solid black;
				}

				div#nagiosAboutWrap div#imageBackground img#logoNagios {
					width: 170px;
					height: 50px;
					border: none;
					margin-left: 5px;
				}

				div#nagiosAboutWrap div.nagiosText {
					text-align: center;
					padding: 10px 0px 20px 0px;
				}

				div#nagiosAboutWrap div.nagiosText p {
					text-align: center;
				}
			</style>
			<div id=\"nagiosAboutWrap\">
				<div id=\"imageBackground\">
					<img id=\"logoNagios\" src=\"".t3lib_extMgm::extRelPath($this->extKey)."/res/images/nagios.png\" alt=\"Nagios\" title=\"Nagios\" />
				</div>
				<div style=\"clear: both\"></div>
				<div class=\"nagiosText\">
					<p>Nagios&reg; - The Industry Standard In Open Source Monitoring.</p>
					<p>Originally developed by Ethan Galstad and licensed under the GNU GPL V2.</p>
					<p><a href=\"http://www.nagios.org\" target=\"_blank\" title=\"Nagios\">http://www.nagios.org</a></p>
					<p>&nbsp;</p>
					<p>The TYPO3 Nagios&reg; Extension was developed by Michael Schams (schams.net).</p>
					<p>You can redistribute it and/or modify it under the terms of the GNU GPL V2</p>
					<p>as long as the original author, including the web site below, is clearly mentioned.</p>
					<p>Any feedback, comments and suggestions are highly welcome.</p>
					<p><a href=\"http://schams.net\" target=\"_blank\" title=\"schams.net\">http://schams.net</a></p>
					<p>&nbsp;</p>
					<p>[TYPO3 Version ".TYPO3_version.", TYPO3 Nagios&reg; Extension Version ".$extensionDetails['version']."]</p>
				</div>
			</div>";
		return $content;
	}
}

?>
