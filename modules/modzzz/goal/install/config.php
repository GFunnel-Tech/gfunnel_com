<?php
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Goal Goal
 * @ingroup     ModzzzModules
 *
 * @{
 */

$aConfig = array(

    /**
     * Main Section.
     */
    'type' => BX_DOL_MODULE_TYPE_MODULE,
    'name' => 'mz_goal',
    'title' => 'Goal',
    'note' => 'Goal module.',
    'version' => '1.0.9',
    'vendor' => 'Modzzz',
    'help_url' => 'http://feed.una.io/?section={module_name}',

    'compatible_with' => array(
        '13.x.x'
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
     */
    'home_dir' => 'modzzz/goal/',
    'home_uri' => 'goal',

    'db_prefix' => 'mz_goal_',
    'class_prefix' => 'MzGoal',

    /**
     * Category for language keys.
     */
    'language_category' => 'Goal',

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
    ),

    /**
     * Storage objects to automatically delete files from upon module uninstallation.
     * Note. Don't add storage objects used in transcoder objects.
     */
    'storages' => array(
        'mz_goal_covers',
    	'mz_goal_photos',
        'mz_goal_videos',
        'mz_goal_files'
    ),

    /**
     * Transcoders.
     */
    'transcoders' => array(

        'mz_goal_preview',
        'mz_goal_gallery',
        'mz_goal_cover',

        'mz_goal_miniature',
        'mz_goal_miniature_photos',

        'mz_goal_preview_photos',
        'mz_goal_gallery_photos', 
        'mz_goal_view_photos',

        'mz_goal_videos_poster',
        'mz_goal_videos_poster_preview',
        'mz_goal_videos_mp4',
        'mz_goal_videos_mp4_hd',

        'mz_goal_preview_files',
        'mz_goal_gallery_files'
    ),

    /**
     * Extended Search Forms.
     */
    'esearches' => array(
        'mz_goal',
    	'mz_goal_cmts'
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
