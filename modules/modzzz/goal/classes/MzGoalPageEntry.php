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
 * Entry create/edit pages
 */
class MzGoalPageEntry extends BxBaseModTextPageEntry
{
    public function __construct($aObject, $oTemplate = false)
    {
        $this->MODULE = 'mz_goal';

        parent::__construct($aObject, $oTemplate);
    }

    protected function _setSubmenu($aParams)
    {
        parent::_setSubmenu(array_merge($aParams, array(
            'title' => '',
            'icon' => ''
        )));
    }
}

/** @} */
