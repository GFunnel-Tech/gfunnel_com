<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) MSolutions
 * 
 * @defgroup    Fansonly Fansonly module
 * @ingroup     MSolutionsModules
 *
 * @{
 */

bx_import('BxDolModuleDb');
class MsFansonlyDb extends BxDolModuleDb
{

    protected $_oModule;    
    protected $_oConfig;
    
    public function __construct(&$oConfig)
    {
        parent::__construct($oConfig);

    }
    
    public function getContentInfoById($iSellerId){

        $oProfile = BxDolProfile::getInstance($iSellerId);
        return $oProfile->getInfo();        

    }
        
	public function selectSysForms()
    {

        $sQuery = $this->prepare("SELECT * FROM `sys_form_displays` WHERE (`display_name` like '%entry%' OR `display_name` like '%_post_%') AND `display_name` not like '%delete%' AND `display_name` not like '%view%' AND `display_name` <> 'bx_photos_entry_edit' GROUP BY `object`" );

        return $this->getAll($sQuery);
        
    }
    public function getAlertHandler(){        
        $sSql = $this->prepare("SELECT * FROM `sys_alerts_handlers` WHERE `name` = 'ms_fansonly' " );        
        return $this->getRow($sSql);        
    }

    public function getSysAlert($sUnit, $sAction, $iAlertHandler){

        $sSql = $this->prepare("SELECT * FROM `sys_alerts` WHERE `unit` = ? AND `action` = ? AND `handler_id` = ? ", $sUnit, $sAction, $iAlertHandler );        
        return $this->getRow($sSql);        
        
    }

    public function resetSysObjectsHadlers( $aFormObject ){
        $aHandler = $this->getAlertHandler();
        if($aHandler){
            $iAlertHandler = $aHandler["id"];
            
//     * $s = "SELECT * FROM `t` WHERE `a` IN (" . $oDb->implode_escape($a) . ")";

            if(!empty($aFormObject) && is_array( $aFormObject )){
                
                //fix for timeline posts
                if (in_array("bx_timeline_post", $aFormObject)) {
                    unset($aFormObject["bx_timeline_post"]);
                    $aFormObject = array_push($aFormObject, "bx_timeline");
                }
                                
                $sSql = $this->prepare("DELETE FROM `sys_alerts` WHERE `handler_id`=? AND `unit` NOT IN (" . $this->implode_escape($aFormObject) . ") ", $iAlertHandler);
                //print "DELETE FROM `sys_alerts` WHERE `handler_id`=? AND `unit` NOT IN (" . $this->implode_escape($aFormObject) . ") <br>"; 

                //DELETE FROM `sys_objects_connection` WHERE `object` IN('bx_market_fans');
                //print json_encode($sSql->queryString, true).'<br><br>';                
                //return (int)$this->query($sSql) > 0;
                
                //$this->query($sSql);
            }
        }

    }
    
    public function setSysObjectsHandlers( $sObject ){
        $aActions = ["added", "deleted", "edited"];

        //fix for timeline posts
        if($sObject == "bx_timeline_post"){
            $sObject = "bx_timeline"; 
            $aActions = ["post_common", "delete"];

            //lets update the table 'sys_objects_content_info', to make alerts work
            
//            $sUpdateQuery = $this->prepare("UPDATE `sys_objects_content_info` SET `alert_action_update`= ? WHERE `name` = ? AND `alert_unit` = ? ", 'post_common_edited', 'bx_timeline', 'bx_timeline');            
//            $this->query($sUpdateQuery);

            //$aWhere = ['name' => 'bx_timeline', 'alert_unit' => 'bx_timeline', 'alert_action_update' => ''];
            //$aSet = ['alert_action_update' => 'post_common_edited'];
            //$sWhereClause = $this->arrayToSQL($aWhere, ' AND ');
            //$r = $this->query("UPDATE `sys_objects_content_info` SET " . $this->arrayToSQL($aSet) . " WHERE " . $sWhereClause);
            //print json_encode($r, true); exit;
            //AND `alert_action_update` is NULL             
        }
        

        if($sObject == "bx_photos_upload"){
            $sObject = "bx_photos"; 
        }

        $sUnit = $sObject;
        
        $aHandler = $this->getAlertHandler();
        if($aHandler){
            $iAlertHandler = $aHandler["id"];
            
            foreach($aActions as $sAction){
                $aExistsHandler = $this->getSysAlert($sUnit, $sAction, $iAlertHandler);
                if(empty($aExistsHandler)){
                     
         $sQuery = $this->prepare("INSERT INTO `sys_alerts` (`unit`, `action`, `handler_id`) VALUES(?, ?, ?)",  $sUnit, $sAction, $iAlertHandler);

                     //print json_encode($sQuery->queryString, true) .'<br>';
                    $this->query($sQuery);
                    //return (int)$this->query($sQuery) > 0 ? $this->lastId() : false;
                    
                }
            }
        }
        
    }


    public function getMarkedContent( $sUnit, $iObject, $sStatus = ''){
        if(!empty($sStatus)){
            $sSql = $this->prepare("SELECT * FROM `ms_fansonly_private_content` WHERE `name` = ? AND `content` = ? AND `status` = ?", $sUnit, $iObject, $sStatus );
        }else{
            $sSql = $this->prepare("SELECT * FROM `ms_fansonly_private_content` WHERE `name` = ? AND `content` = ? ", $sUnit, $iObject );        
        }
        return $this->getRow($sSql);        
        
    }
    //getContentMarked($sModule, $iContentId, $status);

    public function markForFansOnly( $sUnit, $iObject ){
        
        $aMarked = $this->getMarkedContent($sUnit, $iObject);
        
        $added = time();
        $updated = $added;
        
        if(empty($aMarked)){
            $sQuery = $this->prepare("INSERT INTO `ms_fansonly_private_content` (`module`, `name`, `content`, `added`, `updated`) VALUES(?, ?, ?, ?, ?)",  $sUnit, $sUnit, $iObject, $added, $updated);
        }else{
            $sQuery = $this->prepare("UPDATE `ms_fansonly_private_content` SET `updated`= ?, `status`= ? WHERE id = ?",  $updated, 'active', $aMarked["id"]);            
        }

        $this->query($sQuery);

    }
    
    public function unMarkForFansOnly( $sUnit, $iObject ){        
        $aMarked = $this->getMarkedContent($sUnit, $iObject);        
        $added = time();
        $updated = $added;

        if(empty($aMarked)){
            //$added = time();
            //$updated = $added;
            //do not add the content
            //$sQuery = $this->prepare("INSERT INTO `ms_fansonly_private_content` (`module`, `name`, `content`, `added`, `updated`, `status`) VALUES(?, ?, ?, ?, ?, ?)",  $sUnit, $sAction, $iAlertHandler, $added, $updated, 'hidden');
        }else{
            $sQuery = $this->prepare("UPDATE `ms_fansonly_private_content` SET `updated`= ?,  `status`= ? WHERE id = ?",  $updated, 'hidden', $aMarked["id"]);            

            $this->query($sQuery);

        }
    }
    
/*

`ms_fansonly_private_content` 
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `module` varchar(32) NOT NULL,
      `name` varchar(128) NOT NULL,
      `content` int(11) NOT NULL,
      `added` int(10) unsigned NOT NULL,
      `updated` int(10) unsigned NOT NULL,
      `status` enum('active','hidden') NOT NULL DEFAULT 'active',  
      `status_admin` enum('active','hidden') NOT NULL DEFAULT 'active',  



*/

    /*
    function deleteKey($iKeyId, $iLanguageId=0)
    {
        $sSql = $this->prepare("DELETE FROM `msolutions_prettylinks_objects` WHERE `id`=? ", $iKeyId);
        return (int)$this->query($sSql) > 0;
    }

    public function getByFansonly( $sName, $status=false )
    {

        if($status){
            $sSql = $this->prepare("SELECT * FROM `ms_fansonly_fansonly` WHERE `fansonly` = ? AND `status` = ? LIMIT 1", $sName, $status);
        }else
        {
                $sSql = $this->prepare("SELECT * FROM `ms_fansonly_fansonly` WHERE `fansonly` = ? LIMIT 1", $sName);
        }
        
        return $this->getRow($sSql);
        
    }
    
    public function getByProfileID( $iID )
    {
        $sSql = $this->prepare("SELECT * FROM `ms_fansonly_fansonly` WHERE `profile_id` = ? LIMIT 1", $iID);
        
        return $this->getRow($sSql);

    }    
    
    function addFansonly($iProfileId, $sFansonly='', $status='active')
    {

        $sQuery = $this->prepare("INSERT INTO `ms_fansonly_fansonly` (`profile_id`, `fansonly`, `status`) VALUES(?, ?, ?)",  $iProfileId, $sFansonly, $status);

        return (int)$this->query($sQuery) > 0 ? $this->lastId() : false;
        
    }

    function updateFansonly($iProfileId, $sFansonly='', $status='active')
    {
        
        //bx_get_logged_profile_id        
        $sOldFansonly = '';
//        if (isAdmin()){
        if (!isAdmin()){
            $iLimit = (int) getParam("ms_fansonly_editlimit");
//            $iLimit = 1; //for testing
            if($iLimit>0){  //we have a limit, lets find out if we did reach the limit
                $aPreviouschanged = $this->getFansonlyChanged($iProfileId);
                if(!empty($aPreviouschanged) && is_array($aPreviouschanged) && count($aPreviouschanged) >= $iLimit){
                    return false;
                }else{
                    //we can change the fansonly, limit has not been reached
                    //ms_fansonly_editlimit                
                    $aPreviousFansonly = $this->getByProfileID( $iProfileId );
                    if(!empty($aPreviousFansonly['fansonly'])){
                        $sOldFansonly = $aPreviousFansonly['fansonly'];

                        $this->trackFansonlyChanged($sOldFansonly, $iProfileId);
                    }
                }
            }
        }
        //        $iProfileId = getLoggedId();
        //        if($aContentInfo[$CNF['FIELD_AUTHOR']] == bx_get_logged_profile_id() || $this->_isModerator())
        //edit only if we didn't reach the limit
        
        $sQuery = $this->prepare("UPDATE `ms_fansonly_fansonly` SET `fansonly`= ?, `status`= ? WHERE `profile_id`=?", $sFansonly, $status, $iProfileId);

        $updated = (int)$this->query($sQuery) > 0 ? $this->lastId() : false;

        return $updated;        
    }
    
    //save old fansonly into table
    public function trackFansonlyChanged($sOldFansonly, $iProfileId)
    {
        $sQuery = $this->prepare("INSERT INTO `ms_fansonly_changes` (`profile_id`, `oldfansonly`) VALUES(?, ?)",  $iProfileId, $sOldFansonly);
        return (int)$this->query($sQuery) > 0 ? $this->lastId() : false;
        
    }
    public function getFansonlyChanged($iProfileId)
    {
        $sSql = $this->prepare("SELECT * FROM `ms_fansonly_changes` WHERE `profile_id` = ? LIMIT 1", $iProfileId);
        //oldfansonly
        //$this->getOne($sQuery);
        return $this->getAll($sSql);
        //return $this->getRow($sSql);
    }

    */













    //Payments

    //Groups have roles, we have connections, instead of roles, so we are adding a connection for the user fan if they paid
    
    public function getSubscription($iUserProfileId, $iProfileId)
    {
        $this->_oModule = BxDolModule::getInstance('ms_fansonly');
        $this->_oConfig = $this->_oModule->_oConfig;

        return (int)$this->getSubscriptions(array('type' => 'by_pf_id', 'profile_id' => $iUserProfileId, 'fan_id' => $iProfileId));
//        return (int)$this->getSubscriptions(array('type' => 'by_pf_id', 'profile_id' => $iUserProfileId, 'fan_id' => $iProfileId, 'subscription_only' => true));
    }

    public function getSubscriptionRow($iUserProfileId, $iProfileId)
    {
        $this->_oModule = BxDolModule::getInstance('ms_fansonly');
        $this->_oConfig = $this->_oModule->_oConfig;

        return $this->getSubscriptions(array('type' => 'by_pf_id_row', 'profile_id' => $iUserProfileId, 'fan_id' => $iProfileId));
//        return (int)$this->getSubscriptions(array('type' => 'by_pf_id', 'profile_id' => $iUserProfileId, 'fan_id' => $iProfileId, 'subscription_only' => true));
    }
    
    public function getSubscriptions($aParams)
    {
        $this->_oModule = BxDolModule::getInstance('ms_fansonly');
        $this->_oConfig = $this->_oModule->_oConfig;

        $aMethod = array('name' => 'getAll', 'params' => array(0 => 'query'));

        $sSelectClause = "`ta`.*";
        $sJoinClause = $sWhereClause = $sOrderClause = $sLimitClause = "";
        switch($aParams['type']) {
            case 'by_id':
                $aMethod['name'] = 'getRow';
                $aMethod['params'][1] = array(
                    'id' => $aParams['value']
                );

                $sWhereClause = "AND `ta`.`id`=:id";
                break;

            case 'by_pf_id':
                $aMethod['name'] = 'getRow';
            	$aMethod['params'][1] = array(
                    'profile_id' => $aParams['profile_id'],
                    'fan_id' => $aParams['fan_id'],
                );

                $sWhereClause = "AND `profile_id` = :profile_id AND `fan_id` = :fan_id";
                $sLimitClause = 1;

//                if(!empty($aParams['subscription_only'])) {
                    //$aMethod['name'] = 'getOne';
                    //$sSelectClause = "`ta`.`subscription`";
//                }
                break;

            case 'by_pf_id_row':

                //$aMethod['name'] = 'getRow';
            	$aMethod['params'][1] = array(
                    'profile_id' => $aParams['profile_id'],
                    'fan_id' => $aParams['fan_id'],
                );

                $sWhereClause = "AND `profile_id` = :profile_id AND `fan_id` = :fan_id";
                //$sLimitClause = 1;

//                if(!empty($aParams['subscription_only'])) {
                    //$aMethod['name'] = 'getOne';
                    //$sSelectClause = "`ta`.`subscription`";
//                }
                break;

            case 'expired':
                $sWhereClause .= "AND `ta`.`added` < UNIX_TIMESTAMP() AND `ta`.`expired` <> 0 AND `ta`.`expired` < UNIX_TIMESTAMP()";
                break;
            case 'notexpired':
                $sWhereClause .= "AND (`ta`.`added` < UNIX_TIMESTAMP() AND ( `ta`.`expired` = 0 OR `ta`.`expired` > UNIX_TIMESTAMP()  ) )";
                break;
        }

        if(!empty($sOrderClause))
            $sOrderClause = "ORDER BY " . $sOrderClause;
        
        if(!empty($sLimitClause))
            $sLimitClause = "LIMIT " . $sLimitClause;

        $sSql = "SELECT {select} FROM `" . $this->_oConfig->CNF['TABLE_SUBSCRIPTIONS'] . "` AS `ta` " . $sJoinClause . " WHERE 1 " . $sWhereClause . " {order} {limit}";

        $aMethod['params'][0] = str_replace(array('{select}', '{order}', '{limit}'), array($sSelectClause, $sOrderClause, $sLimitClause), $sSql);
        return call_user_func_array(array($this, $aMethod['name']), $aMethod['params']);
    }

    public function updateSubscriptions($aSet, $aWhere)
    {
        $this->_oModule = BxDolModule::getInstance('ms_fansonly');
        $this->_oConfig = $this->_oModule->_oConfig;

        $sWhereClause = 1;
        if(!empty($aWhere))
            $sWhereClause = $this->arrayToSQL($aWhere, ' AND ');

        return (int)$this->query("UPDATE `" . $this->_oConfig->CNF['TABLE_SUBSCRIPTIONS'] . "` SET " . $this->arrayToSQL($aSet) . " WHERE " . $sWhereClause) > 0;
    }
    
    public function deleteSubscriptions($aWhere)
    {
        $this->_oModule = BxDolModule::getInstance('ms_fansonly');
        $this->_oConfig = $this->_oModule->_oConfig;

    	if(empty($aWhere))
            return false;

        return (int)$this->query("DELETE FROM `" . $this->_oConfig->CNF['TABLE_SUBSCRIPTIONS'] . "` WHERE " . $this->arrayToSQL($aWhere, ' AND ')) > 0;
    }
    
/*
        if((int)$aItemInfo['period'] != 0)
            $mixedPeriod = array(
                'period' => (int)$aItemInfo['period'], 
                'period_unit' => $aItemInfo['period_unit'], 
                'period_reserve' => $CNF['PARAM_RECURRING_RESERVE']
            );

        if(!$this->setSubscription($aItemInfo['profile_id'], $iClientId, $aItemInfo['subscription_id'], $mixedPeriod, $sOrder))

*/


    public function setSubscription ($iUserProfileId, $iProfileId, $mixedPeriod = false, $sOrder = '')
    {
        $this->_oModule->addlog(["setSubscription start"]);
        $this->_oModule = BxDolModule::getInstance('ms_fansonly');
        $this->_oConfig = $this->_oModule->_oConfig;

        $CNF = &$this->_oConfig->CNF;

        $this->_oModule->addlog(["setSubscription afterCNF"]);


        /*
        if($this->_oConfig->isMultiSubscriptions() && is_array($mixedSubscription)) {
            if(count($mixedSubscription) != 1 || current($mixedSubscription) != 0) {
                $iProfileSubscription = 0;

                foreach($mixedSubscription as $iSubscription)
                    $iProfileSubscription = $iProfileSubscription | pow(2, ($iSubscription - 1));

                $mixedSubscription = $iProfileSubscription;
            }
            else 
                $mixedSubscription = 0;
        }
        */
        $sDate = time();
        $aBindings = array(
            'profile_id' => $iUserProfileId,
            'fan_id' => $iProfileId,
//            'subscription' => $mixedSubscription,
            'added' => $sDate
        );

        


        $this->_oModule->addlog(["setSubscription afteraBindings", $aBindings]);

//        $sSetClause = "`profile_id` = :profile_id, `fan_id` = :fan_id, `subscription` = :subscription, `added` = :added";
        $sSetClause = "`profile_id` = :profile_id, `fan_id` = :fan_id, `added` = :added";


        /*
        $sExpired = '';


        $sExpired = strtotime("+1 week", $sDate);
        $sExpired = strtotime("+1 Month", $sDate);
        $sExpired = strtotime("+1 year", $sDate);

        


        if(!empty($mixedPeriod)) {
                if(is_numeric($mixedPeriod)){
                	$mixedPeriod = array('period' => (int)$mixedPeriod, 'period_unit' => BX_BASE_MOD_FANSONLY_PERIOD_UNIT_DAY);
                    $sExpired = strtotime("+1 day", $sDate);
                }
        }
        
        $aBindings['expired'] = $sExpired;

        $sSetClause .= ", `expired` = :expired";
        */
        $this->_oModule->addlog(["setSubscription ssetClause", $sSetClause]);
        
        if(!empty($mixedPeriod)) {
                if(is_numeric($mixedPeriod))
                	$mixedPeriod = array('period' => (int)$mixedPeriod, 'period_unit' => BX_BASE_MOD_FANSONLY_PERIOD_UNIT_DAY);

                $aBindings['period'] = (int)$mixedPeriod['period'];
                
                //$aBindings['period_unit'] = (int)$mixedPeriod['period_unit'];
                //$sSetClause .= ", `period` = :period, `period_unit` = :period_unit,";
                //$sSetClause .= ", `period` = :period, `period_unit` = :period_unit,";

                //$sSetExpired = "";
                switch($mixedPeriod['period_unit']) {
                    case BX_BASE_MOD_FANSONLY_PERIOD_UNIT_DAY:
                        $sSetExpired = "DATE_ADD(FROM_UNIXTIME(:added), INTERVAL :period DAY)";
                        
                    case BX_BASE_MOD_FANSONLY_PERIOD_UNIT_WEEK:
                        //if($mixedPeriod['period_unit'] == BX_BASE_MOD_FANSONLY_PERIOD_UNIT_WEEK)
//                            $aBindings['period'] *= 7;
                        $sSetExpired = "DATE_ADD(FROM_UNIXTIME(:added), INTERVAL :period DAY)";
                        break;

                    case BX_BASE_MOD_FANSONLY_PERIOD_UNIT_MONTH:
                        $sSetExpired = "DATE_ADD(FROM_UNIXTIME(:added), INTERVAL :period MONTH)";
                        break;

                    case BX_BASE_MOD_FANSONLY_PERIOD_UNIT_YEAR:
                        $sSetExpired = "DATE_ADD(FROM_UNIXTIME(:added), INTERVAL :period YEAR)";
                        break;
                }
                /*
                    if(!empty($sSetExpired) && !empty($mixedPeriod['period_reserve'])) {
                        $aBindings['reserve'] = (int)$mixedPeriod['period_reserve'];

                        $sSetExpired = "DATE_ADD(" . $sSetExpired . ", INTERVAL :reserve DAY)";
                    }
                */
                if(!empty($sSetExpired))
                    $sSetClause .= ", `expired` = UNIX_TIMESTAMP(" . $sSetExpired . ")";
        }
        
        $this->_oModule->addlog(["setSubscription ssetClause 2 ", $sSetClause]);
        
        if(!empty($sOrder)) {
            $aBindings['order'] = $sOrder;
            
            $sSetClause .= ", `order` = :order";
        }

//        return !$this->query("REPLACE INTO `" . $CNF['TABLE_SUBSCRIPTIONS'] . "` SET " . $sSetClause, $aBindings) ? false : true;
        $this->_oModule->addlog(["SQL", "REPLACE INTO `" . $CNF['TABLE_SUBSCRIPTIONS'] . "` SET " . $sSetClause, $aBindings]);
        
        return !$this->query("REPLACE INTO `" . $CNF['TABLE_SUBSCRIPTIONS'] . "` SET " . $sSetClause, $aBindings) ? false : true;
    }

    public function unsetSubscription($iUserProfileId, $iProfileId)
    {
        return $this->deleteSubscriptions(array('profile_id' => $iUserProfileId, 'fan_id' => $iProfileId));
    }

    public function getPrices($aParams)
    {
        $this->_oModule = BxDolModule::getInstance('ms_fansonly');
        $this->_oConfig = $this->_oModule->_oConfig;

        $aMethod = array('name' => 'getAll', 'params' => array(0 => 'query'));

        $sSelectClause = "`tp`.*";
        $sJoinClause = $sWhereClause = $sOrderClause = $sLimitClause = "";

        if(!isset($aParams['order']) || empty($aParams['order']))
            $sOrderClause = "ORDER BY `tp`.`Order` ASC";

        switch($aParams['type']) {
            case 'by_id':
                $aMethod['name'] = 'getRow';
                $aMethod['params'][1] = array(
                    'id' => $aParams['value']
                );

                $sWhereClause .= "AND `tp`.`id`=:id";
                break;

            case 'by_name':
                $aMethod['name'] = 'getRow';
                $aMethod['params'][1] = array(
                    'id' => $aParams['value']
                );

                $sWhereClause .= "AND `tp`.`name`=:name";
                break;

            case 'by_profile_id':
            	$aMethod['params'][1] = array(
                    'profile_id' => $aParams['profile_id']
                );

                $sWhereClause .= "AND `tp`.`profile_id`=:profile_id";
                break;

            case 'by_prpp':
                $aMethod['name'] = 'getRow';
                $aMethod['params'][1] = array(
                    'profile_id' => $aParams['profile_id'],
//                    'subscription_id' => $aParams['subscription_id'],
                    'period' => $aParams['period'],
                    'period_unit' => $aParams['period_unit'],
                );

                $sWhereClause .= "AND `tp`.`profile_id`=:profile_id AND `tp`.`period`=:period AND `tp`.`period_unit`=:period_unit";
//                $sWhereClause .= "AND `tp`.`profile_id`=:profile_id AND `tp`.`subscription_id`=:subscription_id AND `tp`.`period`=:period AND `tp`.`period_unit`=:period_unit";
                break;
        }
        
        $sSql = "SELECT {select} FROM `" . $this->_oConfig->CNF['TABLE_PRICES'] . "` AS `tp` " . $sJoinClause . " WHERE 1 " . $sWhereClause . " {order} {limit}";

        $aMethod['params'][0] = str_replace(array('{select}', '{order}', '{limit}'), array($sSelectClause, $sOrderClause, $sLimitClause), $sSql);
        return call_user_func_array(array($this, $aMethod['name']), $aMethod['params']);
    }

    public function getPriceOrderMax()
    {
        $this->_oModule = BxDolModule::getInstance('ms_fansonly');
        $this->_oConfig = $this->_oModule->_oConfig;
        return (int)$this->getOne("SELECT MAX(`order`) FROM `" . $this->_oConfig->CNF['TABLE_PRICES'] . "` ", array());

//        return (int)$this->getOne("SELECT MAX(`order`) FROM `" . $this->_oConfig->CNF['TABLE_PRICES'] . "` WHERE `subscription_id`=:subscription_id", array(
//            'subscription_id' => $iSubscriptionId
//        ));
    }

    public function deletePrices($aWhere)
    {
        $this->_oModule = BxDolModule::getInstance('ms_fansonly');
        $this->_oConfig = $this->_oModule->_oConfig;

        return (int)$this->query("DELETE FROM `" . $this->_oConfig->CNF['TABLE_PRICES'] . "` WHERE " . $this->arrayToSQL($aWhere, " AND ")) > 0;
    }
    

/*

    public function getProductsNames ($iVendor = 0, $iLimit = 1000)
    {
        $aBindings = array('limit' => $iLimit);
        return $this->getColumn("SELECT `name` FROM `ms_fansonly_level_prices` WHERE 1 LIMIT :limit", $aBindings);
    }

	public function getLevels($aParams, $bReturnCount = false)
    {
        $aMethod = array('name' => 'getAll', 'params' => array(0 => 'query'));
        $sSelectClause = $sJoinClause = $sWhereClause = $sOrderClause = $sLimitClause = "";

        $sSelectClause = "
			`tal`.`ID` AS `id`,
			`tal`.`Name` AS `name`,
			`tal`.`Icon` AS `icon`,
			`tal`.`Description` AS `description`,
			`tal`.`Active` AS `active`,
			`tal`.`Purchasable` AS `purchasable`,
			`tal`.`Removable` AS `removable`,
			`tal`.`QuotaSize` AS `quota_size`,
			`tal`.`QuotaNumber` AS `quota_number`,
			`tal`.`QuotaMaxFileSize` AS `quota_max_file_size`,
			`tal`.`Order` AS `order`";

        if(!isset($aParams['order']) || empty($aParams['order']))
            $sOrderClause = "ORDER BY `tal`.`Order` ASC";

        switch($aParams['type']) {
        	case 'by_id':
        		$aMethod['name'] = 'getRow';
                $aMethod['params'][1] = array(
                	'id' => $aParams['value']
                );

                $sWhereClause .= "AND `tal`.`id`=:id";
        		break;

            case 'for_selector':
                $aMethod['name'] = 'getPairs';
                $aMethod['params'][1] = 'id';
                $aMethod['params'][2] = 'name';
                $sWhereClause .= "AND `tal`.`Active`='yes' AND (`tal`.`Purchasable`='yes' OR `tal`.`Removable`='yes')";
                break;
        }

        $sSql = "SELECT {select} FROM `sys_acl_levels` AS `tal` " . $sJoinClause . " WHERE 1 " . $sWhereClause . " {order} {limit}";

        $aMethod['params'][0] = str_replace(array('{select}', '{order}', '{limit}'), array($sSelectClause, $sOrderClause, $sLimitClause), $sSql);
        $aItems = call_user_func_array(array($this, $aMethod['name']), $aMethod['params']);

        if(!$bReturnCount)
            return $aItems;

		$aMethod['name'] = 'getOne';
		$aMethod['params'][0] = str_replace(array('{select}', '{order}', '{limit}'), array("COUNT(*)", "", ""), $sSql);

		return array(
			'items' => $aItems, 
			'count' => (int)call_user_func_array(array($this, $aMethod['name']), $aMethod['params'])
		);
    }

	public function updateLevels($aSet, $aWhere)
    {
    	$sSql = "UPDATE `sys_acl_levels` SET " . $this->arrayToSQL($aSet, " AND ") . " WHERE " . $this->arrayToSQL($aWhere, " AND ");
        return (int)$this->query($sSql) > 0;
    }

	public function getPrices($aParams, $bReturnCount = false)
    {
        $aMethod = array('name' => 'getAll', 'params' => array(0 => 'query'));
        $sSelectClause = $sJoinClause = $sWhereClause = $sOrderClause = $sLimitClause = "";

        $sSelectClause = "
			`tap`.`id` AS `id`,
			`tap`.`level_id` AS `level_id`,
			`tap`.`name` AS `name`,
			`tap`.`period` AS `period`,
			`tap`.`period_unit` AS `period_unit`,
			`tap`.`price` AS `price`,
			`tap`.`trial` AS `trial`,
			`tap`.`order` AS `order`";

        if(!isset($aParams['order']) || empty($aParams['order']))
            $sOrderClause = "ORDER BY `tap`.`Order` ASC";

        switch($aParams['type']) {
            case 'by_id':
                $aMethod['name'] = 'getRow';
                $aMethod['params'][1] = array(
                    'id' => $aParams['value']
                );

                $sWhereClause .= "AND `tap`.`id`=:id";
                break;

            case 'by_id_full':
                $aMethod['name'] = 'getRow';
                $aMethod['params'][1] = array(
                    'id' => $aParams['value']
                );

                $sSelectClause .= ", `tal`.`Name` AS `level_name`, `tal`.`Description` AS `level_description`";
                $sJoinClause .= "LEFT JOIN `sys_acl_levels` AS `tal` ON `tap`.`level_id`=`tal`.`ID`";
                $sWhereClause .= "AND `tap`.`id`=:id";
                break;

            case 'by_name_full':
                $aMethod['name'] = 'getRow';
                $aMethod['params'][1] = array(
                    'name' => $aParams['value']
                );

                $sSelectClause .= ", `tal`.`Name` AS `level_name`, `tal`.`Description` AS `level_description`";
                $sJoinClause .= "LEFT JOIN `sys_acl_levels` AS `tal` ON `tap`.`level_id`=`tal`.`ID`";
                $sWhereClause .= "AND `tap`.`name`=:name";
                break;

            case 'by_level_id':
            	$aMethod['params'][1] = array(
                    'level_id' => $aParams['value']
                );

                $sWhereClause .= "AND `tap`.`level_id`=:level_id";
                break;

            case 'by_level_id_duration':
                $aMethod['name'] = 'getRow';
                $aMethod['params'][1] = array(
                    'level_id' => $aParams['level_id'],
                    'period' => $aParams['period'],
                    'period_unit' => $aParams['period_unit'],
                );

                $sWhereClause .= "AND `tap`.`level_id`=:level_id AND `tap`.`period`=:period AND `tap`.`period_unit`=:period_unit";
                break;

            case 'all_full':
                $sSelectClause .= ", `tal`.`Name` AS `level_name`, `tal`.`Description` AS `level_description`";
                $sJoinClause .= "LEFT JOIN `sys_acl_levels` AS `tal` ON `tap`.`level_id`=`tal`.`ID`";
                break;
        }

        $sSql = "SELECT {select} FROM `" . $this->_oConfig->CNF['TABLE_PRICES'] . "` AS `tap` " . $sJoinClause . " WHERE 1 " . $sWhereClause . " {order} {limit}";

        $aMethod['params'][0] = str_replace(array('{select}', '{order}', '{limit}'), array($sSelectClause, $sOrderClause, $sLimitClause), $sSql);
        $aItems = call_user_func_array(array($this, $aMethod['name']), $aMethod['params']);

        if(!$bReturnCount)
            return $aItems;

		$aMethod['name'] = 'getOne';
		$aMethod['params'][0] = str_replace(array('{select}', '{order}', '{limit}'), array("COUNT(*)", "", ""), $sSql);

        return array(
        	'items' => $aItems, 
        	'count' => (int)call_user_func_array(array($this, $aMethod['name']), $aMethod['params'])
        );
    }

    public function getPriceOrderMax($iLevelId)
    {
        $sSql = $this->prepare("SELECT MAX(`order`) FROM `" . $this->_oConfig->CNF['TABLE_PRICES'] . "` WHERE `level_id`=?", $iLevelId);
        return (int)$this->getOne($sSql);
    }

    public function deletePrices($aWhere)
    {
    	$sSql = "DELETE FROM `" . $this->_oConfig->CNF['TABLE_PRICES'] . "` WHERE " . $this->arrayToSQL($aWhere, " AND ");
        return (int)$this->query($sSql) > 0;
    }
    */

    public function getRequest($iProfileId = 0, $iRequesterId = 0){
        $sSql = $this->prepare("SELECT * FROM `ms_fansonly_requests` WHERE `profile_id` = ? AND `requester_id` = ? LIMIT 1", $iProfileId, $iRequesterId);
//        return $this->getAll($sSql);                
        return $this->getRow($sSql);                
    }
    
    public function addRequest($iProfileId = 0, $iRequesterId = 0){
        $aRequest = $this->getRequest($iProfileId, $iRequesterId);
        if(!empty($aRequest)){
            return;
        }

        

//        if(empty($aMarked)){
            $sQuery = $this->prepare("INSERT INTO `ms_fansonly_requests` (`profile_id`, `requester_id`) VALUES(?, ?)",  $iProfileId, $iRequesterId);
//        }else{
//            $sQuery = $this->prepare("UPDATE `ms_fansonly_private_content` SET `updated`= ?, `status`= ? WHERE id = ?",  $updated, 'active', $aMarked["id"]);            
//        }

        $this->query($sQuery);



    }
    public function updateRequest($iRequest, $sStatus = 'pending'){

        $sQuery = $this->prepare("UPDATE `ms_fansonly_requests` SET `status`= ? WHERE id = ?", $sStatus, $iRequest);            

//        $sQuery = $this->prepare("INSERT INTO `ms_fansonly_requests` (`profile_id`, `requester_id`) VALUES(?, ?)",  $iProfileId, $iRequesterId);
        return (int)$this->query($sQuery) > 0;

        //$updated = (int)$this->query($sQuery) > 0 ? $this->lastId() : false;

        //return $updated;  

        //$this->query($sQuery);

    }
    public function getUnreadMessagesNum($iUserId)
    {
        $this->_oModule = BxDolModule::getInstance('ms_fansonly');
        $this->_oConfig = $this->_oModule->_oConfig;

        $sQuery = $this->prepare("SELECT COUNT(`id`) FROM `" . $this->_oConfig->CNF['TABLE_REQUESTS'] . "` WHERE `profile_id` = ? AND `is_read`= 0", $iUserId);

        return $this->getOne($sQuery);

    }


    public function getById ( $iId )
    {
        
        $this->_oModule = BxDolModule::getInstance('ms_fansonly');
        $this->_oConfig = $this->_oModule->_oConfig;

        $sQuery = $this->prepare("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_REQUESTS'] . "` WHERE `id` = ?", $iId);
        
        return $this->getRow($sQuery);
        
    }


}



/** @} */
