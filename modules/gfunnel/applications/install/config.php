<?php
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    GFunnelApplications GFunnel Applications
 * @ingroup     UnaModules
 *
 * @{
 */

$aConfig = array(

    'type' => BX_DOL_MODULE_TYPE_MODULE,
    'name' => 'gfunnel_applications',
    'title' => 'GFunnel Applications',
    'note' => 'Application Hub (workspace app launcher + directory) as a Page Builder service block.',
    'version' => '1.0.0',
    'vendor' => 'GFunnel',
    'help_url' => '',

    'compatible_with' => array(
        '14.0.0'
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique.
     */
    'home_dir' => 'gfunnel/applications/',
    'home_uri' => 'gfunnel_applications',

    'db_prefix' => 'gfapp_',
    'class_prefix' => 'BxGfApps',

    'language_category' => 'GFunnel Applications',

    /**
     * Installation/Uninstallation Section.
     *
     * No execute_sql: the module ships no tables of its own. It reads the app
     * directory from the MySQL mirror (gf_directory_apps …, self-provisioned by
     * gf_applications.php) and stores per-workspace app collections in
     * gf_workspace_apps (self-provisioned too). Installing only registers the
     * module so its serviceBlockHub becomes a native "Service" block in
     * Studio -> Page Builder, renderable inside the workspace shell. See
     * inc/gf_app_blocks.inc.php and docs/gfunnel-home-blocks.md (same pattern).
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
