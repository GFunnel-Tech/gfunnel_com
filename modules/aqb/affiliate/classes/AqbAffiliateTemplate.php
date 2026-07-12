<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    DolphinMigration  Dolphin Migration
 * @ingroup     UnaModules
 *
 * @{
 */

class AqbAffiliateTemplate extends BxBaseModGeneralTemplate
{
    private $_sJsObject = null;
    private $_sCurrency = '';
    private $_sCurrencyCode = '';
    private $_bIsPointsEnabled = false;
    private $_bIsCreditsEnabled = false;
    private $_bIsFiatEnabled = false;
    public function __construct(&$oConfig, &$oDb)
    {
        $this->MODULE = $oConfig -> getName();
        $this->_sJsObject = $oConfig->getJsObject('utils');
        parent::__construct($oConfig, $oDb);

        $aCurr = bx_srv('bx_payment', 'get_currency_info');
        if (!empty($aCurr)) {
            $this->_sCurrency = $aCurr['sign'];
            $this->_sCurrencyCode = $aCurr['code'];
        }

        $this->_bIsPointsEnabled = $this->_oDb->isPointsEnabled();
        $this->_bIsCreditsEnabled = $this->_oDb->isCreditsEnabled();
        $this->_bIsFiatEnabled = $this->_oDb->isFiatEnabled();
    }

    public function getProfileView($iProfileId, $sClass = "")
    {
        $oProfile = BxDolProfile::getInstance($iProfileId);
        if (!$oProfile)
            $oProfile = BxDolProfileUndefined::getInstance();

        return '<td class="' . $sClass . '">
                    <div class="profile-info-link-icon m-1">
                        <img class="bx-def-thumb-size" src="' . $oProfile -> getThumb() . '" />
                            <a target="_blank" href="' . $oProfile -> getUrl() . '">
                                ' . $oProfile -> getDisplayName()  . '
                            </a>
                    </div>
                </td>';
    }

    /**
     * @param int $iId of the program
     * @return string
     */

    public function getProgramsPreview($iId = 0){
        $CNF = &$this->_oConfig->CNF;
        $aPrograms = $this -> _oDb -> getProgramsList();

        if (empty($aPrograms))
            return MsgBox(_t("_{$this -> MODULE}_matrices_list_empty"));

        $aVars = array();
        foreach($aPrograms as &$aProgram){
            $aMembershipInfo = BxDolAcl::getInstance()->getMembershipInfo($aProgram[$CNF['FMP_MEMLEVEL']]);
            if (empty($aMembershipInfo))
                $aMembershipInfo = array(
                    'name' => "_{$this -> MODULE}_unknown",
                    'icon' => 'user-slash'
                );

            $aActionsList = $this->_oDb->getProgramCommission($aProgram[$CNF['FMP_ID']]);
            $aActions = array();

            foreach($aActionsList as $sAction => $aValues){
                $aActions[] = array(
                    'content' => $this -> getCommissionWithPointsForView( $aValues),
                    'name' => $sAction,
                    'title' => _t("_aqb_affiliate_matrix_program_{$sAction}")
                );
            }

            $bIsActive = (int)$aProgram[$CNF['FMP_ACTIVE']];
            $aVars['bx_repeat:items'][] = array(
                'level' => _t($aMembershipInfo['name']),
                'icon' => $this->_oModule->_oTemplate->getIcon($aMembershipInfo['icon']),
                'bx_repeat:action_list' => $aActions,
                'active' => !$bIsActive ? 'disabled' : '',
                'action' => _t("_{$this -> MODULE}" . '_matrix_program_action_' . ( !$bIsActive ? 'enable' : 'disable' )),
                'js_object' => $this -> _sJsObject,
                'id' => $aProgram[$CNF['FMP_ID']],
                'type' => AQB_PROGRAM_TYPE_DEFAULT,
                'status' => _t("_{$this -> MODULE}" . '_program_status_' . ( $bIsActive ? 'enabled' : 'disabled' )),
                'on' => (int)$aProgram[$CNF['FMP_MATRIX']] ? _t("_{$this -> MODULE}" . '_matrix_program_matrix_mode_on') : _t("_{$this -> MODULE}" . '_matrix_program_matrix_mode_off'),
                'bx_if:matrix' => [
                    'condition' => (int)$aProgram[$CNF['FMP_MATRIX']],
                    'content' => [
                        'deep' => $aProgram[$CNF['FMP_DEEP']],
                        'width' => $CNF['MATRIX_WIDTH'],
                        'spillover' => $aProgram[$CNF['FMP_SPILLOVER']] ? _t("_{$this -> MODULE}" . '_matrix_program_matrix_mode_on') : _t("_{$this -> MODULE}" . '_matrix_program_matrix_mode_off')
                    ]
                ],
                'bx_if:paid_levels' => [
                    'condition' => $this->_oDb->isPaidLevelsAvailable(),
                    'content' => ['js_object' => $this -> _sJsObject, 'id' => $aProgram[$CNF['FMP_ID']]]
                ]
            );
        }

        $aVars['bx_if:rebuild'] = array(
            'condition' => !empty($aPrograms),
            'content' => array(
                'js_object' => $this -> _sJsObject,
            )
        );

        return $this -> parseHtmlByName('matrix-program-items.html', $aVars);
    }

    private function getRebuildButton(){
        $aPrograms = $this -> _oDb -> getProgramsList();
        return !empty($aPrograms) ? $this -> parseHtmlByName('rebuild-matrix.html', array('js_object' => $this -> _sJsObject)) : '';
    }
    private function getCommissionWithPointsToDb($sField){
        $aValue[$sField] = bx_get($sField);
        if ($this->_bIsPointsEnabled)
            $aValue[AQB_AFF_CUR_TYPE_POINTS] = bx_get("{$sField}_points");

        return serialize($aValue);
    }

    private function getCommissionsFields(){
        $CNF = &$this->_oConfig->CNF;

        $aCommissions = [];
        $aModules = $this->_oDb->getAvailableCommissionsModule();
        foreach($aModules as &$sAction) {
            $sValue = bx_get($sAction);
            if ($sValue) {
                $aCommissions[$sAction][AQB_AFF_CUR_TYPE_CURRENCY] = $sValue;
                $sPercentage = bx_get("{$sAction}_{$CNF['COMMISSION_PERCENTAGE_OPTION']}");
                if ($sPercentage === 'on')
                    $aCommissions[$sAction][$CNF['COMMISSION_PERCENTAGE_OPTION']] = 1;
            }

            if ($sPoints = bx_get($sAction . '_points'))
                $aCommissions[$sAction][AQB_AFF_CUR_TYPE_POINTS] = $sPoints;

            if ($sCredits = bx_get($sAction . '_credits'))
                $aCommissions[$sAction][AQB_AFF_CUR_TYPE_CREDITS] = $sCredits;
        }

        return !empty($aCommissions) ? serialize($aCommissions) : false;
    }

    private function getCommissionWithPointsForView(&$aValues){
        $CNF = &$this->_oConfig->CNF;

	    $sTitle = '';
        if ($this->_bIsFiatEnabled && isset($aValues[AQB_AFF_CUR_TYPE_CURRENCY]) && $aValues[AQB_AFF_CUR_TYPE_CURRENCY])
            $sTitle .= isset($aValues[$CNF['COMMISSION_PERCENTAGE_OPTION']]) && (int)$aValues[$CNF['COMMISSION_PERCENTAGE_OPTION']]
                    ? '<u title="' . _t('_aqb_affiliate_commission_for_action_percent') . '">' . $aValues[AQB_AFF_CUR_TYPE_CURRENCY] . '%</u>' :
                    '<u title="' . _t('_aqb_affiliate_commission_for_action_currency') . '">' . $this->_sCurrency . $aValues[AQB_AFF_CUR_TYPE_CURRENCY] . '</u>';

        if ($this->_bIsPointsEnabled && isset($aValues[AQB_AFF_CUR_TYPE_POINTS]) && $aValues[AQB_AFF_CUR_TYPE_POINTS])
            $sTitle .= '<u title="' . _t('_aqb_affiliate_commission_for_action_points') . '">' . _t("_{$this -> MODULE}_program_view_points", $aValues[AQB_AFF_CUR_TYPE_POINTS]) . '</u>';

        if ($this->_bIsCreditsEnabled && isset($aValues[AQB_AFF_CUR_TYPE_CREDITS]) && $aValues[AQB_AFF_CUR_TYPE_CREDITS])
            $sTitle .= '<u title="' . _t('_aqb_affiliate_commission_for_action_credits') . '">' . _t("_{$this -> MODULE}_program_view_credits", $aValues[AQB_AFF_CUR_TYPE_CREDITS]) . '</u>';

        return $sTitle ? $sTitle : _t("_{$this -> MODULE}_no_commission") ;
    }

    public function getStudioMatrixForm(){
        return array(
            'matrices' => array(
                'type' => 'custom',
                'content' => $this->getProgramsPreview() . $this->getRebuildButton()
            )
        );
    }

    private function getSelectField($sField, &$aProgramInfo, $sLabel = ''){
        $sTitleKey = "_{$this -> MODULE}_program_form_{$sField}_info";
        $sTitle = _t($sTitleKey);

        $aParams = $sLabel ? ['label' => $sLabel]: [];
        if ($sTitleKey != $sTitle)
            $aParams['info'] = _t("_{$this -> MODULE}_program_form_{$sField}_info");

        $aMembershipsList = array(0 => _t("_{$this -> MODULE}_select_one")) + BxDolAcl::getInstance() -> getMemberships(false, false, true, true);
        return array_merge([
            'type' => 'select',
            'name' => $sField,
            'value' => $aProgramInfo[$sField],
            'values' => $aMembershipsList,
            'caption' =>  _t("_{$this -> MODULE}_program_form_{$sField}"),
            'db' => ['pass' => 'Int'],
        ], $aParams);
    }

    private function getCheckBoxField($sField, &$aProgramInfo, $sLabel = ''){
        $sTitleKey = "_{$this -> MODULE}_program_form_{$sField}_info";
        $sTitle = _t($sTitleKey);

        $aParams = $sLabel ? array('label' => $sLabel): array();
        if ($sTitleKey != $sTitle)
            $aParams['info'] =  _t("_{$this -> MODULE}_program_form_{$sField}_info");

        return array_merge(array(
            'type' => 'checkbox',
            'name' => $sField,
            'checked' => isset($aProgramInfo[$sField]) && $aProgramInfo[$sField] ? true : false,
            'value' => 'on',
            'caption' =>  _t("_{$this -> MODULE}_program_form_{$sField}"),
            'db' => array(
                'pass' => 'Boolean',
            ),
        ), $aParams);
    }

    public function getSimpleCommissionsFields($sName, $sTitle, $sInfo, &$aCommissions = []){
        $CNF = &$this->_oConfig->CNF;
        $aCommissionValues = isset($aCommissions[$sName]) ? $aCommissions[$sName] : array();
        return $this -> parseHtmlByName('join-commissions-fields.html',[
            'bx_if:fiat' => [
                'condition' => $this->_bIsFiatEnabled,
                'content' => [
                    'name' => $sName,
                    'title' => _t("{$sTitle}_cur", $this -> _sCurrencyCode),
                    'info' => _t("{$sInfo}_cur"),
                    'default' => isset($aCommissionValues[AQB_AFF_CUR_TYPE_CURRENCY]) ? $aCommissionValues[AQB_AFF_CUR_TYPE_CURRENCY] : 0,
                ]
            ],
            'bx_if:points' => [
                'condition' => $this->_bIsPointsEnabled,
                'content' => [
                    'name_points' => "{$sName}_points",
                    'title_points' => _t("{$sTitle}_points"),
                    'info_points' => _t("{$sInfo}_points"),
                    'points' => isset($aCommissionValues[AQB_AFF_CUR_TYPE_POINTS]) ? $aCommissionValues[AQB_AFF_CUR_TYPE_POINTS] : 0
                ]
            ],
            'bx_if:credits' => [
                'condition' => $this->_bIsCreditsEnabled,
                'content' => [
                    'name_credits' => "{$sName}_credits",
                    'title_credits' => _t("{$sTitle}_credits"),
                    'info_credits' => _t("{$sInfo}_credits"),
                    'credits' => isset($aCommissionValues[AQB_AFF_CUR_TYPE_CREDITS]) ? $aCommissionValues[AQB_AFF_CUR_TYPE_CREDITS] : 0
                ]
            ]
        ]);
    }

    public function getWithPercentageCommissionsFields($sName, $sTitle, $sInfo, &$aCommissions = []){
        $CNF = &$this->_oConfig->CNF;
        $aCommissionValues = isset($aCommissions[$sName]) ? $aCommissions[$sName] : [];

        $fCurrency = isset($aCommissionValues[AQB_AFF_CUR_TYPE_CURRENCY]) ? $aCommissionValues[AQB_AFF_CUR_TYPE_CURRENCY] : 0;
        $fPoints = isset($aCommissionValues[AQB_AFF_CUR_TYPE_POINTS]) ? $aCommissionValues[AQB_AFF_CUR_TYPE_POINTS] : 0;
        $fCredits = isset($aCommissionValues[AQB_AFF_CUR_TYPE_CREDITS]) ? $aCommissionValues[AQB_AFF_CUR_TYPE_CREDITS] : 0;

        return $this -> parseHtmlByName('percentage-commissions-fields.html', [
            'bx_if:fiat' => [
                'condition' => $this->_bIsFiatEnabled,
                'content' => [
                    'name' => $sName,
                    'title' => _t("{$sTitle}_cur"),
                    'info' => _t("{$sInfo}_cur"),
                    'checked' => isset($aCommissionValues[$CNF['COMMISSION_PERCENTAGE_OPTION']]) && (int)$aCommissionValues[$CNF['COMMISSION_PERCENTAGE_OPTION']] ? 'checked="checked"' : '',
                    'value' => $fCurrency,
                ]
            ],
            'bx_if:points' => [
                'condition' => $this->_bIsPointsEnabled,
                'content' => [
                    'name_points' => "{$sName}_points",
                    'title_points' => _t("{$sTitle}_points"),
                    'info_points' => _t("{$sInfo}_points"),
                    'points' => $fPoints,
                    'name' => $sName
                ]
            ],
            'bx_if:credits' => [
                'condition' => $this->_bIsCreditsEnabled,
                'content' => [
                    'name_credits' => "{$sName}_credits",
                    'title_credits' => _t("{$sTitle}_credits"),
                    'info_credits' => _t("{$sInfo}_credits"),
                    'credits' => $fCredits,
                    'name' => $sName
                ]
            ]
        ]);
    }

    function getCommissionFormFields($aCommissions, $bExcludeJoin = false){
        $aFormFields = [];

        $aModules = $this->_oDb->getAvailableCommissionsModule();
        foreach($aModules as &$sAction) {
            if ($bExcludeJoin && $sAction === AQB_AFF_JOIN)
                continue;

            $sContent = $sAction === AQB_AFF_JOIN ? $this->getSimpleCommissionsFields($sAction, "_{$this -> MODULE}_program_form_{$sAction}", "_{$this -> MODULE}_program_form_{$sAction}_info", $aCommissions)
                                                  : $this -> getWithPercentageCommissionsFields($sAction, "_{$this -> MODULE}_program_form_{$sAction}", "_{$this -> MODULE}_program_form_{$sAction}_info", $aCommissions) ;

            $aFormFields[$sAction] = [
                'type' => 'custom',
                'caption' => _t("_{$this -> MODULE}_program_form_{$sAction}"),
                'content' => $sContent,
                'attrs_wrapper' => ['class' => 'commissions-fields']
            ];
        }

        return $aFormFields;
    }

    public function getAddCommissionsForm($iProgramId, $iAclLevelId = 0){
        $CNF = &$this->_oConfig->CNF;

        if (!($aProgramInfo = $this->_oDb->getProgramById($iProgramId)))
            return false;

        $aList = array(0 => _t("_{$this -> MODULE}_select_one")) + $this->_oDb->getPaidLevels();
        $aForm = [
            'form_attrs' => array(
                'name' => $CNF['PROGRAMS_ACL_CM'],
                'id' => $CNF['PROGRAMS_ACL_CM'],
                'method' => 'post',
                'action' => BX_DOL_URL_ROOT . $this->_oConfig->getBaseUri() . 'set_commissions_rates'
            ),
            'params' => [
                'db' => [
                    'table' => $CNF['PROGRAMS_ACL_CM'],
                    'key' => $CNF['FPMC_ACTL_ID'],
                    'submit_name' => "submit-{$CNF['PROGRAMS_ACL_CM']}",
                ],
                'csrf' => [
                    'disable' => true,
                ],
                'ajax_mode' => true
            ],
            'inputs' => [
                [
                  'type' => 'hidden',
                  'name' => "program_id",
                  'value' => $iProgramId,
                ]
            ]];


        $aForm['inputs'] = array_merge($aForm['inputs'],
                [
                    $CNF['FPMC_ACTL_ID'] => [
                    'type' => 'select',
                    'name' => $CNF['FPMC_ACTL_ID'],
                    'values' => $aList,
                    'value' => isset($aProgramInfo[$CNF['FPMC_ACTL_ID']]) ? (int)$aProgramInfo[$CNF['FPMC_ACTL_ID']] : 0,
                    'caption' => _t("_{$this -> MODULE}_acl_program_level"),
                    'db' => [
                        'pass' => 'Int',
                    ],
                    'checker' => [
                        'func' => 'Avail',
                        'params' => [],
                        'error' => _t("_{$this -> MODULE}_acl_program_form_level_error"),
                     ],
                     'attrs' => ['onchange' => "{$this -> _sJsObject}.loadAclCommissionFields(this);"]
                    ]
                ]
            );


        $aForm['inputs'] = array_merge($aForm['inputs'],
            [
                [
                    'type' => 'custom',
                    'content' => _t('_aqb_affiliate_acl_program_info'),
                ]
            ],
            [
              'buttons' => [
                  'type' => 'input_set',
                  [
                      'type' => 'submit',
                      'name' => "submit-{$CNF['PROGRAMS_ACL_CM']}",
                      'value' => _t("_{$this -> MODULE}_admin_save"),
                  ],
                  [
                      'type' => 'button',
                      'name' => 'clear',
                      'value' => _t("_{$this -> MODULE}_admin_commission_remove_data_button_caption"),
                      'attrs' => array('onclick' => "{$this -> _sJsObject}.removeAclCommissionData(this);")
                  ]
              ]
            ]
            );

        $oForm = new BxTemplFormView($aForm);
        $oForm -> initChecker();
        if ($oForm->isSubmittedAndValid()) {
            $sCommissions = $this->getCommissionsFields();
            if ($sCommissions === FALSE)
                return ['msg' => _t('_aqb_affiliate_not_data_error')];

            $aValsToAdd = [
                $CNF['FPMC_COMMISSION'] => $sCommissions,
                $CNF['FPMC_ACTL_ID'] => (int)$iAclLevelId,
                $CNF['FPMC_PROGRAM_ID'] => (int)$iProgramId,
            ];

            $aData = $this->_oDb->getAclCommission($aValsToAdd[$CNF['FPMC_PROGRAM_ID']], $aValsToAdd[$CNF['FPMC_ACTL_ID']]);
            if (!empty($aData))
                $this->_oDb->removeAclCommission($aValsToAdd[$CNF['FPMC_PROGRAM_ID']], $aValsToAdd[$CNF['FPMC_ACTL_ID']]);

            $oForm->insert($aValsToAdd);
            return ['msg' => _t('_aqb_affiliate_successfully_saved')];
        }

        return $oForm->getCode(true);
    }

    public function getAddProgramForm($iId = 0){
        $CNF = &$this->_oConfig->CNF;

        $aProgramInfo = array();
        if ($iId)
           $aProgramInfo = $this -> _oDb -> getProgramById($iId);

        $aMembershipsList = array(0 => _t("_{$this -> MODULE}_select_one")) + BxDolAcl::getInstance() -> getMemberships(false, false, true, true);
        $aForm = [
            'form_attrs' => array(
                'name' => $CNF['PROGRAMS'],
                'id' => $CNF['PROGRAMS'],
                'method' => 'post',
                'action' => BX_DOL_URL_ROOT . $this->_oConfig->getBaseUri() . 'add_program'
            ),
            'params' => [
                'db' => [
                    'table' => $CNF['PROGRAMS'],
                    'key' => $CNF['FMP_ID'],
                    'submit_name' => "submit-{$CNF['PROGRAMS']}",
                ],
                'csrf' => [
                       'disable' => true,
                ]
            ],
            'inputs' => [
                $CNF['FMP_ACTIVE'] => $this -> getCheckBoxField($CNF['FMP_ACTIVE'], $aProgramInfo)
            ]];


        if (!$iId)
            $aForm['inputs'] = array_merge($aForm['inputs'],
                array($CNF['FMP_MEMLEVEL'] => array(
                    'type' => 'select',
                    'name' => $CNF['FMP_MEMLEVEL'],
                    'values' => $aMembershipsList,
                    'value' => isset($aProgramInfo[$CNF['FMP_MEMLEVEL']]) ? (int)$aProgramInfo[$CNF['FMP_MEMLEVEL']] : 0,
                    'caption' => _t("_{$this -> MODULE}_program_form_memlevel"),
                    'db' => array(
                        'pass' => 'Int',
                    ),
                    'checker' => array (
                        'func' => 'Avail',
                        'params' => array(),
                        'error' => _t("_{$this -> MODULE}_program_form_level_error"),
                    ),
                )
              )
            );

        $aCommissions = @unserialize($aProgramInfo[$CNF['FMP_COMMISSION']]);
        $aForm['inputs'] = array_merge($aForm['inputs'], $this->getCommissionFormFields($aCommissions),
            [
                'info' => [
                    'type' => 'custom',
                    'content' => _t('_aqb_affiliate_program_as_percentage_info'),
                ]
            ],
            [
                    $CNF['FMP_DESC'] => [
                        'type' => 'textarea',
                        'name' => $CNF['FMP_DESC'],
                        'html' => 3,
                        'caption' => _t("_{$this -> MODULE}_program_form_desc"),
                        'info' => _t("_{$this -> MODULE}_program_form_desc_info"),
                        'value' => isset($aProgramInfo[$CNF['FMP_DESC']]) ? $aProgramInfo[$CNF['FMP_DESC']] : '',
                        'db' => ['pass' => 'Xss'],
                    ],
                    'buttons' => [
                        'type' => 'input_set',
                        [
                            'type' => 'button',
                            'name' => 'save',
                            'value' => _t("_{$this -> MODULE}_admin_save"),
                            'attrs' => array('onclick' => "{$this -> _sJsObject}.createProgram('#{$CNF['PROGRAMS']}');")
                        ],
                        [
                            'type' => 'hidden',
                            'name' => "submit-{$CNF['PROGRAMS']}",
                            'value' => "submit-{$CNF['PROGRAMS']}",
                        ],
                        [
                            'type' => 'hidden',
                            'name' => "type",
                            'value' => AQB_PROGRAM_TYPE_DEFAULT,
                        ],
                        [
                            'type' => 'reset',
                            'name' => 'close',
                            'value' => _t("_{$this -> MODULE}_close"),
                            'attrs' => array('onclick' => "$(this).closest('.bx-popup-applied:visible').dolPopupHide({removeOnClose: true})")
                        ]
                    ]
            ]
        );

        if ($iId)
            $aForm['inputs'][] = array(
                'type' => 'hidden',
                'name' => 'entry_id',
                'value' => $iId
            );

        $oForm = new BxTemplFormView($aForm);
        $oForm -> initChecker();
        if ($oForm->isSubmittedAndValid()) {
            $aValsToAdd = array(
                $CNF['FMP_COMMISSION'] => $this->getCommissionsFields(),
            );

            if (!$iId) {
                if ($aProgram = $this->_oDb->getProgramByMembership(bx_get($CNF['FMP_MEMLEVEL'])))
                    return array('msg' => _t("_{$this -> MODULE}" . '_program_exists'));

                $bResult = $oForm->insert($aValsToAdd);
            }
            else
                $bResult = $oForm -> update($iId, $aValsToAdd);

            return $bResult ?
                              array('eval' => "$('.bx-popup-applied:visible').dolPopupHide({removeOnClose: true})")
                            :
                              array('msg' => _t("_{$this -> MODULE}" . '_nothing_has_been_saved'));
        }

        return $oForm->getCode(true);
    }

    public function getMatrixSettingsForm($iId){
        $CNF = &$this->_oConfig->CNF;

        $aProgramInfo = array();
        if ($iId)
            $aProgramInfo = $this -> _oDb -> getProgramById($iId);

        $aForm = array(
            'form_attrs' => array(
                'name' => $CNF['PROGRAMS'],
                'id' => $CNF['PROGRAMS'],
                'method' => 'post',
                'action' => BX_DOL_URL_ROOT . $this->_oConfig->getBaseUri() . 'add_program'
            ),
            'params' => array(
                'db' => array(
                    'table' => $CNF['PROGRAMS'],
                    'key' => $CNF['FMP_ID'],
                    'submit_name' => "submit-{$CNF['PROGRAMS']}",
                ),
                'csrf' => array(
                    'disable' => true,
                )
            ),
            'inputs' => array(
                $CNF['FMP_MATRIX'] => $this -> getCheckBoxField($CNF['FMP_MATRIX'], $aProgramInfo),
                $CNF['FMP_SPILLOVER'] => $this -> getCheckBoxField($CNF['FMP_SPILLOVER'], $aProgramInfo),
                $CNF['FMP_MEMTOADD'] => $this -> getSelectField($CNF['FMP_MEMTOADD'], $aProgramInfo),
                $CNF['FMP_WIDTH'] => [
                    'type' => 'custom',
                    'content' => _t("_{$this -> MODULE}_matrix_width_info", !$CNF['MATRIX_WIDTH'] ? _t('_aqb_affiliate_unlimited') : $CNF['MATRIX_WIDTH'])
                ],
                $CNF['FMP_DEEP'] => [
                    'type' => 'text',
                    'name' => $CNF['FMP_DEEP'],
                    'value' => isset($aProgramInfo[$CNF['FMP_DEEP']]) ? (int)$aProgramInfo[$CNF['FMP_DEEP']] : 0,
                    'caption' => _t("_{$this -> MODULE}_program_form_deep"),
                    'info' => _t("_{$this -> MODULE}_program_form_deep_info"),
                    'db' => [
                        'pass' => 'Int',
                    ],
                ],
                'buttons' => array(
                    'type' => 'input_set',
                    array(
                        'type' => 'button',
                        'name' => 'save',
                        'value' => _t("_{$this -> MODULE}_admin_save"),
                        'attrs' => array('onclick' => "{$this -> _sJsObject}.createProgram('#{$CNF['PROGRAMS']}');")
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => "submit-{$CNF['PROGRAMS']}",
                        'value' => "submit-{$CNF['PROGRAMS']}",
                    ),
                    array(
                        'type' => "hidden",
                        'name' => "type",
                        'value' => "matrix",
                    ),
                    array(
                        'type' => 'button',
                        'name' => "commissions",
                        'value' => _t("_{$this -> MODULE}_program_add_matrix_commission"),
                        'attrs' => array('onclick' => "{$this -> _sJsObject}.setMatrixCommission('{$iId}');")
                    ),
                    array(
                        'type' => 'reset',
                        'name' => 'close',
                        'value' => _t("_{$this -> MODULE}_close"),
                        'attrs' => array('onclick' => "$(this).closest('.bx-popup-applied:visible').dolPopupHide({removeOnClose: true})")
                    )
                )
            )
        );

        if ($iId)
            $aForm['inputs'][] = array(
                'type' => 'hidden',
                'name' => 'entry_id',
                'value' => $iId
            );

        $oForm = new BxTemplFormView($aForm);

        $oForm -> initChecker();
        if ($oForm->isSubmittedAndValid()) {

            $sPercentage = bx_get($CNF['FMP_PERCENTAGE']);
            $aValsToAdd = array(
                $CNF['FMP_PERCENTAGE'] => (int)$sPercentage == 'on',
                $CNF['FMP_UPGRADE'] => bx_get($CNF['FMP_UPGRADE'])
            );

            if (!$iId)
                $bResult = $oForm -> insert($aValsToAdd);
            else
                $bResult = $oForm -> update($iId, $aValsToAdd);

            return $bResult ?
                array('eval' => "$('.bx-popup-applied:visible').dolPopupHide({removeOnClose: true})")
                :
                array('msg' => _t("_{$this -> MODULE}" . '_nothing_has_been_saved'));
        }

        return $oForm->getCode(true);
    }

    private function buildMatrixLevels($iProgramId = 0){
        $aCommissions = [];
        if (!$iProgramId || !($aProgramInfo = $this->_oDb->getProgramById($iProgramId)))
            return $aCommissions;

            $aModules = $this->_oDb->getAvailableCommissionsModule();
            $aLevels = $this->_oDb->getMatrixLevelsCommission($iProgramId);
            if (!empty($aLevels)) {
                $iInc = 0;
                foreach ($aLevels as $iKey => $aLevel) {
                    $aCommissions[$iInc]['level'] = $iKey;

                    foreach ($aModules as &$sAction) {
                        $fCredits = $fValue = $fPoints = 0;
                        $sChecked = '';
                        if (isset($aLevel[$sAction])) {
                            if (isset($aLevel[$sAction]['percentage']))
                                $sChecked = 'checked="checked"';

                            $fValue = isset($aLevel[$sAction][AQB_AFF_CUR_TYPE_CURRENCY]) ? $aLevel[$sAction][AQB_AFF_CUR_TYPE_CURRENCY] : 0;
                            $fPoints = isset($aLevel[$sAction][AQB_AFF_CUR_TYPE_POINTS]) ? $aLevel[$sAction][AQB_AFF_CUR_TYPE_POINTS] : 0;
                            $fCredits = isset($aLevel[$sAction][AQB_AFF_CUR_TYPE_CREDITS]) ? $aLevel[$sAction][AQB_AFF_CUR_TYPE_CREDITS] : 0;
                        }

                        $aCommissions[$iInc]["bx_repeat:fields"][] = $this->getMatrixFormFields($sAction,
                            ['fiat' => $fValue, 'points' => $fPoints, 'credits' => $fCredits, 'checked' => $sChecked, 'key' => $iKey]);
                    }

                    $aCommissions[$iInc] = array_merge($aCommissions[$iInc], array(
                        'display_add' => !$iInc ? 'display:block' : 'display:none',
                        'display_remove' => $iInc ? 'display:block' : 'display:none',
                    ));

                    $iInc++;
                }
            }
            else
            {
                   $aCommissions[] = array(
                        'level' => 1,
                        'display_add' => 'display:block',
                        'display_remove' => 'display:none',
                    );

                    foreach($aModules as &$sAction)
                        $aCommissions[0]["bx_repeat:fields"][] = $this->getMatrixFormFields($sAction);

         }

        return $this -> parseHtmlByName('add-matrix-levels-commissions.html', array('bx_repeat:levels' => $aCommissions));
    }

    private function getMatrixFormFields($sField, $aValues = []){
        $fValue = isset($aValues['fiat']) ? $aValues['fiat']: 0;
        $fPoints = isset($aValues['points']) ? $aValues['points']: 0;
        $fCredits = isset($aValues['credits']) ? $aValues['credits']: 0;
        $sChecked = isset($aValues['checked']) ? $aValues['checked'] : '';
        $iKey = isset($aValues['key']) ? $aValues['key']: 0;

        return [
            'bx_if:fiat' => [
                'condition' => $this->_bIsFiatEnabled,
                'content' => [
                    'title' => _t("_aqb_affiliate_matrix_level_{$sField}"),
                    'sign' => $this -> _sCurrency,
                    'name' => $sField,
                    'value' => $fValue,
                    'bx_if:percentage' => [
                        'condition' => $sField !== AQB_AFF_JOIN,
                        'content' => [
                            'name' => $sField,
                            'checked' => $sChecked,
                            'key' => $iKey,
                            'percentage_title' => _t("_aqb_affiliate_matrix_level_{$sField}_percentage")
                        ]
                    ],
                ]
            ],
            'bx_if:credits' => [
                'condition' => $this->_bIsCreditsEnabled,
                'content' => [
                    'title_credits' => _t("_aqb_affiliate_matrix_level_{$sField}_credits"),
                    'name' => $sField,
                    'value' => $fCredits
                ]
            ],
            'bx_if:points' => [
                'condition' => $this->_bIsPointsEnabled,
                'content' => [
                    'title_points' => _t("_aqb_affiliate_matrix_level_{$sField}_points"),
                    'name' => $sField,
                    'value' => $fPoints
                ]
            ]
        ];

    }

    public function setMatrixCommissionForm($iId){
        $aForm = array(
            'form_attrs' => array(
                'name' => 'matrix-commission',
                'id' => 'matrix-commission',
                'method' => 'post',
                'action' => BX_DOL_URL_ROOT . $this->_oConfig->getBaseUri() . 'save_matrix_commission'
            ),
            'inputs' => array(
                'commissions' => array(
                    'type' => 'custom',
                    'content' => $this->buildMatrixLevels($iId)
                ),
                'buttons' => array(
                    'type' => 'input_set',
                    array(
                        'type' => 'button',
                        'name' => 'save',
                        'value' => _t("_{$this -> MODULE}_admin_save"),
                        'attrs' => array('onclick' => "{$this -> _sJsObject}.saveMatrixLevels('#matrix-commission');")
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => "program",
                        'value' => $iId,
                    ),
                    array(
                        'type' => 'reset',
                        'name' => 'close',
                        'value' => _t("_{$this -> MODULE}_close"),
                        'attrs' => array('onclick' => "$(this).closest('.bx-popup-applied:visible').dolPopupHide({removeOnClose: true})")
                    )
                )
            )
        );

        $oForm = new BxTemplFormView($aForm);
        return $oForm->getCode(true);
    }

    function getProfileInfo($iProfileId){
        $aMembershipInfo = BxDolAcl::getInstance() -> getMemberMembershipInfo($iProfileId);
        $aInfo = $this->_oDb->getDirectlyInvited($iProfileId);
        $aProgram = $this->_oDb->getProfileProgram($iProfileId);

        return $this -> parseHtmlByName('profile-info.html', array(
            'js_object' => $this -> _sJsObject,
            'name' => !empty($aProgram) ? _t($aMembershipInfo['name']) : _t('_aqb_affiliate_program_not_active'),
            'referrals' => (int)$aInfo['referrals'],
            'commissions' => $this->getTotalCommissionsAsTitle($iProfileId)
        ));
    }

    private function generateHeaderSection($sProgram, $sDesc){
        $aSection = array(
            "{$sProgram}_begin" => array(
                'type' => 'block_header',
                'caption' => _t("_{$this -> MODULE}_profile_program_description"),
                'collapsible' => true,
                'collapsed' => true
            ),
            $sProgram => array(
                'type' => 'custom',
                'content' => $sDesc,
            ),
            "{$sProgram}_end" => array(
                'type' => 'block_end'
            )
        );

        $aForm = array();
        $oForm = new BxTemplFormView($aForm);

        $aOpen = array(
            'type' => 'block_header',
            'caption' => _t("_{$this -> MODULE}_profile_program_description"),
            'collapsible' => true,
            'collapsed' => true
        );

        $aContent = array(
            'type' => 'custom',
            'content' => $sDesc,
        );

        $aClose = array(
            'type' => 'block_end',
        );

        return $oForm -> genRowBlockHeader($aOpen) . $oForm -> genInput($aContent) . $oForm -> genBlockEnd($aClose);

    }
    function getProgramsInfoList(){
        $CNF = &$this->_oConfig->CNF;
        $aPrograms = $this -> _oDb -> getProgramsList(true);
        if (empty($aPrograms))
            return MsgBox(_t('_aqb_affiliate_program_not_active_programs'));

        $aVars = [];
        foreach($aPrograms as &$aProgram){
            $aMembershipInfo = BxDolAcl::getInstance() -> getMembershipInfo($aProgram[$CNF['FMP_MEMLEVEL']]);
            if (empty($aMembershipInfo))
                $aMembershipInfo = array(
                    'name' => "_{$this -> MODULE}_unknown",
                    'icon' => 'user-slash'
                );

            $aActionsList = $this->_oDb->getProgramCommission($aProgram[$CNF['FMP_ID']]);
            $aActions = array();
            foreach($aActionsList as $sAction => $aValues){
                $aActions[] = array(
                   'content' => $this -> getCommissionWithPointsForView($aValues),
                   'name' => $sAction,
                   'title' => _t("_aqb_affiliate_matrix_program_{$sAction}")
                );
            }

            $bIsActive = (int)$aProgram[$CNF['FMP_ACTIVE']];
            $aVars['bx_repeat:items'][] = array(
                'level' => _t($aMembershipInfo['name']),
                'icon' => $this->_oModule->_oTemplate->getIcon($aMembershipInfo['icon']),
                'bx_repeat:action_list' => $aActions,
                'active' => !$bIsActive ? 'disabled' : '',
                'action' => _t("_{$this -> MODULE}" . '_matrix_program_action_' . ( !$bIsActive ? 'enable' : 'disable' )),
                'js_object' => $this -> _sJsObject,
                'id' => $aProgram[$CNF['FMP_ID']],
                'type' => AQB_PROGRAM_TYPE_DEFAULT,
                'status' => _t("_{$this -> MODULE}" . '_program_status_' . ( $bIsActive ? 'enabled' : 'disabled' )),
                'bx_if:desc' => array(
                        'condition' => $aProgram[$CNF['FMP_DESC']],
                        'content' => array(
                            'description' => $aProgram[$CNF['FMP_DESC']]
                        ),
                 ),
                'on' => (int)$aProgram[$CNF['FMP_MATRIX']] ? _t("_{$this -> MODULE}" . '_matrix_program_matrix_mode_on') : _t("_{$this -> MODULE}" . '_matrix_program_matrix_mode_off'),
                'bx_if:matrix' => array(
                    'condition' => (int)$aProgram[$CNF['FMP_MATRIX']],
                    'content' => array(
                        'deep' => $aProgram[$CNF['FMP_DEEP']],
                        'width' => $CNF['MATRIX_WIDTH'],
                        'spillover' => $aProgram[$CNF['FMP_SPILLOVER']] ? _t("_{$this -> MODULE}" . '_matrix_program_matrix_mode_on') : _t("_{$this -> MODULE}" . '_matrix_program_matrix_mode_off')
                    )
                )
            );
        }

        return $this -> parseHtmlByName('programs-info-list.html', $aVars);
    }

    function getReferralLinksBlock($iProfileId){
        $CNF = &$this->_oConfig->CNF;

        return $this -> parseHtmlByName('referral-links-block.html', array(
            'link' => $this -> _oConfig -> getReferralLink($iProfileId),
            'js_object' => $this -> _sJsObject,
            'bx_if:send_invitation' => array(
                'condition' => $CNF['SEND_INVITATIONS'],
                'content' => array(
                    'js_object' => $this -> _sJsObject,
                )
            )
        ));
    }

    function getReferralPageLinkBlock($iProfileId){
        $CNF = &$this->_oConfig->CNF;

        $this->addCss('main.css');
        $this->addJs('utils.js');

        $sURL = $this->_oConfig->getReferralLink($iProfileId, 0, true);
        $sUtils = $this->_oModule->_oTemplate->getJsCode('utils', array('sObjNameGrid' => $this -> _sJsObject));

        return $this -> parseHtmlByName('referral-links-page-block.html', array(
            'link' => $sURL,
            'social_sharing_menu' => $this->getSocialSharingCode($iProfileId, true),
            'js_object' => $this -> _sJsObject,
            'bx_if:send_invitation' => array(
                'condition' => $CNF['SEND_INVITATIONS'],
                'content' => array(
                    'js_object' => $this -> _sJsObject,
                    'page_link' => $sURL,
                )
            )
        )) . $sUtils;
    }

    function getInvitationForm($sUrl = ''){
        $CNF = &$this->_oConfig->CNF;
        $aForm = array(
            'form_attrs' => array(
                'id' => 'invite-from',
                'name' => 'invite-from',
                'method' => 'post',
                'enctype' => '',
                'action' => BX_DOL_URL_ROOT . $this -> _oConfig -> getBaseUri() . 'send_invitation/',
            ),
            'params' => array (
                'db' => array(
                    'submit_name' => 'send',
                )
            ),

            'table_attrs' => array(
                'class' => 'aqb-aff-invite-form-table'
            ),

            'inputs' => array(
                'url' => array(
                    'type' => 'hidden',
                    'name' => 'page_url',
                    'value' => $sUrl
                ),
                'emails' => array(
                    'type' => 'textarea',
                    'name' => 'emails',
                    'caption' => _t("_{$this -> MODULE}" . '_invitation_emails'),
                    'html' => 0,
                    'info' => _t("_{$this -> MODULE}" . '_invitation_emails_info', $CNF['MAX_INVITATIONS_NUMBER']),
                    'attrs' => array('id' => 'aqb_emails'),
                )
            )
        );

        if ($CNF['ALLOW_TO_ADD_MESSAGE'])
            $aForm['inputs']['message'] = array(
                'type' => 'textarea',
                'name' => 'message',
                'caption' => _t("_{$this -> MODULE}" . '_invitation_message'),
                'html' => 0,
                'info' => _t("_{$this -> MODULE}" . '_invitation_messages_info', $CNF['MESSAGE_SYMBOLS_NUMBER']),
                'attrs' => array('id' => 'aqb_message'),
            );

        $aForm['inputs']['buttons'] = array(
            'type' => 'input_set',
            array(
                'type' => 'button',
                'name' => 'send',
                'value' => _t("_{$this -> MODULE}" . '_invitation_send_button'),
                'attrs' => array('onclick' => "{$this -> _sJsObject}.sendInvitations('#invite-from');")
            ),
            array(
                'type' => 'reset',
                'name' => 'close',
                'value' => _t("_{$this -> MODULE}_close"),
                'attrs' => array('onclick' => "$(this).closest('.bx-popup-applied:visible').dolPopupHide({removeOnClose: true})")
            )
        );

        $oForm = new BxTemplFormView($aForm);
        return $oForm -> getCode();
    }

    function getReferralsStatBlock($iProfileId){
        $CNF = &$this->_oConfig->CNF;
        $aInfo = $this->_oDb->getDirectlyInvited($iProfileId);
        $aVars = array(
          'directly' => $aInfo['total'],
          'spillover' => 0,
          'referral' => (int)$aInfo['referrals'],
          'banner' => (int)$aInfo['banners'],
           'bx_if:send_invitation' => array(
                'condition' => $CNF['SEND_INVITATIONS'],
                'content' => array(
                    'js_object' => $this -> _sJsObject,
                    'invitations' => $this->_oDb->getAlreadySentEmailsNumber($iProfileId),
                )
           )
        );
        return $this -> parseHtmlByName('referral-info.html', $aVars);
    }

    private function getTotalCommissionsAsTitle($iProfileId, $bBannersOnly = false){
        $CNF = &$this->_oConfig->CNF;
        $aHistory = $this->_oDb->getProfileHistoryTotalCommissions($iProfileId, '', $bBannersOnly);
        $aCommissions = [];
        if ($this->_bIsFiatEnabled) {
            $fCurrency = !empty($aHistory[$CNF['FH_CM']]) ? $this->_oConfig->number_format($aHistory[$CNF['FH_CM']]) . ' ' . $this->_sCurrencyCode : 0;
            if ($fCurrency)
                $aCommissions[] = $fCurrency;
        }

        if ($this->_bIsPointsEnabled) {
            $fPoints = !empty($aHistory[$CNF['FH_POINTS']]) ? bx_srv('aqb_points', 'get_format_points', array($aHistory[$CNF['FH_POINTS']])) : 0;
            if ($fPoints)
                $aCommissions[] = _t('_aqb_affiliate_program_view_points', $fPoints);
        }

        if ($this->_bIsCreditsEnabled) {
            $fCredits = !empty($aHistory[$CNF['FH_CREDITS']]) ? $this->_oConfig->number_format($aHistory[$CNF['FH_CREDITS']]) : 0;
            if ($fCredits)
                $aCommissions[] = _t('_aqb_affiliate_program_view_credits', $fCredits);
        }

        $sCommissions = _t('_aqb_affiliate_no_commission');
        if (!empty($aCommissions))
            $sCommissions = implode(', ', $aCommissions);

        return $sCommissions;
    }

    function getBannersProfileInfo($iProfileId){
        $CNF = &$this->_oConfig->CNF;

        $aUserInfo = $this->_oDb->getUserInfo($iProfileId);
        $aVars = [
                   'total' => $this->_oDb->getInvitedByBanner($iProfileId, true),
                   'impressions' => !empty($aUserInfo) ? $aUserInfo[$CNF['FI_IMPRESSIONS']] : 0,
                   'clicks' => !empty($aUserInfo) ? $aUserInfo[$CNF['FI_CLICKS']] : 0,
                   'commissions' => $this->getTotalCommissionsAsTitle($iProfileId, true)
                 ];

        return $this -> parseHtmlByName('banners-info.html', $aVars);
    }

    private function getNodeInfo($iProfileId){
        $CNF = &$this->_oConfig->CNF;

        if (!$iProfileId || !($oProfile = BxDolProfile::getInstance($iProfileId)))
            return array();

        $aMembershipInfo = BxDolAcl::getInstance() -> getMemberMembershipInfo($iProfileId);

        $bView = isAdmin() || $CNF['SHOW_USERS_DETAILS'];
        return array(
            'text' => array(
                'name' => $oProfile -> getDisplayName(),
                'title' => $bView ? _t($aMembershipInfo['name']) : '',
                'desc' => $bView ? $oProfile->getAccountObject()->getEmail() : ''
            ),
            'image' => $oProfile -> getAvatar(),
            'link' => array(
                'href' => $oProfile -> getUrl(),
                'target' => '_blank'
            ),
            'HTMLid' => $iProfileId
        );
    }

    /**
     * @param $iUserId profile id in profiles tree table
     * @param $iProfileId reail profile id from `sys_profiles` table
     * @return bool|string
     */
    function getUserTree($iUserId, $iProfileId){
        $CNF = &$this->_oConfig->CNF;
        $oMatrix =  new AqbAffiliateCloserTable($this->_oConfig);

        $aProgramSettings = $this->_oDb->getProfileProgram($iProfileId);
        $aMyBranch = $oMatrix->getProfileBranch($iUserId);
        $aStructure = $aProfiles = array();
        if (!empty($aMyBranch)) {
            $iLength = sizeof($aMyBranch);

            end($aMyBranch);
            while($iLength--) {
                $aRow = current($aMyBranch);
                if (!(int)$aRow[$CNF['FPTREE_PROFILE_ID']])
                    continue;

                if ($aRow['parent_id'] !== $aRow[$CNF['FPTREE_PROFILE_ID']]) {
                    if (!isset($aProfiles[$aRow['parent_id']])) {
                        $aParent = $this->getNodeInfo($aRow['parent_id']);
                        if (!empty($aParent))
                            $aProfiles[$aRow['parent_id']] = $aParent;
                    }

                    $aProfiles[$aRow['parent_id']]['children'][] = isset($aProfiles[$aRow[$CNF['FPTREE_PROFILE_ID']]])
                                                                            ? $aProfiles[$aRow[$CNF['FPTREE_PROFILE_ID']]]
                                                                            : $this->getNodeInfo($aRow[$CNF['FPTREE_PROFILE_ID']]);

                    if (isset($aProfiles[$aRow[$CNF['FPTREE_PROFILE_ID']]]))
                        unset($aProfiles[$aRow[$CNF['FPTREE_PROFILE_ID']]]);
                }

                prev($aMyBranch);
            }

            $aStructure = $aProfiles[$oMatrix->getProfileIdByUserId($iUserId)];
        }

        return $this->parseHtmlByName('tree-view.html', array(
            'structure' => json_encode($aStructure)
        ));
    }

    function getProfileTreeJsStructure(){
        $aConfig = array(
                        'container' => "#OrganiseChart1",
                        'rootOrientatidon' =>  'WEST', // NORTH || EAST || WEST || SOUTH
                            // levelSeparation: 30,
                        'siblingSeparation' => 20,
                        'subTeeSeparation' => 60,
                        'scrollbar' => "fancy",
                        'connectors' => array(
                            'type' => 'step'
                        ),
                        'node' => array(
                            'HTMLclass' => 'nodeExample1'
                        )
                     );


    }

    function getUsersBannersArea($iProfileId){
        $CNF = &$this->_oConfig->CNF;
        $oProfile = BxDolProfile::getInstance($iProfileId);
        if (!$oProfile)
            return MsgBox(_t('_aqb_affiliate_profile_is_not_found'));

        if (!$oProfile->isActive())
            return MsgBox(_t('_aqb_affiliate_profile_is_not_active'));

        $aBannersList = $this->_oDb->getBannersList(TRUE);

        if (empty($aBannersList))
            return MsgBox(_t('_Empty'));

        $oImageTranscoder = BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW']);
        $aVars['bx_repeat:items'] = array();
        if (!empty($oImageTranscoder)) {
            foreach($aBannersList as &$aBanner){
                $aCommission = $this->_oDb->getBannerCommissionInfo($aBanner[$CNF['FB_ID']]);
                if (empty($aCommission))
                    continue;

                $aPointsCommission = [];
                if ($this->_bIsPointsEnabled) {
                    $aPointsCommission = array(
                        'imp' => $this->_oConfig->getCommissionValue($aCommission, AQB_AFF_IMPRESSION, AQB_AFF_CUR_TYPE_POINTS),
                        'click' => $this->_oConfig->getCommissionValue($aCommission, AQB_AFF_CLICK, AQB_AFF_CUR_TYPE_POINTS),
                        'join' => $this->_oConfig->getCommissionValue($aCommission, AQB_AFF_JOIN, AQB_AFF_CUR_TYPE_POINTS),
                    );

                    if(!array_filter($aPointsCommission))
                        $aPointsCommission = array();
                }

                $aCreditsCommission = [];
                if ($this->_bIsCreditsEnabled) {
                    $aCreditsCommission = [
                        'imp' => $this->_oConfig->getCommissionValue($aCommission, AQB_AFF_IMPRESSION, AQB_AFF_CUR_TYPE_CREDITS),
                        'click' => $this->_oConfig->getCommissionValue($aCommission, AQB_AFF_CLICK, AQB_AFF_CUR_TYPE_CREDITS),
                        'join' => $this->_oConfig->getCommissionValue($aCommission, AQB_AFF_JOIN, AQB_AFF_CUR_TYPE_CREDITS),
                    ];

                    if(!array_filter($aCreditsCommission))
                        $aCreditsCommission = [];
                }

                $aMoneyCommission = [];
                if ($this->_bIsFiatEnabled) {
                    $aMoneyCommission = [
                        'imp' => $this->_oConfig->getCommissionValue($aCommission, AQB_AFF_IMPRESSION),
                        'click' =>$this->_oConfig->getCommissionValue($aCommission, AQB_AFF_CLICK),
                        'join' => $this->_oConfig->getCommissionValue($aCommission, AQB_AFF_JOIN)
                    ];

                    if(array_filter($aMoneyCommission))
                        $aMoneyCommission['sign'] = $this->_sCurrencyCode;
                    else
                        $aMoneyCommission = [];
                }

                $aVars['bx_repeat:items'][] = [
                    'img' => $oImageTranscoder->getFileUrl($aBanner[$CNF['FB_IMG']]),
                    'size' => $aBanner[$CNF['FB_SIZE']],
                    'title' => $aBanner[$CNF['FB_TITLE']],
                    'js_object' => $this->_sJsObject,
                    'id' => $aBanner[$CNF['FB_ID']],
                    'bx_if:fiat' => [
                        'condition' => !empty($aMoneyCommission),
                        'content' => $aMoneyCommission
                    ],
                    'bx_if:points' => [
                        'condition' => !empty($aPointsCommission),
                        'content' => $aPointsCommission
                    ],
                    'bx_if:credits' => [
                        'condition' => !empty($aCreditsCommission),
                        'content' => $aCreditsCommission
                    ]
                ];
            }
        }

		if (empty($aVars['bx_repeat:items']))
			return MsgBox(_t('_Empty'));
		
        $this -> addCss('tools.css');
        return $this -> parseHtmlByName('banners-list.html', $aVars);
    }

    function getBannerCode($iProfileId, $iBannerId){
        $aBannerInfo = $this->_oDb->getBannerInfo($iBannerId);
        if (empty($aBannerInfo))
            return MsgBox(_t('_aqb_affiliate_admin_banner_not_found'));

        $sHash = $this->_oConfig->getAdsHash($iProfileId, $iBannerId);
        $aInfo = parse_url(BX_DOL_URL_ROOT);

        $aVars = array(
            'domain' => $aInfo['host'],
            'hash' => $sHash,
            'referral' => $iProfileId,
            'url' => $this->_oConfig->getAnalyticsJs(),
            'title' => BX_DOL_URL_ROOT
        );

        $aForm = array(
            'form_attrs' => array(
                'name' => 'ads-code-form',
                'id' => 'ads-code-form',
                'method' => 'post',
                'action' => BX_DOL_URL_ROOT . $this->_oConfig->getBaseUri() . 'save_matrix_commission'
            ),
            'inputs' => array(
                'code' => array(
                    'type' => 'custom',
                    'content' => $this -> parseHtmlByName('ads-code.html', $aVars),
                ),
                'buttons' => array(
                    'type' => 'input_set',
                    array(
                        'type' => 'button',
                        'name' => 'copy',
                        'value' => _t("_{$this -> MODULE}_button_copy"),
                        'attrs' => array('onclick' => "{$this -> _sJsObject}.copyToClipboard(this, '#ads-code');")
                    ),
                    array(
                        'type' => 'reset',
                        'name' => 'close',
                        'value' => _t("_{$this -> MODULE}_close"),
                        'attrs' => array('onclick' => "$(this).closest('.bx-popup-applied:visible').dolPopupHide({removeOnClose: true})")
                    )
                )
            )
        );

        $oForm = new BxTemplFormView($aForm);
        return $oForm->getCode(true);
    }

    function getAds($iProfileId, $iBannerId){
        $CNF = &$this->_oConfig->CNF;
        $aBannerInfo = $this->_oDb->getBannerInfo($iBannerId);
        if (empty($aBannerInfo))
            return '';

        $oStorage = BxDolStorage::getObjectInstance($CNF['OBJECT_STORAGE']);
        if (!$oStorage)
            return '';

        list($iWidth, $iHeight) = explode('X', $aBannerInfo[$CNF['FB_SIZE']]);
        return $this -> parseHtmlByName('banner-img.html', array(
           'width' => $iWidth,
           'height' => $iHeight,
           'src' => $oStorage->getFileUrlById($aBannerInfo[$CNF['FB_IMG']]),
           'redirect_url' => $this->_oConfig->getReferralLink($iProfileId, $iBannerId),
        ));
    }

    private function getCommissionItem(&$aVars, $sType, $fCurrency, $fPoints, $fCredits, $bShowButton= false){
        $iCount = !empty($aVars['bx_repeat:info']) ? count($aVars['bx_repeat:info']) : 0;
        $aVars['bx_repeat:info'][$iCount] = array(
            'title_item' => _t("_aqb_affiliate_quick_info_{$sType}_commission"),
            'bx_if:info' => array(
                'condition' => false, // to add description for the transactions for the future versions
                'content' => array(
                    'type' => $sType,
                    'js_object' => $this->_sJsObject
                )
            ),
            'bx_if:button' => array(
                'condition' => $sType === 'avail' && $bShowButton,
                'content' => array(
                    'info' => _t('_aqb_affiliate_profile_commission_limit_info', $this->_oConfig->CNF['CASH_OUT_LIMIT'], $this->_sCurrencyCode),
                    'js_object' => $this->_sJsObject,
                )
            )
        );

        if ($this->_bIsFiatEnabled)
            $aVars['bx_repeat:info'][$iCount]['bx_repeat:items'][] = array(
              'item' =>  $this->_oConfig->number_format($fCurrency) . ' ' . $this->_sCurrencyCode,
              'title' => _t('_aqb_affiliate_quick_info_currency_title')
            );

        if ($this->_bIsPointsEnabled)
            $aVars['bx_repeat:info'][$iCount]['bx_repeat:items'][] = array(
                'item' => $fPoints ? bx_srv('aqb_points', 'get_format_points', array($fPoints)) : 0,
                'title' => _t('_aqb_affiliate_quick_info_points_title')
            );

        if ($this->_bIsCreditsEnabled)
            $aVars['bx_repeat:info'][$iCount]['bx_repeat:items'][] = array(
                'item' => $this->_oConfig->number_format($fCredits),
                'title' => _t('_aqb_affiliate_quick_info_credits_title')
            );

        if (empty($aVars['bx_repeat:info'][$iCount]['bx_repeat:items']))
            $aVars['bx_repeat:info'][$iCount]['bx_repeat:items'] = ['title' => _t('_aqb_affiliate_no_commission')];
    }

    function getActivitiesQuickInfo($iProfileId, $bShowButton = true){
        $CNF = $this->_oConfig->CNF;
        $aVars = [];
        if ($aHistory = $this->_oDb->getProfileHistoryTotalCommissions($iProfileId))
        $this->getCommissionItem($aVars, 'total',
            !empty($aHistory[$CNF['FH_CM']]) ? $aHistory[$CNF['FH_CM']] : 0 ,
            !empty($aHistory[$CNF['FH_POINTS']]) ? $aHistory[$CNF['FH_POINTS']] : 0,
            !empty($aHistory[$CNF['FH_CREDITS']]) ? $aHistory[$CNF['FH_CREDITS']] : 0);

        if ($aPending = $this->_oDb->getProfileTxns($iProfileId, AQB_AFF_STATUS_PENDING))
        $this->getCommissionItem($aVars, 'pending',
            !empty($aPending[$CNF['FC_AMOUNT']]) ? $aPending[$CNF['FC_AMOUNT']] : 0 ,
            !empty($aPending[$CNF['FC_POINTS']]) ? $aPending[$CNF['FC_POINTS']] : 0 ,
            !empty($aPending[$CNF['FH_CREDITS']]) ? $aPending[$CNF['FH_CREDITS']] : 0);

        if ($aPaid = $this->_oDb->getProfileTxns($iProfileId))
        $this->getCommissionItem($aVars, 'paid',
            !empty($aPaid[$CNF['FC_AMOUNT']]) ? $aPaid[$CNF['FC_AMOUNT']] : 0 ,
            !empty($aPaid[$CNF['FC_POINTS']]) ? $aPaid[$CNF['FC_POINTS']] : 0 ,
            !empty($aPaid[$CNF['FC_CREDITS']]) ? $aPaid[$CNF['FC_CREDITS']] : 0 );

        if ($aHistory = $this->_oDb->getAvailableForCashOutCommissions($iProfileId))
        $this->getCommissionItem($aVars, 'avail',
            !empty($aHistory[$CNF['FC_AMOUNT']]) ? (float)$aHistory[$CNF['FC_AMOUNT']] : 0 ,
            !empty($aHistory[$CNF['FC_POINTS']]) ? $aHistory[$CNF['FC_POINTS']] : 0,
            !empty($aHistory[$CNF['FC_CREDITS']]) ? $aHistory[$CNF['FC_CREDITS']] : 0, $bShowButton);

        if (empty($aVars))
            return MsgBox(_t('_aqb_affiliate_no_earned_commission'));

        return $this -> parseHtmlByName('quick-info.html', $aVars);
    }

    function getCashOutForm($iProfileId){
        $CNF = $this->_oConfig->CNF;

        $aCommission = $this->_oDb->getAvailableForCashOutCommissions($iProfileId);

        $fAmount = $aCommission[$CNF['FC_AMOUNT']];
        $fPoints = $aCommission[$CNF['FC_POINTS']];
        $fCredits = $aCommission[$CNF['FC_CREDITS']];
        if (empty($aCommission) || (!$fPoints && !$fAmount && !$fCredits))
            return MsgBox(_t('_aqb_affiliate_profile_commission_not_enough'));

        $aForm = array(
            'form_attrs' => array(
                'name' => 'cash-out-request',
                'id' => 'cash-out',
                'method' => 'post',
                'action' => BX_DOL_URL_ROOT . $this->_oConfig->getBaseUri() . 'send_cash_out'
            ));

        if ($this->_bIsFiatEnabled && (float)$fAmount)
           $aForm['inputs'][$CNF['FC_AMOUNT']] = array(
                        'type' => 'text',
                        'name' => $CNF['FC_AMOUNT'],
                        'attrs' => array('placeholder' => $fAmount),
                        'caption' => _t("_{$this -> MODULE}_cash_out_form_money"),
                        'info' => _t("_{$this -> MODULE}_cash_out_form_money_info", $fAmount, $this->_sCurrencyCode)
                     );

        if ($this->_bIsPointsEnabled && $fPoints)
            $aForm['inputs'][$CNF['FC_POINTS']] = array(
                'type' => 'text',
                'name' => $CNF['FC_POINTS'],
                'attrs' => array('placeholder' => $fPoints),
                'caption' => _t("_{$this -> MODULE}_cash_out_form_points"),
                'info' => _t("_{$this -> MODULE}_cash_out_form_points_info", $fPoints)
            );

        if ($this->_bIsCreditsEnabled && (float)$fCredits)
            $aForm['inputs'][$CNF['FC_CREDITS']] = array(
                'type' => 'text',
                'name' => $CNF['FC_CREDITS'],
                'attrs' => array('placeholder' => $fCredits),
                'caption' => _t("_{$this -> MODULE}_cash_out_form_credits"),
                'info' => _t("_{$this -> MODULE}_cash_out_form_credits_info", $fCredits)
            );

        $aForm['inputs'] = array_merge($aForm['inputs'], array(
                'buttons' => array(
                    'type' => 'input_set',
                    array(
                        'type' => 'button',
                        'name' => 'copy',
                        'value' => _t("_{$this -> MODULE}_button_cash_out"),
                        'attrs' => array('onclick' => "{$this -> _sJsObject}.cashOutPerform(this);")
                    ),
                    array(
                        'type' => 'reset',
                        'name' => 'close',
                        'value' => _t("_{$this -> MODULE}_close"),
                        'attrs' => array('onclick' => "$(this).closest('.bx-popup-applied:visible').dolPopupHide({removeOnClose: true})")
                    )
                )
            )
        );

        $oForm = new BxTemplFormView($aForm);
        return $oForm->getCode(true);
    }

    public function getSocialSharingCode($iProfileId, $bUsePageUrl = false){
        $sURL = $this->_oConfig->getReferralLink($iProfileId, 0, $bUsePageUrl);
        $sContent = _t('_aqb_affiliate_profile_program_plugin_invitation_text', $sURL);

        $aMarkers = array(
            'id' => $iProfileId,
            'module' => $this->_oConfig->getName(),
            'url' => $sURL,
            'title' => $sContent,
            'img_url' => BxTemplFunctions::getInstance()->getMainLogoUrl()
        );

        $oMenu = BxTemplMenu::getObjectInstance('sys_social_sharing');
        $oMenu->addMarkers($aMarkers);

        if ($bUsePageUrl)
            return BxTemplStudioFunctions::getInstance()->transBox("affiliate-social-sharing-menu", $oMenu->getCode(), true);

        return $oMenu->getCode();
    }

    function addCssJsTimePicker(){
        if (!method_exists('BxTemplFormView', 'getJsCalendarLangs'))
            return false;

        $aLangs = BxTemplFormView::getJsCalendarLangs ();
        $sLang = BxDolLanguages::getInstance()->detectLanguageFromArray ($aLangs);

        $this->addJs(array(
            'flatpickr/dist/flatpickr.min.js',
            'flatpickr/dist/l10n/' . $sLang . '.js'
        ));

        $this->addCss(BX_DIRECTORY_PATH_PLUGINS_PUBLIC . 'flatpickr/dist/|flatpickr.min.css');
    }
}

/** @} */
