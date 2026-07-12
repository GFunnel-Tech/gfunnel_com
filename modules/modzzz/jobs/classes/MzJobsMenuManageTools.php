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
 * 'Ads manage tools' menu.
 */
class MzJobsMenuManageTools extends BxBaseModTextMenuManageTools
{

    public function __construct($aObject, $oTemplate = false)
    {
        $this->MODULE = 'mz_jobs';

        parent::__construct($aObject, $oTemplate);
    }
}

/** @} */
