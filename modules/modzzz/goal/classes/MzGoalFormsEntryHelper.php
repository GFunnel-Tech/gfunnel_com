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
 * Entry forms helper functions
 */
class MzGoalFormsEntryHelper extends BxBaseModTextFormsEntryHelper
{
    public function __construct($oModule)
    {
        parent::__construct($oModule);
    }
 

    public function getObjectFormAdd($sDisplay = false)
    {
        //if(($sCategoryDisplay = $this->_oModule->getCategoryDisplay('add')) !== false)
         //   $sDisplay = $sCategoryDisplay;

        return parent::getObjectFormAdd($sDisplay);
    }


	//begin modified UNA 13 beta
    public function onDataAddAfter($iAccountId, $iContentId)
    {
        $s = parent::onDataAddAfter($iAccountId, $iContentId);
        if(!empty($s))
            return $s;

        $this->_oModule->serviceUpdateCategoriesStats($iContentId);

        return '';
    }

    public function onDataEditAfter($iContentId, $aContentInfo, $aTrackTextFieldsChanges, $oProfile, $oForm)
    {
        $s = parent::onDataEditAfter($iContentId, $aContentInfo, $aTrackTextFieldsChanges, $oProfile, $oForm);
        if(!empty($s))
            return $s;

        $this->_oModule->serviceUpdateCategoriesStats($aContentInfo);

        return '';
    }

    public function onDataDeleteAfter ($iContentId, $aContentInfo, $oProfile)
    {
        $s = parent::onDataDeleteAfter ($iContentId, $aContentInfo, $oProfile);
        if(!empty($s))
            return $s;
 
		$aSubEntry = array('Sponsor','Task','News');
		foreach($aSubEntry as $sSubEntry)
		{
			bx_import('Forms'.$sSubEntry.'Helper', $this->_oModule->_aModule);
			$sClass = $this->_oModule->_aModule['class_prefix'] . 'Forms'.$sSubEntry.'Helper';
			$oFormsHelper = new $sClass($this->_oModule);
			$oFormsHelper->deleteAllData((int)$iContentId); 
		}
 
        $this->_oModule->_oDb->removeInterested(array('entry_id' => $iContentId));

        $this->_oModule->serviceUpdateCategoriesStats($aContentInfo);

        return '';
    }
 	//begin modified UNA 13 beta
  
    public function viewDataForm ($iContentId, $sDisplay = false)
    {
         $sFormCode = parent::viewDataForm($iContentId, $sDisplay);
 
		 return empty(trim(strip_tags($sFormCode))) ? '' : $sFormCode;
    }


}

/** @} */
