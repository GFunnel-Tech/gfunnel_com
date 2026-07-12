<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Posts Posts
 * @ingroup     UnaModules
 * 
 * @{
 */

class AqbSEOFGridLinks extends BxBaseModTextGridCommon
{
    public function __construct ($aOptions, $oTemplate = false)
    {
        $this->MODULE = 'aqb_seof';
        $this->_oModule = BxDolModule::getInstance($this->MODULE);
        parent::__construct ($aOptions, $oTemplate);
        $this->init();
    }

    public function init()
    {
        $aSQLParts = $this->_oModule->_oDb->getContentAsSQLPart();
        $this -> addMarkers([
            'where' => ''
        ]);
    }

    protected function _getCellUrl($mixedValue, $sKey, $aField, $aRow){
        $CNF = &$this->_oModule->_oConfig->CNF;
        $oPermalink = BxDolPermalinks::getInstance();
        $sUrl = bx_absolute_url($oPermalink->permalink('page.php?i=' . $aRow['page_uri'] . '&' . $aRow['param_name'] . '=' .  $aRow['param_value']));
        $mixedValue = '<a href="' . $sUrl . '" target="_blank">' . $sUrl . '</a>';
        return parent::_getCellDefault($mixedValue, $sKey, $aField, $aRow);
    }

    protected function _getCellParam($mixedValue, $sKey, $aField, $aRow){
        $CNF = &$this->_oModule->_oConfig->CNF;

        $oForm = new BxTemplFormView([]);
        $aInput = ['type' => 'text', 'value' => $aRow['uri'], 'name' => 'rec_' . $aRow['id']];
        $mixedValue = $oForm->genInput($aInput);
        return parent::_getCellDefault($mixedValue, $sKey, $aField, $aRow);
    }

    protected function _getDataSql($sFilter, $sOrderField, $sOrderDir, $iStart, $iPerPage)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if ($sFilter) {
            $this->_aOptions['source'] = str_replace("{where}", $this->_oModule->_oDb->prepareAsString(" AND 
            (`{$CNF['FIELD_URI']}` LIKE CONCAT('%',?, '%') OR `{$CNF['FIELD_MODULE']}` LIKE CONCAT('%',?, '%'))", $sFilter, $sFilter), $this->_aOptions['source']);
        } else
            $this->_aOptions['source'] = str_replace("{where}", "", $this->_aOptions['source']);

        return $this->__getDataSql($sFilter, $sOrderField, $sOrderDir, $iStart, $iPerPage);
    }

    public function performActionGet()
    {
        $iId = bx_get('id');
        if ($aUri = $this->_oModule->_oDb->getUriById($iId)) {
            $oGrid = BxDolGrid::getObjectInstance($this->_oModule->_oConfig->getGridObject('links'));
            $oGrid->addMarkers($aUri);
            return echoJson([
                'grid' => $this -> getCode(false),
                'blink' => $iId,
            ]);
        }
    }

    public function performActionSave()
    {
        $iId = bx_get('ids')[0];
        $sObject = $this->_oModule->_oConfig->getJsObject('utils');
        echoJson(['eval' => $sObject . ".onUpdateUriItem($iId)"]);
    }

    public function performActionView(){
        $iId = (int)bx_get('ids')[0];
        if (!$iId || !($aUri = $this->_oDb->getUriById($iId)))
            return echoJson(['msg' => _t('_aqb_seof_no_results')]);

        $oGrid = BxDolGrid::getObjectInstance($this -> _oConfig -> getGridObject('links'));
        $oGrid->addMarkers($aUri);
        return echoJson([
            'popup' => [
                'html' => BxTemplFunctions::getInstance()->popupBox(time(),
                    _t('_aqb_seof_uri_links', $aUri['uri'], _t("_{$aUri['module']}")),
                    $oGrid->getCode()),
                'options' => array('closeOnOuterClick' => false)
            ],
        ]);
    }

    public function performActionConvert()
    {
        if (!($iId = bx_get('ids')[0]))
            return [];

        $aUri = $this->_oModule->_oDb->getUriById($iId);
        $sUri = $this->_oModule->_oDb->convertToLatin($aUri['uri']);

        $this->_oModule->_oDb->updateUriById($sUri, $iId);

        $oGrid = BxDolGrid::getObjectInstance($this->_oModule->_oConfig->getGridObject('links'));
        $oGrid->addMarkers($aUri);

        return echoJson([
            'grid' => $oGrid->getCode(false),
            'eval' => "glGrids['aqb_seof_urls'].action('display');"
        ]);
    }

    public function performActionRemove()
    {
        if (!($iId = bx_get('ids')[0]))
            return [];

        $this->_oModule->_oDb->removeUriById($iId);
        return echoJson([
            'grid' => $this -> getCode(false),
            'eval' => "glGrids['aqb_seof_urls'].action('display');"
        ]);
    }

    protected function _isRowDisabled($aRow)
    {
        return false;
    }

}

/** @} */
