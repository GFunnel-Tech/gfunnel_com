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

class MzListingFormsClaimHelper extends BxBaseModTextFormsEntryHelper
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
            $sDisplay = $CNF['OBJECT_FORM_CLAIM_DISPLAY_VIEW'];
 
        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_CLAIM'], $sDisplay, $this->_oModule->_oTemplate);
    }

    public function viewDataForm ($iContentId, $sDisplay = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
 
         $aContentInfo = $this->_oModule->_oDb->getClaimInfoById($iContentId);
  
        // get form
        $oForm = $this->getObjectFormView($sDisplay);
        if (!$oForm)
            return MsgBox(_t('_sys_txt_error_occured'));
 
        // process metatags
        if (!empty($CNF['OBJECT_METATAGS_CLAIM'])) {
            $oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS_CLAIM']);
            if ($oMetatags->keywordsIsEnabled()) {
                $aFields = $oMetatags->metaFields($aContentInfo, $CNF, $CNF['OBJECT_FORM_CLAIM_DISPLAY_VIEW']);
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

        bx_import('FormsClaimHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsClaimHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iContentId, $sDisplay, $sCheckFunction, $bErrorMsg);
    }

    public function getObjectFormAdd ($sDisplay = false)
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;
 
        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_CLAIM'], $CNF['OBJECT_FORM_CLAIM_DISPLAY_ADD'], $this->_oModule->_oTemplate);
    }

    public function getObjectFormEdit ($sDisplay = false)
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;

        if (false === $sDisplay)
            $sDisplay = $CNF['OBJECT_FORM_CLAIM_DISPLAY_EDIT'];

        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_CLAIM'], $sDisplay, $this->_oModule->_oTemplate);
    }

    public function getObjectFormDelete ($sDisplay = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if (false === $sDisplay)
            $sDisplay = $CNF['OBJECT_FORM_CLAIM_DISPLAY_DELETE'];

        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_CLAIM'], $sDisplay, $this->_oModule->_oTemplate);
    }

    public function viewDataEntry($iClaimId)
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;
 
    	$aClaimInfo = $this->_oModule->_oDb->getClaimInfoById($iClaimId);
        if (!$aClaimInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

		$aEntryInfo = $this->_oModule->_oDb->getContentInfoById($aClaimInfo[$CNF['FIELD_LISTING_ID']]);
		if (!$aEntryInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

        // check access
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->checkAllowedView($aEntryInfo)))
            return MsgBox($sMsg);

        return $this->_oModule->_oTemplate->subitemText($aClaimInfo, 'claim');
    }
 
    public function viewBusinessEntry($iClaimId)
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;
 
    	$aClaimInfo = $this->_oModule->_oDb->getClaimInfoById($iClaimId);
 
		$aEntryInfo = $this->_oModule->_oDb->getContentInfoById($aClaimInfo[$CNF['FIELD_LISTING_ID']]);
		if (!$aEntryInfo)
            return;
 
        return $this->_oModule->_oTemplate->businessText($aEntryInfo);
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
        if (!$oForm->isSubmittedAndValid())
            return array('code' => 2, 'message' => '_sys_txt_error_occured');

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
        $this->_oModule->processClaimMetasAdd($iContentId);

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
            $sCheckFunction = 'checkAllowedAddClaim';

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
/*
	    $iListingAuthor = $aEntryInfo[$CNF['FIELD_AUTHOR']]; 
		$oProfile = BxDolProfile::getInstance($iListingAuthor);
		if($oProfile)
        {   
		   if(isAdmin($oProfile->getAccountId()))
				$oForm->aInputs[$CNF['FIELD_CLAIM_STATUS']] = 'claimable';
		}
*/
 
		$oForm->aInputs['entry_id']['value'] = $this->_iEntryId;

        $oForm->initChecker();
 
        if (!$oForm->isSubmittedAndValid())
            return $oForm->getCode();

        // insert data into database
        $aValsToAdd = array ();
        $iClaimId = $oForm->insert ($aValsToAdd);
        /*
		if (!$iClaimId) {
            if (!$oForm->isValid())
                return $oForm->getCode();
            else
                return MsgBox(_t('_sys_txt_error_entry_creation'));
        }*/

        $sResult = $this->onDataAddAfter(getLoggedId(), $iClaimId);
        if ($sResult)
            return $sResult;
 
        //$this->_oModule->processClaimMetasAdd($iClaimId);
   
        // redirect
        $this->_redirectAndExit('page.php?i=' . $CNF['URI_VIEW_CLAIM'] . '&id=' . $iClaimId);
    }

    public function editDataForm($iClaimId, $sDisplay = false, $sCheckFunction = false, $bErrorMsg = true)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
 
        if ($sDisplay === false)
            $sDisplay = $CNF['OBJECT_FORM_CLAIM_DISPLAY_EDIT'];

        if (!$sCheckFunction)
            $sCheckFunction = 'checkAllowedEditClaim';

        // get content data
        $aClaimInfo = $this->_oModule->_oDb->getClaimInfoById($iClaimId);
        if (!$aClaimInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

        $aEntryInfo = $this->_oModule->_oDb->getContentInfoById($aClaimInfo[$CNF['FIELD_LISTING_ID']]);
        if (!$aEntryInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

        // check access
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->$sCheckFunction($aClaimInfo)))
            return MsgBox($sMsg);

        $oProfile = BxDolProfile::getInstanceMagic($aEntryInfo[$CNF['FIELD_AUTHOR']]);

        // check and display form
        $oForm = $this->getObjectFormEdit($sDisplay);
        if (!$oForm)
            return MsgBox(_t('_sys_txt_error_occured'));
 
        $aSpecificValues = array();        
        if (!empty($CNF['OBJECT_METATAGS_CLAIM'])) {  
            $oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS_CLAIM']);
            if ($oMetatags->locationsIsEnabled()){ 
                $aSpecificValues = $oMetatags->locationGet($iClaimId, empty($CNF['FIELD_LOCATION_PREFIX']) ? '' : $CNF['FIELD_LOCATION_PREFIX']);
			}
        }
 
        $oForm->initChecker($aClaimInfo, $aSpecificValues);
        if (!$oForm->isSubmittedAndValid())
            return $oForm->getCode();

        // update data in the DB
        $aTrackTextFieldsChanges = null;
        $this->onDataEditBefore ($aClaimInfo[$CNF['FIELD_ID']], $aClaimInfo, $aTrackTextFieldsChanges, $oProfile, $oForm);

        if (!$oForm->update ($aClaimInfo[$CNF['FIELD_ID']], array(), $aTrackTextFieldsChanges)) {
            if (!$oForm->isValid())
                return $oForm->getCode();
            else
                return MsgBox(_t('_sys_txt_error_entry_update'));
        }

        $sResult = $this->onDataEditAfter ($aClaimInfo[$CNF['FIELD_ID']], $aClaimInfo, $aTrackTextFieldsChanges, $oProfile, $oForm);
        if ($sResult)
            return $sResult;
  
        /*
         * Process metas.
         * Note. It's essential to process metas a the very end, 
         * because all data related to an entry should be already
         * processed and are ready to be passed to alert. 
         */
        $this->_oModule->processClaimMetasEdit($iClaimId, $oForm);

        // redirect
        $this->_redirectAndExit('page.php?i=' . $CNF['URI_VIEW_CLAIM'] . '&id=' . $iClaimId);
    }

    public function deleteDataForm($iClaimId, $sDisplay = false, $sCheckFunction = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if ($sDisplay === false)
            $sDisplay = $CNF['OBJECT_FORM_CLAIM_DISPLAY_DELETE'];

        if (!$sCheckFunction)
            $sCheckFunction = 'checkAllowedDeleteClaim';

        // get content data
        $aClaimInfo = $this->_oModule->_oDb->getClaimInfoById($iClaimId);
        if (!$aClaimInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

		$aEntryInfo = $this->_oModule->_oDb->getContentInfoById($aClaimInfo[$CNF['FIELD_LISTING_ID']]);
		if (!$aEntryInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

		$oProfile = BxDolProfile::getInstance($aEntryInfo[$CNF['FIELD_AUTHOR']]);
        if (!$oProfile) 
            $oProfile = BxDolProfileUndefined::getInstance();

        // check access
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->$sCheckFunction($aClaimInfo)))
            return MsgBox($sMsg);

        // check and display form
        $oForm = $this->getObjectFormDelete($sDisplay);
        if (!$oForm)
            return MsgBox(_t('_sys_txt_error_occured'));

        $oForm->initChecker($aClaimInfo);
        if (!$oForm->isSubmittedAndValid())
            return $oForm->getCode();

		$sError = $this->deleteData($aClaimInfo[$CNF['FIELD_ID']], $aClaimInfo, $oProfile, $oForm);
        if(!empty($sError))
            return MsgBox($sError);

        // redirect
        $this->_redirectAndExit('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aEntryInfo[$CNF['FIELD_ID']]);
    }

    /**
     * Delete all data 
     * @param $iEntryId entry id
     */
    public function deleteAllData($iEntryId)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
 
		$oForm = BxDolForm::getObjectInstance($CNF['OBJECT_FORM_CLAIM'], $CNF['OBJECT_FORM_CLAIM_DISPLAY_DELETE'], $this->_oModule->_oTemplate);

		$aEntries = $this->_oModule->_oDb->getClaimsInfoByEntryId($iEntryId);

		foreach($aEntries as $aEntry)
		{  
			$oForm->delete($aEntry[$CNF['FIELD_ID']], $aEntry);
		} 
    }


    /**
     * Delete data claim
     * @param $iClaimId entry id
     * @param $aClaimInfo optional content info array
     * @param $oForm optional content info array
     * @return error string on error or empty string on success
     */
    public function deleteData($iClaimId, $aClaimInfo = false, $oProfile = null, $oForm = null)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if(!$aClaimInfo)
			$aClaimInfo = $this->_oModule->_oDb->getClaimInfoById($iClaimId);

        if(!$aClaimInfo)
            return _t('_sys_txt_error_entry_is_not_defined');

        if(!$oForm)
            $oForm = BxDolForm::getObjectInstance($CNF['OBJECT_FORM_CLAIM'], $CNF['OBJECT_FORM_CLAIM_DISPLAY_DELETE'], $this->_oModule->_oTemplate);

        if(!$oForm->delete($aClaimInfo[$CNF['FIELD_ID']], $aClaimInfo))
            return _t('_sys_txt_error_entry_delete');

		$sResult = $this->onDataDeleteAfter ($aClaimInfo[$CNF['FIELD_ID']], $aClaimInfo, $oProfile);
        if(!empty($sResult))
			return $sResult;

        return '';
    }

    public function onDataAddAfter($iAccountId, $iContentId)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
 
        $aContentInfo = $this->_oModule->_oDb->getClaimInfoById($iContentId);
  
        if(($oForm = $this->getObjectFormAdd()) !== false) { 
            if(isset($CNF['FIELD_PHOTO']))
                $oForm->processFiles($CNF['FIELD_PHOTO'], $iContentId, true);
        }
  
        $iViewer = bx_get_logged_profile_id();        
        $oViewer = BxDolProfile::getInstance($iViewer);
        if(!$oViewer)
			return '';

        $iContentAuthor = (int)$aContentInfo[$CNF['FIELD_AUTHOR']];
        if($iContentAuthor == $iViewer)
			return '';
 
        sendMailTemplate($CNF['ETEMPLATE_CLAIM_PENDING'], 0, $iContentAuthor, array(
            'viewer_name' => $oViewer->getDisplayName(),
            'viewer_url' => $oViewer->getUrl(),
            'listing_name' => $aContentInfo[$CNF['FIELD_TITLE']],
            'listing_url' => BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php', array(
                'i' => $CNF['URI_VIEW_ENTRY'], 
                $CNF['FIELD_ID'] => $iContentId
            )),
            'manage_url' => BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink($CNF['URL_MANAGE_ADMINISTRATION'])
        ));
 
        return '';
    }

    public function onDataEditBefore($iContentId, $aContentInfo, &$aTrackTextFieldsChanges, &$oProfile, &$oForm)
    {
        return '';
    } 

    public function onDataEditAfter($iClaimId, $aClaimInfo, $aTrackTextFieldsChanges, $oProfile, $oForm)
    { 
        $CNF = &$this->_oModule->_oConfig->CNF;

        if(isset($CNF['FIELD_PHOTO'])){
            $oForm->processFiles ($CNF['FIELD_PHOTO'], $iClaimId, false);
		}
    }
 
    public function onDataDeleteAfter($iClaimId, $aClaimInfo, $oProfile)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        return '';
    }
}

/** @} */
