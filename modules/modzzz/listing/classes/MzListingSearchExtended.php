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

/**
 * Browse entries pages.
 */
class MzListingSearchExtended extends BxBaseSearchExtended
{
 
    public function __construct($aObject, $oTemplate = false)
    {
        $this->MODULE = 'mz_listing';

        parent::__construct($aObject, $oTemplate);

        $this->_oTemplate->addJs('entry.js'); 
    }
 
    protected function &prepareForm($aParams = array())
    {
        if(!empty($this->_oForm) && $this->_oForm instanceof BxDolForm)
            return $this->_oForm;

        $sForm = 'sys_search_extended_' . $this->_sObject;
        $sFormSubmit = 'search' . $this->_sObject;

        list($sPageLink, $aPageParams) = bx_get_base_url_inline();

        $aForm = array(
            'form_attrs' => array(
                'id' => $sForm,
                'name' => $sForm,
                'action' => BxDolPermalinks::getInstance()->permalink(bx_append_url_params($sPageLink, array('i' => $aPageParams['i']))),
                'method' => 'post'
            ),
            'params' => array(
                'db' => array(
                    'table' => '',
                    'key' => '',
                    'uri' => '',
                    'uri_title' => '',
                    'submit_name' => $sFormSubmit
                ),
            ),
            'inputs' => array()
        );

        foreach ($this->_aObject['fields'] as $aField) {
            if((int)$aField['active'] == 0)
                continue;

            if(in_array($aField['search_type'], array('checkbox_set', 'select_multiple'))) {
                if(isset($aField['values']['']))
                    unset($aField['values']['']);

                if(isset($aField['values'][0]) && !is_array($aField['values'][0]))
                    unset($aField['values'][0]);
            }

            $aAttrs = array();

            if(in_array($aField['search_type'], array('datepicker_range_age')) && !empty($aField['search_value'])) {
                $aFieldParams = BxDolService::callSerialized($aField['search_value']);
                $iMin = isset($aFieldParams['min']) && is_numeric($aFieldParams['min']) ? $aFieldParams['min'] : $this->_iAgeMin;
                $iMax = isset($aFieldParams['max']) && is_numeric($aFieldParams['max']) ? $aFieldParams['max'] : $this->_iAgeMax;

                $aField['search_value'] = $iMin . '-' . $iMax;
                $aAttrs = array('min' => $iMin, 'max' => $iMax, 'step' => 1);
            }

            if(in_array($aField['search_type'], array('datepicker_range')) && !empty($aField['search_value'])) {
                $aField['search_value'] = '';
            }
  
			if($aField['name'] == 'category')
			{
				$aField['values'] = array(''=>'') + $aField['values'];
				$aAttrs = array_merge($aAttrs, array('onchange' => 'oMzListingEntry.onChangeGetSubCategories(this);'));
			}

			if($aField['name'] == 'subcategory') 
			{
				$aAttrs = array_merge($aAttrs, array('id' => 'mz_listing_subcategory'));
 
				$iCategory = bx_get('category');
				if($iCategory)
					$aField['values'] = bx_srv('mz_listing', 'get_category_options', array($iCategory, true)); 
			}

            $aForm['inputs'][$aField['name']] = array(
                'type' => $aField['search_type'],
                'name' => $aField['name'],
                'caption' => _t($aField['caption']),
            	'info' => _t($aField['info']),
                'values' => $aField['values'],
                'value' => $aField['search_value'],
                'attrs' => $aAttrs,
                'db' => array(
                    'pass' => !empty($aField['pass']) ? $aField['pass'] : 'Xss'
                )
            );

            if(in_array($aField['search_type'], array('location', 'location_radius')))
                $aForm['inputs'][$aField['name']]['manual_input'] = true;
        }

        $aForm['inputs']['search'] = array(
            'type' => 'submit',
            'name' => $sFormSubmit,
            'value' => _t($this->_bFilterMode ? '_Apply' : '_Search')
        );

        $sClass = 'BxTemplSearchExtendedForm';
        if(!empty($this->_sFormClassName)) {
            $sClass = $this->_sFormClassName;
            if(!empty($this->_sFormClassPath))
                require_once(BX_DIRECTORY_PATH_ROOT . $this->_sFormClassPath);
        }

        $bJsMode = isset($aParams['js_mode']) ? (bool)$aParams['js_mode'] : $this->_bJsMode;
        $bCondition = !empty($aParams['cond']) && is_array($aParams['cond']);
        $aValues = !$bJsMode && $bCondition ? $aParams['cond'] : array();

        $this->_oForm = new $sClass($aForm, $this->_oTemplate);
        $this->_oForm->initChecker($aValues, $aValues);
 
        return $this->_oForm;
    }

    public function getForm($aParams = array())
    {
        if(!$this->isEnabled())
            return '';

        $oForm = $this->prepareForm($aParams);

        return $this->_oTemplate->getJsCode('entry') . $oForm->getCode();
    }


}

/** @} */
