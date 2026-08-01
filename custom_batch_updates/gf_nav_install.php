<?php
/**
 * GFunnel Nav — one-off install migration.
 *
 * Creates the two tables (gfn_items, gfn_user_items) and seeds the default nav
 * items, without depending on the Studio module-install SQL mechanism. Idempotent:
 * CREATE IF NOT EXISTS, and it only seeds when gfn_items is empty.
 *
 * Once the tables exist, BxBaseFunctions::getGfSidebar() renders the sidebar from
 * them (defaults + each member's gfn_user_items overrides). Until then it uses the
 * built-in defaults, so the nav works either way.
 *
 * Run:  php custom_batch_updates/gf_nav_install.php
 */

require_once(dirname(__DIR__) . '/inc/header.inc.php');

$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';

$oDb = BxDolDb::getInstance();

$oDb->query("
    CREATE TABLE IF NOT EXISTS `gfn_items` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `key` VARCHAR(32) NOT NULL DEFAULT '',
        `title` VARCHAR(128) NOT NULL DEFAULT '',
        `url` VARCHAR(2048) NOT NULL DEFAULT '',
        `icon` VARCHAR(64) NOT NULL DEFAULT 'dot',
        `order` INT(11) NOT NULL DEFAULT 0,
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `system` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `key` (`key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
");

$oDb->query("
    CREATE TABLE IF NOT EXISTS `gfn_user_items` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `account_id` INT(11) NOT NULL DEFAULT 0,
        `workspace_id` INT(11) NOT NULL DEFAULT 0,
        `item` VARCHAR(32) DEFAULT NULL,
        `custom` TINYINT(1) NOT NULL DEFAULT 0,
        `title` VARCHAR(128) NOT NULL DEFAULT '',
        `url` VARCHAR(2048) NOT NULL DEFAULT '',
        `icon` VARCHAR(64) NOT NULL DEFAULT 'dot',
        `hidden` TINYINT(1) NOT NULL DEFAULT 0,
        `order` INT(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `account` (`account_id`, `workspace_id`),
        UNIQUE KEY `account_item` (`account_id`, `workspace_id`, `item`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
");

echo "tables ensured\n";

$iCount = (int)$oDb->getOne("SELECT COUNT(*) FROM `gfn_items`");
if($iCount > 0) {
    echo "gfn_items already has {$iCount} rows - not reseeding\n";
} else {
    $aSeed = array(
        array('home',           'Home',           '',                        'home',        10),
        array('communications', 'Communications', 'page.php?i=messenger',     'message',     20),
        array('sales',          'Sales',          'sales',                    'sales',       30),
        array('memory',         'Memory',         'agents.php',               'memory',      40),
        array('applications',   'Applications',   'applications',             'apps',        50),
        array('social',         'Social',         'page.php?i=timeline',       'social',      60),
        array('explore',        'Explore',        'explore',                  'explore',     70),
        array('communities',    'Communities',    'communities',              'communities', 80),
        array('marketplace',    'Marketplace',    'marketplace',              'market',      90),
        array('events',         'Events',         'page.php?i=events-home',   'calendar',   100),
        array('learning',       'Learning',       'page.php?i=courses-home',  'learning',   110),
        array('partners',       'Partners',       'partners',                 'partners',   120),
    );
    foreach($aSeed as $a) {
        $oDb->query(
            "INSERT INTO `gfn_items` (`key`, `title`, `url`, `icon`, `order`, `active`, `system`) VALUES (:key, :title, :url, :icon, :order, 1, 1)",
            array('key' => $a[0], 'title' => $a[1], 'url' => $a[2], 'icon' => $a[3], 'order' => $a[4])
        );
    }
    echo "seeded " . count($aSeed) . " default items\n";
}

echo "done. Reload an app-layout page; the sidebar now renders from the DB.\n";
