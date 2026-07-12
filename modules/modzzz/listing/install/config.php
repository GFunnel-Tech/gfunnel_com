<?php
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Listing Listing
 * @ingroup     ModzzzModules
 *
 * @{
 */

$aConfig = array(

    /**
     * Main Section.
     */
    'type' => BX_DOL_MODULE_TYPE_MODULE,
    'name' => 'mz_listing',
    'title' => 'Business Listing',
    'note' => 'Business Listing module.',
    'version' => '1.0.8',
    'vendor' => 'Modzzz',
    'help_url' => 'http://feed.una.io/?section={module_name}',

    'compatible_with' => array(
        '13.x.x',
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
     */
    'home_dir' => 'modzzz/listing/',
    'home_uri' => 'listing',

    'db_prefix' => 'mz_listing_',
    'class_prefix' => 'MzListing',

    /**
     * Category for language keys.
     */
    'language_category' => 'Listing',

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
        'mz_listing_covers',
    	'mz_listing_photos',
        'mz_listing_videos',
        'mz_listing_files',
        'mz_listing_miniature',
        'mz_listing_miniature_photos',

    	'mz_listing_photos_branch',
    	'mz_listing_photos_service',
    	'mz_listing_photos_claim',
     	'mz_listing_photos_gift',
    	'mz_listing_photos_reward',

    	'mz_listing_preview_branch',
    	'mz_listing_preview_service',
    	'mz_listing_preview_claim',
      	'mz_listing_preview_gift',
    	'mz_listing_preview_reward',

    	'mz_listing_gallery_branch',
    	'mz_listing_gallery_service',
    	'mz_listing_gallery_claim',
    	'mz_listing_gallery_gift',
    	'mz_listing_gallery_reward',

     ),

    /**
     * Transcoders.
     */
    'transcoders' => array(

        'mz_listing_preview',
        'mz_listing_gallery',
        'mz_listing_cover',

        'mz_listing_view_photos',

        'mz_listing_preview_photos',
        'mz_listing_gallery_photos', 

        'mz_listing_videos_poster',
        'mz_listing_videos_poster_preview',
        'mz_listing_videos_mp4',
        'mz_listing_videos_mp4_hd',

        'mz_listing_preview_files',
        'mz_listing_gallery_files'
    ),

    /**
     * Extended Search Forms.
     */
    'esearches' => array(
        'mz_listing',
    	'mz_listing_cmts'
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
