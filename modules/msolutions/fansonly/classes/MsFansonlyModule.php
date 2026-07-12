<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) MSolutions
 * 
 * @defgroup    Fansonly Fansonly module
 * @ingroup     MSolutionsModules
 *
 * @{
 */

define('BX_BASE_MOD_FANSONLY_ACTION_MANAGE_FANS', 'manage_fans');
define('BX_BASE_MOD_FANSONLY_ACTION_MANAGE_PRICES', 'manage_prices');

define('BX_BASE_MOD_FANSONLY_PERIOD_UNIT_DAY', 'day');
define('BX_BASE_MOD_FANSONLY_PERIOD_UNIT_WEEK', 'week');
define('BX_BASE_MOD_FANSONLY_PERIOD_UNIT_MONTH', 'month');
define('BX_BASE_MOD_FANSONLY_PERIOD_UNIT_YEAR', 'year');

class MsFansonlyModule extends BxBaseModGeneralModule //BxDolModule
{

    private $isPreview = false;
    
    private $sPreviewError = false;
    
    public $isStudioSettings = false;

    public $cookieFansonly = '';
    
    public $defaultFansonly = array('fansonly'=>'', 'profile_id'=>0, 'action'=>'add');

    public $timers = array();
    
    public function __construct(&$aModule)
    {        
        parent::__construct($aModule);
        /*
        $date = time();
        //$date = strtotime($start_date);
        $test = true;
        if($test){

            $dates = [];
            $dates[] = strtotime("+1 day", $date);
            $dates[] = strtotime("+1 week", $date);
            $dates[] = strtotime("+1 Month", $date);
            $dates[] = strtotime("+1 year", $date);

            print  $date." - ".json_encode($dates, true);
        
            print '<br>'. date('Y/m/d H:i:s', $date).' : Today'; 
            foreach($dates as $d){
                print '<br>'. date('Y/m/d H:i:s', $d); 
            }        
        }
        */
        

    }

	public function addlog($line){  
        return;
        //Disable logs
		$ddl=date("dmy");
		$line = json_encode($line, true);
        $filename = BX_DIRECTORY_PATH_LOGS ."FansonlyModule.php";

        $fi = @fopen( $filename,"a+");
        if($fi){
           $array = explode("\n", fread($fi, filesize($filename)));
            if(!in_array($line, $array))
            {
        		fwrite($fi,$line. "\n");
            }

	    	fclose($fi);   	        

        }
    
	}


    public function serviceGetOptionsEnable()
    {

        $aAll = $this->_oDb->selectSysForms();

        $aResult = array();

        $sPrev = '';
        $_oModule = false;

        $sModuleName = '';
        $sModuleTitle = '';
        
        foreach($aAll as $aObject)
        {
            
            $sModuleName = $aObject["module"];
            $var = $aObject["object"];
//            $aResult[] = array('key' => $var, 'value' => $this->getModuleName($sModuleName) .' : '. _t($aObject["title"]) .' : '.$aObject["object"] ); 

            $aResult[] = array('key' => $var, 'value' => $this->getModuleName($sModuleName) .' - '. _t($aObject["title"])  ); 

        }
        return $aResult;
        

    }
    

    public function resetObjects($aFormObject)
    {   
        $this->_oDb->resetSysObjectsHadlers( $aFormObject ); //It will remove the handlers Except those in array
        
    }
    
    public function setObject($sObject)
    {
        if($sObject){
                        
            $this->_oDb->setSysObjectsHandlers( $sObject ); 
            
        }
        
    }
    
    
    public function getModuleName($sModuleName){

        $aModule = BxDolModuleQuery::getInstance()->getModuleByName($sModuleName);

        return $aModule['title'];
    }








    public function serviceIsPricingAvaliable($iProfileId)
    {
        if($this->checkAllowedManagePrice($iProfileId) == CHECK_ACTION_RESULT_ALLOWED)
            return true;
        
        return false;
    }

    public function serviceIsPaidJoinAvaliable($iProfileId)
    {
        return $this->isPaidJoinByProfile($iProfileId);
    }

    
    public function isPaidJoinByProfile($iProfileId)
    {
        return true;
        
        if(!$this->_oConfig->isPaidJoin())
            return false;

        $aPrices = $this->_oDb->getPrices(array('type' => 'by_profile_id', 'profile_id' => $iProfileId));
        if(empty($aPrices) || !is_array($aPrices))
            return false;

        return true;
    }

    /**
     * Integration with Payments.
     */
    public function serviceGetPaymentData()
    {
        return $this->_aModule;
    }

    public function serviceGetCartItem($mixedItemId)
    {
        $CNF = &$this->_oConfig->CNF;

        if(!$mixedItemId)
            return array();

        if(is_numeric($mixedItemId))
            $aItem = $this->_oDb->getPrices(array('type' => 'by_id', 'value' => (int)$mixedItemId));
        else 
            $aItem = $this->_oDb->getPrices(array('type' => 'by_name', 'value' => $mixedItemId));

        if(empty($aItem) || !is_array($aItem))
            return array();

        if(!$this->isPaidJoinByProfile($aItem['profile_id']))
            return array();

        $oProfile = BxDolProfile::getInstance($aItem['profile_id']);
        if(!$oProfile)
            return array();

        $aUserProfile = BxDolProfile::getInstance($aItem['profile_id']);
        
        $name = _t($CNF['T']['txt_cart_item_title'], $oProfile->getDisplayName(), $aItem['period'], $aItem['period_unit']);

            return  array(
                'id' => $aItem['id'],
                'author_id' => $aItem['profile_id'],

//                'name' => $aItem['name'],
                'name' => "P-SDFSDF",
//                'title' => _t($CNF['T']['txt_cart_item_title'], $oProfile->getDisplayName(), $aItem['period'], $aItem['period_unit']),
                'title' => $name,
                'description' => $name,

                'url' => $oProfile->getUrl(),
                'price_single' => $aItem['price'],
                'price_recurring' => $aItem['price'],
                'period_recurring' => $aItem['period'],
                'period_unit_recurring' => $aItem['period_unit'],
                'trial_recurring' => 0
            );

    }

    public function serviceGetCartItems($iSellerId)
    {
    	$CNF = &$this->_oConfig->CNF;

    	if(empty($iSellerId))
    	    return array();

        $sModule = $this->getName();

        $aResult = array();

        $oProfile = BxDolProfile::getInstance($iSellerId);

        if(!$oProfile)
            return $aResult; //continue;

            $aPrices = $this->_oDb->getPrices(array('type' => 'by_profile_id', 'profile_id' => $oProfile->id()));
            if(empty($aPrices) || !is_array($aPrices))
                return $aResult; //continue;

            $sTitle = $oProfile->getDisplayName();
            $sUrl = $oProfile->getUrl();


            foreach($aPrices as $aPrice){

                $name =  _t($CNF['T']['txt_cart_item_title'], $sTitle, $aPrice['period'], $aPrice['period_unit']);

                $aResult[] = array(
                    'id' => $aPrice['id'],
                    'author_id' => $iSellerId,
                    'name' => $aPrice['name'],
                    'title' => $name,

                    'description' => $name, //'',
                    'url' => $sUrl,
                    'price_single' => $aPrice['price'],
                    'price_recurring' => $aPrice['price'],
                    'period_recurring' => $aPrice['period'],
                    'period_unit_recurring' => $aPrice['period_unit']
               );
          }

        return $aResult;
    }


    //Payment cart and after payment

    public function serviceRegisterCartItem($iClientId, $iSellerId, $iItemId, $iItemCount, $sOrder, $sLicense)
    {
        return $this->_serviceRegisterItem($iClientId, $iSellerId, $iItemId, $iItemCount, $sOrder);
    }

    public function serviceRegisterSubscriptionItem($iClientId, $iSellerId, $iItemId, $iItemCount, $sOrder, $sLicense)
    {
        return $this->_serviceRegisterItem($iClientId, $iSellerId, $iItemId, $iItemCount, $sOrder);
    }

    public function serviceReregisterCartItem($iClientId, $iSellerId, $iItemIdOld, $iItemIdNew, $sOrder)
    {
        return $this->_serviceReregisterItem($iClientId, $iSellerId, $iItemIdOld, $iItemIdNew, $sOrder);
    }

    public function serviceReregisterSubscriptionItem($iClientId, $iSellerId, $iItemIdOld, $iItemIdNew, $sOrder)
    {
        return $this->_serviceReregisterItem($iClientId, $iSellerId, $iItemIdOld, $iItemIdNew, $sOrder);
    }

    public function serviceUnregisterCartItem($iClientId, $iSellerId, $iItemId, $iItemCount, $sOrder, $sLicense)
    {
        return $this->_serviceUnregisterItem($iClientId, $iSellerId, $iItemId, $iItemCount, $sOrder);
    }

    public function serviceUnregisterSubscriptionItem($iClientId, $iSellerId, $iItemId, $iItemCount, $sOrder, $sLicense)
    {
    	return $this->_serviceUnregisterItem($iClientId, $iSellerId, $iItemId, $iItemCount, $sOrder); 
    }

    public function serviceCancelSubscriptionItem($iClientId, $iSellerId, $iItemId, $iItemCount, $sOrder)
    {
    	return true;
    }
    
    protected function _serviceRegisterItem($iClientId, $iSellerId, $iItemId, $iItemCount, $sOrder)
    {
        $CNF = &$this->_oConfig->CNF;

    	$aItem = $this->serviceGetCartItem($iItemId);
        if(empty($aItem) || !is_array($aItem))
            return array();

        $aItemInfo = $this->_oDb->getPrices(array('type' => 'by_id', 'value' => $iItemId));

        $mixedPeriod = false;
        if((int)$aItemInfo['period'] != 0)
            $mixedPeriod = array(
                'period' => (int)$aItemInfo['period'], 
                'period_unit' => $aItemInfo['period_unit'], 
                //'period_reserve' => $CNF['PARAM_RECURRING_RESERVE']
            );

        //this works for subscriptions and for single payments too
        if(!$this->setSubscription($aItemInfo['profile_id'], $iClientId, $mixedPeriod, $sOrder))
            return array();

        return $aItem;
    }

    protected function _serviceReregisterItem($iClientId, $iSellerId, $iItemIdOld, $iItemIdNew, $sOrder)
    {
        $aItem = $this->serviceGetCartItem($iItemIdNew);
        if(empty($aItem) || !is_array($aItem))
            return array();

        $aItemInfoOld = $this->_oDb->getPrices(array('type' => 'by_id', 'value' => $iItemIdOld));
        if(empty($aItemInfoOld) || !is_array($aItemInfoOld))
            return array();

        $aItemInfoNew = $this->_oDb->getPrices(array('type' => 'by_id', 'value' => $iItemIdNew));
        if(empty($aItemInfoNew) || !is_array($aItemInfoNew))
            return array();

        if(!$this->unsetSubscription($aItemInfoOld['profile_id'], $iClientId))
            return array();
        
        $aResult = $this->_serviceRegisterItem($iClientId, $iSellerId, $iItemIdNew, 1, $sOrder);
        if(empty($aResult) || !is_array($aResult))
            return array();

    	return $aItem;
    }

    protected function _serviceUnregisterItem($iClientId, $iSellerId, $iItemId, $iItemCount, $sOrder)
    {
        $aItemInfo = $this->_oDb->getPrices(array('type' => 'by_id', 'value' => $iItemId));
        if(empty($aItemInfo) || !is_array($aItemInfo))
            return false;

        return $this->unsetSubscription($aItemInfo['profile_id'], $iClientId);
    }

    //Admin prices
    public function serviceEntityPricing($iProfileId = 0)
    {

        /*
        $oPayments = BxDolPayments::getInstance();
        $aSubscriptions = $this->_oDb->getSubscriptions(array('type' => 'notexpired'));
        print json_encode($aSubscriptions, true).'<br>';
        foreach($aSubscriptions as $aSubscription) {
            $aPaymentSubscription = $oPayments->getSubscriptionsInfo(array('subscription_id' => $aSubscription['order']), true);
            if(!empty($aPaymentSubscription) && is_array($aPaymentSubscription)) {
                print json_encode($aPaymentSubscription, true).'<br>';
             }
        }
        */
        $CNF = &$this->_oConfig->CNF;

        if(!$iProfileId)
            $iProfileId = bx_process_input(bx_get('profile_id'), BX_DATA_INT);
        if(!$iProfileId)
            return '';

        if(!$this->_oConfig->isPaidJoin())
            return '';

        if($this->checkAllowedManageAdmins($iProfileId) !== CHECK_ACTION_RESULT_ALLOWED)
            return MsgBox(_t('_Access denied'));

        $oGrid = BxDolGrid::getObjectInstance($CNF['OBJECT_GRID_PRICES_MANAGE']);
        if(!$oGrid)
            return '';
//            return 'no grid: '.$CNF['OBJECT_GRID_PRICES_MANAGE'];
        $oGrid->setSellerId($iProfileId);

        return $oGrid->getCode();
    }

    public function serviceEntityJoin($iProfileId = 0)
    {

        

        $CNF = &$this->_oConfig->CNF;

        if(!$iProfileId)
            $iProfileId = bx_process_input(bx_get('profile_id'), BX_DATA_INT);
        if(!$iProfileId)
            return '';

        if(bx_get_logged_profile_id() == $iProfileId){
                return '';            
        }
        if( $this->isFan($iProfileId) ){ //owners and fans are allowed to view
                return _t("_ms_fansonly_you_are_subscribed_to_profile", $this->getAuthorName( $iProfileId ));
        }

        if(!$this->_oConfig->isPaidJoin())
            return '';

        $oGrid = BxDolGrid::getObjectInstance($CNF['OBJECT_GRID_PRICES_VIEW']);
        if(!$oGrid)
            return '';

        $oGrid->setSellerId($iProfileId);

        $return = '';
        if(!$this->isPaidButtonsDisabled()){
            $return = $oGrid->getCode();
        }
        $return .= $this->serviceEntityFreeRequest($iProfileId);
        
        return $return;

    }
    public function serviceEntityFreeRequest($iProfileId = 0)
    {

        if(!$this->isFeeRequestsEnabled()){
            return false;
        }
        
        //return 'asd';
        $CNF = &$this->_oConfig->CNF;

        if(!$iProfileId)
            $iProfileId = bx_process_input(bx_get('profile_id'), BX_DATA_INT);
        if(!$iProfileId)
            return '';

        if(bx_get_logged_profile_id() == $iProfileId){
                return '';            
        }
        if( $this->isFan($iProfileId) ){ //owners and fans are allowed to view
                return _t("_ms_fansonly_you_are_subscribed_to_profile", $this->getAuthorName( $iProfileId ));
        }

        //if(!$this->_oConfig->isPaidJoin())
        //    return '';

        $oGrid = BxDolGrid::getObjectInstance($CNF['OBJECT_GRID_REQUESTS_VIEW']);
        if(!$oGrid)
            return '';
        
        $oGrid->setSellerId($iProfileId);
        
        return $oGrid->getCode();
    }
    public function serviceRequests($iProfileId = 0)
    {
        //return 'asd';
        $CNF = &$this->_oConfig->CNF;
        /*
        if(!$iProfileId)
            $iProfileId = bx_process_input(bx_get('profile_id'), BX_DATA_INT);
        if(!$iProfileId)
            return '';

        if(bx_get_logged_profile_id() == $iProfileId){
                return '';            
        }
        if( $this->isFan($iProfileId) ){ //owners and fans are allowed to view
                return _t("_ms_fansonly_you_are_subscribed_to_profile", $this->getAuthorName( $iProfileId ));
        }
        */
        $iProfileId = bx_get_logged_profile_id();

        //if(!$this->_oConfig->isPaidJoin())
        //    return '';

//        $oGrid = BxDolGrid::getObjectInstance($CNF['OBJECT_GRID_REQUESTS_VIEW']);
        $oGrid = BxDolGrid::getObjectInstance($CNF['OBJECT_GRID_REQUESTS_MANAGE']);
        if(!$oGrid)
            return '';
        
        $oGrid->setSellerId($iProfileId);
        
        return $oGrid->getCode();
    }


    public function getUserInfo($iUserId = 0)
    {
        $oProfile = BxDolProfile::getInstanceMagic($iUserId);

        return array(
            $oProfile->getDisplayName(),
            $oProfile->getUrl(),
            $oProfile->getThumb(),
            $oProfile->getUnit(),
            $oProfile->getUnit(0, array('template' => 'unit_wo_info'))
        );
    }

    
    public function getAuthorName( $iProfileId ){
        
        list($sAuthorName, $sAuthorUrl, $sAuthorIcon, $sAuthorUnit, $sAuthorUnitShort) = $this->getUserInfo($iProfileId);
            $sAuthorName = $this->_oTemplate->parseHtmlByName('author_link.html', array(
                'href' => $sAuthorUrl,
                'title' => bx_html_attribute($sAuthorName),
                'content' => $sAuthorName,
                'bx_if:show_account' => array(
                    'condition' => false,
                    'content' => array()
                )
            ));
        return $sAuthorName;
    }




    protected function _checkAllowedConnect (&$aDataEntry, $isPerformAction, $sObjConnection, $isMutual, $isInvertResult, $isSwap = false)
    {
        $sResult = $this->checkAllowedView($aDataEntry);

        $oPrivacy = BxDolPrivacy::getObjectInstance($this->_oConfig->CNF['OBJECT_PRIVACY_VIEW']);

        if (CHECK_ACTION_RESULT_ALLOWED !== $sResult && !in_array($aDataEntry[$this->_oConfig->CNF['FIELD_ALLOW_VIEW_TO']], array_merge($oPrivacy->getPartiallyVisiblePrivacyGroups(), array('s'))))
            return $sResult;

        return parent::_checkAllowedConnect ($aDataEntry, $isPerformAction, $sObjConnection, $isMutual, $isInvertResult, $isSwap);
    }

    public function addFollower ($iProfileId1, $iProfileId2)
    {
        $oConnectionFollow = BxDolConnection::getObjectInstance('sys_profiles_subscriptions');
        if($oConnectionFollow && !$oConnectionFollow->isConnected($iProfileId1, $iProfileId2)){
            $oConnectionFollow->addConnection($iProfileId1, $iProfileId2);
            return true;
        }
        return false;
    }
    
    //contentid is the profile locked
    //iFanId is the profile of the other person, if empty, our profile
    public function isFan ($iContentId, $iFanId = false) 
    {

        $CNF = &$this->_oConfig->CNF;

        $oUserProfile = BxDolProfile::getInstance($iContentId);

        $iFanId = $iFanId ? $iFanId : bx_get_logged_profile_id(); //me
//        $contentId = $iProfileId ? $iProfileId : bx_get_logged_profile_id(); //me
        $iContentProfileId = $oUserProfile->id(); //owner
        
//print $iOurId . '<br>';
//print $iContentProfileId;

        $aSubscriptions = $this->_oDb->getSubscriptionRow($iContentProfileId, $iFanId);
//print json_encode($aSubscriptions);
        return $this->isSubscriptionActive($aSubscriptions);

        if($oUserProfile && isset($CNF['OBJECT_CONNECTIONS']))
            return ($oConnection = BxDolConnection::getObjectInstance($CNF['OBJECT_CONNECTIONS'])) && $oConnection->isConnected($contentId, $profileId, false);

        return false;
    }

    public function isSubscriptionActive($aSubscriptions){
        $now =  time();
        foreach($aSubscriptions as $aSubscription){
                if(!empty($aSubscription) && !empty($aSubscription["expired"]) ){
                    //print json_encode($aSubscription);
                    $expired = (int)$aSubscription["expired"];
                    //print $expired;
                    //print $now;
                    if($expired > $now){
                        return true;
                    }
                }
        }
        return false;
    }
    public function isFanByUserProfileId ($iUserProfileId, $iProfileId = false) 
    {
        $CNF = &$this->_oConfig->CNF;

        $oUserProfile = BxDolProfile::getInstance($iUserProfileId);
        if($oUserProfile && isset($CNF['OBJECT_CONNECTIONS']))
            return ($oConnection = BxDolConnection::getObjectInstance($CNF['OBJECT_CONNECTIONS'])) && $oConnection->isConnected($iProfileId ? $iProfileId : bx_get_logged_profile_id(), $oUserProfile->id(), false);

        return false;
    }

    public function isInvited ($sKey, $iProfileId) 
    {
        $CNF = &$this->_oConfig->CNF;
        $aData = $this->_oDb->getInviteByKey($sKey,  $iProfileId);
        if (!isset($aData['invited_profile_id']))
            return _t($CNF['T']['txt_invitation_popup_error_invitation_absent']);
        
        if ($aData['invited_profile_id'] != bx_get_logged_profile_id())
            return  _t($CNF['T']['txt_invitation_popup_error_wrong_user']);
        
        return true;
    }

    public function getSubscription($iUserProfileId, $iFanProfileId)
    {
        if(!$this->isFanByUserProfileId($iUserProfileId, $iFanProfileId))
            return false;

        return $this->_oDb->getSubscription($iUserProfileId, $iFanProfileId);
    }

    public function setSubscription($iUserProfileId, $iFanProfileId, $mixedPeriod = false, $sOrder = '')
    {
        $CNF = &$this->_oConfig->CNF;

        $this->addlog(["setSubscription", $CNF]);

        if(!isset($CNF['OBJECT_CONNECTIONS']))
            return false;

        $oConnection = BxDolConnection::getObjectInstance($CNF['OBJECT_CONNECTIONS']);
        $oUserProfile = BxDolProfile::getInstance($iUserProfileId);

        $this->addlog(["connectionanduserprofile", $oConnection, $oUserProfile]);

        if(!$oConnection || !$oUserProfile)
            return false;


        if(
            !$oConnection->isConnected($iFanProfileId, $iUserProfileId, false) 
            && !($oConnection->addConnection($iFanProfileId, $iUserProfileId) 
                )){
            $this->addlog("Not working this");
            //return false;
        }

        $this->addlog(["after add connection", $oConnection->isConnected($iFanProfileId, $iUserProfileId, false) ]);


        //we do not need this ?
        if(!$this->_oDb->setSubscription($iUserProfileId, $iFanProfileId, $mixedPeriod, $sOrder)){
            $this->addlog("Not adding subscription");

            return false;
        }

        $this->addlog(["after set subscription"]);

        $this->onSetSubscription($iUserProfileId, $iFanProfileId);

        $this->addlog("after onset subscription");

        return true;
    }

    public function onSetSubscription($iUserProfileId, $iFanProfileId)
    {
        $CNF = &$this->_oConfig->CNF;

        $iProfileId = bx_get_logged_profile_id();
        $oUserProfile = BxDolProfile::getInstance($iUserProfileId); //this  is the same user than iUserProfileid, it is sellerid
        

        $aUserProfileInfo = $oUserProfile->getInfo();

        // notify about admin status
        //TODO, do not send this email
        // Nothing to do here

        if(!empty($CNF['EMAIL_FAN_SET_SUBSCRIPTION']) && $iFanProfileId != $iProfileId)
            sendMailTemplate($CNF['EMAIL_FAN_SET_SUBSCRIPTION'], 0, $iFanProfileId, array(
                'EntryUrl' => $oUserProfile->getUrl(),
                'EntryTitle' => $oUserProfile->getDisplayName(),


            ), BX_EMAIL_NOTIFY);
        
//        bx_alert($this->getName(), 'set_fan', $aUserProfileInfo[$CNF['FIELD_ID']], $iUserProfileId, array(
        bx_alert($this->getName(), 'set_fan', $aUserProfileInfo['id'], $iUserProfileId, array(
            'object_author_id' => $iUserProfileId,
            'performer_id' => $iProfileId, 
            'fan_id' => $iFanProfileId,

            'content' => $aUserProfileInfo, 

            'user_profile' => $iUserProfileId, 
            'profile' => $iProfileId
        ));
        
        $this->doAudit($iUserProfileId, $iFanProfileId, '_ms_fansonly_audit_action_fansonly_fan_changed');

        $this->addlog(["after do audit", $iUserProfileId, $iFanProfileId]);

    }

    public function unsetSubscription($iUserProfileId, $iFanProfileId)
    {
        $CNF = &$this->_oConfig->CNF;

        if(!isset($CNF['OBJECT_CONNECTIONS']))
            return false;

        $oConnection = BxDolConnection::getObjectInstance($CNF['OBJECT_CONNECTIONS']);
        $oUserProfile = BxDolProfile::getInstance($iUserProfileId);
        if(!$oConnection || !$oUserProfile)
            return false;

        if($oConnection->isConnected($iFanProfileId, $iUserProfileId, false) && !$oConnection->removeConnection($iFanProfileId, $iUserProfileId))
            return false;

        $iSubscription = $this->_oDb->getSubscription($iUserProfileId, $iFanProfileId);
        if(!$this->_oDb->unsetSubscription($iUserProfileId, $iFanProfileId)){
            //return false;
            //$iSubscription = 0;
        }

        $this->onUnsetSubscription($iUserProfileId, $iFanProfileId, $iSubscription);

        return true;
    }

    public function onUnsetSubscription($iUserProfileId, $iFanProfileId, $iSubscription)
    {
        $CNF = &$this->_oConfig->CNF;

        $iProfileId = bx_get_logged_profile_id();
        $oUserProfile = BxDolProfile::getInstance($iUserProfileId);

        $aUserProfileInfo = $oUserProfile->getInfo();


//        bx_alert($this->getName(), 'unset_fan', $aUserProfileInfo[$CNF['FIELD_ID']], $iUserProfileId, array(
        bx_alert($this->getName(), 'unset_fan', $aUserProfileInfo['id'], $iUserProfileId, array(
            'object_author_id' => $iUserProfileId,
            'performer_id' => $iProfileId, 
            'fan_id' => $iFanProfileId,

            'content' => $aUserProfileInfo,
            'subscription' => $iSubscription,

            'user_profile' => $iUserProfileId, 
            'profile' => $iProfileId
        ));

        
        $this->doAudit($iUserProfileId, $iFanProfileId, '_ms_fansonly_audit_action_fansonly_fan_removed');

        $this->addlog(["after do audit fan removed", $iUserProfileId, $iFanProfileId]);

    }
 




    public function checkAllowedManageFans($mixedDataEntry, $isPerformAction = false)
    {


        $aDataEntry = array();
        if(!is_array($mixedDataEntry)) {
            $oUserProfile = BxDolProfile::getInstance((int)$mixedDataEntry);

            if($oUserProfile){

                $aDataEntry = $oUserProfile->getInfo();
                
            }
        }
        else
            $aDataEntry = $mixedDataEntry;

        if($this->_checkAllowedActionByOwner(BX_BASE_MOD_FANSONLY_ACTION_MANAGE_FANS, $aDataEntry) === CHECK_ACTION_RESULT_ALLOWED)
            return CHECK_ACTION_RESULT_ALLOWED;

        return parent::checkAllowedEdit($aDataEntry, $isPerformAction);
    }
    

    public function checkAllowedManagePrice($mixedDataEntry, $isPerformAction = false)
    {
        $aDataEntry = array();
        if(!is_array($mixedDataEntry)) {
            $oUserProfile = BxDolProfile::getInstance((int)$mixedDataEntry);
            if($oUserProfile){
                $aDataEntry = $oUserProfile->getInfo();
            }
        }
        else
            $aDataEntry = $mixedDataEntry;

        if($this->_checkAllowedActionByOwner(BX_BASE_MOD_FANSONLY_ACTION_MANAGE_PRICES, $aDataEntry) === CHECK_ACTION_RESULT_ALLOWED){
            return CHECK_ACTION_RESULT_ALLOWED;
        }
        return $this->_checkAllowedDo('edit any price', $aDataEntry, $isPerformAction);
    }
    

    public function checkAllowedManageAdmins($mixedDataEntry, $isPerformAction = false)
    {
        $aDataEntry = array();
        if(!is_array($mixedDataEntry)) {
            $oUserProfile = BxDolProfile::getInstance((int)$mixedDataEntry);

            if($oUserProfile){

                $aDataEntry = $oUserProfile->getInfo();

            }
        }
        else
            $aDataEntry = $mixedDataEntry;

        if($this->_checkAllowedActionByOwner(BX_BASE_MOD_FANSONLY_ACTION_MANAGE_PRICES, $aDataEntry) === CHECK_ACTION_RESULT_ALLOWED)
            return CHECK_ACTION_RESULT_ALLOWED;


        return $this->_checkAllowedDo('edit any price', $aDataEntry, $isPerformAction);
        
    }

   

    protected function _checkAllowedActionByOwner($sAction, $aDataEntry, $iProfileId = 0)
    {
        
        if(empty($iProfileId))
            $iProfileId = bx_get_logged_profile_id();

        if(!empty($aDataEntry) && !empty($iProfileId) && $aDataEntry["id"] == $iProfileId){
            return CHECK_ACTION_RESULT_ALLOWED;
        }
        
        return false;
        
    }


    public function isAllowedActionBySubscription($sAction, $aDataEntry, $iUserProfileId, $iProfileId)
    {
        $bResult = false;
        
        $iProfileSubscription = $this->_oDb->getSubscription($iUserProfileId, $iProfileId);

        $bResult = true;

        // call alert to allow custom checks
        bx_alert('system', 'check_allowed_action_by_subscription', 0, 0, array(
            'module' => $this->getName(), 
            'multi_subscriptions' => $this->_oConfig->isMultiSubscriptions(),
            'action' => $sAction,
            'content_profile_id' => $iUserProfileId, 
            'content_info' => $aDataEntry, 
            'profile_id' => $iProfileId, 
            'profile_subscription' => $iProfileSubscription,
            'override_result' => &$bResult
        ));

        return $bResult;
    }


    
    protected function _checkAllowedDo($sAction, $aDataEntry, $isPerformAction = false)
    {
        if($this->_isModerator($isPerformAction))
			return CHECK_ACTION_RESULT_ALLOWED;


    	$aCheck = checkActionModule($this->_iProfileId, $sAction, $this->getName(), $isPerformAction);

    	if($aCheck[CHECK_ACTION_RESULT] === CHECK_ACTION_RESULT_ALLOWED)
    		return CHECK_ACTION_RESULT_ALLOWED;

        return CHECK_ACTION_RESULT_NOT_ALLOWED; 

    }
    public function getMarkedContent($sModule, $iContentId, $sStatus = ''){
        $aContentForFansOnly = $this->_oDb->getMarkedContent($sModule, $iContentId, $sStatus);
        return $aContentForFansOnly;
    }
    public function isContentForFansOnly($sModule, $iContentId){
        $aContentForFansOnly = $this->getMarkedContent($sModule, $iContentId, 'active');
        if(empty($aContentForFansOnly)){
           return false; 
        }
        return true;
    }

    //All content based modules
    public function isAllowedView($oModule, $iContentId, $aParams = []){
             $CNF = &$oModule->_oConfig->CNF;

             $isPerformAction = false;

//             if(!empty($aParams))
             if(empty($aParams))
                 $aParams = $oModule->_oDb->getContentInfoById( $iContentId );
                     
             $sModule = $oModule->getName();

             if(!$this->isContentForFansOnly($sModule, $iContentId)){
                return true; //content is for everyone
             }
             
             $iAuthor = 0;  
             if(!empty($CNF["FIELD_AUTHOR"]) && !empty($aParams[$CNF["FIELD_AUTHOR"]])){
                    $iAuthor = $aParams[$CNF["FIELD_AUTHOR"]];
             }
             
             if(bx_get_logged_profile_id() == $iAuthor || $this->isFan($iAuthor) || $this->isFreePass($iAuthor)){ //owners and fans are allowed to view
                return true;
             }
                          
             //moderators and admins are allowed too, maybe premium users too if the admin enabled that feature in permissions app
             $aDataEntry = [];
             if(method_exists($oModule->_oDb, "getContentInfoById")){
                $aDataEntry = $oModule->_oDb->getContentInfoById( $iContentId );
                $isPerformAction = true;
             }

             if(CHECK_ACTION_RESULT_ALLOWED == $this->_checkAllowedDo('view all fansonly content', $aDataEntry, $isPerformAction)){
                return true;
             }            
            
            return false;
             //$canIView = $this->_oModule->isAllowedView($oModule, $aParams); //, $author);
             
    }

    public function isAllowedTimelineItemView($aEvent = []){
             
             //   print "<br><br>checking timeline<br>".json_encode($aEvent, true).'<br>';
             //return true;
             //print json_encode($aEvent, true);

            $isPerformAction = false;

             //$CNF = &$oModule->_oConfig->CNF;
             $aEventObject = $aEvent;

             $sType = $aEventObject["type"];
             if($sType == "timeline_common_repost"){
                $aEventObject = $aEvent['content'];
             }
//             $iAuthor = $this->getTimelineItemOwner($aEvent);
//             $sType = $aEvent["type"];
             $iAuthor = $this->getTimelineItemOwner($aEventObject);
             $sType = $aEventObject["type"];
             
             //TODO, check and fix type to module string
             $sModule = $sType;
//             $iContentId = $aEvent["id"];
//             $iContentId = $aEvent["object_id"];
             $iContentId = $aEventObject["object_id"];

            if($sType == "timeline_common_post"){
                $sModule = "bx_timeline";
                $iContentId = $aEvent["id"];
                $aDataEntry = $aEvent;
                
                //print "checking timeline post<br>";
                
             }else{

                if($sModule == "comment"){
                    return true;
                }
                
                $oModule = BxDolModule::getInstance($sModule);                
                $aDataEntry = $oModule->_oDb->getContentInfoById( $iContentId );
                $isPerformAction = true;
             }

             if(!$this->isContentForFansOnly($sModule, $iContentId)){
                //print "this is not for fans only $sModule, $iContentId <br>";
                
                return true; //content is for everyone
             }

             
             if(bx_get_logged_profile_id() == $iAuthor || $this->isFan($iAuthor) || $this->isFreePass($iAuthor)){ //owners and fans are allowed to view
                // print "not logged in<br>";
               return true;
             }

            //print "checking moderators<br>";
                          
             //moderators and admins are allowed too, maybe premium users too if the admin enabled that feature in permissions app
             if(CHECK_ACTION_RESULT_ALLOWED == $this->_checkAllowedDo('view all fansonly content', $aDataEntry, $isPerformAction)){
                //print "this has permission<br>";
                return true;
             }            
            

            //print "nor permission default false permission<br>";
            return false;

    }

    public function getTimelineItemOwner($aEvent){
        

        if(!empty($aEvent["object_owner_id"])){
            return $aEvent["object_owner_id"];
        }

        return $aEvent["owner_id"];
        
    }
    

    public function blurFromUrl(){
        
        //download photo to local tmp
        //blur tmp file
        //copy photo to onlyfans storage
        //remove tmp file
        //get url of the new photo from storage
        
        /*
        unsetSubscription
		$tmpPath = OW::getPluginManager()->getPlugin('adultphotos')->getPluginFilesDir() . $filename;
		$finalePath = OW::getPluginManager()->getPlugin('adultphotos')->getUserFilesDir() . $filename;
			
        $this->getBlurredImage( $file, $times, $tmpPath);
        
		$storage = OW::getStorage();
		$storage->copyFile($tmpPath, $finalePath);
		@unlink($tmpPath);

        */
        
    }



    public function getBlurredImage( $file, $times = 1, $savepath = false)
    {
        $image = imagecreatefromjpeg($file);

        /* Get original image size */
        list($w, $h) = getimagesize($file);

        /* Create array with width and height of down sized images */
        $size = array('sm'=>array('w'=>intval($w/4), 'h'=>intval($h/4)),
                   'md'=>array('w'=>intval($w/2), 'h'=>intval($h/2))
                  );                       

        /* Scale by 25% and apply Gaussian blur */
        $sm = imagecreatetruecolor($size['sm']['w'],$size['sm']['h']);
        imagecopyresampled($sm, $image, 0, 0, 0, 0, $size['sm']['w'], $size['sm']['h'], $w, $h);

        for ($x=1; $x <=$times; $x++){
            imagefilter($sm, IMG_FILTER_GAUSSIAN_BLUR, 999);
        } 

        imagefilter($sm, IMG_FILTER_SMOOTH,99);
        imagefilter($sm, IMG_FILTER_BRIGHTNESS, 10);        

        /* Scale result by 200% and blur again */
        $md = imagecreatetruecolor($size['md']['w'], $size['md']['h']);
        imagecopyresampled($md, $sm, 0, 0, 0, 0, $size['md']['w'], $size['md']['h'], $size['sm']['w'], $size['sm']['h']);
        imagedestroy($sm);

        for ($x=1; $x <=25; $x++){
            imagefilter($md, IMG_FILTER_GAUSSIAN_BLUR, 999);
        } 

        imagefilter($md, IMG_FILTER_SMOOTH,99);
        imagefilter($md, IMG_FILTER_BRIGHTNESS, 10);        

        /* Scale result back to original size */
        imagecopyresampled($image, $md, 0, 0, 0, 0, $w, $h, $size['md']['w'], $size['md']['h']);
        imagedestroy($md);  

        // Apply filters of upsized image if you wish, but probably not needed
        imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR); 
        imagefilter($image, IMG_FILTER_SMOOTH,99);
        imagefilter($image, IMG_FILTER_BRIGHTNESS, 10);       

        if($savepath){
            imagejpeg($image, $savepath);
        }else{
                header('Content-Type: image/jpeg');
                imagejpeg($image);
        }
        imagedestroy($image);
    }
    




    public function doAudit($iProfileId, $iFanId, $sAction)
    {

        $this->addlog(["doaudit start", $iProfileId, $iFanId, $sAction], true); 

        $oProfile = BxDolProfile::getInstance($iFanId);
        
        $iContentId = $oProfile->getContentId();
        $sModule = $oProfile->getModule();
        $oModule = BxDolModule::getInstance($sModule);
        if (BxDolRequest::serviceExists($sModule, 'act_as_profile') && BxDolService::call($sModule, 'act_as_profile') && $oModule->_oConfig){


            $CNF = $oModule->_oConfig->CNF;

            $aContentInfo = BxDolRequest::serviceExists($sModule, 'get_all') ? BxDolService::call($sModule, 'get_all', array(array('type' => 'id', 'id' => $iContentId))) : array();
        
            $this->addlog(["doaudit acontentinfo", $aContentInfo], true); 

            $AuditParams = array(
                'content_title' => (isset($CNF['FIELD_TITLE']) && isset($aContentInfo[$CNF['FIELD_TITLE']])) ? $aContentInfo[$CNF['FIELD_TITLE']] : '',
                'context_profile_id' => $iProfileId,
                'context_profile_title' => BxDolProfile::getInstance($iProfileId)->getDisplayName()
            );
        
            $this->addlog(["bx_audit", $iContentId, 
                $sModule, 
                $sAction,  
                $AuditParams]);


            bx_audit(
                $iContentId, 
                $sModule, 
                $sAction,  
                $AuditParams
            );


            $this->addlog(["bx_audit after"]);



        }
    }
    
    public function isFeeRequestsEnabled (){
        $bEnabledOn = getParam("ms_fansonly_enablerequests") == 'on' ?  1 : 0 ;             
        return $bEnabledOn;
    }
    public function isPaidButtonsDisabled (){
        $bEnabledOn = getParam("ms_fansonly_disablepaid") == 'on' ?  1 : 0 ;             
        return $bEnabledOn;
    }
    
    //New feature, requests and free access when owner accepts request
    public function isFreePass($iAuthor){
        
        if(!$this->isFeeRequestsEnabled()){
            return false;
        }

        $iProfileId = $iAuthor;
        $iRequesterId = bx_process_input(bx_get_logged_profile_id(), BX_DATA_INT);
        $aRequestSent = $this->_oDb->getRequest($iProfileId, $iRequesterId);
//print '<br><br><br><br>'.json_encode($aRequestSent, true);
        if(!empty($aRequestSent) && $aRequestSent["status"] == "accepted"){
            return true;
        }

        //If ! settings_option_is_enabled{
            //return false
        //}
        //Get content owner
        //If iOwnerId accepted request from iUserId
            //Return true
        
        return false;
    }
    public function addNotification(){
        
    }
    public function getNotification(){
        
    }

    //TODO, FIX ALL OF THIS FOR NOTIFICATIONS



    
    public function serviceGetNotificationsData()
    {
        $sModule = $this->_aModule['name'];

        $sEventPrivacy = $sModule . '_allow_view_event_to';
        if(BxDolPrivacy::getObjectInstance($sEventPrivacy) === false)
                $sEventPrivacy = '';

        $aResult = []; //array('handlers'=>array(), 'settings'=>array(), 'alerts'=>array() );

        $aResult['handlers'] = [
            ['group' => $sModule . '_request', 'type' => 'insert', 'alert_unit' => $sModule.'_request' , 'alert_action' => 'doFreeRequest', 'module_name' => $sModule, 'module_method' => 'get_notifications_request', 'module_class' => 'Module', 'module_event_privacy' => $sEventPrivacy],
            //['group' => $sModule . '_match', 'type' => 'insert', 'alert_unit' => $sModule .'_match' , 'alert_action' => 'doMatch', 'module_name' => $sModule, 'module_method' => 'get_notifications_match', 'module_class' => 'Module', 'module_event_privacy' => $sEventPrivacy],
            ['group' => $sModule . '_requestaccepted', 'type' => 'insert', 'alert_unit' => $sModule.'_requestaccepted' , 'alert_action' => 'doRequestaccepted', 'module_name' => $sModule, 'module_method' => 'get_notifications_requestaccepted', 'module_class' => 'Module', 'module_event_privacy' => $sEventPrivacy],
        ];

        $aResult['settings'] = [
            ['group' => $sModule . '_request', 'unit' => $sModule. '_request' , 'action' => 'doFreeRequest', 'types' => ['personal']],
            //['group' => $sModule . '_match', 'unit' => $sModule.'_match' , 'action' => 'doMatch', 'types' => ['personal']],
            ['group' => $sModule . '_requestaccepted', 'unit' => $sModule.'_requestaccepted' , 'action' => 'doRequestaccepted', 'types' => ['personal']],
        ];
        
        $aResult['alerts'] = [
                                ['unit' => $sModule .'_request', 'action' => 'doFreeRequest'],
                                //['unit' => $sModule.'_match' , 'action' => 'doMatch'],
                                ['unit' => $sModule.'_requestaccepted' , 'action' => 'doRequestaccepted'],            
                            ];

        return $aResult; 
    }

    public function getById ($iId)
    {
        return $this->_oDb->getById((int) $iId);
    }

    //when the user sent a request request, the receiver will get a request request notification.
    public function serviceGetNotificationsRequest($aEvent)
    {
//print 'okokokokokokoko';
    	$CNF = &$this->_oConfig->CNF;
    	$iMediaId = (int)$aEvent['object_id'];        
        $aRequest = $this->getById ( $iMediaId );
        
        $aRequest['author'] = $aRequest["profile_id"]; //$aRequest["senderId"]???
        //TODO, fix this
        

//        $sEntryUrl = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink( $CNF['URL_MANAGE_REQUESTS'] ) . '&id=' . $aRequest['id'];
        $sEntryUrl = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink( $CNF['URL_MANAGE_REQUESTS'] );

        $sEntryCaption = '_ms_fansonly_request_received'; //_t('_ms_fansonly_request_receive_caption'); //accepted or approved
        $sEntrySample = '_ms_fansonly_request_received'; //_t('_ms_fansonly_request_receive_sample'); //accepted or approved
        $sSubEntrySample = '_ms_fansonly_request_received'; //_t('_ms_fansonly_request_receive_subsample'); //accepted or approved
        $sLangKey = '_ms_fansonly_request_received'; //accepted or approved
//        $sLangKey = _t('_ms_fansonly_request_receive'); //accepted or approved
        


        return array(
            'entry_sample' => $sEntrySample, //$CNF['T']['txt_request_response'],
            'entry_url' => $sEntryUrl,
            'entry_caption' => $sEntryCaption,
            'entry_author' => $aRequest['author'],
            'subentry_sample' => $sSubEntrySample, //$CNF['T']['txt_request_request_single'],

            'lang_key' => $sLangKey, //$CNF['T']['txt_request_request_single'],
        );
    }
    


    //when they swipe right, the receiver (who did swipe right after) will get a match notification.
    //this will be used for the one who accepted
    /*
    public function serviceGetNotificationsMatch($aEvent)
    {
    	$CNF = &$this->_oConfig->CNF;
    	$iMediaId = (int)$aEvent['object_id'];        
        $aRequest = $this->getById ( $iMediaId );
        
        $aRequest['author'] = $aRequest["receiverId"];                
        $contentId = $this->getUserContentId($aRequest["senderId"]);        
        $sEntryUrl = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=view-persons-profile&id='.$contentId);           


        $sEntryCaption = _t('_ms_fansonly_media'); //accepted or approved
        
        
        return array(
            'entry_sample' => $CNF['T']['txt_request_response'],
            'entry_url' => $sEntryUrl,
            'entry_caption' => $sEntryCaption,
            'entry_author' => $aRequest['author'],
            'subentry_sample' => $CNF['T']['txt_request_request_single'],
            'lang_key' => $CNF['T']['txt_request_request_match'],
        );
        
    }
    */
    //when they swipe right, the sender will get a match notification.
    //this will be used for the one who did send
    public function serviceGetNotificationsRequestaccepted($aEvent)
    {

    	$CNF = &$this->_oConfig->CNF;
    	$iMediaId = (int)$aEvent['object_id'];        
        $aRequest = $this->getById ( $iMediaId );
        
//        $sEntryCaption = _t('_ms_fansonly_media'); //accepted or approved
        
        $aRequest['author'] = $aRequest["requester_id"];        
        //$iProfileId = $this->getUserContentId($aRequest["profile_id"]);
        //TODO, fix this
       
        $oProfile = BxDolProfile::getInstance($aRequest["profile_id"]);
        if(!$oProfile)
            return '';

        $sEntryUrl = $oProfile->getUrl();  //BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=view-persons-profile&id='.$contentId);       
        //Fixed?
        //TODO, fix this. Change url to match with all modules
        
        $sEntryCaption = '_ms_fansonly_request_accepted'; //_t('_ms_fansonly_request_accepted');
        $sEntrySample = '_ms_fansonly_request_accepted';
        $sSubEntrySample = '_ms_fansonly_request_accepted';
        $sLangKey = '_ms_fansonly_request_accepted';
        

        return array(
            'entry_sample' => $sEntrySample,
            'entry_url' => $sEntryUrl,
            'entry_caption' => $sEntryCaption,
            'entry_author' => $aRequest['author'],
            'subentry_sample' => $sSubEntrySample,
            'lang_key' => $sLangKey,
        );
        
    }

    //Get contentId from userId
    public function getUserContentId($iUserId)
    {
        $oProfile = BxDolProfile::getInstance($iUserId);
        if($oProfile)
        {
            return $oProfile->getContentId();
        }

        return false;
        
    }



    /*
    public function getProfileIdFromGet(){

        $iProfileId = bx_process_input(bx_get('profile_id'), BX_DATA_INT);        

        if(empty($iProfileId)){
            $iProfileId = bx_process_input(bx_get('id'), BX_DATA_INT);
        }
        
        return $iProfileId;
    }

    */

    public function serviceGetUnreadMessagesNum ($iProfileId = 0)
    {

        if (!$iProfileId)
            $iProfileId = bx_get_logged_profile_id();

        return $this->_oDb->getUnreadMessagesNum((int)$iProfileId);
    }

    public function serviceGetLiveUpdates($aMenuItemParent, $aMenuItemChild, $iCount = 0)
    {
        $iProfileId = (int)bx_get_logged_profile_id();
        $iCountNew = $this->_oDb->getUnreadMessagesNum($iProfileId);
        //$iCountNew = (int) '1';
        if($iCountNew == $iCount)
			return false;

        return array(
    		'count' => $iCountNew, // required
    		'method' => 'bx_menu_show_live_update(oData)', // required
    		'data' => array(
    			'code' => BxDolTemplate::getInstance()->parseHtmlByTemplateName('menu_item_addon', array(
    				'content' => '{count}'
                )),
                'mi_parent' => $aMenuItemParent,
                'mi_child' => $aMenuItemChild
    		),  // optional, may have some additional data to be passed in JS method provided using 'method' param above.
    	);
    }


    //$this->triggerAlertAction($aRequest, "doMatchaccepted");
    //$this->triggerAlertAction($aRequest);


    public function triggerAlertAction($aRequest, $type = "doFreeRequest") // "match" "matchaccepted"
    {
        
        $iContentId = $aRequest["id"];
        $aIdsAdded = (int) $iContentId;
        if($type == "doRequestaccepted"){
            
            $alertUnit = 'ms_fansonly_requestaccepted';
            $iUserId = $iProfileId = $aRequest["profile_id"];
            $opponentId =  $aRequest["requester_id"];

        }else if($type == "doFreeRequest"){
             
            $alertUnit = 'ms_fansonly_request';             
            $iUserId = $iProfileId = $aRequest["requester_id"];
            $opponentId =  $aRequest["profile_id"];

        }

        
		bx_alert($alertUnit, $type, 
                    $iContentId, 
                    $iUserId,
                     array(
//                                'object_owner_id' => $opponentId,
                                'object_author_id' => $opponentId,
                                'subobjects_ids' => $aIdsAdded,
                                'medias_added' => $aIdsAdded,
                    ));

    }
        
}






/** @} */
