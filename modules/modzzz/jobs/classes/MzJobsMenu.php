<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Jobs Jobs
 * @ingroup     ModzzzModules
 *
 * @{
 */

/**
 * General class for module menu.
 */
class MzJobsMenu extends BxBaseModTextMenu
{
    public function __construct($aObject, $oTemplate = false)
    {
        $this->MODULE = 'mz_jobs';

        parent::__construct($aObject, $oTemplate);
    }
}

/** @} */
