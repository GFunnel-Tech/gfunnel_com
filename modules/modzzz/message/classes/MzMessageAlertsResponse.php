<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) Modzzz
 * 
 * @defgroup    Profile Message Profile Message 
 * @ingroup     ModzzzModules
 *
 * @{
 */
  
class MzMessageAlertsResponse extends BxBaseModTextAlertsResponse
{
    public function __construct()
    {  
        $this->MODULE = 'mz_message'; 
        parent::__construct();
    }

    public function response($oAlert)
    { 
        parent::response($oAlert);

        $CNF = $this->_oModule->_oConfig->CNF;
 
        if ($oAlert->sUnit == 'profile' && $oAlert->sAction == 'delete') 
		{  
			$this->_oModule->_oDb->purgeMessages($oAlert->iObject);  
		} 
	}
 

  
}

/** @} */
