<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) Modzzz
 * 
 * @defgroup    Profile Message Profile Message 
 * @ingroup     ModzzzModules
 *
 * @{
 */

/**
 * Entry forms helper functions
 */
class MzMessageFormsEntryHelper extends BxBaseModTextFormsEntryHelper
{
    public function __construct($oModule)
    {
        parent::__construct($oModule);
    }
 
     /**
     * Delete data entry
     * @param $iContentId entry id
     * @param $oForm optional content info array
     * @param $aContentInfo optional content info array
     * @param $oProfile optional content author profile
     * @return error string on error or empty string on success
     */
    public function deleteData ($iContentId, $aContentInfo = false, $oProfile = null, $oForm = null)
    { 
        $CNF = &$this->_oModule->_oConfig->CNF;

        if (!$aContentInfo || !$oProfile)
            list ($oProfile, $aContentInfo) = $this->_getProfileAndContentData($iContentId);
 

        if (!$aContentInfo)
            return _t('_sys_txt_error_entry_is_not_defined');

        if (!$oForm)
            $oForm = $this->getObjectFormDelete();
 
        if (!$oForm->delete ($aContentInfo[$CNF['FIELD_ID']], $aContentInfo))
            return _t('_sys_txt_error_entry_delete');
 
 
        if ($sResult = $this->onDataDeleteAfter ($aContentInfo[$CNF['FIELD_ID']], $aContentInfo, $oProfile))
            return $sResult;

        // create an alert
        bx_alert($this->_oModule->getName(), 'deleted', $aContentInfo[$CNF['FIELD_ID']], false, array(
            'content' => &$aContentInfo
        ));

        return '';
    }

    public function onDataDeleteAfter ($iContentId, $aContentInfo, $oProfile)
    {
 
        $CNF = &$this->_oModule->_oConfig->CNF;

        //if(($oPrivacy = BxDolPrivacy::getObjectInstance($CNF['OBJECT_PRIVACY_VIEW'])) !== false)
        //    $oPrivacy->deleteGroupCustomByContentId($iContentId);

        bx_audit(
            $iContentId, 
            $this->_oModule->getName(), 
            '_sys_audit_action_detete_content',  
            $this->_oModule->_prepareAuditParams($aContentInfo)
        );
        
        return '';
    }

    protected function redirectAfterEdit($aContentInfo, $sUrl = '')
    {
		$iProfileId = bx_get_logged_profile_id(); 
		$oProfile = BxDolProfile::getInstance($iProfileId); 
        $this->_redirectAndExit($oProfile->getUrl());
    }

    public function redirectAfterAdd($aContentInfo, $sUrl = '')
    {
		$iProfileId = bx_get_logged_profile_id(); 
		$oProfile = BxDolProfile::getInstance($iProfileId); 
        $this->_redirectAndExit($oProfile->getUrl());
    }
  

}

/** @} */
