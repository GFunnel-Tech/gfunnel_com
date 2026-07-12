<?php defined('BX_DOL') or die('hack attempt');
/**
 *     copyright            : (C) 2020 AQB Soft
 *     website              : http://www.aqbsoft.com
 *
 * IMPORTANT: This is a commercial product made by AQB Soft. It cannot be modified for other than personal usage.
 * The "personal usage" means the product can be installed and set up for ONE domain name ONLY.
 * To be able to use this product for another domain names you have to order another copy of this product (license).
 *
 * This product cannot be redistributed for free or a fee without written permission from AQB Soft.
 *
 * This notice may not be removed from the source code.
 *
 * @defgroup    Listing Listing
 * @ingroup     ModzzzModules
 *
 * @{
 */

bx_import('BxTemplGrid');

class MzListingClaimGrid extends BxTemplGrid
{
	var $_oModule;
	var $_sManageType;

    public function __construct ($aOptions, $oTemplate = false)
    {   
		$this->_oModule = BxDolModule::getInstance('mz_listing'); 

    	$CNF = $this->_oModule->_oConfig->CNF;

	    if(!$oTemplate)
			$oTemplate = $this->_oModule->_oTemplate;	

        $this->_sFieldStatus = $CNF['FIELD_STATUS'];

        parent::__construct ($aOptions, $oTemplate);
 
        $this->_sManageType = 'claim';
    }
 
    protected function _getDataSql($sFilter, $sOrderField, $sOrderDir, $iStart, $iPerPage)
    {
    	$CNF = $this->_oModule->_oConfig->CNF;

/*
    	if(!empty($this->_sFilter1Value))
        	$this->_aOptions['source'] .= $this->_oModule->_oDb->prepareAsString(" AND `mz_listing_claims`.`" . $this->_sFieldStatus . "`=?", $this->_sFilter1Value);
		else
			$this->_aOptions['source'] .= $this->_oModule->_oDb->prepareAsString(" AND `mz_listing_claims`.`" . $this->_sFieldStatus . "`=?", 'pending');
*/	

        return $this->__getDataSql($sFilter, $sOrderField, $sOrderDir, $iStart, $iPerPage);
    }

    protected function __getDataSql($sFilter, $sOrderField, $sOrderDir, $iStart, $iPerPage)
    {
        return parent::_getDataSql($sFilter, $sOrderField, $sOrderDir, $iStart, $iPerPage);
    }

    protected function _getCellFilter($mixedValue, $sKey, $aField, $aRow)
    {   /*
        $aRows = array();
        if ($aRow['memlevel']) {
            $oACL = BxDolAcl::getInstance();
            $aLevels = $oACL->getMemberships();
            $aRows[] = _t('_mz_flower_form_filter_memlevel').'&nbsp;<strong>'.$aLevels[$aRow['memlevel']].'</strong>';
        }
  */
        return parent::_getCellDefault(implode('<br />', $aRows), $sKey, $aField, $aRow);
    }
  
    protected function _getEntryClaimLink($mixedValue, $aRow)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $sUrl = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_CLAIM'] . '&id=' . $aRow[$CNF['FIELD_ID']]);
 
        return $this->_oTemplate->parseHtmlByName('title_link.html', array(
            'href' => $sUrl,
            'title' => bx_html_attribute($mixedValue),
            'content' => bx_process_output($mixedValue)
        )); 
    }

    protected function _getCellText($mixedValue, $sKey, $aField, $aRow)
    {  
		$sText = bx_html_attribute($mixedValue);
        return parent::_getCellDefault($sText, $sKey, $aField, $aRow);
    }

    protected function _getCellTitle($mixedValue, $sKey, $aField, $aRow)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $sTitle = $aRow[$CNF['FIELD_TITLE']];
        if((int)$aField['chars_limit'] > 0)
            $sTitle = strmaxtextlen($sTitle, (int)$aField['chars_limit']);

        if ($sTitle == '')
            $sTitle = _t('_sys_txt_no_title');
     
        return parent::_getCellDefault($this->_getEntryClaimLink($sTitle, $aRow), $sKey, $aField, $aRow);
    } 

	protected function _getCellBusiness($mixedValue, $sKey, $aField, $aRow)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

		$aListing = $this->_oModule->_oDb->getContentInfoById($aRow['entry_id']);
        $sTitle = $aListing[$CNF['FIELD_TITLE']];
        if((int)$aField['chars_limit'] > 0)
            $sTitle = strmaxtextlen($sTitle, (int)$aField['chars_limit']);

        if ($sTitle == '')
            $sTitle = _t('_sys_txt_no_title');
        
        return parent::_getCellDefault($this->_getEntryLink($sTitle, $aRow), $sKey, $aField, $aRow);
    }

    protected function _getEntryLink($mixedValue, $aRow)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $sUrl = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aRow[$CNF['FIELD_LISTING_ID']]);

        return $this->_oTemplate->parseHtmlByName('title_link.html', array(
            'href' => $sUrl,
            'title' => bx_html_attribute($mixedValue),
            'content' => bx_process_output($mixedValue)
        ));
    }
 
    protected function _getCellAdded($mixedValue, $sKey, $aField, $aRow)
    {
        return parent::_getCellDefault(bx_time_js($mixedValue), $sKey, $aField, $aRow);
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
 
 	public function performActionClaimReject($aParams = array())
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
 
			$this->_oModule->processClaim(array('id'=>$iId, 'status'=>'rejected'));
 
            $aIdsAffected[] = $iId;
            $iAffected++;
        }

        echoJson($iAffected ? array('grid' => $this->getCode(false), 'blink' => $aIdsAffected) : array('msg' => _t($CNF['T']['grid_action_err_claim'])));
    }

	public function performActionClaimAccept($aParams = array())
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
 
			$this->_oModule->processClaim(array('id'=>$iId, 'status'=>'accepted'));
 
            $aIdsAffected[] = $iId;
            $iAffected++;
        }

        echoJson($iAffected ? array('grid' => $this->getCode(false), 'blink' => $aIdsAffected) : array('msg' => _t($CNF['T']['grid_action_err_claim'])));
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
			$aContentInfo = $this->_oModule->_oDb->getClaimInfoById($iId);

			//if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->checkAllowedManageClaim($aContentInfo)))  
	    	//	continue;

        	if(!$this->_doDelete($iId, $aParams))
                continue;

			if(!$this->_onDelete($iId, $aParams))
				continue;
 
            $aIdsAffected[] = $iId;
            $iAffected++;
        }

        echoJson($iAffected ? array('grid' => $this->getCode(false), 'blink' => $aIdsAffected) : array('msg' => _t($CNF['T']['grid_action_err_delete'])));
    }

    protected function _doDelete($iId, $aParams = array())
    {
    	return $this->_oModule->serviceDeleteClaim($iId) == '';
    }

    protected function _onDelete($iId, $aParams = array())
    {
    	return true;
    }




 
}

/** @} */
