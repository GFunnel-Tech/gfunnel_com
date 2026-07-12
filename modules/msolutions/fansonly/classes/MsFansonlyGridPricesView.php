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

class MsFansonlyGridPricesView extends MsFansonlyGridPrices
{
    protected $_oPayment;
    protected $_bTypeSingle;
    protected $_bTypeRecurring;

    protected $_iSeller;
    protected $_iClient;
    protected $_iClientSubscription;

    public function __construct ($aOptions, $oTemplate = false)
    {
        $this->_sModule = 'ms_fansonly';

        parent::__construct ($aOptions, $oTemplate);

        $CNF = &$this->_oModule->_oConfig->CNF;

        $this->_oPayment = BxDolPayments::getInstance();

        $this->_iSeller = $this->_iUserProfileId;

        $this->_iClient = bx_get_logged_profile_id();

        $this->_iClientSubscription = 0;

        $this->_bTypeSingle = $this->_oPayment->isAcceptingPayments($this->_iSeller, BX_PAYMENT_TYPE_SINGLE);
        $this->_bTypeRecurring = $this->_oPayment->isAcceptingPayments($this->_iSeller, BX_PAYMENT_TYPE_RECURRING);
        
    }
    
    protected function _getFilterControls()
    {
        parent::_getFilterControls();
        return '';
    }

    public function performActionChoose()
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $aIds = $this->_getIds();
        if($aIds === false)
            return echoJson(array());

        $aItem = $this->_oModule->_oDb->getPrices(array('type' => 'by_id', 'value' => $aIds[0]));
        if(!is_array($aItem) || empty($aItem) || (float)$aItem['price'] != 0)
            return echoJson(array());

        $aResult = array();

        if($this->_oModule->setSubscription($this->_iUserProfileId, $this->_iClient, array('period' => $aItem['period'], 'period_unit' => $aItem['period_unit'])))
            $aResult = array('grid' => $this->getCode(false), 'blink' => $aItem['id'], 'msg' => _t($CNF['T']['msg_performed']));
        else
            $aResult = array('msg' => _t($CNF['T']['err_cannot_perform']));

        return echoJson($aResult);
    }

    public function getCode($isDisplayHeader = true)
    {
    	return $this->_oPayment->getCartJs() . parent::getCode($isDisplayHeader);
    }

    protected function _getCellSubscriptionId($mixedValue, $sKey, $aField, $aRow)
    {
        return '';

    }

    protected function _getActionChoose ($sType, $sKey, $a, $isSmall = false, $isDisabled = false, $aRow = array())
    {

        return  parent::_getActionDefault($sType, $sKey, $a, false, $isDisabled, $aRow);
    }

    protected function _getActionBuy ($sType, $sKey, $a, $isSmall = false, $isDisabled = false, $aRow = array())
    {
//        print json_encode([$aRow['price'], $this->_bTypeSingle, $this->_bTypeRecurring]);
//        print 'ok';
        $CNF = &$this->_oModule->_oConfig->CNF;
        
        $price = $aRow["price"];
        $unit = $aRow["period"];
        if((int)$unit == 0)
            $unitText = _t('_lifetime');
        else
            $unitText = _t($CNF['T']['txt_n_unit'], $unit, _t($this->_aPeriodUnits[$aRow['period_unit']]));
        
        if((float)$price != 0) {
            $aCurrency = $this->_oModule->_oConfig->getCurrency();
            $priceText = $aCurrency['sign'] . $price;
        }
        else {
            $priceText = _t('_free');
        }

//        if((float)$aRow['price'] == 0 || !$this->_bTypeSingle || $this->_bTypeRecurring)
        if((float)$aRow['price'] == 0 || !$this->_bTypeSingle)
            return '';

//            return 'asdasd';
        $aJs = $this->_oPayment->getAddToCartJs($this->_iSeller, $this->_sModule, $aRow['id'], 1, true);
        if(!empty($aJs) && is_array($aJs)) {
            list($sJsCode, $sJsMethod) = $aJs;

            //print " $unitText, $priceText ";
            
            $title = bx_html_attribute(_t($CNF['T']['txt_buy_title'], $unitText, $priceText));
            $a['attr'] = array(
//                'title' => bx_html_attribute(_t($CNF['T']['txt_buy_title'])),
                'title' => $title,
                'onclick' => $sJsMethod
            );
            $a['title'] = $title;
        }

        return  parent::_getActionDefault($sType, $sKey, $a, false, $isDisabled, $aRow);
    }

    protected function _getCellHeaderActions ($sKey, $aField) {
          return '';
          $s = parent::_getCellHeaderDefault($sKey, $aField);
          return preg_replace ('/<th(.*?)>(.*?)<\/th>/', '<th$1><img src="' . BxDolTemplate::getInstance()->getIconUrl('user.png') . '"></th>', $s);
    }

    protected function _getActionSubscribe ($sType, $sKey, $a, $isSmall = false, $isDisabled = false, $aRow = array())
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

//        print json_encode([$aRow['price'], $this->_bTypeSingle, $this->_bTypeRecurring]);

        if((float)$aRow['price'] == 0 || !$this->_bTypeRecurring)
            return '';
        
        $price = $aRow["price"];
        $unit = $aRow["period"];
        if((int)$unit == 0)
            $unitText = _t('_lifetime');
        else
            $unitText = _t($CNF['T']['txt_n_unit'], $unit, _t($this->_aPeriodUnits[$aRow['period_unit']]));
        
        if((float)$price != 0) {
            $aCurrency = $this->_oModule->_oConfig->getCurrency();
            $priceText = $aCurrency['sign'] . $price;
        }
        else {
            $priceText = _t('_free');
        }
        
        $aJs = $this->_oPayment->getSubscribeJs($this->_iSeller, '', $this->_sModule, $aRow['id'], 1);
        if(!empty($aJs) && is_array($aJs)) {
            list($sJsCode, $sJsMethod) = $aJs;

            $title = bx_html_attribute(_t($CNF['T']['txt_subscribe_title'], $unitText, $priceText));
            $a['attr'] = array(
                'title' => $title,
                'onclick' => $sJsMethod
            );
            $a['title'] = $title;
            if(!$this->_bTypeSingle){
                $a['attr']['class'] = 'buttonFullWidth';
            }
        }

        return  parent::_getActionDefault($sType, $sKey, $a, false, $isDisabled, $aRow);
    }


    protected function _getDataSql($sFilter, $sOrderField, $sOrderDir, $iStart, $iPerPage)
    {

        $this->_aOptions['source'] .= $this->_oModule->_oDb->prepareAsString("AND `profile_id`=? ", $this->_iUserProfileId);

        return parent::_getDataSql($sFilter, $sOrderField, $sOrderDir, $iStart, $iPerPage);
    }


}

/** @} */
