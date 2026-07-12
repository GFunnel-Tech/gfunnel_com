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

class MzGoalMenuViewNews extends BxBaseModTextMenu
{
	protected $_aNewsInfo;

    public function __construct($aObject, $oTemplate = false)
    {
        $this->MODULE = 'mz_goal';
        parent::__construct($aObject, $oTemplate);
 
        $CNF = &$this->_oModule->_oConfig->CNF;
   
		$sPageUri = bx_process_input(bx_get('i'), BX_DATA_TEXT);
		$iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);
 
		if($sPageUri==$CNF['URI_CREATE_NEWS'])
		{
			$this->setContentId($iContentId); 
		}else{
			$this->_aNewsInfo = $this->_oModule->_oDb->getNewsInfoById($iContentId); 
			$this->setContentId($this->_aNewsInfo[$CNF['FIELD_GOAL_ID']]);  
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
