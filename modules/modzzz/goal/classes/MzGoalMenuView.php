<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup Goal Goal
 * @ingroup ModzzzModules
 *
 * @{
 */
/**
 * View entry menu
 */
class MzGoalMenuView extends BxBaseModTextMenuView
{
    protected array $_aJsCodes = [];

    public function __construct($aObject, $oTemplate = false)
    {
        $this->MODULE = 'mz_goal';
        parent::__construct($aObject, $oTemplate);
        //BEGIN UNA 13 RC
        $this->addMarkers(array(
            'js_object' => $this->_oModule->_oConfig->getJsObject('entry')
        ));
        //END UNA 13 RC
    }

    //BEGIN UNA 13 RC
    public function getCode()
    {
        return parent::getCode() . $this->getJsCode();
    }

    public function getJsCode()
    {
        if (empty($this->_aJsCodes) || !is_array($this->_aJsCodes))
            return '';
        return implode('', $this->_aJsCodes);
    }
    //END UNA 13 RC

    public function setContentId($iContentId)
    {
        parent::setContentId($iContentId);
        $this->addMarkers(array('js_object' => $this->_oModule->_oConfig->getJsObject('entry')));
    }

    protected function _isVisible($a)
    {
        if (!parent::_isVisible($a))
            return false;
        $sCheckFuncName = '';
        $aCheckFuncParams = array($this->_aContentInfo);
        switch ($a['name']) {
            case 'achieved-goal':
                $sCheckFuncName = 'checkAllowedAchieved';
                break;
            case 'interested':
                $sCheckFuncName = 'checkShowInterested';
                break;
            case 'not-interested':
                $sCheckFuncName = 'checkShowNotInterested';
                break;
            case 'create-goal-task':
                $sCheckFuncName = 'checkAllowedAddTask';
                break;
            case 'create-goal-sponsor':
                $sCheckFuncName = 'checkAllowedAddSponsor';
                break;
            case 'create-goal-news':
                $sCheckFuncName = 'checkAllowedAddNews';
                break;
            case 'create-goal-broadcast':
                $sCheckFuncName = 'checkAllowedBroadcast';
                break;
        }
        if (!$sCheckFuncName || !method_exists($this->_oModule, $sCheckFuncName))
            return true;
        return call_user_func_array(array($this->_oModule, $sCheckFuncName), $aCheckFuncParams) === CHECK_ACTION_RESULT_ALLOWED;
    }
}
/** @} */