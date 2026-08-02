<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaStudio UNA Studio
 * @{
 */

class BxDolStudioAgentsAutomatorsCmts extends BxDolStudioAgentsCmts
{
    protected $_sUrlPageAgents;

    public function __construct($sSystem, $iId, $iInit = true, $oTemplate = false)
    {
        parent::__construct($sSystem, $iId, $iInit, $oTemplate);

        $this->_sUrlPageAgents = BX_DOL_URL_STUDIO . 'agents.php?page=automators';
    }
    
    public function actionApproveCode()
    {
        if(!$this->isEnabled())
            return echoJson([]);

        $iCmt = bx_process_input(bx_get('Cmt'), BX_DATA_INT);
        $aCmt = $this->getCommentRow($iCmt);
        if(empty($aCmt) || !is_array($aCmt))
            return echoJson([]);
        
        $iObjId = (int)$this->getId();
        
        $aAutomator = $this->_oAI->getAutomator($iObjId, true);
        $aAutomator['code'] = $aCmt['cmt_text'];

        $aParams = [];
        if($aAutomator['type'] == BX_DOL_AI_AUTOMATOR_EVENT)
            $aParams['alert'] = new BxDolAlerts('test', 'test', 0);

        if(($sResult = $this->_oAI->emulCode($aAutomator, $aParams)) != '')
            return echoJson(['msg' => _t('_sys_agents_automators_txt_eval_response') . $sResult]);

        if(!$this->_oQueryAgents->updateAutomators(['code' => $aCmt['cmt_text'], 'status' => 'ready'], ['id' => (int)$this->getId()]))
            return echoJson([]);

        return echoJson(['redirect' => $this->_sUrlPageAgents]);
    }

    public function getPageJsObject()
    {
        return 'oBxDolStudioPageAgents';
    }

    public function getCommentsBlock($aBp = [], $aDp = [])
    {
        $aComments = parent::getCommentsBlock($aBp, ['in_designbox' => false]);
        if(empty($aComments['content']))
            return MsgBox(_t('_error occured'));

        $aAutomator = $this->_oQueryAgents->getAutomatorsBy(['sample' => 'id', 'id' => (int)$this->getId()]);
        if(empty($aAutomator) || !is_array($aAutomator))
            return MsgBox(_t('_error occured'));

        $aTmplVarsEvent = [];
        $bTmplVarsEvent = $aAutomator['type'] == 'event';
        if($bTmplVarsEvent)
            $aTmplVarsEvent = [
                'unit' => $aAutomator['alert_unit'],
                'action' => $aAutomator['alert_action']
            ];
        
        $aTmplVarsScheduler = [];
        $bTmplVarsScheduler = $aAutomator['type'] == 'scheduler';
        if($bTmplVarsScheduler) {
            $aParams = [];
            if(!empty($aAutomator['params']))
                $aParams = json_decode($aAutomator['params'], true);

            $aTmplVarsScheduler = [
                'time' => $aParams['scheduler_time'],
            ];
        }

        $sAutomator = $this->_oTemplate->parseHtmlByName('agents_automator_info.html', [
            'type' => _t('_sys_agents_automators_field_type_' . $aAutomator['type']),
            'bx_if:show_event' => [
                'condition' => $bTmplVarsEvent,
                'content' => $aTmplVarsEvent
            ],
            'bx_if:show_scheduler' => [
                'condition' => $bTmplVarsScheduler,
                'content' => $aTmplVarsScheduler
            ],
            'code' => $aAutomator['code']
        ]);

        return [
            $sAutomator,
            $aComments['content']
        ];
    }

    public function getComment($mixedCmt, $aBp = [], $aDp = [])
    {
        return parent::getComment($mixedCmt, $aBp, array_merge($aDp, ['class_comment' => $this->_sStylePrefix . '-agents']));
    }
    
    public function getFormBoxPost($aBp = [], $aDp = [])
    {
        $aComments = $this->_oQuery->getCommentsBy(['type' => 'latest', 'object_id' => (int)$this->getId(), 'start' => 0, 'per_page' => 1]);
        if(!empty($aComments) && is_array($aComments)) {
            $aLast = current($aComments);
            if($aLast['cmt_author_id'] != $this->_iProfileIdAi)
                return '';
        }

        return parent::getFormBoxPost($aBp, $aDp);
    }

    public function isAttachImageEnabled()
    {
        return false;
    }

    public function addAuto($aValues)
    {
        $this->_bAuto = true;
        $mixedResult = $this->add($aValues);

        $this->_bAuto = false;
        return $mixedResult;
    }

    public function onPostAfter($iCmtId, $aDp = [])
    {
        $mixedResult = parent::onPostAfter($iCmtId, $aDp);
        if($this->_bAuto || $mixedResult === false) 
            return $mixedResult;

        $iObjId = (int)$this->getId();
        $aAutomator = $this->_oAI->getAutomator($iObjId, true);

        $aComments = $this->_oQuery->getCommentsBy(['type' => 'object_id', 'object_id' => $iObjId]);
        if($aAutomator['type'] == BX_DOL_AI_AUTOMATOR_EVENT && !empty($aAutomator['params']['trigger']))
            $aComments[0]['cmt_text'] .= $aAutomator['params']['trigger'];

        $oMessages = new BxDolAIMessages();
        foreach($aComments as $aComment)
            $oMessages->add((int)$aComment['cmt_author_id'] == $this->_iProfileIdAi ? 'ai' : 'hb', $aComment['cmt_text']);

        $oAIModel = $this->_oAI->getModelObject($aAutomator['model_id']);
        if(($sResponse = $oAIModel->getResponse($aAutomator['type'], $oMessages, $aAutomator['params'])) !== false) {
            $mixedResultAuto = $this->addAuto([
                'cmt_author_id' => $this->_iProfileIdAi,
                'cmt_parent_id' => 0,
                'cmt_text' => $sResponse
            ]);

            if($mixedResultAuto !== false)
                $mixedResult['id'] .= ',' . $mixedResultAuto['id'];
        }

        return $mixedResult;
    }

    protected function _getActionsBox(&$aCmt, $aBp = [], $aDp = [])
    {
        if((int)$aCmt['cmt_author_id'] != $this->_iProfileIdAi)
            return parent::_getActionsBox($aCmt, $aBp, array_merge($aDp, ['view_only' => true]));

        return $this->_oTemplate->parseHtmlByName('agents_comment_actions_automator.html', [
            'js_object' => $this->getPageJsObject(),
            'id' => $aCmt['cmt_id']
        ]);
    }

    protected function _getTmplVarsText($aCmt)
    {
        $aResult = parent::_getTmplVarsText($aCmt);

        if((int)$aCmt['cmt_author_id'] == $this->_iProfileIdAi)
            $aResult['text'] = '<pre>' . $aResult['text'] . '</pre>';

        return $aResult;
    }
    
    protected function _getForm($sAction, $iId, $aDp = [])
    {
        $aResult = parent::_getForm($sAction, $iId, $aDp);

        $aResult->aInputs['cmt_text']['db']['pass'] = 'xss';
        return $aResult;
    }
    
    protected function _prepareTextForOutput($s, $iCmtId = 0)
    {
        return nl2br(parent::_prepareTextForOutput($s, $iCmtId));
    }
}

/** @} */
