<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    BaseGeneral Base classes for modules
 * @ingroup     UnaModules
 * @{
 */

class BxBaseModGeneralGrid extends BxTemplGrid
{
    protected $MODULE;
    protected $_sModule;
    protected $_oModule;

    protected $_sTableAlias;
    protected $_sParamsDivider;

    protected $_sJsObject;

    public function __construct ($aOptions, $oTemplate = false)
    {
        $this->MODULE = $this->MODULE ?: $this->_sModule;
        $this->_sModule = $this->_sModule ?: $this->MODULE;
    	$this->_oModule = BxDolModule::getInstance($this->_sModule);

        parent::__construct ($aOptions, $oTemplate ?: $this->_oModule->_oTemplate);

        $this->_sTableAlias = '';
        $this->_sParamsDivider = '#-#';
    }

    protected function _getFilterSelectOne($sFilterName, $sFilterValue, $aFilterValues, $mixedAddSelectOne = true, $bAsArray = false)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if($mixedAddSelectOne === true && ($sKey = $CNF['T']['filter_item_select_one_' . $sFilterName] ?? ''))
            $mixedAddSelectOne = $sKey;

        return parent::_getFilterSelectOne($sFilterName, $sFilterValue, $aFilterValues, $mixedAddSelectOne, $bAsArray);
    }

    protected function _initFilter($iNumber, $aValues = [])
    {
        $sFfName = '_sFilter' . $iNumber . 'Name';
        $sFfValue = '_sFilter' . $iNumber . 'Value';
        if(!property_exists($this, $sFfName) || !property_exists($this, $sFfValue))
            return;

        $this->$sFfName = 'filter' . $iNumber;
        if(($sFfValues = '_aFilter' . $iNumber . 'Values') && property_exists($this, $sFfValues))
            $this->$sFfValues = $aValues ?: [];

        if(($sValue = bx_get($this->$sFfName)) !== false) {
            $this->$sFfValue = bx_process_input($sValue);
            $this->_aQueryAppend[$this->$sFfName] = $this->$sFfValue;
        }
    }

    protected function _setFilterValue($iNumber, $mixedValue)
    {
        $sFfName = '_sFilter' . $iNumber . 'Name';
        $sFfValue = '_sFilter' . $iNumber . 'Value';

        $this->$sFfValue = bx_process_input($mixedValue);
        $this->_aQueryAppend[$this->$sFfName] = $this->$sFfValue;
    }

    protected function _parseFilterValue(&$sFilter)
    {
        $iFilterParts = substr_count($sFilter, $this->_sParamsDivider);
        if(!$iFilterParts)
            return;

        $aFilterParts = explode($this->_sParamsDivider, $sFilter);
        for($i = 1; $i <= $iFilterParts; $i++)
            if(($sField = '_sFilter' . $i . 'Value') && property_exists($this, $sField))
                $this->$sField = $aFilterParts[$i - 1];

        $sFilter = $aFilterParts[$iFilterParts];
    }

    protected function _getSqlField($sField)
    {
        return ($this->_sTableAlias ? '`' . $this->_sTableAlias . '`.' : '') . '`' . $sField . '`';
    }
}

/** @} */
