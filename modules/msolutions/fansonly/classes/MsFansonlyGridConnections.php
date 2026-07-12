<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) MSolutions
 * 
 * @defgroup    Fansonly Fansonly module
 * @ingroup     MSolutionsModules
 *
 * @{
 */ 

class MsFansonlyGridConnections extends BxBaseGrid//BxDolGridConnections
{
    protected $_oModule;
    protected $_sContentModule;

    protected $_bSubcriptions;
    protected $_aSubcriptions;

    protected $_iUserProfileId;
    protected $_aContentInfo = array();

    protected $_bPaidJoin;
    protected $_bMember;
    protected $_bManageMembers;
    protected $_oConnection;

    public function __construct ($aOptions, $oTemplate = false)
    {
        $this->_sContentModule = 'ms_fansonly';
        $this->_oModule = BxDolModule::getInstance($this->_sContentModule);

        $CNF = &$this->_oModule->_oConfig->CNF;

        $this->_sObjectConnections = $CNF['OBJECT_CONNECTIONS'];

        
        $this->_oConnection = BxDolConnection::getObjectInstance($CNF['OBJECT_CONNECTIONS']);

        parent::__construct ($aOptions, $oTemplate);

        $this->_bSubcriptions = false;
        $this->_aSubcriptions = [];

        $this->_iUserProfileId =  bx_get_logged_profile_id();


        $oUserProfile = BxDolProfile::getInstance($this->_iUserProfileId);
        $aUserProfileInfo = $oUserProfile->getInfo();

        $this->_aContentInfo =  $aUserProfileInfo;

        if($this->_oModule->checkAllowedEdit($this->_aContentInfo) === CHECK_ACTION_RESULT_ALLOWED || $this->_iUserProfileId == bx_get_logged_profile_id())
            $this->_bOwner = true;

        $this->_bPaidJoin = $this->_oModule->isPaidJoinByProfile($this->_iUserProfileId);

        $this->_bMember = false;
        $this->_bManageMembers = $this->_oModule->checkAllowedManageFans($this->_iUserProfileId) === CHECK_ACTION_RESULT_ALLOWED || $this->_oModule->checkAllowedManageAdmins($this->_iUserProfileId) === CHECK_ACTION_RESULT_ALLOWED;

        $this->_aQueryAppendExclude[] = 'join_connections';

        $this->addMarkers(array(
            'profile_id' => $this->_iUserProfileId,
            'join_connections' => '',
            'content_module' => $this->_sContentModule,
        ));
        
    }

    public function getCode ($isDisplayHeader = true)
    {
        
//        $sResult = parent::getCode($isDisplayHeader);
        $sResult = parent::getCode(true);
        if(!$sResult)
            return $sResult;
        
        if($this->_bSubcriptions)
            $sResult .= $this->_oModule->_oTemplate->getJsCode('main', array(
                'sObjNameGrid' => $this->_sObject,
            ));

        return $sResult;
    }

    public function performActionSetSubscription()
    {
        if(!$this->_bSubcriptions)
            return echoJson(array());

        if(($mixedResult = $this->_oModule->checkAllowedManageAdmins($this->_iUserProfileId)) !== CHECK_ACTION_RESULT_ALLOWED)
            return echoJson(array('msg' => $mixedResult));

        list($iId, $iViewedId) = $this->_prepareIds();
        if(!$iId)
            return echoJson(array('msg' => _t('_sys_txt_error_occured')));

        if(empty($this->_aSubcriptions) || !is_array($this->_aSubcriptions))
            return echoJson(array('msg' => _t('_sys_txt_error_occured')));

        $iSubscription = $this->_oModule->_oDb->getSubscription($this->_iUserProfileId, $iId);

        if(!$this->_oModule->_oConfig->isMultiSubcriptions()) {
            $sJsObject = $this->_oModule->_oConfig->getJsObject('main');
            $sHtmlIdPrefix = str_replace('_', '-', $this->_sContentModule) . '-set-subscription-';

            $aMenuItems = array();
            foreach($this->_aSubcriptions as $iSubscriptionId => $sSubscriptionTitle)
                $aMenuItems[] = array(
                    'id' => $sHtmlIdPrefix . $iSubscriptionId, 
                    'name' => $sHtmlIdPrefix . $iSubscriptionId, 
                    'class' => '', 
                    'link' => 'javascript:void(0)', 
                    'onclick' => $sJsObject . '.onClickSetSubscription(' . $iId . ', ' . $iSubscriptionId . ')',
                    'target' => '_self', 
                    'title' => $sSubscriptionTitle, 
                    'active' => 1
                );            

            $oMenu = new BxTemplMenu(array('template' => 'menu_vertical.html', 'menu_id'=> $sHtmlIdPrefix . 'menu', 'menu_items' => $aMenuItems));
            if(!empty($iSubscription))
                $oMenu->setSelected('', $sHtmlIdPrefix . $iSubscription);

            $sPopupContent = $oMenu->getCode();
        }
        else
            $sPopupContent = $this->_oModule->_oTemplate->getPopupSetSubscription($this->_aSubcriptions, $iId, $iSubscription);

        return echoJson(array('popup' => BxTemplFunctions::getInstance()->transBox(str_replace('_', '-', $this->_sContentModule) . '-set-subscription-popup', $sPopupContent)));
    }

    public function performActionSetSubscriptionSubmit()
    {
        if(!$this->_bSubcriptions)
            return echoJson(array());

        if(($mixedResult = $this->_oModule->checkAllowedManageAdmins($this->_iUserProfileId)) !== CHECK_ACTION_RESULT_ALLOWED)
            return echoJson(array('msg' => $mixedResult));

        list($iId, $iViewedId) = $this->_prepareIds();

        if(!$iId)
            return echoJson(array('msg' => _t('_sys_txt_error_occured')));

        if(!$this->_oModule->setSubscription($this->_iUserProfileId, $iId, bx_process_input(bx_get('subscription'), BX_DATA_INT)))
            return echoJson(array('msg' => _t('_error occured')));

        echoJson(array('grid' => $this->getCode(false), 'blink' => $iId));
    }

    protected function _getCellSubscription($mixedValue, $sKey, $aField, $aRow)
    {
        $iProfileSubscription = $this->_oModule->_oDb->getSubscription($this->_iUserProfileId, $aRow[$this->_aOptions['field_id']]);

        if(!empty($iProfileSubscription) && $this->_oModule->_oConfig->isMultiSubcriptions()) {
            $aSubcriptions = array();
            foreach($this->_aSubcriptions as $iSubscription => $sSubscription) {
                if(!$iSubscription)
                    continue;

                if($iProfileSubscription & (1 << ($iSubscription - 1)))
                    $aSubcriptions[] = $sSubscription;
            }

            $mixedValue = implode(', ', $aSubcriptions);
        }
        else 
            $mixedValue = !empty($this->_aSubcriptions[$iProfileSubscription]) ? $this->_aSubcriptions[$iProfileSubscription] : _t('_uknown');

        return parent::_getCellDefault($mixedValue, $sKey, $aField, $aRow);
    }
    protected function _getCellProfileId($mixedValue, $sKey, $aField, $aRow)
    {
        $mixedValue = $this->_oModule->getAuthorName($mixedValue ); //, $oModule);
        
        return parent::_getCellDefault($mixedValue, $sKey, $aField, $aRow);
    }
    protected function _getCellFanId($mixedValue, $sKey, $aField, $aRow)
    {
        $mixedValue = $this->_oModule->getAuthorName($mixedValue ); //, $oModule);
        
        return parent::_getCellDefault($mixedValue, $sKey, $aField, $aRow);
    }
    protected function _getCellInitiator($mixedValue, $sKey, $aField, $aRow)
    {
        $mixedValue = $this->_oModule->getAuthorName($mixedValue ); //, $oModule);
        
        return parent::_getCellDefault($mixedValue, $sKey, $aField, $aRow);
    }
    protected function _getCellContent($mixedValue, $sKey, $aField, $aRow)
    {
        $mixedValue = $this->_oModule->getAuthorName($mixedValue ); //, $oModule);
        
        return parent::_getCellDefault($mixedValue, $sKey, $aField, $aRow);
    }
    
    protected function _getCellExpiration($mixedValue, $sKey, $aField, $aRow)
    {
        $mixedValue = $aRow["expired"];
        
        $mixedValue = bx_time_js($mixedValue);

        return parent::_getCellDefault($mixedValue, $sKey, $aField, $aRow);
    }

    protected function _getCellHeaderSubscriptionAdded($sKey, $aField)
    {
        if(!$this->_bPaidJoin || !($this->_bMember || $this->_bManageMembers))
            return '';

        return parent::_getCellHeaderDefault ($sKey, $aField);
    }

    protected function _getCellSubscriptionAdded($mixedValue, $sKey, $aField, $aRow)
    {
        $mixedValue = '';

        if(!$this->_bPaidJoin || !($this->_bMember || $this->_bManageMembers))
            return $mixedValue;

        $iProfileId = (int)$aRow[$this->_aOptions['field_id']];
        if($this->_bManageMembers || $iProfileId == bx_get_logged_profile_id()) {
            $aSubscription = $this->_oModule->_oDb->getSubcriptions(array('type' => 'by_pf_id', 'profile_id' => $this->_iUserProfileId, 'fan_id' => $iProfileId));
            if(!empty($aSubscription) && is_array($aSubscription))
                $mixedValue = bx_time_js($aSubscription['added'], BX_FORMAT_DATE, true);
        }

        return parent::_getCellDefault($mixedValue, $sKey, $aField, $aRow);
    }

    protected function _getCellHeaderSubscriptionExpired($sKey, $aField)
    {
        if(!$this->_bPaidJoin || !($this->_bMember || $this->_bManageMembers))
            return '';
        
        return parent::_getCellHeaderDefault ($sKey, $aField);
    }

    protected function _getCellSubscriptionExpired($mixedValue, $sKey, $aField, $aRow)
    {
        $mixedValue = '';

        if(!$this->_bPaidJoin || !($this->_bMember || $this->_bManageMembers))
            return '';

        $iProfileId = (int)$aRow[$this->_aOptions['field_id']];
        if($this->_bManageMembers || $iProfileId == bx_get_logged_profile_id()) {
            $aSubscription = $this->_oModule->_oDb->getSubcriptions(array('type' => 'by_pf_id', 'profile_id' => $this->_iUserProfileId, 'fan_id' => $iProfileId));
            if(!empty($aSubscription) && is_array($aSubscription))
                $mixedValue = bx_time_js($aSubscription['expired'], BX_FORMAT_DATE, true);
        }

        return parent::_getCellDefault($mixedValue, $sKey, $aField, $aRow);
    }

    protected function _getActionSetSubscription ($sType, $sKey, $a, $isSmall = false, $isDisabled = false, $aRow = array())
    {
        if ($this->_oModule->checkAllowedManageAdmins($this->_iUserProfileId) !== CHECK_ACTION_RESULT_ALLOWED)
            return '';

        return parent::_getActionDefault ($sType, $sKey, $a, $isSmall, $isDisabled, $aRow);
    }

    protected function _getActionSetSubscriptionSubmit ($sType, $sKey, $a, $isSmall = false, $isDisabled = false, $aRow = array())
    {
        return '';
    }

    protected function _getActionAccept ($sType, $sKey, $a, $isSmall = false, $isDisabled = false, $aRow = array())
    {
        if ($aRow['mutual'])
            return '';

        if ($this->_oModule->checkAllowedManageFans($this->_iUserProfileId) !== CHECK_ACTION_RESULT_ALLOWED)
            return '';

        if (isset($aRow[$this->_aOptions['field_id']]))
            $a['attr']['bx_grid_action_data'] = $aRow[$this->_aOptions['field_id']] . ':' . $this->_iUserProfileId;

        return parent::_getActionDefault ($sType, $sKey, $a, $isSmall, $isDisabled, $aRow);
    }

    protected function _getActionDelete ($sType, $sKey, $a, $isSmall = false, $isDisabled = false, $aRow = array())
    {
        if ($this->_oModule->checkAllowedManageFans($this->_iUserProfileId) !== CHECK_ACTION_RESULT_ALLOWED)
            return '';

        if (isset($aRow[$this->_aOptions['field_id']]))
            $a['attr']['bx_grid_action_data'] = $aRow[$this->_aOptions['field_id']] . ':' . $this->_iUserProfileId;

        return parent::_getActionDefault ($sType, $sKey, $a, $isSmall, $isDisabled, $aRow);
    }
    
    protected function _getActionManageAdmins ($sType, $sKey, $a, $isSmall = false, $isDisabled = false, $aRow = array())
    {
        if (!$this->_oConnection->isConnected($aRow[$this->_aOptions['field_id']], $this->_iUserProfileId, false))
            return '';

        if ($this->_oModule->checkAllowedManageAdmins($this->_iUserProfileId) !== CHECK_ACTION_RESULT_ALLOWED)
            return '';

        if (isset($aRow[$this->_aOptions['field_id']]))
            $a['attr']['bx_grid_action_data'] = $aRow[$this->_aOptions['field_id']] . ':' . $this->_iUserProfileId;

        return parent::_getActionDefault ($sType, $sKey, $a, $isSmall, $isDisabled, $aRow);
    }
    

    protected function _delete ($mixedId)
    {
        list ($iId, $iViewedId) = $this->_prepareIds();

        // send email notification
        $sEmailTemplate = $this->_oConnection->isConnected($iViewedId, $iId, false) ? $this->_oModule->_oConfig->CNF['EMAIL_FAN_REMOVE'] : $this->_oModule->_oConfig->CNF['EMAIL_JOIN_REJECT'];
        list($iUserProfileId, $iProfileId) = $this->_prepareUserProfileAndMemberProfile($iId, $iViewedId);
        if (bx_get_logged_profile_id() != $iProfileId) {
            sendMailTemplate($sEmailTemplate, 0, $iProfileId, array(
                'EntryUrl' => BxDolProfile::getInstance($iUserProfileId)->getUrl(),
                'EntryTitle' => BxDolProfile::getInstance($iUserProfileId)->getDisplayName(),
            ), BX_EMAIL_NOTIFY);
        }

        // delete admin associated with profile
        $this->_oModule->_oDb->deleteAdminsByProfileId($iProfileId);

        return parent::_delete ($mixedId);
    }

    /**
     * @return array where first element is group profile id and second element is some other persons profile id
     */
    protected function _prepareUserProfileAndMemberProfile($iId1, $iId2)
    {
        if (BxDolProfile::getInstance($iId1)->getModule() == $this->_sContentModule)
            return array($iId1, $iId2);
        else
            return array($iId2, $iId1);
    }

    protected function _addJsCss()
    {
        parent::_addJsCss();

        if($this->_bSubcriptions)
            $this->_oModule->_oTemplate->addJs('main.js');
    }
}

/** @} */
