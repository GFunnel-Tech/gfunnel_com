<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) MSolutions
 * 
 * @defgroup    Fansonly Fansonly module
 * @ingroup     MSolutionsModules
 *
 * @{
 */ 

class MsFansonlyCronPruning extends BxDolCron
{
    protected $_sModule;
    protected $_oModule;

    public function __construct()
    {
        $this->_sModule = 'ms_fansonly';

        $this->_oModule = BxDolModule::getInstance($this->_sModule);

        parent::__construct();
    }
    
    //TODO, FIX THIS TO REMOVE SUBSCRIPTION EXPIRED
	public function addlog($line){ 
        return;        //do nothing, remove this line for debugging only
		$ddl=date("dmy");
		$line = json_encode($line);
        $filename = BX_DIRECTORY_PATH_LOGS ."FansonlyModule-Cron.php";

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
    
    function processing()
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $oPayments = BxDolPayments::getInstance();

        $aSubscriptions = $this->_oModule->_oDb->getSubscriptions(array('type' => 'expired'));
        
        foreach($aSubscriptions as $aSubscription) {
            $this->addLog($aSubscription);
            $aPaymentSubscription = $oPayments->getSubscriptionsInfo(array('subscription_id' => $aSubscription['order']), true);
            if(!empty($aPaymentSubscription) && is_array($aPaymentSubscription)) {
                $aPaymentSubscription = array_shift($aPaymentSubscription);

                $this->addLog($aPaymentSubscription);
                
                //Fix this? no reserve needed
                //$iRecurringReserve = 86400 * (int)getParam($CNF['PARAM_RECURRING_RESERVE']);
                
                if(        !empty($aPaymentSubscription) 
                        && !empty($aPaymentSubscription['status']) 
                        && $aPaymentSubscription['status'] == 'active'
                  ) 
                {
        
                    $this->addLog('updatesuscription');
//                    $this->_oModule->_oDb->updateSubscriptions(array('expired' => $aPaymentSubscription['data']['cperiod_end'] 
                    $this->_oModule->_oDb->updateSubscriptions( array('expired' => $aPaymentSubscription['date_next']
//+ $iRecurringReserve
), array('id' => $aSubscription['id']));
                    continue;
                }
                else {
                    $this->addLog('sendletters');
                    $oPayments->sendSubscriptionExpirationLetters($aPaymentSubscription['pending_id'], $aSubscription['order']);
                    
                    $this->_oModule->unsetSubscription($aSubscription['profile_id'], $aSubscription['fan_id']);
                    
                    //$this->_oModule->_oDb->updateSubscriptions(array('expired' => $aSubscription['expired'] 
//+ $iRecurringReserve
//), array('id' => $aSubscription['id']));
                    
                    continue;
                }
            }
            
//            $this->_oModule->unsetSubscription($aSubscription['group_profile_id'], $aSubscription['fan_id']);
            $this->_oModule->unsetSubscription($aSubscription['profile_id'], $aSubscription['fan_id']);
            //fix removing subscription after buying, it works with recurring and it works with single payments
        }
    }

}

/** @} */
