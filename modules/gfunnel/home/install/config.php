<?php
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    GFunnelHome GFunnel Home
 * @ingroup     UnaModules
 *
 * @{
 */

$aConfig = array(

    'type' => BX_DOL_MODULE_TYPE_MODULE,
    'name' => 'gfunnel_home',
    'title' => 'GFunnel Home',
    'note' => 'Homepage blocks (hero, departments, apps, catalogs, feeds, seasonal HTML) for the UNA Page Builder.',
    'version' => '1.0.0',
    'vendor' => 'GFunnel',
    'help_url' => '',

    'compatible_with' => array(
        '14.0.0'
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique.
     */
    'home_dir' => 'gfunnel/home/',
    'home_uri' => 'gfunnel_home',

    'db_prefix' => 'gfhome_',
    'class_prefix' => 'BxGfHome',

    'language_category' => 'GFunnel Home',

    /**
     * Installation/Uninstallation Section.
     *
     * No execute_sql: this module ships no tables and touches no core page tables on
     * install. It only registers itself so its service methods (serviceBlock*) become
     * available as native "Service" blocks in Studio -> Page Builder. Admins compose the
     * homepage from those blocks in the GUI (add / reorder / toggle), which keeps the
     * live site safe and reversible. See docs/gfunnel-home-blocks.md.
     */
    'install' => array(
        'update_languages' => 1,
        'clear_db_cache' => 1,
    ),
    'uninstall' => array(
        'update_languages' => 1,
        'clear_db_cache' => 1,
    ),
    'enable' => array(
        'clear_db_cache' => 1,
    ),
    'disable' => array(
        'clear_db_cache' => 1,
    ),

    'dependencies' => array(),
);

/** @} */
