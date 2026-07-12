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

class MzGoalFormsSponsorHelper extends BxBaseModTextFormsEntryHelper
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
            $sDisplay = $CNF['OBJECT_FORM_SPONSOR_DISPLAY_VIEW'];
 
        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_SPONSOR'], $sDisplay, $this->_oModule->_oTemplate);
    }

    public function viewDataForm ($iContentId, $sDisplay = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
 
         $aContentInfo = $this->_oModule->_oDb->getSponsorInfoById($iContentId);

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
 

        // process metatags
        if (!empty($CNF['OBJECT_METATAGS_SPONSOR'])) {
            $oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS_SPONSOR']);
            if ($oMetatags->keywordsIsEnabled()) {
                $aFields = $oMetatags->metaFields($aContentInfo, $CNF, $CNF['OBJECT_FORM_SPONSOR_DISPLAY_VIEW']);
                $oForm->setMetatagsKeywordsData($iContentId, $aFields, $oMetatags);
            }
        }  
 
        // display profile
        $oForm->initChecker($aContentInfo);
        return $oForm->getCode();
    }
 
    protected function _serviceEntityForm ($sFormMethod, $iContentId = 0, $sDisplay = false, $sCheckFunction = false, $bErrorMsg = true)
    {
        $iContentId = $this->_getContent($iContentId, false);
        if($iContentId === false)
            return false;

        bx_import('FormsSponsorHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsSponsorHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iContentId, $sDisplay, $sCheckFunction, $bErrorMsg);
    }

    public function getObjectFormAdd ($sDisplay = false)
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;

        if (false === $sDisplay)
            $sDisplay = $CNF['OBJECT_FORM_SPONSOR_DISPLAY_ADD'];
        
        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_SPONSOR'], $sDisplay, $this->_oModule->_oTemplate);
    }

    public function getObjectFormEdit ($sDisplay = false)
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;

        if (false === $sDisplay)
            $sDisplay = $CNF['OBJECT_FORM_SPONSOR_DISPLAY_EDIT'];

        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_SPONSOR'], $sDisplay, $this->_oModule->_oTemplate);
    }

    public function getObjectFormDelete ($sDisplay = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if (false === $sDisplay)
            $sDisplay = $CNF['OBJECT_FORM_SPONSOR_DISPLAY_DELETE'];

        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_SPONSOR'], $sDisplay, $this->_oModule->_oTemplate);
    }

    public function viewDataEntry($iSponsorId)
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;
 
    	$aSponsorInfo = $this->_oModule->_oDb->getSponsorInfoById($iSponsorId);
        if (!$aSponsorInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

		$aEntryInfo = $this->_oModule->_oDb->getContentInfoById($aSponsorInfo[$CNF['FIELD_GOAL_ID']]);
		if (!$aEntryInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

        // check access
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->checkAllowedView($aEntryInfo)))
            return MsgBox($sMsg);

        return $this->_oModule->_oTemplate->sponsorText($aSponsorInfo);
    }

    public function addData ($iProfile, $aValues, $sDisplay = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        // check and display form
        $oForm = $this->getObjectFormAdd($sDisplay);
        if (!$oForm)
            return array('code' => 1, 'message' => '_sys_txt_error_occured');

        $oForm->aFormAttrs['method'] = BX_DOL_FORM_METHOD_SPECIFIC;
        $oForm->aParams['csrf']['disable'] = true;
        if(!empty($oForm->aParams['db']['submit_name'])) {            
            $sSubmitName = false;
            if (is_array($oForm->aParams['db']['submit_name'])) {
                foreach ($oForm->aParams['db']['submit_name'] as $sVal) {
                    if (isset($oForm->aInputs[$sVal])) {
                        $sSubmitName = $sVal;
                        break;
                    }
                }
            } 
            else {
                $sSubmitName = $oForm->aParams['db']['submit_name'];
            }
            if ($sSubmitName && isset($oForm->aInputs[$sSubmitName]))
                $aValues[$sSubmitName] = $oForm->aInputs[$sSubmitName]['value'];
        }

        $oForm->initChecker(array(), $aValues);
        if (!$oForm->isSubmittedAndValid()) {
            $aErrors = array();
            array_walk($oForm->aInputs, function($aInput, $sKey) use (&$aErrors) {
                if(!empty($aInput['error']))
                    $aErrors[$sKey] = $aInput['error'];
            });

            return array('code' => 2, 'message' => '_sys_txt_error_occured', 'errors' => $aErrors);
        }

        // insert data into database
        $aValsToAdd = array ();
        if(isset($CNF['FIELD_AUTHOR']))
            $aValsToAdd[$CNF['FIELD_AUTHOR']] = $iProfile;

        $iContentId = $oForm->insert($aValsToAdd);
        if (!$iContentId) {
            if (!$oForm->isValid())
                return array('code' => 2, 'message' => '_sys_txt_error_occured');
            else
                return array('code' => 3, 'message' => '_sys_txt_error_entry_creation');
        }

        $sResult = $this->onDataAddAfter(BxDolProfile::getInstance($iProfile)->getAccountId(), $iContentId);
        if($sResult)
            return array('code' => 4, 'message' => $sResult);

        list($oProfile, $aContentInfo) = $this->_getProfileAndContentData($iContentId);

        /*
         * Process metas.
         * Note. It's essential to process metas a the very end, 
         * because all data related to an entry should be already
         * processed and are ready to be passed to alert. 
         */
        $this->_oModule->processSponsorMetasAdd($iContentId);

        /*
         * Create alert about the completed action.
         */
        $this->_oModule->alertAfterAdd($aContentInfo);

        return array('code' => 0, 'message' => '', 'content' => $aContentInfo);
    }
 
	public function addDataForm($sDisplay = false, $sCheckFunction = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if (!$sCheckFunction)
            $sCheckFunction = 'checkAllowedAddSponsor';

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
        $iSponsorId = $oForm->insert ($aValsToAdd);
        if (!$iSponsorId) {
            if (!$oForm->isValid())
                return $oForm->getCode();
            else
                return MsgBox(_t('_sys_txt_error_entry_creation'));
        }

        $sResult = $this->onDataAddAfter(getLoggedId(), $iSponsorId);
        if ($sResult)
            return $sResult;
 
         $this->_oModule->processSponsorMetasAdd($iSponsorId);
 
        // redirect
        $this->_redirectAndExit('page.php?i=' . $CNF['URI_VIEW_SPONSOR'] . '&id=' . $iSponsorId);
    }

    public function editDataForm($iSponsorId, $sDisplay = false, $sCheckFunction = false, $bErrorMsg = true)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
 
        if ($sDisplay === false)
            $sDisplay = $CNF['OBJECT_FORM_SPONSOR_DISPLAY_EDIT'];

        if (!$sCheckFunction)
            $sCheckFunction = 'checkAllowedEditSponsor';

        // get content data
        $aSponsorInfo = $this->_oModule->_oDb->getSponsorInfoById($iSponsorId);
        if (!$aSponsorInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

        $aEntryInfo = $this->_oModule->_oDb->getContentInfoById($aSponsorInfo[$CNF['FIELD_GOAL_ID']]);
        if (!$aEntryInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

        // check access
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->$sCheckFunction($aSponsorInfo)))
            return MsgBox($sMsg);

        $oProfile = BxDolProfile::getInstanceMagic($aEntryInfo[$CNF['FIELD_AUTHOR']]);

        // check and display form
        $oForm = $this->getObjectFormEdit($sDisplay);
        if (!$oForm)
            return MsgBox(_t('_sys_txt_error_occured'));

        $aSpecificValues = array();        
        if (!empty($CNF['OBJECT_METATAGS_SPONSOR'])) {  
            $oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS_SPONSOR']);
            if ($oMetatags->locationsIsEnabled()){ 
                $aSpecificValues = $oMetatags->locationGet($iSponsorId, empty($CNF['FIELD_LOCATION_PREFIX']) ? '' : $CNF['FIELD_LOCATION_PREFIX']);
			}
        }

        $aSpecificValues = array();        
        $oForm->initChecker($aSponsorInfo, $aSpecificValues);
        if (!$oForm->isSubmittedAndValid())
            return $oForm->getCode();

        // update data in the DB
        $aTrackTextFieldsChanges = null;
        $this->onDataEditBefore ($aSponsorInfo[$CNF['FIELD_ID']], $aSponsorInfo, $aTrackTextFieldsChanges, $oProfile, $oForm);

        if (!$oForm->update ($aSponsorInfo[$CNF['FIELD_ID']], array(), $aTrackTextFieldsChanges)) {
            if (!$oForm->isValid())
                return $oForm->getCode();
            else
                return MsgBox(_t('_sys_txt_error_entry_update'));
        }

        $sResult = $this->onDataEditAfter ($aSponsorInfo[$CNF['FIELD_ID']], $aSponsorInfo, $aTrackTextFieldsChanges, $oProfile, $oForm);
        if ($sResult)
            return $sResult;
 
        $this->_oModule->processSponsorMetasEdit($iSponsorId, $oForm);

        // redirect
        $this->_redirectAndExit('page.php?i=' . $CNF['URI_VIEW_SPONSOR'] . '&id=' . $iSponsorId);
    }

    public function deleteDataForm($iSponsorId, $sDisplay = false, $sCheckFunction = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if ($sDisplay === false)
            $sDisplay = $CNF['OBJECT_FORM_SPONSOR_DISPLAY_DELETE'];

        if (!$sCheckFunction)
            $sCheckFunction = 'checkAllowedDeleteSponsor';

        // get content data
        $aSponsorInfo = $this->_oModule->_oDb->getSponsorInfoById($iSponsorId);
        if (!$aSponsorInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

		$aEntryInfo = $this->_oModule->_oDb->getContentInfoById($aSponsorInfo[$CNF['FIELD_GOAL_ID']]);
		if (!$aEntryInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

		$oProfile = BxDolProfile::getInstance($aEntryInfo[$CNF['FIELD_AUTHOR']]);
        if (!$oProfile) 
            $oProfile = BxDolProfileUndefined::getInstance();

        // check access
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->$sCheckFunction($aSponsorInfo)))
            return MsgBox($sMsg);

        // check and display form
        $oForm = $this->getObjectFormDelete($sDisplay);
        if (!$oForm)
            return MsgBox(_t('_sys_txt_error_occured'));

        $oForm->initChecker($aSponsorInfo);
        if (!$oForm->isSubmittedAndValid())
            return $oForm->getCode();

		$sError = $this->deleteData($aSponsorInfo[$CNF['FIELD_ID']], $aSponsorInfo, $oProfile, $oForm);
        if(!empty($sError))
            return MsgBox($sError);

        // redirect
        $this->_redirectAndExit('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aEntryInfo[$CNF['FIELD_ID']]);
    }

    /**
     * Delete data sponsor
     * @param $iSponsorId entry id
     * @param $aSponsorInfo optional content info array
     * @param $oForm optional content info array
     * @return error string on error or empty string on success
     */
    public function deleteData($iSponsorId, $aSponsorInfo = false, $oProfile = null, $oForm = null)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if(!$aSponsorInfo)
			$aSponsorInfo = $this->_oModule->_oDb->getSponsorInfoById($iSponsorId);

        if(!$aSponsorInfo)
            return _t('_sys_txt_error_entry_is_not_defined');

        if(!$oForm)
            $oForm = BxDolForm::getObjectInstance($CNF['OBJECT_FORM_SPONSOR'], $CNF['OBJECT_FORM_SPONSOR_DISPLAY_DELETE'], $this->_oModule->_oTemplate);

        if(!$oForm->delete($aSponsorInfo[$CNF['FIELD_ID']], $aSponsorInfo))
            return _t('_sys_txt_error_entry_delete');

		$sResult = $this->onDataDeleteAfter ($aSponsorInfo[$CNF['FIELD_ID']], $aSponsorInfo, $oProfile);
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
 
		$oForm = BxDolForm::getObjectInstance($CNF['OBJECT_FORM_SPONSOR'], $CNF['OBJECT_FORM_SPONSOR_DISPLAY_DELETE'], $this->_oModule->_oTemplate);

		$aEntries = $this->_oModule->_oDb->getSponsorsInfoByEntryId($iEntryId);

		foreach($aEntries as $aEntry)
		{  
			$oForm->delete($aEntry[$CNF['FIELD_ID']], $aEntry);
		} 
    }
 
    public function onDataAddAfter($iAccountId, $iSponsorId)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $aSponsorInfo = $this->_oModule->_oDb->getSponsorInfoById($iSponsorId);

        $iEntryId = (int)$aSponsorInfo[$CNF['FIELD_GOAL_ID']];
        $aEntryInfo = $this->_oModule->_oDb->getContentInfoById($iEntryId);

        $aEntryInfo[$CNF['FIELD_SPONSORS']] += 1;
        $this->_oModule->_oDb->updateContentInfoById($iEntryId, array(
            $CNF['FIELD_SPONSORS'] => $aEntryInfo[$CNF['FIELD_SPONSORS']]
        ));
 
        return '';
    }

    public function onDataEditBefore($iContentId, $aContentInfo, &$aTrackTextFieldsChanges, &$oProfile, &$oForm)
    {
        return '';
    }

    public function onDataEditAfter($iSponsorId, $aSponsorInfo, $aTrackTextFieldsChanges, $oProfile, $oForm)
    {
        return '';
    }
 
    public function onDataDeleteAfter($iSponsorId, $aSponsorInfo, $oProfile)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $iEntryId = (int)$aSponsorInfo[$CNF['FIELD_GOAL_ID']];
        $aEntryInfo = $this->_oModule->_oDb->getContentInfoById($iEntryId);
        
        $aParams = array(
            $CNF['FIELD_SPONSORS'] => $aEntryInfo[$CNF['FIELD_SPONSORS']] - 1
        );
 
        $this->_oModule->_oDb->updateContentInfoById($iEntryId, $aParams);

        return '';
    }
}

/** @} */
