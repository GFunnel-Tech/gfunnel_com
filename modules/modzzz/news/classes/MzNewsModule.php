<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    News News
 * @ingroup     ModzzzModules
 *
 * @{
 */

/**
 * News module
 */
class MzNewsModule extends BxBaseModTextModule
{
    function __construct(&$aModule)
    {
        parent::__construct($aModule);

        $CNF = &$this->_oConfig->CNF;
        $this->_aSearchableNamesExcept = array_merge($this->_aSearchableNamesExcept, array(
            $CNF['FIELD_PUBLISHED'],
            $CNF['FIELD_ALLOW_COMMENTS']
        ));
    }

    /**
     * Entry news for Timeline module
     */
    public function serviceGetTimelinePost($aEvent, $aBrowseParams = array())
    {
        $CNF = &$this->_oConfig->CNF;

        $aResult = parent::serviceGetTimelinePost($aEvent, $aBrowseParams);
        if(empty($aResult) || !is_array($aResult) || empty($aResult['date']))
            return $aResult;

        $aContentInfo = $this->_oDb->getContentInfoById($aEvent['object_id']);
        if($aContentInfo[$CNF['FIELD_PUBLISHED']] > $aResult['date'])
            $aResult['date'] = $aContentInfo[$CNF['FIELD_PUBLISHED']];

        return $aResult;
    }

 //begin una alpha update
    public function serviceCheckAllowedCommentsPost($iContentId, $sObjectComments) 
    {
        $CNF = &$this->_oConfig->CNF;
        $aContentInfo = $this->_oDb->getContentInfoById($iContentId);
        if ($aContentInfo && $aContentInfo[$CNF['FIELD_ALLOW_COMMENTS']] == 0)
            return false;
        
        return parent::serviceCheckAllowedCommentsPost($iContentId, $sObjectComments);
    }
	
	public function serviceCheckAllowedCommentsView($iContentId, $sObjectComments) 
    {
        $CNF = &$this->_oConfig->CNF;
        $aContentInfo = $this->_oDb->getContentInfoById($iContentId);
        if ($aContentInfo && $aContentInfo[$CNF['FIELD_ALLOW_COMMENTS']] == 0)
            return false;

        return parent::serviceCheckAllowedCommentsView($iContentId, $sObjectComments);
    }
 //end una alpha update
  
    public function serviceUpdateBreakingNews($iContentId, $aContentInfo=array())
    { 
		$this->_oDb->updateBreakingNews($iContentId, $aContentInfo);
    }

    /**
     *  Site News Block
     */ 
    public function serviceBrowseAdmin ($sUnitView = false, $bEmptyMessage = false, $bAjaxPaginate = true)
    { 
        return $this->_serviceBrowse ('admin', $sUnitView ? array('unit_view' => $sUnitView) : false, BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate); 
	}


    /**
     *  Local News Block
     */ 
    public function serviceBrowseLocal ($sUnitView = false, $bEmptyMessage = false, $bAjaxPaginate = true)
    { 
        return $this->_serviceBrowse ('local', $sUnitView ? array('unit_view' => $sUnitView) : false, BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate); 
	}

    /**
     *  Breaking News Block
     */ 
    public function serviceBrowseBreaking ($sUnitView = false, $bEmptyMessage = false, $bAjaxPaginate = true)
    { 
        return $this->_serviceBrowse ('breaking', $sUnitView ? array('unit_view' => $sUnitView) : false, BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate); 
	}

    /**
     *  
     */ 
    public function serviceBrowseCategory($sUnitView = false, $bEmptyMessage = true, $bAjaxPaginate = true, $aParams = [])
    {
        $CNF = &$this->_oConfig->CNF;

        $sType = 'category';

        $aParams = array_merge(['category' => bx_process_input(bx_get('category'), BX_DATA_INT)], $aParams);
        if($sUnitView)
            $aParams['unit_view'] = $sUnitView;

        $aBlock = $this->_serviceBrowse($sType, $aParams, BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate); 

		$iCategoryId = bx_process_input(bx_get('category'), BX_DATA_INT);
		$oCategory = BxDolCategory::getObjectInstance($CNF['OBJECT_CATEGORY']);
		$sCategoryTitle = $oCategory->getCategoryTitle($iCategoryId);
 
        $sTitle = _t("_mz_news_page_block_title_category_entries", $sCategoryTitle);

		$aBlock['title'] = $sTitle;

		return $aBlock;
	}

     /**
     *  
     */ 
    public function serviceBrowseArchive ($sUnitView = false, $bEmptyMessage = true, $bAjaxPaginate = true)
    {
         $aBlock = $this->_serviceBrowse ('archive', $sUnitView ? array('unit_view' => $sUnitView) : false, BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate);

		 $sPeriod = bx_process_input(bx_get('period'), BX_DATA_TEXT);
 
 		 $iPos = strpos($sPeriod, '-'); 
		 if ($iPos === false){  
			$iMonthId = 0;
			$iYearId = $sPeriod;
		 }else{
			list($iYearId, $iMonthId) = explode('-', $sPeriod);
		 }

		 if($iMonthId)
		 {
 			 $sMonthName = _t("_mz_news_month_" . $iMonthId);
		 }else{
			 $sMonthName = _t("_mz_news_month_all");
		 }

         $sTitle = _t("_mz_news_page_block_title_archive_entries", $sMonthName, $iYearId);
		 
		 $aBlock['title'] = $sTitle;
 
		 return $aBlock;
    }
  
    public function serviceArchiveList()
    { 
        $CNF = &$this->_oConfig->CNF;

		$iNowMonth = date("n")-1;
		$iNowYear = date("Y");
 
		//$iCreated = (int)db_value("SELECT MIN(`when`) FROM `mz_news_main` LIMIT 1");
		$iMonthYears = (int)getParam('mz_news_archive_month_years');
		$iStartYear = $iNowYear - (int)getParam('mz_news_archive_years');//($iCreated) ? date("Y", $iCreated) : $iNowYear;
  
        $aTmplYearVars = array();
		for( $iYear=$iNowYear; $iYear>=$iStartYear; $iYear-- ) 
		{ 
	
			$iYearItems = $this->_oDb->getItemsNumInArchive ($iYear, 0, false); 
			if(!$iYearItems) continue;

			$sBrowseYearUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_ARCHIVE'] . $iYear);

			if($iMonthYears)
			{
				$aTmplMonthVars = array();
				for( $iMonth=1; $iMonth<=12; $iMonth++ ) 
				{ 
					if($iYear==$iNowYear && $iMonth>$iNowMonth)
						break;

					$sPeriod = $iYear .'-'. $iMonth;
					$sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_ARCHIVE'] . $sPeriod);

 					$sMonthName = _t("_mz_news_month_" . $iMonth);
 
 					$iItems = $this->_oDb->getItemsNumInArchive ($iYear, $iMonth, false);
 
					$aTmplMonthVars[] = array(
						'url' => $sBrowseUrl,
						'title' => $sMonthName, 
						'bx_if:show_counter' => array(
							'condition' => true,
							'content' => array(
								'items' => $iItems,
							)
						), 
					); 
				}
 
				$sMonths = $this->_oTemplate->parseHtmlByName('archive_months.html', array(
					'bx_repeat:months' => $aTmplMonthVars
				));
			}

            $aTmplYearVars[] = array(
				'url' => $sBrowseYearUrl,
                'title' => $iYear,
                'bx_if:show_icon' => array(
                    'condition' => true,
                    'content' => array()
                ),  
                'bx_if:show_months' => array(
                    'condition' => $iMonthYears,
                    'content' => array(
						'months' => $sMonths 
					)
                ), 
            );

			if($iMonthYears) $iMonthYears--; 
        }
 
 		$this->_oTemplate->addCss (array('main.css'));

        return $this->_oTemplate->parseHtmlByName('archive.html', array(
            'bx_repeat:years' => $aTmplYearVars
        ));
    }

    public function getCategoryUrl($sValue)
    {
        $CNF = &$this->_oConfig->CNF;

		return BxDolPermalinks::getInstance()->permalink($CNF['URL_CATEGORY'] . $sValue); 
    }

    /**
     * Get all categories list
     * @param $bDisplayEmptyCats display categories with no items, true by default
     * @return categories list html
     */ 
    public function serviceCategoriesList ($sObject, $aParams = array())
    {
    	$bShowEmpty = isset($aParams['show_empty']) ? (bool)$aParams['show_empty'] : false;
    	$bDisplayEmptyCats = isset($aParams['show_empty_categories']) ? (bool)$aParams['show_empty_categories'] : false;
  
        $a = BxDolForm::getDataItems($sObject);
        if (!$a)
            return '';
 
        $aVars = array('bx_repeat:cats' => array());
        foreach ($a as $sValue => $sName) {
            if (!is_numeric($sValue) && !$sValue)
                continue;
            $iNum = $this->getItemsNum($sObject, $sValue);
            if (!$bDisplayEmptyCats && !$iNum)
                continue;
            $aVars['bx_repeat:cats'][] = array(
                'url' => $this->getCategoryUrl($sValue),
                'name' => $sName,
                'value' => $sValue,
                'num' => $iNum,
				'selected_class'=>''
            );
        }
  
        if (!$aVars['bx_repeat:cats'])
            return '';

        $aVars['bx_if:show_all'] = array(
                'condition' => false,
                'content' => array()
            );

        return $this->_oTemplate->parseHtmlByName('category_list.html', $aVars);
    }
 
    /**
     * Get number of items in the specified category
     * @param $sCategoryValue category value
     * @return number
     */
    public function getItemsNum($sObject, $sCategoryValue)
    { 
		$aObject = BxDolCategoryQuery::getCategoryObject($sObject); 
        return $this->_oDb->getItemsNumInCategory ($aObject, $sCategoryValue);
    }
 
}

/** @} */
