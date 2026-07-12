<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    News News
 * @ingroup     ModzzzModules
 *
 * @{
 */

/**
 * Profile's entries page.
 */
class MzNewsPageArchive extends BxTemplPage
{

    public function __construct($aObject, $oTemplate = false)
    {
        parent::__construct($aObject, $oTemplate);

        $this->_oModule = BxDolModule::getInstance($this->MODULE);

        // select module submenu
        if (isset($this->_oModule->_oConfig->CNF['OBJECT_MENU_SUBMENU'])){
            $oMenuSubmenu = BxDolMenu::getObjectInstance('sys_site_submenu');
            if($oMenuSubmenu)
                $oMenuSubmenu->setObjectSubmenu($this->_oModule->_oConfig->CNF['OBJECT_MENU_SUBMENU']);
        } 
    } 

}

/** @} */

 