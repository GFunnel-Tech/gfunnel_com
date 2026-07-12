<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Goal Goal
 * @ingroup     ModzzzModules
 *
 * @{
 */


class MzGoalFormPoll extends BxBaseModTextFormPoll
{
    public function __construct($aInfo, $oTemplate = false)
    {
        $this->_sModule = 'mz_goal';
        $this->_aFieldsCheckForSpam = array('answers');
        parent::__construct($aInfo, $oTemplate);
    }
}

class MzGoalFormPollCheckerHelper extends BxBaseModTextFormPollCheckerHelper {}

/** @} */
