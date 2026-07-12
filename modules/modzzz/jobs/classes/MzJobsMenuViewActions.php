<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Jobs Jobs
 * @ingroup     ModzzzModules
 *
 * @{
 */

/**
 * View entry social actions menu
 */
class MzJobsMenuViewActions extends BxBaseModTextMenuViewActions
{
    public function __construct($aObject, $oTemplate = false)
    {
        $this->_sModule = 'mz_jobs';

        parent::__construct($aObject, $oTemplate);
    }

    protected function _getMenuItemInterested($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }
    
    protected function _getMenuItemEditJob($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }

    protected function _getMenuItemDeleteJob($aItem)
    {
        return $this->_getMenuItemByNameActions($aItem);
    }

    protected function _getMenuItemWhatsapp($aItem)
    { 
        $CNF = &$this->_oModule->_oConfig->CNF;

		$oMenu = BxDolMenu::getObjectInstance('mz_jobs_contact');
		if(!$oMenu)
			return false;
 
		if(empty($this->_aContentInfo[$CNF['FIELD_WHATSAPP']]))
			return false;

		if(empty($this->_aContentInfo[$CNF['FIELD_CONTACT_MOBILE']]))
			return false;

		$sPhoneNumber = str_replace('-','', $this->_aContentInfo[$CNF['FIELD_CONTACT_MOBILE']]);
		$sPhoneNumber = str_replace('(','', $sPhoneNumber);
		$sPhoneNumber = str_replace(')','', $sPhoneNumber);
		$sPhoneNumber = str_replace('+','', $sPhoneNumber);

		if(!ctype_digit($sPhoneNumber))
			return false;

        $this->addMarkers(array(
            'module' => $this->_oModule->_oConfig->getName(),
            'phone_number' => str_replace('-','', $sPhoneNumber),
            'text_message' => urlencode(_t($CNF['T']['txt_whatsapp_msg']))
        ));

		$oMenu->addMarkers($this->_aMarkers);
  
        $aItem = $oMenu->getMenuItem($aItem['name']);
        if(empty($aItem) || !is_array($aItem))
            return false;

        return $this->_getMenuItemDefault($aItem);
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
