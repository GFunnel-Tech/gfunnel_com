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

class MzNewsFormPoll extends BxBaseModTextFormPoll
{
    public function __construct($aInfo, $oTemplate = false)
    {
        $this->_sModule = 'mz_news';
        $this->_aFieldsCheckForSpam = array('answers');
        parent::__construct($aInfo, $oTemplate);
    }
}

class MzNewsFormPollCheckerHelper extends BxBaseModTextFormPollCheckerHelper {}

/** @} */
