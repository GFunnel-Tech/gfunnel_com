<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Jobs Jobs
 * @ingroup     ModzzzModules
 *
 * @{
 */

/**
 * Entry forms helper functions
 */
class MzJobsFormsEntryHelper extends BxBaseModTextFormsEntryHelper
{
    public function __construct($oModule)
    {
        parent::__construct($oModule);
    }

    public function getObjectFormAdd($sDisplay = false)
    {
        if(($sCategoryDisplay = $this->_oModule->getCategoryDisplay('add')) !== false)
            $sDisplay = $sCategoryDisplay;

        return parent::getObjectFormAdd($sDisplay);
    }
 
    public function viewDataForm ($iContentId, $sDisplay = false)
    {
         $sFormCode = parent::viewDataForm($iContentId, $sDisplay);
 
		 return empty(trim(strip_tags($sFormCode))) ? '' : $sFormCode;
    }

    public function viewDataFormOLD ($iContentId, $sDisplay = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        // get content data and profile info
        list ($oProfile, $aContentInfo) = $this->_getProfileAndContentData($iContentId);
        if (!$aContentInfo)
            return MsgBox(_t('_sys_txt_error_entry_is_not_defined'));

        // check access
        if ($sMsg = $this->_processPermissionsCheckForViewDataForm ($aContentInfo, $oProfile))
            return MsgBox($sMsg);

        // get form
        $oForm = $this->getObjectFormView($sDisplay);
        if (!$oForm)
            return MsgBox(_t('_sys_txt_error_occured'));

        // process metatags
        if (!empty($CNF['OBJECT_METATAGS'])) {
            $oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS']);
            if ($oMetatags->keywordsIsEnabled()) {
                $aFields = $oMetatags->metaFields($aContentInfo, $CNF, $CNF['OBJECT_FORM_ENTRY_DISPLAY_VIEW']);
                $oForm->setMetatagsKeywordsData($iContentId, $aFields, $oMetatags);
            }
        }        

        // display profile
        $oForm->initChecker($aContentInfo);
        $sCode = $oForm->getCode();

		$sCodeChecker = trim(strip_tags($sCode));

		if(empty($sCodeChecker))
			return '';
		else
			return $sCode;
    }

    public function viewDataEntry ($iContentId)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $mixedResult = parent::viewDataEntry ($iContentId);
        if(!empty($mixedResult))
            return $mixedResult;

        $aContentInfo = $this->_oModule->_oDb->getContentInfoById($iContentId);

        $sDisplay = false;
        if(!empty($aContentInfo[$CNF['FIELD_CATEGORY']]) && ($sCategoryDisplay = $this->_oModule->getCategoryDisplay('view', $aContentInfo[$CNF['FIELD_CATEGORY']])) !== false)
            $sDisplay = $sCategoryDisplay;
 
        $oForm = $this->getObjectFormView($sDisplay);
        if(!$oForm)
            return '';

        $oForm->initChecker($aContentInfo);
 
        if(!empty($CNF['FIELD_TEXT']) &&  !$oForm->isInputVisible($CNF['FIELD_TEXT']))
            return '';

        return $this->_oModule->_oTemplate->entryText($aContentInfo);
    }

	//begin modified UNA 13 beta
    public function onDataAddAfter($iAccountId, $iContentId)
    {
        $s = parent::onDataAddAfter($iAccountId, $iContentId);
        if(!empty($s))
            return $s;
 
        $this->_oModule->serviceUpdateCategoriesStats($iContentId);

        $aContentInfo = $this->_oModule->_oDb->getContentInfoById($iContentId);

        $aCategory = $this->_oModule->_oDb->getCategories(array('type' => 'id', 'id' => $aContentInfo['category']));

		if($aCategory['type']==2)
			$this->_oModule->_oDb->updateJobType($iContentId, 'wanted');

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

        $this->_oModule->serviceUpdateCategoriesStats($aContentInfo);

        return '';
    }
  	//end modified UNA 13 beta
 
}

/** @} */
