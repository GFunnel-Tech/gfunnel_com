<?php
/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Auto Friends Auto Friends
 * @ingroup     UNAModules
 *
 * @{
 */


$aConfig = array(
    /**
     * Main Section.
     */
    'type' => BX_DOL_MODULE_TYPE_MODULE,
    'name' => 'aqb_auto_friends',
    'title' => 'Auto Friends',
    'note' => 'Auto Friends module from AQB Soft.',
    'version' => '3.1.0',
    'vendor' => 'AQB Soft',
    'help_url' => 'http://feed.aqbsoft.com/?section={module_name}',

    'compatible_with' => array(
        '13.1.0-RC2'
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
     */
    'home_dir' => 'aqb/auto_friends/',
    'home_uri' => 'auto_friends',

    'db_prefix' => 'aqb_af_',
    'class_prefix' => 'AqbAf',

    /**
     * Category for language keys.
     */
    'language_category' => 'Auto Friends',

    /**
     * List of page triggers.
     */
    'page_triggers' => array (
    	'trigger_page_profile_view_entry'
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
    	'process_storages' => 0,
        'execute_sql' => 1,
        'update_languages' => 1,
    	'update_relations' => 0,
        'clear_db_cache' => 1,
    ),
    'enable' => array(
        'execute_sql' => 1,
    	'update_relations' => 0,
        'clear_db_cache' => 1,
    ),
    'enable_success' => array(
    	'process_page_triggers' => 1,
    	'register_transcoders' => 0,
    	'clear_db_cache' => 1,
    ),
    'disable' => array (
        'execute_sql' => 1,
    	'update_relations' => 0,
    	'unregister_transcoders' => 0,
        'clear_db_cache' => 1,
    ),

    /**
     * Dependencies Section
     */
    'dependencies' => array(),
);

/** @} */
