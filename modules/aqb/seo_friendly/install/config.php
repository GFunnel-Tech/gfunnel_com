<?php 
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    JamiiTrade JamiiTrade
 * @ingroup     UnaModules
 *
 * @{
 */

$aConfig = array(
    /**
     * Main Section.
     */
    'type' => BX_DOL_MODULE_TYPE_MODULE,
    'name' => 'aqb_seof',
    'title' => 'Friendly URL',
    'note' => 'Friendly URL created by AQB Soft',
    'version' => '1.0.0',
    'vendor' => 'AQB Soft',
    'help_url' => 'http://feed.una.io/?section={module_name}',

    'compatible_with' => array(
        '13.0.0'
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
     */
    'home_dir' => 'aqb/seo_friendly/',
    'home_uri' => 'seo_friendly',

    'db_prefix' => 'aqb_seof_',
    'class_prefix' => 'AqbSEOF',

    /**
     * Category for language keys.
     */
    'language_category' => 'Friendly URL',

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
        'clear_db_cache' => 1,
    ),
    'disable' => array (
        'execute_sql' => 1,
        'clear_db_cache' => 1,
    ),

    /**
     * Dependencies Section
     */
    'dependencies' => [],
);

/** @} */
