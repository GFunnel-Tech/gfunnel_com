<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Goal Goal
 * @ingroup     ModzzzModules
 *
 * @{
 */

/*
 * Module representation.
 */
class MzGoalTemplate extends BxBaseModTextTemplate
{
    function __construct(&$oConfig, &$oDb)
    {
        $this->MODULE = 'mz_goal';

        parent::__construct($oConfig, $oDb);

        $this->aMethodsToCallAddJsCss[] = 'categories';
    }
 
    public function entryContent ($aData, $sFieldName)
    { 
        $CNF = &$this->getModule()->_oConfig->CNF;
        
        $aVars = $this->getEntertainText($aData);
 
        if(!isset($aData[$CNF[$sFieldName]]))
            return '';

		$sResult = bx_process_output($aData[$CNF[$sFieldName]], BX_DATA_HTML);
        if(empty($sResult))
            return '';
  
        return $this->parseHtmlByName('entry_content.html', array('content'=>$sResult));
    }
 
	public function supporter_unit($aData)
    {  
 		$oProfile = BxDolProfile::getInstanceMagic($aData['profile_id']); 
  
		$aInfo = $oProfile->getInfo();  
		$oPerson = BxDolModule::getInstance($aInfo['type']); 

		return $oPerson->serviceGetSearchResultUnit ($aInfo['content_id']);  
    }
 

//begin sponsor

	public function sponsor_main_unit($aData)
    {
 
        $oPermalink = BxDolPermalinks::getInstance();
 
    	$oModule = $this->getModule();

        $CNF = &$oModule->_oConfig->CNF;

        $iProfile = bx_get_logged_profile_id();
 
        $bDeleteSponsor = $aData[$CNF['FIELD_AUTHOR']] == $iProfile;
  
		$iId = (int)$aData[$CNF['FIELD_ID']];
  
		$oAuthor = BxDolProfile::getInstanceMagic($aData[$CNF['FIELD_AUTHOR']]);
 
        $sUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_SPONSOR'] . '&id=' . $iId));

        $sTitle = $this->getTitle($aData);
        $sText = $this->getText($aData);
        $sSummary = $this->getSummary($aData, $sTitle, $sText, $sUrl); 
        $sSummaryPlain = isset($CNF['PARAM_CHARS_SUMMARY_PLAIN']) && $CNF['PARAM_CHARS_SUMMARY_PLAIN'] ? BxTemplFunctions::getInstance()->getStringWithLimitedLength(strip_tags($sSummary), (int)getParam($CNF['PARAM_CHARS_SUMMARY_PLAIN'])) : '';
  
        $this->addCss('main.css');
   
		return $this->parseHtmlByName('unit_sponsor.html', array( 

            'class' => $this->_getUnitClass($aData,(isset($aParams['template_name']) ? $aParams['template_name'] : '')),
			'id' => $iId,
			'title' => $sTitle,
            'title_attr' => bx_html_attribute($sTitle),  
            'summary' => $sSummary,
            'text' => $sText, 
			'content_url' => $sUrl, 
		)); 
    }
  
    public function sponsorText ($aData, $sTemplateName = 'sponsor-text.html')
    {
        $CNF = &$this->_oConfig->CNF;
  
        $aVars = $aData; 
  
        $aVars['entry_title'] = isset($aData[$CNF['FIELD_SPONSOR_TITLE']]) ? $aData[$CNF['FIELD_SPONSOR_TITLE']] : '';
        $aVars['entry_text'] = $aData[$CNF['FIELD_SPONSOR_TEXT']];

        return $this->parseHtmlByName($sTemplateName, $aVars);
    }
 
    public function sponsorBreadcrumb($aSponsorContentInfo, $aTmplVarsItems = array())
    {
    	$CNF = &$this->_oConfig->CNF;
 
        $aContentInfo = $this->_oDb->getContentInfoById($aSponsorContentInfo[$CNF['FIELD_GOAL_ID']]);

        $oPermalink = BxDolPermalinks::getInstance();

        $aTmplVarsItems = array();
  
		if($aContentInfo[$CNF['FIELD_SUBCATEGORY']])
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_SUBCATEGORY']], $oPermalink, $aTmplVarsItems);
		else
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_CATEGORY']], $oPermalink, $aTmplVarsItems);
 
        $aTmplVarsItems = array_reverse($aTmplVarsItems);
        
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aContentInfo[$CNF['FIELD_TITLE']])
        );
 
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_SPONSOR'] . '&id=' . $aSponsorContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aSponsorContentInfo[$CNF['FIELD_TITLE']])
        );

    	return parent::entryBreadcrumb($aContentInfo, $aTmplVarsItems);
    }

//end sponsor


 //begin news

	public function news_main_unit($aData)
    { 
        $oPermalink = BxDolPermalinks::getInstance();
 
    	$oModule = $this->getModule();

        $CNF = &$oModule->_oConfig->CNF;

        $iProfile = bx_get_logged_profile_id();
 
        $bDeleteNews = $aData[$CNF['FIELD_AUTHOR']] == $iProfile;
  
		$iId = (int)$aData[$CNF['FIELD_ID']];
  
		$oAuthor = BxDolProfile::getInstanceMagic($aData[$CNF['FIELD_AUTHOR']]);
         
		$sUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_NEWS_ITEM'] . '&id=' . $iId));

        $sTitle = $this->getTitle($aData);
        $sText = $this->getText($aData); 
        $sSummary = $this->getText($aData);
        $sSummaryPlain = isset($CNF['PARAM_CHARS_SUMMARY_PLAIN']) && $CNF['PARAM_CHARS_SUMMARY_PLAIN'] ? BxTemplFunctions::getInstance()->getStringWithLimitedLength(strip_tags($sSummary), (int)getParam($CNF['PARAM_CHARS_SUMMARY_PLAIN'])) : '';
  
        $this->addCss('main.css');
 
		return $this->parseHtmlByName('unit_sponsor.html', array( 

            'class' => $this->_getUnitClass($aData,(isset($aParams['template_name']) ? $aParams['template_name'] : '')),
			'id' => $iId,
			'title' => $sTitle,
            'title_attr' => bx_html_attribute($sTitle), 
            'summary' => $sSummary,
            'text' => $sText, 
			'content_url' => $sUrl, 
		));
	}
  
    public function newsText ($aData, $sTemplateName = 'news-text.html')
    {
        $CNF = &$this->_oConfig->CNF;
  
        $aVars = $aData; 
  
        $aVars['entry_title'] = isset($aData[$CNF['FIELD_NEWS_TITLE']]) ? $aData[$CNF['FIELD_NEWS_TITLE']] : '';
        $aVars['entry_text'] = $aData[$CNF['FIELD_NEWS_TEXT']];

        return $this->parseHtmlByName($sTemplateName, $aVars);
    }
 
    public function newsBreadcrumb($aNewsContentInfo, $aTmplVarsItems = array())
    {
    	$CNF = &$this->_oConfig->CNF;
 
        $aContentInfo = $this->_oDb->getContentInfoById($aNewsContentInfo[$CNF['FIELD_GOAL_ID']]);

        $oPermalink = BxDolPermalinks::getInstance();

        $aTmplVarsItems = array();
  
		if($aContentInfo[$CNF['FIELD_SUBCATEGORY']])
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_SUBCATEGORY']], $oPermalink, $aTmplVarsItems);
		else
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_CATEGORY']], $oPermalink, $aTmplVarsItems);
 
        $aTmplVarsItems = array_reverse($aTmplVarsItems);
        
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aContentInfo[$CNF['FIELD_TITLE']])
        );
 
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_NEWS'] . '&id=' . $aNewsContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aNewsContentInfo[$CNF['FIELD_TITLE']])
        );

    	return parent::entryBreadcrumb($aContentInfo, $aTmplVarsItems);
    }

//end news

 //begin task

	public function task_main_unit($aData)
    {

        $oPermalink = BxDolPermalinks::getInstance();
 
    	$oModule = $this->getModule();

        $CNF = &$oModule->_oConfig->CNF;

        $iProfile = bx_get_logged_profile_id();
 
        $bDeleteTask = $aData[$CNF['FIELD_AUTHOR']] == $iProfile;
  
		$iId = (int)$aData[$CNF['FIELD_ID']];
  
		$oAuthor = BxDolProfile::getInstanceMagic($aData[$CNF['FIELD_AUTHOR']]);
 
        $sUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_TASK'] . '&id=' . $aData[$CNF['FIELD_ID']]));

        $sTitle = $this->getTitle($aData);
        $sText = $this->getText($aData);
        $sSummary = $this->getSummary($aData, $sTitle, $sText, $sUrl); 
        $sSummaryPlain = isset($CNF['PARAM_CHARS_SUMMARY_PLAIN']) && $CNF['PARAM_CHARS_SUMMARY_PLAIN'] ? BxTemplFunctions::getInstance()->getStringWithLimitedLength(strip_tags($sSummary), (int)getParam($CNF['PARAM_CHARS_SUMMARY_PLAIN'])) : '';
  
        $this->addCss('main.css');
 
		switch($aData['progress'])
		{
			case 'upcoming':
				$sProgressIcon = 'arrow-circle-right';
			break;
			case 'ongoing':
				$sProgressIcon = 'running';
			break;
			case 'outstanding':
				$sProgressIcon = 'arrow-circle-left';
			break;
			case 'completed':
				$sProgressIcon = 'thumbs-up';
			break;			
			case 'failed':
				$sProgressIcon = 'thumbs-down';
			break;
		}
 
		return $this->parseHtmlByName('unit_task.html', array( 
			'progress_style' => 'mz-goal-' . $aData['progress'],  
			'progress' => _t('_mz_goal_task_progress_'.$aData['progress']), 
			'progress_icon' => $sProgressIcon,
            'class' => $this->_getUnitClass($aData,(isset($aParams['template_name']) ? $aParams['template_name'] : '')),
			'id' => $iId,
			'start_time' => date('M d, Y g:i A', $aData['start_time']),
            'end_time' => date('M d, Y g:i A', $aData['end_time']), 
			'title' => $sTitle,
            'title_attr' => bx_html_attribute($sTitle), 
            'summary' => $sSummary,
            'text' => $sText, 
			'content_url' => $sUrl,  
		)); 
    }
  
    public function taskText ($aData, $sTemplateName = 'task-text.html')
    {
        $CNF = &$this->_oConfig->CNF;
  
        $aVars = $aData; 
        $aVars['entry_title'] = isset($aData[$CNF['FIELD_TASK_TITLE']]) ? $aData[$CNF['FIELD_TASK_TITLE']] : '';
        $aVars['entry_text'] = $aData[$CNF['FIELD_TASK_TEXT']];

        return $this->parseHtmlByName($sTemplateName, $aVars);
    }
 
    public function taskBreadcrumb($aTaskContentInfo, $aTmplVarsItems = array())
    {
    	$CNF = &$this->_oConfig->CNF;
 
        $aContentInfo = $this->_oDb->getContentInfoById($aTaskContentInfo[$CNF['FIELD_GOAL_ID']]);

        $oPermalink = BxDolPermalinks::getInstance();

        $aTmplVarsItems = array();
  
		if($aContentInfo[$CNF['FIELD_SUBCATEGORY']])
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_SUBCATEGORY']], $oPermalink, $aTmplVarsItems);
		else
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_CATEGORY']], $oPermalink, $aTmplVarsItems);
 
        $aTmplVarsItems = array_reverse($aTmplVarsItems);
        
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aContentInfo[$CNF['FIELD_TITLE']])
        );
 
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_TASK'] . '&id=' . $aTaskContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aTaskContentInfo[$CNF['FIELD_TITLE']])
        );

    	return parent::entryBreadcrumb($aContentInfo, $aTmplVarsItems);
    }

//end task

    protected function getUnit ($aData, $aParams = array())
    {   
		$aTmplVars = parent::getUnit($aData, $aParams);
		$aTmplVars['tasks_completed'] = $aData['tasks_completed'];
		$aTmplVars['total_tasks'] = $aData['tasks'];
		$aTmplVars['last_progress'] = ($aData['last_update']) ? date('M d, Y', $aData['last_update']) : _t('_mz_goal_none');    
 		$aTmplVars['percent'] = $aTmplVars['percent_val'] = (int)($aData['tasks']) ? round(($aData['tasks_completed']/$aData['tasks']) * 100) : 0;
 
		return $aTmplVars;
	}
 
    public function entryBreadcrumb($aContentInfo, $aTmplVarsItems = array())
    {
    	$CNF = &$this->_oConfig->CNF;

        $oPermalink = BxDolPermalinks::getInstance();

        $aTmplVarsItems = array();
  
		if($aContentInfo[$CNF['FIELD_SUBCATEGORY']])
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_SUBCATEGORY']], $oPermalink, $aTmplVarsItems);
		else
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
            'cat_url' => bx_absolute_url(BxDolPermalinks::getInstance()->permalink($CNF['URL_CATEGORIES'], array('category' => ''))),
            'subcat_url' => bx_absolute_url(BxDolPermalinks::getInstance()->permalink($CNF['URL_CATEGORIES'], array('subcategory' => '')))
        ));

        if(empty($sResult) && isset($aParams['show_empty']) && $aParams['show_empty'] === true)
            $sResult = MsgBox(_t('_Empty'));

        return $sResult;
    }

    /**
     * Use Gallery image for both because currently there is no Unit types with small thumbnails.
     */
    public function getPublicUnitThumbAndGallery ($aData)
    {
        list($sPhotoThumb, $sPhotoGallery) = parent::getUnitThumbAndGallery($aData);

        return array($sPhotoGallery, $sPhotoGallery);
    }

 

    protected function _categoriesList($iParentId, $aParams = array())
    {
        $aCategories = $this->_oDb->getCategories(array('type' => 'parent_id', 'parent_id' => $iParentId));
 
		$iCategoryId = $iSubCategoryId = 0;
		if($iCategoryId = bx_get('category')){
			//
		}elseif($iSubCategoryId = bx_get('subcategory')){ 
			$aCategory = $this->_oDb->getCategories(array('type' => 'id', 'id' => $iSubCategoryId));
			$iCategoryId = $aCategory['parent_id'];
		}

        $aTmplVars = array();
        foreach($aCategories as $aCategory) {

			$sClass = '';

            $iItems = (int)$aCategory['items'];

            $sSibcategories = $this->_categoriesList($aCategory['id'], $aParams);
            //if($iItems == 0 && empty($sSibcategories))
            /////if(empty($sSibcategories))
                /////continue;

			if($iParentId==0){ 
				$sClass = ($iCategoryId==$aCategory['id']) ? 'bx-form-collapsable' : 'bx-form-collapsable bx-form-collapsed bx-form-section-hidden';
			}

            $aTmplVars[] = array(
                'url' => (($iParentId==0) ? $aParams['cat_url'] : $aParams['subcat_url']) . $aCategory['id'],
                'title' => _t($aCategory['title']),
 
                'bx_if:show_parentcategory1' => array(
                    'condition' => $iParentId==0,
                    'content' => array ()
                 ),
                'bx_if:show_parentcategory2' => array(
                    'condition' => $iParentId==0,
                    'content' => array ()
                 ),

				'class' => $sClass,
                'bx_if:show_icon' => array(
                    'condition' => false /*!empty($aCategory['icon'])*/,
                    'content' => array(
                        'icon' => $aCategory['icon'],
                    )
                ),
                'bx_if:show_counter' => array(
                    'condition' => true, /*$iItems != 0,*/
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

        $sParam = empty($aCategory['parent_id']) ? $CNF['GET_PARAM_CATEGORY'] : $CNF['GET_PARAM_SUBCATEGORY'];

        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink($CNF['URL_CATEGORIES'], array($sParam => $aCategory['id']))),
            'title' => bx_process_output(_t($aCategory['title']))
        );

        if(empty($aCategory['parent_id']))
            return;

        $this->_entryBreadcrumb((int)$aCategory['parent_id'], $oPermalink, $aTmplVarsItems);
    }

    public function entryRating($aData)
    {
        $CNF = &$this->getModule()->_oConfig->CNF;

        $sVotes = '';
        $oVotes = BxDolVote::getObjectInstance($CNF['OBJECT_VOTES'], $aData['id']);
        if($oVotes) {
            $sVotes = $oVotes->getElementBlock(array('show_counter' => true, 'show_legend' => true));
            if(!empty($sVotes))
                $sVotes = $this->parseHtmlByName('entry-rating.html', array(
                'content' => $sVotes,
            ));
        }

        return $sVotes; 
    }
  
    function review_main_unit ($aData)
    {   
        $CNF = &$this->_oConfig->CNF;
  
		$oCmts = BxDolCmts::getObjectInstance($CNF['OBJECT_REVIEWS'], $aData['cmt_object_id']);
		if (!$oCmts || !$oCmts->isEnabled())
			return ''; 

		$aBp = $aDp = array();  
		$aDp['view_only'] = true;
		$aDp['type'] = 'threaded';
		$aBp['type'] = '';
		$aData['cmt_unique_id'] = time();
		$aData['cmt_status_admin'] = 'active';// una 13 b4

		$oPermalink = BxDolPermalinks::getInstance();
		$sUrl = bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aData['cmt_object_id']));
		$sTitle = bx_process_output($aData[$CNF['FIELD_TITLE']]);
 
        $aVars = array ( 
            'url' => $sUrl,
            'title' => $sTitle, 
            'comment' => $oCmts->getComment($aData, $aBp, $aDp),
        );

        return $this->parseHtmlByName('review_main_unit.html', $aVars); 
    } 
  
}

/** @} */
