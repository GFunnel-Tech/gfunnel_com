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
 * View entry menu
 */
class MzJobsMenuView extends BxBaseModTextMenuView
{
    public function __construct($aObject, $oTemplate = false)
    {
        $this->MODULE = 'mz_jobs';

        parent::__construct($aObject, $oTemplate);

		//BEGIN UNA 13 RC
        $this->_aJsCodes = array();

        $this->addMarkers(array(
            'js_object' => $this->_oModule->_oConfig->getJsObject('entry')
        ));
  		//END UNA 13 RC 
    }
  
	//BEGIN UNA 13 RC
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
  	//END UNA 13 RC

    public function setContentId($iContentId)
    {
        parent::setContentId($iContentId);

        //$this->addMarkers(array('js_object' => $this->_oModule->_oConfig->getJsObject('entry')));
    }
}

/** @} */
