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

/**
 * Create/Edit entry form
 */
class AqbAfFormEntry extends BxBaseModGeneralFormEntry
{
    public function __construct($aInfo, $oTemplate = false)
    {
        $this->MODULE = 'aqb_auto_friends';
        parent::__construct($aInfo, $oTemplate);

        $CNF = &$this->_oModule->_oConfig->CNF;

        if(isset($CNF['FIELD_PROFILE_ID']) && isset($this->aInputs[$CNF['FIELD_PROFILE_ID']]))
            $this->aInputs[$CNF['FIELD_PROFILE_ID']]['attrs'] = array(
            	'id' => $this->_oModule->_oConfig->getHtmlId('ff_profile_id'),
            );

        if(isset($CNF['FIELD_PROFILE']) && isset($this->aInputs[$CNF['FIELD_PROFILE']]))
            $this->aInputs[$CNF['FIELD_PROFILE']]['attrs'] = array(
            	'id' => $this->_oModule->_oConfig->getHtmlId('ff_profile'),
            );
    }
}

/** @} */
