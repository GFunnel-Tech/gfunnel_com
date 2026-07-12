<?php
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    News News
 * @ingroup     ModzzzModules
 *
 * @{
 */

$aConfig = array(

    /**
     * Main Section.
     */
    'type' => BX_DOL_MODULE_TYPE_MODULE,
    'name' => 'mz_news',
    'title' => 'News',
    'note' => 'News module.',
    'version' => '1.1.6',
    'vendor' => 'Modzzz',
    'help_url' => 'http://feed.una.io/?section={module_name}',

    'compatible_with' => array(
        '13.x.x',
        '14.x.x'
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
     */
    'home_dir' => 'modzzz/news/',
    'home_uri' => 'news',

    'db_prefix' => 'mz_news_',
    'class_prefix' => 'MzNews',

    /**
     * Category for language keys.
     */
    'language_category' => 'News',

    /**
     * List of page triggers.
     */
    'page_triggers' => array (
    	'trigger_page_profile_view_entry',
    ),
    
    /**
     * Menu triggers.
     */
    'menu_triggers' => array(
        'trigger_profile_view_submenu',
        'trigger_group_view_submenu',
    ),

    /**
     * Storage objects to automatically delete files from upon module uninstallation.
     * Note. Don't add storage objects used in transcoder objects.
     */
    'storages' => array(
        'mz_news_covers',
    	'mz_news_photos',
        'mz_news_videos',
        'mz_news_sounds',
        'mz_news_files'
    ),

    /**
     * Transcoders.
     */
    'transcoders' => array(
        'mz_news_preview',
        'mz_news_gallery',
        'mz_news_cover',

        'mz_news_miniature',
        'mz_news_miniature_photos',

        'mz_news_preview_photos',
        'mz_news_gallery_photos', 

        'mz_news_videos_poster',
        'mz_news_videos_poster_preview',
        'mz_news_videos_mp4',
        'mz_news_videos_mp4_hd',

        'mz_news_sounds_mp3',

        'mz_news_preview_files',
        'mz_news_gallery_files',

        'mz_news_view_photos', 
    ),

    /**
     * Extended Search Forms.
     */
    'esearches' => array(
        'mz_news',
    	'mz_news_cmts'
    ),

    /**
     * Installation/Uninstallation Section.
     */
    'install' => array(
        'execute_sql' => 1,
        'update_languages' => 1,
        'clear_db_cache' => 1,
    ),
    'uninstall' => array (
    	'process_esearches' => 1,
        'execute_sql' => 1,
        'update_languages' => 1,
        'update_relations' => 1,
        'clear_db_cache' => 1,
    ),
    'enable' => array(
        'execute_sql' => 1,
        'update_relations' => 1,
        'clear_db_cache' => 1,
    ),
    'enable_success' => array(
        'process_menu_triggers' => 1,
        'process_page_triggers' => 1,
    	'process_esearches' => 1,
        'register_transcoders' => 1,
        'clear_db_cache' => 1,
    ),
    'disable' => array (
        'execute_sql' => 1,
        'update_relations' => 1,
        'unregister_transcoders' => 1,
        'clear_db_cache' => 1,
    ),
    'disable_failed' => array (
        'register_transcoders' => 1,
        'clear_db_cache' => 1,
    ),

    /**
     * Dependencies Section
     */
    'dependencies' => array(),

    /**
     * Relations Section
     */
    'relations' => array(
    	'bx_timeline',
    	'bx_notifications'
    ),

);

/** @} */
