<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Auto Friends Auto Friends
 * @ingroup     UNAModules
 *
 * @{
 */

define('AQB_AF_BROWSE_FULL', 'full');
define('AQB_AF_BROWSE_SHORT', 'short');

class AqbAfModule extends BxBaseModGeneralModule
{
    public function __construct(&$aModule)
    {
        parent::__construct($aModule);

        $this->_oConfig->init($this->_oDb);
    }

    //--- Service Methods ---//
    public function serviceGetModulesChecklist()
    {
        $aModules = array_keys($this->_oConfig->getSupportedModules());

        $aResults = array();
        foreach($aModules as $sModule) {
            $aModule = $this->_oDb->getModuleByName($sModule);
            if(empty($aModule))
                continue;

            $aResults[$sModule] = $aModule['title'];
        }

        asort($aResults);
        return $aResults;
    }

    public function serviceGetBlockView()
    {
        return $this->_oTemplate->getBlockProfiles(AQB_AF_BROWSE_FULL, 0, $this->_oConfig->getPerPage('list'), false);
    }

    public function serviceGetBlockViewHome()
    {
        return $this->_oTemplate->getBlockProfiles(AQB_AF_BROWSE_SHORT);
    }

    public function serviceGetBlockViewDashboard()
    {
        return $this->_oTemplate->getBlockProfiles(AQB_AF_BROWSE_SHORT);
    }

	//--- Action Methods ---//
    public function actionSearchProfiles()
    {
        $sTerm = bx_get('term');

        $aResult = BxDolService::call('system', 'profiles_search', array($sTerm), 'TemplServiceProfiles');

        echoJson($aResult);
    }

    public function actionGetProfiles()
    {
        $sType = bx_process_input(bx_get('type'));

            $iStart = (int)bx_get('start');

            $iPerPage = (int)bx_get('per_page');
            if($iPerPage == 0)
                    $iPerPage = $this->_oConfig->getPerPage();

            $sContent = $this->_oTemplate->getProfiles($sType, $iStart, $iPerPage);

            header('Content-Type:text/javascript');
            echo json_encode(array('code' => 0, 'content' => $sContent, 'eval' => $this->_oConfig->getJsObject('main') . '.onGetProfiles(oData)'));
    }

    //--- Common Methods ---//
    public function checkAllowedAdd($isPerformAction = false)
    {
        if(!isAdmin())
            return _t('_sys_txt_access_denied');

        return CHECK_ACTION_RESULT_ALLOWED;
    }

    public function checkAllowedEditAnyEntryForProfile ($isPerformAction = false, $iProfileId = false)
    {
        if(!isAdmin())
            return _t('_sys_txt_access_denied');

        return CHECK_ACTION_RESULT_ALLOWED;
    }
}

/** @} */
