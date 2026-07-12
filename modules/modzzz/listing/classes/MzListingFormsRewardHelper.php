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

class MzListingFormsRewardHelper extends BxBaseModTextFormsEntryHelper
{
    protected $_iEntryId;

    public function __construct($oModule, $iEntryId = 0)
    {
        parent::__construct($oModule);

        $this->_iEntryId = $iEntryId;
        if(!$this->_iEntryId && bx_get('id') !== false)
            $this->_iEntryId = bx_process_input(bx_get('id'), BX_DATA_INT);
    }

    public function getObjectFormView ($sDisplay = false)
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;

        if (false === $sDisplay)
            $sDisplay = $CNF['OBJECT_FORM_REWARD_DISPLAY_VIEW'];
 
        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_REWARD'], $sDisplay, $this->_oModule->_oTemplate);
    }

    public function viewDataForm ($iContentId, $sDisplay = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
 
         $aContentInfo = $this->_oModule->_oDb->getRewardInfoById($iContentId);

/*
        // get content data and profile info
        list ($oProfile, $aContentInfo) = $this->_getProfileAndContentData($iContentId);
        if (!$aContentInfo)
			return;
 
        // check access
        if ($sMsg = $this->_processPermissionsCheckForViewDataForm ($aContentInfo, $oProfile))
            return MsgBox($sMsg);
*/

        // get form
        $oForm = $this->getObjectFormView($sDisplay);
        if (!$oForm)
            return MsgBox(_t('_sys_txt_error_occured'));
 
        // display profile
        $oForm->initChecker($aContentInfo);
        return $oForm->getCode();
    }
 
    protected function _rewardEntityForm ($sFormMethod, $iContentId = 0, $sDisplay = false, $sCheckFunction = false, $bErrorMsg = true)
    {
        $iContentId = $this->_getContent($iContentId, false);
        if($iContentId === false)
            return false;

        bx_import('FormsRewardHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsRewardHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iContentId, $sDisplay, $sCheckFunction, $bErrorMsg);
    }

    public function getObjectFormAdd ($sDisplay = false)
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;

        if (false === $sDisplay)
            $sDisplay = $CNF['OBJECT_FORM_REWARD_DISPLAY_ADD'];
        
        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_REWARD'], $sDisplay, $this->_oModule->_oTemplate);
    }

    public function getObjectFormEdit ($sDisplay = false)
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;

        if (false === $sDisplay)
            $sDisplay = $CNF['OBJECT_FORM_REWARD_DISPLAY_EDIT'];

        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_REWARD'], $sDisplay, $this->_oModule->_oTemplate);
    }

    public function getObjectFormDelete ($sDisplay = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if (false === $sDisplay)
            $sDisplay = $CNF['OBJECT_FORM_REWARD_DISPLAY_DELETE'];

        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_REWARD'], $sDisplay, $this->_oModule->_oTemplate);
    }

    public function viewDataEntry($iRewardId)
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;
 
    	$aRewardInfo = $this->_oModule->_oDb->getRewardInfoById($iRewardId);
        if (!$aRewardInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

		$aEntryInfo = $this->_oModule->_oDb->getContentInfoById($aRewardInfo[$CNF['FIELD_LISTING_ID']]);
		if (!$aEntryInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

        // check access
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->checkAllowedView($aEntryInfo)))
            return MsgBox($sMsg);

        return $this->_oModule->_oTemplate->subitemText($aRewardInfo, 'reward'); 
    }
 
	public function addDataForm($sDisplay = false, $sCheckFunction = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if (!$sCheckFunction)
            $sCheckFunction = 'checkAllowedAddReward';

        $aEntryInfo = $this->_oModule->_oDb->getContentInfoById($this->_iEntryId);
		if(!$aEntryInfo)
			return MsgBox(_t('_sys_txt_error_occured'));

        // check access
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->$sCheckFunction($aEntryInfo)))
            return MsgBox($sMsg);

        // check and display form
        $oForm = $this->getObjectFormAdd($sDisplay);
        if (!$oForm)
            return MsgBox(_t('_sys_txt_error_occured'));

		$oForm->aInputs['entry_id']['value'] = $this->_iEntryId;

        $oForm->initChecker();

 
        if (!$oForm->isSubmittedAndValid())
            return $oForm->getCode();

        // insert data into database
        $aValsToAdd = array ();
        $iRewardId = $oForm->insert ($aValsToAdd);
        if (!$iRewardId) {
            if (!$oForm->isValid())
                return $oForm->getCode();
            else
                return MsgBox(_t('_sys_txt_error_entry_creation'));
        }

        $sResult = $this->onDataAddAfter(getLoggedId(), $iRewardId);
        if ($sResult)
            return $sResult;
 
         // process uploaded files
        if (isset($CNF['FIELD_PHOTO']))
            $oForm->processFiles ($CNF['FIELD_PHOTO'], $iRewardId, true);

        // redirect
        $this->_redirectAndExit('page.php?i=' . $CNF['URI_VIEW_REWARD'] . '&id=' . $iRewardId);
    }

    public function editDataForm($iRewardId, $sDisplay = false, $sCheckFunction = false, $bErrorMsg = true)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
 
        if ($sDisplay === false)
            $sDisplay = $CNF['OBJECT_FORM_REWARD_DISPLAY_EDIT'];

        if (!$sCheckFunction)
            $sCheckFunction = 'checkAllowedEditReward';

        // get content data
        $aRewardInfo = $this->_oModule->_oDb->getRewardInfoById($iRewardId);
        if (!$aRewardInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

        $aEntryInfo = $this->_oModule->_oDb->getContentInfoById($aRewardInfo[$CNF['FIELD_LISTING_ID']]);
        if (!$aEntryInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

        // check access
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->$sCheckFunction($aRewardInfo)))
            return MsgBox($sMsg);

        $oProfile = BxDolProfile::getInstanceMagic($aEntryInfo[$CNF['FIELD_AUTHOR']]);

        // check and display form
        $oForm = $this->getObjectFormEdit($sDisplay);
        if (!$oForm)
            return MsgBox(_t('_sys_txt_error_occured'));

        $aSpecificValues = array();        
        $oForm->initChecker($aRewardInfo, $aSpecificValues);
        if (!$oForm->isSubmittedAndValid())
            return $oForm->getCode();

        // update data in the DB
        $aTrackTextFieldsChanges = null;
        $this->onDataEditBefore ($aRewardInfo[$CNF['FIELD_ID']], $aRewardInfo, $aTrackTextFieldsChanges, $oProfile, $oForm);

        if (!$oForm->update ($aRewardInfo[$CNF['FIELD_ID']], array(), $aTrackTextFieldsChanges)) {
            if (!$oForm->isValid())
                return $oForm->getCode();
            else
                return MsgBox(_t('_sys_txt_error_entry_update'));
        }

        $sResult = $this->onDataEditAfter ($aRewardInfo[$CNF['FIELD_ID']], $aRewardInfo, $aTrackTextFieldsChanges, $oProfile, $oForm);
        if ($sResult)
            return $sResult;
 
        // redirect
        $this->_redirectAndExit('page.php?i=' . $CNF['URI_VIEW_REWARD'] . '&id=' . $iRewardId);
    }

    public function deleteDataForm($iRewardId, $sDisplay = false, $sCheckFunction = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if ($sDisplay === false)
            $sDisplay = $CNF['OBJECT_FORM_REWARD_DISPLAY_DELETE'];

        if (!$sCheckFunction)
            $sCheckFunction = 'checkAllowedDeleteReward';

        // get content data
        $aRewardInfo = $this->_oModule->_oDb->getRewardInfoById($iRewardId);
        if (!$aRewardInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

		$aEntryInfo = $this->_oModule->_oDb->getContentInfoById($aRewardInfo[$CNF['FIELD_LISTING_ID']]);
		if (!$aEntryInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

		$oProfile = BxDolProfile::getInstance($aEntryInfo[$CNF['FIELD_AUTHOR']]);
        if (!$oProfile) 
            $oProfile = BxDolProfileUndefined::getInstance();

        // check access
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->$sCheckFunction($aRewardInfo)))
            return MsgBox($sMsg);

        // check and display form
        $oForm = $this->getObjectFormDelete($sDisplay);
        if (!$oForm)
            return MsgBox(_t('_sys_txt_error_occured'));

        $oForm->initChecker($aRewardInfo);
        if (!$oForm->isSubmittedAndValid())
            return $oForm->getCode();

		$sError = $this->deleteData($aRewardInfo[$CNF['FIELD_ID']], $aRewardInfo, $oProfile, $oForm);
        if(!empty($sError))
            return MsgBox($sError);

        // redirect
        $this->_redirectAndExit('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aEntryInfo[$CNF['FIELD_ID']]);
    }

    /**
     * Delete data reward
     * @param $iRewardId entry id
     * @param $aRewardInfo optional content info array
     * @param $oForm optional content info array
     * @return error string on error or empty string on success
     */
    public function deleteData($iRewardId, $aRewardInfo = false, $oProfile = null, $oForm = null)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if(!$aRewardInfo)
			$aRewardInfo = $this->_oModule->_oDb->getRewardInfoById($iRewardId);

        if(!$aRewardInfo)
            return _t('_sys_txt_error_entry_is_not_defined');

        if(!$oForm)
            $oForm = BxDolForm::getObjectInstance($CNF['OBJECT_FORM_REWARD'], $CNF['OBJECT_FORM_REWARD_DISPLAY_DELETE'], $this->_oModule->_oTemplate);

        if(!$oForm->delete($aRewardInfo[$CNF['FIELD_ID']], $aRewardInfo))
            return _t('_sys_txt_error_entry_delete');

		$sResult = $this->onDataDeleteAfter ($aRewardInfo[$CNF['FIELD_ID']], $aRewardInfo, $oProfile);
        if(!empty($sResult))
			return $sResult;

        return '';
    }

    /**
     * Delete all data 
     * @param $iEntryId entry id
     */
    public function deleteAllData($iEntryId)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
 
		$oForm = BxDolForm::getObjectInstance($CNF['OBJECT_FORM_REWARD'], $CNF['OBJECT_FORM_REWARD_DISPLAY_DELETE'], $this->_oModule->_oTemplate);

		$aEntries = $this->_oModule->_oDb->getRewardsInfoByEntryId($iEntryId);

		foreach($aEntries as $aEntry)
		{  
			$oForm->delete($aEntry[$CNF['FIELD_ID']], $aEntry);
		} 
    }
 
    public function onDataAddAfter($iAccountId, $iRewardId)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
 
        return '';
    }

    public function onDataEditBefore($iContentId, $aContentInfo, &$aTrackTextFieldsChanges, &$oProfile, &$oForm)
    {
        return '';
    }

    public function onDataEditAfter($iRewardId, $aRewardInfo, $aTrackTextFieldsChanges, $oProfile, $oForm)
    {
        return '';
    }
 
    public function onDataDeleteAfter($iRewardId, $aRewardInfo, $oProfile)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $iEntryId = (int)$aRewardInfo[$CNF['FIELD_LISTING_ID']];
        $aEntryInfo = $this->_oModule->_oDb->getContentInfoById($iEntryId);
        
        $aParams = array(
            $CNF['FIELD_REWARDS'] => $aEntryInfo[$CNF['FIELD_REWARDS']] - 1
        );
 
        $this->_oModule->_oDb->updateContentInfoById($iEntryId, $aParams);

        return '';
    }
}

/** @} */
