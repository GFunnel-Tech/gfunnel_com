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
class MsFansonlyFansonlyDb extends BxDolModuleDb
{

    public function __construct(&$oConfig = false)
    {
        parent::__construct($oConfig);
    }
    
}


/** @} */
