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

/**
 * View entry social actions menu
 */
class MzListingMenuViewActions extends BxBaseModTextMenuViewActions
{
    public function __construct($aObject, $oTemplate = false)
    {
        $this->_sModule = 'mz_listing';

        parent::__construct($aObject, $oTemplate);
    }
 
    public function getCode ()
    {
        $sCode = parent::getCode();

        if(!empty($this->_oMenuActions))
            $sCode .= $this->_oMenuActions->getJsCode();

    	return $sCode;
    }
 
	//Broadcast
    protected function _getMenuItemCreateListingBroadcast($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }

	//service
    protected function _getMenuItemCreateListingService($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }
 
	//reward
    protected function _getMenuItemCreateListingReward($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }

	//gift
    protected function _getMenuItemCreateListingGift($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }
 
	//announcement
    protected function _getMenuItemCreateListingAnnouncement($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }

	//schedule
    protected function _getMenuItemCreateListingSchedule($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }
 
	//branch
    protected function _getMenuItemCreateListingBranch($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }
	    	
	protected function _getMenuItemClaim($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }

	protected function _getMenuItemNotInterested($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }

    protected function _getMenuItemInterested($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }
    
    protected function _getMenuItemEditListing($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }

    protected function _getMenuItemDeleteListing($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }

    protected function _getMenuItemReview($aItem, $aParams = array())
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        return parent::_getMenuItemComment($aItem, array_merge($aParams, array(
            'object' => $CNF['OBJECT_REVIEWS']
        )));
    }


}

/** @} */
