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

/**
 * View entry social actions menu
 */
class MzGoalMenuViewActions extends BxBaseModTextMenuViewActions
{
    public function __construct($aObject, $oTemplate = false)
    {
        $this->_sModule = 'mz_goal';

        parent::__construct($aObject, $oTemplate);
    }

	//Broadcast
    protected function _getMenuItemCreateGoalBroadcast($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }

	//sponsor
    protected function _getMenuItemCreateGoalSponsor($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }

	//task
    protected function _getMenuItemCreateGoalTask($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }

	//news
    protected function _getMenuItemCreateGoalNews($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }
	    
	protected function _getMenuItemNotInterested($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }

	protected function _getMenuItemAchievedGoal($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }

    protected function _getMenuItemInterested($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }
    
    protected function _getMenuItemEditGoal($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }

    protected function _getMenuItemDeleteGoal($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }

	//UNA 13 beta
    protected function _getMenuItemReview($aItem, $aParams = array())
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        return parent::_getMenuItemComment($aItem, array_merge($aParams, array(
            'object' => $CNF['OBJECT_REVIEWS']
        )));
    }

}

/** @} */
