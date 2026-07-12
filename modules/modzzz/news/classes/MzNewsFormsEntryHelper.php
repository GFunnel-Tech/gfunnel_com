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
 * Entry forms helper functions
 */
class MzNewsFormsEntryHelper extends BxBaseModTextFormsEntryHelper
{
    public function __construct($oModule)
    {
        parent::__construct($oModule);
    }

    public function onDataAddAfter($iAccountId, $iContentId)
    {
        $s = parent::onDataAddAfter($iAccountId, $iContentId);
        if(!empty($s))
            return $s;

        $this->_oModule->serviceUpdateBreakingNews($iContentId);

        return '';
    }

    public function onDataEditAfter($iContentId, $aContentInfo, $aTrackTextFieldsChanges, $oProfile, $oForm)
    {
        $s = parent::onDataEditAfter($iContentId, $aContentInfo, $aTrackTextFieldsChanges, $oProfile, $oForm);
        if(!empty($s))
            return $s;

        $this->_oModule->serviceUpdateBreakingNews($iContentId, $aContentInfo);

        return '';
    }




}

/** @} */
