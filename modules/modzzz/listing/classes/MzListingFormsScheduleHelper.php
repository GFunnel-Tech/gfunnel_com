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

class MzListingFormsScheduleHelper extends BxBaseModTextFormsEntryHelper
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
            $sDisplay = $CNF['OBJECT_FORM_SCHEDULE_DISPLAY_VIEW'];
 
        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_SCHEDULE'], $sDisplay, $this->_oModule->_oTemplate);
    }

    public function viewDataForm ($iContentId, $sDisplay = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
 
         $aContentInfo = $this->_oModule->_oDb->getScheduleInfoById($iContentId);

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
 
    protected function _serviceEntityForm ($sFormMethod, $iContentId = 0, $sDisplay = false, $sCheckFunction = false, $bErrorMsg = true)
    {
        $iContentId = $this->_getContent($iContentId, false);
        if($iContentId === false)
            return false;

        bx_import('FormsScheduleHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsScheduleHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iContentId, $sDisplay, $sCheckFunction, $bErrorMsg);
    }

    public function getObjectFormAdd ($sDisplay = false)
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;

        if (false === $sDisplay)
            $sDisplay = $CNF['OBJECT_FORM_SCHEDULE_DISPLAY_ADD'];
        
        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_SCHEDULE'], $sDisplay, $this->_oModule->_oTemplate);
    }

    public function getObjectFormEdit ($sDisplay = false)
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;

        if (false === $sDisplay)
            $sDisplay = $CNF['OBJECT_FORM_SCHEDULE_DISPLAY_EDIT'];

        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_SCHEDULE'], $sDisplay, $this->_oModule->_oTemplate);
    }

    public function getObjectFormDelete ($sDisplay = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if (false === $sDisplay)
            $sDisplay = $CNF['OBJECT_FORM_SCHEDULE_DISPLAY_DELETE'];

        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_SCHEDULE'], $sDisplay, $this->_oModule->_oTemplate);
    }

    public function viewDataEntry($iScheduleId)
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;
 
    	$aScheduleInfo = $this->_oModule->_oDb->getScheduleInfoById($iScheduleId);
        if (!$aScheduleInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

		$aEntryInfo = $this->_oModule->_oDb->getContentInfoById($aScheduleInfo[$CNF['FIELD_LISTING_ID']]);
		if (!$aEntryInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

        // check access
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->checkAllowedView($aEntryInfo)))
            return MsgBox($sMsg);

        return $this->_oModule->_oTemplate->scheduleText($aScheduleInfo);
    }

	public function addDataForm($sDisplay = false, $sCheckFunction = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if (!$sCheckFunction)
            $sCheckFunction = 'checkAllowedAddSchedule';

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
        $iScheduleId = $oForm->insert ($aValsToAdd);
        if (!$iScheduleId) {
            if (!$oForm->isValid())
                return $oForm->getCode();
            else
                return MsgBox(_t('_sys_txt_error_entry_creation'));
        }

        $sResult = $this->onDataAddAfter(getLoggedId(), $iScheduleId);
        if ($sResult)
            return $sResult;
 
         // process uploaded files
        if (isset($CNF['FIELD_PHOTO']))
            $oForm->processFiles ($CNF['FIELD_PHOTO'], $iScheduleId, true);

        // redirect
        $this->_redirectAndExit('page.php?i=' . $CNF['URI_VIEW_SCHEDULE'] . '&id=' . $iScheduleId);
    }

    public function editDataForm($iScheduleId, $sDisplay = false, $sCheckFunction = false, $bErrorMsg = true)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
 
        if ($sDisplay === false)
            $sDisplay = $CNF['OBJECT_FORM_SCHEDULE_DISPLAY_EDIT'];

        if (!$sCheckFunction)
            $sCheckFunction = 'checkAllowedEditSchedule';

        // get content data
        $aScheduleInfo = $this->_oModule->_oDb->getScheduleInfoById($iScheduleId);
        if (!$aScheduleInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

        $aEntryInfo = $this->_oModule->_oDb->getContentInfoById($aScheduleInfo[$CNF['FIELD_LISTING_ID']]);
        if (!$aEntryInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

        // check access
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->$sCheckFunction($aScheduleInfo)))
            return MsgBox($sMsg);

        $oProfile = BxDolProfile::getInstanceMagic($aEntryInfo[$CNF['FIELD_AUTHOR']]);

        // check and display form
        $oForm = $this->getObjectFormEdit($sDisplay);
        if (!$oForm)
            return MsgBox(_t('_sys_txt_error_occured'));

        $aSpecificValues = array();        
        $oForm->initChecker($aScheduleInfo, $aSpecificValues);
        if (!$oForm->isSubmittedAndValid())
            return $oForm->getCode();

        // update data in the DB
        $aTrackTextFieldsChanges = null;
        $this->onDataEditBefore ($aScheduleInfo[$CNF['FIELD_ID']], $aScheduleInfo, $aTrackTextFieldsChanges, $oProfile, $oForm);

        if (!$oForm->update ($aScheduleInfo[$CNF['FIELD_ID']], array(), $aTrackTextFieldsChanges)) {
            if (!$oForm->isValid())
                return $oForm->getCode();
            else
                return MsgBox(_t('_sys_txt_error_entry_update'));
        }

        $sResult = $this->onDataEditAfter ($aScheduleInfo[$CNF['FIELD_ID']], $aScheduleInfo, $aTrackTextFieldsChanges, $oProfile, $oForm);
        if ($sResult)
            return $sResult;
 
         // process uploaded files
        if (isset($CNF['FIELD_PHOTO']))
            $oForm->processFiles ($CNF['FIELD_PHOTO'], $iScheduleId, true);

        // redirect
        $this->_redirectAndExit('page.php?i=' . $CNF['URI_VIEW_SCHEDULE'] . '&id=' . $iScheduleId);
    }

    public function deleteDataForm($iScheduleId, $sDisplay = false, $sCheckFunction = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if ($sDisplay === false)
            $sDisplay = $CNF['OBJECT_FORM_SCHEDULE_DISPLAY_DELETE'];

        if (!$sCheckFunction)
            $sCheckFunction = 'checkAllowedDeleteSchedule';

        // get content data
        $aScheduleInfo = $this->_oModule->_oDb->getScheduleInfoById($iScheduleId);
        if (!$aScheduleInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

		$aEntryInfo = $this->_oModule->_oDb->getContentInfoById($aScheduleInfo[$CNF['FIELD_LISTING_ID']]);
		if (!$aEntryInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

		$oProfile = BxDolProfile::getInstance($aEntryInfo[$CNF['FIELD_AUTHOR']]);
        if (!$oProfile) 
            $oProfile = BxDolProfileUndefined::getInstance();

        // check access
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->$sCheckFunction($aScheduleInfo)))
            return MsgBox($sMsg);

        // check and display form
        $oForm = $this->getObjectFormDelete($sDisplay);
        if (!$oForm)
            return MsgBox(_t('_sys_txt_error_occured'));

        $oForm->initChecker($aScheduleInfo);
        if (!$oForm->isSubmittedAndValid())
            return $oForm->getCode();

		$sError = $this->deleteData($aScheduleInfo[$CNF['FIELD_ID']], $aScheduleInfo, $oProfile, $oForm);
        if(!empty($sError))
            return MsgBox($sError);

        // redirect
        $this->_redirectAndExit('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aEntryInfo[$CNF['FIELD_ID']]);
    }

    /**
     * Delete data schedule
     * @param $iScheduleId entry id
     * @param $aScheduleInfo optional content info array
     * @param $oForm optional content info array
     * @return error string on error or empty string on success
     */
    public function deleteData($iScheduleId, $aScheduleInfo = false, $oProfile = null, $oForm = null)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if(!$aScheduleInfo)
			$aScheduleInfo = $this->_oModule->_oDb->getScheduleInfoById($iScheduleId);

        if(!$aScheduleInfo)
            return _t('_sys_txt_error_entry_is_not_defined');

        if(!$oForm)
            $oForm = BxDolForm::getObjectInstance($CNF['OBJECT_FORM_SCHEDULE'], $CNF['OBJECT_FORM_SCHEDULE_DISPLAY_DELETE'], $this->_oModule->_oTemplate);

        if(!$oForm->delete($aScheduleInfo[$CNF['FIELD_ID']], $aScheduleInfo))
            return _t('_sys_txt_error_entry_delete');

		$sResult = $this->onDataDeleteAfter ($aScheduleInfo[$CNF['FIELD_ID']], $aScheduleInfo, $oProfile);
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
 
		$oForm = BxDolForm::getObjectInstance($CNF['OBJECT_FORM_SCHEDULE'], $CNF['OBJECT_FORM_SCHEDULE_DISPLAY_DELETE'], $this->_oModule->_oTemplate);

		$aEntries = $this->_oModule->_oDb->getScheduleInfoByEntryId($iEntryId);

		foreach($aEntries as $aEntry)
		{  
			$oForm->delete($aEntry[$CNF['FIELD_ID']], $aEntry);
		} 
    }

    public function onDataAddAfter($iAccountId, $iScheduleId)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $aScheduleInfo = $this->_oModule->_oDb->getScheduleInfoById($iScheduleId);

        $iEntryId = (int)$aScheduleInfo[$CNF['FIELD_LISTING_ID']];
        $aEntryInfo = $this->_oModule->_oDb->getContentInfoById($iEntryId);

        $aEntryInfo[$CNF['FIELD_SCHEDULE']] += 1;
        $this->_oModule->_oDb->updateContentInfoById($iEntryId, array(
            $CNF['FIELD_SCHEDULE'] => $aEntryInfo[$CNF['FIELD_SCHEDULE']]
        ));
 
        return '';
    }

    public function onDataEditBefore($iContentId, $aContentInfo, &$aTrackTextFieldsChanges, &$oProfile, &$oForm)
    {
        return '';
    }

    public function onDataEditAfter($iScheduleId, $aScheduleInfo, $aTrackTextFieldsChanges, $oProfile, $oForm)
    {
        return '';
    }
 
    public function onDataDeleteAfter($iScheduleId, $aScheduleInfo, $oProfile)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $iEntryId = (int)$aScheduleInfo[$CNF['FIELD_LISTING_ID']];
        $aEntryInfo = $this->_oModule->_oDb->getContentInfoById($iEntryId);
        
        $aParams = array(
            $CNF['FIELD_SCHEDULE'] => $aEntryInfo[$CNF['FIELD_SCHEDULE']] - 1
        );
 
        $this->_oModule->_oDb->updateContentInfoById($iEntryId, $aParams);

        return '';
    }
}

/** @} */
