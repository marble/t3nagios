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
 * public static function exec($command, &$output = NULL, &$returnValue = 0)
 *	Wrapper function for PHP exec() (original: t3lib/utility/class.t3lib_utility_command.php since TYPO3 v4.5.0)
 *
 */

/**
 * Class to handle system commands.
 *
 * @author		Steffen Kamper <steffen@typo3.org>
 * @author		Michael Schams <schams.net>
 * @package		TYPO3
 * @subpackage	tx_nagios
 */
class tx_nagios_utility_Command {

	var $scriptRelPath = 'classes/class.tx_nagios_utility_command.php';
	var $extKey        = 'nagios';

	/**
	 * Wrapper function for PHP exec() function
	 * Needs to be central to have better control and possible fix for issues
	 * (original: t3lib/utility/class.t3lib_utility_command.php since TYPO3 v4.5.0)
	 *
	 * @static
	 * @param 	string			$command
	 * @param	NULL/array		$output
	 * @param	integer			$returnValue
	 * @return	NULL/array
	 */
	public static function exec($command, &$output = NULL, &$returnValue = 0) {
		$lastLine = exec($command, $output, $returnValue);
		return $lastLine;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nagios/classes/class.tx_nagios_utility_command.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nagios/classes/class.tx_nagios_utility_command.php']);
}

?>
