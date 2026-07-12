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

class MzJobsCmtsSearchResult extends BxBaseModGeneralCmtsSearchResult
{
    function __construct($sMode = '', $aParams = array())
    {
    	$this->sModule = 'mz_jobs';

        parent::__construct($sMode, $aParams);

        $this->aCurrent['title'] = _t('_mz_jobs_page_block_title_browse_cmts');
    }
}

/** @} */
