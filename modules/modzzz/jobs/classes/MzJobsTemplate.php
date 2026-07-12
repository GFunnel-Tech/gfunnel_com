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

/*
 * Module representation.
 */
class MzJobsTemplate extends BxBaseModTextTemplate
{
    function __construct(&$oConfig, &$oDb)
    {
        $this->MODULE = 'mz_jobs';

        parent::__construct($oConfig, $oDb);

        $this->aMethodsToCallAddJsCss[] = 'categories';
    }
 
    public function entryBreadcrumb($aContentInfo, $aTmplVarsItems = array())
    {
    	$CNF = &$this->_oConfig->CNF;

        $oPermalink = BxDolPermalinks::getInstance();

        $aTmplVarsItems = array();
        $this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_CATEGORY']], $oPermalink, $aTmplVarsItems);
        $aTmplVarsItems = array_reverse($aTmplVarsItems);
        
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aContentInfo[$CNF['FIELD_TITLE']])
        );

    	return parent::entryBreadcrumb($aContentInfo, $aTmplVarsItems);
    }

    public function categoriesList($aParams = array())
    {
        $CNF = &$this->_oConfig->CNF;

        $sResult = $this->_categoriesList(0, array(
			'category_type' => isset($aParams['type']) ? $aParams['type'] : '',
            'url' => bx_absolute_url(BxDolPermalinks::getInstance()->permalink($CNF['URL_CATEGORIES'], array('category' => '')))
        ));

        if(empty($sResult) && isset($aParams['show_empty']) && $aParams['show_empty'] === true)
            $sResult = MsgBox(_t('_Empty'));

        return $sResult;
    }

    /**
     * Use Gallery image for both because currently there is no Unit types with small thumbnails.
     */
    protected function getUnitThumbAndGallery ($aData)
    {
        list($sPhotoThumb, $sPhotoGallery) = parent::getUnitThumbAndGallery($aData);

        return array($sPhotoGallery, $sPhotoGallery);
    }

    protected function _categoriesList($iParentId, $aParams = array())
    {  
        $aCategories = $this->_oDb->getCategories(array('type' => 'parent_id', 'parent_id' => $iParentId, 'category_type' => $aParams['category_type']));

        $aTmplVars = array();
        foreach($aCategories as $aCategory) {
            $iItems = (int)$aCategory['items'];

            $sSibcategories = $this->_categoriesList($aCategory['id'], $aParams);
            if($iItems == 0 && empty($sSibcategories))
                continue;

            $aTmplVars[] = array(
                'url' => $aParams['url'] . $aCategory['id'],
                'title' => _t($aCategory['title']),
                'bx_if:show_icon' => array(
                    'condition' => !empty($aCategory['icon']),
                    'content' => array(
                        'icon' => $aCategory['icon'],
                    )
                ),
                'bx_if:show_counter' => array(
                    'condition' => $iItems != 0,
                    'content' => array(
                        'items' => $iItems,
                    )
                ),
                'bx_if:show_subcategories' => array(
                    'condition' => !empty($sSibcategories),
                    'content' => array(
                        'subcategories' => $sSibcategories
                    )
                )
            );
        }

        if(empty($aTmplVars))
            return '';

        return $this->parseHtmlByName('categories.html', array(
            'bx_repeat:categories' => $aTmplVars
        ));
    }

    protected function _entryBreadcrumb($iCategory, &$oPermalink, &$aTmplVarsItems)
    {
        $CNF = &$this->_oConfig->CNF;

        $aCategory = $this->_oDb->getCategories(array('type' => 'id', 'id' => $iCategory));
        if(empty($aCategory) || !is_array($aCategory))
            return;

        switch($aCategory['name'])
		{
			case 'seeking':
				$sUrl = bx_absolute_url($oPermalink->permalink($CNF['URL_HOME']));
			break;
			case 'wanted':
				$sUrl = bx_absolute_url($oPermalink->permalink($CNF['URL_WANTED']));
			break;
			default:
				$sUrl = bx_absolute_url($oPermalink->permalink($CNF['URL_CATEGORIES'], array($CNF['GET_PARAM_CATEGORY'] => $aCategory['id'])));
		}

        $aTmplVarsItems[] = array(
            'url' => $sUrl,
            'title' => bx_process_output(_t($aCategory['title']))
        );

        if(empty($aCategory['parent_id']))
            return;

        $this->_entryBreadcrumb((int)$aCategory['parent_id'], $oPermalink, $aTmplVarsItems);
    }
 
    protected function getUnit ($aData, $aParams = array())
    {
        $oModule = BxDolModule::getInstance($this->MODULE);
        $CNF = &$oModule->_oConfig->CNF;

        // get thumb url
        list($sPhotoThumb, $sPhotoGallery) = $this->getUnitThumbAndGallery($aData);
		
		if ($sPhotoGallery == '' && isset($CNF['PARAM_USE_GALERY_AS_COVER']) && getParam($CNF['PARAM_USE_GALERY_AS_COVER']) == 'on'){
			if(!empty($CNF['OBJECT_STORAGE_PHOTOS'])){
				$sStorage = $CNF['OBJECT_STORAGE_PHOTOS'];
				$oStorage = BxDolStorage::getObjectInstance($sStorage); 
				list($oTranscoder, $oTranscoderPreview) = $this->getAttachmentsImagesTranscoders($sStorage);
				$aGhostFiles = $oStorage->getGhosts ($this->getModule()->serviceGetContentOwnerProfileId($aData[$CNF['FIELD_ID']]), $aData[$CNF['FIELD_ID']]);
				if ($aGhostFiles){
					foreach ($aGhostFiles as $k => $a) {
						$sPhotoGallery = $oTranscoder->getFileUrl($a['id']);
						$sPhotoThumb  = $oTranscoderPreview->getFileUrl($a['id']);
						break; 
					}
				}
			}
		}

        // get entry url
        $sUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aData[$CNF['FIELD_ID']]));

        $oProfile = BxDolProfile::getInstanceMagic($aData[$CNF['FIELD_AUTHOR']]);
        
        $sTitle = $this->getTitle($aData);
        $sText = $this->getText($aData);
        $sSummary = $this->getSummary($aData, $sTitle, $sText, $sUrl);
        $sSummaryPlain = isset($CNF['PARAM_CHARS_SUMMARY_PLAIN']) && $CNF['PARAM_CHARS_SUMMARY_PLAIN'] ? BxTemplFunctions::getInstance()->getStringWithLimitedLength(strip_tags($sSummary), (int)getParam($CNF['PARAM_CHARS_SUMMARY_PLAIN'])) : '';

        if(!empty($CNF['OBJECT_METATAGS'])) {
            $oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS']);
            $sText = $oMetatags->metaParse($aData[$CNF['FIELD_ID']], $sText);
        }

        $aTmplVarsMeta = array();
        if(!empty($CNF['OBJECT_MENU_SNIPPET_META'])) {
            $oMenuMeta = BxDolMenu::getObjectInstance($CNF['OBJECT_MENU_SNIPPET_META'], $this);
            if($oMenuMeta) {
                $oMenuMeta->setContentId($aData[$CNF['FIELD_ID']]);
                $aTmplVarsMeta = array(
                    'meta' => $oMenuMeta->getCode()
                );
            }
        }
 
 	    $oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS']);

 		$sMinSalary = (isset($aData[$CNF['FIELD_MIN_SALARY']]) && $aData[$CNF['FIELD_MIN_SALARY']]) ? _t_format_currency((float)$aData[$CNF['FIELD_MIN_SALARY']]) : 0;
		$sMaxSalary = (isset($aData[$CNF['FIELD_MAX_SALARY']]) && $aData[$CNF['FIELD_MAX_SALARY']]) ? _t_format_currency((float)$aData[$CNF['FIELD_MAX_SALARY']]) : 0;
    
		$sSalary = '';
		if($sMinSalary && $sMaxSalary){
			$sSalary = $sMinSalary . ' - ' . $sMaxSalary; 
		}elseif($sMinSalary){
			$sSalary = $sMinSalary; 
	    }elseif($sMaxSalary){
			$sSalary = $sMaxSalary; 
		}
   
		if($aData[$CNF['FIELD_SALARY_TYPE']]){
			$aSalaryType = BxDolForm::getDataItems('mz_jobs_salary_type');
			$sSalaryType = $aSalaryType[$aData[$CNF['FIELD_SALARY_TYPE']]]; 
			$sSalary = $sSalary . ' ' . $sSalaryType; 
		}

		$sNegotiable = (isset($aData[$CNF['FIELD_SALARY_NEGOTIABLE']]) && $aData[$CNF['FIELD_SALARY_NEGOTIABLE']]) ? ($sSalary ? _t('_mz_jobs_caption_negotiable_short') : _t('_mz_jobs_caption_negotiable_long')) : '';
 
		$sCompanyLink = bx_absolute_url(BxDolPermalinks::getInstance()->permalink($CNF['URL_COMPANY'], array($CNF['GET_PARAM_COMPANY'] => $aData[$CNF['FIELD_COMPANY_URI']])));

        $aTmplVars = array (
            'class' => $this->_getUnitClass($aData,(isset($aParams['template_name']) ? $aParams['template_name'] : '')),
            'id' => $aData[$CNF['FIELD_ID']],
            'content_url' => $sUrl,
            'title' => $sTitle,
            'badges' => $oModule->serviceGetBadges($aData[$CNF['FIELD_ID']], false, true),
            'title_attr' => bx_html_attribute($sTitle),
            'summary' => $sSummary,
			'company' => $aData[$CNF['FIELD_CONTACT_COMPANY']],
			'company_url' => $sCompanyLink,
 
            'location' => $oMetatags->locationsIsEnabled() ? $oMetatags->locationsString($aData[$CNF['FIELD_ID']], false) : '',  
            'bx_if:salary' => array(
                'condition' => !empty(trim($sSalary .' '. $sNegotiable)),
                'content' =>  array (
					'salary' => trim($sSalary .' '. $sNegotiable),
				),
            ),
            'text' => $sText,
            'author' => $oProfile->getDisplayName(),
            'author_url' => $oProfile->getUrl(),
            'author_icon' => $oProfile->getIcon(),
            'author_thumb' => $oProfile->getThumb(),
            'author_avatar' => $oProfile->getAvatar(),
            'entry_posting_date' => bx_time_js($aData[$CNF['FIELD_ADDED']], BX_FORMAT_DATE),
            'module_name' => _t($CNF['T']['txt_sample_single']),
            'ts' => $aData[$CNF['FIELD_ADDED']],
            'bx_if:meta' => array(
                'condition' => !empty($aTmplVarsMeta),
                'content' => $aTmplVarsMeta
            ),
            'bx_if:thumb' => array (
                'condition' => $sPhotoThumb,
                'content' => array (
                    'title' => $sTitle,
                    'summary_attr' => bx_html_attribute($sSummaryPlain),
                    'content_url' => $sUrl,
                    'thumb_url' => $sPhotoThumb ? $sPhotoThumb : '',
                    'gallery_url' => $sPhotoGallery ? $sPhotoGallery : '',
                    'strecher' => str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ', 40),
                ),
            ),
            'bx_if:no_thumb' => array (
                'condition' => !$sPhotoThumb,
                'content' => array (
                    'module_icon' => $CNF['ICON'],
                    'content_url' => $sUrl,
                    'summary_plain' => $sSummaryPlain,
                    'strecher' => mb_strlen($sSummaryPlain) > 240 ? '' : str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ', round((240 - mb_strlen($sSummaryPlain)) / 6)),
                ),
            ),
        );

        if(isset($aParams['template_vars']) && is_array($aParams['template_vars']))
            $aTmplVars = array_merge($aTmplVars, $aParams['template_vars']);

        // generate html
        return $aTmplVars;
    }

 

}

/** @} */
