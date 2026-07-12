<?php 
/**
 * Copyright (c) Modzzz
 * 
 * @defgroup    Message Message 
 * @ingroup     ModzzzModules
 *
 * @{
 */

$aConfig = array(
	/**
	 * Main Section.
	 */
	'type' => BX_DOL_MODULE_TYPE_MODULE,
    'name' => 'mz_message',
	'title' => 'Profile Message',
    'note' => 'Displays defined profile message at top of Profile Page',
	'version' => '1.0.1',
	'vendor' => 'Modzzz',
    'product_url' => 'http://www.boonex.com/products/{uri}',
	'update_url' => '',
	
	'compatible_with' => array(
        '13.x.x',
        '14.x.x'
    ),

    /**
	 * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
	 */
	'home_dir' => 'modzzz/message/',
	'home_uri' => 'message',
	
	'db_prefix' => 'mz_message',
	'class_prefix' => 'MzMessage',

	/**
	 * Category for language keys.
	 */
	'language_category' => 'Modzzz Profile Message',

    /**
     * Menu triggers.
     */
    'menu_triggers' => array(
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
    ),
    'enable_success' => array(
        'process_menu_triggers' => 1,
		'clear_db_cache' => 1,
    ),
    'disable' => array(
        'execute_sql' => 1,
        'recompile_global_paramaters' => 1,
        'clear_db_cache' => 1,
    ),

    /**
	 * Dependencies Section
	 */
	'dependencies' => array(
    ),
);

/** @} */

