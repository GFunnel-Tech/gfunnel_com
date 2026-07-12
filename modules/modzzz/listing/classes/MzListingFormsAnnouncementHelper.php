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

class MzListingFormsAnnouncementHelper extends BxBaseModTextFormsEntryHelper
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
            $sDisplay = $CNF['OBJECT_FORM_ANNOUNCEMENT_DISPLAY_VIEW'];
 
        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_ANNOUNCEMENT'], $sDisplay, $this->_oModule->_oTemplate);
    }

    public function viewDataForm ($iContentId, $sDisplay = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
 
         $aContentInfo = $this->_oModule->_oDb->getAnnouncementInfoById($iContentId);

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

        bx_import('FormsAnnouncementHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsAnnouncementHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iContentId, $sDisplay, $sCheckFunction, $bErrorMsg);
    }

    public function getObjectFormAdd ($sDisplay = false)
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;

        if (false === $sDisplay)
            $sDisplay = $CNF['OBJECT_FORM_ANNOUNCEMENT_DISPLAY_ADD'];
        
        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_ANNOUNCEMENT'], $sDisplay, $this->_oModule->_oTemplate);
    }

    public function getObjectFormEdit ($sDisplay = false)
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;

        if (false === $sDisplay)
            $sDisplay = $CNF['OBJECT_FORM_ANNOUNCEMENT_DISPLAY_EDIT'];

        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_ANNOUNCEMENT'], $sDisplay, $this->_oModule->_oTemplate);
    }

    public function getObjectFormDelete ($sDisplay = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if (false === $sDisplay)
            $sDisplay = $CNF['OBJECT_FORM_ANNOUNCEMENT_DISPLAY_DELETE'];

        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_ANNOUNCEMENT'], $sDisplay, $this->_oModule->_oTemplate);
    }

    public function viewDataEntry($iAnnouncementId)
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;
 
    	$aAnnouncementInfo = $this->_oModule->_oDb->getAnnouncementInfoById($iAnnouncementId);
        if (!$aAnnouncementInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

		$aEntryInfo = $this->_oModule->_oDb->getContentInfoById($aAnnouncementInfo[$CNF['FIELD_LISTING_ID']]);
		if (!$aEntryInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

        // check access
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->checkAllowedView($aEntryInfo)))
            return MsgBox($sMsg);

        return $this->_oModule->_oTemplate->announcementText($aAnnouncementInfo);
    }

	public function addDataForm($sDisplay = false, $sCheckFunction = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if (!$sCheckFunction)
            $sCheckFunction = 'checkAllowedAddAnnouncement';

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
        $iAnnouncementId = $oForm->insert ($aValsToAdd);
        if (!$iAnnouncementId) {
            if (!$oForm->isValid())
                return $oForm->getCode();
            else
                return MsgBox(_t('_sys_txt_error_entry_creation'));
        }

        $sResult = $this->onDataAddAfter(getLoggedId(), $iAnnouncementId);
        if ($sResult)
            return $sResult;
 
         // process uploaded files
        if (isset($CNF['FIELD_PHOTO']))
            $oForm->processFiles ($CNF['FIELD_PHOTO'], $iAnnouncementId, true);

        // redirect
        $this->_redirectAndExit('page.php?i=' . $CNF['URI_VIEW_ANNOUNCEMENT'] . '&id=' . $iAnnouncementId);
    }

    public function editDataForm($iAnnouncementId, $sDisplay = false, $sCheckFunction = false, $bErrorMsg = true)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
 
        if ($sDisplay === false)
            $sDisplay = $CNF['OBJECT_FORM_ANNOUNCEMENT_DISPLAY_EDIT'];

        if (!$sCheckFunction)
            $sCheckFunction = 'checkAllowedEditAnnouncement';

        // get content data
        $aAnnouncementInfo = $this->_oModule->_oDb->getAnnouncementInfoById($iAnnouncementId);
        if (!$aAnnouncementInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

        $aEntryInfo = $this->_oModule->_oDb->getContentInfoById($aAnnouncementInfo[$CNF['FIELD_LISTING_ID']]);
        if (!$aEntryInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

        // check access
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->$sCheckFunction($aAnnouncementInfo)))
            return MsgBox($sMsg);

        $oProfile = BxDolProfile::getInstanceMagic($aEntryInfo[$CNF['FIELD_AUTHOR']]);

        // check and display form
        $oForm = $this->getObjectFormEdit($sDisplay);
        if (!$oForm)
            return MsgBox(_t('_sys_txt_error_occured'));

        $aSpecificValues = array();        
        $oForm->initChecker($aAnnouncementInfo, $aSpecificValues);
        if (!$oForm->isSubmittedAndValid())
            return $oForm->getCode();

        // update data in the DB
        $aTrackTextFieldsChanges = null;
        $this->onDataEditBefore ($aAnnouncementInfo[$CNF['FIELD_ID']], $aAnnouncementInfo, $aTrackTextFieldsChanges, $oProfile, $oForm);

        if (!$oForm->update ($aAnnouncementInfo[$CNF['FIELD_ID']], array(), $aTrackTextFieldsChanges)) {
            if (!$oForm->isValid())
                return $oForm->getCode();
            else
                return MsgBox(_t('_sys_txt_error_entry_update'));
        }

        $sResult = $this->onDataEditAfter ($aAnnouncementInfo[$CNF['FIELD_ID']], $aAnnouncementInfo, $aTrackTextFieldsChanges, $oProfile, $oForm);
        if ($sResult)
            return $sResult;
 
         // process uploaded files
        if (isset($CNF['FIELD_PHOTO']))
            $oForm->processFiles ($CNF['FIELD_PHOTO'], $iAnnouncementId, true);

        // redirect
        $this->_redirectAndExit('page.php?i=' . $CNF['URI_VIEW_ANNOUNCEMENT'] . '&id=' . $iAnnouncementId);
    }

    public function deleteDataForm($iAnnouncementId, $sDisplay = false, $sCheckFunction = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if ($sDisplay === false)
            $sDisplay = $CNF['OBJECT_FORM_ANNOUNCEMENT_DISPLAY_DELETE'];

        if (!$sCheckFunction)
            $sCheckFunction = 'checkAllowedDeleteAnnouncement';

        // get content data
        $aAnnouncementInfo = $this->_oModule->_oDb->getAnnouncementInfoById($iAnnouncementId);
        if (!$aAnnouncementInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

		$aEntryInfo = $this->_oModule->_oDb->getContentInfoById($aAnnouncementInfo[$CNF['FIELD_LISTING_ID']]);
		if (!$aEntryInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

		$oProfile = BxDolProfile::getInstance($aEntryInfo[$CNF['FIELD_AUTHOR']]);
        if (!$oProfile) 
            $oProfile = BxDolProfileUndefined::getInstance();

        // check access
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->$sCheckFunction($aAnnouncementInfo)))
            return MsgBox($sMsg);

        // check and display form
        $oForm = $this->getObjectFormDelete($sDisplay);
        if (!$oForm)
            return MsgBox(_t('_sys_txt_error_occured'));

        $oForm->initChecker($aAnnouncementInfo);
        if (!$oForm->isSubmittedAndValid())
            return $oForm->getCode();

		$sError = $this->deleteData($aAnnouncementInfo[$CNF['FIELD_ID']], $aAnnouncementInfo, $oProfile, $oForm);
        if(!empty($sError))
            return MsgBox($sError);

        // redirect
        $this->_redirectAndExit('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aEntryInfo[$CNF['FIELD_ID']]);
    }

    /**
     * Delete data announcement
     * @param $iAnnouncementId entry id
     * @param $aAnnouncementInfo optional content info array
     * @param $oForm optional content info array
     * @return error string on error or empty string on success
     */
    public function deleteData($iAnnouncementId, $aAnnouncementInfo = false, $oProfile = null, $oForm = null)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if(!$aAnnouncementInfo)
			$aAnnouncementInfo = $this->_oModule->_oDb->getAnnouncementInfoById($iAnnouncementId);

        if(!$aAnnouncementInfo)
            return _t('_sys_txt_error_entry_is_not_defined');

        if(!$oForm)
            $oForm = BxDolForm::getObjectInstance($CNF['OBJECT_FORM_ANNOUNCEMENT'], $CNF['OBJECT_FORM_ANNOUNCEMENT_DISPLAY_DELETE'], $this->_oModule->_oTemplate);

        if(!$oForm->delete($aAnnouncementInfo[$CNF['FIELD_ID']], $aAnnouncementInfo))
            return _t('_sys_txt_error_entry_delete');

		$sResult = $this->onDataDeleteAfter ($aAnnouncementInfo[$CNF['FIELD_ID']], $aAnnouncementInfo, $oProfile);
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
 
		$oForm = BxDolForm::getObjectInstance($CNF['OBJECT_FORM_ANNOUNCEMENT'], $CNF['OBJECT_FORM_ANNOUNCEMENT_DISPLAY_DELETE'], $this->_oModule->_oTemplate);

		$aEntries = $this->_oModule->_oDb->getAnnouncementInfoByEntryId($iEntryId);

		foreach($aEntries as $aEntry)
		{  
			$oForm->delete($aEntry[$CNF['FIELD_ID']], $aEntry);
		} 
    }

    public function onDataAddAfter($iAccountId, $iAnnouncementId)
    {
        return '';
    }

    public function onDataEditBefore($iContentId, $aContentInfo, &$aTrackTextFieldsChanges, &$oProfile, &$oForm)
    {
        return '';
    }

    public function onDataEditAfter($iAnnouncementId, $aAnnouncementInfo, $aTrackTextFieldsChanges, $oProfile, $oForm)
    {
        return '';
    }
 
    public function onDataDeleteAfter($iAnnouncementId, $aAnnouncementInfo, $oProfile)
    { 
        return '';
    }
}

/** @} */
