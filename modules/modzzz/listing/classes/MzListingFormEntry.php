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
 * Create/Edit entry form
 */
class MzListingFormEntry extends BxBaseModTextFormEntry
{
    protected $_iCategory; 
    protected $_iSubCategory;
    protected $_sGhostTemplateCover = 'form_ghost_template_cover.html';
    protected static $_isCssJsEventsAdded = false; 
    protected $_iContentId = 0;
 
    public function __construct($aInfo, $oTemplate = false)
    {
        $this->MODULE = 'mz_listing';
        parent::__construct($aInfo, $oTemplate);

        $CNF = &$this->_oModule->_oConfig->CNF;
 
        $this->_initCategoryFields();

        if (isset($this->aInputs['hour'])) {
            $this->aInputs['hour']['ghost_template'] = array (
                
                'params' => array(
                    'nested_form_template' => 'form_ghost_template_wrapper.html',
                    'db' => array(
                        'table' => 'mz_listing_hours',
                        'key' => 'hour_id',
                        'submit_name' => 'hour_id',
                    ), 
                ),
                'inputs' => array (
                    'hour_id' => array (
                        'type' => 'hidden',
                        'name' => 'hour_id[]',
                        'value' => '{hour_id}',
                        'caption' => _t('Interval ID'),
                        'db' => array (
                            'pass' => 'Int',
                        ),
                    ),                
                    'repeat_from_day_of_week' => array (
                        'type' => 'select',
                        'name' => 'repeat_from_day_of_week[]',
                        'value' => '{repeat_from_day_of_week}',
                        'values' => BxDolForm::getDataItems('mz_listing_repeat_day_of_week'),
                        'caption' => _t('_mz_listing_form_input_repeat_from_day_of_week'),
                        'db' => array (
                            'pass' => 'Int',
                        ),
                    ),
                    'repeat_to_day_of_week' => array (
                        'type' => 'select',
                        'name' => 'repeat_to_day_of_week[]',
                        'value' => '{repeat_to_day_of_week}',
                        'values' => BxDolForm::getDataItems('mz_listing_repeat_day_of_week'),
                        'caption' => _t('_mz_listing_form_input_repeat_to_day_of_week'),
                        'db' => array (
                            'pass' => 'Int',
                        ),
                    ), 
                    'repeat_from_hour' => array (
                        'type' => 'select',
                        'name' => 'repeat_from_hour[]',
                        'value' => '{repeat_from_hour}',
                        'values' => BxDolForm::getDataItems('mz_listing_repeat_hour'),
                        'caption' => _t('_mz_listing_form_input_repeat_from_hour'),
                        'db' => array (
                            'pass' => 'Int',
                        ),
                    ),
                    'repeat_from_minute' => array (
                        'type' => 'select',
                        'name' => 'repeat_from_minute[]',
                        'value' => '{repeat_from_minute}',
                        'values' => BxDolForm::getDataItems('mz_listing_repeat_minute'),
                        'caption' => _t('_mz_listing_form_input_repeat_from_minute'),
                        'db' => array (
                            'pass' => 'Int',
                        ),
                    ), 
                    'repeat_from_period' => array (
                        'type' => 'select',
                        'name' => 'repeat_from_period[]',
                        'value' => '{repeat_from_period}',
                        'values' => BxDolForm::getDataItems('mz_listing_repeat_period'),
                        'caption' => _t('_mz_listing_form_input_repeat_from_period'),
                        'db' => array (
                            'pass' => 'Int',
                        ),
                    ), 
 
                   'repeat_to' => array (
                        'type' => 'custom',
                        'name' => 'repeat_to[]', 
                        'caption' => _t('_mz_listing_form_input_repeat_to'), 
                    ),
 
                    'repeat_to_hour' => array (
                        'type' => 'select',
                        'name' => 'repeat_to_hour[]',
                        'value' => '{repeat_to_hour}',
                        'values' => BxDolForm::getDataItems('mz_listing_repeat_hour'),
                        'caption' => _t('_mz_listing_form_input_repeat_to_hour'),
                        'db' => array (
                            'pass' => 'Int',
                        ),
                    ),
                    'repeat_to_minute' => array (
                        'type' => 'select',
                        'name' => 'repeat_to_minute[]',
                        'value' => '{repeat_to_minute}',
                        'values' => BxDolForm::getDataItems('mz_listing_repeat_minute'),
                        'caption' => _t('_mz_listing_form_input_repeat_to_minute'),
                        'db' => array (
                            'pass' => 'Int',
                        ),
                    ), 
                    'repeat_to_period' => array (
                        'type' => 'select',
                        'name' => 'repeat_to_period[]',
                        'value' => '{repeat_to_period}',
                        'values' => BxDolForm::getDataItems('mz_listing_repeat_period'),
                        'caption' => _t('_mz_listing_form_input_repeat_to_period'),
                        'db' => array (
                            'pass' => 'Int',
                        ),
                    ), 
                ),
            );
        }

    	if(isset($CNF['FIELD_COVER']) && isset($this->aInputs[$CNF['FIELD_COVER']])) {
            if($this->_oModule->checkAllowedSetThumb() === CHECK_ACTION_RESULT_ALLOWED) {
                $this->aInputs[$CNF['FIELD_COVER']]['storage_object'] = $CNF['OBJECT_STORAGE'];
                $this->aInputs[$CNF['FIELD_COVER']]['uploaders'] = !empty($this->aInputs[$CNF['FIELD_COVER']]['value']) ? unserialize($this->aInputs[$CNF['FIELD_COVER']]['value']) : $CNF['OBJECT_UPLOADERS'];
                $this->aInputs[$CNF['FIELD_COVER']]['upload_buttons_titles'] = array(
                    'Simple' => _t('_mz_listing_form_entry_input_covers_uploader_simple_title'), 
                    'HTML5' => _t('_mz_listing_form_entry_input_covers_uploader_html5_title')
                );
                $this->aInputs[$CNF['FIELD_COVER']]['images_transcoder'] = $CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW'];
                $this->aInputs[$CNF['FIELD_COVER']]['storage_private'] = 0;
                $this->aInputs[$CNF['FIELD_COVER']]['mulservicele'] = false;
                $this->aInputs[$CNF['FIELD_COVER']]['content_id'] = 0;
                $this->aInputs[$CNF['FIELD_COVER']]['ghost_template'] = '';
            }
            else
                unset($this->aInputs[$CNF['FIELD_COVER']]);
        }

        if(isset($CNF['FIELD_PHOTO']) && isset($this->aInputs[$CNF['FIELD_PHOTO']])) {
            $this->aInputs[$CNF['FIELD_PHOTO']]['storage_object'] = $CNF['OBJECT_STORAGE_PHOTOS'];
            $this->aInputs[$CNF['FIELD_PHOTO']]['uploaders'] = !empty($this->aInputs[$CNF['FIELD_PHOTO']]['value']) ? unserialize($this->aInputs[$CNF['FIELD_PHOTO']]['value']) : $CNF['OBJECT_UPLOADERS'];
            $this->aInputs[$CNF['FIELD_PHOTO']]['images_transcoder'] = $CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW_PHOTOS'];
            $this->aInputs[$CNF['FIELD_PHOTO']]['storage_private'] = 0;
            $this->aInputs[$CNF['FIELD_PHOTO']]['mulservicele'] = true;
            $this->aInputs[$CNF['FIELD_PHOTO']]['content_id'] = 0;
            $this->aInputs[$CNF['FIELD_PHOTO']]['ghost_template'] = '';
            $this->aInputs[$CNF['FIELD_PHOTO']]['tr_attrs'] = array('class'=> 'bx-base-text-attachment-item');
        }

        if(isset($this->aInputs[$CNF['FIELD_VIDEO']])) {
            $this->aInputs[$CNF['FIELD_VIDEO']]['storage_object'] = $CNF['OBJECT_STORAGE_VIDEOS'];
            $this->aInputs[$CNF['FIELD_VIDEO']]['uploaders'] = !empty($this->aInputs[$CNF['FIELD_VIDEO']]['value']) ? unserialize($this->aInputs[$CNF['FIELD_VIDEO']]['value']) : $CNF['OBJECT_UPLOADERS'];
            $this->aInputs[$CNF['FIELD_VIDEO']]['images_transcoder'] = $CNF['OBJECT_VIDEOS_TRANSCODERS']['poster_preview'];
            $this->aInputs[$CNF['FIELD_VIDEO']]['storage_private'] = 0;
            $this->aInputs[$CNF['FIELD_VIDEO']]['mulservicele'] = true;
            $this->aInputs[$CNF['FIELD_VIDEO']]['content_id'] = 0;
            $this->aInputs[$CNF['FIELD_VIDEO']]['ghost_template'] = '';
            $this->aInputs[$CNF['FIELD_VIDEO']]['tr_attrs'] = array('class'=> 'bx-base-text-attachment-item');
        }

        if (isset($CNF['FIELD_FILE']) && isset($this->aInputs[$CNF['FIELD_FILE']])) {
            $this->aInputs[$CNF['FIELD_FILE']]['storage_object'] = $CNF['OBJECT_STORAGE_FILES'];
            $this->aInputs[$CNF['FIELD_FILE']]['uploaders'] = !empty($this->aInputs[$CNF['FIELD_FILE']]['value']) ? unserialize($this->aInputs[$CNF['FIELD_FILE']]['value']) : $CNF['OBJECT_UPLOADERS'];
            $this->aInputs[$CNF['FIELD_FILE']]['images_transcoder'] = $CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW_FILES'];
            $this->aInputs[$CNF['FIELD_FILE']]['storage_private'] = 0;
            $this->aInputs[$CNF['FIELD_FILE']]['mulservicele'] = true;
            $this->aInputs[$CNF['FIELD_FILE']]['content_id'] = 0;
            $this->aInputs[$CNF['FIELD_FILE']]['ghost_template'] = '';
            $this->aInputs[$CNF['FIELD_FILE']]['tr_attrs'] = array('class'=> 'bx-base-text-attachment-item');
        }

        if(isset($this->aInputs[$CNF['FIELD_POLL']])) {
            $this->aInputs[$CNF['FIELD_POLL']]['tr_attrs'] = array('class'=> 'bx-base-text-attachment-item');
        }
    }
/*
    function getCode($bDynamicMode = false)
    {
        $sInclude = $this->_oModule->_oTemplate->addJs(array('entry.js'), $bDynamicMode);
        return ($bDynamicMode ? $sInclude : '') . $this->_oModule->_oTemplate->getJsCode('entry') . parent::getCode($bDynamicMode);
    }
*/

/**** removed lately
    function getCode($bDynamicMode = false)
    {
        $sJsCode = '';
        if(empty($this->aParams['view_mode']))
            $sJsCode = $this->_oModule->_oTemplate->getJsCode('entry');

        return $sJsCode . parent::getCode($bDynamicMode);
    }
 */
 
    function initChecker ($aValues = array (), $aSpecificValues = array())
    {  
        $CNF = &$this->_oModule->_oConfig->CNF;

        $bValues = $aValues && !empty($aValues['id']);
        $aContentInfo = $bValues ? $this->_oModule->_oDb->getContentInfoById($aValues['id']) : array();
 
 		if(!empty($aContentInfo)) 
			$this->_initCategoryFields($aContentInfo[$CNF['FIELD_CATEGORY']], $aContentInfo[$CNF['FIELD_SUBCATEGORY']]);

        if(isset($CNF['FIELD_COVER']) && isset($this->aInputs[$CNF['FIELD_COVER']])) {
            if($bValues)
                $this->aInputs[$CNF['FIELD_COVER']]['content_id'] = $aValues['id'];

            $this->aInputs[$CNF['FIELD_COVER']]['ghost_template'] = $this->_oModule->_oTemplate->parseHtmlByName($this->_sGhostTemplateCover, $this->_getCoverGhostTmplVars($aContentInfo));
        }

        parent::initChecker ($aValues, $aSpecificValues);
    }

    public function insert ($aValsToAdd = array(), $isIgnore = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
   
		if(isAdmin())
			$aValsToAdd[$CNF['FIELD_CLAIM_STATUS']] = 'claimable';
 
        $iContentId = parent::insert ($aValsToAdd, $isIgnore);
 
        if (isset($this->aInputs['hour']) && is_array($this->aInputs['hour']['ghost_template']) && !isset($this->aInputs['hour']['ghost_template']['inputs'])) {
            foreach ($this->aInputs['hour']['ghost_template'] as $oFormNested) {
                $oFormNested->insert(array('listing_id' => $iContentId));
            }
        }
 
        if(!empty($iContentId))
            $this->processFiles($CNF['FIELD_COVER'], $iContentId, true);

        return $iContentId;
    }

    public function update ($iContentId, $aValsToAdd = array(), &$aTrackTextFieldsChanges = null)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
 
        $iResult = parent::update ($iContentId, $aValsToAdd, $aTrackTextFieldsChanges);
 
        $this->processFiles($CNF['FIELD_COVER'], $iContentId, false);

        if (isset($this->aInputs['hour']) && is_array($this->aInputs['hour']['ghost_template']) && !isset($this->aInputs['hour']['ghost_template']['inputs'])) {
            foreach ($this->aInputs['hour']['ghost_template'] as $oFormNested) {
                $aSpecificValues = $oFormNested->getSpecificValues();
                $iHourId = $oFormNested->getSubmittedValue('hour_id', BX_DOL_FORM_METHOD_SPECIFIC, $aSpecificValues);
                if ($iHourId) {
                    $oFormNested->update($iHourId);
                } else {
                    $oFormNested->insert(array('listing_id' => $this->_iContentId));
                }
            }
        }

        return $iResult;
    }
  
	protected function genCustomInputCategoryView(&$aInput)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if(!empty($this->_iCategory)) {
            $aCategory = $this->_oModule->_oDb->getCategories(array('type' => 'id', 'id' => $this->_iCategory));
            if(!empty($aCategory) && is_array($aCategory))
                $aInput['value'] = bx_process_output(_t($aCategory['title']));
        }

        return $this->genInputStandard($aInput);
    }
 
    protected function genCustomViewRowValueCategoryView(&$aInput)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if(empty($aInput['value']))
            return '';

        $aCategory = $this->_oModule->_oDb->getCategories(array('type' => 'id', 'id' => $aInput['value']));
        if(empty($aCategory) || !is_array($aCategory))
            return '';

		if($aCategory['parent_id']==0)
			$sLink = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink($CNF['URL_CATEGORIES'], array($CNF['GET_PARAM_CATEGORY'] => $aCategory['id']));
		else
			$sLink = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink($CNF['URL_SUBCATEGORIES'], array($CNF['GET_PARAM_SUBCATEGORY'] => $aCategory['id']));
 
        return $this->_oModule->_oTemplate->parseLink($sLink, bx_process_output(_t($aCategory['title'])));
    }
 
    public function genViewRowValue(&$aInput)
    {  
		if(isset($aInput['value']) && '0' == $aInput['value']){ 
 			return null;
		}
		return parent::genViewRowValue($aInput); 
	}
 

    protected function genCustomViewRowValueFacebook(&$aInput)
    { 
        $CNF = &$this->_oModule->_oConfig->CNF;

        if(empty($aInput['value']))
            return null;
 
        $sLink = $aInput['value'];
        $sTitle = _t('_mz_listing_caption_official_facebook');
  
		$aAttrs = array('target' => '_blank'); 
        return $this->_oModule->_oTemplate->parseLink($sLink, bx_process_output($sTitle), $aAttrs);
    }

    protected function genCustomViewRowValueWebsite(&$aInput)
    { 
        $CNF = &$this->_oModule->_oConfig->CNF;

        if(empty($aInput['value']))
            return null;
 
        $sLink = $aInput['value'];
        $sTitle = _t('_mz_listing_caption_official_website');
  
		$aAttrs = array('target' => '_blank'); 
        return $this->_oModule->_oTemplate->parseLink($sLink, bx_process_output($sTitle), $aAttrs);
    }

    protected function genCustomViewRowValueInstagram(&$aInput)
    { 
        $CNF = &$this->_oModule->_oConfig->CNF;

        if(empty($aInput['value']))
            return null;
 
        $sLink = $aInput['value'];
        $sTitle = _t('_mz_listing_caption_official_instagram');
  
		$aAttrs = array('target' => '_blank'); 
        return $this->_oModule->_oTemplate->parseLink($sLink, bx_process_output($sTitle), $aAttrs);
    }    
	
	protected function genCustomViewRowValueTwitter(&$aInput)
    { 
        $CNF = &$this->_oModule->_oConfig->CNF;

        if(empty($aInput['value']))
            return null;
 
        $sLink = $aInput['value'];
        $sTitle = _t('_mz_listing_caption_official_twitter');
  
		$aAttrs = array('target' => '_blank'); 
        return $this->_oModule->_oTemplate->parseLink($sLink, bx_process_output($sTitle), $aAttrs);
    }

	protected function genCustomViewRowValueLinkedin(&$aInput)
    { 
        $CNF = &$this->_oModule->_oConfig->CNF;

        if(empty($aInput['value']))
            return null;
 
        $sLink = $aInput['value'];
        $sTitle = _t('_mz_listing_caption_official_linkedin');
  
		$aAttrs = array('target' => '_blank'); 
        return $this->_oModule->_oTemplate->parseLink($sLink, bx_process_output($sTitle), $aAttrs);
    }
 
	protected function genCustomViewRowValueOtherSocial(&$aInput)
    { 
        $CNF = &$this->_oModule->_oConfig->CNF;

        if(empty($aInput['value']))
            return null;
 
        $sLink = $aInput['value'];
        $sTitle = _t('_mz_listing_caption_official_other');
  
		$aAttrs = array('target' => '_blank'); 
        return $this->_oModule->_oTemplate->parseLink($sLink, bx_process_output($sTitle), $aAttrs);
    }
 
    protected function genCustomViewRowValueEmail(&$aInput)
    { 
        $CNF = &$this->_oModule->_oConfig->CNF;

        if(empty($aInput['value']))
            return null;
 
        $sLink = 'mailto:' . $aInput['value'];
        $sTitle = _t('_mz_listing_caption_official_email');
  
		$aAttrs = array('target' => '_blank'); 
        return $this->_oModule->_oTemplate->parseLink($sLink, bx_process_output($sTitle), $aAttrs);
    }
    
    protected function _getCoverGhostTmplVars($aContentInfo = array())
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;

    	return array (
            'name' => $this->aInputs[$CNF['FIELD_COVER']]['name'],
            'content_id' => $this->aInputs[$CNF['FIELD_COVER']]['content_id'],
            'editor_id' => isset($CNF['FIELD_TEXT_ID']) ? $CNF['FIELD_TEXT_ID'] : '',
            'thumb_id' => isset($CNF['FIELD_THUMB']) && isset($aContentInfo[$CNF['FIELD_THUMB']]) ? $aContentInfo[$CNF['FIELD_THUMB']] : 0,
            'name_thumb' => isset($CNF['FIELD_THUMB']) ? $CNF['FIELD_THUMB'] : ''
        );
    }

	//UNA 13 RC2 
	protected function _getPhotoGhostTmplVars($aContentInfo = array())
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;
    	return array (
            'name' => $this->aInputs[$CNF['FIELD_PHOTO']]['name'],
            'content_id' => (int)$this->aInputs[$CNF['FIELD_PHOTO']]['content_id'],
            'editor_id' => isset($CNF['FIELD_TEXT_ID']) ? $CNF['FIELD_TEXT_ID'] : '',
            'bx_if:set_thumb' => [
				'condition' => false,
				'content' => []
			],
    	);
    }

    protected function _initCategoryFields($iCategory = 0, $iSubCategory = 0)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $this->_iCategory = (int)$iCategory;
        $this->_iSubCategory = (int)$iSubCategory;

        if(empty($this->_iCategory) && bx_get('category') !== false)
            $this->_iCategory = (int)bx_get('category');
 
        if(isset($CNF['FIELD_CATEGORY']) && isset($this->aInputs[$CNF['FIELD_CATEGORY']]) && $this->_iCategory != 0)
            $this->aInputs[$CNF['FIELD_CATEGORY']]['value'] = $this->_iCategory;

        if(isset($CNF['FIELD_SUBCATEGORY']) && isset($this->aInputs[$CNF['FIELD_SUBCATEGORY']]) && $this->_iSubCategory != 0)
            $this->aInputs[$CNF['FIELD_SUBCATEGORY']]['value'] = $this->_iSubCategory;

		if($this->_iSubCategory){ 
			if(isset($CNF['FIELD_CATEGORY_VIEW']) && isset($this->aInputs[$CNF['FIELD_CATEGORY_VIEW']]) && $this->_iSubCategory != 0)
				$this->aInputs[$CNF['FIELD_CATEGORY_VIEW']]['value'] = $this->_iSubCategory;

		}else{
			if(isset($CNF['FIELD_CATEGORY_VIEW']) && isset($this->aInputs[$CNF['FIELD_CATEGORY_VIEW']]) && $this->_iCategory != 0)
				$this->aInputs[$CNF['FIELD_CATEGORY_VIEW']]['value'] = $this->_iCategory; 
		}
 
        if(isset($CNF['FIELD_CATEGORY_SELECT']) && isset($this->aInputs[$CNF['FIELD_CATEGORY_SELECT']])){
            $this->aInputs[$CNF['FIELD_CATEGORY_SELECT']]['values'] = $this->_oModule->serviceGetCategoryOptions(0, true);

			if(isset($CNF['FIELD_SUBCATEGORY']) && isset($this->aInputs[$CNF['FIELD_SUBCATEGORY']]) && $iCategory)
				$this->aInputs[$CNF['FIELD_SUBCATEGORY_SELECT']]['values'] = $this->_oModule->serviceGetCategoryOptions($iCategory, true);
		}

    }
 
    protected function genCustomRowHour (&$aInput)
    {
        return $this->genRowCustom($aInput, 'genInputHour');
    }

    protected function genInputHour (&$aInput, $sInfo = '', $sError = '')
    {
        $sUniqId = genRndPwd (8, false);

        return $this->oTemplate->parseHtmlByName('form_field_hour.html', array(
            'info' => $sInfo,
            'error' => $sError,
            'uniq_id' => $sUniqId,
            'js_instance_name' => 'oBxListingHours_' . $sUniqId,
            'options' => json_encode(array(
                'uniq_id' => $sUniqId,
                'js_instance_name' => 'oBxListingHours_' . $sUniqId,
                'template_ghost' => $this->genGhostTemplate($aInput),
                'content_id' => $this->_iContentId, 
            )),
        ));
    }
 
    function addCssJs ()
    {
        if ((!isset($this->aParams['view_mode']) || !$this->aParams['view_mode']) && !self::$_isCssJsEventsAdded) { 
            $this->oTemplate->addJs(array('entry.js','hours.js'));
            $this->oTemplate->addCss('informer.css');
            self::$_isCssJsEventsAdded = true;
        }else{
            $this->oTemplate->addJs(array('entry.js','hours.js'));
		}

        parent::addCssJs ();
    }


    function delete ($iContentId, $aContentInfo = array())
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

		if (!empty($CNF['OBJECT_REVIEWS'])) {
            $o = BxDolCmts::getObjectInstance($CNF['OBJECT_REVIEWS'], $iContentId);
            if ($o) $o->onObjectDelete();
        }
 
        return parent::delete($iContentId, $aContentInfo);
    }


    function _initCheckerNestedForms ()
    {
        $isValid = true;
 
        // process nested forms
        foreach ($this->aInputs as $sKey => $aInput) {

            if (!isset($aInput['ghost_template']))
                continue;

            if (!(is_array($aInput['ghost_template']) && isset($aInput['ghost_template']['inputs'])) && !(is_object($aInput['ghost_template']) && $aInput['ghost_template'] instanceof BxDolFormNestedGhost))
                continue;

            $sName = $aInput['name'];
            $aIds = $this->getSubmittedValue($sName, $this->aFormAttrs['method']);
            if (!$aIds)
                continue;

            $aNestedForms = array ();
            foreach ($aIds as $i => $iFileId) {

                if (isset($this->aParams['db']['submit_name']) && is_array($this->aParams['db']['submit_name']))
					$this->aParams['db']['submit_name'] = $this->aParams['db']['submit_name'][0];

                // create separate form instance for each file
                $oFormNested = false;
                if (is_object($aInput['ghost_template'])) {
                    $oFormNested = clone($aInput['ghost_template']);
                } else {
                    $oFormNested = new BxDolFormNestedGhost($aInput['name'], $aInput['ghost_template'], $this->aParams['db']['submit_name'], $this->oTemplate);
                }
                if ($iFileId && 0 != $iFileId)
                    $aNestedForms[$iFileId] = $oFormNested;
                else
                    $aNestedForms[] = $oFormNested;

                // collect nested form values
                $aSpecificValues = array ();
                if (isset($this->aParams['db']['submit_name']))
                    $aSpecificValues = array ($this->aParams['db']['submit_name'] => 1);
                foreach ($oFormNested->aInputs as $r) {
                    $sName = str_replace('[]', '', $r['name']);
                    $aValue = $this->getSubmittedValue($sName, $this->aFormAttrs['method']);
                    $aSpecificValues[$sName] = isset($aValue[$i]) ? $aValue[$i] : '';
                }
                $oFormNested->initChecker(array(), $aSpecificValues);

                // if nested form in invalid - then the whole worm is failed
                if (!$oFormNested->isValid ())
                    $isValid = false;
            }

            if ($aNestedForms)
                $this->aInputs[$sKey]['ghost_template'] = $aNestedForms;

        }

        return $isValid;
    }





}

/** @} */
