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

class MzListingMenuViewBranch extends BxBaseModTextMenu
{
	protected $_aBranchInfo;

    public function __construct($aObject, $oTemplate = false)
    {
        $this->MODULE = 'mz_listing';
        parent::__construct($aObject, $oTemplate);
 
        $CNF = &$this->_oModule->_oConfig->CNF;
   
		$sPageUri = bx_process_input(bx_get('i'), BX_DATA_TEXT);
		$iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);
 
		if($sPageUri==$CNF['URI_CREATE_BRANCH'])
		{
			$this->setContentId($iContentId); 
		}else{
			$this->_aBranchInfo = $this->_oModule->_oDb->getBranchInfoById($iContentId); 
			$this->setContentId($this->_aBranchInfo[$CNF['FIELD_LISTING_ID']]);  
		}
    }

    public function setContentId($iContentId)
    {
        $this->_iContentId = (int)$iContentId;

        $this->_aContentInfo = $this->_oModule->_oDb->getContentInfoById($this->_iContentId);
        if($this->_aContentInfo)
            $this->addMarkers(array('content_id' => (int)$this->_iContentId));
    } 
}

/** @} */
