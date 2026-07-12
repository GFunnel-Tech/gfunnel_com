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

bx_import('BxDolStudioInstaller');

class AqbLocationsMapInstaller extends BxDolStudioInstaller {
    function __construct($aConfig) {
        parent::__construct($aConfig);
    }

    protected function actionSetDefaultTypes($sOperation) {
    	$oModule = BxDolModule::getInstance('aqb_locations_map');
    	$oModule->_oDb->setDefaultTypesOption();
    	return BX_DOL_STUDIO_INSTALLER_SUCCESS;
    }
}

/** @} */
