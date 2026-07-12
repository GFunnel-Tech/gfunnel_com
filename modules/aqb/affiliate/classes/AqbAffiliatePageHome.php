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
class AqbAffiliatePageHome extends BxBaseModGroupsPageBrowse
{
    public function __construct($aObject, $oTemplate = false)
    {
        $this->MODULE = 'aqb_affiliate';
        parent::__construct($aObject, $oTemplate);

        $this->_oModule = BxDolModule::getInstance($this->MODULE);
    }

    public function getCode(){
        if (!isLogged())
            return MsgBox(_t('_Empty'));

        $JsObject = $this->_oModule->_oConfig->getJsObject('utils');
        $sUtils = $this->_oModule->_oTemplate->getJsCode('utils', array('sObjNameGrid' => $JsObject));
        $this->_oModule->_oTemplate->addJs('utils.js');
        $this->_oModule->_oTemplate->addCss(array('profile.css', 'program.css', 'main.css'));
        return $sUtils . parent::getCode();
    }
}

/** @} */
