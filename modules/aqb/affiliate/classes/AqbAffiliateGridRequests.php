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

class AqbAffiliateGridRequests extends BxBaseModGeneralGridAdministration
{
    private $_oDb = null;
    private $_oConfig = null;
    private $_sPrefix = null;
    private $_sJsObject = null;

    public function __construct ($aOptions, $oTemplate = false)
    {
    	$this->MODULE = 'aqb_affiliate';
		$this->_oModule = BxDolModule::getInstance($this->MODULE);
        $this->_oDb = $this -> _oModule -> _oDb;
        $this->_oConfig = $this -> _oModule -> _oConfig;
        $this->_sPrefix = '_' . $this->_oConfig->getName();
        $this->_sJsObject = $this->_oConfig->getJsObject('manage_tools');
		$this->_aConfirmMessages = ['remove' => _t( "_{$this->MODULE}_remove_txn_history_confirmation" )];

        parent::__construct ($aOptions, $oTemplate);
		$this -> init();
    }

    public function init()
    {
        $aSQLParts = $this->_oDb->getContentAsSQLPart('requests');
        $aMarkers = [
            'join_table' => $aSQLParts['join'],
            'join_fields' => $aSQLParts['fields'],
            'join_where' => $aSQLParts['where']
        ];

        $this -> addMarkers($aMarkers);
        $this->_aQueryAppendExclude = array_keys($aMarkers);
    }

    protected function _getCellDesign($sKey, $aField, $aRow){
        $aRow['added'] = $aRow['added'] ? bx_process_output($aRow['added'], BX_DATA_DATETIME_TS) : '-------------';
        $aRow['paid'] = $aRow['paid'] ? bx_process_output($aRow['paid'], BX_DATA_DATETIME_TS) : '-------------';
        return parent::_getCellDesign($sKey, $aField, $aRow);
    }

    protected function _getCellHeaderAmount($sKey, $aField)
    {
       $aField['title'] = (BxDolInstallerUtils::isModuleInstalled('payment') && BxDolModuleQuery::getInstance()->isEnabled('payment')) ? _t("_{$this->MODULE}_admin_request_title_amount", bx_srv('bx_payment', 'get_currency_info')['sign']) : _t("_{$this->MODULE}_admin_request_title_unknown");
       return parent::_getCellHeaderDefault($sKey, $aField);
    }

    protected function _getCellAdded($mixedValue, $sKey, $aField, $aRow)
    {
      return "<td>{$mixedValue}</td>";
    }

    protected function _getRowHead ()
    {
        $aRet = array();
        foreach ($this->_aOptions['fields'] as $sKey => $a) {
            if ($sKey === 'points' && !$this->_oDb->isPointsEnabled())
                continue;

            if ($sKey === 'credits' && !$this->_oDb->isCreditsEnabled())
                continue;

            if ($sKey === 'amount' && !$this->_oDb->isFiatEnabled())
                continue;

            $sMethod = '_getCellHeaderDefault';
            $sCustomMethod = '_getCellHeader' . $this->_genMethodName($sKey);
            if (method_exists($this, $sCustomMethod))
                $sMethod = $sCustomMethod;

            $aRet[] = array('header_cell' => $this->$sMethod($sKey, $a));
        }
        return $aRet;
    }

    protected function _getCellFullName($mixedValue, $sKey, $aField, $aRow)
    {
        $oProfile = BxDolProfile::getInstance($aRow['pid']);
        return '<td>
                    <div class="profile-info-link-icon m-1">
                        <img src="' . $oProfile -> getThumb() . '" />
                            <a target="_blank" href="' . $oProfile -> getUrl() . '">' . $mixedValue  . '</a>
                    </div>
                </td>';
    }

    protected function _getCellStatus($mixedValue, $sKey, $aField, $aRow)
    {
        $sClass = '';
        $iStatus = (int)$aRow[$this->_oConfig->CNF['FC_STATUS']];
        if ($iStatus === AQB_AFF_STATUS_PENDING)
            $sClass = 'class="aqb-row-red"';

        return "<td $sClass>" . _t('_aqb_affiliate_admin_status_' . $iStatus) . "</td>";
    }

    private function getManuallyForms($iTnx)
    {
        $aTnxInfo = $this -> _oDb -> getTxnInfo($iTnx);
        $CNF = &$this->_oConfig->CNF;
        $aForm = array(
            'form_attrs' => array(
                'name' => 'allocate',
                'method' => 'post'
            ),
            'inputs' => array(
                'tnx' =>	array(
                    'type' => 'text',
                    'name' => 'tnx',
                    'required' => 1,
                    'value' => $aTnxInfo[$CNF['FC_TXN']],
                    'caption' =>  _t("_{$this->MODULE}_admin_tnx_id_caption")
                ),
                'status' =>	array(
                    'type' => 'radio_set',
                    'name' => 'status',
                    'value' => $aTnxInfo[$CNF['FC_STATUS']],
                    'caption' => _t("_{$this->MODULE}_admin_status_caption"),
                    'values' => array(
                                        AQB_AFF_STATUS_PENDING => _t("_{$this->MODULE}_admin_status_" . AQB_AFF_STATUS_PENDING),
                                        AQB_AFF_STATUS_PAID => _t("_{$this->MODULE}_admin_status_" . AQB_AFF_STATUS_PAID
                                    ))
                ),
                'buttons' => array(
                    'type' => 'input_set',
                    0 => array(
                        'type' => 'button',
                        'name' => 'save',
                        'value' => _t("_{$this->MODULE}_admin_save"),
                        'attrs' => array('onclick' => "javascript:{$this->_sJsObject}.manuallyUpdateTnx('{$iTnx}')")
                    ),
                    1 => array(
                        'type' => 'reset',
                        'name' => 'close',
                        'value' => _t("_{$this->MODULE}_close"),
                        'attrs' => array('onclick' => "$(this).closest('.bx-popup-applied:visible').dolPopupHide()")
                    )
                )
            )
        );

        $oForm = new BxTemplFormView($aForm);
        return $oForm -> getCode();
    }

    public function performActionManually()
    {
        $iId = bx_get('ids')[0];
        $sCode = $this -> getManuallyForms($iId);
        $sTitle = _t("_{$this->MODULE}_admin_tnx_manually");

       return echoJson(
           array('popup' =>  array(
               'html' => BxTemplFunctions::getInstance()->popupBox(time(), $sTitle, $sCode),
               'options' => array('closeOnOuterClick' => false)
           ))
        );
    }

    public function performActionProcessTnx()
    {
        $iTnx = (int)bx_get('id');
        $sTxn = bx_get('value');
        $sStatus = bx_get('status');

        if (!$iTnx || !$sTxn)
            return echoJson(
                array('msg' => _t("_{$this->MODULE}_admin_tnx_empty_fields_error"))
            );

        $CNF = &$this->_oConfig->CNF;
        $aTnxInfo = $this->_oDb->getTxnInfo($iTnx);
        if ($this->_oDb->updateTnxInfo($iTnx, array(
                $CNF['FC_TXN'] => $sTxn,
                $CNF['FC_STATUS'] => $sStatus,
                $CNF['FC_PAID'] => $sStatus == AQB_AFF_STATUS_PENDING ? 0 : time(),
            )))
        {
            if ($this->_oDb->isPointsEnabled() && $aTnxInfo[$CNF['FC_POINTS']]) {
                $sMessage = _t('_aqb_affiliate_profile_commission_cash_out_points');
                $fPoints = $aTnxInfo[$CNF['FC_POINTS']];
                if ($sStatus == AQB_AFF_STATUS_PENDING) {
                    $sMessage = _t('_aqb_affiliate_profile_commission_cash_out_cancelled_points');
                    $fPoints = -$fPoints;
                    $this->_oDb->updateTnxInfo($iTnx, [$CNF['FC_PPOINTS'] => 0]);
                }

                if (!(int)$aTnxInfo[$CNF['FC_PPOINTS']] || $sStatus == AQB_AFF_STATUS_PENDING) {
                    BxDolService::call('aqb_points', 'assign_points', array($fPoints, $sMessage, $aTnxInfo[$CNF['FC_PROFILE_ID']]));
                }
            }

            if ($this->_oDb->isCreditsEnabled() && $aTnxInfo[$CNF['FC_CREDITS']]) {
                if ($sStatus == AQB_AFF_STATUS_PENDING && (int)$aTnxInfo[$CNF['FC_PCREDITS']]) {
                    $oCreditsModule = BxDolModule::getInstance('bx_credits');
                    if ($oCreditsModule && $oCreditsModule->_oDb->deleteHistory(['id' => (int)$aTnxInfo[$CNF['FC_PCREDITS']]])) {
                        $this->_oDb->updateTnxInfo($iTnx, [$CNF['FC_PCREDITS'] => 0]);
                        $oCreditsModule->_oDb->updateProfileBalance($aTnxInfo[$CNF['FC_PROFILE_ID']], -$aTnxInfo[$CNF['FC_CREDITS']]);
                    }
                }

                if (!(int)$aTnxInfo[$CNF['FC_PCREDITS']] && $sStatus == AQB_AFF_STATUS_PAID) {
                    $iCreditsTnxId = bx_srv('bx_credits', 'update_profile_balance', [$aTnxInfo[$CNF['FC_PROFILE_ID']], $aTnxInfo[$CNF['FC_CREDITS']], 0, '', _t('_aqb_affiliate_profile_commission_cash_out_credits')]);
                    $this->_oDb->updateTnxInfo($iTnx, [$CNF['FC_PCREDITS'] => $iCreditsTnxId]);
                }
            }
        }

        echoJson(
            array(
                'grid' => $this -> getCode(false),
                'eval' => "$('.bx-popup-active').dolPopupHide();"
            )
        );
    }

    protected function _getActionCard ($sType, $sKey, $a, $isSmall = false, $isDisabled = false, $aRow = array())
    {
        if (!BxDolInstallerUtils::isModuleInstalled('payment')){
            $a['attr']['class'] = 'bx-btn-disabled';
            $a['attr']['title'] = _t("_{$this->MODULE}_admin_tnx_has_no_active_card");
        }

        return parent::_getActionDefault($sType, $sKey, $a, $isSmall, $isDisabled, $aRow);
    }

    public function performActionCard()
    {
        $CNF = &$this->_oConfig->CNF;
        $iId = bx_get('ids')[0];

        $aTnxInfo = $this->_oDb->getTxnInfo($iId);
        $aParams = array($aTnxInfo[$CNF['FC_PROFILE_ID']], $this->_oConfig->getId(), $aTnxInfo[$CNF['FC_ID']], 1);
        $aReturn = BxDolService::call('bx_payment', 'add_to_cart', $aParams, 'Cart');
        echoJson(array('msg' => $aReturn['message']));
    }

	public function performActionRemove()
	{
        $iTnx = (int)bx_get('ids')[0];
        $this -> _oDb -> removeTnx($iTnx);
		echoJson(array(
			'grid' => $this -> getCode(false),
			'blink' => $iTnx,
		));	
	}

    protected function _getDataSqlCounter($sQuery, $sFilter)
    {
        $oDb = BxDolDb::getInstance();
        $sQuery = preg_replace("/^SELECT.*FROM/mUs", "SELECT COUNT(*) FROM ", $sQuery);
        return $oDb->getOne($sQuery);
    }
}

/** @} */
