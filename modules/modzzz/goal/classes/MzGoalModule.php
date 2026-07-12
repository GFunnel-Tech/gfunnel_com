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

/**
 * Goal module
 */
class MzGoalModule extends BxBaseModTextModule
{
    function __construct(&$aModule)
    {
        parent::__construct($aModule);

        $CNF = &$this->_oConfig->CNF;

        $this->_aSearchableNamesExcept = array_merge($this->_aSearchableNamesExcept, array(
            $CNF['FIELD_CATEGORY_VIEW'], 
        ));
    }
   
    public function serviceEntityEntertainBlock ($iContentId = 0)
    {
        list($iContentId, $aContentInfo) = $this->_getContent($iContentId, true);
        if($iContentId === false)
            return false;
 
        return $this->_oTemplate->entryContent ($aContentInfo, 'FIELD_ENTERTAIN');
    }
  
	public function serviceEntityStatistics($iContentId = 0) 
	{ 
 		$iCompletedTasks = $this->_oDb->getTaskCount($iContentId, 'completed');
 		$iFailedTasks = $this->_oDb->getTaskCount($iContentId, 'failed');
 		$iUpcomingTasks = $this->_oDb->getTaskCount($iContentId, 'upcoming');
		$iPresentTasks = $this->_oDb->getTaskCount($iContentId, 'present');
		$iPastTasks = $this->_oDb->getTaskCount($iContentId, 'past');
 
        $aVars = array (
			'completed_tasks' => $iCompletedTasks,
			'failed_tasks' => $iFailedTasks,
			'upcoming_tasks' => $iUpcomingTasks,
			'present_tasks' => $iPresentTasks,
			'past_tasks' => $iPastTasks,
        );

        return array($this->_oTemplate->parseHtmlByName('entry_view_block_info.html', $aVars)); 
    }
 
    /** 
     * @ref bx_base_general-entity_text_block "entity_text_block"
     */
    public function serviceEntityTextBlock ($iContentId = 0)
    {
        return $this->_serviceEntityForm ('viewDataEntry', $iContentId);
    }

 	//begin broadcast
    public function checkAllowedBroadcast($aContentInfo, $isPerformAction = false)
    { 
 		$oCnvModule = BxDolModule::getInstance('bx_convos');  
        if (!$oCnvModule) 
			return false;
 
		return $this->checkAllowedEdit($aContentInfo, $isPerformAction); 
    }
 
	public function serviceBroadcastCreate()
    {
    	$CNF = &$this->_oConfig->CNF;

    	$iEntryId = (int)bx_get('id');
    	if(empty($iEntryId))
    		return MsgBox(_t('_sys_txt_error_occured'));
  
        bx_import('FormsBroadcastHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsBroadcastHelper';
        $oFormsHelper = new $sClass($this, $iEntryId);
        return $oFormsHelper->addDataForm();
    } 
 	//end broadcast
   
//- BEGIN sponsor

    public function serviceSponsorBreadcrumb ($iContentId = 0)
    {
        return $this->_serviceSponsorTemplateFunc ('sponsorBreadcrumb', $iContentId);
    }
 
    public function serviceSponsorAuthor ($iContentId = 0)
    {
        return $this->_serviceSponsorTemplateFunc ('entryAuthor', $iContentId);
    }
 
    protected function _serviceSponsorTemplateFunc ($sFunc, $iContentId, $sFuncGetContent = 'getSponsorInfoById')
    {
        $mixedContent = $this->_getSponsorContent($iContentId, $sFuncGetContent);
        if($mixedContent === false)
            return false;

        list($iContentId, $aContentInfo) = $mixedContent;
 
        return $this->_oTemplate->$sFunc($aContentInfo);
    }

    protected function _getSponsorContent($iContentId = 0, $sFuncGetContent = true)
    {
        if(!$iContentId)
            $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);
 
        if(!$iContentId)
            return false;

        if($sFuncGetContent === true)
            $sFuncGetContent = 'getSponsorInfoById';

        if(empty($sFuncGetContent) || !method_exists($this->_oDb, $sFuncGetContent))
            return $iContentId;

        $aContentInfo = $this->_oDb->$sFuncGetContent($iContentId);
        if(!$aContentInfo)
            return false;

        return array($iContentId, $aContentInfo);
    }

    public function serviceSponsorActions ($iContentId = 0)
    { 
        $mixedContent = $this->_getSponsorContent($iContentId, true);
        if($mixedContent === false)
            return false;
 
        list($iContentId, $aContentInfo) = $mixedContent;

        if($this->checkAllowedEditSponsor($aContentInfo) != CHECK_ACTION_RESULT_ALLOWED)
			return;

        $oMenu = BxTemplMenu::getObjectInstance($this->_oConfig->CNF['OBJECT_MENU_ACTIONS_VIEW_SPONSOR']);
        return $oMenu ? $oMenu->getCode() : false;
    }
  
    public function serviceEntitySponsors ($iEntryId, $sUnitView = 'gallery', $bEmptyMessage = true, $bAjaxPaginate = true)
	{
        $iEntryId = ($iEntryId) ? $iEntryId : -999;
 
		return $this->_serviceBrowse ('sponsors', array('goal' => $iEntryId, 'unit_view' => $sUnitView), BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate); 
	}
   
	public function serviceSponsorText($iSponsorId = 0)
    {
        return $this->_serviceSponsorForm ('viewDataEntry', $iSponsorId);
    }

    public function serviceSponsorComments($iSponsorId = 0)
    {
        return $this->_entityComments($this->_oConfig->CNF['OBJECT_COMMENTS_SPONSORS'], $iSponsorId);
    }

	public function serviceSponsorCreate()
    {
    	$CNF = &$this->_oConfig->CNF;

    	$iEntryId = (int)bx_get('id');
    	if(empty($iEntryId))
    		return MsgBox(_t('_sys_txt_error_occured'));
  
        bx_import('FormsSponsorHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsSponsorHelper';
        $oFormsHelper = new $sClass($this, $iEntryId);
        return $oFormsHelper->addDataForm();
    }

    public function serviceSponsorEdit($iSponsorId = 0)
    {
        return $this->_serviceSponsorForm('editDataForm', $iSponsorId);
    }

    public function serviceSponsorDelete($iSponsorId = 0)
    {
		return $this->_serviceSponsorForm('deleteDataForm', $iSponsorId);
    }
  
    public function checkAllowedAddSponsor($isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
  
      	$iEntryId = (int)bx_get('id');

        $aContentInfo = $this->_oDb->getContentInfoById($iEntryId);
 
        if (CHECK_ACTION_RESULT_ALLOWED == ($sMsg = $this->checkAllowedEdit ($aContentInfo))) 
			return CHECK_ACTION_RESULT_ALLOWED;
 
        return _t('_mz_goal_err_access_denied'); 
    }

    public function checkAllowedEditSponsor($aDataSponsor, $isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
  
        $aContentInfo = $this->_oDb->getContentInfoById($aDataSponsor[$CNF['FIELD_GOAL_ID']]);
 
        if (CHECK_ACTION_RESULT_ALLOWED == ($sMsg = $this->checkAllowedEdit ($aContentInfo))) 
			return CHECK_ACTION_RESULT_ALLOWED;

        return _t('_mz_goal_err_access_denied'); 
    }

    public function checkAllowedDeleteSponsor($aDataSponsor, $isPerformAction = false)
    {
        return $this->checkAllowedEditSponsor($aDataSponsor, $isPerformAction);
    }

    /**
     * Protected methods
     */
    protected function _serviceSponsorForm($sFormMethod, $iSponsorId = 0)
    {
        if (!$iSponsorId)
            $iSponsorId = bx_process_input(bx_get('id'), BX_DATA_INT);
        if (!$iSponsorId)
            return false;

        bx_import('FormsSponsorHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsSponsorHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iSponsorId);
    }
 
    public function serviceSponsorInfo ($iContentId = 0, $sDisplay = false)
    {
        return $this->_serviceSponsorEntityForm ('viewDataForm', $iContentId, $sDisplay);
    }
 
    protected function _serviceSponsorEntityForm ($sFormMethod, $iContentId = 0, $sDisplay = false, $sCheckFunction = false, $bErrorMsg = true)
    {
        $iContentId = $this->_getSponsorContent($iContentId, false);
        if($iContentId === false)
            return false;

        bx_import('FormsSponsorHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsSponsorHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iContentId, $sDisplay, $sCheckFunction, $bErrorMsg);
    }
 
    public function processSponsorMetasAdd($iContentId)
    {
        $CNF = &$this->_oConfig->CNF;

        if(empty($CNF['OBJECT_METATAGS_SPONSOR'])) 
            return false;

        $aContentInfo = $this->_oDb->getSponsorInfoById($iContentId);

        $bFldStatus = isset($CNF['FIELD_STATUS']);
        $bFldStatusAdmin = isset($CNF['FIELD_STATUS_ADMIN']);
        $bContentInfo = $aContentInfo && (!$bFldStatus || ($bFldStatus && $aContentInfo[$CNF['FIELD_STATUS']] == 'active')) && (!$bFldStatusAdmin || ($bFldStatusAdmin && $aContentInfo[$CNF['FIELD_STATUS_ADMIN']] == 'active'));
        if(!$bContentInfo)
            return false;

        $oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS_SPONSOR']);
        $oMetatags->metaAddAuto($iContentId, $aContentInfo, $CNF, $CNF['OBJECT_FORM_SPONSOR_DISPLAY_ADD']);

        $sKey = 'FIELD_LOCATION';
        if($oMetatags->locationsIsEnabled() && !empty($CNF[$sKey]) && !empty($aContentInfo[$CNF[$sKey]])) {
            $aLocation = unserialize($aContentInfo[$CNF[$sKey]]);
            if(!empty($aLocation) && is_array($aLocation))
                call_user_func_array(array($oMetatags, 'locationsAdd'), array_merge(array($iContentId), array_values($aLocation)));
        }
        
        return true;
    }

    public function processSponsorMetasEdit($iContentId, $oForm)
    {
        $CNF = &$this->_oConfig->CNF;

        if(empty($CNF['OBJECT_METATAGS_SPONSOR']))
            return false;

        $aContentInfo = $this->_oDb->getSponsorInfoById($iContentId);
 
        $bFldStatus = isset($CNF['FIELD_STATUS']);
        $bFldStatusAdmin = isset($CNF['FIELD_STATUS_ADMIN']);
        $bContentInfo = $aContentInfo && (!$bFldStatus || ($bFldStatus && $aContentInfo[$CNF['FIELD_STATUS']] == 'active')) && (!$bFldStatusAdmin || ($bFldStatusAdmin && $aContentInfo[$CNF['FIELD_STATUS_ADMIN']] == 'active'));
        if(!$bContentInfo)
            return false;
 
        $oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS_SPONSOR']);
        $oMetatags->metaAddAuto($iContentId, $aContentInfo, $CNF, $CNF['OBJECT_FORM_SPONSOR_DISPLAY_EDIT']);

        $sKey = 'FIELD_LOCATION';
        if($oMetatags->locationsIsEnabled() && !empty($CNF[$sKey]) && !empty($aContentInfo[$CNF[$sKey]])) {
 
            $aLocation = unserialize($aContentInfo[$CNF[$sKey]]);
            if(!empty($aLocation) && is_array($aLocation)){ 
                call_user_func_array(array($oMetatags, 'locationsAdd'), array_merge(array($iContentId), array_values($aLocation)));
			}
        }
 
        return true;
    }

 	//end sponsor
 

//- BEGIN news

    public function serviceNewsBreadcrumb ($iContentId = 0)
    {
        return $this->_serviceNewsTemplateFunc ('newsBreadcrumb', $iContentId);
    }
 
    public function serviceNewsAuthor ($iContentId = 0)
    {
        return $this->_serviceNewsTemplateFunc ('entryAuthor', $iContentId);
    }
 
    protected function _serviceNewsTemplateFunc ($sFunc, $iContentId, $sFuncGetContent = 'getNewsInfoById')
    {
        $mixedContent = $this->_getNewsContent($iContentId, $sFuncGetContent);
        if($mixedContent === false)
            return false;

        list($iContentId, $aContentInfo) = $mixedContent;
 
        return $this->_oTemplate->$sFunc($aContentInfo);
    }

    protected function _getNewsContent($iContentId = 0, $sFuncGetContent = true)
    {
        if(!$iContentId)
            $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);
 
        if(!$iContentId)
            return false;

        if($sFuncGetContent === true)
            $sFuncGetContent = 'getNewsInfoById';

        if(empty($sFuncGetContent) || !method_exists($this->_oDb, $sFuncGetContent))
            return $iContentId;

        $aContentInfo = $this->_oDb->$sFuncGetContent($iContentId);
        if(!$aContentInfo)
            return false;

        return array($iContentId, $aContentInfo);
    }

    public function serviceNewsActions ($iContentId = 0)
    { 
        $mixedContent = $this->_getNewsContent($iContentId, true);
        if($mixedContent === false)
            return false;
 
        list($iContentId, $aContentInfo) = $mixedContent;

        if($this->checkAllowedEditNews($aContentInfo) != CHECK_ACTION_RESULT_ALLOWED)
			return;

        $oMenu = BxTemplMenu::getObjectInstance($this->_oConfig->CNF['OBJECT_MENU_ACTIONS_VIEW_NEWS']);
        return $oMenu ? $oMenu->getCode() : false;
    }
  
    public function serviceEntityNews ($iEntryId, $sUnitView = 'gallery', $bEmptyMessage = true, $bAjaxPaginate = true)
	{
        $iEntryId = ($iEntryId) ? $iEntryId : -999;
 
		return $this->_serviceBrowse ('news', array('goal' => $iEntryId, 'unit_view' => $sUnitView), BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate); 
	}
   
	public function serviceNewsText($iNewsId = 0)
    {
        return $this->_serviceNewsForm ('viewDataEntry', $iNewsId);
    }

    public function serviceNewsComments($iNewsId = 0)
    {
        return $this->_entityComments($this->_oConfig->CNF['OBJECT_COMMENTS_NEWS'], $iNewsId);
    }

	public function serviceNewsCreate()
    {
    	$CNF = &$this->_oConfig->CNF;

    	$iEntryId = (int)bx_get('id');
    	if(empty($iEntryId))
    		return MsgBox(_t('_sys_txt_error_occured'));
  
        bx_import('FormsNewsHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsNewsHelper';
        $oFormsHelper = new $sClass($this, $iEntryId);
        return $oFormsHelper->addDataForm();
    }

    public function serviceNewsEdit($iNewsId = 0)
    {
        return $this->_serviceNewsForm('editDataForm', $iNewsId);
    }

    public function serviceNewsDelete($iNewsId = 0)
    {
		return $this->_serviceNewsForm('deleteDataForm', $iNewsId);
    }
 
	/**
     * @return CHECK_ACTION_RESULT_ALLOWED if access is granted or error message if access is forbidden. So make sure to make strict(===) checking.
     */
	 /*
    public function checkAllowedJoin(&$aDataEntry, $isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
 
        // check privacy
        $oPrivacy = BxDolPrivacy::getObjectInstance($CNF['OBJECT_PRIVACY_JOIN']);
        if($oPrivacy && !$oPrivacy->check($aDataEntry[$CNF['FIELD_ID']]))
            return _t('_sys_access_denied_to_private_content');

        return CHECK_ACTION_RESULT_ALLOWED;
    }
*/

    public function checkAllowedAddNews($isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
  
      	$iEntryId = (int)bx_get('id');

        $aContentInfo = $this->_oDb->getContentInfoById($iEntryId);
 
        if (CHECK_ACTION_RESULT_ALLOWED == ($sMsg = $this->checkAllowedEdit ($aContentInfo))) 
			return CHECK_ACTION_RESULT_ALLOWED;

        return _t('_mz_goal_err_access_denied'); 
    }
  
    public function checkAllowedEditNews($aDataNews, $isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
  
        $aContentInfo = $this->_oDb->getContentInfoById($aDataNews[$CNF['FIELD_GOAL_ID']]);
  
        if (CHECK_ACTION_RESULT_ALLOWED == ($sMsg = $this->checkAllowedEdit ($aContentInfo))) 
			return CHECK_ACTION_RESULT_ALLOWED;

        return _t('_mz_goal_err_access_denied'); 
    }

    public function checkAllowedDeleteNews($aDataNews, $isPerformAction = false)
    {
        return $this->checkAllowedEditNews($aDataNews, $isPerformAction);
    }

    /**
     * Protected methods
     */
    protected function _serviceNewsForm($sFormMethod, $iNewsId = 0)
    {
        if (!$iNewsId)
            $iNewsId = bx_process_input(bx_get('id'), BX_DATA_INT);
        if (!$iNewsId)
            return false;

        bx_import('FormsNewsHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsNewsHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iNewsId);
    }
 
    public function serviceNewsInfo ($iContentId = 0, $sDisplay = false)
    {
        return $this->_serviceNewsEntityForm ('viewDataForm', $iContentId, $sDisplay);
    }
 
    protected function _serviceNewsEntityForm ($sFormMethod, $iContentId = 0, $sDisplay = false, $sCheckFunction = false, $bErrorMsg = true)
    {
        $iContentId = $this->_getNewsContent($iContentId, false);
        if($iContentId === false)
            return false;

        bx_import('FormsNewsHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsNewsHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iContentId, $sDisplay, $sCheckFunction, $bErrorMsg);
    }
 
 	//end news




//- BEGIN task

    public function serviceTaskBreadcrumb ($iContentId = 0)
    {
        return $this->_serviceTaskTemplateFunc ('taskBreadcrumb', $iContentId);
    }
 
    public function serviceTaskAuthor ($iContentId = 0)
    {
        return $this->_serviceTaskTemplateFunc ('entryAuthor', $iContentId);
    }
 
    protected function _serviceTaskTemplateFunc ($sFunc, $iContentId, $sFuncGetContent = 'getTaskInfoById')
    {
        $mixedContent = $this->_getTaskContent($iContentId, $sFuncGetContent);
        if($mixedContent === false)
            return false;

        list($iContentId, $aContentInfo) = $mixedContent;
 
        return $this->_oTemplate->$sFunc($aContentInfo);
    }

    protected function _getTaskContent($iContentId = 0, $sFuncGetContent = true)
    {
        if(!$iContentId)
            $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);
 
        if(!$iContentId)
            return false;

        if($sFuncGetContent === true)
            $sFuncGetContent = 'getTaskInfoById';

        if(empty($sFuncGetContent) || !method_exists($this->_oDb, $sFuncGetContent))
            return $iContentId;

        $aContentInfo = $this->_oDb->$sFuncGetContent($iContentId);
        if(!$aContentInfo)
            return false;

        return array($iContentId, $aContentInfo);
    }

    public function serviceTaskActions ($iContentId = 0)
    {
        $mixedContent = $this->_getTaskContent($iContentId, true);
        if($mixedContent === false)
            return false;

         list($iContentId, $aContentInfo) = $mixedContent;

        if($this->checkAllowedEditTask($aContentInfo) != CHECK_ACTION_RESULT_ALLOWED)
			return;
 
        $oTask = BxTemplMenu::getObjectInstance($this->_oConfig->CNF['OBJECT_MENU_ACTIONS_VIEW_TASK']);
        return $oTask ? $oTask->getCode() : false;
    }
  
    public function serviceEntityTasks ($iEntryId, $sUnitView = 'gallery', $bEmptyMessage = true, $bAjaxPaginate = true)
	{
        $iEntryId = ($iEntryId) ? $iEntryId : -999;
 
		return $this->_serviceBrowse ('tasks', array('goal' => $iEntryId, 'unit_view' => $sUnitView), BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate); 
	}
   
	public function serviceTaskText($iTaskId = 0)
    {
        return $this->_serviceTaskForm ('viewDataEntry', $iTaskId);
    }

    public function serviceTaskComments($iTaskId = 0)
    {
        return $this->_entityComments($this->_oConfig->CNF['OBJECT_COMMENTS_TASK'], $iTaskId);
    }

	public function serviceTaskCreate()
    {
    	$CNF = &$this->_oConfig->CNF;

    	$iEntryId = (int)bx_get('id');
    	if(empty($iEntryId))
    		return MsgBox(_t('_sys_txt_error_occured'));
  
        bx_import('FormsTaskHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsTaskHelper';
        $oFormsHelper = new $sClass($this, $iEntryId);
        return $oFormsHelper->addDataForm();
    }

    public function serviceTaskEdit($iTaskId = 0)
    {
        return $this->_serviceTaskForm('editDataForm', $iTaskId);
    }

    public function serviceTaskDelete($iTaskId = 0)
    {
		return $this->_serviceTaskForm('deleteDataForm', $iTaskId);
    }
  
    public function checkAllowedAddTask($isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
   
       	$iEntryId = (int)bx_get('id');

	    $aContentInfo = $this->_oDb->getContentInfoById($iEntryId);
 
        if (CHECK_ACTION_RESULT_ALLOWED == ($sMsg = $this->checkAllowedEdit ($aContentInfo))) 
			return CHECK_ACTION_RESULT_ALLOWED;
 
        return _t('_mz_goal_err_access_denied'); 
    }
 
    public function checkAllowedEditTask($aDataTask, $isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
  
        $aContentInfo = $this->_oDb->getContentInfoById($aDataTask[$CNF['FIELD_GOAL_ID']]);
  
        if (CHECK_ACTION_RESULT_ALLOWED == ($sMsg = $this->checkAllowedEdit ($aContentInfo))) 
			return CHECK_ACTION_RESULT_ALLOWED;

        return _t('_mz_goal_err_access_denied'); 
    }
  
    public function checkAllowedDeleteTask($aDataTask, $isPerformAction = false)
    {
        return $this->checkAllowedEditTask($aDataTask, $isPerformAction);
    }

    /**
     * Protected methods
     */
    protected function _serviceTaskForm($sFormMethod, $iTaskId = 0)
    {
        if (!$iTaskId)
            $iTaskId = bx_process_input(bx_get('id'), BX_DATA_INT);
        if (!$iTaskId)
            return false;

        bx_import('FormsTaskHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsTaskHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iTaskId);
    }
 
    public function serviceTaskInfo ($iContentId = 0, $sDisplay = false)
    {
        return $this->_serviceTaskEntityForm ('viewDataForm', $iContentId, $sDisplay);
    }
 
    protected function _serviceTaskEntityForm ($sFormMethod, $iContentId = 0, $sDisplay = false, $sCheckFunction = false, $bErrorMsg = true)
    {
        $iContentId = $this->_getTaskContent($iContentId, false);
        if($iContentId === false)
            return false;

        bx_import('FormsTaskHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsTaskHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iContentId, $sDisplay, $sCheckFunction, $bErrorMsg);
    }
 
    public function serviceIsAllowedTaskProgress($iEntryId = 0)
    {
        $CNF = &$this->_oConfig->CNF;
    
        $aTaskEntry = $this->_oDb->getTaskInfoById($iEntryId);
  
        if($this->_oDb->isTaskProgressUpdated($iEntryId))
			return false;
 
        return($aTaskEntry[$CNF['FIELD_AUTHOR']]==bx_get_logged_profile_id()); 
    }
 
    public function checkAllowedAchieved($aContentInfo, $isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;

        $iContentId = (int)$aContentInfo[$CNF['FIELD_ID']];
 
		//return echoJson(array('msg' => _t('_mz_goal_err_access_denied2')));
 
        $iViewer = bx_get_logged_profile_id();        
        $oViewer = BxDolProfile::getInstance($iViewer);
        if(!$oViewer)
            return _t('_mz_goal_err_access_denied');

        if($this->_oDb->isGoalAchieved($iContentId))
            return _t('_mz_goal_err_access_denied');
             
        if($aContentInfo[$CNF['FIELD_AUTHOR']] != $iViewer)
            return _t('_mz_goal_err_access_denied');

        return CHECK_ACTION_RESULT_ALLOWED;
    }

    public function checkAllowedCompleteTask($aDataTask, $isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
 
        $iContentId = (int)$aDataTask[$CNF['FIELD_GOAL_ID']];
  
        $iViewer = bx_get_logged_profile_id();        
        $oViewer = BxDolProfile::getInstance($iViewer);
        if(!$oViewer)
            return echoJson(array('msg' => _t('_mz_goal_err_access_denied')));

        if($this->_oDb->isTaskProgressUpdated($iContentId))
            return echoJson(array('msg' => _t('_mz_goal_err_access_denied')));
  
        if($aDataTask[$CNF['FIELD_AUTHOR']] != bx_get_logged_profile_id())
            return echoJson(array('msg' => _t('_mz_goal_err_access_denied')));

        return CHECK_ACTION_RESULT_ALLOWED;
    }
 
    public function checkAllowedFailedTask($aDataTask, $isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
 
        $iContentId = (int)$aDataTask[$CNF['FIELD_GOAL_ID']]; 
 
        $iViewer = bx_get_logged_profile_id();        
        $oViewer = BxDolProfile::getInstance($iViewer);
        if(!$oViewer)
            return echoJson(array('msg' => _t('_mz_goal_err_access_denied')));
 
        if($this->_oDb->isTaskProgressUpdated($iContentId))
            return echoJson(array('msg' => _t('_mz_goal_err_access_denied')));
 
        if($aDataTask[$CNF['FIELD_AUTHOR']] != bx_get_logged_profile_id())
            return echoJson(array('msg' => _t('_mz_goal_err_access_denied')));

        return CHECK_ACTION_RESULT_ALLOWED;
    }
 
    public function actionAchieved()
    {
        $CNF = &$this->_oConfig->CNF;

        $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);

        $aContentInfo = $this->_oDb->getContentInfoById($iContentId); 
        if(empty($aContentInfo) || !is_array($aContentInfo))
            return echoJson(array());
  
		if($this->checkAllowedAchieved($aContentInfo) != CHECK_ACTION_RESULT_ALLOWED)
            return echoJson(array('msg' => _t('_mz_goal_txt_err_cannot_perform_action')));

        if(!$this->_oDb->setGoalAchieved($aContentInfo))
            return echoJson(array('msg' => _t('_mz_goal_txt_err_cannot_perform_action')));

		$sUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i='.$CNF['URI_VIEW_ENTRY'], array(
                $CNF['FIELD_ID'] => $aContentInfo[$CNF['FIELD_ID']]
            )));
 
        return echoJson(array('redirect' => $sUrl, 'msg' => _t('_mz_goal_txt_msg_achieved')));
    }

    public function actionComplete()
    {
        $CNF = &$this->_oConfig->CNF;

        $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);

        $aContentInfo = $this->_oDb->getTaskInfoById($iContentId); 
        if(empty($aContentInfo) || !is_array($aContentInfo))
            return echoJson(array());
  
		if($this->checkAllowedCompleteTask($aContentInfo) != CHECK_ACTION_RESULT_ALLOWED)
            return echoJson(array('msg' => _t('_mz_goal_txt_err_cannot_perform_action')));

        if(!$this->_oDb->setTaskProgress($aContentInfo, 'completed'))
            return echoJson(array('msg' => _t('_mz_goal_txt_err_cannot_perform_action')));

		$sUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i='.$CNF['URI_VIEW_TASK'], array(
                $CNF['FIELD_ID'] => $aContentInfo[$CNF['FIELD_ID']]
            )));
 
        return echoJson(array('redirect' => $sUrl, 'msg' => _t('_mz_goal_txt_msg_complete')));
    }
  
    public function actionFail()
    {
        $CNF = &$this->_oConfig->CNF;

        $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);
 
        $aContentInfo = $this->_oDb->getTaskInfoById($iContentId); 
        if(empty($aContentInfo) || !is_array($aContentInfo))
            return echoJson(array());
 
 		if($this->checkAllowedFailedTask($aContentInfo) != CHECK_ACTION_RESULT_ALLOWED)
            return echoJson(array('msg' => _t('_mz_goal_txt_err_cannot_perform_action')));
  
        if(!$this->_oDb->setTaskProgress($aContentInfo, 'failed'))
            return echoJson(array('msg' => _t('_mz_goal_txt_err_cannot_perform_action')));
 
		$sUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i='.$CNF['URI_VIEW_TASK'], array(
                $CNF['FIELD_ID'] => $aContentInfo[$CNF['FIELD_ID']]
            )));
 
        return echoJson(array('redirect' => $sUrl, 'msg' => _t('_mz_goal_txt_msg_fail')));
    }

 	//end task

    //begin - subcontent related methods

    public function serviceCheckAllowedCommentsView($iContentId, $sObjectComments) 
    {
        //negative id used in comments for reports
        if ($iContentId < 0)
            return CHECK_ACTION_RESULT_ALLOWED;

		if(in_array($sObjectComments, array($this->_oConfig->CNF['OBJECT_COMMENTS'], $this->_oConfig->CNF['OBJECT_NOTES'], $this->_oConfig->CNF['OBJECT_REVIEWS'])))
		{
			return $this->serviceCheckAllowedWithContent('comments_view', $iContentId);
		}else{
			return $this->serviceCheckAllowedWithSubcontent($sObjectComments, 'comments_view', $iContentId);
		}
    }
    
    public function serviceCheckAllowedCommentsPost($iContentId, $sObjectComments) 
    {
        //negative id used in comments for reports
        if ($iContentId < 0)
            return CHECK_ACTION_RESULT_ALLOWED;

		if(in_array($sObjectComments, array($this->_oConfig->CNF['OBJECT_COMMENTS'], $this->_oConfig->CNF['OBJECT_NOTES'], $this->_oConfig->CNF['OBJECT_REVIEWS'])))
		{
			return $this->serviceCheckAllowedWithContent('comments_post', $iContentId);
		}else{
			return $this->serviceCheckAllowedWithSubcontent($sObjectComments, 'comments_post', $iContentId);
		} 
    }

	/**
     * Check particular action permission with subcontent
     * @param $sAction action to check, for example: View, Edit
     * @param $iContentId subcontent ID
     * @return message on error, or CHECK_ACTION_RESULT_ALLOWED when allowed
     */ 
    public function serviceCheckAllowedWithSubcontent($sObjectComments, $sAction, $iContentId, $isPerformAction = false)
    {
        //negative id used in comments for reports
        if ($iContentId < 0)
            return CHECK_ACTION_RESULT_ALLOWED;

		if($sObjectComments==$this->_oConfig->CNF['OBJECT_COMMENTS_TASK']){
			if (!$iContentId || !($aContentInfo = $this->_oDb->getTaskInfoById($iContentId)))
				return _t('_sys_request_page_not_found_cpt');
		}

		if($sObjectComments==$this->_oConfig->CNF['OBJECT_COMMENTS_SPONSORS']){
			if (!$iContentId || !($aContentInfo = $this->_oDb->getSponsorInfoById($iContentId)))
				return _t('_sys_request_page_not_found_cpt');
		}

		if($sObjectComments==$this->_oConfig->CNF['OBJECT_COMMENTS_NEWS']){
			if (!$iContentId || !($aContentInfo = $this->_oDb->getNewsInfoById($iContentId)))
				return _t('_sys_request_page_not_found_cpt');
		}
 
        $sMethod = 'checkAllowed' . bx_gen_method_name($sAction);
        if (!method_exists($this, $sMethod))
            return _t('_sys_request_method_not_found_cpt');

        return $this->$sMethod($aContentInfo, $isPerformAction);
    }
 
    /**
     * @return CHECK_ACTION_RESULT_ALLOWED if access is granted or error message if access is forbidden. So make sure to make strict(===) checking.
     */
    public function checkAllowedSetThumb ($iContentId = 0)
    { 
        $CNF = &$this->_oConfig->CNF;
		$sPage = bx_get('i'); 
		if(!in_array($sPage, array($CNF['URI_ADD_ENTRY'], $CNF['URI_EDIT_ENTRY'])))
			return false;
 
        // check ACL
        $aCheck = checkActionModule($this->_iProfileId, 'set thumb', $this->getName(), false);
        if ($aCheck[CHECK_ACTION_RESULT] !== CHECK_ACTION_RESULT_ALLOWED)
            return $aCheck[CHECK_ACTION_MESSAGE];
        return CHECK_ACTION_RESULT_ALLOWED;
    }
  
  //end - subcontent related methods

    public function actionGetSubCategoryOptions()
    {
		$iCategory = bx_get('category');

        if(bx_get('category') === false)
            return echoJson(array());

        return echoJson(array(
            'content' => $this->serviceGetSubCategoryOptions(array(
                'category' => $iCategory,
             ))
        )); 
    }
  
    /**
     * Create subcategory options
     * @return HTML string 
     */
    public function serviceGetSubCategoryOptions ($aParams=array())
    { 
		$aCategories = $this->_oDb->getCategories(array('type' => 'parent_id', 'parent_id' => $aParams['category']));
 
        $aTmplVars = array();
		$aTmplVars[] = array('id' => '','title' => '');
        foreach($aCategories as $aCategory) {
            $aTmplVars[] = array(
                'id' => $aCategory['id'],
                'title' => _t($aCategory['title']), 
            );
        }
 
        return $this->_oTemplate->parseHtmlByName('category_options.html', array(
            'bx_repeat:options' => $aTmplVars
        ));
    }
	 
    public function actionGetCategoryForm()
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

    public function checkShowInterested($aContentInfo=array())
    {
        $CNF = &$this->_oConfig->CNF;
 
		$iViewer = bx_get_logged_profile_id();        
 
		$iContentId = $aContentInfo[$CNF['FIELD_ID']]; 
		 
        if($this->_oDb->isInterested($iContentId, $iViewer))
			return false;
 
        return CHECK_ACTION_RESULT_ALLOWED;
	}

    public function checkShowNotInterested($aContentInfo=array())
    {
        $CNF = &$this->_oConfig->CNF;

		$iViewer = bx_get_logged_profile_id();        
 
		$iContentId = $aContentInfo[$CNF['FIELD_ID']]; 

        if($this->_oDb->isInterested($iContentId, $iViewer))
			return CHECK_ACTION_RESULT_ALLOWED;

		return false;
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
        //if($iContentAuthor == $iViewer)
            //return echoJson(array('msg' => _t('_mz_goal_txt_err_your_own')));

        if($this->_oDb->isInterested($iContentId, $iViewer))
            return echoJson(array('msg' => _t('_mz_goal_txt_err_duplicate')));

        if(!$this->_oDb->insertInterested(array('entry_id' => $iContentId, 'profile_id' => $iViewer)))
            return echoJson(array('msg' => _t('_mz_goal_txt_err_cannot_perform_action')));

		$sUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i='.$CNF['URI_VIEW_ENTRY'], array(
                $CNF['FIELD_ID'] => $iContentId
            )));

        sendMailTemplate($CNF['ETEMPLATE_INTERESTED'], 0, $iContentAuthor, array(
            'viewer_name' => $oViewer->getDisplayName(),
            'viewer_url' => $oViewer->getUrl(),
            'goal_name' => $aContentInfo[$CNF['FIELD_TITLE']],
            'goal_url' => $sUrl,  
        ));

        return echoJson(array('redirect' => $sUrl, 'msg' => _t('_mz_goal_txt_msg_author_notified')));
    }

    public function actionNotInterested()
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
 
        if(!$this->_oDb->removeInterested(array('entry_id' => $iContentId, 'profile_id' => $iViewer)))
            return echoJson(array('msg' => _t('_mz_goal_txt_err_cannot_perform_action')));
 
		$sUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i='.$CNF['URI_VIEW_ENTRY'], array(
                $CNF['FIELD_ID'] => $iContentId
            )));

		return echoJson(array('redirect' => $sUrl, 'msg' => _t('_mz_goal_txt_msg_not_interested')));
    }

    public function serviceGetSafeServices()
    {
        $a = parent::serviceGetSafeServices();
        return array_merge($a, array (
            'EntityReviews' => '',
            'EntityReviewsRating' => '',
            'CategoriesList' => '',
            'BrowseCategory' => '', 
        ));
    }
 
  
    /**
     * Get all categories list
     * @param $bDisplayEmptyCats display categories with no items, true by default
     * @return categories list html
     */ 
    public function serviceCustomCategoriesList ($bMultiList, $sObject, $aParams = array())
    { 
    	$bDisplayEmptyCats = isset($aParams['show_empty']) ? (bool)$aParams['show_empty'] : false;
  
		$o = BxDolCategory::getObjectInstance($sObject);

        $a = BxDolForm::getDataItems($sObject);
        if (!$a)
            return '';
 
        $aVars = array('bx_repeat:cats' => array());
        foreach ($a as $sValue => $sName) {

            if (!is_numeric($sValue) && !$sValue)
                continue;
  
			if($bMultiList)
				$iNum = 0;//$o->getItemsNum($sValue);
			else	
				$iNum = $o->getItemsNum($sValue);
 
            if (!$bDisplayEmptyCats && !$iNum)
                continue;
            $aVars['bx_repeat:cats'][] = array(
                'url' => $this->getListItemUrl($sObject, $sValue),
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
  
    public function getListItemUrl($sObject, $sValue)
    {
        $CNF = &$this->_oConfig->CNF;
 
		return BxDolPermalinks::getInstance()->permalink($CNF[$CNF['CATEGORY_URL_TYPE'][$sObject]], array($CNF[$CNF['CATEGORY_PARAM_TYPE'][$sObject]]=>$sValue)); 
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

        if(!empty($aContentInfo['subcategory'])){
            $iSubCategory = (int)$aContentInfo['subcategory']; 
			$this->serviceUpdateCategoriesStatsByCategory($iSubCategory, 'subcategory');
		}

        return $this->serviceUpdateCategoriesStatsByCategory($iCategoryId, 'category');
    }

    public function serviceUpdateCategoriesStatsByCategory($iCategoryId = 0, $sCategoryType='category')
    {
        $aParams = ['type' => 'collect_stats', 'category_type' => $sCategoryType];
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


    public function serviceUpdateCategoriesStats($sCategoryType='category')
    {
        $aStats = $this->_oDb->getCategories(array('type' => 'collect_stats', 'category_type' => $sCategoryType));
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

    public function serviceGetSearchableFields($aInputsAdd = array())
    {
        $CNF = &$this->_oConfig->CNF;
 
        $aResult = parent::serviceGetSearchableFields(array_merge($aInputsAdd, $this->_getSearchableFields()));
 
        unset($aResult[$CNF['FIELD_CATEGORY_VIEW']]);

        return $aResult;
    }
  
    public function serviceGetSearchableFieldsExtended($aInputsAdd = array())
    {
        $CNF = &$this->_oConfig->CNF;

        $aInputsAdd = array_merge($aInputsAdd, $this->_getSearchableFields());

        if(isset($aInputsAdd[$CNF['FIELD_CATEGORY']])) {
 
            $aInputsAdd[$CNF['FIELD_CATEGORY']]['search_type'] = 'select';
            $aInputsAdd[$CNF['FIELD_CATEGORY']]['type'] = 'select';
            $aInputsAdd[$CNF['FIELD_CATEGORY']]['values_src'] = BxDolService::getSerializedService($this->_oConfig->getName(), 'get_category_options', array(0));
        }

        if(isset($aInputsAdd[$CNF['FIELD_SUBCATEGORY']])) { 
            $aInputsAdd[$CNF['FIELD_SUBCATEGORY']]['search_type'] = 'select';
            $aInputsAdd[$CNF['FIELD_SUBCATEGORY']]['type'] = 'select';
        }
 
        return parent::serviceGetSearchableFieldsExtended($aInputsAdd);
    }

    public function serviceBrowseFavoriteMain ($sUnitView = false, $bEmptyMessage = true, $bAjaxPaginate = true)
	{
		$oProfile = BxDolProfile::getInstance();
        $iProfileId = ($oProfile) ? $oProfile->id() : -999;
    
		return $this->_serviceBrowse ('favorite', array('user' => $iProfileId, 'unit_view' => $sUnitView), BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate);
	}
  
    /**
     * @page service Service Calls
     * @section mz_goal Ads 
     * @subsection mz_goal-page_blocks Page Blocks
     * @subsubsection mz_goal-entity_reviews entity_reviews
     * 
     * @code bx_srv('mz_goal', 'entity_reviews', [...]); @endcode
     * 
     * Get reviews for particular content
     * @param $iContentId content ID
     * 
     * @see MzGoalModule::serviceEntityReviews
     */
    /** 
     * @ref mz_goal-entity_reviews "entity_reviews"
     */
    public function serviceEntityReviews($iContentId = 0)
    {
        $CNF = &$this->_oConfig->CNF;
        if(empty($CNF['OBJECT_REVIEWS']))
            return false;

        return $this->_entityComments($CNF['OBJECT_REVIEWS'], $iContentId);
    }
 
    /** 
     * @ref mz_goal-entity_reviews_rating "entity_reviews_rating"
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

        return $oCmts->getRatingBlock(array('show_counter' => true, 'show_empty' => false, 'in_designbox' => false));
    } 
 
    /** 
     * @ref mz_goal-categories_list "categories_list"
     */
    public function serviceCategoriesList($aParams = array())
    {
        if(!isset($aParams['show_empty']))
            $aParams['show_empty'] = true;

        return $this->_oTemplate->categoriesList($aParams);
    }

    /**
     * @page service Service Calls
     * @section mz_goal Ads 
     * @subsection mz_goal-browse Browse
     * @subsubsection mz_goal-browse_category browse_category
     * 
     * @code bx_srv('mz_goal', 'browse_category', [...]); @endcode
     * 
     * Get reviews rating for particular content
     * @param $iCategoryId category ID
     * @param $aParams additional params array, such as empty_message, ajax_paginate, etc
     * 
     * @see MzGoalModule::serviceBrowseCategory
     */
    /**
     * @ref mz_goal-browse_category "browse_category"
     */
    public function serviceBrowseCategory($sUnitView = false, $bEmptyMessage = true, $bAjaxPaginate = true, $aParams = [])
    {
        $sParamName = $sParamGet = 'category';
		$iCategoryId = 0;

        if(!$iCategoryId && bx_get($sParamGet) !== false)
            $iCategoryId = bx_process_input(bx_get($sParamGet), BX_DATA_INT);

        if(!$iCategoryId){
			$sParamName = $sParamGet = 'subcategory';
            $iCategoryId = bx_process_input(bx_get($sParamGet), BX_DATA_INT);
		}

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
                $aBlock['title'] = _t('_mz_goal_page_block_title_entries_by_category_mask', _t($aCategory['title']));
        }

        return $aBlock;
    }
 
    protected function _getCategoryOptions($iParentId, &$aValues)
    {
        $aCategories = $this->_oDb->getCategories(array('type' => 'parent_id', 'parent_id' => $iParentId));
        foreach($aCategories as $aCategory) {
            $aValues[] = array('key' => $aCategory['id'], 'value' => _t($aCategory['title']));

            //$this->_getCategoryOptions($aCategory['id'], $aValues);
        }
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
        if(!empty($CNF['FIELD_SUBCATEGORY']) && !empty($aContentInfo[$CNF['FIELD_SUBCATEGORY']])) {
            $iCategory = (int)$aContentInfo[$CNF['FIELD_SUBCATEGORY']];
            $aCategory = $this->_oDb->getCategories(array('type' => 'id', 'id' => $iCategory));
            $sCategory = _t($aCategory['title']);
            $sCategoryLink = bx_absolute_url(BxDolPermalinks::getInstance()->permalink($CNF['URL_SUBCATEGORIES'], array($CNF['GET_PARAM_SUBCATEGORY'] => $iCategory)));
        }elseif(!empty($CNF['FIELD_CATEGORY']) && !empty($aContentInfo[$CNF['FIELD_CATEGORY']])) {
            $iCategory = (int)$aContentInfo[$CNF['FIELD_CATEGORY']];
            $aCategory = $this->_oDb->getCategories(array('type' => 'id', 'id' => $iCategory));
            $sCategory = _t($aCategory['title']);
            $sCategoryLink = bx_absolute_url(BxDolPermalinks::getInstance()->permalink($CNF['URL_CATEGORIES'], array($CNF['GET_PARAM_CATEGORY'] => $iCategory)));
        }
 
        $sInclude = $this->_oTemplate->addCss(array('timeline.css'), $bDynamic);

        $aResult = parent::_getContentForTimelinePost($aEvent, $aContentInfo, $aBrowseParams);
        $aResult['text'] = $this->_oTemplate->parseHtmlByName('timeline_post_text.html', array(
            'category_link' => $sCategoryLink,
            'category_title' => $sCategory,
            'category_title_attr' => bx_html_attribute($sCategory),  
            'text' => $aResult['text']
        ));// . ($bDynamic ? $sInclude : '');
 
        return $aResult;
    }
 
    public function serviceBrowseToday ($sUnitView = false, $bEmptyMessage = true, $bAjaxPaginate = true)
    { 
        return $this->_serviceBrowse ('today', $sUnitView ? array('unit_view' => $sUnitView) : false, BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate);
    }
 
    public function serviceBrowseCompleted ($sUnitView = 'gallery', $bEmptyMessage = true, $bAjaxPaginate = true)
	{ 
		return $this->_serviceBrowse ('completed', $sUnitView ? array('unit_view' => $sUnitView) : false, BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate);
	}

    public function serviceBrowseAchieved ($sUnitView = 'gallery', $bEmptyMessage = true, $bAjaxPaginate = true)
	{ 
		return $this->_serviceBrowse ('achieved', $sUnitView ? array('unit_view' => $sUnitView) : false, BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate);
	}
 
    protected function _getAuthorInfo($iAuthorId = 0)
    {
        $iUserId = $this->_getAuthorId();
        $iAuthorId = (int)$iAuthorId;
        $oProfile = $this->_getAuthorObject($iAuthorId);

        return array(
            $oProfile->getDisplayName(),
            $oProfile->getUrl(),
            $oProfile->getThumb(),
            $oProfile->getUnit(0, array('template' => 'unit_wo_info')),
            $oProfile->getBadges()
        );
    }

    protected function _getAuthorObject($iAuthorId = 0)
    {
        return BxDolProfile::getInstanceMagic($iAuthorId);
    }

    protected function _getAuthorId ()
    {
        return isMember() ? bx_get_logged_profile_id() : 0;
    }
 
    /** 
     * 
     */
    public function serviceEntityMainReviews ($bDisplayEmptyMsg = false, $bAjaxPaginate = false)
    { 
		$CNF = &$this->_oConfig->CNF; 

		$aParams = array(); 
  
        return $this->_serviceBrowse ('review', $aParams, BX_DB_PADDING_DEF, $bDisplayEmptyMsg, $bAjaxPaginate); 
	} 

    public function serviceEntityTrip ($iEntryId, $sUnitView = 'gallery', $bEmptyMessage = true, $bAjaxPaginate = true)
	{
        $iEntryId = ($iEntryId) ? $iEntryId : -999;
 
		return $this->_serviceBrowse ('trip', array('goal' => $iEntryId, 'unit_view' => $sUnitView), BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate); 
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

        $sEntryUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $iContentId), '{bx_url_root}');

        return array(
            'entry_sample' => $CNF['T']['txt_sample_single'],
            'entry_url' => $sEntryUrl,
            'entry_caption' => $aContentInfo[$CNF['FIELD_TITLE']],
            'entry_author' => $aContentInfo[$CNF['FIELD_AUTHOR']],
            'subentry_sample' => $CNF['T']['txt_sample_interest_single'],
            'subentry_url' => '',
            'lang_key' => '_mz_goal_txt_ntfs_subobject_interested', //may be empty or not specified. In this case the default one from Notification module will be used.
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
            return array('msg' => _t('_mz_goal_txt_err_cannot_perform_action'));

        $this->serviceUpdateCategoriesStats($aContentInfo);

        return array(
            'reload' => 1
        );
    }
	//END - UNA 13 RC2 

}

/** @} */
