<?php defined('BX_DOL') or die('hack attempt');

/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Affiliate System Affiliate System
 * @ingroup     UnaModules
 *
 * @{
 */

require_once('AqbAffiliateBannersFormAdd.php');

class AqbAffiliateBannersFormEdit extends AqbAffiliateBannersFormAdd
{
    function __construct ($aCustomForm = array())
    {
        $oCNF = &BxDolModule::getInstance('aqb_affiliate')->_oConfig->CNF;
        $aCustomForm = array_replace_recursive(array(
            'inputs' => array(
                $oCNF['FB_ID'] => array(
                    'type' => 'hidden',
                    'name' => $oCNF['FB_ID'],
                    'value' => '',
                    'db' => array (
                        'pass' => 'Int',
                    ),
                ),
                $oCNF['FB_IMG'] => array(
                    'required' => false
                ),
            )
        ), $aCustomForm);

        parent::__construct($aCustomForm);
    }
}

/** @} */
