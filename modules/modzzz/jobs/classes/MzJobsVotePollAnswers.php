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

class MzJobsVotePollAnswers extends BxBaseModTextVotePollAnswers
{
    function __construct($sSystem, $iId, $iInit = 1)
    {
    	$this->_sModule = 'mz_jobs';

        parent::__construct($sSystem, $iId, $iInit);
    }
}

/** @} */
