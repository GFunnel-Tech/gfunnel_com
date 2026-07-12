<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) Modzzz
 * 
 * @defgroup    Profile Message Profile Message 
 * @ingroup     ModzzzModules
 *
 * @{
 */
 
bx_import('BxDolProfile');

class MzMessageModule extends BxBaseModTextModule 
{
    function __construct(&$aModule) 
    {
        parent::__construct($aModule); 
    }

	function checkAllowedSetThumb($iContentId = 0)
	{
		return false; 
	}

	function checkAllowedView($aDataEntry, $isPerformAction = false)
	{
 		return (CHECK_ACTION_RESULT_ALLOWED === ($sMsg = $this->serviceCheckAllowedViewForProfile($aDataEntry))); 
	}
   
	function serviceIsMessageAvailable($iProfileId = 0)
    { 
		return true;

		if ($iProfileId !== $this->_iProfileId) return false;
 
		$oProfile = BxDolProfile::getInstanceMagic($iProfileId);  

		if(!$oProfile) 
			return false;

		$aInfo = $oProfile->getInfo();  
		
		if(!in_array($aInfo['type'],array('bx_persons','bx_organizations')))  
			return false;

		return (CHECK_ACTION_RESULT_ALLOWED === ($sMsg = $this->checkAllowedAdd())); 
	}
    
    /**
     * @return CHECK_ACTION_RESULT_ALLOWED if access is granted or error message if access is forbidden. So make sure to make strict(===) checking.
     */
    public function checkAllowedEditAnyEntryForProfile ($isPerformAction = false, $iProfileId = false)
    {
		return (CHECK_ACTION_RESULT_ALLOWED === ($sMsg = $this->checkAllowedAdd())); 
    }

	function serviceShowMessage($iProfileId) {
 
		$aReason = array();

		$this->_oTemplate->addCss(array('main.css'));
 
        $aProfile = $this->_oDb->getInfoByProfileId($iProfileId, true);
 
		if(empty($aProfile)) return;
			
  		if(!$this->checkAllowedView($aProfile)) return;

		$sMessage = trim($aProfile['text']);
		$iReasonId = $aProfile['cat'];

		if(!$sMessage) return;
   
		$sCaption = $aProfile['title'];
  
		$oProfile = BxDolProfile::getInstanceMagic($iProfileId);  

		if(!$oProfile) return;

		$aInfo = $oProfile->getInfo();  
		
		if($aInfo['type']=='bx_persons')  
			$aReason = BxDolFormQuery::getDataItems('mz_message_person_reason'); 

		if($aInfo['type']=='bx_organizations')  
			$aReason = BxDolFormQuery::getDataItems('mz_message_org_reason'); 

		if(empty($aReason)) return;

		$sReason = $aReason[$iReasonId];
  
		$sCaption = ($iReasonId) ? $sReason .' - '. $sCaption : $sCaption;
 		 
		$aBlock = array();
		$aBlock['title'] = $this->_oTemplate->parseHtmlByName('message_title.html', array('content' => $sCaption));
  		$aBlock['content'] = $this->_oTemplate->parseHtmlByName('message_body.html', array('content' => $sMessage));
 
		return $aBlock; 
    }

    /**
     * Create entry form
     * @return HTML string
     */
    public function serviceEntityCreate ($sParams = false)
    {
        $CNF = &$this->_oConfig->CNF;
 
		$iEntryId = bx_get('id');
 
		$aContentInfo = $this->_oDb->getContentInfoById($iEntryId);
   
		if(!empty($aContentInfo)){
			return $this->_serviceEntityForm ('editDataForm', $aContentInfo[$CNF['FIELD_ID']], false); 
		} 
 
        $bParamsArray = is_array($sParams);

        $sDisplay = is_string($sParams) ? $sParams : false;
        if($bParamsArray && !empty($sParams['display']))
            $sDisplay = $sParams['display'];

        bx_import('FormsEntryHelper', $this->_aModule);
        $sClass = $this->_aModule['class_prefix'] . 'FormsEntryHelper';
        $oFormsHelper = new $sClass($this);
        if($bParamsArray && isset($sParams['dynamic_mode']))
            $oFormsHelper->setDynamicMode($sParams['dynamic_mode']);

        $mixedResult = $oFormsHelper->addDataForm($sDisplay);
        if(isset($mixedResult['_dt']) && $mixedResult['_dt'] == 'json') {
            echoJson($mixedResult);
            exit;
        }

        return $mixedResult; 
    }
    
    function serviceManageMessages($sGridObject, $sJSGridObject)
    {    
        $this->_oTemplate->addCss(array('manage_tools.css'));
        $this->_oTemplate->addJs(array('manage_tools.js'));
  
        $this->_oTemplate->addJsTranslation(array('_sys_grid_search'));
 
        $oGrid = BxDolGrid::getObjectInstance($sGridObject);
        if (!$oGrid) return;

        return $this->_oTemplate->getJsCode('manage_tools', array('sObjNameGrid' => $this->_oConfig->getGridObject($sJSGridObject))) . $oGrid->getCode(); 
    }


}

/** @} */
