<?php
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Jobs Jobs
 * @ingroup     ModzzzModules
 *
 * @{
 */

$aConfig = array(

    /**
     * Main Section.
     */
    'type' => BX_DOL_MODULE_TYPE_MODULE,
    'name' => 'mz_jobs',
    'title' => 'Jobs',
    'note' => 'Jobs module.',
    'version' => '1.0.5',
    'vendor' => 'Modzzz',
    'help_url' => 'http://feed.una.io/?section={module_name}',

    'compatible_with' => array(
        '13.x.x'
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
     */
    'home_dir' => 'modzzz/jobs/',
    'home_uri' => 'jobs',

    'db_prefix' => 'mz_jobs_',
    'class_prefix' => 'MzJobs',

    /**
     * Category for language keys.
     */
    'language_category' => 'Jobs',

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
        'mz_jobs_covers',
    	'mz_jobs_photos',
        'mz_jobs_videos',
        'mz_jobs_files'
    ),

    /**
     * Transcoders.
     */
    'transcoders' => array(
        'mz_jobs_preview',
        'mz_jobs_gallery',
        'mz_jobs_cover',

        'mz_jobs_miniature',
        'mz_jobs_miniature_photos',

        'mz_jobs_preview_photos',
        'mz_jobs_gallery_photos', 
        'mz_jobs_view_photos',

        'mz_jobs_videos_poster',
        'mz_jobs_videos_poster_preview',
        'mz_jobs_videos_mp4',
        'mz_jobs_videos_mp4_hd',

        'mz_jobs_preview_files',
        'mz_jobs_gallery_files'
    ),

    /**
     * Extended Search Forms.
     */
    'esearches' => array(
        'mz_jobs',
    	'mz_jobs_cmts'
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
