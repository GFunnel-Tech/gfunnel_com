<?php
/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Affiliate System Affiliate System
 * @ingroup     UNAModules
 *
 * @{
 */

$aConfig = array(
    /**
     * Main Section.
     */
    'type' => BX_DOL_MODULE_TYPE_MODULE,
    'name' => 'aqb_affiliate',
    'title' => 'Affiliate System',
    'note' => 'Affiliate System created by AQB Soft',
    'version' => '13.0.0',
    'vendor' => 'AQB Soft',
	'help_url' => 'http://feed.aqbsoft.com/?section={module_name}',

    'compatible_with' => array(
        '13.0.0-B1'
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
     */
    'home_dir' => 'aqb/affiliate/',
    'home_uri' => 'affiliate',

    'db_prefix' => 'aqb_aff_',
    'class_prefix' => 'AqbAffiliate',

    /**
     * Category for language keys.
     */
    'language_category' => 'Affiliate System',

    /**
     * Installation/Uninstallation Section.
     */

    'install' => array(
        'execute_sql' => 1,
        'update_languages' => 1,
    	'clear_db_cache' => 1
    ),
    'uninstall' => array (
        'execute_sql' => 1,
        'update_languages' => 1,
    	'clear_db_cache' => 1
    ),
    'enable' => array(
        'execute_sql' => 1,    	
        'clear_db_cache' => 1,
    ),
    'disable' => array(
        'execute_sql' => 1,    	
        'clear_db_cache' => 1,
    ),
    'storages' => array(
        'aqb_aff_files'
    ),
    'transcoders' => array(
        'aqb_affiliate_bnr_preview'
    ),

    /**
     * Dependencies Section
     */
    'dependencies' => array(),
);

/** @} */
