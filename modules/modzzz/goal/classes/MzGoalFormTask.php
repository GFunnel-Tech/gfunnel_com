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

class MzGoalFormTask extends BxBaseModTextFormEntry
{ 
    public function __construct($aInfo, $oTemplate = false)
    {
        $this->MODULE = 'mz_goal';
 
        parent::__construct($aInfo, $oTemplate);

        $CNF = &$this->_oModule->_oConfig->CNF; 
    }

    function initChecker ($aValues = array (), $aSpecificValues = array())
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
 
        return BxTemplFormView::initChecker($aValues, $aSpecificValues);
    }
  
  
}

/** @} */
