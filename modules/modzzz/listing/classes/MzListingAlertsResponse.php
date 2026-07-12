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

class MzListingAlertsResponse extends BxBaseModTextAlertsResponse
{
    public function __construct()
    {
        $this->MODULE = 'mz_listing'; 

        parent::__construct();
    }

    public function response($oAlert)
    {
        $sMethod = '_process' . bx_gen_method_name($oAlert->sUnit . '_' . $oAlert->sAction);           	
        if(!method_exists($this, $sMethod))
            return;

        $this->$sMethod($oAlert);
    }
 
	//BEGIN UNA 13 beta 
    protected function _processProfileApprove($oAlert)
    {
        $this->_processProfileChangeStatus($oAlert);
    }

    protected function _processProfileActivate($oAlert)
    {
        $this->_processProfileChangeStatus($oAlert);
    }

    protected function _processProfileDisapprove($oAlert)
    {
        $this->_processProfileChangeStatus($oAlert);
    }

    protected function _processProfileSuspend($oAlert)
    {
        $this->_processProfileChangeStatus($oAlert);
    }

    protected function _processProfileChangeStatus($oAlert)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $aEntries = $this->_oModule->_oDb->getEntriesBy([
            'type' => 'author', 
            'author' => $oAlert->iObject
        ]);

        $aCategories = $aSubCategories = []; 
        foreach($aEntries as $aEntry) {
            if($aEntry[$CNF['FIELD_STATUS']] != 'active' || $aEntry[$CNF['FIELD_STATUS_ADMIN']] != 'active')
                continue;

            if(!in_array($aEntry[$CNF['FIELD_CATEGORY']], $aCategories)) 
				$aCategories[] = $aEntry[$CNF['FIELD_CATEGORY']];

            if(!in_array($aEntry[$CNF['FIELD_SUBCATEGORY']], $aSubCategories)) 
				$aSubCategories[] = $aEntry[$CNF['FIELD_SUBCATEGORY']];
        }

        if(!empty($aCategories))
            foreach($aCategories as $iCategoryId)
                $this->_oModule->serviceUpdateCategoriesStatsByCategory($iCategoryId, 'category');  
 
        if(!empty($aSubCategories))
            foreach($aSubCategories as $iCategoryId)
                $this->_oModule->serviceUpdateCategoriesStatsByCategory($iCategoryId, 'subcategory');   
    }
	//END UNA 13 beta 
 


}

/** @} */
