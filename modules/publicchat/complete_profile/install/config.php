<?php 
/**
 * Copyright (c) Publicchat.in
 * 
 * @defgroup    Complete Profile Publicchat.in module
 * @ingroup     PublicchatModules
 *
 * @{
 */

$aConfig = array(
	/**
	 * Main Section.
	 */
	'type' => BX_DOL_MODULE_TYPE_MODULE,
    'name' => 'publicchat_complete_profile',
	'title' => 'Complete Profile',
    'note' => 'Complete profile modules will guide new users to complete their profile.',
	'version' => '1.0.1',
	'vendor' => 'Publicchat.in',
    'product_url' => 'http://www.boonex.com/products/{uri}',
	'update_url' => '',
	
	'compatible_with' => array(
        '11.0.x'
    ),

    /**
	 * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
	 */
	'home_dir' => 'publicchat/complete_profile/',
	'home_uri' => 'complete_profile',
	
	'db_prefix' => 'publicchat_complete_profile',
	'class_prefix' => 'PcCb',

	/**
	 * Category for language keys.
	 */
	'language_category' => 'Publicchat Complete Profile',

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

