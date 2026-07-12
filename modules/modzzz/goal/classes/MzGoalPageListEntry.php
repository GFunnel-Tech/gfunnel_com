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
 * List entry page
 */
class MzGoalPageListEntry extends BxBaseModTextPageListEntry
{    
    public function __construct($aObject, $oTemplate = false)
    {
        $this->MODULE = 'mz_goal';
        parent::__construct($aObject, $oTemplate);
    }
}

/** @} */
