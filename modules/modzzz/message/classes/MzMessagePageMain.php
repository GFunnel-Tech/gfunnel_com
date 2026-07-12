<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Profile Message Profile Message 
 * @ingroup     ModzzzModules
 *
 * @{
 */

/**
 * Profile create/edit/delete pages.
 */
class MzMessagePageMain extends BxBaseModTextPageBrowse
{
    public function __construct($aObject, $oTemplate = false)
    {
        $this->MODULE = 'mz_message';
        parent::__construct($aObject, $oTemplate); 

    }
  
}

/** @} */
