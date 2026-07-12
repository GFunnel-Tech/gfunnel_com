<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Listing Listing
 * @ingroup     ModzzzModules
 *
 * @{
 */

bx_import('BxDolInformer');

class MzListingConfig extends BxBaseModTextConfig
{
    function __construct($aModule)
    {
        parent::__construct($aModule);

        $aMenuItems2Methods = array (
            'approve' => 'checkAllowedApprove',
            'edit-listing' => 'checkAllowedEdit',
            'delete-listing' => 'checkAllowedDelete',
        );

        $this->CNF = array_merge($this->CNF, array (
 
 			/*[begin] UNA 13 RC */
			'FIELD_THUMB_POSITION' => 'thumb_data',
			'OBJECT_IMAGES_TRANSCODER_MINIATURE' => 'mz_listing_miniature',
			'OBJECT_IMAGES_TRANSCODER_MINIATURE_PHOTOS' => 'mz_listing_miniature_photos',
			/*[end] UNA 13 RC */

			/*BEGIN UNA 13*/
            'PARAM_USE_IIN' => 'mz_listing_internal_interested_notification',  
            'FIELD_CF' => 'cf',
			/*END UNA 13*/

			'OBJECT_CONTENT_INFO' => 'mz_listing',
            'OBJECT_CMTS_CONTENT_INFO' => 'mz_listing_cmts',

            'PARAM_AUTO_APPROVE' => 'mz_listing_enable_auto_approve', 
            'OBJECT_IMAGES_TRANSCODER_VIEW_PHOTOS' => 'mz_listing_view_photos',
 
			/* begin broadcast*/
            'OBJECT_FORM_BROADCAST' => 'mz_listing_broadcast',
            'OBJECT_FORM_BROADCAST_DISPLAY_ADD' => 'mz_listing_broadcast_add',
            'OBJECT_MENU_ACTIONS_VIEW_BROADCAST' => 'mz_listing_view_broadcast', // actions menu on view entry page
            'OBJECT_MENU_SUBMENU_VIEW_BROADCAST' => 'mz_listing_view_broadcast_submenu', // broadcast submenu     

            'URI_CREATE_BROADCAST' => 'create-listing-broadcast',
 
            'ETEMPLATE_BROADCAST' => 'mz_listing_broadcast', 
			/* end broadcast*/
 
			/* begin announcement*/
            'TABLE_ANNOUNCEMENT' => $aModule['db_prefix'] . 'announcements',
            'FIELD_ANNOUNCEMENT' => 'announcements',
            'OBJECT_COMMENTS_ANNOUNCEMENT' => 'mz_listing_announcements',
            'OBJECT_FORM_ANNOUNCEMENT' => 'mz_listing_announcement',
            'OBJECT_FORM_ANNOUNCEMENT_DISPLAY_VIEW' => 'mz_listing_announcement_view',
            'OBJECT_FORM_ANNOUNCEMENT_DISPLAY_ADD' => 'mz_listing_announcement_add',
            'OBJECT_FORM_ANNOUNCEMENT_DISPLAY_EDIT' => 'mz_listing_announcement_edit',
            'OBJECT_FORM_ANNOUNCEMENT_DISPLAY_DELETE' => 'mz_listing_announcement_delete',
            'OBJECT_MENU_ACTIONS_VIEW_ANNOUNCEMENT' => 'mz_listing_view_announcement', // actions menu on view entry page
            'OBJECT_MENU_SUBMENU_VIEW_ANNOUNCEMENT' => 'mz_listing_view_announcement_submenu', // announcement submenu     
 
            'URI_VIEW_ANNOUNCEMENTS' => 'view-listing-announcements',
            'URI_VIEW_ANNOUNCEMENT' => 'view-listing-announcement',
            'URI_CREATE_ANNOUNCEMENT' => 'create-listing-announcement',
            'URI_EDIT_ANNOUNCEMENT' => 'edit-listing-announcement',
 
            'PARAM_CHARS_TITLE_ANNOUNCEMENT' => 'mz_listing_title_chars_announcement',
            'PARAM_CHARS_SUMMARY_PLAIN_ANNOUNCEMENT' => 'mz_listing_plain_summary_chars_announcement',
            'PARAM_DELETE_ANNOUNCEMENT' => 'mz_listing_delete_announcement',
            'FIELD_ANNOUNCEMENT_TITLE' => 'title',
            'FIELD_ANNOUNCEMENT_TEXT' => 'text', 
            'FIELD_URI' => 'uri',  
			/* end announcement*/
    
   			/* begin schedule*/
            'TABLE_SCHEDULE' => $aModule['db_prefix'] . 'schedules',
            'FIELD_SCHEDULE' => 'schedules',
            'OBJECT_COMMENTS_SCHEDULE' => 'mz_listing_schedules',
            'OBJECT_FORM_SCHEDULE' => 'mz_listing_schedule',
            'OBJECT_FORM_SCHEDULE_DISPLAY_VIEW' => 'mz_listing_schedule_view',
            'OBJECT_FORM_SCHEDULE_DISPLAY_ADD' => 'mz_listing_schedule_add',
            'OBJECT_FORM_SCHEDULE_DISPLAY_EDIT' => 'mz_listing_schedule_edit',
            'OBJECT_FORM_SCHEDULE_DISPLAY_DELETE' => 'mz_listing_schedule_delete',
            'OBJECT_MENU_ACTIONS_VIEW_SCHEDULE' => 'mz_listing_view_schedule', // actions menu on view entry page
            'OBJECT_MENU_SUBMENU_VIEW_SCHEDULE' => 'mz_listing_view_schedule_submenu', // schedule submenu     
 
            'URI_VIEW_SCHEDULES' => 'view-listing-schedules',
            'URI_VIEW_SCHEDULE' => 'view-listing-schedule',
            'URI_CREATE_SCHEDULE' => 'create-listing-schedule',
            'URI_EDIT_SCHEDULE' => 'edit-listing-schedule',
 
            'PARAM_CHARS_TITLE_SCHEDULE' => 'mz_listing_title_chars_schedule',
            'PARAM_CHARS_SUMMARY_PLAIN_SCHEDULE' => 'mz_listing_plain_summary_chars_schedule',
            'PARAM_DELETE_SCHEDULE' => 'mz_listing_delete_schedule',
            'FIELD_SCHEDULE_TITLE' => 'title',
            'FIELD_SCHEDULE_TEXT' => 'text', 
            'FIELD_URI' => 'uri',  
			/* end schedule*/
 
   			/* begin claim*/
            'TABLE_CLAIMS' => $aModule['db_prefix'] . 'claims',
            'OBJECT_FORM_CLAIM' => 'mz_listing_claim',
            'OBJECT_FORM_CLAIM_DISPLAY_VIEW' => 'mz_listing_claim_view',
            'OBJECT_FORM_CLAIM_DISPLAY_ADD' => 'mz_listing_claim_add',
            'OBJECT_FORM_CLAIM_DISPLAY_EDIT' => 'mz_listing_claim_edit',
            'OBJECT_FORM_CLAIM_DISPLAY_DELETE' => 'mz_listing_claim_delete',
            'OBJECT_MENU_ACTIONS_VIEW_CLAIM' => 'mz_listing_view_claim', // actions menu on view entry page
            'OBJECT_MENU_SUBMENU_VIEW_CLAIM' => 'mz_listing_view_claim_submenu', // claim submenu     
 
            'URI_VIEW_CLAIM' => 'view-listing-claim',
            'URI_CREATE_CLAIM' => 'claim-listing', 
            'URI_EDIT_CLAIM' => 'edit-listing-claim',

            'PARAM_CHARS_TITLE_CLAIM' => 'mz_listing_title_chars_claim',
            'PARAM_CHARS_SUMMARY_PLAIN_CLAIM' => 'mz_listing_plain_summary_chars_claim',
            'PARAM_DELETE_CLAIM' => 'mz_listing_delete_claim',

			'OBJECT_STORAGE_CLAIM' => 'mz_listing_photos_claim',
            'OBJECT_IMAGES_TRANSCODER_PREVIEW_CLAIM' => 'mz_listing_preview_claim',
            'OBJECT_IMAGES_TRANSCODER_GALLERY_CLAIM' => 'mz_listing_gallery_claim',
   
			/* end claim*/

			/* begin branch*/
            'TABLE_BRANCHES' => $aModule['db_prefix'] . 'branches',
            'FIELD_BRANCHES' => 'branches',
            'OBJECT_COMMENTS_BRANCHES' => 'mz_listing_branches',
            'OBJECT_FORM_BRANCH' => 'mz_listing_branch',
            'OBJECT_FORM_BRANCH_DISPLAY_VIEW' => 'mz_listing_branch_view',
            'OBJECT_FORM_BRANCH_DISPLAY_ADD' => 'mz_listing_branch_add',
            'OBJECT_FORM_BRANCH_DISPLAY_EDIT' => 'mz_listing_branch_edit',
            'OBJECT_FORM_BRANCH_DISPLAY_DELETE' => 'mz_listing_branch_delete',
            'OBJECT_MENU_ACTIONS_VIEW_BRANCH' => 'mz_listing_view_branch', // actions menu on view entry page
            'OBJECT_MENU_SUBMENU_VIEW_BRANCH' => 'mz_listing_view_branch_submenu', // branch submenu     
 
            'URI_VIEW_BRANCHES' => 'view-listing-branches',
            'URI_VIEW_BRANCH' => 'view-listing-branch',
            'URI_CREATE_BRANCH' => 'create-listing-branch',
            'URI_EDIT_BRANCH' => 'edit-listing-branch',
 
            'PARAM_CHARS_TITLE_BRANCH' => 'mz_listing_title_chars_branch',
            'PARAM_CHARS_SUMMARY_PLAIN_BRANCH' => 'mz_listing_plain_summary_chars_branch',
            'PARAM_DELETE_BRANCH' => 'mz_listing_delete_branch',
            'FIELD_BRANCH_TITLE' => 'title',
            'FIELD_BRANCH_TEXT' => 'text', 
 
            'OBJECT_STORAGE_BRANCH' => 'mz_listing_photos_branch',
            'OBJECT_IMAGES_TRANSCODER_PREVIEW_BRANCH' => 'mz_listing_preview_branch',
            'OBJECT_IMAGES_TRANSCODER_GALLERY_BRANCH' => 'mz_listing_gallery_branch',
   
			'OBJECT_METATAGS_BRANCH' => 'mz_listing_branch',

			/* end branch*/

			/* begin service*/
            'TABLE_SERVICES' => $aModule['db_prefix'] . 'services',
            'FIELD_SERVICES' => 'services',
            'OBJECT_COMMENTS_SERVICES' => 'mz_listing_services',
            'OBJECT_FORM_SERVICE' => 'mz_listing_service',
            'OBJECT_FORM_SERVICE_DISPLAY_VIEW' => 'mz_listing_service_view',
            'OBJECT_FORM_SERVICE_DISPLAY_ADD' => 'mz_listing_service_add',
            'OBJECT_FORM_SERVICE_DISPLAY_EDIT' => 'mz_listing_service_edit',
            'OBJECT_FORM_SERVICE_DISPLAY_DELETE' => 'mz_listing_service_delete',
            'OBJECT_MENU_ACTIONS_VIEW_SERVICE' => 'mz_listing_view_service', // actions menu on view entry page
            'OBJECT_MENU_SUBMENU_VIEW_SERVICE' => 'mz_listing_view_service_submenu', // service submenu     
 
            'URI_VIEW_SERVICES' => 'view-listing-services',
            'URI_VIEW_SERVICE' => 'view-listing-service',
            'URI_CREATE_SERVICE' => 'create-listing-service',
            'URI_EDIT_SERVICE' => 'edit-listing-service',
 
            'PARAM_CHARS_TITLE_SERVICE' => 'mz_listing_title_chars_service',
            'PARAM_CHARS_SUMMARY_PLAIN_SERVICE' => 'mz_listing_plain_summary_chars_service',
            'PARAM_DELETE_SERVICE' => 'mz_listing_delete_service',
            'FIELD_SERVICE_TITLE' => 'title',
            'FIELD_SERVICE_TEXT' => 'text',   
            'OBJECT_STORAGE_SERVICE' => 'mz_listing_photos_service',
            'OBJECT_IMAGES_TRANSCODER_PREVIEW_SERVICE' => 'mz_listing_preview_service',
            'OBJECT_IMAGES_TRANSCODER_GALLERY_SERVICE' => 'mz_listing_gallery_service', 
			/* end service*/

            'FIELD_LISTING_ID' => 'entry_id', 
			'GET_PARAM_LISTING' => 'listing',  
            'OBJECT_PRIVACY_JOIN' => 'mz_listing_allow_join_to',
 
            'PARAM_PER_PAGE_FOR_FAVORITES_LISTS' => 'mz_listing_per_page_for_favorites_lists',
            'URI_FAVORITES_LIST' => 'listing-favorites',
			'OBJECT_NOTES' => 'mz_listing_notes',
			'OBJECT_PRIVACY_LIST_VIEW' => 'mz_listing_allow_view_favorite_list',


            // module icon
            'ICON' => 'briefcase col-green2',

            // database tables
            'TABLE_ENTRIES' => $aModule['db_prefix'] . 'entries',
            'TABLE_ENTRIES_FULLTEXT' => 'title_text',
            'TABLE_CATEGORIES' => $aModule['db_prefix'] . 'categories',
            'TABLE_INTERESTED_TRACK' => $aModule['db_prefix'] . 'interested_track',
 
            // database fields
     
            'FIELD_WEBSITE' => 'website', 
            'FIELD_EMAIL' => 'email', 
            'FIELD_PHONE' => 'phone',   
            'FIELD_ID' => 'id',
            'FIELD_AUTHOR' => 'author',
            'FIELD_ADDED' => 'added',
            'FIELD_CHANGED' => 'changed',
            'FIELD_TITLE' => 'title',
            'FIELD_TEXT' => 'text',
            'FIELD_TEXT_ID' => 'listing-text',
            'FIELD_CATEGORY' => 'category',
            'FIELD_SUBCATEGORY' => 'subcategory', 
            'FIELD_CATEGORY_VIEW' => 'category_view',
            'FIELD_CATEGORY_SELECT' => 'category',
            'FIELD_SUBCATEGORY_SELECT' => 'subcategory',
            'FIELD_ALLOW_VIEW_TO' => 'allow_view_to',
            'FIELD_COVER' => 'covers',
            'FIELD_PHOTO' => 'pictures',
            'FIELD_VIDEO' => 'videos',
            'FIELD_FILE' => 'files',
            'FIELD_POLL' => 'polls',
            'FIELD_THUMB' => 'thumb',
            'FIELD_ATTACHMENTS' => 'attachments',
            'FIELD_VIEWS' => 'views',
            'FIELD_COMMENTS' => 'comments',
            'FIELD_STATUS' => 'status',
            'FIELD_STATUS_ADMIN' => 'status_admin',
            'FIELD_LOCATION' => 'location',
            'FIELD_LOCATION_PREFIX' => 'location',
            'FIELD_LABELS' => 'labels',
            'FIELD_ANONYMOUS' => 'anonymous',
            'FIELDS_WITH_KEYWORDS' => 'auto', // can be 'auto', array of fields or comma separated string of field names, works only when OBJECT_METATAGS is specified
            'FIELDS_DELAYED_PROCESSING' => 'videos', // can be array of fields or comma separated string of field names
 			'FIELD_HOURS' => 'hours',

			'FIELD_CLAIM_STATUS' => 'claim_status',
			'URI_VIEW_CLAIM' => 'view-listing-claim',

            // page URIs
            'URI_VIEW_ENTRY' => 'view-listing',
            'URI_AUTHOR_ENTRIES' => 'listing-author',
            'URI_ENTRIES_BY_CONTEXT' => 'listing-context',
            'URI_ADD_ENTRY' => 'create-listing',
            'URI_EDIT_ENTRY' => 'edit-listing',
            'URI_MANAGE_COMMON' => 'listing-manage',

            'URL_HOME' => 'page.php?i=listing-home',
            'URL_POPULAR' => 'page.php?i=listing-popular',
            'URL_LOCAL' => 'page.php?i=listing-local',  
            'URL_FEATURED' => 'page.php?i=listing-featured',
            'URL_UPDATED' => 'page.php?i=listing-updated',
            'URL_CATEGORIES' => 'page.php?i=listing-categories',
            'URL_SUBCATEGORIES' => 'page.php?i=listing-categories',
				
            'URL_MANAGE_COMMON' => 'page.php?i=listing-manage',
            'URL_MANAGE_ADMINISTRATION' => 'page.php?i=listing-administration',

            'GET_PARAM_CATEGORY' => 'category',
            'GET_PARAM_SUBCATEGORY' => 'subcategory',

			'CATEGORY_URL_TYPE' => array (
					'mz_listing_today' => 'URL_TODAY', 
			 ),

			'CATEGORY_PARAM_TYPE' => array (
					'mz_listing_today' => 'GET_PARAM_TODAY', 
			 ),

            'URL_TODAY' => 'page.php?i=listing-today',
            'GET_PARAM_TODAY' => 'today',
 
            // some params
            'PARAM_CHARS_SUMMARY' => 'mz_listing_summary_chars',
            'PARAM_CHARS_SUMMARY_PLAIN' => 'mz_listing_plain_summary_chars',
            'PARAM_NUM_RSS' => 'mz_listing_rss_num',
            'PARAM_SEARCHABLE_FIELDS' => 'mz_listing_searchable_fields',
            'PARAM_PER_PAGE_BROWSE_SHOWCASE' => 'mz_listing_per_page_browse_showcase',
            'PARAM_CATEGORY_LEVEL_MAX' => 1,

            // objects
            'OBJECT_STORAGE' => 'mz_listing_covers',
            'OBJECT_STORAGE_FILES' => 'mz_listing_files',
            'OBJECT_STORAGE_PHOTOS' => 'mz_listing_photos',
            'OBJECT_STORAGE_VIDEOS' => 'mz_listing_videos',
            'OBJECT_IMAGES_TRANSCODER_PREVIEW' => 'mz_listing_preview',
            'OBJECT_IMAGES_TRANSCODER_GALLERY' => 'mz_listing_gallery',
            'OBJECT_IMAGES_TRANSCODER_COVER' => 'mz_listing_cover',
            'OBJECT_IMAGES_TRANSCODER_PREVIEW_FILES' => 'mz_listing_preview_files',
            'OBJECT_IMAGES_TRANSCODER_GALLERY_FILES' => 'mz_listing_gallery_files',
            'OBJECT_IMAGES_TRANSCODER_PREVIEW_PHOTOS' => 'mz_listing_preview_photos',
            'OBJECT_IMAGES_TRANSCODER_GALLERY_PHOTOS' => 'mz_listing_gallery_photos',
            'OBJECT_VIDEOS_TRANSCODERS' => array(
                'poster' => 'mz_listing_videos_poster', 
            	'poster_preview' => 'mz_listing_videos_poster_preview',
            	'mp4' => 'mz_listing_videos_mp4', 
            	'mp4_hd' => 'mz_listing_videos_mp4_hd'
            ),
            'OBJECT_VIDEO_TRANSCODER_HEIGHT' => '480px',
 
            'OBJECT_REPORTS' => 'mz_listing',
            'OBJECT_VIEWS' => 'mz_listing',
            'OBJECT_VOTES' => 'mz_listing',
            'OBJECT_REACTIONS' => 'mz_listing_reactions',
            'OBJECT_SCORES' => 'mz_listing',
            'OBJECT_FAVORITES' => 'mz_listing',
            'OBJECT_FEATURED' => 'mz_listing',
            'OBJECT_METATAGS' => 'mz_listing',
            'OBJECT_COMMENTS' => 'mz_listing',
            'OBJECT_REVIEWS' => 'mz_listing_reviews',
            'OBJECT_CATEGORY' => '',
            'OBJECT_PRIVACY_VIEW' => 'mz_listing_allow_view_to',
            'OBJECT_FORM_CATEGORY' => 'mz_listing_category',
            'OBJECT_FORM_CATEGORY_DISPLAY_ADD' => 'mz_listing_category_add',
            'OBJECT_FORM_CATEGORY_DISPLAY_EDIT' => 'mz_listing_category_edit',
            'OBJECT_FORM_CATEGORY_DISPLAY_DELETE' => 'mz_listing_category_delete',
            'OBJECT_FORM_ENTRY' => 'mz_listing',
            'OBJECT_FORM_ENTRY_DISPLAY_VIEW' => 'mz_listing_entry_view',
 
            'OBJECT_FORM_ENTRY_DISPLAY_ADD' => 'mz_listing_entry_add',
            'OBJECT_FORM_ENTRY_DISPLAY_EDIT' => 'mz_listing_entry_edit',
            'OBJECT_FORM_ENTRY_DISPLAY_DELETE' => 'mz_listing_entry_delete',
            'OBJECT_FORM_POLL' => 'mz_listing_poll',
            'OBJECT_FORM_POLL_DISPLAY_ADD' => 'mz_listing_poll_add',
            'OBJECT_MENU_ENTRY_ATTACHMENTS' => 'mz_listing_entry_attachments', // attachments menu in create/edit forms
            'OBJECT_MENU_ACTIONS_VIEW_ENTRY' => 'mz_listing_view', // actions menu on view entry page
            'OBJECT_MENU_ACTIONS_VIEW_ENTRY_ALL' => 'mz_listing_view_actions', // all actions menu on view entry page
            'OBJECT_MENU_ACTIONS_MY_ENTRIES' => 'mz_listing_my', // actions menu on my entries page
            'OBJECT_MENU_SUBMENU' => 'mz_listing_submenu', // main module submenu
            'OBJECT_MENU_SUBMENU_VIEW_ENTRY' => 'mz_listing_view_submenu', // view entry submenu
            'OBJECT_MENU_SUBMENU_VIEW_ENTRY_MAIN_SELECTION' => 'listing-home', // first item in view entry submenu from main module submenu
            'OBJECT_MENU_SNIPPET_META' => 'mz_listing_snippet_meta', // menu for snippet meta info
            'OBJECT_MENU_MANAGE_TOOLS' => 'mz_listing_menu_manage_tools', //manage menu in content administration tools
            'OBJECT_GRID_CATEGORIES' => 'mz_listing_categories',
            'OBJECT_GRID_ADMINISTRATION' => 'mz_listing_administration',
            'OBJECT_GRID_COMMON' => 'mz_listing_common',
            'OBJECT_UPLOADERS' => array('mz_listing_html5'), 
            
			'OBJECT_GRID_CLAIM' => 'mz_listing_claim', 
			'OBJECT_GRID_CLAIM_ACCEPT' => 'mz_listing_claim_accept', 
			'OBJECT_GRID_CLAIM_REJECT' => 'mz_listing_claim_reject', 

			/* begin reward*/
			'TABLE_REWARDS' => $aModule['db_prefix'] . 'rewards',
			'FIELD_REWARDS' => 'rewards',
			'OBJECT_COMMENTS_REWARDS' => 'mz_listing_rewards',
			'OBJECT_FORM_REWARD' => 'mz_listing_reward',
			'OBJECT_FORM_REWARD_DISPLAY_VIEW' => 'mz_listing_reward_view',
			'OBJECT_FORM_REWARD_DISPLAY_ADD' => 'mz_listing_reward_add',
			'OBJECT_FORM_REWARD_DISPLAY_EDIT' => 'mz_listing_reward_edit',
			'OBJECT_FORM_REWARD_DISPLAY_DELETE' => 'mz_listing_reward_delete',
			'OBJECT_MENU_ACTIONS_VIEW_REWARD' => 'mz_listing_view_reward', // actions menu on view entry page
			'OBJECT_MENU_SUBMENU_VIEW_REWARD' => 'mz_listing_view_reward_submenu', // reward submenu     

			'URI_VIEW_REWARDS' => 'view-listing-rewards',
			'URI_VIEW_REWARD' => 'view-listing-reward',
			'URI_CREATE_REWARD' => 'create-listing-reward',
			'URI_EDIT_REWARD' => 'edit-listing-reward',

			'PARAM_CHARS_TITLE_REWARD' => 'mz_listing_title_chars_reward',
			'PARAM_CHARS_SUMMARY_PLAIN_REWARD' => 'mz_listing_plain_summary_chars_reward',
			'PARAM_DELETE_REWARD' => 'mz_listing_delete_reward',
			'FIELD_REWARD_TITLE' => 'title',
			'FIELD_REWARD_TEXT' => 'text',   
			'OBJECT_STORAGE_REWARD' => 'mz_listing_photos_reward',
			'OBJECT_IMAGES_TRANSCODER_PREVIEW_REWARD' => 'mz_listing_preview_reward',
			'OBJECT_IMAGES_TRANSCODER_GALLERY_REWARD' => 'mz_listing_gallery_reward', 
			/* end reward*/

			/* begin gift*/
			'TABLE_GIFTS' => $aModule['db_prefix'] . 'gifts',
			'FIELD_GIFTS' => 'gifts',
			'OBJECT_COMMENTS_GIFTS' => 'mz_listing_gifts',
			'OBJECT_FORM_GIFT' => 'mz_listing_gift',
			'OBJECT_FORM_GIFT_DISPLAY_VIEW' => 'mz_listing_gift_view',
			'OBJECT_FORM_GIFT_DISPLAY_ADD' => 'mz_listing_gift_add',
			'OBJECT_FORM_GIFT_DISPLAY_EDIT' => 'mz_listing_gift_edit',
			'OBJECT_FORM_GIFT_DISPLAY_DELETE' => 'mz_listing_gift_delete',
			'OBJECT_MENU_ACTIONS_VIEW_GIFT' => 'mz_listing_view_gift', // actions menu on view entry page
			'OBJECT_MENU_SUBMENU_VIEW_GIFT' => 'mz_listing_view_gift_submenu', // gift submenu     

			'URI_VIEW_GIFTS' => 'view-listing-gifts',
			'URI_VIEW_GIFT' => 'view-listing-gift',
			'URI_CREATE_GIFT' => 'create-listing-gift',
			'URI_EDIT_GIFT' => 'edit-listing-gift',

			'PARAM_CHARS_TITLE_GIFT' => 'mz_listing_title_chars_gift',
			'PARAM_CHARS_SUMMARY_PLAIN_GIFT' => 'mz_listing_plain_summary_chars_gift',
			'PARAM_DELETE_GIFT' => 'mz_listing_delete_gift',
			'FIELD_GIFT_TITLE' => 'title',
			'FIELD_GIFT_TEXT' => 'text',   
			'OBJECT_STORAGE_GIFT' => 'mz_listing_photos_gift',
			'OBJECT_IMAGES_TRANSCODER_PREVIEW_GIFT' => 'mz_listing_preview_gift',
			'OBJECT_IMAGES_TRANSCODER_GALLERY_GIFT' => 'mz_listing_gallery_gift', 
			/* end gift*/
 
            // menu items which visibility depends on custom visibility checking
            'MENU_ITEM_TO_METHOD' => array (
                'mz_listing_my' => array (
                    'create-listing' => 'checkAllowedAdd',
                ),
                'mz_listing_view' => $aMenuItems2Methods,
            ),

            // informer messages
            'INFORMERS' => array (
                'approving' => array (
                    'name' => 'mz-listing-approving',
                    'map' => array (
                        'pending' => array('msg' => '_mz_listing_txt_msg_status_pending', 'type' => BX_INFORMER_ALERT),
                        'hidden' => array('msg' => '_mz_listing_txt_msg_status_hidden', 'type' => BX_INFORMER_ERROR),
                    ),
                ), 
                'processing' => array (
                    'name' => 'mz-listing-processing',
                    'map' => array (
                        'awaiting' => array('msg' => '_mz_listing_txt_processing_awaiting', 'type' => BX_INFORMER_ALERT),
                        'failed' => array('msg' => '_mz_listing_txt_processing_failed', 'type' => BX_INFORMER_ERROR)
                    ),
                ),
                'scheduled' => array (
                    'name' => 'mz-listing-scheduled',
                    'map' => array (
                        'awaiting' => array('msg' => '_mz_listing_txt_scheduled_awaiting', 'type' => BX_INFORMER_ALERT),
                    ),
                ),
            ),

            // email templates
            'ETEMPLATE_INTERESTED' => 'mz_listing_interested',
            'ETEMPLATE_BRANCH' => 'mz_listing_branch',
            'ETEMPLATE_CLAIM_PENDING' => 'mz_listing_claim_pending',
            'ETEMPLATE_CLAIM_ACCEPTED' => 'mz_listing_claim_accepted',
            'ETEMPLATE_CLAIM_REJECTED' => 'mz_listing_claim_rejected',
  
            // some reservation keys
            'T' => array (
                'txt_sample_single' => '_mz_listing_txt_sample_single',
            	'txt_sample_single_with_article' => '_mz_listing_txt_sample_single_with_article',
            	'txt_sample_comment_single' => '_mz_listing_txt_sample_comment_single',
            	'txt_sample_vote_single' => '_mz_listing_txt_sample_vote_single',
                'txt_sample_reaction_single' => '_mz_listing_txt_sample_reaction_single',
                'txt_sample_score_up_single' => '_mz_listing_txt_sample_score_up_single',
                'txt_sample_score_down_single' => '_mz_listing_txt_sample_score_down_single',
                'form_field_author' => '_mz_listing_form_entry_input_author',
            	'grid_action_err_delete' => '_mz_listing_grid_action_err_delete',
            	'grid_action_err_claim' => '_mz_listing_grid_action_claim_delete',
            	'grid_txt_account_manager' => '_mz_listing_grid_txt_account_manager',
                'filter_item_active' => '_mz_listing_grid_filter_item_title_adm_active',
            	'filter_item_hidden' => '_mz_listing_grid_filter_item_title_adm_hidden',
            	'filter_item_select_one_filter1' => '_mz_listing_grid_filter_item_title_adm_select_one_filter1',
            	'menu_item_manage_my' => '_mz_listing_menu_item_title_manage_my',
            	'menu_item_manage_all' => '_mz_listing_menu_item_title_manage_all',
                'txt_all_entries_by' => '_mz_listing_txt_all_entries_by',
                'txt_all_entries_in' => '_mz_listing_txt_all_entries_in',
                'txt_all_entries_by_author' => '_mz_listing_page_title_browse_by_author',
                'txt_all_entries_by_context' => '_mz_listing_page_title_browse_by_context',
                'txt_err_cannot_perform_action' => '_mz_listing_txt_err_cannot_perform_action',
                'txt_poll_form_answers_add' => '_mz_listing_form_poll_input_answers_add',
                'txt_poll_menu_view_answers' => '_mz_listing_txt_poll_view_answers',
                'txt_poll_menu_view_results' => '_mz_listing_txt_poll_view_results',
                'txt_poll_answer_vote_do_by' => '_mz_listing_txt_poll_answer_vote_do_by',
                'txt_poll_answer_vote_counter' => '_mz_listing_txt_poll_answer_vote_counter',
                'txt_poll_answer_vote_percent' => '_mz_listing_txt_poll_answer_vote_percent',
                'txt_display_add' => '_mz_listing_txt_display_title_add',
                'txt_display_edit' => '_mz_listing_txt_display_title_edit',
                'txt_display_view' => '_mz_listing_txt_display_title_view',
                'filter_item_pending' => '_mz_listing_grid_filter_item_title_adm_pending', 
                'filter_item_select_one_filter2' => '_mz_listing_grid_filter_item_title_adm_select_one_filter2',  
				/*BEGIN UNA 13*/
                'txt_sample_interest_single' => '_mz_listing_txt_sample_interest_single',
				/*END UNA 13*/ 	
            ),
        ));
        
        $this->_aJsClasses = array_merge($this->_aJsClasses, array(
			'manage_tools' => 'MzListingManageTools',
            'studio' => 'MzListingStudio',
            'entry' => 'MzListingEntry',
        ));

        $this->_aJsObjects = array_merge($this->_aJsObjects, array(
            'manage_tools' => 'oMzListingManageTools',
            'studio' => 'oMzListingStudio',
            'entry' => 'oMzListingEntry',
        ));

        $this->_aGridObjects = array(
            'categories' => $this->CNF['OBJECT_GRID_CATEGORIES'],
            'claim' => $this->CNF['OBJECT_GRID_CLAIM'],
            'claim_accept' => $this->CNF['OBJECT_GRID_CLAIM_ACCEPT'],
            'claim_reject' => $this->CNF['OBJECT_GRID_CLAIM_REJECT'],  
            'common' => $this->CNF['OBJECT_GRID_COMMON'],
            'administration' => $this->CNF['OBJECT_GRID_ADMINISTRATION'],
        );
  
        $this->_bAttachmentsInTimeline = true;
 
    }
  

}

/** @} */
