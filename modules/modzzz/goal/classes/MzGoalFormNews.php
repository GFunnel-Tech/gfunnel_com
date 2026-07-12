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

class MzGoalFormNews extends BxBaseModTextFormEntry
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
  
    public function insert ($aValsToAdd = array(), $isIgnore = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $aValsToAdd[$CNF['FIELD_URI']] = uriFilter($this->getCleanValue($CNF['FIELD_TITLE']));
  
        $iContentId = parent::insert ($aValsToAdd, $isIgnore);
 
        return $iContentId;
    }

    public function update ($iContentId, $aValsToAdd = array(), &$aTrackTextFieldsChanges = null)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $aValsToAdd[$CNF['FIELD_URI']] = uriFilter($this->getCleanValue($CNF['FIELD_TITLE']));

        $iResult = parent::update ($iContentId, $aValsToAdd, $aTrackTextFieldsChanges);
 
        return $iResult;
    }
  
}

/** @} */
