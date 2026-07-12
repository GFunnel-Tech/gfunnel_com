<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Listing Listing
 * @ingroup     ModzzzModules
 *
 * @{
 */

define('BX_DOL_STUDIO_MOD_TYPE_MANAGE', 'listings');
define('BX_DOL_STUDIO_MOD_TYPE_CLAIMS', 'claims'); 
define('BX_DOL_STUDIO_MOD_TYPE_CATEGORIES', 'categories');

class MzListingStudioPage extends BxTemplStudioModule
{
    protected $_sModule;
    protected $_oModule;

    function __construct($sModule, $mixedPageName, $sPage = "")
    {
    	$this->_sModule = 'mz_listing';
    	$this->_oModule = BxDolModule::getInstance($this->_sModule);

        parent::__construct($sModule, $mixedPageName, $sPage);

        $oPermalink = BxDolPermalinks::getInstance();

        $this->aMenuItems[BX_DOL_STUDIO_MOD_TYPE_MANAGE] = array('name' => BX_DOL_STUDIO_MOD_TYPE_MANAGE , 'icon' => 'bars', 'title' => '_mz_listing_menu_item_title_manage_listings', 'link' => BX_DOL_URL_ROOT . $oPermalink->permalink('page.php?i=listing-administration'));

        $this->aMenuItems[BX_DOL_STUDIO_MOD_TYPE_CLAIMS] = array('name' => BX_DOL_STUDIO_MOD_TYPE_CLAIMS , 'icon' => 'bars', 'title' => '_mz_listing_menu_item_title_manage_claims', 'link' => BX_DOL_URL_ROOT . $oPermalink->permalink('page.php?i=listing-claims'));

        $this->aMenuItems[BX_DOL_STUDIO_MOD_TYPE_CATEGORIES] = array('name' => BX_DOL_STUDIO_MOD_TYPE_CATEGORIES, 'icon' => 'bars', 'title' => '_mz_listing_menu_item_title_categories');  
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
