<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    JamiiTrade JamiiTrade
 * @ingroup     UnaModules
 *
 * @{
 */

class AqbSEOFStudioPage extends BxTemplStudioModule
{
    protected $_oModule;

    function __construct($sModule, $mixedPageName, $sPage)
    {
        parent::__construct($sModule, $mixedPageName, $sPage);

        $this -> _oModule = BxDolModule::getInstance($sModule);

        if (!$sPage)
            $this->sPage = "urls";

        $this->aMenuItems = [
            'urls' => ['name' => 'urls', 'icon' => 'list', 'title' => '_aqb_seof_adm_cpt_urls'],
            'settings' => ['name' => 'settings', 'icon' => 'cogs', 'title' => '_aqb_seof_adm_cpt_settings'],
        ];

        $this->_oModule->_oTemplate->addStudioCss(['admin.css']);
        $this->_oModule->_oTemplate->addStudioJs(['manage_tools.js', 'utils.js']);
    }

    function getPageCode($sPage = '', $bWrap = true)
    {
        $sMethod = 'get' . ucfirst($this->sPage);
        if(method_exists($this, $sMethod))
            return $this->$sMethod();

        $sObject = $this->_oModule->_oConfig->getGridObject($this->sPage);
        $oGrid = BxDolGrid::getObjectInstance($sObject, BxDolStudioTemplate::getInstance());
        if ($oGrid) {
            $sManageTools = $this->_oModule->_oTemplate->getJsCode('manage_tools', array('sObjNameGrid' => $sObject));
            $sUtils = $this->_oModule->_oTemplate->getJsCode('utils', array('sObjNameGrid' => $sObject));
            return $this->getBlockCode([
                'content' => $oGrid->getCode() . $sUtils . $sManageTools
            ]);
        }

        return MsgBox(_t("_{$this->_sModule}_installation_problem")) ;
    }
}

/** @} */
