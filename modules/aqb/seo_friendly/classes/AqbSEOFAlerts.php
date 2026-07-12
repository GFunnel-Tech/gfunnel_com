<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    JamiiTrade JamiiTrade
 * @ingroup     UnaModules
 *
 * @{
 */

class AqbSEOFAlerts extends BxDolAlertsResponse
{
    private $_sModule = null;
    private $_oModule = null;

    function __construct()
    {
        $this->_sModule = 'aqb_seof';
        $this->_oModule = BxDolModule::getInstance($this->_sModule);
        parent::__construct();
    }

    public function response($oAlert)
    {
        parent::response($oAlert);

        $sMethod = '_process' . bx_gen_method_name($oAlert->sUnit . '_' . $oAlert->sAction);
        if(method_exists($this, $sMethod))
            $this->$sMethod($oAlert);
    }

    private function _processSystemUriGenerate($oAlert){
        if ($this->_oModule->_oConfig->CNF['AUTO-CONVERSION'] && isset($oAlert->aExtras['uri']) && isset($oAlert->aExtras['module']) && isset($oAlert->aExtras['page_uri'])) {
            $sUri = $this->_oModule->_oDb->convertToLatin($oAlert->aExtras['uri']);
            if (!($aData = $this->_oModule->_oDb->getPageByModulePageUri($oAlert->aExtras['module'], $oAlert->aExtras['page_uri'], $sUri)))
                $oAlert->aExtras['uri'] = $sUri;
        }
    }
}

/** @} */
