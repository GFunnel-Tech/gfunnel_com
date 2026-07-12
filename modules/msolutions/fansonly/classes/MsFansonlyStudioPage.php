<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) MSolutions
 * 
 * @defgroup    Fansonly Fansonly module
 * @ingroup     MSolutionsModules
 *
 * @{
 */

class MsFansonlyStudioPage extends BxTemplStudioModule
{

    protected $_oModule;
    protected $sSubpageUrl;
    protected $aMenuItems;
    protected $aGridObjects;

    function __construct($sModule, $mixedPageName, $sPage = "")
    {

        $_oModule = $this->_oModule = BxDolModule::getInstance('ms_fansonly');
        $CNF = &$this->_oModule->_oConfig->CNF;
        $CNF['IS_STUDIO_SETTINGS'] = true;
    
        $this->sSubpageUrl = 'module.php?name=ms_fansonly&page=';
        
		$this->aGridObjects = array(

        	'fansonly' => 'ms_fansonly_administration',
        );

        $this->aMenuItems = array(
            'settings' => array('name' => 'settings', 'icon' => 'cogs', 'title' => '_adm_lmi_cpt_settings'),            
            'help' => array('name' => 'help', 'icon' => 'question', 'title' => '_ms_fansonly_help'),
        );
        
        parent::__construct($sModule, $mixedPageName, $sPage);
        
    }


    protected function _switcherChecked2State($isChecked)
    {
        return $isChecked ? 'active' : 'hidden';
    }

    protected function _switcherState2Checked($mixedState)
    {
        return 'active' == $mixedState ? true : false;
    }

    function getPageCode($sPage = '', $bWrap = true)
    {

        if($this->sPage=='fansonly' || $this->sPage=='help'){
            $sMethod = 'get' . bx_gen_method_name($this->sPage);
            return $this->$sMethod();
        }
        return parent::getPageCode($sPage, $bWrap);

                
    }
    function getHelp ()
    {
        return _t('_ms_fansonly_help_text');
    }

	public function addlog($line){ 
        return;     
		$ddl=date("dmy");
		$line = json_encode($line);
        $filename = BX_DIRECTORY_PATH_LOGS ."FansonlyModule-PhpFoundLines.php";

        $fi = @fopen( $filename,"a+");
        if($fi){
           $array = explode("\n", fread($fi, filesize($filename)));
            if(!in_array($line, $array))
            {
        		fwrite($fi,$line. "\n");
            }
            
	    	fclose($fi);   	        
            
        }
        
	}
        
}

/** @} */
