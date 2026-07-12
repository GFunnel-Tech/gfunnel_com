<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    News News
 * @ingroup     ModzzzModules
 *
 * @{
 */

/**
 * View entry menu
 */
class MzNewsMenuAttachments extends BxBaseModTextMenuAttachments
{
    public function __construct($aObject, $oTemplate = false)
    {
        $this->_sModule = 'mz_news';

        parent::__construct($aObject, $oTemplate);
    }
}

/** @} */
