<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Auto Friends Auto Friends
 * @ingroup     UNAModules
 *
 * @{
 */

class AqbAfFormsEntryHelper extends BxBaseModGeneralFormsEntryHelper
{
    public function __construct($oModule)
    {
        parent::__construct($oModule);
    }

    protected function _redirectAndExit ($sUrl, $isPermalink = true, $aMarkers = false)
    {
        header('Location: ' . BX_DOL_URL_STUDIO . 'module.php?name=' . $this->_oModule->_oConfig->getName() . '&page=friends');
        exit;
    }
}

/** @} */
