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

require_once(BX_DOL_DIR_STUDIO_INC . 'utils.inc.php');

class MzGoalCmtsNews extends BxTemplCmts
{
    protected $_sModule;
    protected $_oModule; 

    function __construct($sSystem, $iId, $iInit = 1)
    {
        parent::__construct($sSystem, $iId, $iInit);

        $this->_sModule = 'mz_goal'; 
        $this->_oModule = BxDolModule::getInstance($this->_sModule);
    }

    public function isAttachImageEnabled()
    {
        return false;
    }

    public function isPostReplyAllowed ($isPerformAction = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $aNews = $this->_oModule->_oDb->getNewsInfoById($this->getId());

        $mixedResult = BxDolService::call($this->_aSystem['module'], 'check_allowed_comments_post', array($aNews['entry_id'], $CNF['OBJECT_COMMENTS']));
        if($mixedResult !== CHECK_ACTION_RESULT_ALLOWED)
            return false;

        return $this->checkAction ('comments post', $isPerformAction);
    }
 
}

/** @} */
