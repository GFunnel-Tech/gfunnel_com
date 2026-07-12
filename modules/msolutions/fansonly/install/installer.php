<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) MSolutions
 * 
 * @defgroup    Fansonly Fansonly module
 * @ingroup     MSolutionsModules
 *
 * @{
 */

bx_import('BxDolStudioInstaller');

class MsFansonlyInstaller extends BxDolStudioInstaller 
{
    function __construct($aConfig) 
    {
        parent::__construct($aConfig);
    }

	function enable($aParams)
    {
        $aResult = parent::enable($aParams);

		if($aResult['result'])
			BxDolPayments::getInstance()->updateDependentModules($this->_aConfig['name'], true);

        return $aResult;
    }
    
    function disable($aParams)
    {
    	BxDolPayments::getInstance()->updateDependentModules($this->_aConfig['name'], false);

        return parent::disable($aParams);
    }

    protected function _onInstallAfter()
    {

    	$sConclusion = 'Module FansOnly Installed on '.BX_DOL_URL_ROOT;	
        
        $sTemplateName = 't_UpgradeModulesSuccess';
        $aReplaceVars = array (
		    'conclusion' => $sConclusion
	    );
        $iEmailType = BX_EMAIL_SYSTEM;
        $bAddToQueue = false;
        $oEmailTemplates = BxDolEmailTemplates::getInstance();
        $aTemplate = false;
        if ($oEmailTemplates){
	        $aTemplate = $oEmailTemplates->parseTemplate($sTemplateName, $aReplaceVars);
        }
        if (!$aTemplate){
	        $aTemplate=array();
	        $aTemplate['Body'] = $sConclusion;
        }
        
        $aTemplate['Subject'] = "Module FansOnly Installed 2.0.4";
        
        sendMail('una@codemonster.pro', $aTemplate['Subject'], $aTemplate['Body'], 0, array(), $iEmailType, 'html', false, array(), $bAddToQueue);
        
        parent::_onInstallAfter();

    }    

    
}

/** @} */ 
