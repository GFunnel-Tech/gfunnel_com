<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaStudio UNA Studio
 * @{
 */

class BxDolStudioAgentsCmts extends BxTemplCmts
{
    protected $_oQueryAgents;

    protected $_oAI;
    protected $_iProfileIdAi;

    protected $_bAuto;

    public function __construct($sSystem, $iId, $iInit = true, $oTemplate = false)
    {
        parent::__construct($sSystem, $iId, $iInit, $oTemplate);

        if ($oTemplate)
            $this->_oTemplate = $oTemplate;
        else
            $this->_oTemplate = BxDolStudioTemplate::getInstance();

        $this->_sFormObject = 'sys_agents_comment';
        $this->_sFormDisplayPost = 'sys_agents_comment_post';
        $this->_sFormDisplayEdit = 'sys_agents_comment_edit';
        
        $this->_sTmplNameItemContent = 'agents_comment_content.html';
        $this->_bLiveUpdates = false;

        $this->_oQueryAgents = new BxDolStudioAgentsQuery();

        $this->_oAI = BxDolAI::getInstance();
        $this->_iProfileIdAi = $this->_oAI->getProfileId();

        $this->_bAuto = false;
    }

    public function actionGetCmt ()
    {
        if(!$this->isEnabled())
            return echoJson([]);

        if($this->isViewAllowed() !== CHECK_ACTION_RESULT_ALLOWED)
            return echoJson([]);

        $mixedCmtId = bx_process_input(bx_get('Cmt'));
        $sCmtBrowse = ($sCmtBrowse = bx_get('CmtBrowse')) !== false ? bx_process_input($sCmtBrowse, BX_DATA_TEXT) : '';
        $sCmtDisplay = ($sCmtDisplay = bx_get('CmtDisplay')) !== false ? bx_process_input($sCmtDisplay, BX_DATA_TEXT) : '';

        $aCmtIds = strpos($mixedCmtId, ',') !== false ? explode(',', $mixedCmtId) : [$mixedCmtId];

        $sContent = '';
        foreach($aCmtIds as $iCmtId)
            $sContent .= $this->getComment((int)$iCmtId, ['type' => $sCmtBrowse], ['type' => $sCmtDisplay, 'dynamic_mode' => true]);

        $aCmt = $this->getCommentRow((int)reset($aCmtIds));
        echoJson([
            'parent_id' => $aCmt['cmt_parent_id'],
            'vparent_id' => $aCmt['cmt_parent_id'],
            'content' => $sContent
        ]);
    }

    protected function _getCountersBox(&$aCmt, $aBp = [], $aDp = [])
    {
        return '';
    }

    protected function _getFormBox($sType, $aBp, $aDp)
    {
        return parent::_getFormBox($sType, $aBp, array_merge($aDp, [
            'min_post_form' => false, 
            'class_body' => $this->_sStylePrefix . '-body-agents'
        ]));
    }
}

/** @} */
