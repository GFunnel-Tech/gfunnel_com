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
 * 'View person' menu.
 */
class MzMessageMenuView extends BxBaseModTextMenuView
{
    public function __construct($aObject, $oTemplate = false)
    {
        $this->_sModule = 'mz_message';

        parent::__construct($aObject, $oTemplate);
    }
}

/** @} */
