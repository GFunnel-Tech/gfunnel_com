<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) Publicchat.in
 * 
 * @defgroup    Complete Profile Publicchat.in module
 * @ingroup     PublicchatModules
 *
 * @{
 */

bx_import ('BxDolModuleTemplate');

class PcCbTemplate extends BxDolModuleTemplate 
{    
	function __construct(&$oConfig, &$oDb) 
    {
	    parent::__construct($oConfig, $oDb);
    }    
}

/** @} */
