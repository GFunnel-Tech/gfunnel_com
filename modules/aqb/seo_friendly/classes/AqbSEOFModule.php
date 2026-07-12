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

class AqbSEOFModule extends BxBaseModTextModule
{
    function __construct(&$aModule)
    {
        parent::__construct($aModule);
    }

    function actionUpdateItem(){
        $iId = bx_get('id');
        $sUri = bx_get('uri');
        if ((!isAdmin() && !isModerator()) || !$iId)
            return [];

        return echoJson(['code' => $this->_oDb->updateUriById($sUri, $iId)]);
    }

    function actionUpdateUri(){
        $iId = bx_get('id');
        $sUri = bx_get('uri');
        $aUri = $this->_oDb->getUriById($iId);
        if ((!isAdmin() && !isModerator()) || empty($aUri) || !$sUri)
            return [];

        if ($aPageExisted = $this->_oDb->getPageByModulePageUri($aUri['module'], $aUri['page_uri'], $sUri)){
            return echoJson(['msg' => _t('_aqb_seof_page_uri_exists')]);
        }

        return echoJson(['code' => $this->_oDb->updateUri($aUri['uri'], $aUri['module'], $sUri)]);
    }

    function serviceGetLangsList(){
        $aLanguages = array_keys($this->_oDb->getLangs());
        $aValues = [];
        foreach($aLanguages as &$sLang)
            $aValues[$sLang] = _t('_aqb_seof_select_language_item_' . $sLang);

        return $aValues;
    }
}

/** @} */
