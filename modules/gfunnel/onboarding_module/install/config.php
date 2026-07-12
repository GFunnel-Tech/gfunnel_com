<?php
/**
 * GFunnel Onboarding Module Configuration
 */

$aConfig = array(
    /**
     * Main Section
     */
    'type' => BX_DOL_MODULE_TYPE_MODULE,
    'name' => 'gfunnel_onb',
    'title' => 'GFunnel Onboarding',
    'note' => 'Manages new user onboarding flow and data collection',
    'version' => '1.0.0',
    'vendor' => 'GFunnel',
    'help_url' => 'https://gfunnel.com/help',

    'compatible_with' => array(
        '13.0.0',
        '14.0.0',
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces here.
     */
    'home_dir' => 'gfunnel/onboarding_module/',
    'home_uri' => 'gfunnel_onb',

    'db_prefix' => 'gfo_',
    'class_prefix' => 'BxGfunnelOnb',

    /**
     * Category for listing modules in the Market
     */
    'category' => 'system',

    /**
     * Installation/Uninstallation Section
     */
    'install' => array(
        'execute_sql' => 1,
        'update_languages' => 0,
        'clear_db_cache' => 1,
    ),
    'uninstall' => array (
        'execute_sql' => 1,
        'update_languages' => 0,
        'clear_db_cache' => 1,
    ),
    'enable' => array(
        'execute_sql' => 1,
        'clear_db_cache' => 1,
    ),
    'enable_success' => array(
        'process_menu_triggers' => 1,
        'clear_db_cache' => 1,
    ),
    'disable' => array(
        'execute_sql' => 1,
        'clear_db_cache' => 1,
    ),

    /**
     * Dependencies Section
     */
    'dependencies' => array(),

    /**
     * Relations Section (optional)
     */
    'relations' => array(),
);