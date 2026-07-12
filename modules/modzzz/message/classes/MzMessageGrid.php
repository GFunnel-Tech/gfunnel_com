<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) Modzzz
 * 
 * @defgroup    Profile Message Profile Message 
 * @ingroup     ModzzzModules
 *
 * @{
 */
  

bx_import('BxTemplGrid');

class MzMessageGrid extends BxTemplGrid
{
	var $_oModule;
	var $_sManageType;

    public function __construct ($aOptions, $oTemplate = false)
    {   
		$this->_oModule = BxDolModule::getInstance('mz_message'); 

    	$CNF = $this->_oModule->_oConfig->CNF;

	    if(!$oTemplate)
			$oTemplate = $this->_oModule->_oTemplate;	

        $this->_sFieldStatus = $CNF['FIELD_STATUS'];

        parent::__construct ($aOptions, $oTemplate);
 
        $this->_sManageType = 'message';
    }
   
    protected function _getDataSql($sFilter, $sOrderField, $sOrderDir, $iStart, $iPerPage)
    {
    	$CNF = $this->_oModule->_oConfig->CNF;

		$this->_aOptions['source'] .= $this->_oModule->_oDb->prepareAsString(" AND `author`=?", bx_get_logged_profile_id());
	 
        return $this->__getDataSql($sFilter, $sOrderField, $sOrderDir, $iStart, $iPerPage);
    }

    protected function __getDataSql($sFilter, $sOrderField, $sOrderDir, $iStart, $iPerPage)
    {
        return parent::_getDataSql($sFilter, $sOrderField, $sOrderDir, $iStart, $iPerPage);
    }
 
    protected function _getCellFilter($mixedValue, $sKey, $aField, $aRow)
    {  
        return parent::_getCellDefault(implode('<br />', $aRows), $sKey, $aField, $aRow);
    }
  
    protected function _getCellCat($mixedValue, $sKey, $aField, $aRow)
    {  
		$sValue = '';
    	$CNF = $this->_oModule->_oConfig->CNF;
 
		$oProfile = BxDolProfile::getInstanceMagic($aRow[$CNF['FIELD_AUTHOR']]);  
 
		if($oProfile){

			$aInfo = $oProfile->getInfo();  
			
			if($aInfo['type']=='bx_persons')  
				$aCat = BxDolFormQuery::getDataItems('mz_message_person_reason'); 

			if($aInfo['type']=='bx_organizations')  
				$aCat = BxDolFormQuery::getDataItems('mz_message_org_reason'); 
  
			$sValue = $aCat[$mixedValue];
		}

	    $sValue = ($sValue) ? $sValue : '';
	 
        return parent::_getCellDefault($sValue, $sKey, $aField, $aRow);
    }

    protected function _getCellText($mixedValue, $sKey, $aField, $aRow)
    {  
		$mixedValue = html_entity_decode($mixedValue);
		$mixedValue = strip_tags($mixedValue);

        if((int)$aField['chars_limit'] > 0)
            $mixedValue = strmaxtextlen($mixedValue, (int)$aField['chars_limit']);

        return parent::_getCellDefault($mixedValue, $sKey, $aField, $aRow);
    }

    protected function _getCellTitle($mixedValue, $sKey, $aField, $aRow)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $sTitle = $aRow[$CNF['FIELD_TITLE']];
        if((int)$aField['chars_limit'] > 0)
            $sTitle = strmaxtextlen($sTitle, (int)$aField['chars_limit']);

        if ($sTitle == '')
            $sTitle = _t('_sys_txt_no_title');
     
        return parent::_getCellDefault($this->_getEntryLink($sTitle, $aRow), $sKey, $aField, $aRow);
    } 

    protected function _getCellProfileId($mixedValue, $sKey, $aField, $aRow)
    {  
		$oProfile = BxDolProfile::getInstance($mixedValue);   
		if($oProfile == null) return;
 
		$aGroupInfo = $oProfile->getInfo($mixedValue);  
		if(empty($aGroupInfo)) return;
 
		$oGroup = BxDolModule::getInstance($aGroupInfo['type']); 
		if($oGroup == null) return;

 		$aGroupInfoContentInfo = $oGroup->_oDb->getContentInfoById($aGroupInfo['content_id']);
     
        return parent::_getCellDefault($this->_getGroupEntryLink($oGroup, $aGroupInfoContentInfo), $sKey, $aField, $aRow);
    }

    protected function _getGroupEntryLink($oGroup, $aRow)
    {
        $CNF = &$oGroup->_oConfig->CNF;

        $sUrl = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_MANAGE_ENTRY'] . '&id=' . $aRow[$CNF['FIELD_ID']]);

        return $this->_oTemplate->parseHtmlByName('title_link.html', array(
            'href' => $sUrl,
            'title' => bx_html_attribute($aRow[$CNF['FIELD_TITLE']]),
            'content' => bx_process_output($aRow[$CNF['FIELD_TITLE']])
        ));
    }
 
    protected function _getCellPublished($mixedValue, $sKey, $aField, $aRow)
    {
		$mixedValue = ($mixedValue==0) ? '-' : bx_time_js($mixedValue, BX_FORMAT_DATE_TIME);
        return parent::_getCellDefault($mixedValue, $sKey, $aField, $aRow);
    }

    protected function _getCellExpire($mixedValue, $sKey, $aField, $aRow)
    {
		$mixedValue = ($mixedValue==0) ? '-' : bx_time_js($mixedValue, BX_FORMAT_DATE_TIME);
        return parent::_getCellDefault($mixedValue, $sKey, $aField, $aRow);
    }

    protected function _getCellChanged($mixedValue, $sKey, $aField, $aRow)
    {
        return parent::_getCellDefault(bx_time_js($mixedValue), $sKey, $aField, $aRow);
    }
    
    protected function _getCellAuthor($mixedValue, $sKey, $aField, $aRow)
    {  
    	$oProfile = $this->_getProfileObject($aRow['author']);
    	$sProfile = $oProfile->getDisplayName();

        $oAcl = BxDolAcl::getInstance();

    	$sAccountEmail = '';
    	$sManageAccountUrl = '';
    	if($oProfile && $oProfile instanceof BxDolProfile && $oAcl->isMemberLevelInSet(128)) {
            $sAccountEmail = $oProfile->getAccountObject()->getEmail();
            $sManageAccountUrl = $this->_getManageAccountUrl($sAccountEmail);
    	}

        $sAddon = '';
        if(!empty($sManageAccountUrl))
            $sAddon = $this->_oTemplate->parseHtmlByName('account_link.html', array(
                'href' => $sManageAccountUrl,
                'title' => _t($this->_oModule->_oConfig->CNF['T']['grid_txt_account_manager']),
                'content' => $sAccountEmail,
                'class' => 'bx-def-font-grayed'
            ));

        $mixedValue = $oProfile->getUnit(0, array('template' => array('vars' => array('addon' => $sAddon))));

        return parent::_getCellDefault($mixedValue, $sKey, $aField, $aRow);
    }
 
    protected function _getProfileObject($iId)
    {
        return BxDolProfile::getInstanceMagic($iId);
    }

	protected function _getManageAccountUrl($sFilter = '')
    {
    	$sModuleAccounts = 'bx_accounts';
    	if(!BxDolModuleQuery::getInstance()->isEnabledByName($sModuleAccounts))
    		return '';

		$sTypeUpc = strtoupper($this->_sManageType);
		$oModuleAccounts = BxDolModule::getInstance($sModuleAccounts);
		if(!$oModuleAccounts || empty($oModuleAccounts->_oConfig->CNF['URL_MANAGE_' . $sTypeUpc]))
			return '';

		$sLink = $oModuleAccounts->_oConfig->CNF['URL_MANAGE_' . $sTypeUpc];

		$sLink = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink($sLink);
		
		if(!empty($sFilter))
			$sLink = bx_append_url_params($sLink, array('filter' => $sFilter));

		return $sLink;
    }
 
	public function _getActionView($sType, $sKey, $a, $isSmall = false, $isDisabled = false, $aRow = array())
    { 
    	$CNF = &$this->_oModule->_oConfig->CNF;

        $sUrl = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_MANAGE_ENTRY'] . '&id=' . $aRow[$CNF['FIELD_ID']]);

    	$a['attr'] = array_merge($a['attr'], array(
    		"onclick" => "window.open('" . $sUrl . "','_self');"
    	));

    	return $this->_getActionDefault ($sType, $sKey, $a, $isSmall, $isDisabled, $aRow);
    }
  
	public function performActionDelete($aParams = array())
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;

        $iAffected = 0;
        $aIds = bx_get('ids');
        if(!$aIds || !is_array($aIds)) {
            echoJson(array());
            exit;
        }

        $aIdsAffected = array ();
        foreach($aIds as $iId) {
			$aContentInfo = $this->_getContentInfo($iId);
	    	if($this->_oModule->checkAllowedDelete($aContentInfo) !== CHECK_ACTION_RESULT_ALLOWED)
	    		continue;

        	if(!$this->_doDelete($iId, $aParams))
                continue;
 
			$this->_oModule->checkAllowedDelete($aContentInfo, true);

            $aIdsAffected[] = $iId;
            $iAffected++;
        }

        echoJson($iAffected ? array('grid' => $this->getCode(false), 'blink' => $aIdsAffected) : array('msg' => _t($CNF['T']['grid_action_err_delete'])));
    }

	protected function _getContentInfo($iId)
    {
    	return $this->_oModule->_oDb->getContentInfoById($iId);
    }
    
    protected function _getEntryLink($mixedValue, $aRow)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $sUrl = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_MANAGE_ENTRY'] . '&id=' . $aRow[$CNF['FIELD_ID']]);

        return $this->_oTemplate->parseHtmlByName('title_link.html', array(
            'href' => $sUrl,
            'title' => bx_html_attribute($mixedValue),
            'content' => bx_process_output($mixedValue)
        ));
    }

    protected function _doDelete($iId, $aParams = array())
    {
    	return $this->_oModule->serviceDeleteEntity($iId) == '';
    }

    protected function _onDelete($iId, $aParams = array())
    {
    	return true;
    }
  
    protected function _redirectAndExit ($sUrl, $isPermalink = true, $aMarkers = false)
    {
        if ($isPermalink)
            $sUrl = BxDolPermalinks::getInstance()->permalink($sUrl);

        if(strncmp($sUrl, BX_DOL_URL_ROOT, strlen(BX_DOL_URL_ROOT)) !== 0)
            $sUrl = BX_DOL_URL_ROOT . $sUrl;

        header('Location: ' . bx_replace_markers($sUrl, $aMarkers));
        exit;
    }


 
}

/** @} */
