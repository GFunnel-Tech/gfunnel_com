<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    News News
 * @ingroup     ModzzzModules
 *
 * @{
 */

bx_import('BxDolInformer');

class MzNewsConfig extends BxBaseModTextConfig
{

	public $_isMultiLanguage;

    function __construct($aModule)
    {
        parent::__construct($aModule);

        $aMenuItems2Methods = array (
            'approve' => 'checkAllowedApprove',
            'edit-news' => 'checkAllowedEdit',
            'delete-news' => 'checkAllowedDelete',
        );

        $this->CNF = array_merge($this->CNF, array (
 
			'PARAM_SOURCE_REDIRECT' => (getParam('mz_news_source_redirect')=='on') ? true : false,

			/*[begin] UNA 13 RC */
			'FIELD_THUMB_POSITION' => 'thumb_data',
			'OBJECT_IMAGES_TRANSCODER_MINIATURE' => 'mz_news_miniature',
			'OBJECT_IMAGES_TRANSCODER_MINIATURE_PHOTOS' => 'mz_news_miniature_photos',
			/*[end] UNA 13 RC */

			/*[begin] UNA 13 alpha */

            'OBJECT_IMAGES_TRANSCODER_VIEW_PHOTOS' => 'mz_news_view_photos',
            'FIELD_ALLOW_COMMENTS' => 'allow_comments',
            'PARAM_AUTO_APPROVE' => 'mz_news_enable_auto_approve',

			/*[end] UNA 13 alpha */

			/*[begin] UNA 13 beta */

            'PARAM_LINKS_ENABLED' => true,
			'FIELD_CF' => 'cf',
			'FIELD_LINK' => 'link',
			'FIELD_SOURCE' => 'source',

			/*[end] UNA 13 beta */

			/*[begin] UNA 12*/
            'FIELD_SOUND' => 'sounds',
            'PARAM_PER_PAGE_FOR_FAVORITES_LISTS' => 'mz_news_per_page_for_favorites_lists',
            'URI_FAVORITES_LIST' => 'news-favorites',
            'OBJECT_STORAGE_SOUNDS' => 'mz_news_sounds',
            'OBJECT_SOUNDS_TRANSCODER' => 'mz_news_sounds_mp3',
			'OBJECT_NOTES' => 'mz_news_notes',
			'OBJECT_PRIVACY_LIST_VIEW' => 'mz_news_allow_view_favorite_list',
			/*[end] UNA 12*/


            // module icon
            'ICON' => 'file-alt col-red3',

            // database tables
            'TABLE_ENTRIES' => $aModule['db_prefix'] . 'news',
            'TABLE_ENTRIES_FULLTEXT' => 'title_text',

            // database fields
            'FIELD_ID' => 'id',
            'FIELD_AUTHOR' => 'author',
            'FIELD_ADDED' => 'added',
            'FIELD_CHANGED' => 'changed',
            'FIELD_PUBLISHED' => 'published',
            'FIELD_BREAKING_NEWS' => 'breaking', 
            'FIELD_BREAKING_THRESHOLD' => 'breaking_threshold', 
            'FIELD_LANGUAGE' => 'language', 
            'FIELD_TITLE' => 'title',
            'FIELD_TEXT' => 'text',
            'FIELD_TEXT_ID' => 'news-text',
            'FIELD_CATEGORY' => 'cat',
            'FIELD_MULTICAT' => 'multicat',
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
            'FIELD_DISABLE_COMMENTS' => 'disable_comments',
            'FIELDS_WITH_KEYWORDS' => 'auto', // can be 'auto', array of fields or comma separated string of field names, works only when OBJECT_METATAGS is specified
            'FIELDS_DELAYED_PROCESSING' => 'videos', // can be array of fields or comma separated string of field names

             // some params
            'PARAM_MULTICAT_ENABLED' => true,
            'PARAM_MULTICAT_AUTO_ACTIVATION_FOR_CATEGORIES' => 'mz_news_auto_activation_for_categories',
            
            // page URIs
            'URI_VIEW_ENTRY' => 'view-news',
            'URI_AUTHOR_ENTRIES' => 'news-author',
            'URI_ENTRIES_BY_CONTEXT' => 'news-context',
            'URI_ADD_ENTRY' => 'create-news',
            'URI_EDIT_ENTRY' => 'edit-news',
            'URI_MANAGE_COMMON' => 'news-manage',

            'URL_HOME' => 'page.php?i=news-home', 
            'URL_LOCAL' => 'page.php?i=news-local', 
            'URL_ARCHIVE' => 'page.php?i=news-archive&period=', 
            'URL_CATEGORY' => 'page.php?i=news-category&category=',
            'URL_POPULAR' => 'page.php?i=news-popular',
            'URL_TOP' => 'page.php?i=news-top',
            'URL_FEATURED' => 'page.php?i=news-featured', 
            'URL_UPDATED' => 'page.php?i=news-updated',
            'URL_MANAGE_COMMON' => 'page.php?i=news-manage',
            'URL_MANAGE_ADMINISTRATION' => 'page.php?i=news-administration',

            // some params
            'PARAM_CHARS_SUMMARY' => 'mz_news_summary_chars',
            'PARAM_CHARS_SUMMARY_PLAIN' => 'mz_news_plain_summary_chars',
            'PARAM_NUM_RSS' => 'mz_news_rss_num',
            'PARAM_SEARCHABLE_FIELDS' => 'mz_news_searchable_fields',
            'PARAM_PER_PAGE_BROWSE_SHOWCASE' => 'mz_news_per_page_browse_showcase',

            // objects
            'OBJECT_STORAGE' => 'mz_news_covers',
            'OBJECT_STORAGE_FILES' => 'mz_news_files',
            'OBJECT_STORAGE_PHOTOS' => 'mz_news_photos',
            'OBJECT_STORAGE_VIDEOS' => 'mz_news_videos',
            'OBJECT_IMAGES_TRANSCODER_PREVIEW' => 'mz_news_preview',
            'OBJECT_IMAGES_TRANSCODER_GALLERY' => 'mz_news_gallery',
            'OBJECT_IMAGES_TRANSCODER_COVER' => 'mz_news_cover',
            'OBJECT_IMAGES_TRANSCODER_PREVIEW_FILES' => 'mz_news_preview_files',
            'OBJECT_IMAGES_TRANSCODER_GALLERY_FILES' => 'mz_news_gallery_files',
            'OBJECT_IMAGES_TRANSCODER_PREVIEW_PHOTOS' => 'mz_news_preview_photos',
            'OBJECT_IMAGES_TRANSCODER_GALLERY_PHOTOS' => 'mz_news_gallery_photos',
            'OBJECT_VIDEOS_TRANSCODERS' => array(
                'poster' => 'mz_news_videos_poster', 
            	'poster_preview' => 'mz_news_videos_poster_preview',
            	'mp4' => 'mz_news_videos_mp4', 
            	'mp4_hd' => 'mz_news_videos_mp4_hd'
            ),
            'OBJECT_VIDEO_TRANSCODER_HEIGHT' => '480px',
            'OBJECT_REPORTS' => 'mz_news',
            'OBJECT_VIEWS' => 'mz_news',
            'OBJECT_VOTES' => 'mz_news',
            'OBJECT_REACTIONS' => 'mz_news_reactions',
            'OBJECT_SCORES' => 'mz_news',
            'OBJECT_FAVORITES' => 'mz_news',
            'OBJECT_FEATURED' => 'mz_news',
            'OBJECT_METATAGS' => 'mz_news',
            'OBJECT_COMMENTS' => 'mz_news',
            'OBJECT_CATEGORY' => 'mz_news_cats',
            'OBJECT_PRIVACY_VIEW' => 'mz_news_allow_view_to',
            'OBJECT_FORM_ENTRY' => 'mz_news',
            'OBJECT_FORM_ENTRY_DISPLAY_VIEW' => 'mz_news_entry_view',
            'OBJECT_FORM_ENTRY_DISPLAY_ADD' => 'mz_news_entry_add',
            'OBJECT_FORM_ENTRY_DISPLAY_EDIT' => 'mz_news_entry_edit',
            'OBJECT_FORM_ENTRY_DISPLAY_DELETE' => 'mz_news_entry_delete',
            'OBJECT_FORM_POLL' => 'mz_news_poll',
            'OBJECT_FORM_POLL_DISPLAY_ADD' => 'mz_news_poll_add',
            'OBJECT_MENU_ENTRY_ATTACHMENTS' => 'mz_news_entry_attachments', // attachments menu in create/edit forms
            'OBJECT_MENU_ACTIONS_VIEW_ENTRY' => 'mz_news_view', // actions menu on view entry page
            'OBJECT_MENU_ACTIONS_VIEW_ENTRY_ALL' => 'mz_news_view_actions', // all actions menu on view entry page
            'OBJECT_MENU_ACTIONS_MY_ENTRIES' => 'mz_news_my', // actions menu on my entries page
            'OBJECT_MENU_SUBMENU' => 'mz_news_submenu', // main module submenu
            'OBJECT_MENU_SUBMENU_VIEW_ENTRY' => 'mz_news_view_submenu', // view entry submenu
            'OBJECT_MENU_SUBMENU_VIEW_ENTRY_MAIN_SELECTION' => 'news-home', // first item in view entry submenu from main module submenu
            'OBJECT_MENU_SNIPPET_META' => 'mz_news_snippet_meta', // menu for snippet meta info
            'OBJECT_MENU_MANAGE_TOOLS' => 'mz_news_menu_manage_tools', //manage menu in content administration tools
            'OBJECT_GRID_ADMINISTRATION' => 'mz_news_administration',
            'OBJECT_GRID_COMMON' => 'mz_news_common',
            'OBJECT_GRID_CATEGORIES' => 'mz_news_categories',
            'OBJECT_UPLOADERS' => array('mz_news_html5'),
            'OBJECT_CONTENT_INFO' => 'mz_news',
            'OBJECT_CMTS_CONTENT_INFO' => 'mz_news_cmts',
            
            'BADGES_AVALIABLE' => true,

            // menu items which visibility depends on custom visibility checking
            'MENU_ITEM_TO_METHOD' => array (
                'mz_news_my' => array (
                    'create-news' => 'checkAllowedAdd',
                ),
                'mz_news_view' => $aMenuItems2Methods,
            ),

            // informer messages
            'INFORMERS' => array (
                'approving' => array ( /* una 13 alpha */
                    'name' => 'mz-news-approving',
                    'map' => array (
                        'pending' => array('msg' => '_mz_news_txt_msg_status_pending', 'type' => BX_INFORMER_ALERT),
                        'hidden' => array('msg' => '_mz_news_txt_msg_status_hidden', 'type' => BX_INFORMER_ERROR),
                    ),
                ),  
                'processing' => array (
                    'name' => 'mz-news-processing',
                    'map' => array (
                        'awaiting' => array('msg' => '_mz_news_txt_processing_awaiting', 'type' => BX_INFORMER_ALERT),
                        'failed' => array('msg' => '_mz_news_txt_processing_failed', 'type' => BX_INFORMER_ERROR)
                    ),
                ),
                'scheduled' => array (
                    'name' => 'mz-news-scheduled',
                    'map' => array (
                        'awaiting' => array('msg' => '_mz_news_txt_scheduled_awaiting', 'type' => BX_INFORMER_ALERT),
                    ),
                ),
            ),
 
            // some language keys
            'T' => array (
                'txt_sample_single' => '_mz_news_txt_sample_single',
            	'txt_sample_single_with_article' => '_mz_news_txt_sample_single_with_news',
            	'txt_sample_comment_single' => '_mz_news_txt_sample_comment_single',
            	'txt_sample_vote_single' => '_mz_news_txt_sample_vote_single',
                'txt_sample_reaction_single' => '_mz_news_txt_sample_reaction_single',
                'txt_sample_score_up_single' => '_mz_news_txt_sample_score_up_single',
                'txt_sample_score_down_single' => '_mz_news_txt_sample_score_down_single',
                'form_field_author' => '_mz_news_form_entry_input_author',
            	'grid_action_err_delete' => '_mz_news_grid_action_err_delete',
            	'grid_txt_account_manager' => '_mz_news_grid_txt_account_manager',
                'filter_item_active' => '_mz_news_grid_filter_item_title_adm_active',
            	'filter_item_hidden' => '_mz_news_grid_filter_item_title_adm_hidden',
            	'filter_item_select_one_filter1' => '_mz_news_grid_filter_item_title_adm_select_one_filter1',
            	'menu_item_manage_my' => '_mz_news_menu_item_title_manage_my',
            	'menu_item_manage_all' => '_mz_news_menu_item_title_manage_all',
                'txt_all_entries_by' => '_mz_news_txt_all_entries_by',
                'txt_all_entries_in' => '_mz_news_txt_all_entries_in',
                'txt_all_entries_by_author' => '_mz_news_page_title_browse_by_author',
                'txt_all_entries_by_context' => '_mz_news_page_title_browse_by_context',
                'txt_err_cannot_perform_action' => '_mz_news_txt_err_cannot_perform_action',
                'txt_poll_form_answers_add' => '_mz_news_form_poll_input_answers_add',
                'txt_poll_menu_view_answers' => '_mz_news_txt_poll_view_answers',
                'txt_poll_menu_view_results' => '_mz_news_txt_poll_view_results',
                'txt_poll_menu_view_' => '_mz_news_txt_poll_view_',
                'txt_poll_answer_vote_do_by' => '_mz_news_txt_poll_answer_vote_do_by',
                'txt_poll_answer_vote_counter' => '_mz_news_txt_poll_answer_vote_counter',
                'txt_poll_answer_vote_percent' => '_mz_news_txt_poll_answer_vote_percent',
                'filter_item_pending' => '_mz_news_grid_filter_item_title_adm_pending', /* una 13 alpha */ 
                'filter_item_select_one_filter2' => '_mz_news_grid_filter_item_title_adm_select_one_filter2', /* una 13 beta */

            ),
        ));
  
        $this->_aJsClasses = array_merge($this->_aJsClasses, array(
            'manage_tools' => 'MzNewsManageTools',
			'categories' => 'BxDolCategories' /*una12*/
        ));

        $this->_aJsObjects = array_merge($this->_aJsObjects, array(
            'manage_tools' => 'oMzNewsManageTools',
			'categories' => 'oBxDolCategories' /*una12*/
        ));
 
        $this->_aGridObjects = array(
            'common' => $this->CNF['OBJECT_GRID_COMMON'],
            'administration' => $this->CNF['OBJECT_GRID_ADMINISTRATION'],
        );

		$this->_isMultiLanguage = $this->isMultiLanguage();

        $this->_bAttachmentsInTimeline = true;

        $this->_aPrefixes = array(
            'style' => 'mz-news',
        );//una 13 beta 

    }

	function isMultiLanguage()
	{
            $aLangs = BxDolLanguages::getInstance()->getLanguages(false, true);
            if (count($aLangs) < 2)
                return false;

			return true;
	}

}

/** @} */
