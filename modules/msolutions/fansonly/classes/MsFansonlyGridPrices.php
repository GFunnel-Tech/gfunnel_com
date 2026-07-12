<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) MSolutions
 * 
 * @defgroup    Fansonly Fansonly module
 * @ingroup     MSolutionsModules
 *
 * @{
 */ 

class MsFansonlyGridPrices extends BxTemplGrid
{
    protected $_sModule;
    protected $_oModule;

    protected $_sParamsDivider = '#-#';

    protected $_iUserProfileId;
    protected $_iUserContentId;
    protected $_aUserContentInfo;

    protected $_aPeriodUnits;

    // Declared properties to resolve deprecation warnings (without types for compatibility)
    protected $_iSeller = 0;
    protected $_oPayment = null;
    protected $_bTypeSingle = false;
    protected $_bTypeRecurring = false;

    public function __construct ($aOptions, $oTemplate = false)
    {
        $this->_oModule = BxDolModule::getInstance($this->_sModule);

        parent::__construct ($aOptions, $oTemplate);

        $CNF = $this->_oModule->_oConfig->CNF;

        $this->_aPeriodUnits = BxDolForm::getDataItems($CNF['OBJECT_FORM_PRELISTS_PERIOD_UNITS']);

        $this->_iUserProfileId = 0;
        $this->_iUserContentId = 0;
        $this->_aUserContentInfo = array();

        $iUserProfileId = bx_process_input(bx_get('profile_id'), BX_DATA_INT);

        $this->setProfileId($iUserProfileId);
    }

    public function setSellerId($iProfileId){
        $this->setProfileId($iProfileId);
        $this->_iUserProfileId = $iProfileId;
        $this->_iSeller = $iProfileId;
        $this->_oPayment = BxDolPayments::getInstance();
        $this->_bTypeSingle = $this->_oPayment->isAcceptingPayments($this->_iSeller, BX_PAYMENT_TYPE_SINGLE);
        $this->_bTypeRecurring = $this->_oPayment->isAcceptingPayments($this->_iSeller, BX_PAYMENT_TYPE_RECURRING);
        
    }

    public function setProfileId($iProfileId)
    {
        $this->_iUserProfileId = (int)$iProfileId;
        $this->_aQueryAppend['profile_id'] = $this->_iUserProfileId;

        $oUserProfile = BxDolProfile::getInstance($this->_iUserProfileId);
        $this->_iUserContentId = $iProfileId;

        if($oUserProfile) {

            $this->_aUserContentInfo = $oUserProfile->getInfo();

        }
    }

    protected function _getCellPeriod($mixedValue, $sKey, $aField, $aRow)
    {
        $CNF = $this->_oModule->_oConfig->CNF;

        if((int)$mixedValue == 0)
            $mixedValue = _t('_lifetime');
        else
            $mixedValue = _t($CNF['T']['txt_n_unit'], $mixedValue, _t($this->_aPeriodUnits[$aRow['period_unit']]));

        return parent::_getCellDefault($mixedValue, $sKey, $aField, $aRow);
    }

    protected function _getCellPrice($mixedValue, $sKey, $aField, $aRow)
    {
        if((float)$mixedValue != 0) {
            $aCurrency = $this->_oModule->_oConfig->getCurrency();

            $mixedValue = $aCurrency['sign'] . $mixedValue;
        }
        else 
            $mixedValue = _t('_free');

        return parent::_getCellDefault($mixedValue, $sKey, $aField, $aRow);
    }

    protected function _getIds()
    {
        $aIds = bx_get('ids');
        if(!$aIds || !is_array($aIds)) {
            $iId = (int)bx_get('id');
            if(!$iId) 
                return false;

            $aIds = array($iId);
        }

        return $aIds;
    }
}

/** @} */