<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Listing Listing
 * @ingroup     ModzzzModules
 *
 * @{
 */

require_once(BX_DOL_DIR_STUDIO_INC . 'utils.inc.php');

class MzListingCmtsGifts extends BxTemplCmts
{
    protected $_sModule;
    protected $_oModule;

    function __construct($sSystem, $iId, $iInit = 1)
    {
        parent::__construct($sSystem, $iId, $iInit);
 
        $this->_sModule = 'mz_listing'; 
        $this->_oModule = BxDolModule::getInstance($this->_sModule);
    }

    public function isAttachImageEnabled()
    {
        return false;
    }

    public function isPostReplyAllowed ($isPerformAction = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $aGift = $this->_oModule->_oDb->getGiftInfoById($this->getId());

        $mixedResult = BxDolGift::call($this->_aSystem['module'], 'check_allowed_comments_post', array($aGift['entry_id'], $CNF['OBJECT_COMMENTS']));
        if($mixedResult !== CHECK_ACTION_RESULT_ALLOWED)
            return false;

        return $this->checkAction ('comments post', $isPerformAction);
    }

    protected function _getFormObject($sAction = BX_CMT_ACTION_POST)
    {
        $oForm = parent::_getFormObject($sAction);
        if($sAction != BX_CMT_ACTION_POST || !isset($oForm->aInputs['cmt_submit']))
            return $oForm;

        $aInputControls = array(
            'name' => 'controls',
            'type' => 'input_set',
            0 => $oForm->aInputs['cmt_submit'],
            1 => array (
                'type' => 'reset',
                'name' => 'close',
                'value' => _t('_mz_listing_txt_close'),
                'attrs' => array(
                    'onclick' => '$(\'.bx-popup-applied:visible\').dolPopupHide()',
                    'class' => 'bx-def-margin-sec-left',
                )
            )
        );

        $oForm->aInputs = bx_array_insert_after(array('controls' => $aInputControls), $oForm->aInputs, 'cmt_submit');
        unset($oForm->aInputs['cmt_submit']);       

        return $oForm;
    }
}

/** @} */
