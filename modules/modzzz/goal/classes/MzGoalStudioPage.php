<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Goal Goal
 * @ingroup     ModzzzModules
 *
 * @{
 */

define('BX_DOL_STUDIO_MOD_TYPE_PETS', 'goals');

define('BX_DOL_STUDIO_MOD_TYPE_CATEGORIES', 'categories');

class MzGoalStudioPage extends BxTemplStudioModule
{
    protected $_sModule;
    protected $_oModule;

    function __construct($sModule, $mixedPageName, $sPage = "")
    {
    	$this->_sModule = 'mz_goal';
    	$this->_oModule = BxDolModule::getInstance($this->_sModule);

        parent::__construct($sModule, $mixedPageName, $sPage);

        $oPermalink = BxDolPermalinks::getInstance();

        $this->aMenuItems[BX_DOL_STUDIO_MOD_TYPE_PETS] = array('name' => BX_DOL_STUDIO_MOD_TYPE_PETS , 'icon' => 'bars', 'title' => '_mz_goal_menu_item_title_manage_goals', 'link' => BX_DOL_URL_ROOT . $oPermalink->permalink('page.php?i=goal-administration'));

        $this->aMenuItems[BX_DOL_STUDIO_MOD_TYPE_CATEGORIES] = array('name' => BX_DOL_STUDIO_MOD_TYPE_CATEGORIES, 'icon' => 'bars', 'title' => '_mz_goal_menu_item_title_categories');  
    }

    protected function getCategories()
    {
        $oGrid = BxDolGrid::getObjectInstance($this->_oModule->_oConfig->CNF['OBJECT_GRID_CATEGORIES'], BxDolStudioTemplate::getInstance());
        if(!$oGrid)
            return '';

        $this->_oModule->_oTemplate->addJs('studio');
        return $this->_oModule->_oTemplate->getJsCode('studio') . $oGrid->getCode();
    }
}

/** @} */
