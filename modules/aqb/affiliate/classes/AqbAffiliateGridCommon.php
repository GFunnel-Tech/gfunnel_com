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

class AqbAffiliateGridCommon extends BxBaseModTextGridCommon
{
    public function __construct($aOptions, $oTemplate = false)
    {
        $this->MODULE = 'aqb_affiliate';
        $this -> _aConfirmMessages = array(
            'delete' => _t("_{$this->MODULE}_remove_item_confirmation"),
        );

        parent::__construct($aOptions, $oTemplate);
        $this -> init();

        $iProfileId = (int)bx_get('profile_id');
        $this->addMarkers(['profile_id' => $iProfileId ? $iProfileId : bx_get_logged_profile_id()]);

        if ((int)bx_get('is_studio'))
            $this->addMarkers(['is_studio' => (int)bx_get('is_studio')]);

        $this -> _oModule = BxDolModule::getInstance($this -> MODULE);

        $this->_aOptions['paginate_per_page'] = $this -> _oModule -> _oConfig -> CNF['PER_PAGE_REFERRALS'];
        $this->_aOptions['show_total_count'] = false;
    }

    public function init()
    {
        $aSQLParts = $this -> _oModule -> _oDb->getContentAsSQLPart('referrals');
        $aMarkers = [
            'join_referrals_table' => $aSQLParts['join'],
            'join_referrals_fields' => $aSQLParts['fields'],
            'join_referrals_where' => $aSQLParts['where'],
        ];

        $this -> addMarkers($aMarkers);
        $this->_aQueryAppendExclude = array_keys($aMarkers);
    }

    protected function _getDataSql($sFilter, $sOrderField, $sOrderDir, $iStart, $iPerPage)
    {
        if ($sFilter) {
            $sStartDate = $sEndDate = '';
            $aFilter = explode($this->_sParamsDivider, $sFilter);
            if (!empty($aFilter)) {
                $sFilter = isset($aFilter[0]) ? $aFilter[0] : $sFilter;

                if (isset($aFilter[1]))
                    $sStartDate = $aFilter[1];

                if (isset($aFilter[2]))
                    $sEndDate = $aFilter[2];
            }

            if ($sStartDate && $sEndDate) {
                $CNF = &$this->_oModule->_oConfig->CNF;
                $this->_aOptions['source'] .= $this->_oModule->_oDb->prepareAsString(" AND `r`.`{$CNF['FH_ADDED']}` BETWEEN UNIX_TIMESTAMP(?) AND UNIX_TIMESTAMP(?) ", $sStartDate, $sEndDate);
            }
        }

        if (isset($this->_aMarkers['is_studio']) && (int)$this->_aMarkers['is_studio'])
            $sFilter = '';

        return $this->__getDataSql($sFilter, $sOrderField, $sOrderDir, $iStart, $this -> _oModule ->_oConfig->CNF['PER_PAGE_REFERRALS'] + 1);
    }

    //--- Layout methods ---//
    protected function _getFilterControls()
    {
        return $this->_getSearchInput() . $this->_getFilterSelectDate();
    }

    protected function _getSearchInput()
    {
        $sJsObject = $this->_oModule->_oConfig->getJsObject('manage_tools');
        $aInputSearch = [
            'type' => 'input_set',
            'attrs' => [
                'id' => "{$this->MODULE}_common-search_activities"
            ],
            [
                'type' => 'text',
                'name' => 'search',
                'attrs' => [
                    'id' => "{$this->MODULE}_common-search",
                    'onKeyUp' => 'javascript:$(this).off(\'keyup focusout\'); ' . $sJsObject . '.onChangeFilter(this)',
                    'onBlur' => 'javascript:' . $sJsObject . '.onChangeFilter(this)',
                    'placeholder' => _t("_{$this->MODULE}_page_common_date_search_activities")
                ]
            ]
        ];

        $oForm = new BxTemplFormView([]);
        return $oForm->genRow($aInputSearch);
    }

    protected function _getFilterSelectDate()
    {

        $sJsObject = $this->_oModule->_oConfig->getJsObject('manage_tools');
        $sPrefix = $this->_oModule->_oConfig->getName();

        $aInputModules = array(
            'type' => 'input_set',
            'caption' => _t("_{$this->MODULE}_page_common_date_filter_title"),
            array
            (
                'type' => 'datepicker',
                'name' => 'date_from',
                'attrs' => array(
                    'id' => "{$this->_sObject}_start",
                    'placeholder' => _t("_{$this->MODULE}_page_common_date_filter_start")
                ),
            ),
            array
            (
                'type' => 'datepicker',
                'name' => 'date_to',
                'attrs' => array(
                    'id' => "{$this->_sObject}_end",
                    'placeholder' => _t("_{$this->MODULE}_page_common_date_filter_end")
                )
            ),
            array
            (
                'type' => 'button',
                'name' => 'filter',
                'value' => _t("_{$this->MODULE}_page_common_date_filter"),
                'attrs' => array(
                    'onClick' => "javascript:{$sJsObject}.onChangeFilter(this)",
                )
            ),
            array
            (
                'type' => 'button',
                'name' => 'clear',
                'value' => _t("_{$this->MODULE}_page_common_date_reset"),
                'attrs' => array(
                    'onClick' => "javascript:$(this).siblings('input').val(''); {$sJsObject}.onChangeFilter(this);",
                )
            )
        );

        $oForm = new BxTemplFormView($aInputModules, $this->_oModule->_oTemplate);
        $this->_oModule->_oTemplate->addCssJsTimePicker();
        return $oForm->genRow($aInputModules);
    }

    protected function _getCellHeaderCheckbox($sKey, $aField){
        return isAdmin() ? parent::_getCellHeaderCheckbox($sKey, $aField) : '';
    }

    protected function _getActionDelete($sType, $sKey, $a, $isSmall = false, $isDisabled = false, $aRow = array()){
        $sButton = '';
        $sTitle = _t("_{$this->MODULE}_admin_close");

        if (isset($this->_aMarkers['is_studio']) && (int)$this->_aMarkers['is_studio']){
        $sGrid = 'glGrids.'. $this -> _oModule -> _oConfig -> getGridObject('common');
        $sButton = <<<EOF
            <button onclick="$('.bx-popup-active').dolPopupHide({removeOnClose:true, onHide:() => delete $sGrid})" class="bx-btn bx-def-margin-thd">{$sTitle}</button>
EOF;
        }

        return isAdmin() ? parent::_getActionDefault($sType, $sKey, $a, $isSmall = false, $isDisabled = false, $aRow = array()) . $sButton : '';
    }

    protected function _getCellCheckbox($mixedValue, $sKey, $aField, $aRow){
        return isAdmin() ? parent::_getCellCheckbox($mixedValue, $sKey, $aField, $aRow) : '';
    }

    protected function _getCellRefType($mixedValue, $sKey, $aField, $aRow){
        $CNF = &$this->_oModule->_oConfig->CNF;
        $sTitle = $this->_oModule->_oConfig->getReferralLink($aRow['pid']);
        if ($aRow['ref_type'] == AQB_AFF_TYPE_BANNER && ($aBannerInfo = $this->_oModule->_oDb->getBannerInfo($aRow['banner'])))
            $sTitle = _t('_aqb_affiliate_referrals_grid_banner_info', $aBannerInfo[$CNF['FB_TITLE']]);

        return '<td title="' . $sTitle . '">' .  _t("_{$this->MODULE}_grid_type_{$mixedValue}") . '</td>';
    }

    protected function _getCellFullname($mixedValue, $sKey, $aField, $aRow){
        return $this->_oModule->_oTemplate->getProfileView($aRow['pid']);
    }

    protected function _getCellModule($mixedValue, $sKey, $aField, $aRow)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $this->_oModule->_oDb->updateHistoryItem($aRow[$CNF['FH_ID']], 0, $CNF['FH_NEW']);
        if (!(int)$aRow[$CNF['FH_ACTION_ID']])
            $mixedValue = _t("_{$this->MODULE}_no_modules");
        else
        {
            $aActionInfo = $this->_oModule->_oDb->getActionById($aRow[$CNF['FH_ACTION_ID']]);
            if (!empty($aActionInfo))
                $mixedValue = $aActionInfo[$CNF['FA_MODULE']] ? _t("_{$aActionInfo[$CNF['FA_MODULE']]}") : _t("_{$this->MODULE}_{$aActionInfo[$CNF['FA_UNIT']]}");
        }

        return parent::_getCellDefault($mixedValue, $sKey, $aField, $aRow);
    }

    protected function _getCellData($sKey, $aField, $aRow)
    {
        if ($sKey == 'desc')
            return $aRow[$sKey];

        return parent::_getCellData($sKey, $aField, $aRow);
    }

    public function performActionDelete($aParams = array())
    {
        if (!isAdmin())
            return echoJson(array(
                'msg' => _t("_{$this->MODULE}_you_can_not_remove_referral")
        ));

        $iProfileId = (int)bx_get('profile_id');
        $aRefListIds = bx_get('ids');

        if (empty($aRefListIds) || !$iProfileId)
            return echoJson(array(
                'msg' => _t("_{$this->MODULE}_no_data_to_process")
        ));

        if($this -> _oModule -> _oDb -> removeReferralsById($aRefListIds)) {
            $aResult = array(
                'grid' => $this->getCode(false),
            );

            if (isset($this->_aMarkers['is_studio']) && (int)$this->_aMarkers['is_studio'])
                $aResult['eval'] = 'glGrids.' . $this->_oModule->_oConfig->getGridObject('members') . '.reload()';
            else
                $aResult['eval'] = "loadDynamicBlockAuto($('div.aqb-affiliate-referral-info'));loadDynamicBlockAuto($('div.aqb-affiliate-referral-matrix-block'));";
        } else
            $aResult = array('msg' => _t("_{$this->MODULE}_nothing_has_been_updated"));

        return echoJson($aResult);
    }

    protected function _isRowDisabled($aRow)
    {
        return !isAdmin();
    }
}

/** @} */
