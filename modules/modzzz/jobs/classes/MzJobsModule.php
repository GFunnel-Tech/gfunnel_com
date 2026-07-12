<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Jobs Jobs
 * @ingroup     ModzzzModules
 *
 * @{
 */

/**
 * Jobs module
 */
class MzJobsModule extends BxBaseModTextModule
{
    function __construct(&$aModule)
    {
        parent::__construct($aModule);

        $CNF = &$this->_oConfig->CNF;

        $this->_aSearchableNamesExcept = array_merge($this->_aSearchableNamesExcept, array(
            $CNF['FIELD_CATEGORY_VIEW'],
            $CNF['FIELD_CATEGORY_SELECT'],
            $CNF['FIELD_CONTACT_EMAIL'],
            $CNF['FIELD_CONTACT_PHONE'],
            $CNF['FIELD_CONTACT_MOBILE'],
            $CNF['FIELD_CONTACT_WEBSITE'],  
            $CNF['FIELD_DEADLINE'],  
            $CNF['FIELD_VACANCIES'],   
            $CNF['FIELD_ALLOW_MESSAGE'],   
            $CNF['FIELD_APPLICATION_LINK'],  
            $CNF['FIELD_RESPONSIBILITIES'],  
            $CNF['FIELD_SKILLS'],  
            $CNF['FIELD_SALARY']   
        ));
    }

    public function actionGetCategoryForm1111111()
    {
        if(bx_get('category') === false)
            return echoJson(array());

        return echoJson(array(
            'content' => $this->serviceGetCreatePostForm(array(
                'absolute_action_url' => true,
                'dynamic_mode' => true
            ))
        ));
    }

    public function actionGetCategoryForm()
    {
        if(($iCategory = bx_get('category')) === false || (int)$iCategory == 0)
            return echoJson(['msg' => _t('_mz_jobs_form_entry_input_category_select_err')]);

        return echoJson([
            'eval' => $this->_oConfig->getJsObject('form') . '.onSelectCategory(oData)',
            'content' => $this->serviceGetCreatePostForm([
                'absolute_action_url' => true,
                'dynamic_mode' => true
            ])
        ]);
    }

    public function actionInterested()
    {
        $CNF = &$this->_oConfig->CNF;

        $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);

        $aContentInfo = $this->_oDb->getContentInfoById($iContentId);
        if(empty($aContentInfo) || !is_array($aContentInfo))
            return echoJson(array());

        $iViewer = bx_get_logged_profile_id();        
        $oViewer = BxDolProfile::getInstance($iViewer);
        if(!$oViewer)
            return echoJson(array());

        $iContentAuthor = (int)$aContentInfo[$CNF['FIELD_AUTHOR']];
        if($iContentAuthor == $iViewer)
            return echoJson(array('msg' => _t('_mz_jobs_txt_err_your_own')));

        if($this->_oDb->isInterested($iContentId, $iViewer))
            return echoJson(array('msg' => _t('_mz_jobs_txt_err_duplicate')));

        if(!$this->_oDb->insertInterested(array('entry_id' => $iContentId, 'profile_id' => $iViewer)))
            return echoJson(array('msg' => _t('_mz_jobs_txt_err_cannot_perform_action')));

        sendMailTemplate($CNF['ETEMPLATE_INTERESTED'], 0, $iContentAuthor, array(
            'viewer_name' => $oViewer->getDisplayName(),
            'viewer_url' => $oViewer->getUrl(),
            'item_name' => $aContentInfo[$CNF['FIELD_TITLE']],
            'item_url' => bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php', array(
                'i' => $CNF['URI_VIEW_ENTRY'], 
                $CNF['FIELD_ID'] => $iContentId
            )))
        ));

        return echoJson(array('msg' => _t('_mz_jobs_txt_msg_author_notified')));
    }

    public function serviceGetSafeServices()
    {
        $a = parent::serviceGetSafeServices();
        return array_merge($a, array (
            'EntityReviews' => '',
            'EntityReviewsRating' => '',
            'CategoriesList' => '',
            'BrowseCategory' => '',
            'BrowseLocal' => '', 
            'BrowseRemote' => '', 
        ));
    }

	//BEGIN - UNA 13 beta
    public function serviceUpdateCategoriesStats($mixedContentInfo = false)
    {
        $aContentInfo = [];
        if(!empty($mixedContentInfo))
            $aContentInfo = !is_array($mixedContentInfo) ? $this->_oDb->getContentInfoById((int)$mixedContentInfo) : $mixedContentInfo;

		if(empty($aContentInfo)) return;

        $iCategoryId = $iSubCategory = 0;
        if(!empty($aContentInfo['category']))
            $iCategoryId = (int)$aContentInfo['category'];
 
        return $this->serviceUpdateCategoriesStatsByCategory($iCategoryId);
    }

    public function serviceUpdateCategoriesStatsByCategory($iCategoryId = 0)
    {
        $aParams = ['type' => 'collect_stats'];
        if($iCategoryId)
            $aParams['category_id'] = (int)$iCategoryId;

        $aStats = $this->_oDb->getCategories($aParams);
        if(empty($aStats) || !is_array($aStats))
            return true;

        $iUpdated = 0;
        foreach($aStats as $aStat)
            if($this->_oDb->updateCategory(array('items' => $aStat['count']), array('id' => $aStat['id'])))
                $iUpdated++;

        return count($aStats) == $iUpdated;
    }
    //END - UNA 13 beta 
 
/* removed UNA 13 beta  
    public function serviceUpdateCategoriesStats()
    {
        $aStats = $this->_oDb->getCategories(array('type' => 'collect_stats'));
        if(empty($aStats) || !is_array($aStats))
            return true;

        $iUpdated = 0;
        foreach($aStats as $aStat)
            if($this->_oDb->updateCategory(array('items' => $aStat['count']), array('id' => $aStat['id'])))
                $iUpdated++;

        return count($aStats) == $iUpdated;
    }
 */

    public function serviceGetCategoryOptions($iParentId, $bPleaseSelect = false)
    {
        $aValues = array();
        if($bPleaseSelect)
            $aValues[] = array('key' => '', 'value' => _t('_sys_please_select'));

        $this->_getCategoryOptions($iParentId, $aValues);

        return $aValues;
    }

    protected function _getCategoryOptions1111111($iParentId, &$aValues)
    {
        $aCategories = $this->_oDb->getCategories(array('type' => 'parent_id', 'parent_id' => $iParentId));
        foreach($aCategories as $aCategory) {
            $aValues[] = array('key' => $aCategory['id'], 'value' => str_repeat('--', (int)$aCategory['level']) . ' ' . _t($aCategory['title']));

            $this->_getCategoryOptions($aCategory['id'], $aValues);
        }
    }

	  	//UNA 13 RC2 
    protected function _getCategoryOptions($iParentId, &$aValues)
    {
        $aCategories = $this->_oDb->getCategories(array('type' => 'parent_id', 'parent_id' => $iParentId, 'active' => true));
        foreach($aCategories as $aCategory) {
            $aValues[] = array('key' => $aCategory['id'], 'value' => str_repeat('--', (int)$aCategory['level']) . ' ' . _t($aCategory['title']));

            $this->_getCategoryOptions($aCategory['id'], $aValues);
        }
    }

    public function serviceGetSearchableFields($aInputsAdd = array())
    {
        $CNF = &$this->_oConfig->CNF;

        $aResult = parent::serviceGetSearchableFields(array_merge($aInputsAdd, $this->_getSearchableFields()));
        unset($aResult[$CNF['FIELD_CATEGORY_VIEW']], $aResult[$CNF['FIELD_CATEGORY_SELECT']]);
        unset($aResult[$CNF['FIELD_SALARY']]);
 
        return $aResult;
    }
 
    public function serviceGetSearchableFieldsExtended($aInputsAdd = array())
    {
        $CNF = &$this->_oConfig->CNF;
 
        $aInputsAdd = array_merge($aInputsAdd, $this->_getSearchableFields());

        if(isset($aInputsAdd[$CNF['FIELD_CATEGORY']])) {
            $aInputsAdd[$CNF['FIELD_CATEGORY']]['type'] = 'select';
            $aInputsAdd[$CNF['FIELD_CATEGORY']]['values_src'] = BxDolService::getSerializedService($this->_oConfig->getName(), 'get_category_options', array(0));
        }
 
        if(isset($aInputsAdd[$CNF['FIELD_MAX_SALARY']])) {
            $aInputsAdd[$CNF['FIELD_MAX_SALARY']]['search_type'] = 'text_range';
            $aInputsAdd[$CNF['FIELD_MAX_SALARY']]['search_operator'] = 'between';
        }

        if(isset($aInputsAdd[$CNF['FIELD_MIN_SALARY']])) {
            $aInputsAdd[$CNF['FIELD_MIN_SALARY']]['search_type'] = 'text_range';
            $aInputsAdd[$CNF['FIELD_MIN_SALARY']]['search_operator'] = 'between';
        }
   
        return parent::serviceGetSearchableFieldsExtended($aInputsAdd);
    }

    public function serviceEntityCreate ($sParams = false)
    {
        if(($sDisplay = $this->getCategoryDisplay('add')) !== false) {
            if(empty($sParams) || !is_array($sParams))
                $sParams = array();

            $sParams['display'] = $sDisplay;
        }

        return parent::serviceEntityCreate($sParams);
    }

    /**
     * @page service Service Calls
     * @section mz_jobs Jobs 
     * @subsection mz_jobs-page_blocks Page Blocks
     * @subsubsection mz_jobs-entity_reviews entity_reviews
     * 
     * @code bx_srv('mz_jobs', 'entity_reviews', [...]); @endcode
     * 
     * Get reviews for particular content
     * @param $iContentId content ID
     * 
     * @see MzJobsModule::serviceEntityReviews
     */
    /** 
     * @ref mz_jobs-entity_reviews "entity_reviews"
     */
    public function serviceEntityReviews($iContentId = 0)
    {
        $CNF = &$this->_oConfig->CNF;
        if(empty($CNF['OBJECT_REVIEWS']))
            return false;

        return $this->_entityComments($CNF['OBJECT_REVIEWS'], $iContentId);
    }
    
    /**
     * @page service Service Calls
     * @section mz_jobs Jobs 
     * @subsection mz_jobs-page_blocks Page Blocks
     * @subsubsection mz_jobs-entity_reviews_rating entity_reviews_rating
     * 
     * @code bx_srv('mz_jobs', 'entity_reviews_rating', [...]); @endcode
     * 
     * Get reviews rating for particular content
     * @param $iContentId content ID
     * 
     * @see MzJobsModule::serviceEntityReviewsRating
     */
    /** 
     * @ref mz_jobs-entity_reviews_rating "entity_reviews_rating"
     */
    public function serviceEntityReviewsRating($iContentId = 0)
    {
        $CNF = &$this->_oConfig->CNF;
        if(empty($CNF['OBJECT_REVIEWS']))
            return false;

        if(!$iContentId)
            $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);

        if(!$iContentId)
            return false;

        $oCmts = BxDolCmts::getObjectInstance($CNF['OBJECT_REVIEWS'], $iContentId);
        if (!$oCmts || !$oCmts->isEnabled())
            return false;

        return $oCmts->getRatingBlock(array('in_designbox' => false));
    }

    public function serviceEntityResponsibilities($iContentId=0)
    {
		$CNF = &$this->_oConfig->CNF; 

		if(!$iContentId)
			$iContentId = bx_get('id');
        $aContentInfo = $this->_oDb->getContentInfoById($iContentId);
 
		if(empty(trim($aContentInfo[$CNF['FIELD_RESPONSIBILITIES']]))) return;

		$aVars = array(
			'content' => $aContentInfo[$CNF['FIELD_RESPONSIBILITIES']], 
		);

		return $this->_oTemplate->parseHtmlByName('entry_content.html', $aVars); 
	}

    public function serviceEntitySkills($iContentId=0)
    {
		$CNF = &$this->_oConfig->CNF; 

		if(!$iContentId)
			$iContentId = bx_get('id');
        $aContentInfo = $this->_oDb->getContentInfoById($iContentId);
 
		if(empty(trim($aContentInfo[$CNF['FIELD_SKILLS']]))) return;

		$aVars = array(
			'content' => $aContentInfo[$CNF['FIELD_SKILLS']], 
		);

		return $this->_oTemplate->parseHtmlByName('entry_content.html', $aVars); 
	}
  
    public function serviceEntityApply($iContentId=0)
    {
		$CNF = &$this->_oConfig->CNF; 

		if(!$iContentId)
			$iContentId = bx_get('id');
        $aContentInfo = $this->_oDb->getContentInfoById($iContentId);
 
		if(empty(trim($aContentInfo[$CNF['FIELD_APPLICATION_LINK']]))) return;

		$aVars = array(
			'url' => $this->bx_linkify($aContentInfo[$CNF['FIELD_APPLICATION_LINK']], _t('_mz_jobs_link_title_apply_job')), 
		);

		return $this->_oTemplate->parseHtmlByName('entry_apply.html', $aVars); 
	}
   
	function bx_linkify($sLink, $sText, $sAttrs = '', $bHtmlSpecialChars = false)
	{
		if ($bHtmlSpecialChars)
			$sLink = htmlspecialchars($sLink, ENT_NOQUOTES, 'UTF-8');

		$re = "@\b((https?://)|(www\.))(([0-9a-zA-Z_!~*'().&=+$%-]+:)?[0-9a-zA-Z_!~*'().&=+$%-]+\@)?(([0-9]{1,3}\.){3}[0-9]{1,3}|([0-9a-zA-Z_!~*'()-]+\.)*([0-9a-zA-Z][0-9a-zA-Z-]{0,61})?[0-9a-zA-Z]\.[a-zA-Z]{2,16})(:[0-9]{1,4})?((/[0-9a-zA-Z_!~*'().;?:\@&=+$,%#-]+)*/?)@";
		preg_match_all($re, $sLink, $matches, PREG_OFFSET_CAPTURE);

		$matches = $matches[0];

		if ($i = count($matches))
			$bAddNofollow = getParam('sys_add_nofollow') == 'on';

		while ($i--)
		{
			$sAttrsLocal = $sAttrs;
			$url = $matches[$i][0];
			if (!preg_match('@^https?://@', $url))
				$url = 'http://'.$url;

			if (strncmp(BX_DOL_URL_ROOT, $url, strlen(BX_DOL_URL_ROOT)) !== 0) {
				$sAttrsLocal .= ' target="_blank" ';
				if ($bAddNofollow)
					$sAttrsLocal .= ' rel="nofollow" ';
			}

			$sLink = substr_replace($sLink, '<a ' . $sAttrsLocal . ' href="'.$url.'">'.$sText.'</a>', $matches[$i][1], strlen($matches[$i][0]));
		}
		
		// email pattern
		$mail_pattern = "/([A-z0-9\._-]+\@[A-z0-9_-]+\.)([A-z0-9\_\-\.]{1,}[A-z])/";
		$sLink = preg_replace($mail_pattern, '<a href="mailto:$1$2">$1$2</a>', $sLink);
		
		return $sLink;
	}
 
    /**
     * @page service Service Calls
     * @section mz_jobs Jobs 
     * @subsection mz_jobs-page_blocks Page Blocks
     * @subsubsection mz_jobs-categories_list categories_list
     * 
     * @code bx_srv('mz_jobs', 'categories_list', [...]); @endcode
     * 
     * Get reviews rating for particular content
     * @param $aParams additional params array, such as 'show_empty'
     * 
     * @see MzJobsModule::serviceCategoriesList
     */
    /** 
     * @ref mz_jobs-categories_list "categories_list"
     */
    public function serviceCategoriesList($aParams = array())
    {
        if(!isset($aParams['show_empty']))
            $aParams['show_empty'] = true;
 
        return $this->_oTemplate->categoriesList($aParams);
    }

    /**
     * @page service Service Calls
     * @section mz_jobs Jobs 
     * @subsection mz_jobs-browse Browse
     * @subsubsection mz_jobs-browse_category browse_category
     * 
     * @code bx_srv('mz_jobs', 'browse_category', [...]); @endcode
     * 
     * Get reviews rating for particular content
     * @param $iCategoryId category ID
     * @param $aParams additional params array, such as empty_message, ajax_paginate, etc
     * 
     * @see MzJobsModule::serviceBrowseCategory
     */
    /**
     * @ref mz_jobs-browse_category "browse_category"
     */
    public function serviceBrowseCategory($sUnitView = false, $bEmptyMessage = true, $bAjaxPaginate = true, $aParams = [])
    {
        $sParamName = $sParamGet = 'category';

		$iCategoryId = 0;
 
        if(!$iCategoryId && bx_get($sParamGet) !== false)
            $iCategoryId = bx_process_input(bx_get($sParamGet), BX_DATA_INT);

        $bEmptyMessage = true;
        if(isset($aParams['empty_message'])) {
            $bEmptyMessage = (bool)$aParams['empty_message'];
            unset($aParams['empty_message']);
        }

        $bAjaxPaginate = true;
        if(isset($aParams['ajax_paginate'])) {
            $bAjaxPaginate = (bool)$aParams['ajax_paginate'];
            unset($aParams['ajax_paginate']);
        }

        $aBlock = $this->_serviceBrowse ($sParamName, array_merge(array($sParamName => $iCategoryId), $aParams), BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate);
        if(!empty($aBlock['content'])) {
            $aCategory = $this->_oDb->getCategories(array('type' => 'id', 'id' => $iCategoryId));
            if(!empty($aCategory['title']))
                $aBlock['title'] = _t('_mz_jobs_page_block_title_entries_by_category_mask', _t($aCategory['title']));
        }

        return $aBlock;
    }


    /**
     * Common methods.
     */
    public function processMetasAdd($iContentId)
    {
        if(!parent::processMetasAdd($iContentId))
            return false;

        $CNF = &$this->_oConfig->CNF;

        $oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS']);
        $aContentInfo = $this->_oDb->getContentInfoById($iContentId);

        $aCategoryTypes = $this->_oDb->getCategoryTypes(array('type' => 'all'));
        foreach($aCategoryTypes as $aCategoryType)
            $oMetatags->metaAddAuto($iContentId, $aContentInfo, $CNF, $aCategoryType['display_add']);

        return true;
    }

    public function processMetasEdit($iContentId, $oForm)
    {
        if(!parent::processMetasEdit($iContentId, $oForm))
            return false;
        
        $CNF = &$this->_oConfig->CNF;

        $oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS']);
        $aContentInfo = $this->_oDb->getContentInfoById($iContentId);
        
        $aCategoryTypes = $this->_oDb->getCategoryTypes(array('type' => 'all'));
        foreach($aCategoryTypes as $aCategoryType)
            $oMetatags->metaAddAuto($iContentId, $aContentInfo, $CNF, $aCategoryType['display_edit']);

        return true;
    }

    public function getCategoryDisplay($sDisplayType, $iCategory = 0)
    {
        if(empty($iCategory) && bx_get('category') !== false)
            $iCategory = (int)bx_get('category');

        if(empty($iCategory))
            return false;

        $aCategory = $this->_oDb->getCategories(array('type' => 'id_full', 'id' => $iCategory));

        $sKey = 'type_display_' . $sDisplayType;
        if(empty($aCategory[$sKey]))
            return false;

        return $aCategory[$sKey];
    }
  
    /**
     * Internal methods.
     */
    protected function _serviceEntityForm ($sFormMethod, $iContentId = 0, $sDisplay = false, $sCheckFunction = false, $bErrorMsg = true)
    {
        $CNF = &$this->_oConfig->CNF;
 
        $mixedContent = $this->_getContent($iContentId, true);
        if($mixedContent === false)
            return false;

        list($iContentId, $aContentInfo) = $mixedContent;

        $sDisplayType = false;
        switch($sFormMethod) {
            case 'editDataForm':
                $sDisplayType = 'edit';
                break;
            case 'viewDataForm':
            case 'viewDataEntry':
                $sDisplayType = 'view';
                break;
        }

        if($sDisplay === false && $sDisplayType !== false && ($sDisplayNew = $this->getCategoryDisplay($sDisplayType, $aContentInfo[$CNF['FIELD_CATEGORY']])) !== false)
            $sDisplay = $sDisplayNew;
 
        return parent::_serviceEntityForm ($sFormMethod, $iContentId, $sDisplay, $sCheckFunction, $bErrorMsg);
    }
 
    public function serviceGetYesNo ($bPleaseSelect = false)
    {
        $aValues = array(); 
        $a = BxDolForm::getDataItems('mz_jobs_yes_no');
        
        foreach ($a as $sKey => $sValue) { 

           if (!$bPleaseSelect && $sKey==0)
                continue;

            $aValues[] = array('key' => $sKey, 'value' => $sValue); 
        }
  
        return $aValues;   
    }
 
    protected function _getSearchableFields($mixedDisplayType = '')
    {
        $CNF = &$this->_oConfig->CNF;

        if(empty($mixedDisplayType))
            $mixedDisplayType = array('add', 'edit');

        $aResult = array();
        $aDisplays = $this->_oDb->getDisplays($this->_oConfig->getName() . '_entry', $mixedDisplayType);
        foreach($aDisplays as $aDisplay) {
            if($aDisplay['display_name'] == $CNF['OBJECT_FORM_ENTRY_DISPLAY_ADD'])
                continue;

            $oForm = BxDolForm::getObjectInstance($CNF['OBJECT_FORM_ENTRY'], $aDisplay['display_name'], $this->_oTemplate);
            if(!$oForm)
                continue;

            $aResult = array_merge($aResult, $oForm->aInputs);
        }

        return $aResult;
    }
    
    protected function _getContentForTimelinePost($aEvent, $aContentInfo, $aBrowseParams = array())
    {
        $CNF = &$this->_oConfig->CNF;

        $bDynamic = isset($aBrowseParams['dynamic_mode']) && (bool)$aBrowseParams['dynamic_mode'] === true;

        $sCategory = '';
        if(!empty($CNF['FIELD_CATEGORY']) && !empty($aContentInfo[$CNF['FIELD_CATEGORY']])) {
            $iCategory = (int)$aContentInfo[$CNF['FIELD_CATEGORY']];
            $aCategory = $this->_oDb->getCategories(array('type' => 'id', 'id' => $iCategory));
            $sCategory = _t($aCategory['title']);
            $sCategoryLink = bx_absolute_url(BxDolPermalinks::getInstance()->permalink($CNF['URL_CATEGORIES'], array($CNF['GET_PARAM_CATEGORY'] => $iCategory)));
        }

        $sPrice = _t('_mz_jobs_txt_free');
		/*
        if(!empty($CNF['FIELD_PRICE']) && !empty($aContentInfo[$CNF['FIELD_PRICE']])){
			$fPrice = (isset($aContentInfo[$CNF['FIELD_SALE_PRICE']])) ? $aContentInfo[$CNF['FIELD_SALE_PRICE']] : $aContentInfo[$CNF['FIELD_PRICE']];
            $sPrice = _t_format_currency((float)$fPrice);
		}*/

        $sInclude = $this->_oTemplate->addCss(array('timeline.css'), $bDynamic);

        $aResult = parent::_getContentForTimelinePost($aEvent, $aContentInfo, $aBrowseParams);
        $aResult['text'] = $this->_oTemplate->parseHtmlByName('timeline_post_text.html', array(
            'category_link' => $sCategoryLink,
            'category_title' => $sCategory,
            'category_title_attr' => bx_html_attribute($sCategory),
            'price' => $sPrice,
            'text' => $aResult['text']
        ));// . ($bDynamic ? $sInclude : '');

        
        return $aResult;
    }

    /** 
     * @ref bx_base_text-browse_rent "browse_rent"
     */
    public function serviceBrowseWanted ($sUnitView = false, $bEmptyMessage = true, $bAjaxPaginate = true)
    {
        return $this->_serviceBrowse ('wanted', $sUnitView ? array('unit_view' => $sUnitView) : false, BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate);
    }
 
    public function serviceEntityConvoCreate ($iContentId = 0)
    { 
        $CNF = &$this->_oConfig->CNF;
  
        if(!$iViewer = bx_get_logged_profile_id()) return;

		$oCnvModule = BxDolModule::getInstance('bx_convos');  
        if (!$oCnvModule) return;

        if (!$iContentId)
            $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);
 
        $aContentInfo = $this->_oDb->getContentInfoById($iContentId);

		if(!$aContentInfo["allow_message"]) return;
 
		$iProfileId = $aContentInfo[$CNF['FIELD_AUTHOR']];
  
		$sAbsUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i=' . $oCnvModule->_oConfig->CNF['URI_ADD_ENTRY']));

		bx_set('profiles', $iProfileId);
        bx_set('absolute_action_url', $sAbsUrl);

		$sMsg = '';
		$bMsg = bx_get('msg');

		if($bMsg){
			$oProfile = BxDolProfile::getInstance($iProfileId); 
			$aVars = array('message' => _t('_mz_jobs_msg_success',$oProfile->getDisplayName())); 
			$sMsg = $this->_oTemplate->parseHtmlByName('message_success.html', $aVars);  
		}

		$aBlock = array();
        $aBlock['content'] = $sMsg . $oCnvModule->serviceEntityCreate(); 
		$aBlock['title'] = _t("_mz_jobs_page_block_title_send_msg");

		return $aBlock;
    } 
 
    /**
     *  Local Block
     */ 
    public function serviceBrowseLocal ($sUnitView = false, $bEmptyMessage = false, $bAjaxPaginate = true)
    { 
        return $this->_serviceBrowse ('local', $sUnitView ? array('unit_view' => $sUnitView) : false, BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate); 
	}

    /**
     *  Remote Block
     */ 
    public function serviceBrowseRemote ($sUnitView = false, $bEmptyMessage = false, $bAjaxPaginate = true)
    { 
        return $this->_serviceBrowse ('remote', $sUnitView ? array('unit_view' => $sUnitView) : false, BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate); 
	}
  
    /**
     *  Job Types Block
     */ 
    public function serviceBrowseJobTypes ($sUnitView = false, $bEmptyMessage = false, $bAjaxPaginate = true)
    {  
        $CNF = &$this->_oConfig->CNF;
		$iId = bx_process_input(bx_get($CNF['GET_PARAM_JOB_TYPE']), BX_DATA_INT);
 
        $aBlock = $this->_serviceBrowse ('job_types', array('unit_view' => $sUnitView, $CNF['GET_PARAM_JOB_TYPE'] => $iId), BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate);
 
        $aJobTypes = BxDolForm::getDataItems('mz_jobs_type');

        $aBlock['title'] = _t("_mz_jobs_page_block_title_type_jobs", $aJobTypes[$iId]);
 
		return $aBlock; 
	}
 


	function serviceTypesList() 
	{ 
        $CNF = &$this->_oConfig->CNF;
        $aJobTypes = BxDolForm::getDataItems('mz_jobs_type');
   
		$aVars['bx_repeat:types'] = array();        
		foreach($aJobTypes as $iKey=>$sTypeValue){ 
			if(!$iKey) continue;
			$sTypeUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink($CNF['URL_JOB_TYPE'],  array($CNF['GET_PARAM_JOB_TYPE'] => $iKey)));
			$aVars['bx_repeat:types'][] = array(
				'type_name' => $sTypeValue, 
				'type_url' => $sTypeUrl,
 			); 
		}
   
		return $this->_oTemplate->parseHtmlByName('type_list.html', $aVars);   
	}
  
    /**
     *  Companies Block
     */ 
    public function serviceBrowseCompanies ($sUnitView = false, $bEmptyMessage = false, $bAjaxPaginate = true)
    { 
        $CNF = &$this->_oConfig->CNF;

		$aCompanies = $this->_oDb->getCompanies();
 
        $aTmplVars = array();
        foreach($aCompanies as $aCompany) { 
 
            $sCompanyLink = bx_absolute_url(BxDolPermalinks::getInstance()->permalink($CNF['URL_COMPANY'], array($CNF['GET_PARAM_COMPANY'] => $aCompany[$CNF['FIELD_COMPANY_URI']])));
 
            $aTmplVars[] = array(
                'company' => $aCompany[$CNF['FIELD_CONTACT_COMPANY']],
                'url' => $sCompanyLink, 
            );
        }
 
        if(empty($aTmplVars))
            return '';
  

		$this->_oTemplate->addCssJs();

        return $this->_oTemplate->parseHtmlByName('browse_companies.html', array(
            'bx_repeat:companies' => $aTmplVars
        ));
    }
   
    /**
     *  Job Companies Block
     */ 
    public function serviceBrowseCompany ($sUnitView = false, $bEmptyMessage = false, $bAjaxPaginate = true)
    {  
        $CNF = &$this->_oConfig->CNF;
		$sUri = bx_process_input(bx_get($CNF['GET_PARAM_COMPANY']), BX_DATA_TEXT);
 
        $aBlock = $this->_serviceBrowse ('company', array('unit_view' => $sUnitView, $CNF['GET_PARAM_COMPANY'] => $sUri), BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate);
 
        $sCompany = $this->_oDb->getCompanyByUri($sUri);

        $aBlock['title'] = _t("_mz_jobs_page_block_title_company_entries", $sCompany);
 
		return $aBlock; 
	}

	//begin UNA 13 beta 
	public function serviceGetNotificationsData()
    {
        $sModule = $this->_aModule['name'];

        $sEventPrivacy = $sModule . '_allow_view_event_to';
        if(BxDolPrivacy::getObjectInstance($sEventPrivacy) === false)
            $sEventPrivacy = '';

        $aResult = parent::serviceGetNotificationsData();
        $aResult['handlers'] = array_merge($aResult['handlers'], array(
            array('group' => $sModule . '_interest', 'type' => 'insert', 'alert_unit' => $sModule, 'alert_action' => 'doInterest', 'module_name' => $sModule, 'module_method' => 'get_notifications_interest', 'module_class' => 'Module', 'module_event_privacy' => $sEventPrivacy)
        ));

        $aResult['settings'] = array_merge($aResult['settings'], array(
            array('group' => 'interest', 'unit' => $sModule, 'action' => 'doInterest', 'types' => array('personal'))
        ));

        $aResult['alerts'] = array_merge($aResult['alerts'], array(
            array('unit' => $sModule, 'action' => 'doInterest')
        ));

        return $aResult; 
    }
    
    public function serviceGetNotificationsInterest($aEvent)
    {
    	$CNF = &$this->_oConfig->CNF;

    	$iContentId = (int)$aEvent['object_id'];
    	$aContentInfo = $this->_oDb->getContentInfoById($iContentId);
        if(empty($aContentInfo) || !is_array($aContentInfo))
            return array();

        $iInterestedId = (int)$aEvent['subobject_id'];
        $aInterestedInfo = $this->_oDb->getInterested(array('type' => 'id', 'id' => $iInterestedId));
        if(empty($aInterestedInfo) || !is_array($aInterestedInfo))
            return array();

        $sEntryUrl = '{bx_url_root}' . BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $iContentId);

        return array(
            'entry_sample' => $CNF['T']['txt_sample_single'],
            'entry_url' => $sEntryUrl,
            'entry_caption' => $aContentInfo[$CNF['FIELD_TITLE']],
            'entry_author' => $aContentInfo[$CNF['FIELD_AUTHOR']],
            'subentry_sample' => $CNF['T']['txt_sample_interest_single'],
            'subentry_url' => '',
            'lang_key' => '_mz_jobs_txt_ntfs_subobject_interested', //may be empty or not specified. In this case the default one from Notification module will be used.
        );
    }
    //end UNA 13 beta 

	//UNA 13 RC2 
    public function onPublished($iContentId)
    {
        $this->serviceUpdateCategoriesStats($iContentId);

        parent::onPublished($iContentId);
    }

	protected function _actionChangeStatus($sStatus)
    {
        $CNF = &$this->_oConfig->CNF;

        $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);
        $aContentInfo = $this->_oDb->getContentInfoById($iContentId);

        if(($mixedResult = $this->checkAllowedEdit($aContentInfo)) !== CHECK_ACTION_RESULT_ALLOWED)
            return array('msg' => $mixedResult);

        if(!$this->_oDb->updateEntriesBy(array($CNF['FIELD_STATUS'] => $sStatus), array($CNF['FIELD_ID'] => $iContentId)))
            return array('msg' => _t('_mz_jobs_txt_err_cannot_perform_action'));

        $this->serviceUpdateCategoriesStats($aContentInfo);

        return array(
            'reload' => 1
        );
    }
	//END - UNA 13 RC2  
 
}

/** @} */
