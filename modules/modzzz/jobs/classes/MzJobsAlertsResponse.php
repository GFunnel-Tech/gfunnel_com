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

class MzJobsAlertsResponse extends BxBaseModTextAlertsResponse
{
    public function __construct()
    {
        $this->MODULE = 'mz_jobs';

        parent::__construct();
    }

    public function response($oAlert)
    {
        parent::response($oAlert);

        $CNF = $this->_oModule->_oConfig->CNF;
        $sModule = $this->_oModule->getName();
 
        // connection events
        if ($oAlert->sUnit == 'bx_convos' && $oAlert->sAction == 'added') 
		{ 
			if (strpos($_SERVER['HTTP_REFERER'], $CNF['URI_VIEW_ENTRY']) !== false) 
			{  
				$sRefererUrl = bx_append_url_params($_SERVER['HTTP_REFERER'], array('msg' => 1));
				$this->_redirectAndExit($sRefererUrl);
			}
		}
	}
 
    protected function _redirectAndExit ($sUrl, $isPermalink = true, $aMarkers = false)
    {
        if ($isPermalink)
            $sUrl = BxDolPermalinks::getInstance()->permalink($sUrl);

        if(strncmp($sUrl, BX_DOL_URL_ROOT, strlen(BX_DOL_URL_ROOT)) !== 0)
            $sUrl = BX_DOL_URL_ROOT . $sUrl;

        header('Location: ' .  $sUrl);
        exit;
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
