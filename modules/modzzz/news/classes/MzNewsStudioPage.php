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

define('BX_DOL_STUDIO_MOD_TYPE_NEWS', 'news');

class MzNewsStudioPage extends BxTemplStudioModule
{
    protected $_sModule;
	protected $_oModule;
    
    function __construct($sModule, $mixedPageName, $sPage = "")
    {
    	$this->_sModule = 'mz_news';
    	$this->_oModule = BxDolModule::getInstance($this->_sModule);

        $oPermalink = BxDolPermalinks::getInstance();

        $this->aMenuItems['news-administration'] = array('name' => BX_DOL_STUDIO_MOD_TYPE_NEWS , 'icon' => 'bars', 'title' => '_mz_news_menu_item_news', 'link' => BX_DOL_URL_ROOT . $oPermalink->permalink('page.php?i=news-administration'));
 
        $this->aMenuItems['create-news'] = array('name' => 'create-news' , 'icon' => 'plus', 'title' => '_mz_news_studio_menu_item_title_create_entry', 'link' => BX_DOL_URL_ROOT . $oPermalink->permalink('page.php?i=create-news'));
 
        parent::__construct($sModule, $mixedPageName, $sPage);
    }


}

/** @} */
