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

class AqbAffiliateGridBanners extends BxBaseModGeneralGridAdministration
{
    private $_oDb = null;
    private $_oConfig = null;

    public function __construct ($aOptions, $oTemplate = false)
    {
    	$this -> MODULE = 'aqb_affiliate';
		$this -> _oModule = BxDolModule::getInstance($this -> MODULE);
        $this -> _oDb = $this -> _oModule -> _oDb;
        $this -> _oConfig = $this -> _oModule -> _oConfig;
		$this -> _aConfirmMessages = array(
												'remove' => _t("_{$this -> MODULE}_remove_banner_confirmation")
											);

        parent::__construct ($aOptions, $oTemplate);
    }

	protected function _getCellTitle($mixedValue, $sKey, $aField, $aRow)
    {
        $CNF = &$this->_oConfig->CNF;
        $oImageTranscoder = BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW']);
        $sImage = '';
        if (!empty($oImageTranscoder)) {
            $sImage = $oImageTranscoder->getFileUrl($aRow[$CNF['FB_IMG']]);
            $sImage = '<img class="aqb-affiliate-banner-preview bx-def-border bx-def-box-sizing" src="' . $sImage . '" />';
        }

        return '<td><div class="aqb-affiliate-banner-icon-title">' . $sImage . '<span>' . $mixedValue . '</span></div></td>';
    }

    protected function _getCellActive($mixedValue, $sKey, $aField, $aRow)
    {
        return '<td class="' . (!$mixedValue ? 'disabled' : 'active') . '" >' .  ((int)$mixedValue ? _t("_{$this -> MODULE}_banner_status_active") : _t("_{$this -> MODULE}_banner_status_hidden")) . '</td>';
    }

    protected function _getCellAdded($mixedValue, $sKey, $aField, $aRow)
    {
        return '<td>' .  bx_time_js($mixedValue, BX_FORMAT_DATE) . '</td>';
    }

    protected function _getCellCommission($mixedValue, $sKey, $aField, $aRow)
    {
        $CNF = &$this->_oConfig->CNF;
        $aCommission = $this->_oDb->getBannerCommissionInfo($aRow[$CNF['FB_ID']]);
        $sCommission = '----------';
        if (!empty($aCommission)) {
            asort($aCommission);
            foreach($aCommission as $iKey => $fItem)
                $aCommission[$iKey] = $this->_oConfig->floatToString($fItem);

            $sCommission = implode('/', $aCommission);
        }

        return "<td>{$sCommission}</td>";
    }

	public function performActionRemove()
	{
        $CNF = &$this -> _oConfig -> CNF;
	    $iBannerId = bx_get('ids')[0];
        $aBannerInfo = $this->_oDb->getBannerInfo($iBannerId);
        if (empty($aBannerInfo))
            return echoJson(array(
                               'msg' => _t("_{$this -> MODULE}_admin_banner_not_found")
                           ));

        $this->_oDb->deleteBanner($iBannerId);
        if ($oStorage = BxDolStorage::getObjectInstance($CNF['OBJECT_STORAGE']))
            $oStorage->deleteFile($aBannerInfo[$CNF['FB_IMG']]);

        echoJson(array(
			'grid' => $this -> getCode(false)
		));
	}

    public function performActionDisable()
    {
        $CNF = &$this -> _oConfig -> CNF;
        $iBannerId = bx_get('ids')[0];
        $aBannerInfo = $this->_oDb->getBannerInfo($iBannerId);

        if (empty($aBannerInfo))
            return echoJson(array(
                'msg' => _t("_{$this -> MODULE}_admin_banner_not_found")
            ));

        $this->_oDb->updateBanner($iBannerId, $CNF['FB_ACTIVE'], !$aBannerInfo[$CNF['FB_ACTIVE']]);
        echoJson(array(
            'grid' => $this -> getCode(false)
        ));
    }

    public function performActionCommission()
    {
        $iBannerId = bx_get('ids')[0];
        $aBannerInfo = $this->_oDb->getBannerInfo($iBannerId);
        if (empty($aBannerInfo))
            return echoJson(array(
                'msg' => _t("_{$this -> MODULE}_admin_banner_not_found")
            ));

        $mixedContent = $this -> getBannerCommissionForm($iBannerId);
        if (is_array($mixedContent)) {
            $mixedContent['grid'] = $this -> getCode(false);
            return echoJson($mixedContent);
        }

        echoJson(array(
            'popup' => array(
              'html' => $mixedContent,
              'options' => array('closeOnOuterClick' => false)
            ),
            'grid' => $this -> getCode(false)
        ));
    }

	public function performActionEdit()
    {
        $CNF = &$this -> _oConfig -> CNF;
        $mixedLevel = bx_get('ids');
        if (is_array($mixedLevel))
            $iLevelId = bx_get('ids')[0];
        else
            $iLevelId = bx_get('id');

        $aLevelInfo = $this->_oDb->getBannerInfo($iLevelId);
        if (empty($aLevelInfo)){
            return echoJson(array(
                'msg' => _t("_{$this -> MODULE}_admin_banner_not_found")
            ));
        }

        bx_import('BannersFormEdit',  $this->_oModule->_aModule);

        $oForm = new AqbAffiliateBannersFormEdit($aLevelInfo);
        $oForm->initChecker($aLevelInfo);
        if ($oForm->isSubmittedAndValid()) {
            $aRes = array();
            if ($oForm->update($iLevelId)) {
                $aRes = array('grid' => $this->getCode(false), 'blink' => $iLevelId);
            }

            return echoJson($aRes);
        }

        $sContent = BxDolStudioTemplate::getInstance() -> parseHtmlByName('form_add_form.html', array(
            'form_id' => $CNF['OBJECT_GRID_BANNERS'],
            'form' => $oForm->getCode(true),
            'object' => $CNF['OBJECT_GRID_BANNERS'],
            'action' => 'edit'
        ));

        echoJson(array(
            'popup' => array(
                'html' => BxTemplFunctions::getInstance()->popupBox(
                    time(),
                    $aLevelInfo[$CNF['FB_TITLE']],
                    $sContent
                ),
            'options' => array('closeOnOuterClick' => false))));
    }

    public function performActionAdd()
    {
        $sTitle = _t("_{$this -> MODULE}_admin_action_banner_add");

        bx_import('BannersFormAdd',  $this-> _oModule->_aModule);
        $oForm = new AqbAffiliateBannersFormAdd();

        $oForm->initChecker();
        if ($oForm->isSubmittedAndValid()) {
            $iNewId = $oForm->insert();
            if ($iNewId) {
                $aRes = array('grid' => $this->getCode(false), 'blink' => $iNewId);
            }
            else
                $aRes = array('msg' => _t('_error occurred'));

            return echoJson($aRes);
        }

        $sContent = BxDolStudioTemplate::getInstance()->parseHtmlByName('form_add_form.html', array(
            'form_id' => $this -> _oConfig -> CNF['OBJECT_GRID_BANNERS'],
            'form' => $oForm->getCode(true),
            'object' => $this -> _oConfig -> CNF['OBJECT_GRID_BANNERS'],
            'action' => 'add'
        ));

        echoJson(array('popup' => array('html' => BxTemplFunctions::getInstance()->popupBox(time(), $sTitle, $sContent), 'options' => array('closeOnOuterClick' => false))));
    }

    function getCommissionsFields($iBannerId, $sPrefix = '', $bCollapsed = false){
        $sPrefix = $sPrefix ? "{$sPrefix}_" : '';

        $aInfo = $this -> _oDb -> getBannerCommissionInfo($iBannerId);
        return array(
            "{$sPrefix}section_begin" => array(
                'type' => 'block_header',
                'caption' => _t("_{$this -> MODULE}_commission_{$sPrefix}title"),
                'collapsible' => true,
                'collapsed' => $bCollapsed,
                'attrs' => array(
                        'onclick'=> "javascript:" . $this->_oConfig->getJsObject('utils') . ".onCloseLegend(this);",
                        'class' => ''
                )
            ),
            "{$sPrefix}impression" => array(
                'type' => 'text',
                'name' => $sPrefix . AQB_AFF_IMPRESSION,
                'caption' => _t("_{$this -> MODULE}_admin_impression"),
                'value' => isset($aInfo[$sPrefix.AQB_AFF_IMPRESSION]) ? $this->_oConfig->floatToString($aInfo[$sPrefix.AQB_AFF_IMPRESSION]) : 0
            ),
            "{$sPrefix}click" => array(
                'type' => 'text',
                'name' => $sPrefix . AQB_AFF_CLICK,
                'value' => isset($aInfo[$sPrefix . AQB_AFF_CLICK]) ? $this->_oConfig->floatToString($aInfo[$sPrefix.AQB_AFF_CLICK]) : 0,
                'caption' => _t("_{$this -> MODULE}_admin_click")
            ),
            "{$sPrefix}join" => array(
                'type' => 'text',
                'name' => $sPrefix . AQB_AFF_JOIN,
                'value' => isset($aInfo[$sPrefix . AQB_AFF_JOIN]) ? $this->_oConfig->floatToString($aInfo[$sPrefix.AQB_AFF_JOIN]) : 0,
                'caption' => _t("_{$this -> MODULE}_admin_join"),
                'info' => _t("_{$this -> MODULE}_admin_join_desc")
            ),
            "{$sPrefix}section_end" => array(
                'type' => 'block_end'
            )
        );
    }

    public function getBannerCommissionForm($iBannerId){
        $CNF = &$this->_oConfig->CNF;

        $aForm = [
            'form_attrs' => [
                'id' => 'banners_commission',
                'name' => 'banners_commission',
                'method' => 'post',
                'action' => BX_DOL_URL_ROOT . 'grid.php?' . bx_encode_url_params($_GET)
            ],
            'params' => [
                'db' => [
                    'table' => $CNF['BANNERS'],
                    'key' => $CNF['FB_ID'],
                    'submit_name' => 'save',
                ]
            ],
            'inputs' => [
                'info' => [
                    'type' => 'custom',
                    'content' => _t("_{$this -> MODULE}_price_per_banner_action"),
                ],
            ]];


        $aFiatFields = $aCreditsFields = $aPointsFields = [];
        if ($this->_oDb->isPointsEnabled())
            $aPointsFields = $this -> getCommissionsFields($iBannerId,AQB_AFF_CUR_TYPE_POINTS, true);

        if ($this->_oDb->isCreditsEnabled())
            $aCreditsFields = $this -> getCommissionsFields($iBannerId,AQB_AFF_CUR_TYPE_CREDITS, true);

        if ($this->_oDb->isFiatEnabled())
            $aFiatFields = $this -> getCommissionsFields($iBannerId, '', true);

        $aForm['inputs'] = array_merge($aForm['inputs'],
            $aFiatFields,
            $aPointsFields,
            $aCreditsFields,
            [
              'buttons' => [
                  'type' => 'input_set',
                  [
                      'type' => 'submit',
                      'name' => 'save',
                      'value' => _t("_{$this -> MODULE}_save")
                  ],
                  [
                      'type' => 'reset',
                      'name' => 'close',
                      'value' => _t("_{$this -> MODULE}_close"),
                      'attrs' => ['onclick' => "$(this).closest('.bx-popup-applied:visible').dolPopupHide()"]
                  ]
              ]
            ]
        );

        $oForm = new BxTemplFormView($aForm);
        $oForm->initChecker($aForm);
        if ($oForm->isSubmittedAndValid()) {
            $aCommission = &$_POST;
            unset($aCommission['csrf_token']);
            unset($aCommission['save']);

            $aRes = array('msg' => _t('_aqb_affiliate_nothing_has_been_saved'));
            if ($oForm->update($iBannerId, array($CNF['FB_COMMISSION'] => serialize($aCommission))))
                $aRes = array('blink' => $iBannerId);
            
            return $aRes;
        }

        $sContent = BxDolStudioTemplate::getInstance() -> parseHtmlByName('form_add_form.html', array(
            'form_id' => 'banners_commission',
            'form' => $oForm->getCode(true),
            'object' => $CNF['OBJECT_GRID_BANNERS'],
            'action' => 'save_commission'
        ));

        return BxTemplFunctions::getInstance()->popupBox(time(), _t("_{$this -> MODULE}_set_commission_title"), $sContent);
    }
}

/** @} */
