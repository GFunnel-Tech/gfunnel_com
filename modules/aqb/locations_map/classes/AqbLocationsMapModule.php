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


class AqbLocationsMapModule extends BxBaseModGeneralModule {
    function __construct(&$aModule) {
        parent::__construct($aModule);
    }

    function serviceGetContentTypesOptions() {
        return $this->_oDb->getPossibleLocationsTypes();
    }

    function actionSetDefaultMapView() {
        if (!isAdmin()) die;
        $this->_oDb->setParam('aqb_locations_map_def_lat', doubleval($_POST['lat']));
        $this->_oDb->setParam('aqb_locations_map_def_lng', doubleval($_POST['lng']));
        $this->_oDb->setParam('aqb_locations_map_def_zoom', intval($_POST['zoom']));
    }

    function serviceGetMapBlock() {
        $iProfileID = bx_get_logged_profile_id();
        $aCheck = checkActionModule($iProfileID, 'view map', $this->_aModule['name'], true);
        if($aCheck[CHECK_ACTION_RESULT] != CHECK_ACTION_RESULT_ALLOWED) return MsgBox($aCheck[CHECK_ACTION_MESSAGE]);

        $sTypes = $this->_oDb->getParam('aqb_locations_map_content_types');
        $aTypes = explode(',', $sTypes);
        if ($aTypes) foreach ($aTypes as $sType) {
            if ($sType == 'online_profiles') $this->_oTemplate->addJsTranslation('_aqb_locations_map_online_profiles');
            $this->_oTemplate->addJsTranslation('_'.$sType);
        }

        return $this->_oTemplate->getMap();
    }

    function actionGetLocations() {
        $iProfileID = bx_get_logged_profile_id();
        $aCheck = checkActionModule($iProfileID, 'view map', $this->_aModule['name'], true);
        if($aCheck[CHECK_ACTION_RESULT] != CHECK_ACTION_RESULT_ALLOWED) return json_encode(array());

        $iRandSeed = intval($_REQUEST['randseed']);

        echo json_encode($this->_oDb->getLocations($iRandSeed));
    }

    function actionGetLocationInfo($fLat, $fLng, $sFilter) {
        $fLat = floatval($fLat);
        $fLng = floatval($fLng);
        echo $this->_oTemplate->getLocationInfo($fLat, $fLng, $sFilter);
    }
}

/** @} */
