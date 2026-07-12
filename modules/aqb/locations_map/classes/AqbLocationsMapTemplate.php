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

bx_import('BxBaseFormView');

class AqbLocationsMapTemplate extends BxBaseModGeneralTemplate {
	function __construct(&$oConfig, &$oDb) {
	    parent::__construct($oConfig, $oDb);
    }

    function checkAPIKeyAvailability($sMapProvider, &$sAPIKey) {
        $sMapProvider = $this->_oDb->getParam('aqb_locations_map_map_provider');
        $sAPIKey = '';
        if ($sMapProvider == 'Google') {
            $sAPIKey = $this->_oDb->getParam('aqb_locations_map_google_map_api_key');
            if (!$sAPIKey) return MsgBox(_t('_aqb_locations_map_google_api_key_missing'));
        } elseif (strncmp($sMapProvider, 'Mapbox', 6) == 0) {
            $sAPIKey = $this->_oDb->getParam('aqb_locations_map_mapbox_map_api_key');
            if (!$sAPIKey) return MsgBox(_t('_aqb_locations_map_mapbox_api_key_missing'));
        } elseif (strncmp($sMapProvider, 'Thunderforest', 13) == 0) {
            $sAPIKey = $this->_oDb->getParam('aqb_locations_map_thunderforest_map_api_key');
            if (!$sAPIKey) return MsgBox(_t('_aqb_locations_map_thunderforest_api_key_missing'));
        }
        return false;
    }

    function getAdminMap() {
        $sMapProvider = $this->_oDb->getParam('aqb_locations_map_map_provider');

        $sAPIKey = '';
        $sErrorMessage = $this->checkAPIKeyAvailability($sMapProvider, $sAPIKey);
        if ($sErrorMessage) return $sErrorMessage;

        $oMapOptions = array();
        if ($sMapProvider != 'Google') {
            $oMapOptions = $this->_oConfig->getOSMMapProvider($sMapProvider, $sAPIKey);
            if (!$oMapOptions) return MsgBox(_t('_aqb_locations_map_unknown_map_provider'));
        }

        $oMapOptions += array(
            'zoom' => intval($this->_oDb->getParam('aqb_locations_map_def_zoom')),
            'lat' => doubleval($this->_oDb->getParam('aqb_locations_map_def_lat')),
            'lng' => doubleval($this->_oDb->getParam('aqb_locations_map_def_lng')),
            'admin_mode' => 1,
        );


        if ($sMapProvider == 'Google') {
            $this->addStudioJs('https://maps.googleapis.com/maps/api/js?v=3.exp&key='.$sAPIKey);
            $this->addStudioJs('maps_google.js');
        } else {
            $this->addStudioCss('https://unpkg.com/leaflet@1.3.4/dist/leaflet.css');
            $this->addStudioJs('https://unpkg.com/leaflet@1.3.4/dist/leaflet.js');
            $this->addStudioJs('maps_osm.js');
        }

        return $this->parseHtmlByName('map_admin.html', array(
            'bx_if:map_google' => array(
                'condition' => $sMapProvider == 'Google',
                'content' => array('api_key' => $sAPIKey),
            ),
            'bx_if:map_osm' => array(
                'condition' => $sMapProvider != 'Google',
                'content' => array(),
            ),
            'options' => json_encode($oMapOptions),
            'api_key' => $sAPIKey,
        ));
    }

    function getMap() {
        $this->addCss('main.css');

    	$sMapProvider = $this->_oDb->getParam('aqb_locations_map_map_provider');

        $sAPIKey = '';
        $sErrorMessage = $this->checkAPIKeyAvailability($sMapProvider, $sAPIKey);
        if ($sErrorMessage) return $sErrorMessage;

        $oMapOptions = array();
        if ($sMapProvider != 'Google') {
            $oMapOptions = $this->_oConfig->getOSMMapProvider($sMapProvider, $sAPIKey);
            if (!$oMapOptions) return MsgBox(_t('_aqb_locations_map_unknown_map_provider'));
        }

        $oMapOptions += array(
            'zoom' => intval($this->_oDb->getParam('aqb_locations_map_def_zoom')),
            'lat' => doubleval($this->_oDb->getParam('aqb_locations_map_def_lat')),
            'lng' => doubleval($this->_oDb->getParam('aqb_locations_map_def_lng')),
            'randseed' => rand(),
            'max_markers' => intval($this->_oDb->getParam('aqb_locations_map_max_locations')),
        );


        if ($sMapProvider == 'Google') {
            $this->addJs('https://maps.googleapis.com/maps/api/js?v=3.exp&key='.$sAPIKey);
            $this->addJs('maps_google.js');
        } else {
            $this->addCss('https://unpkg.com/leaflet@1.3.4/dist/leaflet.css');
            $this->addJs('https://unpkg.com/leaflet@1.3.4/dist/leaflet.js');
            $this->addJs('maps_osm.js');
        }

        $oMapOptions['locations_icons'] = array();
        $aFilters = array();
        $sTypesSet = $this->_oDb->getParam('aqb_locations_map_content_types');
        $aTypesSet = $sTypesSet ? explode(',', $sTypesSet) : false;
        if ($aTypesSet) {
            $aPossibleTypes = $this->_oDb->getPossibleLocationsTypes();
            foreach ($aPossibleTypes as $sType => $dummy) {
                if (!in_array($sType, $aTypesSet)) unset($aPossibleTypes[$sType]);

                if ($this->_oDb->getParam('aqb_locations_map_custom_icons')) {
                    if ($sType != 'online_profiles') {
                        $oModule = BxDolModule::getInstance($sType);
                        $oMapOptions['locations_icons'][$sType] = $oModule->_oTemplate->getIconUrl('std-icon.svg');
                        if (!$oMapOptions['locations_icons'][$sType]) $oMapOptions['locations_icons'][$sType] = $oModule->_oTemplate->getIconUrl('std-mi.png');
                    } else {
                        $oMapOptions['locations_icons'][$sType] = $this->getIconUrl('user.png');
                    }
                }
            }

            if ($aPossibleTypes) {
                $oForm = new BxBaseFormView(array(), $this);
                $oForm->addCssJs();

                $sTypesSelected = $this->_oDb->getParam('aqb_locations_map_content_types_selected');
                $aTypesSelected = $sTypesSelected ? explode(',', $sTypesSelected) : array();

                foreach ($aPossibleTypes as $sType => $sCaption) {
                    $aInput = array(
                        'caption' => $sCaption,
                        'type' => 'switcher',
                        'checked' => in_array($sType, $aTypesSelected),
                        'name' => $sType,
                        'value' => 1,
                        'attrs' => array(
                            'onchange' => "aqb_locations_map_on_filter_change();",
                        ),
                    );

                    $aFilters[] = array(
                        'name' => $sCaption,
                        'switcher' => $oForm->genInputSwitcher($aInput),
                    );

                    if ($aInput['checked']) $oMapOptions['filter'][] = $sType;
                }
            }
        }

        return $this->parseHtmlByName('map_member.html', array(
            'bx_if:filters' => array(
                'condition' => count($aFilters) > 1,
                'content' => array(
                    'bx_repeat:items' => $aFilters,
                ),
            ),
            'bx_if:map_google' => array(
                'condition' => $sMapProvider == 'Google',
                'content' => array('api_key' => $sAPIKey),
            ),
            'bx_if:map_osm' => array(
                'condition' => $sMapProvider != 'Google',
                'content' => array(),
            ),
            'options' => json_encode($oMapOptions),
            'api_key' => $sAPIKey,
        ));
    }

    function getLocationInfo($fLat, $fLng, $sFilter) {
        $aLocations = $this->_oDb->getSameLocations($fLat, $fLng, $sFilter);
        if (!$aLocations) return '';

        $aEntries = array();
        $aEntriesIDs = array();
        foreach ($aLocations as $aObject) {
            $aEntry = $this->getObjectInfo($aObject['type'], $aObject['object_id']);
            if ($aEntry && !isset($aEntriesIDs[$aEntry['id']])) {
                $aEntries[] = $aEntry;
                $aEntriesIDs[$aEntry['id']] = 1;
            }
        }

        if (empty($aEntries)) return '';

        return $this->parseHtmlByName('location_info.html', array('bx_repeat:entries' => $aEntries));
    }

    function getObjectInfo($sType, $iId) {
        $aInfo = array();

        if ($sType != 'online_profiles') {
            $aInfo = $this->getContentInfo($sType, $iId);
        } else {
            $aProfile = $this->_oDb->getProfileInfo($iId);
            $aInfo = $this->getContentInfo($aProfile['type'], $aProfile['content_id']);
        }

        if (!$aInfo) return false;

        return $aInfo;
    }

    function getCNFVariables(&$CNF) {
        $MYCNF = array();
        if (isset($CNF['FIELD_NAME'])) $MYCNF['NAME'] = $CNF['FIELD_NAME'];
        elseif (isset($CNF['FIELD_TITLE'])) $MYCNF['NAME'] = $CNF['FIELD_TITLE'];

        if (isset($CNF['TABLE_ENTRIES']) && $CNF['TABLE_ENTRIES'] == 'bx_polls_entries') $MYCNF['NAME'] = $CNF['FIELD_TEXT'];

        if (isset($CNF['FIELD_PICTURE'])) $MYCNF['PICTURE'] = $CNF['FIELD_PICTURE'];
        elseif (isset($CNF['FIELD_THUMB'])) $MYCNF['PICTURE'] = $CNF['FIELD_THUMB'];

        if (isset($CNF['TABLE_ENTRIES'])) $MYCNF['TABLE'] = $CNF['TABLE_ENTRIES'];
        if (isset($CNF['FIELD_ID'])) $MYCNF['FIELD_ID'] = $CNF['FIELD_ID'];
        if (isset($CNF['ICON'])) $MYCNF['ICON'] = $CNF['ICON'];

        if (isset($CNF['OBJECT_IMAGES_TRANSCODER_THUMB'])) $MYCNF['OBJECT_IMAGES_TRANSCODER_THUMB'] = $CNF['OBJECT_IMAGES_TRANSCODER_THUMB'];
        elseif (isset($CNF['OBJECT_IMAGES_TRANSCODER_COVER'])) $MYCNF['OBJECT_IMAGES_TRANSCODER_THUMB'] = $CNF['OBJECT_IMAGES_TRANSCODER_COVER'];

        return $MYCNF;
    }

    function getContentInfo($sType, $iId) {
        $oModule = BxDolModule::getInstance($sType);
        $CNF = &$oModule->_oConfig->CNF;
        if (!$CNF) return '';

        $MYCNF = $this->getCNFVariables($CNF);
        if (!$MYCNF['NAME'] || !$MYCNF['TABLE'] || !$MYCNF['FIELD_ID']) return '';

        $sName = $this->_oDb->getLocationInfo($MYCNF['NAME'], $MYCNF['TABLE'], $MYCNF['FIELD_ID'], $iId);
        if (!$sName) return '';

        $sLink = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . intval($iId));

        $sPic = '';
        if (isset($MYCNF['PICTURE']) && isset($MYCNF['OBJECT_IMAGES_TRANSCODER_THUMB'])) {
            $iPic = $this->_oDb->getLocationInfo($MYCNF['PICTURE'], $MYCNF['TABLE'], $MYCNF['FIELD_ID'], $iId);
            if ($iPic) $sPic = BxDolTranscoder::getObjectInstance($MYCNF['OBJECT_IMAGES_TRANSCODER_THUMB'])->getFileUrl($iPic);
        }

        return array(
            'id' => $sType.$iId,
            'bx_if:pic' => array(
                'condition' => !empty($sPic),
                'content' => array('url' => $sPic, 'link' => $sLink),
            ),
            'type' => _t('_'.$sType),
            'name' => strmaxtextlen($sName, 100),
            'link' => $sLink,
        );
    }
}

/** @} */

