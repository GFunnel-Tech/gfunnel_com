<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Goal Goal
 * @ingroup     ModzzzModules
 *
 * @{
 */

bx_import('BxDolInformer');

class MzGoalConfig extends BxBaseModTextConfig
{

	/*BEGIN UNA 13*/
    protected $_oDb; 
    protected $_aCurrency;
	/*END UNA 13*/
 
    public function __construct($aModule)
    {
        parent::__construct($aModule);

        $aMenuItems2Methods = array (
            'approve' => 'checkAllowedApprove',
            'edit-goal' => 'checkAllowedEdit',
            'delete-goal' => 'checkAllowedDelete',
        );

        $this->CNF = array_merge($this->CNF, array (
 
 			/*[begin] UNA 13 RC */
			'FIELD_THUMB_POSITION' => 'thumb_data',
			'OBJECT_IMAGES_TRANSCODER_MINIATURE' => 'mz_goal_miniature',
			'OBJECT_IMAGES_TRANSCODER_MINIATURE_PHOTOS' => 'mz_goal_miniature_photos',
			/*[end] UNA 13 RC */

			/*BEGIN UNA 13*/
            'PARAM_USE_IIN' => 'mz_goal_internal_interested_notification',  
            'FIELD_CF' => 'cf',
			/*END UNA 13*/

			'OBJECT_CONTENT_INFO' => 'mz_goal',
            'OBJECT_CMTS_CONTENT_INFO' => 'mz_goal_cmts', 
            'PARAM_AUTO_APPROVE' => 'mz_goal_enable_auto_approve', 
            'OBJECT_IMAGES_TRANSCODER_VIEW_PHOTOS' => 'mz_goal_view_photos',


			/* begin broadcast*/
            'OBJECT_FORM_BROADCAST' => 'mz_goal_broadcast',
            'OBJECT_FORM_BROADCAST_DISPLAY_ADD' => 'mz_goal_broadcast_add',
            'OBJECT_MENU_ACTIONS_VIEW_BROADCAST' => 'mz_goal_view_broadcast', // actions menu on view entry page
            'OBJECT_MENU_SUBMENU_VIEW_BROADCAST' => 'mz_goal_view_broadcast_submenu', // broadcast submenu     

            'URI_CREATE_BROADCAST' => 'create-goal-broadcast',
 
            'ETEMPLATE_BROADCAST' => 'mz_goal_broadcast', 
			/* end broadcast*/
 
			/* begin task*/
            'TABLE_TASK' => $aModule['db_prefix'] . 'tasks',
            'FIELD_TASK' => 'tasks',
            'OBJECT_COMMENTS_TASK' => 'mz_goal_tasks',
            'OBJECT_FORM_TASK' => 'mz_goal_task',
            'OBJECT_FORM_TASK_DISPLAY_VIEW' => 'mz_goal_task_view',
            'OBJECT_FORM_TASK_DISPLAY_ADD' => 'mz_goal_task_add',
            'OBJECT_FORM_TASK_DISPLAY_EDIT' => 'mz_goal_task_edit',
            'OBJECT_FORM_TASK_DISPLAY_DELETE' => 'mz_goal_task_delete',
            'OBJECT_MENU_ACTIONS_VIEW_TASK' => 'mz_goal_view_task', // actions menu on view entry page
            'OBJECT_MENU_SUBMENU_VIEW_TASK' => 'mz_goal_view_task_submenu', // task submenu     
 
            'URI_VIEW_TASKS' => 'view-goal-tasks',
            'URI_VIEW_TASK' => 'view-goal-task',
            'URI_CREATE_TASK' => 'create-goal-task',
            'URI_EDIT_TASK' => 'edit-goal-task',
 
            'PARAM_CHARS_TITLE_TASK' => 'mz_goal_title_chars_task',
            'PARAM_CHARS_SUMMARY_PLAIN_TASK' => 'mz_goal_plain_summary_chars_task',
            'PARAM_DELETE_TASK' => 'mz_goal_delete_task',
            'FIELD_TASK_TITLE' => 'title',
            'FIELD_TASK_TEXT' => 'text', 
            'FIELD_URI' => 'uri',  
			/* end task*/
   
			/* begin news*/
            'TABLE_NEWS' => $aModule['db_prefix'] . 'news',
            'FIELD_NEWS' => 'news',
            'OBJECT_COMMENTS_NEWS' => 'mz_goal_news',
            'OBJECT_FORM_NEWS' => 'mz_goal_news',
            'OBJECT_FORM_NEWS_DISPLAY_VIEW' => 'mz_goal_news_view',
            'OBJECT_FORM_NEWS_DISPLAY_ADD' => 'mz_goal_news_add',
            'OBJECT_FORM_NEWS_DISPLAY_EDIT' => 'mz_goal_news_edit',
            'OBJECT_FORM_NEWS_DISPLAY_DELETE' => 'mz_goal_news_delete',
            'OBJECT_MENU_ACTIONS_VIEW_NEWS' => 'mz_goal_view_news', // actions menu on view entry page
            'OBJECT_MENU_SUBMENU_VIEW_NEWS' => 'mz_goal_view_news_submenu', // news submenu     
 
            'URI_VIEW_NEWS' => 'view-goal-news',
            'URI_VIEW_NEWS_ITEM' => 'view-goal-news-item',
            'URI_CREATE_NEWS' => 'create-goal-news',
            'URI_EDIT_NEWS' => 'edit-goal-news',
 
            'PARAM_CHARS_TITLE_NEWS' => 'mz_goal_title_chars_news',
            'PARAM_CHARS_SUMMARY_PLAIN_NEWS' => 'mz_goal_plain_summary_chars_news',
            'PARAM_DELETE_NEWS' => 'mz_goal_delete_news',
            'FIELD_NEWS_TITLE' => 'title',
            'FIELD_NEWS_TEXT' => 'text', 
            'FIELD_URI' => 'uri',  
			/* end news*/

			/* begin sponsor*/
            'TABLE_SPONSORS' => $aModule['db_prefix'] . 'sponsors',
            'FIELD_SPONSORS' => 'sponsors',
            'OBJECT_COMMENTS_SPONSORS' => 'mz_goal_sponsors',
            'OBJECT_FORM_SPONSOR' => 'mz_goal_sponsor',
            'OBJECT_FORM_SPONSOR_DISPLAY_VIEW' => 'mz_goal_sponsor_view',
            'OBJECT_FORM_SPONSOR_DISPLAY_ADD' => 'mz_goal_sponsor_add',
            'OBJECT_FORM_SPONSOR_DISPLAY_EDIT' => 'mz_goal_sponsor_edit',
            'OBJECT_FORM_SPONSOR_DISPLAY_DELETE' => 'mz_goal_sponsor_delete',
            'OBJECT_MENU_ACTIONS_VIEW_SPONSOR' => 'mz_goal_view_sponsor', // actions menu on view entry page
            'OBJECT_MENU_SUBMENU_VIEW_SPONSOR' => 'mz_goal_view_sponsor_submenu', // sponsor submenu     
 
            'URI_VIEW_SPONSORS' => 'view-goal-sponsors',
            'URI_VIEW_SPONSOR' => 'view-goal-sponsor',
            'URI_CREATE_SPONSOR' => 'create-goal-sponsor',
            'URI_EDIT_SPONSOR' => 'edit-goal-sponsor',
 
            'PARAM_CHARS_TITLE_SPONSOR' => 'mz_goal_title_chars_sponsor',
            'PARAM_CHARS_SUMMARY_PLAIN_SPONSOR' => 'mz_goal_plain_summary_chars_sponsor',
            'PARAM_DELETE_SPONSOR' => 'mz_goal_delete_sponsor',
            'FIELD_SPONSOR_TITLE' => 'title',
            'FIELD_SPONSOR_TEXT' => 'text', 
            'FIELD_URI' => 'uri',  

			'OBJECT_METATAGS_SPONSOR' => 'mz_goal_sponsor',

			/* end sponsor*/
 
            'FIELD_GOAL_ID' => 'entry_id', 
			'GET_PARAM_GOAL' => 'goal',  
            'OBJECT_PRIVACY_JOIN' => 'mz_goal_allow_join_to',
 
            'PARAM_PER_PAGE_FOR_FAVORITES_LISTS' => 'mz_goal_per_page_for_favorites_lists',
            'URI_FAVORITES_LIST' => 'goal-favorites',
			'OBJECT_NOTES' => 'mz_goal_notes',
			'OBJECT_PRIVACY_LIST_VIEW' => 'mz_goal_allow_view_favorite_list',


            // module icon
            'ICON' => 'arrow-circle-up col-green2',

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
            'FIELD_TEXT_ID' => 'goal-text',
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

            // page URIs
            'URI_VIEW_ENTRY' => 'view-goal',
            'URI_AUTHOR_ENTRIES' => 'goal-author',
            'URI_ENTRIES_BY_CONTEXT' => 'goal-context',
            'URI_ADD_ENTRY' => 'create-goal',
            'URI_EDIT_ENTRY' => 'edit-goal',
            'URI_MANAGE_COMMON' => 'goal-manage',

            'URL_HOME' => 'page.php?i=goal-home',
            'URL_POPULAR' => 'page.php?i=goal-popular',
            'URL_LOCAL' => 'page.php?i=goal-local',  
            'URL_FEATURED' => 'page.php?i=goal-featured',
            'URL_COMPLETED' => 'page.php?i=goal-completed',
            'URL_ACHIEVED' => 'page.php?i=goal-achieved',
            'URL_UPDATED' => 'page.php?i=goal-updated',
            'URL_CATEGORIES' => 'page.php?i=goal-categories',
            'URL_SUBCATEGORIES' => 'page.php?i=goal-categories',
				
            'URL_MANAGE_COMMON' => 'page.php?i=goal-manage',
            'URL_MANAGE_ADMINISTRATION' => 'page.php?i=goal-administration',

            'GET_PARAM_CATEGORY' => 'category',
            'GET_PARAM_SUBCATEGORY' => 'subcategory',

			'CATEGORY_URL_TYPE' => array (
					'mz_goal_today' => 'URL_TODAY', 
			 ),

			'CATEGORY_PARAM_TYPE' => array (
					'mz_goal_today' => 'GET_PARAM_TODAY', 
			 ),

            'URL_TODAY' => 'page.php?i=goal-today',
            'GET_PARAM_TODAY' => 'today',
 
            // some params
            'PARAM_CHARS_SUMMARY' => 'mz_goal_summary_chars',
            'PARAM_CHARS_SUMMARY_PLAIN' => 'mz_goal_plain_summary_chars',
            'PARAM_NUM_RSS' => 'mz_goal_rss_num',
            'PARAM_SEARCHABLE_FIELDS' => 'mz_goal_searchable_fields',
            'PARAM_PER_PAGE_BROWSE_SHOWCASE' => 'mz_goal_per_page_browse_showcase',
            'PARAM_CATEGORY_LEVEL_MAX' => 1,

            // objects
            'OBJECT_STORAGE' => 'mz_goal_covers',
            'OBJECT_STORAGE_FILES' => 'mz_goal_files',
            'OBJECT_STORAGE_PHOTOS' => 'mz_goal_photos',
            'OBJECT_STORAGE_VIDEOS' => 'mz_goal_videos',
            'OBJECT_IMAGES_TRANSCODER_PREVIEW' => 'mz_goal_preview',
            'OBJECT_IMAGES_TRANSCODER_GALLERY' => 'mz_goal_gallery',
            'OBJECT_IMAGES_TRANSCODER_COVER' => 'mz_goal_cover',
            'OBJECT_IMAGES_TRANSCODER_PREVIEW_FILES' => 'mz_goal_preview_files',
            'OBJECT_IMAGES_TRANSCODER_GALLERY_FILES' => 'mz_goal_gallery_files',
            'OBJECT_IMAGES_TRANSCODER_PREVIEW_PHOTOS' => 'mz_goal_preview_photos',
            'OBJECT_IMAGES_TRANSCODER_GALLERY_PHOTOS' => 'mz_goal_gallery_photos',
            'OBJECT_VIDEOS_TRANSCODERS' => array(
                'poster' => 'mz_goal_videos_poster', 
            	'poster_preview' => 'mz_goal_videos_poster_preview',
            	'mp4' => 'mz_goal_videos_mp4', 
            	'mp4_hd' => 'mz_goal_videos_mp4_hd'
            ),
            'OBJECT_VIDEO_TRANSCODER_HEIGHT' => '480px',
            'OBJECT_UPLOADERS' => array('mz_goal_html5'), 

            'OBJECT_REPORTS' => 'mz_goal',
            'OBJECT_VIEWS' => 'mz_goal',
            'OBJECT_VOTES' => 'mz_goal',
            'OBJECT_REACTIONS' => 'mz_goal_reactions',
            'OBJECT_SCORES' => 'mz_goal',
            'OBJECT_FAVORITES' => 'mz_goal',
            'OBJECT_FEATURED' => 'mz_goal',
            'OBJECT_METATAGS' => 'mz_goal',
            'OBJECT_COMMENTS' => 'mz_goal',
            'OBJECT_REVIEWS' => 'mz_goal_reviews',
            'OBJECT_CATEGORY' => '',
            'OBJECT_PRIVACY_VIEW' => 'mz_goal_allow_view_to',
            'OBJECT_FORM_CATEGORY' => 'mz_goal_category',
            'OBJECT_FORM_CATEGORY_DISPLAY_ADD' => 'mz_goal_category_add',
            'OBJECT_FORM_CATEGORY_DISPLAY_EDIT' => 'mz_goal_category_edit',
            'OBJECT_FORM_CATEGORY_DISPLAY_DELETE' => 'mz_goal_category_delete',
            'OBJECT_FORM_ENTRY' => 'mz_goal',
            'OBJECT_FORM_ENTRY_DISPLAY_VIEW' => 'mz_goal_entry_view',
 
            'OBJECT_FORM_ENTRY_DISPLAY_ADD' => 'mz_goal_entry_add',
            'OBJECT_FORM_ENTRY_DISPLAY_EDIT' => 'mz_goal_entry_edit',
            'OBJECT_FORM_ENTRY_DISPLAY_DELETE' => 'mz_goal_entry_delete',
            'OBJECT_FORM_POLL' => 'mz_goal_poll',
            'OBJECT_FORM_POLL_DISPLAY_ADD' => 'mz_goal_poll_add',
            'OBJECT_MENU_ENTRY_ATTACHMENTS' => 'mz_goal_entry_attachments', // attachments menu in create/edit forms
            'OBJECT_MENU_ACTIONS_VIEW_ENTRY' => 'mz_goal_view', // actions menu on view entry page
            'OBJECT_MENU_ACTIONS_VIEW_ENTRY_ALL' => 'mz_goal_view_actions', // all actions menu on view entry page
            'OBJECT_MENU_ACTIONS_MY_ENTRIES' => 'mz_goal_my', // actions menu on my entries page
            'OBJECT_MENU_SUBMENU' => 'mz_goal_submenu', // main module submenu
            'OBJECT_MENU_SUBMENU_VIEW_ENTRY' => 'mz_goal_view_submenu', // view entry submenu
            'OBJECT_MENU_SUBMENU_VIEW_ENTRY_MAIN_SELECTION' => 'goal-home', // first item in view entry submenu from main module submenu
            'OBJECT_MENU_SNIPPET_META' => 'mz_goal_snippet_meta', // menu for snippet meta info
            'OBJECT_MENU_MANAGE_TOOLS' => 'mz_goal_menu_manage_tools', //manage menu in content administration tools
            'OBJECT_GRID_CATEGORIES' => 'mz_goal_categories',
            'OBJECT_GRID_ADMINISTRATION' => 'mz_goal_administration',
            'OBJECT_GRID_COMMON' => 'mz_goal_common',
            //'OBJECT_UPLOADERS' => array('mz_goal_simple', 'mz_goal_html5'),

            // menu items which visibility depends on custom visibility checking
            'MENU_ITEM_TO_METHOD' => array (
                'mz_goal_my' => array (
                    'create-goal' => 'checkAllowedAdd',
                ),
                'mz_goal_view' => $aMenuItems2Methods,
            ),

            // informer messages
            'INFORMERS' => array (
                'approving' => array (
                    'name' => 'mz-goal-approving',
                    'map' => array (
                        'pending' => array('msg' => '_mz_goal_txt_msg_status_pending', 'type' => BX_INFORMER_ALERT),
                        'hidden' => array('msg' => '_mz_goal_txt_msg_status_hidden', 'type' => BX_INFORMER_ERROR),
                    ),
                ),  
                'processing' => array (
                    'name' => 'mz-goal-processing',
                    'map' => array (
                        'awaiting' => array('msg' => '_mz_goal_txt_processing_awaiting', 'type' => BX_INFORMER_ALERT),
                        'failed' => array('msg' => '_mz_goal_txt_processing_failed', 'type' => BX_INFORMER_ERROR)
                    ),
                 ),
                'scheduled' => array (
                    'name' => 'mz-goal-scheduled',
                    'map' => array (
                        'awaiting' => array('msg' => '_mz_goal_txt_scheduled_awaiting', 'type' => BX_INFORMER_ALERT),
                    ),
                ),
            ),

            // email templates
            'ETEMPLATE_INTERESTED' => 'mz_goal_interested',
            'ETEMPLATE_PRE_REMINDER' => 'mz_goal_pre_reminder',
            'ETEMPLATE_POST_REMINDER' => 'mz_goal_post_reminder',
  
            // some reservation keys
            'T' => array (
                'txt_sample_single' => '_mz_goal_txt_sample_single',
            	'txt_sample_single_with_article' => '_mz_goal_txt_sample_single_with_article',
            	'txt_sample_comment_single' => '_mz_goal_txt_sample_comment_single',
            	'txt_sample_vote_single' => '_mz_goal_txt_sample_vote_single',
                'txt_sample_reaction_single' => '_mz_goal_txt_sample_reaction_single',
                'txt_sample_score_up_single' => '_mz_goal_txt_sample_score_up_single',
                'txt_sample_score_down_single' => '_mz_goal_txt_sample_score_down_single',
                'form_field_author' => '_mz_goal_form_entry_input_author',
            	'grid_action_err_delete' => '_mz_goal_grid_action_err_delete',
            	'grid_txt_account_manager' => '_mz_goal_grid_txt_account_manager',
 
                'txt_status_completed' => '_mz_goal_txt_status_completed',
                'txt_status_achieved' => '_mz_goal_txt_status_achieved',
 
                'filter_item_completed' => '_mz_goal_grid_filter_item_title_adm_completed',
                'filter_item_achieved' => '_mz_goal_grid_filter_item_title_adm_achieved', 
                'filter_item_active' => '_mz_goal_grid_filter_item_title_adm_active',
            	'filter_item_hidden' => '_mz_goal_grid_filter_item_title_adm_hidden',
            	'filter_item_select_one_filter1' => '_mz_goal_grid_filter_item_title_adm_select_one_filter1',
            	'menu_item_manage_my' => '_mz_goal_menu_item_title_manage_my',
            	'menu_item_manage_all' => '_mz_goal_menu_item_title_manage_all',
                'txt_all_entries_by' => '_mz_goal_txt_all_entries_by',
                'txt_all_entries_in' => '_mz_goal_txt_all_entries_in',
                'txt_all_entries_by_author' => '_mz_goal_page_title_browse_by_author',
                'txt_all_entries_by_context' => '_mz_goal_page_title_browse_by_context',
                'txt_err_cannot_perform_action' => '_mz_goal_txt_err_cannot_perform_action',
                'txt_poll_form_answers_add' => '_mz_goal_form_poll_input_answers_add',
                'txt_poll_menu_view_answers' => '_mz_goal_txt_poll_view_answers',
                'txt_poll_menu_view_results' => '_mz_goal_txt_poll_view_results',
                'txt_poll_answer_vote_do_by' => '_mz_goal_txt_poll_answer_vote_do_by',
                'txt_poll_answer_vote_counter' => '_mz_goal_txt_poll_answer_vote_counter',
                'txt_poll_answer_vote_percent' => '_mz_goal_txt_poll_answer_vote_percent',
                'txt_display_add' => '_mz_goal_txt_display_title_add',
                'txt_display_edit' => '_mz_goal_txt_display_title_edit',
                'txt_display_view' => '_mz_goal_txt_display_title_view',
                'filter_item_pending' => '_mz_goal_grid_filter_item_title_adm_pending', 
                'filter_item_select_one_filter2' => '_mz_goal_grid_filter_item_title_adm_select_one_filter2',  
				/*BEGIN UNA 13*/
                'txt_sample_interest_single' => '_mz_goal_txt_sample_interest_single',
				/*END UNA 13*/ 	  
            ),
        ));
        
        $this->_aJsClasses = array_merge($this->_aJsClasses, array(
			'manage_tools' => 'MzGoalManageTools',
            'studio' => 'MzGoalStudio',
            'entry' => 'MzGoalEntry'
        ));

        $this->_aJsObjects = array_merge($this->_aJsObjects, array(
            'manage_tools' => 'oMzGoalManageTools',
            'studio' => 'oMzGoalStudio',
            'entry' => 'oMzGoalEntry'
        ));

        $this->_aGridObjects = array(
            'categories' => $this->CNF['OBJECT_GRID_CATEGORIES'],
            'common' => $this->CNF['OBJECT_GRID_COMMON'],
            'administration' => $this->CNF['OBJECT_GRID_ADMINISTRATION'],
        );
  
        $this->_bAttachmentsInTimeline = true;
    }

	/*BEGIN UNA 13*/ 
    public function init(&$oDb)
    {
        $this->_oDb = &$oDb; 
    } 
	/*END UNA 13*/
 
}

/** @} */
