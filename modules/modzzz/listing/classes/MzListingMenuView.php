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
 * View entry menu
 */
class MzListingMenuView extends BxBaseModTextMenuView
{ 
    public function __construct($aObject, $oTemplate = false)
    {
        $this->MODULE = 'mz_listing';

        parent::__construct($aObject, $oTemplate);
 
        $this->_aJsCodes = array();

        $this->addMarkers(array(
            'js_object' => $this->_oModule->_oConfig->getJsObject('entry')
        )); 
    }
 
    public function setContentId($iContentId)
    { 
        parent::setContentId($iContentId);

        $this->addMarkers(array('js_object' => $this->_oModule->_oConfig->getJsObject('entry')));
    }
 
    public function getCode ()
    {
    	return parent::getCode() . $this->getJsCode();
    }

    public function getJsCode()
    {
        if(empty($this->_aJsCodes) || !is_array($this->_aJsCodes))
            return '';

        return implode('', $this->_aJsCodes);
    }
 
    protected function _isVisible ($a)
    {
        if(!parent::_isVisible($a))
            return false;

        $sCheckFuncName = '';
        $aCheckFuncParams = array($this->_aContentInfo);
        switch ($a['name']) {
            case 'listing-contact': 
                $sCheckFuncName = 'checkAllowedSendMessage';
                break; 
            case 'claim':
                $sCheckFuncName = 'checkAllowedAddClaim';
                break;            
			case 'interested':
                $sCheckFuncName = 'checkShowInterested';
                break;
            case 'not-interested':
                $sCheckFuncName = 'checkShowNotInterested';
                break;
            case 'create-listing-announcement':
                $sCheckFuncName = 'checkAllowedAddAnnouncement';
                break; 
            case 'create-listing-schedule':
                $sCheckFuncName = 'checkAllowedAddSchedule';
                break; 
            case 'create-listing-service': 
                $sCheckFuncName = 'checkAllowedAddService';
                break; 
            case 'create-listing-gift': 
                $sCheckFuncName = 'checkAllowedAddGift';
                break; 
            case 'create-listing-reward': 
                $sCheckFuncName = 'checkAllowedAddReward';
                break; 
            case 'create-listing-branch': 
                $sCheckFuncName = 'checkAllowedAddBranch';
                break; 
            case 'create-listing-broadcast': 
                $sCheckFuncName = 'checkAllowedBroadcast';
                break;  
        }

        if(!$sCheckFuncName || !method_exists($this->_oModule, $sCheckFuncName))
            return true;

        return call_user_func_array(array($this->_oModule, $sCheckFuncName), $aCheckFuncParams) === CHECK_ACTION_RESULT_ALLOWED;
    }






}

/** @} */
