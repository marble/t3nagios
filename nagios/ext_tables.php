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

if (!defined ('TYPO3_MODE')) {
	die('Access denied.');
}

// include additional class for dynamic flexform generation
include_once(t3lib_extMgm::extPath($_EXTKEY).'classes/class.tx_nagios_flexforms.php');

// load default TCA values for tt_content
t3lib_div::loadTCA('tt_content');

// 'layout' excludes drop-down box for selecting Normal, Layout 1, Layout 2, Layout 3
// 'select_key' excludes input field for entering "CODE"
// 'pages' excludes section "Startingpoint"
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1'] = 'layout,select_key,pages';

// enable flexforms for BE rendering
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1'] = 'pi_flexform';

// add flexform configuration
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:'.$_EXTKEY.'/res/flexforms/nagios_pi1.xml');

// add extension name to plugin list (BE)
t3lib_extMgm::addPlugin(array('LLL:EXT:nagios/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1', t3lib_extMgm::extRelPath($_EXTKEY).'ext_icon.gif'), 'list_type');

// add constants.txt an setup.txt
t3lib_extMgm::addStaticFile($_EXTKEY, 'res/nagios_monitoring/', 'Nagios Monitoring');

?>
