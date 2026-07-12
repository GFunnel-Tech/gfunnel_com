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
 * Create/Edit entry form
 */
class MzMessageFormsEntry extends BxBaseModTextFormEntry
{
     protected $_bAllowChangeUserForAdmins;

    public function __construct($aInfo, $oTemplate = false)
    {
        $this->MODULE = 'mz_message';
		$this->_bAllowChangeUserForAdmins = false;
 
        parent::__construct($aInfo, $oTemplate);
 
        $CNF = &$this->_oModule->_oConfig->CNF; 
    }

    function initChecker ($aValues = array (), $aSpecificValues = array())
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $bValues = $aValues && !empty($aValues['id']);
        $aContentInfo = $bValues ? $this->_oModule->_oDb->getContentInfoById($aValues['id']) : false;

        //if($this->aParams['display'] == $CNF['OBJECT_FORM_ENTRY_DISPLAY_EDIT'] && isset($CNF['FIELD_PUBLISHED']) && isset($this->aInputs[$CNF['FIELD_PUBLISHED']]))
            //if($bValues && in_array($aValues[$CNF['FIELD_STATUS']], array('active', 'hidden')))
                //unset($this->aInputs[$CNF['FIELD_PUBLISHED']]);
  
		$oProfile = BxDolProfile::getInstanceMagic(bx_get_logged_profile_id());    
		$aInfo = $oProfile->getInfo();  
		
		if($aInfo['type']=='bx_persons')  
			$this->aInputs[$CNF['FIELD_CATEGORY']]['values'] = BxDolFormQuery::getDataItems('mz_message_person_reason'); 

		if($aInfo['type']=='bx_organizations')  
			$this->aInputs[$CNF['FIELD_CATEGORY']]['values'] = BxDolFormQuery::getDataItems('mz_message_org_reason'); 
 
        parent::initChecker ($aValues, $aSpecificValues);
    }
 
    public function insert ($aValsToAdd = array(), $isIgnore = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
  
        if(isset($CNF['FIELD_ADDED']) && empty($aValsToAdd[$CNF['FIELD_ADDED']])) {
            $iAdded = 0;
            if(isset($this->aInputs[$CNF['FIELD_ADDED']]))
                $iAdded = $this->getCleanValue($CNF['FIELD_ADDED']);
 
            if(empty($iAdded))
                 $iAdded = time();

            $aValsToAdd[$CNF['FIELD_ADDED']] = $iAdded;
        }

        if(empty($aValsToAdd[$CNF['FIELD_PUBLISHED']])) {
            $iPublished = 0;
            if(isset($this->aInputs[$CNF['FIELD_PUBLISHED']]))
                $iPublished = $this->getCleanValue($CNF['FIELD_PUBLISHED']);
                
             if(empty($iPublished))
                 $iPublished = time();

             $aValsToAdd[$CNF['FIELD_PUBLISHED']] = $iPublished;
        }

        $aValsToAdd[$CNF['FIELD_STATUS']] = $aValsToAdd[$CNF['FIELD_PUBLISHED']] > $aValsToAdd[$CNF['FIELD_ADDED']] ? 'awaiting' : 'active';

        $iContentId = parent::insert ($aValsToAdd, $isIgnore);
 
        return $iContentId;
    }

    function update ($iContentId, $aValsToAdd = array(), &$aTrackTextFieldsChanges = null)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if(empty($aValsToAdd[$CNF['FIELD_PUBLISHED']]) && isset($this->aInputs[$CNF['FIELD_PUBLISHED']])) {
            $iPublished = $this->getCleanValue($CNF['FIELD_PUBLISHED']);
            if(empty($iPublished))
                $iPublished = time();

            $aValsToAdd[$CNF['FIELD_PUBLISHED']] = $iPublished;
        }
        
        $iResult = parent::update ($iContentId, $aValsToAdd, $aTrackTextFieldsChanges);
        return $iResult;
    }
 
}

/** @} */
