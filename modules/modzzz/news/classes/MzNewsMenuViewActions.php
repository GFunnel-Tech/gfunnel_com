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

/**
 * View entry social actions menu
 */
class MzNewsMenuViewActions extends BxBaseModTextMenuViewActions
{
    public function __construct($aObject, $oTemplate = false)
    {
        $this->_sModule = 'mz_news';

        parent::__construct($aObject, $oTemplate);
    }

    protected function _getMenuItemEditNews($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }

    protected function _getMenuItemDeleteNews($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }
}

/** @} */
