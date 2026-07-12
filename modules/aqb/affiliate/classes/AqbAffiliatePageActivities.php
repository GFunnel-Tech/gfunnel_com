<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defdroup    Channels Channels
 * @indroup     UnaModules
 *
 * @{
 */

/**
 * Browse entries pages.
 */
class AqbAffiliatePageActivities extends BxBaseModGroupsPageBrowse
{
    public function __construct($aObject, $oTemplate = false)
    {
        $this->MODULE = 'aqb_affiliate';
        parent::__construct($aObject, $oTemplate);

        $this->_oModule = BxDolModule::getInstance($this->MODULE);
    }

    public function getCode($isDisplayHeader = true){
        if (!isLogged())
            return MsgBox(_t('_Empty'));

        $sObject = $this->_oModule->_oConfig->getGridObject('history');
        $sTools = $this->_oModule->_oTemplate->getJsCode('manage_tools', array('sObjNameGrid' => $sObject));
        $sUtils = $this->_oModule->_oTemplate->getJsCode('utils', array('sObjName' => $this->_oModule->_oConfig->getJsObject('utils')));
        $this->_oModule->_oTemplate->addJs(array('manage_tools.js', 'utils.js'));
        $this->_oModule->_oTemplate->addCss(array('main.css', 'history.css'));
        return $sTools . $sUtils . parent::getCode();
    }
}

/** @} */
