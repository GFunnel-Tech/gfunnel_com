<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Auto Friends Auto Friends
 * @ingroup     UNAModules
 *
 * @{
 */

require_once(BX_DIRECTORY_PATH_INC . "design.inc.php");

class AqbAfGridFriends extends BxTemplStudioGrid
{
    protected $_oModule;

    public function __construct ($aOptions, $oTemplate = false)
    {
        parent::__construct ($aOptions, $oTemplate);

        $this->_oModule = BxDolModule::getInstance('aqb_auto_friends');
    }

    public function getCode($isDisplayHeader = true)
    {
        $sContent = '';
        if($isDisplayHeader)
            $sContent .= $this->_oModule->_oTemplate->getJsCode('main');
        $sContent .= parent::getCode($isDisplayHeader);

        return $sContent;
    }
    
    public function performActionAdd()
    {
        $sAction = 'add';

        $CNF = &$this->_oModule->_oConfig->CNF;

        // check and display form
        $oForm = BxDolForm::getObjectInstance($CNF['OBJECT_FORM_ENTRY'], $CNF['OBJECT_FORM_ENTRY_DISPLAY_ADD'], $this->_oModule->_oTemplate);
        if(!$oForm)
            return echoJson(array('msg' => _t('_sys_txt_error_occured')));

        $oForm->aFormAttrs['id'] = $this->_oModule->_oConfig->getHtmlId('ff_form');
        $oForm->aFormAttrs['action'] = BX_DOL_URL_ROOT . 'grid.php?o=' . $this->_sObject . '&a=' . $sAction;        

        $oForm->initChecker();
        if($oForm->isSubmittedAndValid()) {
            $iInitiator = (int)$oForm->getCleanValue($CNF['FIELD_PROFILE_ID']);
            if(empty($iInitiator))
                return echoJson(array('msg' => _t('_aqb_auto_friends_err_not_found')));

            $aInitiator = $this->_oModule->_oDb->getEntries(array('type' => 'profile_id', 'profile_id' => $iInitiator));
            if(!empty($aInitiator) && is_array($aInitiator))
                return echoJson(array('msg' => _t('_aqb_auto_friends_err_already_exists')));

            $aValsToAdd = array ();
            $iId = $oForm->insert($aValsToAdd);
            if($iId != 0) {
                $oConnection = BxDolConnection::getObjectInstance($CNF['OBJECT_CONNECTION_FRIENDS']);

                $aProfiles = $this->_oModule->_oDb->getProfiles();
                foreach($aProfiles as $aProfile) {
                    if($iInitiator == (int)$aProfile['id'])
                        continue;

                    if($oConnection->isConnected($iInitiator, $aProfile['id'], true))
                        continue;

                    $oConnection->addConnection($iInitiator, $aProfile['id']);
                    if(!$this->_oModule->_oConfig->isRequest())
                        $oConnection->addConnection($aProfile['id'], $iInitiator);
                }

                $aResult = array('grid' => $this->getCode(false), 'blink' => $iId);
            }
            else
                $aResult = array('msg' => _t('_sys_txt_error_entry_creation'));

            return echoJson($aResult);
        }

        $sPopupName = $this->_oModule->_oConfig->getHtmlId('ff_popup');
        $sPopupTitle = _t('_aqb_auto_friends_grid_popup_add');
        $sPopupContent = BxTemplStudioFunctions::getInstance()->popupBox($sPopupName, $sPopupTitle, $this->_oModule->_oTemplate->parseHtmlByName('form_add.html', array(
            'js_object' => $this->_oModule->_oConfig->getJsObject('main'),
            'form_id' => $oForm->aFormAttrs['id'],
            'form' => $oForm->getCode(true),
            'object' => $this->_sObject,
            'action' => $sAction
        )));

        echoJson(array('popup' => array('html' => $sPopupContent, 'options' => array('closeOnOuterClick' => false))));
    }

	public function performActionDeleteAndUnfriend()
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        $this->_replaceMarkers();

        $iAffected = 0;
        $aIds = bx_get('ids');
        if (!$aIds || !is_array($aIds))
            return echoJson(array());

        $oConnection = BxDolConnection::getObjectInstance($CNF['OBJECT_CONNECTION_FRIENDS']);
        foreach ($aIds as $mixedId) {
            $aContentInfo = $this->_oModule->_oDb->getContentInfoById($mixedId);
            if(empty($aContentInfo) || !is_array($aContentInfo))
                continue;

            $iInitiator = (int)$aContentInfo[$CNF['FIELD_PROFILE_ID']];
            $aConnections = $oConnection->getConnectedContent($iInitiator, true);
            foreach($aConnections as $iContent)
                $oConnection->removeConnection($iInitiator, (int)$iContent);

            $iAffected += $this->_delete($mixedId) ? 1 : 0;
        }

        return echoJson(array_merge(
            array(
                'grid' => $this->getCode(false),
            ),
            $iAffected ? array() : array('msg' => _t("_sys_grid_delete_failed"))
        ));
    }

    protected function _getCellProfileId($mixedValue, $sKey, $aField, $aRow)
    {
        $oProfile = $this->_oModule->_oConfig->getProfile($mixedValue);
        return parent::_getCellDefault($oProfile->getUnit(), $sKey, $aField, $aRow);
    }

    protected function _getCellAdded($mixedValue, $sKey, $aField, $aRow)
    {
        return parent::_getCellDefault(bx_time_js($mixedValue), $sKey, $aField, $aRow);
    }

    protected function _addJsCss()
    {
        parent::_addJsCss();

        $this->_oModule->_oTemplate->addStudioCss(array(
            'jquery-ui/jquery-ui.min.css',
            'main.css',
            'studio.css'
        ));

        $this->_oModule->_oTemplate->addStudioJs(array(
            'jquery-ui/jquery-ui.custom.min.js', 
            'jquery-ui/jquery.ui.widget.min.js',
            'jquery-ui/jquery.ui.menu.min.js', 
            'jquery-ui/jquery.ui.autocomplete.min.js',
            'jquery.form.min.js',
            'main.js'
        ));

        $oForm = new BxTemplStudioFormView(array());
        $oForm->addCssJs();
    }

	protected function _isVisibleGrid ($a)
    {
        return isAdmin();
    }
}

/** @} */
