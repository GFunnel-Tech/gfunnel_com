<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) MSolutions
 * 
 * @defgroup    Fansonly Fansonly module
 * @ingroup     MSolutionsModules
 *
 * @{
 */ 


class MsFansonlyGridRequests extends BxTemplGrid //BxBaseModTextGridCommon //BxBaseModGeneralGridAdministration // //BxBaseModGeneralGridAdministration
{
/*
    protected $_bInit = false;
    protected $_bOwner = false;
    protected $_sObjectConnections = 'sys_profiles_friends';
    protected $_oProfile;
    protected $_oConnection;

*/
    protected $_sModule;
    protected $_oModule;

    protected $_sParamsDivider = '#-#';

    protected $_iUserProfileId;
    protected $_iUserContentId;
    protected $_aUserContentInfo;

    //protected $_aPeriodUnits;

    public function __construct ($aOptions, $oTemplate = false)
    {
        $this->_sModule = 'ms_fansonly';

    	$this->_oModule = BxDolModule::getInstance($this->_sModule);

        parent::__construct ($aOptions, $oTemplate);

        $CNF = $this->_oModule->_oConfig->CNF;

        //$this->_aPeriodUnits = BxDolForm::getDataItems($CNF['OBJECT_FORM_PRELISTS_PERIOD_UNITS']);

        $this->_iUserProfileId = 0;
        $this->_iUserContentId = 0;
        $this->_aUserContentInfo = array();

        $iUserProfileId = bx_process_input(bx_get('profile_id'), BX_DATA_INT);

        $this->setProfileId($iUserProfileId);
        /*
        $this->MODULE = 'msolutions_meet';
        parent::__construct ($aOptions, $oTemplate);
        $this->_sDefaultSortingOrder = 'DESC';

        $this->_aQueryAppendExclude[] = 'join_connections';

        */
        $this->setProfile(bx_get_logged_profile_id());

    }
//		$aVarsHead = $this->_getRowHead();

    protected function _getRowHead ()
    {
        $aRet = array();

//        foreach ($this->_aOptions['fields'] as $sKey => $a) {
//
//            $sMethod = '_getCellHeaderDefault';
//            $sCustomMethod = '_getCellHeader' . $this->_genMethodName($sKey);
//            if (method_exists($this, $sCustomMethod))
//                $sMethod = $sCustomMethod;
//
//            $aRet[] = array('header_cell' => $this->$sMethod($sKey, $a));
//        }

        return $aRet;
    }

    protected function _getRowsDataDesign (&$aData)
    {
        $aGrid = array();

        if(empty($aData))
            $aGrid[] = array(
                'id_row' => 0,
                'row_class' => 'bx-grid-table-row-empty',
                'row' => '<td class="bx-def-padding-sec-topbottom" colspan="' . count($this->_aOptions['fields']) . '"></td>'
            );
        else
            foreach ($aData as $aRow)
                $aGrid[] = array(
                    'id_row' => $this->_getRowId($aRow),
                    'row_class' => $this->_isRowDisabled($aRow) ? 'bx-grid-table-row-disabled bx-def-font-grayed' : '',
                    'row' => $this->_getRowDesign($aRow)
                );

        return $aGrid;
    }

        
    public function setProfile($iProfileId)
    {
        $this->_oProfile = BxDolProfile::getInstance((int)$iProfileId);

        $this->_bInit = $this->init();
    }

    public function setSellerId($iProfileId){
        $this->setProfileId($iProfileId);
        $this->_iUserProfileId = $iProfileId;
        $this->_iSeller = $iProfileId;
        //$this->_oPayment = BxDolPayments::getInstance();
        //$this->_bTypeSingle = $this->_oPayment->isAcceptingPayments($this->_iSeller, BX_PAYMENT_TYPE_SINGLE);
        //$this->_bTypeRecurring = $this->_oPayment->isAcceptingPayments($this->_iSeller, BX_PAYMENT_TYPE_RECURRING);
        
    }

    public function setProfileId($iProfileId)
    {
        $this->_iUserProfileId = (int)$iProfileId;
        $this->_aQueryAppend['profile_id'] = $this->_iUserProfileId;

        $oUserProfile = BxDolProfile::getInstance($this->_iUserProfileId);
        $this->_iUserContentId = $iProfileId;

        if($oUserProfile) {

            $this->_aUserContentInfo = $oUserProfile->getInfo();

        }
    }
    /*
    protected function _getCellPeriod($mixedValue, $sKey, $aField, $aRow)
    {
        $CNF = $this->_oModule->_oConfig->CNF;

        if((int)$mixedValue == 0)
            $mixedValue = _t('_lifetime');
        else
            $mixedValue = _t($CNF['T']['txt_n_unit'], $mixedValue, _t($this->_aPeriodUnits[$aRow['period_unit']]));

        return parent::_getCellDefault($mixedValue, $sKey, $aField, $aRow);
    }

    protected function _getCellPrice($mixedValue, $sKey, $aField, $aRow)
    {
        if((float)$mixedValue != 0) {
            $aCurrency = $this->_oModule->_oConfig->getCurrency();

            $mixedValue = $aCurrency['sign'] . $mixedValue;
        }
        else 
            $mixedValue = _t('_free');

        return parent::_getCellDefault($mixedValue, $sKey, $aField, $aRow);
    }
    */

    protected function _getIds()
    {
        $aIds = bx_get('ids');
        if(!$aIds || !is_array($aIds)) {
            $iId = (int)bx_get('id');
            if(!$iId) 
                return false;

            $aIds = array($iId);
        }

        return $aIds;
    }
//}

/** @} */


    /*
class MsolutionsMeetGrid extends BxBaseModGeneralGridAdministration //BxTemplGrid //BxBaseModTextGridCommon
{



    public function __construct ($aOptions, $oTemplate = false)
    {
        
    }
    */
    public function init()
    {
        if(!$this->_oProfile)
            return false;

        if ($this->_oProfile->id() == bx_get_logged_profile_id())
            $this->_bOwner = true;

        //mark everything as read
        
        $iUserId = (int) bx_get_logged_profile_id();
        $response = (int) 1;


        //$this->_oModule->_oDb->updateRead($iUserId);
        //Fix this?
        
        $this->_aQueryAppendExclude[] = 'join_connections';
        $where = $this->_oModule->_oDb->prepareAsString(" WHERE (`m`.`senderId` = ? OR `m`.`receiverId` = ?) AND `tp`.`id` != ? AND `m`.`response` = ? ", $iUserId, $iUserId, $iUserId, $response);  

        $this->addMarkers(array(
            'join_connections' => "LEFT JOIN `sys_profiles` AS `tp` ON (`tp`.`id` = `m`.`senderId` OR `tp`.`id` = `m`.`receiverId`) AND `tp`.`type`='bx_persons' LEFT JOIN `bx_persons_data` AS `td` ON `td`.`id`=`tp`.`content_id` LEFT JOIN `sys_accounts` AS `ta` ON `tp`.`account_id`=`ta`.`id`" . $where
        ));
                
    }

    /*
    protected function _delete ($mixedId)
    {
        
        list ($iId, $iViewedId) = $this->_prepareIds();
        
        return true;        
        
        //if ($this->_oConnection->isConnected($iViewedId, $iId, true))
        //    $a = $this->_oConnection->actionRemove($iId, $iViewedId);
        //else
        //    $a = $this->_oConnection->actionReject($iId, $iViewedId);
        
        //        if (isset($a['err']) && $a['err'])
        //            return false;

        return true;
    }
    */
    
    /*
    protected function _getActionDelete ($sType, $sKey, $a, $isSmall = false, $isDisabled = false, $aRow = array())
    {
        if (!$this->_bOwner)
            return '';
        return parent::_getActionDefault ($sType, $sKey, $a, $isSmall, $isDisabled, $aRow);
    }
    */
    

    /*
    protected function _getCellName ($mixedValue, $sKey, $aField, $aRow)
    {
        $iUserId = (int) bx_get_logged_profile_id();
        if($iUserId == $aRow['senderId'])
        {
            $opponentId = $aRow['receiverId'];
        }else{
            $opponentId = $aRow['senderId'];
        }
        
        $oProfile = BxDolProfile::getInstance($opponentId);
        if (!$oProfile)
            return _t('_sys_txt_error_occured');

        return parent::_getCellDefault ($oProfile->getUnit(), $sKey, $aField, $aRow);
    }

    protected function _getCellInfo ($mixedValue, $sKey, $aField, $aRow)
    {
        $s = '';
        
        return parent::_getCellDefault ($s, $sKey, $aField, $aRow);
    }
    */

    protected function _getActionRequest ($sType, $sKey, $a, $isSmall = false) {
        
        $iProfileId = $this->_iUserProfileId;
        $iRequesterId = bx_process_input(bx_get_logged_profile_id(), BX_DATA_INT);
        $aRequestSent = $this->_oModule->_oDb->getRequest($iProfileId, $iRequesterId);
        
        
        $text = !empty($aRequestSent) ? _t('_ms_fansonly_request_sent') : $a['title'];
        $disabled = !empty($aRequestSent) ? 'onclick="$(this).off();" disabled' : '';
        
        $sAttr = $this->_convertAttrs(
            $a, 'attr',
            'bx-btn bx-def-margin-sec-left' . ($isSmall ? ' bx-btn-small' : '') // add default classes
        );

        //print json_encode([$aRequestSent, $iProfileId, $iRequesterId, $_GET], true);
        
        return '<button ' . $sAttr . ' '.$disabled.'>' . $text . '</button>';
    }

    public function performActionRequest() {
        
        //lets add it to the database
        
        //$iId = bx_get('profile_id', 'int');

        if(!isLogged()){
            $this->_echoResultJson(array('msg' => _t('_ms_fansonly_please_login')));
            return false;
        }

        $iProfileId = bx_process_input(bx_get('profile_id'), BX_DATA_INT);
        $iRequesterId = bx_process_input(bx_get_logged_profile_id(), BX_DATA_INT);
        if(!empty($iProfileId) && !empty($iRequesterId)){
            $this->_oModule->_oDb->addRequest($iProfileId, $iRequesterId);
            
            //TODO, send notification to profile owner
            $aRequestSent = $this->_oModule->_oDb->getRequest($iProfileId, $iRequesterId);
            $this->_oModule->triggerAlertAction($aRequestSent);
            //$this->triggerAlertAction($aRequest, "doMatchaccepted");
            

        }

        
            /*

        $ok = _t('_ms_fansonly_request_sent'); //. json_encode([$iProfileId, $iRequesterId], true); //'Request sent'
        $s = BxTemplFunctions::getInstance()->transBox('ms-fansonly-request-sent', '
                <div class="bx-def-padding bx-def-color-bg-block">' . $ok . '</div>');
//        $this->_echoResultJson(array('popup' => $s));
//        $this->_echoResultJson(array('grid' => $s));
//        $this->_echoResultJson(array('grid' => $this->getCode(false), 'eval'=>'alert("ok")'));
//        $this->_echoResultJson(array('grid' => $this->getCode(false), 'msg' => "Request sent, you will receive a notification if your request is accepted", 'eval'=>'$(\'#bx-grid-wrap-ms_fansonly_requests_view\').remove()'));
//          $this->_echoResultJson(array('grid' => $this->getCode(false), 'eval'=>'var newGridCode = '.json_encode($this->getCode(false), true).'; $(\'#bx-grid-wrap-ms_fansonly_requests_view\').html($(newGridCode).html());'));
      
//          $this->_echoResultJson(array('grid' => $this->getCode(false), 'eval'=>'$(\'#bx-grid-wrap-ms_fansonly_requests_view\').html($(\''.json_encode($this->getCode(false), true).'\').html());'));
  
//          $this->_echoResultJson(array('grid' => $this->getCode(false), 'eval'=>'var newGridCode = '.json_encode($this->getCode(false), true).'; $(\'#bx-grid-wrap-ms_fansonly_requests_view\').html(JSON.parse(newGridCode));'));

//          $this->_echoResultJson(array('grid' => $this->getCode(true)));

//          $this->_echoResultJson(['eval'=>'var newGridCode = '.json_encode($this->getCode(true), true).'; $(\'#bx-grid-wrap-ms_fansonly_requests_view\').html(JSON.stringify(JSON.stringify(newGridCode)));']);
          $eval = 'var newGridCode = '.json_encode($this->getCode(true), true).'; ';
          $eval .= 'var newGridCodeInside = $(newGridCode).find("#bx-grid-wrap-ms_fansonly_requests_view").html(); ';
//          $eval .= "$('#bx-grid-wrap-ms_fansonly_requests_view').html(JSON.stringify(newGridCode));";
//          $eval .= "$('#bx-grid-wrap-ms_fansonly_requests_view').html(newGridCode).bxProcessHtml();";

//          $eval .= "$('#bx-grid-wrap-ms_fansonly_requests_view').html($(newGridCode).find('#bx-grid-wrap-ms_fansonly_requests_view').html()).bxProcessHtml();";
//          $eval .= "$('#bx-grid-wrap-ms_fansonly_requests_view').html($(newGridCode).html()).bxProcessHtml();";
          $eval .= "var gridDivFind = '.bx-grid-header'; ";
          $eval .= "var newGridCodeReplacement = $(newGridCode).find(gridDivFind).html();";
          $eval .= " console.log(gridDivFind, newGridCodeReplacement); ";
          $eval .= "$('#bx-grid-wrap-ms_fansonly_requests_view ' + gridDivFind).html(newGridCodeReplacement);";
          
          $eval .= "$('#bx-grid-wrap-ms_fansonly_requests_view').remove();";
            */            
          //$eval = "$('#bx-grid-wrap-ms_fansonly_requests_view').html('<p style=\"margin: 0 auto;\">"._t('_ms_fansonly_request_sent')."</p>');";

          $text = _t('_ms_fansonly_request_sent');
//          $text = _t('_ms_fansonly_cancel_free_request');

          $aReturn = [];
          $eval = "$('#bx-grid-wrap-ms_fansonly_requests_view .bx-grid-header-controls-independent button').html('<u>".$text."</u>').attr('disabled', 'disabled'); console.log($('#bx-grid-wrap-ms_fansonly_requests_view .bx-grid-header-controls-independent button').html());";
          $aReturn['eval'] = $eval;
//          $aReturn['msg'] = $text;
                      
          $this->_echoResultJson($aReturn);
//          $this->_echoResultJson(['eval'=>'console.log("ok");']);


        return;        
      
    }
    private function _echoResultJson($result = array()){
        echo json_encode($result, true);
    }


    protected function _getFilterControls()
    {
        parent::_getFilterControls();
        return '';
    }


}

/** @} */
