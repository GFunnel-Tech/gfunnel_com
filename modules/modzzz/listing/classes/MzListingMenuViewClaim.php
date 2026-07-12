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

class MzListingMenuViewClaim extends BxBaseModTextMenu
{
	protected $_aClaimInfo;

    public function __construct($aObject, $oTemplate = false)
    {
        $this->MODULE = 'mz_listing';
        parent::__construct($aObject, $oTemplate);
 
        $CNF = &$this->_oModule->_oConfig->CNF;
   
		$sPageUri = bx_process_input(bx_get('i'), BX_DATA_TEXT);
		$iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);
 
		if($sPageUri==$CNF['URI_CREATE_CLAIM'])
		{
			$this->setContentId($iContentId); 
		}else{
			$this->_aClaimInfo = $this->_oModule->_oDb->getClaimInfoById($iContentId); 
			$this->setContentId($this->_aClaimInfo[$CNF['FIELD_LISTING_ID']]);  
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
