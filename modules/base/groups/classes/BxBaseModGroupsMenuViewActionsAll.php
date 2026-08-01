<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    BaseGroups Base classes for groups modules
 * @ingroup     UnaModules
 *
 * @{
 */

/**
 * View entry all actions menu
 */
class BxBaseModGroupsMenuViewActionsAll extends BxBaseModProfileMenuViewActionsAll
{
    public function __construct($aObject, $oTemplate = false)
    {
        parent::__construct($aObject, $oTemplate);
        
        $CNF = &$this->_oModule->_oConfig->CNF;
        
        if(isset($CNF['OBJECT_CONNECTIONS']))
            $this->_aConnectionToFunctionCheck[$CNF['OBJECT_CONNECTIONS']] = [
                'fans' => 'checkAllowedFans', 
                'add' => 'checkAllowedFanAdd', 
                'remove' => 'checkAllowedFanRemove'
            ];

        if(($iContextPid = $this->_aContentInfo['profile_id']) && $this->_oModule->isPaidJoinByProfile($iContextPid)) {
            $aDefault = $this->_oModule->_oDb->getPrices(['type' => 'by_profile_id', 'profile_id' => $iContextPid, 'default' => true]);
            if(empty($aDefault) || !is_array($aDefault)) {
                $aPrices = $this->_oModule->_oDb->getPrices(['type' => 'by_profile_id', 'profile_id' => $iContextPid]);
                if(!empty($aPrices) && is_array($aPrices))
                    $aDefault = reset($aPrices);
            }

            if(!empty($aDefault) && is_array($aDefault))
                $this->addMarkers([
                    'title_pay_and_join' => _t($CNF['T']['menu_item_title_pay_and_join'], $this->_oModule->_oConfig->getPriceTitle($this->_aContentInfo[$CNF['FIELD_AUTHOR']], $aDefault))
                ]);
        }
    }

    protected function _isContentPublic($iContentId, $aPublicGroups = [])
    {
        return parent::_isContentPublic($iContentId, [BX_DOL_PG_ALL, BX_DOL_PG_MEMBERS, 'c']);
    }

    protected function _getMenuItemProfileFans($aItem, $aParams = [])
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if(empty($CNF['OBJECT_CONNECTIONS']))
            return false;

        return $this->_getMenuItemConnection($aItem, array_merge($aParams, [
            'object' => $CNF['OBJECT_CONNECTIONS']
        ]));
    }

    protected function _getMenuItemProfileFanAdd($aItem)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
        
        $mixedResult = $this->_getMenuItemByNameActions($aItem);
        if(!$mixedResult)
            return $mixedResult;

        if($this->_bIsApi && $CNF['OBJECT_CONNECTIONS'])
            return $this->_getMenuItemConnectionApi($CNF['OBJECT_CONNECTIONS'], 'add', $aItem);

        return $mixedResult;
    }

    protected function _getMenuItemProfileFanAddPaid($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }

    protected function _getMenuItemProfileFanRemove($aItem)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
        
        $mixedResult = $this->_getMenuItemByNameActions($aItem);
        if(!$mixedResult)
            return $mixedResult;

        if($this->_bIsApi && $CNF['OBJECT_CONNECTIONS'])
            return $this->_getMenuItemConnectionApi($CNF['OBJECT_CONNECTIONS'], 'remove', $aItem);

        return $mixedResult;
    }
}

/** @} */
