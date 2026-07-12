<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Auto Friends Auto Friends
 * @ingroup     UNAModules
 *
 * @{
 */

class AqbAfStudioPage extends BxTemplStudioModule
{
    protected $_sModule;
    protected $_oModule;

    function __construct($sModule, $mixedPageName, $sPage = "")
    {
        $this->_sModule = 'aqb_auto_friends';
        $this->_oModule = BxDolModule::getInstance($this->_sModule);
        
        parent::__construct($sModule, $mixedPageName, $sPage);

        $this->aMenuItems = array_merge($this->aMenuItems, array(
            'friends' => array('name' => 'friends', 'icon' => 'users', 'title' => '_aqb_auto_friends_menu_item_title_friends'),
        ));
    }

    public function getFriends()
    {
        $oGrid = BxDolGrid::getObjectInstance($this->_oModule->_oConfig->CNF['OBJECT_GRID_FRIENDS'], BxDolStudioTemplate::getInstance());
        if(!$oGrid)
            return '';

        return $oGrid->getCode();
    }
}

/** @} */
