<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) Publicchat.in
 * 
 * @defgroup    Complete Profile Publicchat.in module
 * @ingroup     PublicchatModules
 *
 * @{
 */

bx_import('BxDolModuleDb');

class PcCbDb extends BxDolModuleDb
{
	function __construct(&$oConfig) 
    {
		parent::__construct($oConfig);	
    }
}

/** @} */
