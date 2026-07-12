<?php defined('BX_DOL') or die('hack attempt');
/**
 *     copyright            : (C) 2018 AQB Soft
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
 * @defgroup    Personal Bookmarks Personal Bookmarks
 * @ingroup     UnaModules
 *
 * @{
 */


class AqbPersonalBookmarksDb extends BxBaseModGeneralDb {
	function __construct(&$oConfig) {
		parent::__construct($oConfig);
    }

    function deleteAccountBookmarks($iAccount) {
    	$sQuery = $this->prepare("DELETE FROM `".$this->getPrefix()."_entries` WHERE `owner_id` = ?", $iAccount);
		return $this->query($sQuery);
	}

	function getAccountBookmarks($iAccount) {
		$sQuery = $this->prepare("SELECT `id`, `name`, `url` FROM `".$this->getPrefix()."_entries` WHERE `owner_id` = ? ORDER BY `order` ASC, `name` ASC", $iAccount);
		return $this->getAll($sQuery);
	}

	function addAccountBookmark($iAccount, $sUrl, $sName) {
		$sQuery = $this->prepare("INSERT INTO `".$this->getPrefix()."_entries` (`owner_id`, `url`, `name`) VALUES (?, ?, ?)", $iAccount, $sUrl, $sName);
        $this->query($sQuery);
	}

	function deleteAccountBookmark($iAccount, $iId) {
		$sQuery = $this->prepare("DELETE FROM `".$this->getPrefix()."_entries` WHERE `id` = ? AND `owner_id` = ? LIMIT 1", $iId, $iAccount);
		$this->query($sQuery);
	}

	function updateOrder($aOrder, $iProfile) {
		foreach ($aOrder as $iOrder => $iID) {
			$sQuery = $this->prepare("UPDATE `".$this->getPrefix()."_entries` SET `order` = ? WHERE `id` = ? AND `owner_id` = ? LIMIT 1", $iOrder, $iID, $iProfile);
			$this->query($sQuery);
		}
	}
}

/** @} */
