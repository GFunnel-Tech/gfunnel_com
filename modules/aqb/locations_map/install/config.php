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
 * @defgroup    Locations Map Locations Map
 * @ingroup     UnaModules
 *
 * @{
 */

$aConfig = array(
	/**
	 * Main Section.
	 */
	'type' => BX_DOL_MODULE_TYPE_MODULE,
    'name' => 'aqb_locations_map',
	'title' => 'Locations Map',
    'note' => 'Locations Map module from AQB Soft.',
	'version' => '1.0.8',
	'vendor' => 'AQBSoft',
    'help_url' => 'http://feed.aqbsoft.com/?section={module_name}',

	'compatible_with' => array(
        '9.0.x'
    ),

    /**
	 * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
	 */
	'home_dir' => 'aqb/locations_map/',
	'home_uri' => 'aqb_locations_map',

	'db_prefix' => 'aqb_locations_map',
	'class_prefix' => 'AqbLocationsMap',

	/**
	 * Category for language keys.
	 */
	'language_category' => 'AQB Locations Map',

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
        'recompile_global_paramaters' => 1,
        'clear_db_cache' => 1,
        'set_default_types' => 1,
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

