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

class MzListingFormsBroadcastHelper extends BxBaseModTextFormsEntryHelper
{
    protected $_iEntryId;

    public function __construct($oModule, $iEntryId = 0)
    {
        parent::__construct($oModule);

        $this->_iEntryId = $iEntryId;
        if(!$this->_iEntryId && bx_get('id') !== false)
            $this->_iEntryId = bx_process_input(bx_get('id'), BX_DATA_INT);
    }
  
    protected function _serviceEntityForm ($sFormMethod, $iContentId = 0, $sDisplay = false, $sCheckFunction = false, $bErrorMsg = true)
    {
        $iContentId = $this->_getContent($iContentId, false);
        if($iContentId === false)
            return false;

        bx_import('FormsBroadcastHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsBroadcastHelper';
        $oFormsHelper = new $sClass($this);
        return $oFormsHelper->$sFormMethod((int)$iContentId, $sDisplay, $sCheckFunction, $bErrorMsg);
    }

    public function getObjectFormAdd ($sDisplay = false)
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;

        if (false === $sDisplay)
            $sDisplay = $CNF['OBJECT_FORM_BROADCAST_DISPLAY_ADD'];
        
        return BxDolForm::getObjectInstance($CNF['OBJECT_FORM_BROADCAST'], $sDisplay, $this->_oModule->_oTemplate);
    }
  
	public function addDataForm($sDisplay = false, $sCheckFunction = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if (!$sCheckFunction)
            $sCheckFunction = 'checkAllowedEdit';

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
 
        $oForm->initChecker();
 
        if (!$oForm->isSubmittedAndValid())
            return $oForm->getCode();

        // insert data into database
        $aValsToAdd = array ();
        $aValsToAdd['last_reply_timestamp'] = time();
        $aValsToAdd['last_reply_profile_id'] = bx_get_logged_profile_id();
  
        $iBroadcastId = $oForm->insert ($aValsToAdd);
        if (!$iBroadcastId) {
            if (!$oForm->isValid())
                return $oForm->getCode();
            else
                return MsgBox(_t('_sys_txt_error_entry_creation'));
        }

        $sResult = $this->onDataAddAfter(getLoggedId(), $iBroadcastId);
        if ($sResult)
            return $sResult;
 
        // redirect
        $this->_redirectAndExit('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $this->_iEntryId);
    }
  
    public function onDataAddAfter ($iAccountId, $iBroadcastId)
    { 
        if (!($aContentInfo = $this->_oModule->_oDb->getContentInfoById($this->_iEntryId)))
            return MsgBox(_t('_sys_txt_error_occured'));

        $CNF = $this->_oModule->_oConfig->CNF;

		$oCnvModule = BxDolModule::getInstance('bx_convos');  
        if (!$oCnvModule) return;
 
        $aConvoInfo = $oCnvModule->_oDb->getContentInfoById($iBroadcastId);
 
        // send notification to all collaborators
        $oProfile = BxDolProfile::getInstance($aConvoInfo[$oCnvModule->_oConfig->CNF['FIELD_AUTHOR']]);
 
		$iRecipient = (int)$aContentInfo[$CNF['FIELD_AUTHOR']];

		if($oCnvModule->checkAllowedContact($iRecipient) !== CHECK_ACTION_RESULT_ALLOWED)
			return;

		if ($iRecipient == $oProfile->id())
		   return;

		$oCnvModule->_oDb->conversationToFolder($iBroadcastId, BX_CNV_FOLDER_INBOX, $iRecipient, -1);

		sendMailTemplate('bx_cnv_new_message', 0, $iRecipient, array(
			'SenderDisplayName' => $oProfile->getDisplayName(),
			'SenderUrl' => $oProfile->getUrl(),
			'Message' => $aConvoInfo[$oCnvModule->_oConfig->CNF['FIELD_TEXT']],
			'PageUrl' => BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $this->_iEntryId),
			'PageTitle' => strmaxtextlen($aConvoInfo[$oCnvModule->_oConfig->CNF['FIELD_TEXT']], 100),
		), BX_EMAIL_NOTIFY);
	   
        return '';
    }
 
}

/** @} */
