<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) MSolutions
 * 
 * @defgroup    Fansonly Fansonly module
 * @ingroup     MSolutionsModules
 *
 * @{
 */

bx_import('BxDolModuleConfig');

class MsFansonlyConfig extends BxDolModuleConfig
{
    public $CNF;
    protected $_aHtmlIds;
    protected $_aCurrency;
    // Add these lines to declare the properties
    protected $_aJsClasses = [];
    protected $_aJsObjects = [];
    protected $_aGridObjects = [];

    function __construct($aModule)
    {
        parent::__construct($aModule);
        $this->CNF = array (           
            'IS_STUDIO_SETTINGS' => false,
            'FIELD_STATUS' => 'status',
            
            //'TABLE_ENTRIES' => $aModule['db_prefix'] . 'prices',
            //This field should change for the 

            'TABLE_PRICES' => $aModule['db_prefix'] . 'prices',

            'TABLE_REQUESTS' => $aModule['db_prefix'] . 'requests',
            //'TABLE_ADMINS' => $aModule['db_prefix'] . 'admins',

            'TABLE_SUBSCRIPTIONS' => $aModule['db_prefix'] . 'subscriptions',

            //'TABLE_PRICES' => $aModule['db_prefix'] . 'prices',

            //'ENABLE' => getParam('ms_fansonly_enable'),
            
            /*
            'PERSONS_ENABLE' => getParam('ms_fansonly_enable_persons'),
            'PERSONS_CREATE' => getParam('ms_fansonly_persons_create'),
            'PERSONS_EDIT' => getParam('ms_fansonly_persons_edit'),
            'PERSONS_AUTO' => getParam('ms_fansonly_persons_auto'),
            'PERSONS_REDIRECT' => getParam('ms_fansonly_persons_redirect'),


            'ORGANIZATIONS_ENABLE' => getParam('ms_fansonly_enable_organizations'),
            'ORGANIZATIONS_CREATE' => getParam('ms_fansonly_organizations_create'),
            'ORGANIZATIONS_EDIT' => getParam('ms_fansonly_organizations_edit'),
            'ORGANIZATIONS_AUTO' => getParam('ms_fansonly_organizations_auto'),
            'ORGANIZATIONS_REDIRECT' => getParam('ms_fansonly_organizations_redirect'),

            */            


            // some language keys
            'T' => array (
            	'grid_txt_account_manager' => '_ms_fansonly_grid_txt_account_manager',    //this
                'txt_n_unit' => '_ms_fansonly_txt_n_unit',
                'txt_subscribe_title' => '_ms_fansonly_grid_action_title_subscribe_title',

                'txt_sample_single' => '_ms_fansonly_txt_sample_single',
                'txt_sample_single_with_article' => '_ms_fansonly_txt_sample_single_with_article',
                'txt_sample_comment_single' => '_ms_fansonly_txt_sample_comment_single',
                'txt_sample_vote_single' => '_ms_fansonly_txt_sample_vote_single',
                'txt_sample_score_up_single' => '_ms_fansonly_txt_sample_score_up_single',
                'txt_sample_score_down_single' => '_ms_fansonly_txt_sample_score_down_single',
                'txt_private_profile' => '_ms_fansonly_txt_private_profile',
                'txt_N_fans' => '_ms_fansonly_txt_N_fans',
                'txt_ntfs_join_invitation' => '_ms_fansonly_txt_ntfs_join_invitation',
                'txt_ntfs_join_request' => '_ms_fansonly_txt_ntfs_join_request',
                'txt_ntfs_fan_added' => '_ms_fansonly_txt_ntfs_fan_added',
                'txt_ntfs_timeline_post_common' => '_ms_fansonly_txt_ntfs_timeline_post_common',
                'option_members_mode_multi_subscriptions' => '_ms_fansonly_option_members_mode_multi_subscriptions',
//TODO, avoid users having multi subscriptions, more than 1 subscription per user to user
//Or allow?                
                'option_members_mode_paid_join' => '_ms_fansonly_option_members_mode_paid_join',
                'form_field_author' => '_ms_fansonly_form_entry_input_author',
                'menu_item_title_befriend_sent' => '_ms_fansonly_menu_item_title_befriend_sent',
                'menu_item_title_unfriend_cancel_request' => '_ms_fansonly_menu_item_title_unfriend_cancel_request',
                'menu_item_title_befriend_confirm' => '_ms_fansonly_menu_item_title_befriend_confirm',
                'menu_item_title_unfriend_reject_request' => '_ms_fansonly_menu_item_title_unfriend_reject_request',
                'menu_item_title_befriend' => '_ms_fansonly_menu_item_title_befriend',
                'menu_item_title_unfriend' => '_ms_fansonly_menu_item_title_unfriend',
                'grid_action_err_delete' => '_ms_fansonly_grid_action_err_delete',
                'grid_txt_account_manager' => '_ms_fansonly_grid_txt_account_manager',
                'filter_item_active' => '_ms_fansonly_grid_filter_item_title_adm_active',
                'filter_item_pending' => '_ms_fansonly_grid_filter_item_title_adm_pending',
                'filter_item_suspended' => '_ms_fansonly_grid_filter_item_title_adm_suspended',
                'filter_item_select_one_filter1' => '_ms_fansonly_grid_filter_item_title_adm_select_one_filter1',
                'menu_item_manage_my' => '_ms_fansonly_menu_item_title_manage_my',
                'menu_item_manage_all' => '_ms_fansonly_menu_item_title_manage_all',
                'menu_item_title_become_fan_sent' => '_ms_fansonly_menu_item_title_become_fan_sent',
                'menu_item_title_leave_profile_cancel_request' => '_ms_fansonly_menu_item_title_leave_profile_cancel_request',
                'menu_item_title_become_fan' => '_ms_fansonly_menu_item_title_become_fan',
                'menu_item_title_leave_profile' => '_ms_fansonly_menu_item_title_leave_profile',
                'txt_all_entries_in' => '_ms_fansonly_txt_all_entries_in',
                'txt_all_entries_by_author' => '_ms_fansonly_page_title_browse_by_author',
                'txt_all_entries_by_context' => '_ms_fansonly_page_title_browse_by_context',
                'txt_invitation_popup_title' => '_ms_fansonly_txt_invite_popup_title',
                'txt_invitation_popup_text' => '_ms_fansonly_txt_invite_popup_text',
                'txt_invitation_popup_accept_button' => '_ms_fansonly_txt_invite_popup_button_accept',
                'txt_invitation_popup_decline_button' => '_ms_fansonly_txt_invite_popup_button_decline',
                'txt_invitation_popup_error_invitation_absent' => '_ms_fansonly_txt_invite_popup_error_invitation_absent',
                'txt_invitation_popup_error_wrong_user' => '_ms_fansonly_txt_invite_popup_error_invitation_wrong_user',

                'txt_buy_title' => '_ms_fansonly_grid_action_title_buy_title',
                'txt_cart_item_title' => '_ms_fansonly_txt_cart_item_title',
                
                'msg_performed' => '_ms_fansonly_msg_performed',
                'err_cannot_perform' => '_ms_fansonly_err_cannot_perform',
            ),

        //);



/*

        $this->_aMenuItems2MethodsActions = array_merge($this->_aMenuItems2MethodsActions, array(
            'view-profile-profile' => 'checkAllowedView',
            'edit-profile-profile' => 'checkAllowedEdit',
            'edit-profile-cover' => 'checkAllowedChangeCover',
            'invite-to-profile' => 'checkAllowedInvite',
            'delete-profile-profile' => 'checkAllowedDelete'
        ));

        $this->CNF = array (
*/

            'OBJECT_FORM_PRICE' => 'ms_fansonly_price',
            'OBJECT_FORM_PRICE_DISPLAY_ADD' => 'ms_fansonly_price_add',
            'OBJECT_FORM_PRICE_DISPLAY_EDIT' => 'ms_fansonly_price_edit',

            'OBJECT_GRID_ADMINISTRATION' => 'ms_fansonly_administration',
            'OBJECT_GRID_COMMON' => 'ms_fansonly_common',
            'OBJECT_GRID_CONNECTIONS' => 'ms_fansonly_fans',

            'OBJECT_GRID_PRICES_MANAGE' => 'ms_fansonly_prices_manage',
            'OBJECT_GRID_PRICES_VIEW' => 'ms_fansonly_prices_view',

            'OBJECT_GRID_REQUESTS_MANAGE' => 'ms_fansonly_requests_manage',
            'OBJECT_GRID_REQUESTS_VIEW' => 'ms_fansonly_requests_view',

            'OBJECT_CONNECTIONS' => 'ms_fansonly_fans',
            'OBJECT_FORM_PRELISTS_PERIOD_UNITS' => 'ms_fansonly_period_units',

            //'OBJECT_PRE_LIST_SUBSCRIPTIONS' => 'ms_fansonly_subscriptions',

            'URI_MANAGE_COMMON' => 'fansonly-manage',
            'URL_MANAGE_COMMON' => 'page.php?i=fansonly-manage',
            'URL_MANAGE_ADMINISTRATION' => 'page.php?i=fansonly-administration',
            
            'URL_MANAGE_REQUESTS' => 'page.php?i=fansonly-requests',
            'URI_MANAGE_REQUESTS' => 'fansonly-requests',
            
            //'URL_HOME' => 'page.php?i=meet-main',
            
            'FIELD_AUTHOR' => 'id',

            'EMAIL_FAN_SET_SUBSCRIPTION' => 'ms_fansonly_set_subscription',
            'EMAIL_INVITATION' => 'ms_fansonly_invitation',
            'EMAIL_JOIN_REQUEST' => 'ms_fansonly_join_request',
            'EMAIL_JOIN_CONFIRM' => 'ms_fansonly_join_confirm',
            'EMAIL_FAN_BECOME_ADMIN' => 'ms_fansonly_fan_become_admin',
            'EMAIL_ADMIN_BECOME_FAN' => 'ms_fansonly_admin_become_fan',

//            'PARAM_RECURRING_RESERVE' => 3, // 3 days for recurring payment to be registered
            'PARAM_RECURRING_RESERVE' => 0, // 3 days for recurring payment to be registered
            
            //Todo blur photos
            //'OBJECT_STORAGE' => 'ms_fansonly_photos', //storage for blurred photos
            //we need to save the full url to the link
            

        );


        $this->_aJsClasses = array(
            'manage_tools' => 'MsFansonlyManageTools',
            'prices' => 'MsFansonlyPrices',
            'requests' => 'MsFansonlyRequests'
        );

        $this->_aJsObjects = array(
            'manage_tools' => 'oMsFansonlyManageTools',
            'prices' => 'oMsFansonlyPrices',
            'requests' => 'oMsFansonlyRequests'
        );

        $this->_aGridObjects = array(
            'common' => $this->CNF['OBJECT_GRID_COMMON'],
            'administration' => $this->CNF['OBJECT_GRID_ADMINISTRATION'],
        );

        $sHtmlPrefix = str_replace('_', '-', $this->_sName);
        $this->_aHtmlIds = array(
            'popup_price' => $sHtmlPrefix . '-popup-price'
        );

        $oPayments = BxDolPayments::getInstance();
        $this->_aCurrency = array(
            'code' => $oPayments->getOption('default_currency_code'),
            'sign' => $oPayments->getOption('default_currency_sign')
        );

        
    }

    public function getObject($sType = '')
    {
    	if(empty($sType))
            return $this->_aObjects;

        return isset($this->_aObjects[$sType]) ? $this->_aObjects[$sType] : '';
    }

    public function getPrefix($sType = '')
    {
    	if(empty($sType))
            return $this->_aPrefixes;

        return isset($this->_aPrefixes[$sType]) ? $this->_aPrefixes[$sType] : '';
    }

    public function getJsClass($sType)
    {
        return isset($this->_aJsClasses[$sType]) ? $this->_aJsClasses[$sType] : '';
    }

    public function getJsObject($sType)
    {
        return isset($this->_aJsObjects[$sType]) ? $this->_aJsObjects[$sType] : '';
    }

    public function getGridObject($sType)
    {
        return isset($this->_aGridObjects[$sType]) ? $this->_aGridObjects[$sType] : '';
    }

    public function getHtmlIds($sKey = '')
    {
        if(empty($sKey))
            return $this->_aHtmlIds;

        return isset($this->_aHtmlIds[$sKey]) ? $this->_aHtmlIds[$sKey] : '';
    }

    public function getCurrency()
    {
    	return $this->_aCurrency;
    }

    public function isPaidJoin()
    {
        return true;
        //        return !empty($this->CNF['PARAM_MMODE']) && getParam($this->CNF['PARAM_MMODE']) == BX_BASE_MOD_GROUPS_MMODE_PAID_JOIN;
    }
    public function isMultiSubscriptions(){
        return false; //Disallow multi subscriptions
        //1 subscription per person only
    }
}

/** @} */
