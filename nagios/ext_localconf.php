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

if(!defined ('TYPO3_MODE')) {
 	die('Access denied.');
}

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_nagios_pi1.php', '_pi1', 'list_type', 0);

// eID
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['nagios'] = 'EXT:'.$_EXTKEY.'/classes/class.tx_nagios_eid.php';

// extend hook for showing admin error messages in TYPO3 backend
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages'][] = 'EXT:nagios/classes/class.tx_nagios_befunc.php:tx_nagios_befunc';

?>
