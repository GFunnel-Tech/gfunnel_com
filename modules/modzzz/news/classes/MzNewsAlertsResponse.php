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

class MzNewsAlertsResponse extends BxBaseModTextAlertsResponse
{
    public function __construct()
    { 
        $this->MODULE = 'mz_news';
        parent::__construct();
    }
}

/** @} */
