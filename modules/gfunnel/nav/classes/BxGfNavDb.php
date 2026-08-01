<?php
/**
 * GFunnel Nav — database access.
 *
 * `gfn_items`      : global default items (admin-editable).
 * `gfn_user_items` : per-member, per-workspace overrides (hide / reorder /
 *                    custom-added).
 */

defined('BX_DOL') or die('hack attempt');

class BxGfNavDb extends BxDolModuleDb
{
    protected $_sTblItems;
    protected $_sTblUserItems;

    function __construct(&$oConfig)
    {
        parent::__construct($oConfig);

        $this->_sTblItems = $this->_sPrefix . 'items';
        $this->_sTblUserItems = $this->_sPrefix . 'user_items';
    }

    /**
     * Global default items (active only), in order.
     */
    public function getItems()
    {
        return $this->getAll("SELECT * FROM `{$this->_sTblItems}` WHERE `active` = 1 ORDER BY `order` ASC, `id` ASC");
    }

    /**
     * A member's overrides for a workspace, keyed by the item key (and the
     * custom rows appended).
     */
    public function getUserItems($iAccountId, $iWorkspaceId)
    {
        return $this->getAll(
            "SELECT * FROM `{$this->_sTblUserItems}` WHERE `account_id` = :account AND `workspace_id` = :ws ORDER BY `order` ASC, `id` ASC",
            array('account' => (int)$iAccountId, 'ws' => (int)$iWorkspaceId)
        );
    }

    /**
     * Hide/show a default item for a member (upsert on the item key).
     */
    public function setHidden($iAccountId, $iWorkspaceId, $sItem, $bHidden)
    {
        return (bool)$this->query(
            "INSERT INTO `{$this->_sTblUserItems}` (`account_id`, `workspace_id`, `item`, `hidden`) VALUES (:account, :ws, :item, :hidden)
             ON DUPLICATE KEY UPDATE `hidden` = :hidden",
            array('account' => (int)$iAccountId, 'ws' => (int)$iWorkspaceId, 'item' => $sItem, 'hidden' => $bHidden ? 1 : 0)
        );
    }

    /**
     * Add a member's own custom link.
     */
    public function addCustom($iAccountId, $iWorkspaceId, $sTitle, $sUrl, $sIcon = 'dot')
    {
        return (bool)$this->query(
            "INSERT INTO `{$this->_sTblUserItems}` (`account_id`, `workspace_id`, `item`, `custom`, `title`, `url`, `icon`, `order`)
             VALUES (:account, :ws, NULL, 1, :title, :url, :icon, 9000)",
            array('account' => (int)$iAccountId, 'ws' => (int)$iWorkspaceId, 'title' => $sTitle, 'url' => $sUrl, 'icon' => $sIcon)
        );
    }

    /**
     * Delete a member's custom row by its id (own rows only).
     */
    public function deleteUserRow($iAccountId, $iWorkspaceId, $iId)
    {
        return (bool)$this->query(
            "DELETE FROM `{$this->_sTblUserItems}` WHERE `id` = :id AND `account_id` = :account AND `workspace_id` = :ws AND `custom` = 1",
            array('id' => (int)$iId, 'account' => (int)$iAccountId, 'ws' => (int)$iWorkspaceId)
        );
    }

    /**
     * Persist a member's item order. $aKeys is the ordered list of item keys
     * ('key' for defaults, 'custom:<id>' for the member's own rows).
     */
    public function setOrder($iAccountId, $iWorkspaceId, $aKeys)
    {
        $iOrder = 0;
        foreach($aKeys as $sKey) {
            $iOrder += 10;
            if(strpos($sKey, 'custom:') === 0) {
                $iId = (int)substr($sKey, 7);
                $this->query(
                    "UPDATE `{$this->_sTblUserItems}` SET `order` = :order WHERE `id` = :id AND `account_id` = :account AND `workspace_id` = :ws",
                    array('order' => $iOrder, 'id' => $iId, 'account' => (int)$iAccountId, 'ws' => (int)$iWorkspaceId)
                );
            } else {
                $this->query(
                    "INSERT INTO `{$this->_sTblUserItems}` (`account_id`, `workspace_id`, `item`, `order`) VALUES (:account, :ws, :item, :order)
                     ON DUPLICATE KEY UPDATE `order` = :order",
                    array('account' => (int)$iAccountId, 'ws' => (int)$iWorkspaceId, 'item' => $sKey, 'order' => $iOrder)
                );
            }
        }
        return true;
    }

    /**
     * Drop all of a member's overrides for a workspace (back to defaults).
     */
    public function resetUser($iAccountId, $iWorkspaceId)
    {
        return (bool)$this->query(
            "DELETE FROM `{$this->_sTblUserItems}` WHERE `account_id` = :account AND `workspace_id` = :ws",
            array('account' => (int)$iAccountId, 'ws' => (int)$iWorkspaceId)
        );
    }
}
