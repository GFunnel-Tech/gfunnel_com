<?php 
/**
 * Copyright (c) MSolutions
 * 
 * @defgroup    Fansonly Fansonly module
 * @ingroup     MSolutionsModules
 *
 * @{
 */

$aConfig = array(
	/**
	 * Main Section.
	 */
	'type' => BX_DOL_MODULE_TYPE_MODULE,
    'name' => 'ms_fansonly',
	'title' => 'Fans Only', //change this for FansOnly, no spaces 
    'note' => 'Users can set their content for fans only and get paid for subscribers',
	'version' => '2.0.4',
	'vendor' => 'MSolutions',
    'help_url' => 'http://feed.una.io/?section={module_name}',
    'compatible_with' => array(
        '12',
        '13'
     ),
    /**
	 * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
	 */
	'home_dir' => 'msolutions/fansonly/',
	'home_uri' => 'fansonly',
	
	'db_prefix' => 'ms_fansonly_',
	'class_prefix' => 'MsFansonly',

	/**
	 * Category for language keys.
	 */
	'language_category' => 'MSolutions FansOnly', //change this before publishing module

    //'connections' => array(
    	//'sys_profiles_friends' => array ('type' => 'profiles'),
		//'sys_profiles_subscriptions' => array ('type' => 'profiles'),
    //),

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
		'trigger_profile_view_actions',
    ),

	/**
	 * Installation/Uninstallation Section.
	 */
	'install' => array(
		'execute_sql' => 1,
		'update_languages' => 1,
	),
	'uninstall' => array (
		'execute_sql' => 1,
		'update_languages' => 1,
    ),
    'enable' => array(
        'execute_sql' => 1,
        'recompile_global_paramaters' => 1,
        'clear_db_cache' => 1,
    	'update_relations' => 1,
    ),
    'enable_success' => array(
        'process_menu_triggers' => 1,
//        'register_transcoders' => 1,
        'process_page_triggers' => 1,
        'clear_db_cache' => 1,
    ),
    'disable' => array(
        'execute_sql' => 1,
        'recompile_global_paramaters' => 1,
        'clear_db_cache' => 1,
    	'update_relations' => 1,
    ),

    /**
	 * Dependencies Section
	 */
	'dependencies' => array(
    ),
    
    'relations' => array(
    	'bx_notifications'
    ),
);

/** @} */

