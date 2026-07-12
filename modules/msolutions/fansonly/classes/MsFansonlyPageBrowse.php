<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) MSolutions
 * 
 * @defgroup    Fansonly Fansonly module
 * @ingroup     MSolutionsModules
 *
 * @{
 */ 

/**
 * Browse entries pages.
 */
class MsFansonlyPageBrowse extends BxBaseModProfilePageBrowse
{
    public function __construct($aObject, $oTemplate = false)
    {
        $this->MODULE = 'ms_fansonly';

        parent::__construct($aObject, $oTemplate);
    }
}

/** @} */
