<?php
/**
 * Copyright (c) Green Meteor - https://greenmeteor.net/
 * AGPL-3.0 License - https://opensource.org/licenses/AGPL-3.0
 *
 * @defgroup    DiscordConnect Discord Connect
 * @ingroup     GreenMeteorModule
 *
 * @{
 */

$aConfig = [
    /**
     * Main Section.
     */
    'type' => BX_DOL_MODULE_TYPE_MODULE,
    'name' => 'gm_discord',
    'title' => 'Discord connect',
    'note' => 'Join the site using Discord account.',
    'version' => '11.0.4',
    'vendor' => 'greenmeteor',
    'help_url' => 'http://feed.una.io/?section={module_name}',

    'compatible_with' => [
        '12.0.0'
    ],

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
     */
    'home_dir' => 'greenmeteor/discord_connect/',
    'home_uri' => 'discord',

    'db_prefix' => 'gm_discord_',
    'class_prefix' => 'GmDiscord',

    /**
     * Category for language keys.
     */
    'language_category' => 'Discord Connect',

    /**
     * Installation/Uninstallation Section.
     */
    'install' => [
        'execute_sql' => 1,
        'update_languages' => 1,
    ],
    'uninstall' => [
        'execute_sql' => 1,
        'update_languages' => 1,
    ],
    'enable' => [
        'execute_sql' => 1,
        'clear_db_cache' => 1,
    ],
    'disable' => [
        'execute_sql' => 1,
        'clear_db_cache' => 1,
    ],

    /**
     * Dependencies Section
     */
    'dependencies' => [],
];

/** @} */
