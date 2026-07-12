<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Auto Friends Auto Friends
 * @ingroup     UNAModules
 *
 * @{
 */

class AqbAfAlertsResponse extends BxBaseModGeneralAlertsResponse
{
    public function __construct()
    {
        $this->MODULE = 'aqb_auto_friends';
        parent::__construct();
    }

    public function response($oAlert)
    {
        $sMethod = 'process' . bx_gen_method_name($oAlert->sUnit . '_' . $oAlert->sAction);
        if(method_exists($this, $sMethod) && $oAlert->aExtras['module'] != 'system')
            $this->$sMethod($oAlert);

        parent::response($oAlert);
    }

    protected function processProfileAdd($oAlert)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $sProfileModule = $oAlert->aExtras['module'];
        if(!in_array($sProfileModule, array_keys($this->_oModule->_oConfig->getSupportedModules())) || in_array($sProfileModule, $this->_oModule->_oConfig->getDisabledFor()))
            return;

        $bFollow = $this->_oModule->_oConfig->isFollow();
        $oConnection = BxDolConnection::getObjectInstance($CNF[$bFollow ? 'OBJECT_CONNECTION_FOLLOW' : 'OBJECT_CONNECTION_FRIENDS']);

        if($this->_oModule->_oConfig->isGeneral())
            $aEntries = $this->_oModule->_oDb->getProfiles();
        else
            $aEntries = $this->_oModule->_oDb->getEntries(array('type' => 'all_for_add'));

        foreach($aEntries as $aEntry) {
            $iInitiator = $aEntry[$CNF['FIELD_PROFILE_ID']];

            if($bFollow) {
                if($oConnection->isConnected($oAlert->iObject, $iInitiator))
                    continue;

                $oConnection->addConnection($oAlert->iObject, $iInitiator);
            }
            else {
                if($oConnection->isConnected($iInitiator, $oAlert->iObject, true))
                    continue;

                $oConnection->addConnection($iInitiator, $oAlert->iObject);
                if(!$this->_oModule->_oConfig->isRequest())
                    $oConnection->addConnection($oAlert->iObject, $iInitiator);
            }
        }
    }
}

/** @} */
