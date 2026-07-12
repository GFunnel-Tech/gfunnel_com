<?php defined('BX_DOL') or die('hack attempt');


bx_import('BxDolPrivacy');

class BxPeopleConfig extends BxBaseModGeneralConfig
{
    
    /**
     * Constructor
     */
    public function __construct($aModule)
    {
        parent::__construct($aModule);
		
		$this->_aObjects = array(
        	'form_app' => $this->_sName . '_add_app',
        	'form_display_app' => $this->_sName . '_add_application',
			'form_display_app_edit' => $this->_sName . '_app_edit'
			
        );
	
    }
	
}

/** @} */
