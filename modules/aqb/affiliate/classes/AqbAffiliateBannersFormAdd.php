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
require_once('AqbAffiliateBannersFormEntryHelper.php');
require_once('AqbAffiliateBannersFormEntry.php');

class AqbAffiliateBannersFormAdd extends AqbAffiliateBannersFormEntry
{
    protected $_sModule;
    protected $_oModule;
    protected $_sJsObject;
    private $_oConfig;
    private $_oTemplate;

    function __construct($aCustomForm = array())
    {
        $this->MODULE = 'aqb_affiliate';
    	$this->_oModule = BxDolModule::getInstance($this -> MODULE);
        $this->_oConfig = &$this->_oModule->_oConfig;
    	$this->_oTemplate = &$this->_oModule->_oTemplate;
        $oCNF = &$this->_oModule->_oConfig->CNF;
        $this->_sJsObject = $this->_oModule->_oConfig->getJsObject('manage_tools');

        $aCustomForm = array_replace_recursive(array(
            'form_attrs' => array(
                'method' => 'post',
                'id' => $oCNF['OBJECT_GRID_BANNERS'],
                'action' => BX_DOL_URL_ROOT . 'grid.php?' . bx_encode_url_params($_GET, array('ids', '_r'))
            ),
            'params' => array (
                'db' => array(
                    'table' => $oCNF['BANNERS'],
                    'key' => $oCNF['FB_ID'],
                    'submit_name' => 'submit-banner',
                )
            ),
            'inputs' => array(
                $oCNF['FB_TITLE'] => array(
                    'type' => 'text',
                    'name' => $oCNF['FB_TITLE'],
                    'required' => true,
                    'caption' => _t("_{$this->MODULE}_admin_banner_title"),
                    'db' => array (
                        'pass' => 'Xss',
                    ),
                    'checker' => array (
                        'func' => 'Avail',
                        'params' => array(),
                        'error' => _t("_{$this->MODULE}_admin_banner_title_error"),
                    ),
                ),
                'icon_preview' => array(
                    'type' => 'custom',
                    'name' => 'icon_preview',
                    'caption' => _t("_{$this->MODULE}_admin_banner_image_prev"),
                    'content' => ''
                ),
                $oCNF['FB_IMG'] => array(
                    'type' => 'file',
                    'name' => $oCNF['FB_IMG'],
                    'caption' => _t("_{$this->MODULE}_admin_banner_image"),
                    'required' => true,
                    'checker' => array (
                        'func' => 'Avail',
                        'params' => '',
                        'error' => _t("_{$this->MODULE}_admin_banner_image_error"),
                    ),
                ),
                $oCNF['FB_LINK'] => array(
                    'type' => 'text',
                    'name' => $oCNF['FB_LINK'],
                    'required' => true,
                    'caption' => _t("_{$this->MODULE}_admin_banner_link"),
                    'info' => _t("_{$this->MODULE}_admin_banner_link_info"),
                    'value' => BX_DOL_URL_ROOT,
                    'db' => array (
                        'pass' => 'Xss',
                    ),
                    'checker' => array (
                        'func' => 'Avail',
                        'params' => array(),
                        'error' => _t("_{$this->MODULE}_admin_banner_link_error"),
                    ),
                ),
                'buttons' => array(
                    'type' => 'input_set',
                    'name' => 'buttons',
                    array(
                        'type' => 'submit',
                        'value' => _t("_{$this->MODULE}_admin_save"),
                        'name' => 'submit-banner'
                    ),
                    array(
                        'type' => 'reset',
                        'name' => 'close',
                        'value' => _t("_{$this->MODULE}_close"),
                        'attrs' => array('onclick' => "$(this).closest('.bx-popup-applied:visible').dolPopupHide()")
                    )
                )
            ),
        ), $aCustomForm);

        if (!isset($aCustomForm['id']))
            unset($aCustomForm['inputs']['icon_preview']);
        else
            unset($aCustomForm['inputs'][$oCNF['FB_IMG']]['checker']);

        parent::__construct ($aCustomForm, $this->_oTemplate);
    }
}

/** @} */
