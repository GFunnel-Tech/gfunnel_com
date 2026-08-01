<?php
/**
 * GFunnel Nav module — the GFunnel-owned left navigation (workspace/app sidebar).
 *
 * DB-backed and customizable: `gfn_items` holds the global default items
 * (admin-editable), `gfn_user_items` holds each member's per-workspace overrides
 * (hidden / reordered / custom-added). Rendered by BxGfNavModule::serviceSidebar()
 * and injected into the app layout via the gf_sidebar component key
 * (BxBaseFunctions::getGfSidebar delegates here when this module is installed).
 */

$aConfig = array(
    'type' => BX_DOL_MODULE_TYPE_MODULE,
    'name' => 'gfunnel_nav',
    'title' => 'GFunnel Nav',
    'note' => 'GFunnel-owned, DB-backed, customizable left navigation (sidebar)',
    'version' => '1.0.0',
    'vendor' => 'GFunnel',
    'help_url' => 'https://gfunnel.com/help',

    'compatible_with' => array(
        '13.0.0',
        '14.0.0',
    ),

    /**
     * 'home_dir' and 'home_uri' - unique, no spaces.
     */
    'home_dir' => 'gfunnel/nav/',
    'home_uri' => 'gfunnel_nav',

    'db_prefix' => 'gfn_',
    'class_prefix' => 'BxGfNav',

    'category' => 'system',

    'install' => array(
        'execute_sql' => 1,
        'update_languages' => 0,
        'clear_db_cache' => 1,
    ),
    'uninstall' => array(
        'execute_sql' => 1,
        'update_languages' => 0,
        'clear_db_cache' => 1,
    ),
    'enable' => array(
        'execute_sql' => 1,
        'clear_db_cache' => 1,
    ),
    'enable_success' => array(
        'clear_db_cache' => 1,
    ),
    'disable' => array(
        'execute_sql' => 1,
        'clear_db_cache' => 1,
    ),

    'dependencies' => array(),
    'relations' => array(),
);
