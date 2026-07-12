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

/**
 * View entry menu
 */
class MzGoalMenuAttachments extends BxBaseModTextMenuAttachments
{
    public function __construct($aObject, $oTemplate = false)
    {
        $this->_sModule = 'mz_goal';

        parent::__construct($aObject, $oTemplate);
    }
}

/** @} */
