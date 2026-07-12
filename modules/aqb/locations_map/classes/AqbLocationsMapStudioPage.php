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
 * @defgroup    Locations Map Locations Map
 * @ingroup     UnaModules
 *
 * @{
 */

class AqbLocationsMapStudioPage extends BxTemplStudioModule
{
    protected $oModule;

    function __construct($sModule = "", $sPage = "")
    {
        parent::__construct($sModule, $sPage);

        $this->oModule = BxDolModule::getInstance('aqb_locations_map');
    }

    protected function getSettings() {
        return
            DesignBoxContent('', parent::getSettings(), 10).
            DesignBoxContent(_t('_aqb_locations_map_map_def_location'), $this->oModule->_oTemplate->getAdminMap());
    }

    protected function getPageCaptionHelp()
    {
        $oTemplate = BxDolStudioTemplate::getInstance();
        return $oTemplate->parseHtmlByName('page_caption_help.html', array('content' => _t('_aqb_locations_map_help')));
    }
}

/** @} */
