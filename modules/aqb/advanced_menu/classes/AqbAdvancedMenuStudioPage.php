<?php defined('BX_DOL') or die('hack attempt');
/**
 *     copyright            : (C) 2021 AQB Soft
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
 * @defgroup    Advanced Menu Advanced Menu
 * @ingroup     UnaModules
 *
 * @{
 */

class AqbAdvancedMenuStudioPage extends BxTemplStudioModule
{
    protected $oModule;

    function __construct($sModule, $mixedPageName, $sPage = "")
    {
        parent::__construct($sModule, $mixedPageName, $sPage);

        $this->oModule = BxDolModule::getInstance('aqb_advanced_menu');

        $this->aMenuItems = array(
            'main' => array('name' => 'main', 'icon' => 'cogs', 'title' => '_aqb_advanced_menu'),
        );
    }

    protected function getMain() {
        bx_import('BxTemplGrid');
        $oGrid = BxDolGrid::getObjectInstance('aqb_advanced_menu', BxDolStudioTemplate::getInstance());
        if (!$oGrid) return 'Module needs to be reinstalled';

        $oStudioTemplate = BxDolStudioTemplate::getInstance();
        $oStudioTemplate->addCss(BX_DIRECTORY_PATH_PLUGINS_PUBLIC . 'codemirror/|codemirror.css');
        $oStudioTemplate->addJs('codemirror/codemirror.min.js');

        return
            DesignBoxContent(_t('_aqb_advanced_menu_warning'), _t('_aqb_advanced_menu_warning_text')).
            '<div style="margin-top: 50px;"></div>'.
            DesignBoxContent(_t('_aqb_advanced_menu_custom_items'), $oGrid->getCode()).
            DesignBoxContent(_t('_aqb_advanced_menu_next_steps'), _t('_aqb_advanced_menu_next_steps_cont', BX_DOL_URL_ROOT));
    }
}

/** @} */
