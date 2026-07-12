<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    DolphinMigration  Dolphin Migration
 * @ingroup     UnaModules
 *
 * @{
 */


class AqbAffiliateStudioPage extends BxTemplStudioModule
{    
	/**
	 *  @var ref $_oModule main module reference
	 */
	protected $_oModule;
	private $_sModule;

    function __construct($sModule, $mixedPageName, $sPage = "")
    {
		$this->_sModule = 'aqb_affiliate';
        parent::__construct($sModule, $mixedPageName, $sPage);

		$this -> _oModule = BxDolModule::getInstance($sModule);
		
		$this->aMenuItems = array(
            'members' => array('name' => 'members', 'icon' => 'users', 'title' => "_{$this->_sModule}_cpt_members"),
            'banners' => array('name' => 'banners', 'icon' => 'ad', 'title' => "_{$this->_sModule}_cpt_banners"),
            'requests' => array('name' => 'requests', 'icon' => 'exchange-alt', 'title' => "_{$this->_sModule}_cpt_requests"),
            'matrix' => array('name' => 'matrix', 'icon' => 'users-cog', 'title' => "_{$this->_sModule}_cpt_matrix"),
            'settings' => array('name' => 'settings', 'icon' => 'cogs', 'title' => "_{$this->_sModule}_cpt_settings")
        );

        $this -> _oModule -> _oTemplate -> addStudioJs(array('jquery-ui/jquery-ui.custom.min.js', 'BxDolGrid.js', 'manage_tools.js', 'utils.js'));
        $this -> _oModule -> _oTemplate -> addStudioCss(array('admin.css', 'jquery-ui/jquery.ui.css', 'history.css', 'main.css'));
    }

    function getPageCode($bHidden = false, $bWrap = true)
    {
        $sMethod = 'get' . ucfirst($this->sPage);
        if(method_exists($this, $sMethod))
            return $this->$sMethod();

        $sObject = $this->_oModule->_oConfig->getGridObject($this->sPage);
        $oGrid = BxDolGrid::getObjectInstance($sObject, BxDolStudioTemplate::getInstance());
        if ($oGrid) {
            $sManageTools = $this->_oModule->_oTemplate->getJsCode('manage_tools', array('sObjNameGrid' => $sObject));
            $sUtils = $this->_oModule->_oTemplate->getJsCode('utils', array('sObjNameGrid' => $sObject));

            return $this->getBlockCode(array(
                'content' => $oGrid->getCode() . $sUtils . $sManageTools
            ));
        }

        return MsgBox(_t("_{$this -> _sModule}_installation_problem")) ;
    }

    protected function getMatrix()
    {
        bx_import('MatrixFormEntry',  $this->_oModule->_aModule);
        $oForm = new AqbAffiliateMatrixFormEntry();
        $oForm->initChecker();
        if ($oForm->isSubmittedAndValid()) {
            $aRes = array();
            if ($oForm->update())
                $aRes = array('grid' => $this->getCode(false));

            return echoJson($aRes);
        }

        $this->_oModule->_oTemplate->addStudioJsTranslation(['_aqb_affiliate_rebuild_confirm', '_aqb_affiliate_admin_commission_remove_data_confirm']);

        $sJsInit = $this -> _oModule -> _oTemplate -> getJsCode('utils', array(
            'sActionUrl' => BX_DOL_URL_ROOT . $this->_oModule->_oConfig->getBaseUri()
        ));

        $this -> _oModule -> _oTemplate -> addStudioCss(array('program.css'));
        return $this->getBlockCode(array(
            'content' => $sJsInit . $oForm->getCode(true)
        ));
    }

    protected function getSettings()
    {
       return $this->getBlockCode(array(
            'content' => parent::getSettings()
        ));
    }
}

/** @} */
