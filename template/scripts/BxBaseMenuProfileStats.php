<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaBaseView UNA Base Representation Classes
 * @{
 */

/**
 * Menu representation.
 * @see BxDolMenu
 */
class BxBaseMenuProfileStats extends BxTemplMenuAccountNotifications
{
    protected $_iMenuItemsMin;

    public function __construct ($aObject, $oTemplate)
    {
        parent::__construct ($aObject, $oTemplate);

        $this->_bDisplayAddons = true;
        $this->_iMenuItemsMin = 10;
    }

    public function getMenuItems ()
    {
        $aItems = parent::getMenuItems();
        if(empty($aItems) || !is_array($aItems) || $this->_bIsApi)
            return $aItems;

        return $this->_addMenuItemsMoreLess($aItems, $this->_iMenuItemsMin);
    }

    protected function getMenuItemsRaw ()
    {
        return $this->getMenuItemsRawProfileRelated();
    }

    protected function _getMenuItem($a)
    {
        $mixedResult = parent::_getMenuItem($a);

        if($mixedResult !== false && !empty($mixedResult['link']) && strpos($mixedResult['link'], 'javascript:') === false)
            $mixedResult['link'] = bx_append_url_params($mixedResult['link'], [
                'owner' => 1
            ]);

        return $mixedResult;
    }
}

/** @} */
