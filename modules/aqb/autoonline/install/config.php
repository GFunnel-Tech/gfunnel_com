<?php
/**
 *     copyright            : (C) 2018 AQB Soft
 *     website              : http://www.aqbsoft.com
 *
 * IMPORTANT: This is a commercial product made by AQB Soft. It cannot be modified for other than personal usage.
 * The "personal usage" means the product can be installed and set up for ONE domain name ONLY.
 * To be able to use this product for another domain names you have to order another copy of this product (license).
 *
 * This product cannot be redistributed for free or a fee without written permission from AQB Soft.
 *
 * This notice may not be removed from the source code.
 *
 * @defgroup    Autoonline Autoonline
 * @ingroup     UnaModules
 *
 * @{
 */

$aConfig = array(
	/**
	 * Main Section.
	 */
	'type' => BX_DOL_MODULE_TYPE_MODULE,
    'name' => 'aqb_autoonline',
	'title' => 'Autoonline',
    'note' => 'Autoonline module from AQB Soft.',
	'version' => '1.1.5',
	'vendor' => 'AQBSoft',
    'help_url' => 'http://feed.aqbsoft.com/?section={module_name}',

	'compatible_with' => array(
        '9.0.0'
    ),

    /**
	 * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
	 */
	'home_dir' => 'aqb/autoonline/',
	'home_uri' => 'aqb_autoonline',

	'db_prefix' => 'aqb_autoonline',
	'class_prefix' => 'AqbAutoonline',

	/**
	 * Category for language keys.
	 */
	'language_category' => 'AQB Autoonline',

	/**
	 * Installation/Uninstallation Section.
	 */
	'install' => array(
		'execute_sql' => 1,
		'update_languages' => 1,
		'clear_db_cache' => 1,
	),
	'uninstall' => array (
		'execute_sql' => 1,
		'update_languages' => 1,
		'clear_db_cache' => 1,
    ),
    'enable' => array(
        'execute_sql' => 1,
        'clear_db_cache' => 1,
    ),
    'disable' => array(
        'execute_sql' => 1,
        'clear_db_cache' => 1,
    ),

    /**
	 * Dependencies Section
	 */
	'dependencies' => array(
    ),
);

/** @} */

