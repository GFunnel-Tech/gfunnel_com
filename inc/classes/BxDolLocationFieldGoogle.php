<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

class BxDolLocationFieldGoogle extends BxDolLocationField
{
    protected $_sApiKey;
    protected $_sEndpoint;

    protected function __construct($aObject)
    {
        parent::__construct($aObject);

        $this->_sApiKey = trim(getParam('sys_maps_api_key'));
        $this->_sEndpoint = 'https://maps.googleapis.com/maps/api/place/';
    }

    public function getAutocomplete($sValue)
    {
        $sEndpoint = bx_append_url_params($this->_sEndpoint . 'autocomplete/json', [
            'key' => $this->_sApiKey,
            'input' => urlencode($sValue)
        ]);

        $sResponse = bx_file_get_contents($sEndpoint);
        if(empty($sResponse))
            return false;

        $aResponse = json_decode($sResponse, true);
        if(empty($aResponse) || !is_array($aResponse))
            return false;

        return $aResponse;
    }

    public function getPlace($sPlaceId, $aFields = [])
    {
        if(!$aFields)
            $aFields = ['geometry', 'formatted_address', 'address_component'];

        $sEndpoint = bx_append_url_params($this->_sEndpoint . 'details/json', [
            'key' => $this->_sApiKey,
            'place_id' => $sPlaceId,
            'fields' => implode(',', $aFields)
        ]);

        $sResponse = bx_file_get_contents($sEndpoint);
        if(empty($sResponse))
            return false;

        $aResponse = json_decode($sResponse, true);
        if(empty($aResponse) || !is_array($aResponse) || empty($aResponse['result']))
            return false;

        return $aResponse['result'];
    }

    public function genInputLocation (&$aInput, $oForm)
    {
        $aVars = $this->_getInputLocationVars($aInput, $oForm);
        return $oForm->getTemplate()->parseHtmlByName('location_field_google.html', $aVars);
    }

    protected function _getInputLocationVars(&$aInput, $oForm)
    {
        $isManualInput = (int)(isset($aInput['manual_input']) && $aInput['manual_input']);
        $sIdStatus = $oForm->getInputId($aInput) . '_status';
        $sIdInput = $oForm->getInputId($aInput) . '_location';

        $aVars = array (
            'key' => $this->_sApiKey,
            'lang' => bx_lang_name(),
            'name' => $aInput['name'],
            'id_status' => $sIdStatus,
            'id_input' => $sIdInput,
            'manual_input' => $isManualInput,            
            'bx_if:manual_input' => array(
                'condition' => $isManualInput,
                'content' => array(),
            ),
            'bx_if:auto_input' => array(
                'condition' => !$isManualInput,
                'content' => array(
                    'id_status' => $sIdStatus,
                    'location_string' => _t('_sys_location_field_label'),
                ),
            ),
            'api_field_name_short' => 'short_name',
            'api_field_name_long' => 'long_name',
            'api_field_name_2_length' => json_encode(array()),
        );

        $aLocationIndexes = BxDolForm::$LOCATION_INDEXES;
        foreach ($aLocationIndexes as $sKey)
            $aVars[$sKey] = $this->getLocationVal($aInput, $sKey, $oForm);

        if ($isManualInput) {
            $aAttrs = empty($aInput['attrs']) ? array() : $aInput['attrs'];
            $aInput2 = $aInput;
            $aInput2['type'] = 'text';
            $aInput2['attrs']['id'] = $sIdInput;
            $aInput2['attrs'] = array_merge($aAttrs, $aInput2['attrs']);
            $aVars['input'] = $oForm->genInputStandard($aInput2);
        } 
        else {
            if ($this->getLocationVal($aInput, 'lat', $oForm) && $this->getLocationVal($aInput, 'lng', $oForm))
                $aInput['checked'] = true;
            else
                $aInput['checked'] = $oForm->getCleanValue($aInput['name'] . '_lat') && $oForm->getCleanValue($aInput['name'] . '_lng') ? 1 : 0;
            $aVars['input'] = $oForm->genInputSwitcher($aInput);

            $sLocationString = _t($aInput['checked'] ? '_sys_location_undefined' : '_sys_location_field_label');
            if ($aVars['country']) {
                $aCountries = BxDolFormQuery::getDataItems('Country');
                $sLocationString = ($aVars['street_number'] ? $aVars['street_number'] . ', ' : '') . ($aVars['street'] ? $aVars['street'] . ', ' : '') . ($aVars['city'] ? $aVars['city'] . ', ' : '') . ($aVars['state'] ? $aVars['state'] . ', ' : '') . $aCountries[$aVars['country']];
            }
            $aVars['bx_if:auto_input']['content']['location_string'] = $sLocationString;
        }

        return $aVars;
    }
}

/** @} */
