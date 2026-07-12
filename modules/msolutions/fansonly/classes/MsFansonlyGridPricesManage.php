<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) MSolutions
 * 
 * @defgroup    Fansonly Fansonly module
 * @ingroup     MSolutionsModules
 *
 * @{
 */ 

require_once('MsFansonlyGridPrices.php');

class MsFansonlyGridPricesManage extends MsFansonlyGridPrices
{


    public function __construct ($aOptions, $oTemplate = false)
    {
        $this->_sModule = 'ms_fansonly';

        parent::__construct ($aOptions, $oTemplate);

        $CNF = &$this->_oModule->_oConfig->CNF;

    }
    public function setSubscriptionId($iSubscriptionId)
    {
        //$this->_iSubscriptionId = (int)$iSubscriptionId;
        //$this->_aQueryAppend['subscription_id'] = $this->_iSubscriptionId;
    }

    public function getCode($isDisplayHeader = true)
    {
        $sResult = parent::getCode($isDisplayHeader);
        if(empty($sResult))
            return $sResult;

        $sJsCode = $this->_oModule->_oTemplate->getJsCode('prices', array(
            'sObjNameGrid' => $this->_sObject, 
            'aHtmlIds' => $this->_oModule->_oConfig->getHtmlIds()
        ));

        return $sJsCode . $sResult;
    }

    public function performActionAdd()
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;

    	$sAction = 'add';

        $sFilter = bx_get('filter');


    	$oForm = BxDolForm::getObjectInstance($CNF['OBJECT_FORM_PRICE'], $CNF['OBJECT_FORM_PRICE_DISPLAY_ADD']);


    	$oForm->aFormAttrs['action'] = BX_DOL_URL_ROOT . bx_append_url_params('grid.php', array('o' => $this->_sObject, 'a' => $sAction, 'profile_id' => $this->_iUserProfileId));

        $oForm->initChecker();
        if($oForm->isSubmittedAndValid()) {
            $iPeriod = $oForm->getCleanValue('period');
            $sPeriodUnit = $oForm->getCleanValue('period_unit');
            $sPrice = $oForm->getCleanValue('price');

            if(!empty($iPeriod) && empty($sPeriodUnit)) 
                return echoJson(array('msg' => _t('_ms_fansonly_form_price_input_err_period_unit')));

            $aPrice = $this->_oModule->_oDb->getPrices(array('type' => 'by_prpp', 'profile_id' => $this->_iUserProfileId, 'period' => $iPeriod, 'period_unit' => $sPeriodUnit));

            if(!empty($aPrice) && is_array($aPrice))
                return echoJson(array('msg' => _t('_ms_fansonly_err_price_duplicate')));


            $sName = $this->_iUserProfileId.'_'.$iPeriod. '-' . $sPeriodUnit .'-'.$sPrice;
            //$sName = $this->_iUserProfileId.'-'.$iPeriod. '' . $sPeriodUnit .''.$sPrice;
            
            $iId = (int)$oForm->insert(array('name' => $sName, 'profile_id' => $this->_iUserProfileId, 'order' => $this->_oModule->_oDb->getPriceOrderMax() + 1));

            if($iId != 0)
                $aRes = array('grid' => $this->getCode(false), 'blink' => $iId);
            else
                $aRes = array('msg' => _t('_ms_fansonly_err_cannot_perform'));

            echoJson($aRes);
            return;
        }

        bx_import('BxTemplFunctions');
        $sContent = BxTemplFunctions::getInstance()->popupBox($this->_oModule->_oConfig->getHtmlIds('popup_price'), _t('_ms_fansonly_popup_title_price_add'), $this->_oModule->_oTemplate->parseHtmlByName('popup_price.html', array(
            'form_id' => $oForm->aFormAttrs['id'],
            'form' => $oForm->getCode(true),
            'object' => $this->_sObject,
            'action' => $sAction
        )));

        echoJson(array('popup' => array('html' => $sContent, 'options' => array('closeOnOuterClick' => false))));
    }

    public function performActionEdit()
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;

        $sAction = 'edit';

        $aIds = $this->_getIds();
        if($aIds === false)
            return echoJson(array());

        $aItem = $this->_oModule->_oDb->getPrices(array('type' => 'by_id', 'value' => array_shift($aIds)));
        if(!is_array($aItem) || empty($aItem))
            return echoJson(array());

        $oForm = BxDolForm::getObjectInstance($CNF['OBJECT_FORM_PRICE'], $CNF['OBJECT_FORM_PRICE_DISPLAY_EDIT']);

    	$oForm->aFormAttrs['action'] = BX_DOL_URL_ROOT . bx_append_url_params('grid.php', array('o' => $this->_sObject, 'a' => $sAction, 'profile_id' => $this->_iUserProfileId));

        $oForm->initChecker($aItem);
        if($oForm->isSubmittedAndValid()) {
            if($oForm->update($aItem['id']) !== false)
                $aRes = array('grid' => $this->getCode(false), 'blink' => $aItem['id']);
            else
                $aRes = array('msg' => _t('_ms_fansonly_err_cannot_perform'));

            return echoJson($aRes);
        }

        bx_import('BxTemplFunctions');
        $sContent = BxTemplFunctions::getInstance()->popupBox($this->_oModule->_oConfig->getHtmlIds('popup_price'), _t('_ms_fansonly_popup_title_price_edit'), $this->_oModule->_oTemplate->parseHtmlByName('popup_price.html', array(
            'form_id' => $oForm->aFormAttrs['id'],
            'form' => $oForm->getCode(true),
            'object' => $this->_sObject,
            'action' => $sAction
        )));

        $aRes = array('popup' => array('html' => $sContent, 'options' => array('closeOnOuterClick' => false)));

        return echoJson($aRes);
    }

    public function performActionDelete()
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;

    	$aIds = bx_get('ids');
        if(!$aIds || !is_array($aIds))
            return echoJson(array());

        $iAffected = 0;
        $aIdsAffected = array();
        foreach($aIds as $iId)
            if($this->_oModule->_oDb->deletePrices(array('id' => $iId))) {
                $aIdsAffected[] = $iId;
                $iAffected++;
            }

        return echoJson($iAffected ? array('grid' => $this->getCode(false), 'blink' => $aIdsAffected) : array('msg' => _t('_ms_fansonly_err_cannot_perform')));
    }

    protected function _addJsCss()
    {
        parent::_addJsCss();

        $this->_oModule->_oTemplate->addJs(array('jquery.form.min.js', 'prices.js'));
        $this->_oModule->_oTemplate->addCss(array('prices.css'));
    }

    protected function _getFilterControls()
    {
        parent::_getFilterControls();

        $sContent = '';
        $oForm = new BxTemplFormView(array());

        $aInputSearch = array(
            'type' => 'text',
            'name' => 'keyword',
            'attrs' => array(
                'id' => 'bx-grid-search-' . $this->_sObject,
            ),
        );
        $sContent .= $oForm->genRow($aInputSearch);

        return $sContent;
    }

    protected function _getDataSql($sFilter, $sOrderField, $sOrderDir, $iStart, $iPerPage)
    {
        if(strpos($sFilter, $this->_sParamsDivider) !== false) {
            list($iSubscriptionId, $sFilter) = explode($this->_sParamsDivider, $sFilter);
            if(!is_numeric($iSubscriptionId))
                $iSubscriptionId = 0;

            $this->setSubscriptionId($iSubscriptionId);
        }

        $this->_aOptions['source'] .= $this->_oModule->_oDb->prepareAsString("AND `profile_id`=? ", $this->_iUserProfileId);

        return parent::_getDataSql($sFilter, $sOrderField, $sOrderDir, $iStart, $iPerPage);
    }

    protected function _isVisibleGrid ($a)
    {

        //TODO, fix this
        return $this->_oModule->checkAllowedManagePrice($this->_aUserContentInfo) == CHECK_ACTION_RESULT_ALLOWED;
    }
}

/** @} */
