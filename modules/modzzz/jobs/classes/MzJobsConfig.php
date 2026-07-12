<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Jobs Jobs
 * @ingroup     ModzzzModules
 *
 * @{
 */

bx_import('BxDolInformer');

class MzJobsConfig extends BxBaseModTextConfig
{

	/*BEGIN UNA 13*/
    protected $_oDb; 
    protected $_aCurrency;
	/*END UNA 13*/
 

    function __construct($aModule)
    {
        parent::__construct($aModule);

        $aMenuItems2Methods = array (
            'approve' => 'checkAllowedApprove', 
            'edit-job' => 'checkAllowedEdit',
            'delete-job' => 'checkAllowedDelete',
        );

        $this->CNF = array_merge($this->CNF, array (
 
 			/*[begin] UNA 13 RC */
			'FIELD_THUMB_POSITION' => 'thumb_data',
			'OBJECT_IMAGES_TRANSCODER_MINIATURE' => 'mz_jobs_miniature',
			'OBJECT_IMAGES_TRANSCODER_MINIATURE_PHOTOS' => 'mz_jobs_miniature_photos',
			/*[end] UNA 13 RC */

			/*BEGIN UNA 13*/
            'OBJECT_NOTES' => 'mz_jobs_notes',
            'PARAM_USE_IIN' => 'mz_jobs_internal_interested_notification',  
            'FIELD_CF' => 'cf',
			/*END UNA 13*/
 
			'OBJECT_CONTENT_INFO' => 'mz_jobs',
            'OBJECT_CMTS_CONTENT_INFO' => 'mz_jobs_cmts', 
            'PARAM_AUTO_APPROVE' => 'mz_jobs_enable_auto_approve', 
            'OBJECT_IMAGES_TRANSCODER_VIEW_PHOTOS' => 'mz_jobs_view_photos',
 
            // module icon
            'ICON' => 'handshake col-green2',

            // database tables
            'TABLE_ENTRIES' => $aModule['db_prefix'] . 'entries',
            'TABLE_ENTRIES_FULLTEXT' => 'title_text',
            'TABLE_CATEGORIES' => $aModule['db_prefix'] . 'categories',
            'TABLE_CATEGORIES_TYPES' => $aModule['db_prefix'] . 'categories_types',
            'TABLE_INTERESTED_TRACK' => $aModule['db_prefix'] . 'interested_track',

            // database fields
            'FIELD_ID' => 'id',
            'FIELD_AUTHOR' => 'author',
            'FIELD_ADDED' => 'added',
            'FIELD_CHANGED' => 'changed',
            'FIELD_TITLE' => 'title',
            'FIELD_TEXT' => 'text',
            'FIELD_TEXT_ID' => 'job-text',
            'FIELD_COMPANY_URI' => 'company_uri', 
            'FIELD_CATEGORY' => 'category',
            'FIELD_CATEGORY_VIEW' => 'category_view',
            'FIELD_CATEGORY_SELECT' => 'category_select', 
            'FIELD_VACANCIES' => 'vacancies', 
            'FIELD_ALLOW_MESSAGE' => 'allow_message', 
            'FIELD_DEADLINE' => 'deadline', 
            'FIELD_APPLICATION_LINK' => 'application_link', 
            'FIELD_SKILLS' => 'skills', 
            'FIELD_RESPONSIBILITIES' => 'responsibilities', 
            'FIELD_SALARY' => 'salary', 
            'FIELD_SALARY_NEGOTIABLE' => 'salary_negotiable', 
            'FIELD_MAX_SALARY' => 'max_salary', 
            'FIELD_MIN_SALARY' => 'min_salary', 
            'FIELD_SALARY_TYPE' => 'salary_type',   
            'FIELD_CONTACT_EMAIL' => 'email',  
            'FIELD_CONTACT_COMPANY' => 'company',  
            'FIELD_CONTACT_PHONE' => 'phone',  
            'FIELD_CONTACT_MOBILE' => 'mobile',  
            'FIELD_CONTACT_WEBSITE' => 'website', 
			'FIELD_WHATSAPP' => 'whatsapp',   
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
            'URI_VIEW_ENTRY' => 'view-job',
            'URI_AUTHOR_ENTRIES' => 'job-author',
            'URI_ENTRIES_BY_CONTEXT' => 'job-context',
            'URI_ADD_ENTRY' => 'create-job',
            'URI_EDIT_ENTRY' => 'edit-job',
            'URI_MANAGE_COMMON' => 'jobs-manage',

            'URL_HOME' => 'page.php?i=jobs-home',
            'URL_WANTED' => 'page.php?i=jobs-wanted',
            'URL_REMOTE' => 'page.php?i=jobs-remote', 
            'URL_JOB_TYPE' => 'page.php?i=job-type',  
            'URL_LOCAL' => 'page.php?i=jobs-local',  
            'URL_COMPANY' => 'page.php?i=jobs-company',  
            'URL_COMPANIES' => 'page.php?i=jobs-companies',  
            'URL_POPULAR' => 'page.php?i=jobs-popular',
            'URL_UPDATED' => 'page.php?i=jobs-updated',
            'URL_CATEGORIES' => 'page.php?i=jobs-categories',
            'URL_MANAGE_COMMON' => 'page.php?i=jobs-manage',
            'URL_MANAGE_ADMINISTRATION' => 'page.php?i=jobs-administration',

            'GET_PARAM_CATEGORY' => 'category',
            'GET_PARAM_COMPANY' => 'company',
            'GET_PARAM_JOB_TYPE' => 'type',
   
            // some params
            'PARAM_CHARS_SUMMARY' => 'mz_jobs_summary_chars',
            'PARAM_CHARS_SUMMARY_PLAIN' => 'mz_jobs_plain_summary_chars',
            'PARAM_NUM_RSS' => 'mz_jobs_rss_num',
            'PARAM_SEARCHABLE_FIELDS' => 'mz_jobs_searchable_fields',
            'PARAM_PER_PAGE_BROWSE_SHOWCASE' => 'mz_jobs_per_page_browse_showcase',
            'PARAM_LIFETIME' => 'mz_jobs_lifetime',
            'PARAM_CATEGORY_LEVEL_MAX' => 1,

            // objects
            'OBJECT_STORAGE' => 'mz_jobs_covers',
            'OBJECT_STORAGE_FILES' => 'mz_jobs_files',
            'OBJECT_STORAGE_PHOTOS' => 'mz_jobs_photos',
            'OBJECT_STORAGE_VIDEOS' => 'mz_jobs_videos',
            'OBJECT_IMAGES_TRANSCODER_PREVIEW' => 'mz_jobs_preview',
            'OBJECT_IMAGES_TRANSCODER_GALLERY' => 'mz_jobs_gallery',
            'OBJECT_IMAGES_TRANSCODER_COVER' => 'mz_jobs_cover',
            'OBJECT_IMAGES_TRANSCODER_PREVIEW_FILES' => 'mz_jobs_preview_files',
            'OBJECT_IMAGES_TRANSCODER_GALLERY_FILES' => 'mz_jobs_gallery_files',
            'OBJECT_IMAGES_TRANSCODER_PREVIEW_PHOTOS' => 'mz_jobs_preview_photos',
            'OBJECT_IMAGES_TRANSCODER_GALLERY_PHOTOS' => 'mz_jobs_gallery_photos',
            'OBJECT_VIDEOS_TRANSCODERS' => array(
                'poster' => 'mz_jobs_videos_poster', 
            	'poster_preview' => 'mz_jobs_videos_poster_preview',
            	'mp4' => 'mz_jobs_videos_mp4', 
            	'mp4_hd' => 'mz_jobs_videos_mp4_hd'
            ),
            'OBJECT_VIDEO_TRANSCODER_HEIGHT' => '480px',
            'OBJECT_REPORTS' => 'mz_jobs',
            'OBJECT_VIEWS' => 'mz_jobs',
            'OBJECT_VOTES' => 'mz_jobs',
            'OBJECT_REACTIONS' => 'mz_jobs_reactions',
            'OBJECT_SCORES' => 'mz_jobs',
            'OBJECT_FAVORITES' => 'mz_jobs',
            'OBJECT_FEATURED' => 'mz_jobs',
            'OBJECT_METATAGS' => 'mz_jobs',
            'OBJECT_COMMENTS' => 'mz_jobs',
            'OBJECT_REVIEWS' => 'mz_jobs_reviews',
            'OBJECT_CATEGORY' => '',
            'OBJECT_PRIVACY_VIEW' => 'mz_jobs_allow_view_to',
            'OBJECT_FORM_CATEGORY' => 'mz_jobs_category',
            'OBJECT_FORM_CATEGORY_DISPLAY_ADD' => 'mz_jobs_category_add',
            'OBJECT_FORM_CATEGORY_DISPLAY_EDIT' => 'mz_jobs_category_edit',
            'OBJECT_FORM_CATEGORY_DISPLAY_DELETE' => 'mz_jobs_category_delete',
            'OBJECT_FORM_ENTRY' => 'mz_jobs',
            'OBJECT_FORM_ENTRY_DISPLAY_VIEW' => '',
            'OBJECT_FORM_ENTRY_DISPLAY_ADD' => 'mz_jobs_entry_add',
            'OBJECT_FORM_ENTRY_DISPLAY_EDIT' => '',
            'OBJECT_FORM_ENTRY_DISPLAY_DELETE' => 'mz_jobs_entry_delete',
            'OBJECT_FORM_POLL' => 'mz_jobs_poll',
            'OBJECT_FORM_POLL_DISPLAY_ADD' => 'mz_jobs_poll_add',
            'OBJECT_MENU_ENTRY_ATTACHMENTS' => 'mz_jobs_entry_attachments', // attachments menu in create/edit forms
            'OBJECT_MENU_ACTIONS_VIEW_ENTRY' => 'mz_jobs_view', // actions menu on view entry page
            'OBJECT_MENU_ACTIONS_VIEW_ENTRY_ALL' => 'mz_jobs_view_actions', // all actions menu on view entry page
            'OBJECT_MENU_ACTIONS_MY_ENTRIES' => 'mz_jobs_my', // actions menu on my entries page
            'OBJECT_MENU_SUBMENU' => 'mz_jobs_submenu', // main module submenu
            'OBJECT_MENU_SUBMENU_VIEW_ENTRY' => 'mz_jobs_view_submenu', // view entry submenu
            'OBJECT_MENU_SUBMENU_VIEW_ENTRY_MAIN_SELECTION' => 'jobs-home', // first item in view entry submenu from main module submenu
            'OBJECT_MENU_SNIPPET_META' => 'mz_jobs_snippet_meta', // menu for snippet meta info
            'OBJECT_MENU_MANAGE_TOOLS' => 'mz_jobs_menu_manage_tools', //manage menu in content administration tools
            'OBJECT_GRID_CATEGORIES' => 'mz_jobs_categories',
            'OBJECT_GRID_ADMINISTRATION' => 'mz_jobs_administration',
            'OBJECT_GRID_COMMON' => 'mz_jobs_common',
            'OBJECT_UPLOADERS' => array('mz_jobs_html5'),

            // menu items which visibility depends on custom visibility checking
            'MENU_ITEM_TO_METHOD' => array (
                'mz_jobs_my' => array (
                    'create-job' => 'checkAllowedAdd',
                ),
                'mz_jobs_view' => $aMenuItems2Methods,
            ),

            // informer messages
            'INFORMERS' => array (
                'approving' => array (
                    'name' => 'mz-jobs-approving',
                    'map' => array (
                        'pending' => array('msg' => '_mz_jobs_txt_msg_status_pending', 'type' => BX_INFORMER_ALERT),
                        'hidden' => array('msg' => '_mz_jobs_txt_msg_status_hidden', 'type' => BX_INFORMER_ERROR),
                    ),
                ), 
                'processing' => array (
                    'name' => 'mz-jobs-processing',
                    'map' => array (
                        'awaiting' => array('msg' => '_mz_jobs_txt_processing_awaiting', 'type' => BX_INFORMER_ALERT),
                        'failed' => array('msg' => '_mz_jobs_txt_processing_failed', 'type' => BX_INFORMER_ERROR)
                    ),
                ),
                'scheduled' => array (
                    'name' => 'mz-jobs-scheduled',
                    'map' => array (
                        'awaiting' => array('msg' => '_mz_jobs_txt_scheduled_awaiting', 'type' => BX_INFORMER_ALERT),
                    ),
                ),
            ),

            // email templates
            'ETEMPLATE_INTERESTED' => 'mz_jobs_interested',

            // some language keys
            'T' => array (
                'txt_sample_single' => '_mz_jobs_txt_sample_single',
            	'txt_sample_single_with_article' => '_mz_jobs_txt_sample_single_with_article',
            	'txt_sample_comment_single' => '_mz_jobs_txt_sample_comment_single',
            	'txt_sample_vote_single' => '_mz_jobs_txt_sample_vote_single',
                'txt_sample_reaction_single' => '_mz_jobs_txt_sample_reaction_single',
                'txt_sample_score_up_single' => '_mz_jobs_txt_sample_score_up_single',
                'txt_sample_score_down_single' => '_mz_jobs_txt_sample_score_down_single',
                'form_field_author' => '_mz_jobs_form_entry_input_author',
            	'grid_action_err_delete' => '_mz_jobs_grid_action_err_delete',
            	'grid_txt_account_manager' => '_mz_jobs_grid_txt_account_manager',
                'filter_item_active' => '_mz_jobs_grid_filter_item_title_adm_active',
            	'filter_item_hidden' => '_mz_jobs_grid_filter_item_title_adm_hidden',
            	'filter_item_select_one_filter1' => '_mz_jobs_grid_filter_item_title_adm_select_one_filter1',
            	'menu_item_manage_my' => '_mz_jobs_menu_item_title_manage_my',
            	'menu_item_manage_all' => '_mz_jobs_menu_item_title_manage_all',
                'txt_all_entries_by' => '_mz_jobs_txt_all_entries_by',
                'txt_all_entries_in' => '_mz_jobs_txt_all_entries_in',
                'txt_all_entries_by_author' => '_mz_jobs_page_title_browse_by_author',
                'txt_all_entries_by_context' => '_mz_jobs_page_title_browse_by_context',
                'txt_err_cannot_perform_action' => '_mz_jobs_txt_err_cannot_perform_action',
                'txt_poll_form_answers_add' => '_mz_jobs_form_poll_input_answers_add',
                'txt_poll_menu_view_answers' => '_mz_jobs_txt_poll_view_answers',
                'txt_poll_menu_view_results' => '_mz_jobs_txt_poll_view_results',
                'txt_poll_answer_vote_do_by' => '_mz_jobs_txt_poll_answer_vote_do_by',
                'txt_poll_answer_vote_counter' => '_mz_jobs_txt_poll_answer_vote_counter',
                'txt_poll_answer_vote_percent' => '_mz_jobs_txt_poll_answer_vote_percent',
                'txt_display_add' => '_mz_jobs_txt_display_title_add',
                'txt_display_edit' => '_mz_jobs_txt_display_title_edit',
                'txt_display_view' => '_mz_jobs_txt_display_title_view', 
				'txt_whatsapp_msg' => '_mz_jobs_default_whatsapp_msg',
                'filter_item_pending' => '_mz_jobs_grid_filter_item_title_adm_pending', 
                'filter_item_select_one_filter2' => '_mz_jobs_grid_filter_item_title_adm_select_one_filter2',   
				/*BEGIN UNA 13*/
                'txt_sample_interest_single' => '_mz_jobs_txt_sample_interest_single',
				/*END UNA 13*/ 	 

            ),
        ));
        
        $this->_aJsClasses = array_merge($this->_aJsClasses, array(
            'manage_tools' => 'MzJobsManageTools',
            'studio' => 'MzJobsStudio',
            'entry' => 'MzJobsEntry',
		    /*'form' => 'BxJobsForm',  */
        ));
  
        $this->_aJsObjects = array_merge($this->_aJsObjects, array(
            'manage_tools' => 'oMzJobsManageTools',
            'studio' => 'oMzJobsStudio',
            'entry' => 'oMzJobsEntry',
             /*'form' => 'BxJobsForm', */
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

	/*BEGIN UNA 13 RC2*/ 
    public function getActiveStatus()
    {
        return [BX_BASE_MOD_TEXT_STATUS_ACTIVE];
    }

    public function getActiveStatusAdmin()
    {
        return [BX_BASE_MOD_TEXT_STATUS_ACTIVE];
    }
	/*END UNA 13 RC2*/ 

}

/** @} */
