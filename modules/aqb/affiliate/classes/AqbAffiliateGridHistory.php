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

class AqbAffiliateGridHistory extends BxBaseModTextGridCommon
{
    private $_sCurrencySign = '';
    public function __construct($aOptions, $oTemplate = false)
    {
        $this->MODULE = 'aqb_affiliate';
        $this -> _aConfirmMessages = array(
            'delete' => _t("_{$this->MODULE}_remove_item_confirmation"),
        );

        parent::__construct($aOptions, $oTemplate);

        $this->init();
        $this->addMarkers(array(
            'profile_id' => (int)bx_get_logged_profile_id()
        ));

        $aCurrency = bx_srv('bx_payment', 'get_currency_info');
        if (!empty($aCurrency))
            $this->_sCurrencySign = $aCurrency['sign'];

        $this->_oModule = BxDolModule::getInstance($this->MODULE);
        $this->_aOptions['paginate_per_page'] = $this->_oModule->_oConfig->CNF['PER_PAGE_REFERRALS'];
        $this->_aOptions['show_total_count'] = false;
    }

    public function init()
    {
        $aSQLParts = $this -> _oModule -> _oDb->getContentAsSQLPart('activities');
        $aMarkers = [
            'join_history_table' => $aSQLParts['join'],
            'join_history_fields' => $aSQLParts['fields'],
            'join_history_where' => $aSQLParts['where'],
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
                $this->_aOptions['source'] .= $this->_oModule->_oDb->prepareAsString(" AND `h`.`{$CNF['FH_ADDED']}` BETWEEN UNIX_TIMESTAMP(?) AND UNIX_TIMESTAMP(?) ", $sStartDate, $sEndDate);
            }
        }

        if (isset($this->_aMarkers['is_studio']) && (int)$this->_aMarkers['is_studio'])
            $sFilter = '';

        if ($sFilter == 'unknown') {
            $this->_aOptions['source'] .= $this->_oModule->_oDb->prepareAsString(" AND `fullname` IS NULL");
            $sFilter = '';
        }

        return $this->__getDataSql($sFilter, $sOrderField, $sOrderDir, $iStart, $this -> _oModule ->_oConfig->CNF['PER_PAGE_REFERRALS'] + 1);
    }

    protected function _getFilterControls()
    {
        if (isset($this->_aMarkers['is_studio']) && (int)$this->_aMarkers['is_studio'])
            return $this->_oModule->_oTemplate->getActivitiesQuickInfo((int)$this->_aMarkers['profile_id'], false);

        return $this->_getSearchInput() . $this->_getFilterSelectDate();
    }

    protected function _getSearchInput()
    {
        $sJsObject = $this->_oModule->_oConfig->getJsObject('manage_tools');
        $sGridName = $this->_oModule->_oConfig->getGridObject('history');
        $aInputSearch = array(
            'type' => 'input_set',
            'attrs' => array(
                'id' => "{$sGridName}-search"
            ),
            array(
                'type' => 'text',
                'name' => 'search',
                'attrs' => array(
                    'id' => "{$sGridName}-search",
                    'onKeyUp' => 'javascript:$(this).off(\'keyup focusout\'); ' . $sJsObject . '.onChangeFilter(this)',
                    'onBlur' => 'javascript:' . $sJsObject . '.onChangeFilter(this)',
                    'placeholder' => _t("_{$this->MODULE}_page_common_date_search_activities")
                )
            )
        );

        $oForm = new BxTemplFormView(array());
        return $oForm->genRow($aInputSearch);
    }

    protected function _getFilterSelectDate()
    {

        $sJsObject = $this->_oModule->_oConfig->getJsObject('manage_tools');

        $aInputModules = array(
            'type' => 'input_set',
            'caption' => _t("_{$this->MODULE}_page_activities_date_filter_title"),
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
        if (isAdmin()){
            $sGrid = 'glGrids.'. $this -> _oModule -> _oConfig -> getGridObject('history');
            $sButton = <<<EOF
                <button onclick="$('.bx-popup-active').dolPopupHide({removeOnClose:true, onHide:() => delete $sGrid})" class="bx-btn bx-def-margin-thd">{$sTitle}</button>
EOF;
            $sButton =  parent::_getActionDefault($sType, $sKey, $a, $isSmall = false, $isDisabled = false, $aRow = array()) . $sButton;
        }

        return $sButton;
    }

    protected function _getCellCheckbox($mixedValue, $sKey, $aField, $aRow){
        return isAdmin() ? parent::_getCellCheckbox($mixedValue, $sKey, $aField, $aRow) : '';
    }

    protected function _getCellFullname($mixedValue, $sKey, $aField, $aRow){
        return $this->_oModule->_oTemplate->getProfileView($aRow['pid']);
    }

    private function getBannerView($iBannerId){
        $sContent = '------';
        $aBannerInfo = $this->_oModule->_oDb->getBannerInfo($iBannerId);
        if (empty($aBannerInfo))
            return $sContent;

        $CNF = &$this->_oModule->_oConfig->CNF;
        $oImageTranscoder = BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW']);
        if (!empty($oImageTranscoder)) {
            $sImage = $oImageTranscoder->getFileUrl($aBannerInfo[$CNF['FB_IMG']]);
            $sImage = '<img class="aqb-affiliate-banner-preview bx-def-border bx-def-box-sizing" src="' . $sImage . '" />';
            $sContent = '<td>
                               <div class="aqb-affiliate-banner-icon-title">' . $sImage . '<span>' . $aBannerInfo[$CNF['FB_TITLE']] . '</span></div>
                         </td>';
        }

        return $sContent;
    }

    protected function _getCellPerformer($mixedValue, $sKey, $aField, $aRow){
        $CNF = &$this->_oModule->_oConfig->CNF;
        return $aRow[$CNF['FH_TYPE']] === AQB_AFF_TYPE_REF ?
                    $this->_oModule->_oTemplate->getProfileView($mixedValue, 'performer') :
                    $this->getBannerView($mixedValue) ;
    }

    protected function _getCellAction($mixedValue, $sKey, $aField, $aRow){
        $CNF = &$this->_oModule->_oConfig->CNF;
        if ($mixedValue === AQB_AFF_CUSTOM && $aRow[$CNF['FH_INFO']])
            return '<td class="action">' . $aRow[$CNF['FH_INFO']] . '</td>';

        return '<td class="action">' .  _t("_{$this->MODULE}_grid_action_{$mixedValue}") . '</td>';
    }

    protected function _getCellHeaderCommissions($sKey, $aField)
    {
        if (!$this->_oModule->_oDb->isFiatEnabled())
            return false;

        $aField['title'] = _t('_aqb_affiliate_grid_column_history_commissions', $this->_sCurrencySign);
        return parent::_getCellHeaderDefault($sKey, $aField);
    }

    protected function _getCellCommissions($mixedValue, $sKey, $aField, $aRow){
        if (!$this->_oModule->_oDb->isFiatEnabled())
            return false;

        return '<td>' .  $aRow['commissions'] . '</td>';
    }

    protected function _getCellHeaderCredits($sKey, $aField)
    {
        if (!$this->_oModule->_oDb->isCreditsEnabled())
            return false;

        return parent::_getCellHeaderDefault($sKey, $aField);
    }

    protected function _getCellCredits($mixedValue, $sKey, $aField, $aRow){
        if (!$this->_oModule->_oDb->isCreditsEnabled())
            return false;

        return '<td>' .  $aRow['credits'] . '</td>';
    }

    protected function _getCellHeaderPoints($sKey, $aField)
    {
        if (!$this->_oModule->_oDb->isPointsEnabled())
            return false;

        return parent::_getCellHeaderDefault($sKey, $aField);
    }

    protected function _getCellPoints($mixedValue, $sKey, $aField, $aRow){
        if (!$this->_oModule->_oDb->isPointsEnabled())
            return false;

        return '<td>' .  $aRow['points'] . '</td>';
    }


    public function performActionDelete($aParams = array())
    {
        if (!isAdmin())
            return echoJson(array(
                'msg' => _t("_{$this->MODULE}_you_can_not_remove_referral")
        ));

        $iProfileId = bx_get('profile_id');
        $aItems = bx_get('ids');
        $bIsStudio = (bool)bx_get('is_studio');
        if (empty($aItems) || !$iProfileId)
            return echoJson(array(
                'msg' => _t("_{$this->MODULE}_no_data_to_process")
        ));

        $aResult = array();
        if($this->_oModule->_oDb->removeCommissionItem($iProfileId, $aItems)) {
            if ($bIsStudio) {
                $this->_aMarkers['profile_id'] = $iProfileId;
                $this->_aMarkers['is_studio'] = 1;

                $sReload = 'glGrids.' . $this->_oModule->_oConfig->getGridObject('members') . '.reload()';
                $sHTML = bx_js_string($this->_oModule->_oTemplate->getActivitiesQuickInfo($iProfileId, false));
                $aResult['eval'] =<<<EOF
                    const sHeader = $(oData.grid).find('.bx-grid-header').size() ? $(oData.grid).find('.bx-grid-header .aqb-affiliate-profile-info').html() : '';
                    $('#bx-grid-wrap-aqb_affiliate_history').find('.aqb-affiliate-profile-info').html(sHeader);
                    {$sReload}
EOF;
            }

            $aResult['grid'] = $this->getCode();
        }
         else
            $aResult = array('msg' => _t("_{$this->MODULE}_nothing_has_been_updated"));

        return echoJson($aResult);
    }

    protected function _isRowDisabled($aRow)
    {
        return !isAdmin();
    }
}

/** @} */
