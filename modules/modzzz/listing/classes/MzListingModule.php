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
 * Listing module
 */
class MzListingModule extends BxBaseModTextModule
{
    function __construct(&$aModule)
    {
        parent::__construct($aModule);

        $CNF = &$this->_oConfig->CNF;

        $this->_aSearchableNamesExcept = array_merge($this->_aSearchableNamesExcept, array(
            $CNF['FIELD_CATEGORY_VIEW'], 
        ));
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
   
//- BEGIN service

    public function serviceServiceBreadcrumb ($iContentId = 0)
    {
        return $this->_serviceServiceTemplateFunc ('serviceBreadcrumb', $iContentId);
    }
 
    public function serviceServiceAuthor ($iContentId = 0)
    {
        return $this->_serviceServiceTemplateFunc ('entryAuthor', $iContentId);
    }
 
    protected function _serviceServiceTemplateFunc ($sFunc, $iContentId, $sFuncGetContent = 'getServiceInfoById')
    {
        $mixedContent = $this->_getServiceContent($iContentId, $sFuncGetContent);
        if($mixedContent === false)
            return false;

        list($iContentId, $aContentInfo) = $mixedContent;
 
        return $this->_oTemplate->$sFunc($aContentInfo);
    }

    protected function _getServiceContent($iContentId = 0, $sFuncGetContent = true)
    {
        if(!$iContentId)
            $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);
 
        if(!$iContentId)
            return false;

        if($sFuncGetContent === true)
            $sFuncGetContent = 'getServiceInfoById';

        if(empty($sFuncGetContent) || !method_exists($this->_oDb, $sFuncGetContent))
            return $iContentId;

        $aContentInfo = $this->_oDb->$sFuncGetContent($iContentId);
        if(!$aContentInfo)
            return false;

        return array($iContentId, $aContentInfo);
    }

    public function serviceServiceActions ($iContentId = 0)
    { 
        $mixedContent = $this->_getServiceContent($iContentId, true);
        if($mixedContent === false)
            return false;
 
        list($iContentId, $aContentInfo) = $mixedContent;

        if($this->checkAllowedEditService($aContentInfo) != CHECK_ACTION_RESULT_ALLOWED)
			return;

        $oMenu = BxTemplMenu::getObjectInstance($this->_oConfig->CNF['OBJECT_MENU_ACTIONS_VIEW_SERVICE']);
        return $oMenu ? $oMenu->getCode() : false;
    }
  
    public function serviceEntityServices ($iEntryId, $sUnitView = 'gallery', $bEmptyMessage = true, $bAjaxPaginate = true)
	{
        $iEntryId = ($iEntryId) ? $iEntryId : -999;
 
		return $this->_serviceBrowse ('services', array('listing' => $iEntryId, 'unit_view' => $sUnitView), BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate); 
	}
   
	public function serviceServiceText($iServiceId = 0)
    {
        return $this->_serviceServiceForm ('viewDataEntry', $iServiceId);
    }

    public function serviceServiceComments($iServiceId = 0)
    {
        return $this->_entityComments($this->_oConfig->CNF['OBJECT_COMMENTS_SERVICES'], $iServiceId);
    }

	public function serviceServiceCreate()
    {
    	$CNF = &$this->_oConfig->CNF;

    	$iEntryId = (int)bx_get('id');
    	if(empty($iEntryId))
    		return MsgBox(_t('_sys_txt_error_occured'));
  
        bx_import('FormsServiceHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsServiceHelper';
        $oFormsHelper = new $sClass($this, $iEntryId);
        return $oFormsHelper->addDataForm();
    }

    public function serviceServiceEdit($iServiceId = 0)
    {
        return $this->_serviceServiceForm('editDataForm', $iServiceId);
    }

    public function serviceServiceDelete($iServiceId = 0)
    {
		return $this->_serviceServiceForm('deleteDataForm', $iServiceId);
    }
 
	public function checkAllowedAddService($isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
  
      	$iEntryId = (int)bx_get('id');

        $aContentInfo = $this->_oDb->getContentInfoById($iEntryId);
 
        if (CHECK_ACTION_RESULT_ALLOWED == ($sMsg = $this->checkAllowedEdit ($aContentInfo))) 
			return CHECK_ACTION_RESULT_ALLOWED;

        return _t('_mz_listing_err_access_denied'); 
    }
  
    public function checkAllowedEditService($aDataSubItem, $isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
  
        $aContentInfo = $this->_oDb->getContentInfoById($aDataSubItem[$CNF['FIELD_LISTING_ID']]);
  
        if (CHECK_ACTION_RESULT_ALLOWED == ($sMsg = $this->checkAllowedEdit ($aContentInfo))) 
			return CHECK_ACTION_RESULT_ALLOWED;

        return _t('_mz_listing_err_access_denied'); 
    }

    public function checkAllowedDeleteService($aDataService, $isPerformAction = false)
    {
        return $this->checkAllowedEditService($aDataService, $isPerformAction);
    }

    /**
     * Protected methods
     */
    protected function _serviceServiceForm($sFormMethod, $iServiceId = 0)
    {
        if (!$iServiceId)
            $iServiceId = bx_process_input(bx_get('id'), BX_DATA_INT);
        if (!$iServiceId)
            return false;

        bx_import('FormsServiceHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsServiceHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iServiceId);
    }
 
    public function serviceServiceInfo ($iContentId = 0, $sDisplay = false)
    {
        return $this->_serviceServiceEntityForm ('viewDataForm', $iContentId, $sDisplay);
    }
 
    protected function _serviceServiceEntityForm ($sFormMethod, $iContentId = 0, $sDisplay = false, $sCheckFunction = false, $bErrorMsg = true)
    {
        $iContentId = $this->_getServiceContent($iContentId, false);
        if($iContentId === false)
            return false;

        bx_import('FormsServiceHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsServiceHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iContentId, $sDisplay, $sCheckFunction, $bErrorMsg);
    }
 
    public function serviceServiceAttachments ($iContentId = 0)
    {
        return $this->_serviceServiceTemplateFunc ('serviceAttachments', $iContentId);
    }

 	//end service
 
	//- BEGIN reward

    public function serviceRewardBreadcrumb ($iContentId = 0)
    {
        return $this->_serviceRewardTemplateFunc ('rewardBreadcrumb', $iContentId);
    }
 
    public function serviceRewardAuthor ($iContentId = 0)
    {
        return $this->_serviceRewardTemplateFunc ('entryAuthor', $iContentId);
    }
 
    protected function _serviceRewardTemplateFunc ($sFunc, $iContentId, $sFuncGetContent = 'getRewardInfoById')
    {
        $mixedContent = $this->_getRewardContent($iContentId, $sFuncGetContent);
        if($mixedContent === false)
            return false;

        list($iContentId, $aContentInfo) = $mixedContent;
 
        return $this->_oTemplate->$sFunc($aContentInfo);
    }

    protected function _getRewardContent($iContentId = 0, $sFuncGetContent = true)
    {
        if(!$iContentId)
            $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);
 
        if(!$iContentId)
            return false;

        if($sFuncGetContent === true)
            $sFuncGetContent = 'getRewardInfoById';

        if(empty($sFuncGetContent) || !method_exists($this->_oDb, $sFuncGetContent))
            return $iContentId;

        $aContentInfo = $this->_oDb->$sFuncGetContent($iContentId);
        if(!$aContentInfo)
            return false;

        return array($iContentId, $aContentInfo);
    }

    public function serviceRewardActions ($iContentId = 0)
    { 
        $mixedContent = $this->_getRewardContent($iContentId, true);
        if($mixedContent === false)
            return false;
 
        list($iContentId, $aContentInfo) = $mixedContent;

        if($this->checkAllowedEditReward($aContentInfo) != CHECK_ACTION_RESULT_ALLOWED)
			return;

        $oMenu = BxTemplMenu::getObjectInstance($this->_oConfig->CNF['OBJECT_MENU_ACTIONS_VIEW_REWARD']);
        return $oMenu ? $oMenu->getCode() : false;
    }
  
    public function serviceEntityRewards ($iEntryId, $sUnitView = 'gallery', $bEmptyMessage = true, $bAjaxPaginate = true)
	{
        $iEntryId = ($iEntryId) ? $iEntryId : -999;
 
		return $this->_serviceBrowse ('rewards', array('listing' => $iEntryId, 'unit_view' => $sUnitView), BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate); 
	}
   
	public function serviceRewardText($iRewardId = 0)
    {
        return $this->_serviceRewardForm ('viewDataEntry', $iRewardId);
    }

    public function serviceRewardComments($iRewardId = 0)
    {
        return $this->_entityComments($this->_oConfig->CNF['OBJECT_COMMENTS_REWARDS'], $iRewardId);
    }

	public function serviceRewardCreate()
    {
    	$CNF = &$this->_oConfig->CNF;

    	$iEntryId = (int)bx_get('id');
    	if(empty($iEntryId))
    		return MsgBox(_t('_sys_txt_error_occured'));
  
        bx_import('FormsRewardHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsRewardHelper';
        $oFormsHelper = new $sClass($this, $iEntryId);
        return $oFormsHelper->addDataForm();
    }

    public function serviceRewardEdit($iRewardId = 0)
    {
        return $this->_serviceRewardForm('editDataForm', $iRewardId);
    }

    public function serviceRewardDelete($iRewardId = 0)
    {
		return $this->_serviceRewardForm('deleteDataForm', $iRewardId);
    }
   
	public function checkAllowedAddReward($isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
  
      	$iEntryId = (int)bx_get('id');

        $aContentInfo = $this->_oDb->getContentInfoById($iEntryId);
 
        if (CHECK_ACTION_RESULT_ALLOWED == ($sMsg = $this->checkAllowedEdit ($aContentInfo))) 
			return CHECK_ACTION_RESULT_ALLOWED;

        return _t('_mz_listing_err_access_denied'); 
    }
  
    public function checkAllowedEditReward($aDataSubItem, $isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
  
        $aContentInfo = $this->_oDb->getContentInfoById($aDataSubItem[$CNF['FIELD_LISTING_ID']]);
  
        if (CHECK_ACTION_RESULT_ALLOWED == ($sMsg = $this->checkAllowedEdit ($aContentInfo))) 
			return CHECK_ACTION_RESULT_ALLOWED;

        return _t('_mz_listing_err_access_denied'); 
    }
 
    public function checkAllowedDeleteReward($aDataReward, $isPerformAction = false)
    {
        return $this->checkAllowedEditReward($aDataReward, $isPerformAction);
    }

    /**
     * Protected methods
     */
    protected function _serviceRewardForm($sFormMethod, $iRewardId = 0)
    {
        if (!$iRewardId)
            $iRewardId = bx_process_input(bx_get('id'), BX_DATA_INT);
        if (!$iRewardId)
            return false;

        bx_import('FormsRewardHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsRewardHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iRewardId);
    }
 
    public function serviceRewardInfo ($iContentId = 0, $sDisplay = false)
    {
        return $this->_serviceRewardEntityForm ('viewDataForm', $iContentId, $sDisplay);
    }
 
    protected function _serviceRewardEntityForm ($sFormMethod, $iContentId = 0, $sDisplay = false, $sCheckFunction = false, $bErrorMsg = true)
    {
        $iContentId = $this->_getRewardContent($iContentId, false);
        if($iContentId === false)
            return false;

        bx_import('FormsRewardHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsRewardHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iContentId, $sDisplay, $sCheckFunction, $bErrorMsg);
    }
 
    public function serviceRewardAttachments ($iContentId = 0)
    {
        return $this->_serviceRewardTemplateFunc ('rewardAttachments', $iContentId);
    }

 	//end reward
 

	//- BEGIN gift

    public function serviceGiftBreadcrumb ($iContentId = 0)
    {
        return $this->_serviceGiftTemplateFunc ('giftBreadcrumb', $iContentId);
    }
 
    public function serviceGiftAuthor ($iContentId = 0)
    {
        return $this->_serviceGiftTemplateFunc ('entryAuthor', $iContentId);
    }
 
    protected function _serviceGiftTemplateFunc ($sFunc, $iContentId, $sFuncGetContent = 'getGiftInfoById')
    {
        $mixedContent = $this->_getGiftContent($iContentId, $sFuncGetContent);
        if($mixedContent === false)
            return false;

        list($iContentId, $aContentInfo) = $mixedContent;
 
        return $this->_oTemplate->$sFunc($aContentInfo);
    }

    protected function _getGiftContent($iContentId = 0, $sFuncGetContent = true)
    {
        if(!$iContentId)
            $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);
 
        if(!$iContentId)
            return false;

        if($sFuncGetContent === true)
            $sFuncGetContent = 'getGiftInfoById';

        if(empty($sFuncGetContent) || !method_exists($this->_oDb, $sFuncGetContent))
            return $iContentId;

        $aContentInfo = $this->_oDb->$sFuncGetContent($iContentId);
        if(!$aContentInfo)
            return false;

        return array($iContentId, $aContentInfo);
    }

    public function serviceGiftActions ($iContentId = 0)
    { 
        $mixedContent = $this->_getGiftContent($iContentId, true);
        if($mixedContent === false)
            return false;
 
        list($iContentId, $aContentInfo) = $mixedContent;

        if($this->checkAllowedEditGift($aContentInfo) != CHECK_ACTION_RESULT_ALLOWED)
			return;

        $oMenu = BxTemplMenu::getObjectInstance($this->_oConfig->CNF['OBJECT_MENU_ACTIONS_VIEW_GIFT']);
        return $oMenu ? $oMenu->getCode() : false;
    }
  
    public function serviceEntityGifts ($iEntryId, $sUnitView = 'gallery', $bEmptyMessage = true, $bAjaxPaginate = true)
	{
        $iEntryId = ($iEntryId) ? $iEntryId : -999;
 
		return $this->_serviceBrowse ('gifts', array('listing' => $iEntryId, 'unit_view' => $sUnitView), BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate); 
	}
   
	public function serviceGiftText($iGiftId = 0)
    {
        return $this->_serviceGiftForm ('viewDataEntry', $iGiftId);
    }

    public function serviceGiftComments($iGiftId = 0)
    {
        return $this->_entityComments($this->_oConfig->CNF['OBJECT_COMMENTS_GIFTS'], $iGiftId);
    }

	public function serviceGiftCreate()
    {
    	$CNF = &$this->_oConfig->CNF;

    	$iEntryId = (int)bx_get('id');
    	if(empty($iEntryId))
    		return MsgBox(_t('_sys_txt_error_occured'));
  
        bx_import('FormsGiftHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsGiftHelper';
        $oFormsHelper = new $sClass($this, $iEntryId);
        return $oFormsHelper->addDataForm();
    }

    public function serviceGiftEdit($iGiftId = 0)
    {
        return $this->_serviceGiftForm('editDataForm', $iGiftId);
    }

    public function serviceGiftDelete($iGiftId = 0)
    {
		return $this->_serviceGiftForm('deleteDataForm', $iGiftId);
    }
  
	public function checkAllowedAddGift($isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
  
      	$iEntryId = (int)bx_get('id');

        $aContentInfo = $this->_oDb->getContentInfoById($iEntryId);
 
        if (CHECK_ACTION_RESULT_ALLOWED == ($sMsg = $this->checkAllowedEdit ($aContentInfo))) 
			return CHECK_ACTION_RESULT_ALLOWED;

        return _t('_mz_listing_err_access_denied'); 
    }
  
    public function checkAllowedEditGift($aDataSubItem, $isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
  
        $aContentInfo = $this->_oDb->getContentInfoById($aDataSubItem[$CNF['FIELD_LISTING_ID']]);
  
        if (CHECK_ACTION_RESULT_ALLOWED == ($sMsg = $this->checkAllowedEdit ($aContentInfo))) 
			return CHECK_ACTION_RESULT_ALLOWED;

        return _t('_mz_listing_err_access_denied'); 
    }

    public function checkAllowedDeleteGift($aDataGift, $isPerformAction = false)
    {
        return $this->checkAllowedEditGift($aDataGift, $isPerformAction);
    }

    /**
     * Protected methods
     */
    protected function _serviceGiftForm($sFormMethod, $iGiftId = 0)
    {
        if (!$iGiftId)
            $iGiftId = bx_process_input(bx_get('id'), BX_DATA_INT);
        if (!$iGiftId)
            return false;

        bx_import('FormsGiftHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsGiftHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iGiftId);
    }
 
    public function serviceGiftInfo ($iContentId = 0, $sDisplay = false)
    {
        return $this->_serviceGiftEntityForm ('viewDataForm', $iContentId, $sDisplay);
    }
 
    protected function _serviceGiftEntityForm ($sFormMethod, $iContentId = 0, $sDisplay = false, $sCheckFunction = false, $bErrorMsg = true)
    {
        $iContentId = $this->_getGiftContent($iContentId, false);
        if($iContentId === false)
            return false;

        bx_import('FormsGiftHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsGiftHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iContentId, $sDisplay, $sCheckFunction, $bErrorMsg);
    }
 
    public function serviceGiftAttachments ($iContentId = 0)
    {
        return $this->_serviceGiftTemplateFunc ('giftAttachments', $iContentId);
    }

 	//end gift
 

//- BEGIN branch

    public function serviceBranchBreadcrumb ($iContentId = 0)
    {
        return $this->_serviceBranchTemplateFunc ('branchBreadcrumb', $iContentId);
    }
 
    public function serviceBranchAuthor ($iContentId = 0)
    {
        return $this->_serviceBranchTemplateFunc ('entryAuthor', $iContentId);
    }
 
    protected function _serviceBranchTemplateFunc ($sFunc, $iContentId, $sFuncGetContent = 'getBranchInfoById')
    {
        $mixedContent = $this->_getBranchContent($iContentId, $sFuncGetContent);
        if($mixedContent === false)
            return false;

        list($iContentId, $aContentInfo) = $mixedContent;
 
        return $this->_oTemplate->$sFunc($aContentInfo);
    }

    protected function _getBranchContent($iContentId = 0, $sFuncGetContent = true)
    {
        if(!$iContentId)
            $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);
 
        if(!$iContentId)
            return false;

        if($sFuncGetContent === true)
            $sFuncGetContent = 'getBranchInfoById';

        if(empty($sFuncGetContent) || !method_exists($this->_oDb, $sFuncGetContent))
            return $iContentId;

        $aContentInfo = $this->_oDb->$sFuncGetContent($iContentId);
        if(!$aContentInfo)
            return false;

        return array($iContentId, $aContentInfo);
    }

    public function serviceEntityInfo ($iContentId = 0, $sDisplay = false)
    { 
        return $this->_serviceEntityForm ('viewDataForm', $iContentId, $sDisplay);
    }
 
    public function serviceBranchActions ($iContentId = 0)
    { 
        $mixedContent = $this->_getBranchContent($iContentId, true);
        if($mixedContent === false)
            return false;
 
        list($iContentId, $aContentInfo) = $mixedContent;

        if($this->checkAllowedEditBranch($aContentInfo) != CHECK_ACTION_RESULT_ALLOWED)
			return;

        $oMenu = BxTemplMenu::getObjectInstance($this->_oConfig->CNF['OBJECT_MENU_ACTIONS_VIEW_BRANCH']);
        return $oMenu ? $oMenu->getCode() : false;
    }
  
    public function serviceEntityBranches ($iEntryId, $sUnitView = 'gallery', $bEmptyMessage = true, $bAjaxPaginate = true)
	{
        $iEntryId = ($iEntryId) ? $iEntryId : -999;
 
		return $this->_serviceBrowse ('branches', array('listing' => $iEntryId, 'unit_view' => $sUnitView), BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate); 
	}
   
	public function serviceBranchText($iBranchId = 0)
    {   
        return $this->_serviceBranchForm ('viewDataEntry', $iBranchId);
    }

    public function serviceBranchComments($iBranchId = 0)
    {
        return $this->_entityComments($this->_oConfig->CNF['OBJECT_COMMENTS_BRANCHES'], $iBranchId);
    }

	public function serviceBranchCreate()
    {
    	$CNF = &$this->_oConfig->CNF;

    	$iEntryId = (int)bx_get('id');
    	if(empty($iEntryId))
    		return MsgBox(_t('_sys_txt_error_occured'));
  
        bx_import('FormsBranchHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsBranchHelper';
        $oFormsHelper = new $sClass($this, $iEntryId);
        return $oFormsHelper->addDataForm();
    }

    public function serviceBranchEdit($iBranchId = 0)
    {
        return $this->_serviceBranchForm('editDataForm', $iBranchId);
    }

    public function serviceBranchDelete($iBranchId = 0)
    {
		return $this->_serviceBranchForm('deleteDataForm', $iBranchId);
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
 
	public function checkAllowedAddBranch($isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
  
      	$iEntryId = (int)bx_get('id');

        $aContentInfo = $this->_oDb->getContentInfoById($iEntryId);
 
        if (CHECK_ACTION_RESULT_ALLOWED == ($sMsg = $this->checkAllowedEdit ($aContentInfo))) 
			return CHECK_ACTION_RESULT_ALLOWED;

        return _t('_mz_listing_err_access_denied'); 
    }
  
    public function checkAllowedEditBranch($aDataSubItem, $isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
  
        $aContentInfo = $this->_oDb->getContentInfoById($aDataSubItem[$CNF['FIELD_LISTING_ID']]);
  
        if (CHECK_ACTION_RESULT_ALLOWED == ($sMsg = $this->checkAllowedEdit ($aContentInfo))) 
			return CHECK_ACTION_RESULT_ALLOWED;

        return _t('_mz_listing_err_access_denied'); 
    }

    public function checkAllowedDeleteBranch($aDataBranch, $isPerformAction = false)
    {
        return $this->checkAllowedEditBranch($aDataBranch, $isPerformAction);
    }

    /**
     * Protected methods
     */
    protected function _serviceBranchForm($sFormMethod, $iBranchId = 0)
    {
        if (!$iBranchId)
            $iBranchId = bx_process_input(bx_get('id'), BX_DATA_INT);
        if (!$iBranchId)
            return false;

        bx_import('FormsBranchHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsBranchHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iBranchId);
    }
 
    public function serviceBranchInfo ($iContentId = 0, $sDisplay = false)
    { 
        return $this->_serviceBranchEntityForm ('viewDataForm', $iContentId, $sDisplay);
    }
 
    protected function _serviceBranchEntityForm ($sFormMethod, $iContentId = 0, $sDisplay = false, $sCheckFunction = false, $bErrorMsg = true)
    {
        $iContentId = $this->_getBranchContent($iContentId, false);
 
        if($iContentId === false)
            return false;
 
        bx_import('FormsBranchHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsBranchHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iContentId, $sDisplay, $sCheckFunction, $bErrorMsg);
    }

    public function serviceBranchAttachments ($iContentId = 0)
    {
        return $this->_serviceBranchTemplateFunc ('branchAttachments', $iContentId);
    }

    public function processBranchMetasAdd($iContentId)
    {
        $CNF = &$this->_oConfig->CNF;

        if(empty($CNF['OBJECT_METATAGS_BRANCH'])) 
            return false;

        $aContentInfo = $this->_oDb->getBranchInfoById($iContentId);

        $bFldStatus = isset($CNF['FIELD_STATUS']);
        $bFldStatusAdmin = isset($CNF['FIELD_STATUS_ADMIN']);
        $bContentInfo = $aContentInfo && (!$bFldStatus || ($bFldStatus && $aContentInfo[$CNF['FIELD_STATUS']] == 'active')) && (!$bFldStatusAdmin || ($bFldStatusAdmin && $aContentInfo[$CNF['FIELD_STATUS_ADMIN']] == 'active'));
        if(!$bContentInfo)
            return false;

        $oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS_BRANCH']);
        $oMetatags->metaAddAuto($iContentId, $aContentInfo, $CNF, $CNF['OBJECT_FORM_BRANCH_DISPLAY_ADD']);

        $sKey = 'FIELD_LOCATION';
        if($oMetatags->locationsIsEnabled() && !empty($CNF[$sKey]) && !empty($aContentInfo[$CNF[$sKey]])) {
            $aLocation = unserialize($aContentInfo[$CNF[$sKey]]);
            if(!empty($aLocation) && is_array($aLocation))
                call_user_func_array(array($oMetatags, 'locationsAdd'), array_merge(array($iContentId), array_values($aLocation)));
        }
/*
        $sKey = 'FIELD_LABELS';
        if($oMetatags->keywordsIsEnabled() && !empty($CNF[$sKey]) && !empty($aContentInfo[$CNF[$sKey]])) {
            $aLabels = unserialize($aContentInfo[$CNF[$sKey]]);
            if(!empty($aLabels) && is_array($aLabels))
                foreach ($aLabels as $sLabel)
                    $oMetatags->keywordsAddOne($iContentId, $sLabel, false);
        }
*/        
        return true;
    }

    public function processBranchMetasEdit($iContentId, $oForm)
    {
        $CNF = &$this->_oConfig->CNF;

        if(empty($CNF['OBJECT_METATAGS_BRANCH']))
            return false;

        $aContentInfo = $this->_oDb->getBranchInfoById($iContentId);
  
        $bFldStatus = isset($CNF['FIELD_STATUS']);
        $bFldStatusAdmin = isset($CNF['FIELD_STATUS_ADMIN']);
        $bContentInfo = $aContentInfo && (!$bFldStatus || ($bFldStatus && $aContentInfo[$CNF['FIELD_STATUS']] == 'active')) && (!$bFldStatusAdmin || ($bFldStatusAdmin && $aContentInfo[$CNF['FIELD_STATUS_ADMIN']] == 'active'));
        if(!$bContentInfo)
            return false;
 
        $oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS_BRANCH']);
        $oMetatags->metaAddAuto($iContentId, $aContentInfo, $CNF, $CNF['OBJECT_FORM_BRANCH_DISPLAY_EDIT']);


        $sKey = 'FIELD_LOCATION';
        if($oMetatags->locationsIsEnabled() && !empty($CNF[$sKey]) && !empty($aContentInfo[$CNF[$sKey]])) {
 
            $aLocation = unserialize($aContentInfo[$CNF[$sKey]]);
            if(!empty($aLocation) && is_array($aLocation)){ 
                call_user_func_array(array($oMetatags, 'locationsAdd'), array_merge(array($iContentId), array_values($aLocation)));
			}
        }
/*
        $sKey = 'FIELD_LABELS';
        if($oMetatags->keywordsIsEnabled() && !empty($CNF[$sKey]) && !empty($aContentInfo[$CNF[$sKey]])) {
            $aLabels = unserialize($aContentInfo[$CNF[$sKey]]);
            if(!empty($aLabels) && is_array($aLabels))
                foreach ($aLabels as $sLabel)
                    $oMetatags->keywordsAddOne($iContentId, $sLabel, false);
        }
*/
        return true;
    }
 
 	//end branch
 

//- BEGIN announcement

    public function serviceAnnouncementBreadcrumb ($iContentId = 0)
    {
        return $this->_serviceAnnouncementTemplateFunc ('announcementBreadcrumb', $iContentId);
    }
 
    public function serviceAnnouncementAuthor ($iContentId = 0)
    {
        return $this->_serviceAnnouncementTemplateFunc ('entryAuthor', $iContentId);
    }
  
    protected function _serviceAnnouncementTemplateFunc ($sFunc, $iContentId, $sFuncGetContent = 'getAnnouncementInfoById')
    {
        $mixedContent = $this->_getAnnouncementContent($iContentId, $sFuncGetContent);
        if($mixedContent === false)
            return false;

        list($iContentId, $aContentInfo) = $mixedContent;
 
        return $this->_oTemplate->$sFunc($aContentInfo);
    }

    protected function _getAnnouncementContent($iContentId = 0, $sFuncGetContent = true)
    {
        if(!$iContentId)
            $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);
 
        if(!$iContentId)
            return false;

        if($sFuncGetContent === true)
            $sFuncGetContent = 'getAnnouncementInfoById';

        if(empty($sFuncGetContent) || !method_exists($this->_oDb, $sFuncGetContent))
            return $iContentId;

        $aContentInfo = $this->_oDb->$sFuncGetContent($iContentId);
        if(!$aContentInfo)
            return false;

        return array($iContentId, $aContentInfo);
    }

    public function serviceAnnouncementActions ($iContentId = 0)
    {
        $mixedContent = $this->_getAnnouncementContent($iContentId, true);
        if($mixedContent === false)
            return false;
 
         list($iContentId, $aContentInfo) = $mixedContent;

        if($this->checkAllowedEditAnnouncement($aContentInfo) != CHECK_ACTION_RESULT_ALLOWED)
			return;
 
        $oAnnouncement = BxTemplMenu::getObjectInstance($this->_oConfig->CNF['OBJECT_MENU_ACTIONS_VIEW_ANNOUNCEMENT']);
        return $oAnnouncement ? $oAnnouncement->getCode() : false;
    }
  
    public function serviceEntityAnnouncements ($iEntryId, $sUnitView = 'gallery', $bEmptyMessage = true, $bAjaxPaginate = true)
	{
        $iEntryId = ($iEntryId) ? $iEntryId : -999;
 
		return $this->_serviceBrowse ('announcements', array('listing' => $iEntryId, 'unit_view' => $sUnitView), BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate); 
	}
   
	public function serviceAnnouncementText($iAnnouncementId = 0)
    {
        return $this->_serviceAnnouncementForm ('viewDataEntry', $iAnnouncementId);
    }

    public function serviceAnnouncementComments($iAnnouncementId = 0)
    {
        return $this->_entityComments($this->_oConfig->CNF['OBJECT_COMMENTS_ANNOUNCEMENT'], $iAnnouncementId);
    }

	public function serviceAnnouncementCreate()
    {
    	$CNF = &$this->_oConfig->CNF;

    	$iEntryId = (int)bx_get('id');
    	if(empty($iEntryId))
    		return MsgBox(_t('_sys_txt_error_occured'));
  
        bx_import('FormsAnnouncementHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsAnnouncementHelper';
        $oFormsHelper = new $sClass($this, $iEntryId);
        return $oFormsHelper->addDataForm();
    }

    public function serviceAnnouncementEdit($iAnnouncementId = 0)
    {
        return $this->_serviceAnnouncementForm('editDataForm', $iAnnouncementId);
    }

    public function serviceAnnouncementDelete($iAnnouncementId = 0)
    {
		return $this->_serviceAnnouncementForm('deleteDataForm', $iAnnouncementId);
    }
 
	public function checkAllowedAddAnnouncement($isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
  
      	$iEntryId = (int)bx_get('id');

        $aContentInfo = $this->_oDb->getContentInfoById($iEntryId);
 
        if (CHECK_ACTION_RESULT_ALLOWED == ($sMsg = $this->checkAllowedEdit ($aContentInfo))) 
			return CHECK_ACTION_RESULT_ALLOWED;

        return _t('_mz_listing_err_access_denied'); 
    }
  
    public function checkAllowedEditAnnouncement($aDataSubItem, $isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
  
        $aContentInfo = $this->_oDb->getContentInfoById($aDataSubItem[$CNF['FIELD_LISTING_ID']]);
  
        if (CHECK_ACTION_RESULT_ALLOWED == ($sMsg = $this->checkAllowedEdit ($aContentInfo))) 
			return CHECK_ACTION_RESULT_ALLOWED;

        return _t('_mz_listing_err_access_denied'); 
    }
  
    public function checkAllowedDeleteAnnouncement($aDataAnnouncement, $isPerformAction = false)
    {
        return $this->checkAllowedEditAnnouncement($aDataAnnouncement, $isPerformAction);
    }

    /**
     * Protected methods
     */
    protected function _serviceAnnouncementForm($sFormMethod, $iAnnouncementId = 0)
    {
        if (!$iAnnouncementId)
            $iAnnouncementId = bx_process_input(bx_get('id'), BX_DATA_INT);
        if (!$iAnnouncementId)
            return false;

        bx_import('FormsAnnouncementHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsAnnouncementHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iAnnouncementId);
    }
 
    public function serviceAnnouncementInfo ($iContentId = 0, $sDisplay = false)
    {
        return $this->_serviceAnnouncementEntityForm ('viewDataForm', $iContentId, $sDisplay);
    }
 
    protected function _serviceAnnouncementEntityForm ($sFormMethod, $iContentId = 0, $sDisplay = false, $sCheckFunction = false, $bErrorMsg = true)
    {
        $iContentId = $this->_getAnnouncementContent($iContentId, false);
        if($iContentId === false)
            return false;

        bx_import('FormsAnnouncementHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsAnnouncementHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iContentId, $sDisplay, $sCheckFunction, $bErrorMsg);
    }
 
 	//end announcement

//- BEGIN schedule

    public function serviceScheduleBreadcrumb ($iContentId = 0)
    {
        return $this->_serviceScheduleTemplateFunc ('scheduleBreadcrumb', $iContentId);
    }
 
    public function serviceScheduleAuthor ($iContentId = 0)
    {
        return $this->_serviceScheduleTemplateFunc ('entryAuthor', $iContentId);
    }
  
    protected function _serviceScheduleTemplateFunc ($sFunc, $iContentId, $sFuncGetContent = 'getScheduleInfoById')
    {
        $mixedContent = $this->_getScheduleContent($iContentId, $sFuncGetContent);
        if($mixedContent === false)
            return false;

        list($iContentId, $aContentInfo) = $mixedContent;
 
        return $this->_oTemplate->$sFunc($aContentInfo);
    }

    protected function _getScheduleContent($iContentId = 0, $sFuncGetContent = true)
    {
        if(!$iContentId)
            $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);
 
        if(!$iContentId)
            return false;

        if($sFuncGetContent === true)
            $sFuncGetContent = 'getScheduleInfoById';

        if(empty($sFuncGetContent) || !method_exists($this->_oDb, $sFuncGetContent))
            return $iContentId;

        $aContentInfo = $this->_oDb->$sFuncGetContent($iContentId);
        if(!$aContentInfo)
            return false;

        return array($iContentId, $aContentInfo);
    }

    public function serviceScheduleActions ($iContentId = 0)
    {
        $mixedContent = $this->_getScheduleContent($iContentId, true);
        if($mixedContent === false)
            return false;
 
         list($iContentId, $aContentInfo) = $mixedContent;

        if($this->checkAllowedEditSchedule($aContentInfo) != CHECK_ACTION_RESULT_ALLOWED)
			return;
 
        $oSchedule = BxTemplMenu::getObjectInstance($this->_oConfig->CNF['OBJECT_MENU_ACTIONS_VIEW_SCHEDULE']);
        return $oSchedule ? $oSchedule->getCode() : false;
    }
  
    public function serviceEntitySchedules ($iEntryId, $sUnitView = 'gallery', $bEmptyMessage = true, $bAjaxPaginate = true)
	{
        $iEntryId = ($iEntryId) ? $iEntryId : -999;
 
		return $this->_serviceBrowse ('schedules', array('listing' => $iEntryId, 'unit_view' => $sUnitView), BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate); 
	}
   
	public function serviceScheduleText($iScheduleId = 0)
    {
        return $this->_serviceScheduleForm ('viewDataEntry', $iScheduleId);
    }

    public function serviceScheduleComments($iScheduleId = 0)
    {
        return $this->_entityComments($this->_oConfig->CNF['OBJECT_COMMENTS_SCHEDULE'], $iScheduleId);
    }

	public function serviceScheduleCreate()
    {
    	$CNF = &$this->_oConfig->CNF;

    	$iEntryId = (int)bx_get('id');
    	if(empty($iEntryId))
    		return MsgBox(_t('_sys_txt_error_occured'));
  
        bx_import('FormsScheduleHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsScheduleHelper';
        $oFormsHelper = new $sClass($this, $iEntryId);
        return $oFormsHelper->addDataForm();
    }

    public function serviceScheduleEdit($iScheduleId = 0)
    {
        return $this->_serviceScheduleForm('editDataForm', $iScheduleId);
    }

    public function serviceScheduleDelete($iScheduleId = 0)
    {
		return $this->_serviceScheduleForm('deleteDataForm', $iScheduleId);
    }

	public function checkAllowedAddSchedule($isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
  
      	$iEntryId = (int)bx_get('id');

        $aContentInfo = $this->_oDb->getContentInfoById($iEntryId);
 
        if (CHECK_ACTION_RESULT_ALLOWED == ($sMsg = $this->checkAllowedEdit ($aContentInfo))) 
			return CHECK_ACTION_RESULT_ALLOWED;

        return _t('_mz_listing_err_access_denied'); 
    }
  
    public function checkAllowedEditSchedule($aDataSubItem, $isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
  
        $aContentInfo = $this->_oDb->getContentInfoById($aDataSubItem[$CNF['FIELD_LISTING_ID']]);
  
        if (CHECK_ACTION_RESULT_ALLOWED == ($sMsg = $this->checkAllowedEdit ($aContentInfo))) 
			return CHECK_ACTION_RESULT_ALLOWED;

        return _t('_mz_listing_err_access_denied'); 
    }
  
    public function checkAllowedDeleteSchedule($aDataSchedule, $isPerformAction = false)
    {
        return $this->checkAllowedEditSchedule($aDataSchedule, $isPerformAction);
    }

    /**
     * Protected methods
     */
    protected function _serviceScheduleForm($sFormMethod, $iScheduleId = 0)
    {
        if (!$iScheduleId)
            $iScheduleId = bx_process_input(bx_get('id'), BX_DATA_INT);
        if (!$iScheduleId)
            return false;

        bx_import('FormsScheduleHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsScheduleHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iScheduleId);
    }
 
    public function serviceScheduleInfo ($iContentId = 0, $sDisplay = false)
    {
        return $this->_serviceScheduleEntityForm ('viewDataForm', $iContentId, $sDisplay);
    }
 
    protected function _serviceScheduleEntityForm ($sFormMethod, $iContentId = 0, $sDisplay = false, $sCheckFunction = false, $bErrorMsg = true)
    {
        $iContentId = $this->_getScheduleContent($iContentId, false);
        if($iContentId === false)
            return false;

        bx_import('FormsScheduleHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsScheduleHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iContentId, $sDisplay, $sCheckFunction, $bErrorMsg);
    }
 
 	//end schedule


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
 
		if($sObjectComments==$this->_oConfig->CNF['OBJECT_COMMENTS_SCHEDULE']){
			if (!$iContentId || !($aContentInfo = $this->_oDb->getScheduleInfoById($iContentId)))
				return _t('_sys_request_page_not_found_cpt');
		}
 
		if($sObjectComments==$this->_oConfig->CNF['OBJECT_COMMENTS_ANNOUNCEMENT']){
			if (!$iContentId || !($aContentInfo = $this->_oDb->getAnnouncementInfoById($iContentId)))
				return _t('_sys_request_page_not_found_cpt');
		}

		if($sObjectComments==$this->_oConfig->CNF['OBJECT_COMMENTS_SERVICES']){
			if (!$iContentId || !($aContentInfo = $this->_oDb->getServiceInfoById($iContentId)))
				return _t('_sys_request_page_not_found_cpt');
		}
 
		if($sObjectComments==$this->_oConfig->CNF['OBJECT_COMMENTS_GIFTS']){
			if (!$iContentId || !($aContentInfo = $this->_oDb->getGiftInfoById($iContentId)))
				return _t('_sys_request_page_not_found_cpt');
		}

		if($sObjectComments==$this->_oConfig->CNF['OBJECT_COMMENTS_REWARDS']){
			if (!$iContentId || !($aContentInfo = $this->_oDb->getRewardInfoById($iContentId)))
				return _t('_sys_request_page_not_found_cpt');
		}

		if($sObjectComments==$this->_oConfig->CNF['OBJECT_COMMENTS_BRANCHES']){
			if (!$iContentId || !($aContentInfo = $this->_oDb->getBranchInfoById($iContentId)))
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

		if(!in_array($sPage, array($CNF['URI_ADD_ENTRY'], $CNF['URI_EDIT_ENTRY'], $CNF['URI_CREATE_BRANCH'], $CNF['URI_EDIT_BRANCH'], $CNF['URI_CREATE_SERVICE'], $CNF['URI_EDIT_SERVICE'], $CNF['URI_CREATE_GIFT'], $CNF['URI_EDIT_GIFT'], $CNF['URI_CREATE_REWARD'], $CNF['URI_EDIT_REWARD'], $CNF['URI_CREATE_CLAIM'], $CNF['URI_EDIT_CLAIM']))) 
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
            //return echoJson(array('msg' => _t('_mz_listing_txt_err_your_own')));

        if($this->_oDb->isInterested($iContentId, $iViewer))
            return echoJson(array('msg' => _t('_mz_listing_txt_err_duplicate')));

        if(!$this->_oDb->insertInterested(array('entry_id' => $iContentId, 'profile_id' => $iViewer)))
            return echoJson(array('msg' => _t('_mz_listing_txt_err_cannot_perform_action')));

		$sUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i='.$CNF['URI_VIEW_ENTRY'], array(
                $CNF['FIELD_ID'] => $iContentId
            )));

        sendMailTemplate($CNF['ETEMPLATE_INTERESTED'], 0, $iContentAuthor, array(
            'viewer_name' => $oViewer->getDisplayName(),
            'viewer_url' => $oViewer->getUrl(),
            'listing_name' => $aContentInfo[$CNF['FIELD_TITLE']],
            'listing_url' => $sUrl,  
        ));

        return echoJson(array('redirect' => $sUrl, 'msg' => _t('_mz_listing_txt_msg_author_notified')));
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
            return echoJson(array('msg' => _t('_mz_listing_txt_err_cannot_perform_action')));
 
 		$sUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i='.$CNF['URI_VIEW_ENTRY'], array(
                $CNF['FIELD_ID'] => $iContentId
            )));

		return echoJson(array('redirect' => $sUrl, 'msg' => _t('_mz_listing_txt_msg_not_interested')));
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
 
		return bx_absolute_url(BxDolPermalinks::getInstance()->permalink($CNF[$CNF['CATEGORY_URL_TYPE'][$sObject]], array($CNF[$CNF['CATEGORY_PARAM_TYPE'][$sObject]]=>$sValue))); 
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
     * @section mz_listing Ads 
     * @subsection mz_listing-page_blocks Page Blocks
     * @subsubsection mz_listing-entity_reviews entity_reviews
     * 
     * @code bx_srv('mz_listing', 'entity_reviews', [...]); @endcode
     * 
     * Get reviews for particular content
     * @param $iContentId content ID
     * 
     * @see MzListingModule::serviceEntityReviews
     */
    /** 
     * @ref mz_listing-entity_reviews "entity_reviews"
     */
    public function serviceEntityReviews($iContentId = 0)
    {
        $CNF = &$this->_oConfig->CNF;
        if(empty($CNF['OBJECT_REVIEWS']))
            return false;

        return $this->_entityComments($CNF['OBJECT_REVIEWS'], $iContentId);
    }
 
    /** 
     * @ref mz_listing-entity_reviews_rating "entity_reviews_rating"
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
     * @ref mz_listing-categories_list "categories_list"
     */
    public function serviceCategoriesList($aParams = array())
    {
        if(!isset($aParams['show_empty']))
            $aParams['show_empty'] = true;

        return $this->_oTemplate->categoriesList($aParams);
    }

    /**
     * @page service Service Calls
     * @section mz_listing Ads 
     * @subsection mz_listing-browse Browse
     * @subsubsection mz_listing-browse_category browse_category
     * 
     * @code bx_srv('mz_listing', 'browse_category', [...]); @endcode
     * 
     * Get reviews rating for particular content
     * @param $iCategoryId category ID
     * @param $aParams additional params array, such as empty_message, ajax_paginate, etc
     * 
     * @see MzListingModule::serviceBrowseCategory
     */
    /**
     * @ref mz_listing-browse_category "browse_category"
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
                $aBlock['title'] = _t('_mz_listing_page_block_title_entries_by_category_mask', _t($aCategory['title']));
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

    public function serviceBrowseLocal ($sUnitView = false, $bEmptyMessage = true, $bAjaxPaginate = true)
    { 
        return $this->_serviceBrowse ('local', $sUnitView ? array('unit_view' => $sUnitView) : false, BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate);
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

    public function serviceEntityClient ($iEntryId, $sUnitView = 'gallery', $bEmptyMessage = true, $bAjaxPaginate = true)
	{
        $iEntryId = ($iEntryId) ? $iEntryId : -999;
 
		return $this->_serviceBrowse ('client', array('listing' => $iEntryId, 'unit_view' => $sUnitView), BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate); 
	}

    public function serviceEntityLocal ($iEntryId, $sUnitView = 'gallery', $bEmptyMessage = true, $bAjaxPaginate = true)
	{
        $iEntryId = ($iEntryId) ? $iEntryId : -999;
 
		return $this->_serviceBrowse ('city', array('listing' => $iEntryId, 'unit_view' => $sUnitView), BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate); 
	}


//- BEGIN claim

    function serviceManageClaims($sGridObject, $sJSGridObject)
    {    
        $this->_oTemplate->addCss(array('manage_tools.css'));
        $this->_oTemplate->addJs(array('manage_tools.js'));
  
        $this->_oTemplate->addJsTranslation(array('_sys_grid_search'));
  
        //$oGrid = BxDolGrid::getObjectInstance('mz_flower', BxDolStudioTemplate::getInstance());
        $oGrid = BxDolGrid::getObjectInstance($sGridObject);
        if (!$oGrid) return;

        return $this->_oTemplate->getJsCode('manage_tools', array('sObjNameGrid' => $this->_oConfig->getGridObject($sJSGridObject))) . $oGrid->getCode(); 
    }

	public function actionprocess ()
	{ 
			$this->processClaim(array('id'=>1, 'status'=>'accepted'));
			echo "succ";
	}

	public function processClaim ($aParams=array())
	{  
		$CNF = &$this->_oConfig->CNF; 
 
  		$aClaimInfo = $this->_oDb->getClaimInfoById($aParams['id']);
        $iContentAuthor = (int)$aClaimInfo[$CNF['FIELD_AUTHOR']];
        $iContentId = (int)$aClaimInfo[$CNF['FIELD_LISTING_ID']];
  		$aContentInfo = $this->_oDb->getContentInfoById($iContentId);

		$aParams['entry_id'] = $iContentId;
  		if(!$this->_oDb->changeClaimStatus($aParams))
			return false;
 
        $oOwner = BxDolProfile::getInstance($iContentAuthor);
        if(!$oOwner)
            return false;

		$sUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i='.$CNF['URI_VIEW_ENTRY'], array(
                $CNF['FIELD_ID'] => $iContentId
            )));

		$sTemplate = ($aParams['status'] =='accepted') ? 'ETEMPLATE_CLAIM_ACCEPTED' : 'ETEMPLATE_CLAIM_ACCEPTED';

        sendMailTemplate($sTemplate, 0, $iContentAuthor, array(
            'viewer_name' => $oOwner->getDisplayName(),
            'viewer_url' => $oOwner->getUrl(),
            'listing_name' => $aContentInfo[$CNF['FIELD_TITLE']],
            'listing_url' => $sUrl,  
        )); 

		return true;
	}

    public function serviceClaimBreadcrumb ($iContentId = 0)
    {
        return $this->_serviceClaimTemplateFunc ('claimBreadcrumb', $iContentId);
    }
 
    public function serviceClaimAuthor ($iContentId = 0)
    {
        return $this->_serviceClaimTemplateFunc ('entryAuthor', $iContentId);
    }
 
    protected function _serviceClaimTemplateFunc ($sFunc, $iContentId, $sFuncGetContent = 'getClaimInfoById')
    {
        $mixedContent = $this->_getClaimContent($iContentId, $sFuncGetContent);
        if($mixedContent === false)
            return false;

        list($iContentId, $aContentInfo) = $mixedContent;
 
        return $this->_oTemplate->$sFunc($aContentInfo);
    }

    protected function _getClaimContent($iContentId = 0, $sFuncGetContent = true)
    {
        if(!$iContentId)
            $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);
 
        if(!$iContentId)
            return false;

        if($sFuncGetContent === true)
            $sFuncGetContent = 'getClaimInfoById';

        if(empty($sFuncGetContent) || !method_exists($this->_oDb, $sFuncGetContent))
            return $iContentId;

        $aContentInfo = $this->_oDb->$sFuncGetContent($iContentId);
        if(!$aContentInfo)
            return false;

        return array($iContentId, $aContentInfo);
    }

    public function serviceClaimActions ($iContentId = 0)
    { 
        $mixedContent = $this->_getClaimContent($iContentId, true);
        if($mixedContent === false)
            return false;
 
        list($iContentId, $aContentInfo) = $mixedContent;

        if($this->checkAllowedEditClaim($aContentInfo) != CHECK_ACTION_RESULT_ALLOWED)
			return;

        $oMenu = BxTemplMenu::getObjectInstance($this->_oConfig->CNF['OBJECT_MENU_ACTIONS_VIEW_CLAIM']);
        return $oMenu ? $oMenu->getCode() : false;
    }
  
    public function serviceEntityClaims ($iEntryId, $sUnitView = 'gallery', $bEmptyMessage = true, $bAjaxPaginate = true)
	{
        $iEntryId = ($iEntryId) ? $iEntryId : -999;
 
		return $this->_serviceBrowse ('claims', array('listing' => $iEntryId, 'unit_view' => $sUnitView), BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate); 
	}
   
	public function serviceClaimText($iClaimId = 0)
    {
        return $this->_serviceClaimForm ('viewDataEntry', $iClaimId);
    }

	public function serviceBusinessText($iClaimId = 0)
    {
        return $this->_serviceClaimForm ('viewBusinessEntry', $iClaimId);
    }
 
	public function serviceClaimCreate()
    {
    	$CNF = &$this->_oConfig->CNF;

    	$iEntryId = (int)bx_get('id');
    	if(empty($iEntryId))
    		return MsgBox(_t('_sys_txt_error_occured'));
  
        bx_import('FormsClaimHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsClaimHelper';
        $oFormsHelper = new $sClass($this, $iEntryId);
        return $oFormsHelper->addDataForm();
    }

    public function serviceClaimEdit($iClaimId = 0)
    {
        return $this->_serviceClaimForm('editDataForm', $iClaimId);
    }

    public function serviceClaimDelete($iClaimId = 0)
    {
		return $this->_serviceClaimForm('deleteDataForm', $iClaimId);
    }

    public function checkAllowedAddClaim ($aDataEntry=array(), $isPerformAction=false, $iProfileId=0)
    {
        $CNF = &$this->_oConfig->CNF;
 
        if(!$iProfileId)
            $iProfileId = $this->_iProfileId;
 
        if(empty($aDataEntry) || !is_array($aDataEntry)){
      		$iEntryId = (int)bx_get('id');
			$aDataEntry = $this->_oDb->getContentInfoById($iEntryId);
        }

		if(empty($iProfileId))
            return _t('_mz_listing_err_access_denied');
 
        // moderator and owner always have access
        if(abs($aDataEntry[$CNF['FIELD_AUTHOR']]) == (int)$iProfileId)
            return _t('_mz_listing_txt_err_your_own');
 
        if($aDataEntry[$CNF['FIELD_CLAIM_STATUS']] != 'claimable')
            return _t('_mz_listing_err_access_denied');

        // check ACL
        $aCheck = checkActionModule($iProfileId, 'claim entry', $this->getName(), $isPerformAction);
        if($aCheck[CHECK_ACTION_RESULT] !== CHECK_ACTION_RESULT_ALLOWED)
            return $aCheck[CHECK_ACTION_MESSAGE];
 
        return CHECK_ACTION_RESULT_ALLOWED;
    }
  
    public function checkAllowedEditClaim($aDataClaim, $isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
    
        $aContentInfo = $this->_oDb->getContentInfoById($aDataClaim[$CNF['FIELD_LISTING_ID']]);
  
        if (CHECK_ACTION_RESULT_ALLOWED == ($sMsg = $this->checkAllowedEdit ($aContentInfo))) 
			return CHECK_ACTION_RESULT_ALLOWED;
 
        if($aDataClaim[$CNF['FIELD_AUTHOR']] != bx_get_logged_profile_id())
			return CHECK_ACTION_RESULT_ALLOWED;

		return _t('_mz_listing_err_access_denied');
    }
  
    public function checkAllowedDeleteClaim($aDataClaim, $isPerformAction = false)
    {
        return $this->checkAllowedEditClaim($aDataClaim, $isPerformAction);
    }

    /**
     * Protected methods
     */
    protected function _serviceClaimForm($sFormMethod, $iClaimId = 0)
    {
        if (!$iClaimId)
            $iClaimId = bx_process_input(bx_get('id'), BX_DATA_INT);
        if (!$iClaimId)
            return false;

        bx_import('FormsClaimHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsClaimHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iClaimId);
    }
 
    public function serviceClaimInfo ($iContentId = 0, $sDisplay = false)
    {
        return $this->_serviceClaimEntityForm ('viewDataForm', $iContentId, $sDisplay);
    }
 
    protected function _serviceClaimEntityForm ($sFormMethod, $iContentId = 0, $sDisplay = false, $sCheckFunction = false, $bErrorMsg = true)
    {
        $iContentId = $this->_getClaimContent($iContentId, false);
        if($iContentId === false)
            return false;

        bx_import('FormsClaimHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsClaimHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iContentId, $sDisplay, $sCheckFunction, $bErrorMsg);
    }

    public function serviceClaimAttachments ($iContentId = 0)
    {
        return $this->_serviceClaimTemplateFunc ('claimAttachments', $iContentId);
    }

    public function processClaimMetasAdd($iContentId)
    {
        $CNF = &$this->_oConfig->CNF;

        if(empty($CNF['OBJECT_METATAGS_CLAIM'])) 
            return false;

        $aContentInfo = $this->_oDb->getClaimInfoById($iContentId);

        $bFldStatus = isset($CNF['FIELD_STATUS']);
        $bFldStatusAdmin = isset($CNF['FIELD_STATUS_ADMIN']);
        $bContentInfo = $aContentInfo && (!$bFldStatus || ($bFldStatus && $aContentInfo[$CNF['FIELD_STATUS']] == 'active')) && (!$bFldStatusAdmin || ($bFldStatusAdmin && $aContentInfo[$CNF['FIELD_STATUS_ADMIN']] == 'active'));
        if(!$bContentInfo)
            return false;

        $oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS_CLAIM']);
        $oMetatags->metaAddAuto($iContentId, $aContentInfo, $CNF, $CNF['OBJECT_FORM_CLAIM_DISPLAY_ADD']);
  
        return true;
    }

    public function processClaimMetasEdit($iContentId, $oForm)
    {
        $CNF = &$this->_oConfig->CNF;

        if(empty($CNF['OBJECT_METATAGS_CLAIM']))
            return false;

        $aContentInfo = $this->_oDb->getClaimInfoById($iContentId);
 
        $bFldStatus = isset($CNF['FIELD_STATUS']);
        $bFldStatusAdmin = isset($CNF['FIELD_STATUS_ADMIN']);
        $bContentInfo = $aContentInfo && (!$bFldStatus || ($bFldStatus && $aContentInfo[$CNF['FIELD_STATUS']] == 'active')) && (!$bFldStatusAdmin || ($bFldStatusAdmin && $aContentInfo[$CNF['FIELD_STATUS_ADMIN']] == 'active'));
        if(!$bContentInfo)
            return false;
 
        $oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS_CLAIM']);
        $oMetatags->metaAddAuto($iContentId, $aContentInfo, $CNF, $CNF['OBJECT_FORM_CLAIM_DISPLAY_EDIT']);
 
        return true;
    }
 
    /**
     * Delete content entry
     * @param $iContentId content id 
     * @return error message or empty string on success
     */
    public function serviceDeleteClaim ($iContentId, $sFuncDelete = 'deleteData')
    {
        bx_import('FormsClaimHelper', $this->_aModule);
        $sClass = $this->_oConfig->getClassPrefix() . 'FormsClaimHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFuncDelete($iContentId);
    }
   
    public function checkAllowedManageClaim ($aDataEntry, $isPerformAction=false, $iProfileId=0)
    {
        $CNF = &$this->_oConfig->CNF;
 
        if(!$iProfileId)
            $iProfileId = $this->_iProfileId;

        if(empty($aDataEntry) || !is_array($aDataEntry))
            return _t('_sys_txt_not_found');

        // moderator and owner always have access
        if(!empty($iProfileId) && (abs($aDataEntry[$CNF['FIELD_AUTHOR']]) == (int)$iProfileId || $this->_isModeratorForProfile($isPerformAction, $iProfileId)))
            return CHECK_ACTION_RESULT_ALLOWED;

        // check ACL
        $aCheck = checkActionModule($iProfileId, 'manage claim', $this->getName(), $isPerformAction);
        if($aCheck[CHECK_ACTION_RESULT] !== CHECK_ACTION_RESULT_ALLOWED)
            return $aCheck[CHECK_ACTION_MESSAGE];
 
        return CHECK_ACTION_RESULT_ALLOWED;
    }
 
 	//end claim

    public function checkAllowedSendMessage ($aDataEntry, $isPerformAction=false, $iProfileId=0)
    {
        $CNF = &$this->_oConfig->CNF;
 
        if(!$iProfileId)
            $iProfileId = $this->_iProfileId;

        if(!$iProfileId) 
            return _t('_mz_listing_err_access_denied');

        if(empty($aDataEntry) || !is_array($aDataEntry))
            return _t('_sys_txt_not_found');

		$oCnvModule = BxDolModule::getInstance('bx_convos');  
        if (!$oCnvModule) 
            return _t('_mz_listing_err_access_denied');
 
		if(!$aDataEntry["allow_message"]) 
            return _t('_mz_listing_err_access_denied');

        // check ACL
        $aCheck = checkActionModule($iProfileId, 'send message', $this->getName(), $isPerformAction);
        if($aCheck[CHECK_ACTION_RESULT] !== CHECK_ACTION_RESULT_ALLOWED)
            return $aCheck[CHECK_ACTION_MESSAGE];
 
        return CHECK_ACTION_RESULT_ALLOWED;
    }
 
    public function serviceEntityContact ($iContentId = 0)
    { 
        $CNF = &$this->_oConfig->CNF;
    
        if (!$iContentId)
            $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);
 
        $aContentInfo = $this->_oDb->getContentInfoById($iContentId);
 
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->checkAllowedSendMessage($aContentInfo)))  
            return MsgBox(_t('_mz_listing_err_access_denied'));

		$iProfileId = $aContentInfo[$CNF['FIELD_AUTHOR']];
  
  		$oCnvModule = BxDolModule::getInstance('bx_convos');  

		$sAbsUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i=' . $oCnvModule->_oConfig->CNF['URI_ADD_ENTRY']));

		bx_set('profiles', $iProfileId);
        bx_set('absolute_action_url', $sAbsUrl);

		$sMsg = '';
		$bMsg = bx_get('msg');

		if($bMsg){
			$oProfile = BxDolProfile::getInstance($iProfileId); 
			$aVars = array('content' => _t('_mz_listing_msg_success',$oProfile->getDisplayName())); 
			$sMsg = $this->_oTemplate->parseHtmlByName('entry-content.html', $aVars);  
		}

		$aBlock = array();
        $aBlock['content'] = $sMsg . $oCnvModule->serviceEntityCreate(); 
		$aBlock['title'] = _t("_mz_listing_page_block_title_send_msg");

		return $aBlock;
    } 
 
    public function serviceEntityClaim ($iContentId = 0)
    {
        $CNF = &$this->_oConfig->CNF;

         // check ACL
        $aCheck = checkActionModule($this->_iProfileId, 'manage claim', $this->getName(), false);

        if ($aCheck[CHECK_ACTION_RESULT] !== CHECK_ACTION_RESULT_ALLOWED)
            return;
 
		if(!$iContentId)
      		$iContentId = (int)bx_get('id');

        $aDataEntry = $this->_oDb->getContentInfoById($iContentId);
 
        if($aDataEntry[$CNF['FIELD_CLAIM_STATUS']] != 'claimable')
            return;
		 
		if(!$this->_oDb->hasPendingClaim($iContentId))
            return;

		$oPermalink = BxDolPermalinks::getInstance();

		$sClaimUrl =bx_absolute_url($oPermalink->permalink('page.php?i=listing-claims'));
  
		$aVars = array('content' => _t('_mz_listing_entry_msg_claim_pending', $sClaimUrl)); 

		return $this->_oTemplate->parseHtmlByName('entry-content.html', $aVars);   
    }
 

    /** 
     * @ref "browse_last_reviewed"
     */
    public function serviceBrowseLastReviewed ($sUnitView = false, $bEmptyMessage = true, $bAjaxPaginate = true)
    {   
        return $this->_serviceBrowse ('last_reviewed', $sUnitView ? array('unit_view' => $sUnitView) : false, BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate);
    }

    /** 
     * @ref "browse_top_reviewed"
     */
    public function serviceBrowseTopReviewed ($sUnitView = false, $bEmptyMessage = true, $bAjaxPaginate = true)
    {   
        return $this->_serviceBrowse ('top_reviewed', $sUnitView ? array('unit_view' => $sUnitView) : false, BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate);
    }

    public function serviceEntityHoursBlock ($iContentId = 0)
    {  
        if (!$iContentId)
            $iContentId = (int)bx_get('id');

		$aContentInfo = $this->_oDb->getViewHours($iContentId);
	
		if(empty($aContentInfo)) return;

		ksort($aContentInfo);
 
		$aDataDay = BxDolForm::getDataItems('mz_listing_repeat_day_of_week');
		$aDataPeriod = BxDolForm::getDataItems('mz_listing_repeat_period');
		$aDataHour = BxDolForm::getDataItems('mz_listing_repeat_hour');
		$aDataMinute = BxDolForm::getDataItems('mz_listing_repeat_minute');

		$aOutput = array();
		for($iter=1;$iter<=7;$iter++)
		{
			$aVars = array('day'=>$aDataDay[$iter], 'content'=>_t('_mz_listing_txt_closed'));
			$aOutput[$iter] = $this->_oTemplate->parseHtmlByName('view-hour-closed.html', $aVars);  
		}
 
		$sCode = '';
		foreach($aContentInfo as $iDay=>$aDayInfo)
		{	  
			$sDay = $aDataDay[$iDay];
			$sFromHour = $aDataHour[$aDayInfo['repeat_from_hour']];
			$sFromMinute = $aDataMinute[$aDayInfo['repeat_from_minute']];
			$iFromPeriod = $aDayInfo['repeat_from_period'];
			$sFromPeriod = $aDataPeriod[$iFromPeriod];
			
			$sToHour = $aDataHour[$aDayInfo['repeat_to_hour']];
			$sToMinute = $aDataMinute[$aDayInfo['repeat_to_minute']];
			$iToPeriod = $aDayInfo['repeat_to_period'];
			$sToPeriod = $aDataPeriod[$iToPeriod];

			$aVars = array();
			$aVars['day'] = $sDay;
			$aVars['from_hour'] = $sFromHour;
			$aVars['from_minute'] = $sFromMinute;
			$aVars['from_period'] = $sFromPeriod;
			$aVars['to_hour'] = $sToHour;
			$aVars['to_minute'] = $sToMinute;
			$aVars['to_period'] = $sToPeriod;
  
			$aOutput[$iDay] = $this->_oTemplate->parseHtmlByName('view-hour.html', $aVars);  
		}

		return implode('',$aOutput);
    }

    public function actionHours()
    {
        $sAction = bx_get('a');
        $sMethodName = 'subaction' . ucfirst($sAction);
        if (!method_exists($this, $sMethodName)) {
            $this->_oTemplate->pageNotFound();
            return;
        }
        $this->$sMethodName();
    }

    public function subactionRestore($iContentId = 0)
    {
        if (!$iContentId)
            $iContentId = (int)bx_get('c');

        $aContentInfo = $this->_oDb->getContentInfoById($iContentId);

        if (
            ($aContentInfo && CHECK_ACTION_RESULT_ALLOWED !== $this->checkAllowedEdit ($aContentInfo))
            ||
            (!$aContentInfo && CHECK_ACTION_RESULT_ALLOWED !== $this->checkAllowedAdd ())
        ) {
            $this->_oTemplate->displayAccessDenied();
            exit;
        }

        $a = $this->_oDb->getHours($iContentId);
        foreach ($a as $iHourId => $r) {
            $a[$iHourId]['file_id'] = $r['hour_id'];
        }

        if ('json' == bx_get('f')) {
            header('Content-type: text/html; charset=utf-8');
            echo json_encode($a);
        }
    }

    public function subactionDelete()
    {
        header('Content-type: text/html; charset=utf-8');

        $iHourId = (int)bx_get('id');

        if (!($aContentInfo = $this->_oDb->getContentInfoByHourId($iHourId))) {
            echo _t('_sys_request_page_not_found_cpt');
        }
        elseif (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->checkAllowedEdit ($aContentInfo))) {
            echo $sMsg;
        } 
        elseif (!$this->_oDb->deleteHourById($iHourId)) {
            echo _t('_sys_txt_error_occured');
        } 
        else {
            echo 'ok';
        }
    }

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
            return array('msg' => _t('_mz_listing_txt_err_cannot_perform_action'));

        $this->serviceUpdateCategoriesStats($aContentInfo);

        return array(
            'reload' => 1
        );
    }
	//END - UNA 13 RC2  

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
            'lang_key' => '_mz_listing_txt_ntfs_subobject_interested', //may be empty or not specified. In this case the default one from Notification module will be used.
        );
    }
    //end UNA 13 beta 
 
}

/** @} */
