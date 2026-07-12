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

class AqbAfConfig extends BxBaseModGeneralConfig {
    protected $_oDb;

    protected $_bGeneral;
    protected $_bRequest;
    protected $_bFollow;
    protected $_iSnippetLenth;
    protected $_iPerPage;
    protected $_iPerPageList;

    protected $_aDisabledFor;
    protected $_aSupportedModules;

    protected $_aHtmlIds;
    protected $_sAnimationEffect;
    protected $_iAnimationSpeed;

    public function __construct($aModule)
    {
        parent::__construct($aModule);

        $this->CNF = array (

            // module icon
            'ICON' => 'group col-blue3',

            // database tables
            'TABLE_ENTRIES' => $aModule['db_prefix'] . 'entries',

            // database fields
            'FIELD_ID' => 'id',
            'FIELD_PROFILE_ID' => 'profile_id',
            'FIELD_PROFILE' => 'profile',
            'FIELD_DESCRIPTION' => 'description',
            'FIELD_ADDED' => 'added',
            'FIELD_CHANGED' => 'changed',

            // page URIs
            'URL_VIEW' => 'page.php?i=auto-friends-view',

            // objects
            'OBJECT_FORM_ENTRY' => 'aqb_auto_friends',
            'OBJECT_FORM_ENTRY_DISPLAY_VIEW' => 'aqb_auto_friends_entry_view',
            'OBJECT_FORM_ENTRY_DISPLAY_ADD' => 'aqb_auto_friends_entry_add',
            'OBJECT_FORM_ENTRY_DISPLAY_EDIT' => 'aqb_auto_friends_entry_edit',
            'OBJECT_FORM_ENTRY_DISPLAY_DELETE' => 'aqb_auto_friends_entry_delete',
            'OBJECT_CONNECTION_FRIENDS' => 'sys_profiles_friends',
            'OBJECT_CONNECTION_FOLLOW' => 'sys_profiles_subscriptions',
            'OBJECT_GRID_FRIENDS' => 'aqb_auto_friends',
        );

        $this->_aPrefixes = array(
            'style' => 'aqb-auto-friends',
            'language' => '_aqb_auto_friends',
            'option' => 'aqb_auto_friends_',
        );

        $this->_aJsClasses = array(
            'main' => 'AqbAfMain'
        );

        $this->_aJsObjects = array(
            'main' => 'oAqbAfMain'
        );

        $this->_aHtmlIds = array(
            'ff_popup' => 'aqb-af-profile-add-popup',
            'ff_form' => 'aqb-af-profile-add-form',
            'ff_profile_id' => 'aqb-af-profile-id',
            'ff_profile' => 'aqb-af-profile',

            'profiles' => 'aqb-af-profiles',
            'profiles_menu' => 'aqb-af-profiles-menu',
        );

        $this->_aDisabledFor = array();
        $this->_aSupportedModules = array(
            'bx_persons' => 'view-persons-profile',
            'bx_organizations' => 'view-organization-profile',
        );

        $this->_sAnimationEffect = 'fade';
        $this->_iAnimationSpeed = 'slow';
    }

    public function init(&$oDb) {
        $this->_oDb = &$oDb;
        $sOptionPrefix = $this->getPrefix('option');

        $this->_bGeneral = $this->_oDb->getParam($sOptionPrefix . 'general') == 'on';
        $this->_bRequest = $this->_oDb->getParam($sOptionPrefix . 'request') == 'on';
        $this->_bFollow = $this->_oDb->getParam($sOptionPrefix . 'follow') == 'on';
        $this->_iSnippetLenth = (int)$this->_oDb->getParam($sOptionPrefix . 'snippet_lenth');
        $this->_iPerPage = (int)$this->_oDb->getParam($sOptionPrefix . 'per_page');
        $this->_iPerPageList = (int)$this->_oDb->getParam($sOptionPrefix . 'per_page_list');

        $sDisabledFor = getParam($sOptionPrefix . 'disabled_for');
        if(!empty($sDisabledFor))
            $this->_aDisabledFor = explode(',', $sDisabledFor);
    }

    public function isGeneral()
    {
        return $this->_bGeneral;
    }

    public function isRequest()
    {
        return $this->_bRequest;
    }

    public function isFollow()
    {
        return $this->_bFollow;
    }

    public function getSnippetLenth()
    {
        return $this->_iSnippetLenth;
    }

    public function getPerPage($sType = '')
    {
        $iResult = 0;
        switch($sType) {
            case 'list':
                $iResult = $this->_iPerPageList;				
                break;

            default:
                $iResult = $this->_iPerPage;
        }

        return $iResult;
    }

    public function isDisabledFor($sModule)
    {
        return in_array($sModule, $this->_aDisabledFor);
    }

    public function getDisabledFor()
    {
        return $this->_aDisabledFor;
    }

    public function getSupportedModules($sModule = '')
    {
        if(empty($sModule))
            return $this->_aSupportedModules;

        return $this->_aSupportedModules[$sModule];
    }

    public function getHtmlId($sType = '')
    {
        if(empty($sType))
            return $this->_aHtmlIds;

        return $this->_aHtmlIds[$sType];
    }

    public function getAnimationEffect()
    {
        return $this->_sAnimationEffect;
    }

    public function getAnimationSpeed()
    {
        return $this->_iAnimationSpeed;
    }

    public function getProfile($iProfileId = 0)
    {
        return BxDolProfile::getInstanceMagic($iProfileId);
    }
}

/** @} */
