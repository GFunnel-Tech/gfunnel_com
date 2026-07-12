<?php defined('BX_DOL') or die('hack attempt');
/**
 *     copyright            : (C) 2019 AQB Soft
 *     website              : http://www.aqbsoft.com
 *
 * IMPORTANT: This is a commercial product made by AQB Soft. It cannot be modified for other than personal usage.
 * The "personal usage" means the product can be installed and set up for ONE domain name ONLY.
 * To be able to use this product for another domain names you have to order another copy of this product (license).
 *
 * This product cannot be redistributed for free or a fee without written permission from AQB Soft.
 *
 * This notice may not be removed from the source code.
 *
 * @defgroup    Advanced Menu Advanced Menu
 * @ingroup     UnaModules
 *
 * @{
 */


class AqbAdvancedMenuDb extends BxBaseModGeneralDb {
	function __construct(&$oConfig) {
		parent::__construct($oConfig);
    }

    function getEntryDetails($iEntry) {
	    return $this->getRow("SELECT `id`, `name`, `menu_item_name`, `menu_item_eval` FROM `aqb_advanced_menu_items` WHERE `id` = :id", ['id' => $iEntry]);
    }

    function insertEntry($aData) {
	    $this->query("
            INSERT INTO `aqb_advanced_menu_items` SET 
                `name` = :name,
                `menu_item_name` = :menu_item_name,
                `menu_item_eval` = :menu_item_eval
            ", [
            'name' => $aData['name'],
            'menu_item_name' => $aData['menu_item_name'],
            'menu_item_eval' => $aData['menu_item_eval'],
        ]);

	    return $this->lastId();
    }

    function updateEntry($iEntry, $aData) {
	    return $this->query("
            UPDATE `aqb_advanced_menu_items` SET 
                `name` = :name,
                `menu_item_name` = :menu_item_name,
                `menu_item_eval` = :menu_item_eval
            WHERE `id` = :id", [
            'id' => $iEntry,
            'name' => $aData['name'],
            'menu_item_name' => $aData['menu_item_name'],
            'menu_item_eval' => $aData['menu_item_eval'],
        ]);
    }

    function deleteEntry($iEntry) {
	    return $this->query("DELETE FROM `aqb_advanced_menu_items` WHERE `id` = :id", ['id' => $iEntry]);
    }

    function getActiveEntries() {
	    return $this->getPairs("SELECT `menu_item_name`, IF(`active`, `menu_item_eval`, 'return \' \';') AS `menu_item_eval` FROM `aqb_advanced_menu_items`", 'menu_item_name', 'menu_item_eval');
    }
}

/** @} */
