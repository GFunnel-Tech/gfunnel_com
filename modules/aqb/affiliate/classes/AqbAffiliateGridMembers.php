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

class AqbAffiliateGridMembers extends BxBaseModGeneralGridAdministration
{
    private $_oDb = null;
    private $_oConfig = null;
    private $_sJsObject = null;
    private $_sJsUtils = null;
    private $_sPrefix = null;

    public function __construct ($aOptions, $oTemplate = false)
    {
    	$this -> MODULE = 'aqb_affiliate';
		$this -> _oModule = BxDolModule::getInstance($this -> MODULE);
        $this -> _oDb = $this -> _oModule -> _oDb;
        $this -> _oConfig = $this -> _oModule -> _oConfig;
        $this -> _sJsUtils = $this->_oConfig->getJsObject('utils');
        $this -> _sJsObject = $this->_oConfig->getJsObject('manage_tools');

        $this -> _aConfirmMessages = array(
											'clean' => _t("_{$this -> MODULE}_clean_confirmation"),
											'remove' => _t("_{$this -> MODULE}_remove_content_confirmation"),
										  );

        parent::__construct ($aOptions, $oTemplate);
	$this -> init();
    }

    public function init()
    {
        $aSQLParts = $this->_oDb->getContentAsSQLPart();
        $aMarkers = [
            'join_members_history' => $aSQLParts['join'],
            'join_members_fields' => $aSQLParts['fields'],
            'join_members_where' => $aSQLParts['where'],
        ];

        $this -> addMarkers($aMarkers);
        $this->_aQueryAppendExclude = array_keys($aMarkers);

        $this->_oModule->_oTemplate->addJsTranslation(array('_aqb_affiliate_import_file_not_found'));
    }

    protected function _getCellDesign($sKey, $aField, $aRow){
        $aRow['added'] = $aRow['added'] ? bx_process_output($aRow['added'], BX_DATA_DATE_TS) : _t("_{$this -> MODULE}_admin_empty_line");
        $aRow['logged'] = $aRow['logged'] ? bx_process_output($aRow['logged'], BX_DATA_DATE_TS) : _t("_{$this -> MODULE}_admin_empty_line");
        return parent::_getCellDesign($sKey, $aField, $aRow);
    }

    protected function _getCellUpgrades($mixedValue, $sKey, $aField, $aRow)
    {
        return '<td>' .  (int)$mixedValue . '</td>';
    }
    protected function _getCellPurchases($mixedValue, $sKey, $aField, $aRow)
    {
        return '<td>' .  (int)$mixedValue . '</td>';
    }

    protected function _getCellMarkets($mixedValue, $sKey, $aField, $aRow)
    {
        return '<td>' .  (int)$mixedValue . '</td>';
    }

    protected function _getCellReferral($mixedValue, $sKey, $aField, $aRow)
    {
        return (int)$mixedValue ? $this -> _oModule -> _oTemplate -> getProfileView($mixedValue) : '<td>------</td>';
    }

    protected function _getCellReferred($mixedValue, $sKey, $aField, $aRow)
    {
        return '<td>' . (int)$mixedValue . '</td>';
    }

    protected function _getCellClicks($mixedValue, $sKey, $aField, $aRow)
    {
        return '<td>' .  (float)$mixedValue . '</td>';
    }

    protected function _getCellImpressions($mixedValue, $sKey, $aField, $aRow)
    {
        return '<td>' .  (float)$mixedValue . '</td>';
    }

    protected function _getCellHeaderEarned($sKey, $aField)
    {
        $aTitle = array();
        if ($this -> _oDb -> isFiatEnabled())
            $aTitle[] =  bx_srv('bx_payment', 'get_currency_info')['sign'];

        if ($this -> _oDb -> isPointsEnabled())
            $aTitle[] = _t("_{$this -> MODULE}_admin_points_title");

        if ($this -> _oDb -> isCreditsEnabled())
            $aTitle[] = _t("_{$this -> MODULE}_admin_credits_title");

        $aField['title'] ='';
        if(!empty($aTitle))
            $aField['title'] = _t("_{$this -> MODULE}_admin_members_title_earned", implode(' | ', $aTitle));

        return parent::_getCellHeaderDefault($sKey, $aField);
    }

    protected function _getCellEarned($mixedValue, $sKey, $aField, $aRow)
    {
        $aValues = [];
        if ($this -> _oDb -> isFiatEnabled())
            $aValues[] = (float)$mixedValue;

        if ($this -> _oDb -> isPointsEnabled())
            $aValues[] = bx_srv('aqb_points', 'get_format_points', [$aRow[$this->_oConfig->CNF['FI_POINTS']]]);

        if ($this -> _oDb -> isCreditsEnabled())
            $aValues[] = (float)$aRow[$this->_oConfig->CNF['FI_CREDITS']];

        return '<td>' .  implode('|', $aValues) . '</td>';
    }

	protected function _getCellFullName($mixedValue, $sKey, $aField, $aRow)
    {
        return $this -> _oModule -> _oTemplate -> getProfileView($aRow['id']);
    }

    public function performActionAddReferral()
    {
        $iUserId = (int)bx_get('ids')[0];
        $sContent = $this->editReferralForm($iUserId);
        $sTitle = _t("_{$this -> MODULE}_admin_change_referral", BxDolProfile::getInstance($iUserId) -> getDisplayName());
        echoJson(array(
                            'popup' => array(
                                    'html' => BxTemplFunctions::getInstance()->popupBox(time(), $sTitle, $sContent),
                                    'options' => array('closeOnOuterClick' => false, 'removeOnClose' => true)
                                ),
                         ));
    }

    public function performActionRewardCommission()
    {
        $iProfileId = (int)bx_get('profile');
        $fAmount = bx_get('amount');
        $sType = bx_get('type');
        $sCurrType = bx_get('curr_type');
        $sDesc = bx_get('desc');
        if (!$iProfileId || !$fAmount || !$sType)
            return echoJson(['code' => 0, 'message' => _t('_aqb_affiliate_admin_reward_form_error')]);

        $aParams = [
            'profile' => $iProfileId,
            'performer' => $iProfileId,
            'type' => AQB_AFF_TYPE_REF,
            'action' => $sType,
            'count' => 1,
            'commission' => 0,
            'points' => 0,
            'credits' => 0,
            'info' => $sDesc ? $sDesc : _t('_aqb_affiliate_reward_default_custom_explanation')
        ];

        if ($sCurrType === AQB_AFF_CUR_TYPE_CURRENCY)
            $aParams['commission'] = $fAmount;
        else
            $aParams[$sCurrType] = $fAmount;

        $this->_oConfig->logData($aParams, false);

        if ($this->_oDb->registerActionInHistory($aParams))
                return echoJson(['grid' => $this -> getCode(false),
                                  'blink' => $iProfileId,
                                  'eval' => "$('.bx-popup-active').dolPopupHide();" . 'glGrids.' . $this->_oModule->_oConfig->getGridObject('members') . '.reload()']);

        echoJson(['grid' => $this -> getCode(false)]);
    }

    public function performActionAddCommission()
    {
        $iProfileId = (int)bx_get('ids')[0];
        $sContent = $this->addCommissionForm($iProfileId);
        $sTitle = _t("_{$this -> MODULE}_admin_reward_commission", BxDolProfile::getInstance($iProfileId) -> getDisplayName());

        echoJson(array(
            'popup' => array(
                'html' => BxTemplFunctions::getInstance()->popupBox(time(), $sTitle, $sContent),
                'options' => array('closeOnOuterClick' => false)
            ),
        ));
    }

    public function performActionHistory()
    {
        $iProfileId = (int)bx_get('ids')[0];
        $oGrid = BxDolGrid::getObjectInstance($this->_oConfig->getGridObject('history'));
        $oGrid->addMarkers(array(
            'profile_id' => $iProfileId,
            'is_studio' => 1
        ));

        return echoJson(array(
                            'popup' => array(
                                    'html' => BxTemplFunctions::getInstance()->popupBox(time(),
                                               _t('_aqb_affiliate_studio_history_commission_title', BxDolProfile::getInstance($iProfileId)->getDisplayName()),
                                               $oGrid->getCode()),
                                    'options' => array('closeOnOuterClick' => false)
                                ),
                         ));
    }

    public function performActionUpdateReferral(){
        $iReferral = (int)bx_get('referral');
        $iProfileId = (int)bx_get('profile');

        $aResult = array('code' => 1, 'msg' => _t('_aqb_affiliate_nothing_has_been_updated'));
        if (!$iReferral || !$iProfileId)
            $aResult = array('code' => 1, 'msg' => _t('_aqb_affiliate_wrong_data'));
        else
            if ($this -> _oDb -> saveReferral($iProfileId, $iReferral))
                $aResult = array(
                    'code' => 0,
                    'grid' => $this -> getCode(false),
                    'eval' => "$('.bx-popup-active').dolPopupHide();"
                );

        echoJson($aResult);
    }

    public function performActionImport()
    {
        $iProfileId = (int)bx_get('ids')[0];
        echoJson(array('eval' => "oAqbAffiliateUtils.onDataImport({$iProfileId});"));
    }

    private function editReferralForm($iProfileId)
    {
        $aForm = array(
            'form_attrs' => array(
                'name' => 'referral',
                'method' => 'post'
            ),
            'inputs' => array());

        $iSponsorId = $this->_oDb->getSponsorProfileId($iProfileId);
        if ($iSponsorId && ($oReferral = BxDolProfile::getInstance($iSponsorId))) {
            $aForm['inputs']['current'] = array(
                'type' => 'custom',
                'content' => $this->_oTemplate->parseHtmlByName('admin-referral.html', array(
                    'thumb' => $oReferral -> getThumb(),
                    'title' => $oReferral -> getDisplayName(),
                    'profile' => $iProfileId,
                    'js_object' => $this->_sJsObject,
                    'id' => $iProfileId
                ))
            );
        }

        $aForm['inputs'] = array_merge($aForm['inputs'], array(
                'profile' => array(
                    'type' => 'hidden',
                    'name' => 'profile',
                    'value' => $iProfileId
                ),
                'referral' =>	array(
                    'type' => 'text',
                    'name' => 'referral',
                    'caption' =>  _t("_{$this -> MODULE}_admin_referral_caption"),
                    'attrs' => array(
                        'onkeyup' => "javascript:{$this->_sJsUtils}.onSearch(this, {$iProfileId});",
                        'placeholder' => _t("_{$this -> MODULE}_admin_type_name"),
                    )
                ),
                'desc' =>	array(
                    'type' => 'custom',
                    'content' => '<div id="admin-search-result"></div>'
                ),
                'buttons' => array(
                    'type' => 'input_set',
                    0 => array(
                        'type' => 'reset',
                        'name' => 'close',
                        'value' => _t("_{$this -> MODULE}_close"),
                        'attrs' => array('onclick' => "$(this).closest('.bx-popup-applied:visible').dolPopupHide({removeOnClose:true})")
                    )
                )
            )
        );

        $oForm = new BxTemplFormView($aForm);
        return $oForm -> getCode();
    }

    private function addCommissionForm($iProfileId)
    {
        $CNF = &$this->_oConfig->CNF;

        $aCommissionCurrType = [];
        if ($this -> _oDb -> isFiatEnabled())
            $aCommissionCurrType[AQB_AFF_CUR_TYPE_CURRENCY] =  bx_srv('bx_payment', 'get_currency_info')['sign'];

        if ($this -> _oDb -> isPointsEnabled())
            $aCommissionCurrType[AQB_AFF_CUR_TYPE_POINTS] = _t("_{$this -> MODULE}_admin_points_title");

        if ($this -> _oDb -> isCreditsEnabled())
            $aCommissionCurrType[AQB_AFF_CUR_TYPE_CREDITS] = _t("_{$this -> MODULE}_admin_credits_title");

        $aForm = [
            'form_attrs' => [
                'name' => 'add_commission',
                'action' => BX_DOL_URL_ROOT . 'grid.php?o=' . $CNF['OBJECT_GRID_MEMBERS'] . '&a=reward_commission',
                'method' => 'post'
            ],
            'params' => array(
                'ajax_mode' => true,
            ),
            'inputs' => [
                'profile' => [
                    'type' => 'hidden',
                    'name' => 'profile',
                    'value' => $iProfileId
                ],
                'type' => [
                    'type' => 'select',
                    'name' => 'type',
                    'values' => [
                            0 => _t("_{$this -> MODULE}_admin_select_empty"),
                            'clicks' => _t("_{$this -> MODULE}_history_title_clicks"),
                            'impressions' => _t("_{$this -> MODULE}_history_title_impressions"),
                            'joins' => _t("_{$this -> MODULE}_history_title_joins"),
                            'upgrades' => _t("_{$this -> MODULE}_history_title_upgrades"),
                            'markets' => _t("_{$this -> MODULE}_history_title_markets"),
                            'purchases' => _t("_{$this -> MODULE}_history_title_purchases"),
                            //'referred' => _t("_{$this -> MODULE}_history_title_referred"),
                            'custom' => _t("_{$this -> MODULE}_history_title_custom"),
                        ],
                    'attrs' => [
                        'onchange' => "javascript:{$this->_sJsUtils}.hideDesc(this);",
                        'placeholder' => _t("_{$this -> MODULE}_admin_type_name"),
                    ]
                ],
                'desc' => [
                    'type' => 'text',
                    'name' => 'desc',
                    'caption' => _t("_{$this -> MODULE}_admin_reward_commission_desc"),
                    'info' => _t("_{$this -> MODULE}_admin_reward_commission_desc_info"),
                    'attrs' => ['placeholder' => _t('_aqb_affiliate_reward_default_custom_explanation')]
                ],
                'curr_type' => [
                    'type' => 'select',
                    'name' => 'curr_type',
                    'caption' =>  _t("_{$this -> MODULE}_admin_select_commission_type_caption"),
                    'values' => $aCommissionCurrType
                ],
                'amount' => [
                    'type' => 'text',
                    'name' => 'amount',
                    'caption' => _t("_{$this -> MODULE}_admin_reward_commission_amount")
                ],
                'buttons' => [
                    'type' => 'input_set',
                    [
                        'type' => 'submit',
                        'name' => 'reward',
                        'value' => _t("_{$this -> MODULE}_admin_button_reward")
                    ]
                ]
            ]
        ];

        $oForm = new BxTemplFormView($aForm);
        return $oForm -> getCode();
    }

    public function performActionAssign()
    {
       $iProfileId = bx_get('profile_id');
       $sAction = bx_get('action');
       $sDesc = bx_get('desc');

       $fPoints = (float)bx_get(AQB_POINTS_NAME);
       $fPoints = $sAction == 'allocate' ? $fPoints : -$fPoints;

       if ($fPoints && $iProfileId)
           BxDolService::call($this->_oConfig->getName(), 'assign_points', array($fPoints, $sDesc, $iProfileId));
       else
           return echoJson(array(
               'msg' => _t( $this -> _sPrefix . '_nothing_assigned'),
               'blink' => $iProfileId,
           ));

       return echoJson(array(
           'grid' => $this -> getCode(false),
           'blink' => array(),
           'eval' => "$('.bx-popup-active').dolPopupHide({removeOnClose:true});"
        ));
    }

    public function performActionReferred()
    {
        $iReferral = bx_get('ids')[0];
        $oGrid = BxDolGrid::getObjectInstance($this->_oConfig->getGridObject('common'));

        $oGrid->addMarkers(array(
            'profile_id' => $iReferral,
            'is_studio' => 1
        ));

        $sContent = BxTemplFunctions::getInstance()->popupBox(
                time(),
                _t("_{$this -> MODULE}_view_ref_user_history", BxDolProfile::getInstance($iReferral)->getDisplayName()),
                $oGrid->getCode(false));

        echoJson(array(
              'popup' => array(
                     'html' => $sContent,
                     'options' => array('closeOnOuterClick' => false)
               ),
              'blink' => $iReferral
        ));
    }

    public function performActionRemoveReferral(){
        $iProfileId = bx_get('profile');
        if (!$iProfileId || !isAdmin())
            return echoJson(array('msg' => _t('_aqb_affiliate_studio_members_referral_no_updates')));

        $aResult = array('msg' => _t('_aqb_affiliate_studio_members_referral_no_updates'));
        if ($this->_oDb->removeReferred($iProfileId)) {
            $sJS=<<<EOF
        $('#aqb-affiliate-sponsor-{$iProfileId}').closest('.bx-form-element-wrapper').fadeOut(function(){
                $(this).remove();
            }
        );
EOF;
            $aResult = array(
                'grid' => $this->getCode(false),
                'eval' => $sJS
            );
        }

        echoJson($aResult);
    }

	public function performActionRemove()
	{
		$iProfileId = bx_get('ids')[0];
		$this ->_oDb->removeCommissionItem($iProfileId);
		echoJson(array(
			'grid' => $this -> getCode(false),
			'blink' => $iProfileId,
		));	
	}
    protected function _getCellStatusAdmin($mixedValue, $sKey, $aField, $aRow)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if(!empty($CNF['T']['filter_item_' . $mixedValue]))
            $mixedValue = $CNF['T']['filter_item_' . $mixedValue];
        else
            $mixedValue = '_undefined';

        return parent::_getCellDefault(_t($mixedValue), $sKey, $aField, $aRow);
    }
    protected function _getActionAllocate($sType, $sKey, $a, $isSmall = false, $isDisabled = false, $aRow = array())
    {
        $a['attr']['bx_grid_action_data'] = "{$a['attr']['bx_grid_action_data']},{$a['icon']}";
        return parent::_getActionDefault($sType, $sKey, $a, $isSmall, $isDisabled, $aRow);
    }

    protected function _getActionDeduct($sType, $sKey, $a, $isSmall = false, $isDisabled = false, $aRow = array())
    {
        return $this -> _getActionAllocate($sType, $sKey, $a, $isSmall, $isDisabled, $aRow);
    }

    protected function _getDataSqlCounter($sQuery, $sFilter)
    {
        $oDb = BxDolDb::getInstance();
        $sQuery = preg_replace("/^SELECT.*FROM/mUs", "SELECT COUNT(*) FROM ", $sQuery);
        return $oDb->getOne($sQuery);
    }
}

/** @} */
