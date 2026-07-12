<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Affiliate System Affiliate System
 * @ingroup     UnaModules
 *
 * @{
 */

class AqbSEOFGridPage extends BxBaseModTextGridCommon
{
    private $_oDb = null;
    private $_oConfig = null;

    public function __construct ($aOptions, $oTemplate = false)
    {
    	$this -> MODULE = 'aqb_seof';
		$this -> _oModule = BxDolModule::getInstance($this->MODULE);
        $this -> _oDb = $this -> _oModule -> _oDb;
        $this -> _oConfig = $this -> _oModule -> _oConfig;

        $this -> _aConfirmMessages = ['clean' => _t("_{$this -> MODULE}_clean_confirmation"), 'remove' => _t("_{$this -> MODULE}_remove_content_confirmation")];

		$this -> init();
        parent::__construct ($aOptions, $oTemplate);
    }

    public function init()
    {
        $aSQLParts = $this->_oDb->getContentAsSQLPart(['module' => bx_get('module'), 'mode' => (int)bx_get('mode')]);
        $this -> addMarkers($aSQLParts);
    }

    protected function _getCellModule($mixedValue, $sKey, $aField, $aRow){
        return parent::_getCellDefault(_t("_{$mixedValue}"), $sKey, $aField, $aRow);
    }

    protected function _getCellUri($mixedValue, $sKey, $aField, $aRow){
        return parent::_getCellDefault($mixedValue, $sKey, $aField, $aRow);
    }

    protected function _getDataSql($sFilter, $sOrderField, $sOrderDir, $iStart, $iPerPage)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
        if ($sFilter)
            $this->_aOptions['source'] = str_replace("{addon}", $this->_oModule->_oDb->prepareAsString(" AND 
            (`{$CNF['FIELD_URI']}` LIKE CONCAT('%',?, '%') OR `{$CNF['FIELD_MODULE']}` LIKE CONCAT('%',?, '%'))", $sFilter, $sFilter), $this->_aOptions['source']);
         else
            $this->_aOptions['source'] = str_replace("{addon}", "", $this->_aOptions['source']);

        $this->_aOptions['filter_fields'] = [true];
        return $this->__getDataSql($sFilter, $sOrderField, $sOrderDir, $iStart, $iPerPage);
    }

    protected function _getDataSqlWhereClause($sFilter, &$sOrderByFilter)
    {
        return '';
    }

    public function performActionUpdate()
    {
        $iId = (int)bx_get('ids')[0];
        $aUri = $this->_oDb->getUriById($iId);

        $sJsObject = $this->_oModule->_oConfig->getJsObject('utils');
        $aInputs['inputs'] = [
            [
                'type' => 'text',
                'name' => 'uri',
                'value' => $aUri['uri'],
                'attrs' => [
                    'style' => "max-width:80%",
                ]
            ],
            [
                'type' => 'button',
                'name' => 'send',
                'value' => _t("_aqb_seof_submit_title"),
                'attrs' => [
                    'onClick' => "javascript:{$sJsObject}.onUpdateUri($iId);",
                    'class' => 'bx-btn-primary'
                ]
            ]
        ];

        $oForm = new BxTemplFormView($aInputs, $this->_oModule->_oTemplate);
        return echoJson([
            'popup' => [
                'html' => BxTemplFunctions::getInstance()->popupBox(time(), _t('_aqb_seof_all_uri_title', $aUri['uri'], _t('_' . $aUri['module'])),  $oForm->getCode()),
                'options' => ['closeOnOuterClick' => false]
            ],
        ]);
    }

    public function performActionLatin(){
        $sModule = bx_get('module');
        $aPages = $this->_oDb->findNotLatinUrls($sModule);
        $aConverted = [];
        foreach($aPages as &$aPage){
            $sUri = $aPage['uri'];
            if (!isset($aConverted[$sUri]))
                $aConverted[$sUri] = $this->_oDb->convertToLatin($sUri);
        }

        $iCountSkipped = $iCountDone = 0;
        foreach($aConverted as $sUri => $sUriNew){
            if (!$this->_oDb->getUriByUri($sUriNew) && $this->_oDb->updateUriByUri($sUri, $sUriNew))
                $iCountDone++;
            else
                $iCountSkipped++;
        }

        $oGrid = BxDolGrid::getObjectInstance($this -> _oConfig -> getGridObject('urls'));
        $aResult = ['grid' => $oGrid->getCode()];

        if ($iCountDone)
            $aResult['msg'] = _t('_aqb_seof_update_urls_latin_done', $iCountDone);

        if ($iCountSkipped)
            $aResult['msg'] = (isset($aResult['msg']) ? $aResult['msg'] . ' ' : '') . _t('_aqb_seof_update_urls_latin_skipped', $iCountSkipped);

        return echoJson($aResult);
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

    protected function _getFilterControls()
    {
        $sJsObject = $this->_oModule->_oConfig->getJsObject('utils');
        $aInputs = [
            'type' => 'input_set',
            [
                'type' => 'checkbox',
                'name' => 'view_not_latin',
                'label' => _t("_aqb_seof_view_not_latin_title"),
                'attrs' => [
                    'onChange' => "javascript:{$sJsObject}.switchMode(this);",
                    'class' => 'bx-btn-small'
                ]
            ],
            [
                'type' => 'select',
                'name' => 'modules',
                'caption' => _t("_aqb_seof_view_modules"),
                'values' => array_merge([_t('_aqb_seof_please_select')], $this->_oDb->getModulesList()),
                'attrs' => [
                    'onChange' => "javascript:{$sJsObject}.switchModule(this);"
                ]
            ],
            'attrs_wrapper' => [
               'class' => 'aqb-seof-urls-non-ascii'
             ]
        ];

        $oForm = new BxTemplFormView($aInputs, $this->_oModule->_oTemplate);
        return $oForm->genRow($aInputs) . $this->_getSearchInput();
    }

    public function performActionConvert()
    {
        if (!($iId = bx_get('ids')[0]))
            return [];

        $aUri = $this->_oDb->getUriById($iId);
        $sUri = $this->_oDb->convertToLatin($aUri['uri']);
        if ($aPageExisted = $this->_oDb->getPageByModulePageUri($aUri['module'], $aUri['page_uri'], $sUri))
            return echoJson(['msg' => _t('_aqb_seof_page_uri_exists')]);

        $this->_oModule->_oDb->updateUriByIdForUriAndModule($iId);
        return echoJson([
            'grid' => $this -> getCode(false),
            'eval' => "glGrids['aqb_seof_urls'].action('display');"
        ]);
    }

    public function performActionRemove()
    {
        if (!($iId = bx_get('ids')[0]))
            return [];

        $this->_oModule->_oDb->removeUriByIdForUriAndModule($iId);
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
