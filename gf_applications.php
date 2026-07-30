<?php
/**
 * GFunnel — Application Hub (app launcher + directory).
 *
 * The member-facing home for every tool in the ecosystem, rendered standalone
 * and skinned with the shared homepage design system (template/css/gf_home.css)
 * plus the hub-specific layer (template/css/gf_applications.css). Same
 * self-contained pattern as home.php / gf_marketplace.php.
 *
 *   /applications                 -> Apps tab: welcome hero, Core Applications
 *                                    (icon grid), hub cards
 *   /marketplace/applications     -> Marketplace tab: full App Directory
 *                                    (search + category filter + grid)
 *   /application/<slug>           -> per-app detail page
 *
 * DATA FLOW (see docs/directory-sync-runbook.md):
 *   Supabase Postgres  = source of truth
 *        │  row change -> Database Webhook
 *        ▼
 *   Edge Function sync-directory-apps-to-mysql  = owned one-way push (PG -> MySQL)
 *        │
 *        ▼
 *   MySQL mirrors  <-- THIS PAGE READS THEM (via the platform's BxDolDb)
 *     gf_directory_apps      (list/grid/icons)  <- public.directory_apps
 *     gf_platform_apps       (rich detail)      <- public.platform_apps
 *     gf_app_tutorials       (detail)           <- public.app_tutorials
 *     gf_app_docs            (detail)           <- public.app_docs
 *     gf_app_help_articles   (detail)           <- public.app_help_articles
 *
 * No Supabase creds live here; mirror tables auto-create on first load so the
 * page renders cleanly (empty state) before the first sync runs.
 *
 * Routes wired in r.php. Setting `gf_applications = off` (sys_options) disables it.
 */

require_once('./inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_INC . 'design.inc.php');
require_once(BX_DIRECTORY_PATH_INC . 'gf_home_blocks.inc.php');
require_once(BX_DIRECTORY_PATH_INC . 'gf_app_blocks.inc.php');

bx_import('BxDolLanguages');

if (getParam('gf_applications') == 'off') {
    BxDolTemplate::getInstance()->displayPageNotFound();
    exit;
}

$oDb = BxDolDb::getInstance();
gfDirEnsureTables($oDb);
gfWsAppsEnsureTable($oDb);

// JSON endpoints (POST ?gfa_action=...):
//   import            -> pull the directory from Supabase into the MySQL mirror
//                        (admin session, or ?key=<gf_dir_import_token> for cron)
//   add|remove|list   -> the signed-in member's personal app collection
// Only divert to a handler for a real action verb; an empty/unknown gfa_action
// (e.g. a stray "?gfa_action=") must still render the page, not 405.
$sGfAction = strtolower(trim((string)bx_get('gfa_action')));
if ($sGfAction !== '') {
    if ($sGfAction === 'import') { gfAppRunImport($oDb); exit; }
    if ($sGfAction === 'admin')  { gfAppRunAdmin($oDb);  exit; }
    if (in_array($sGfAction, array('add', 'remove', 'list'), true)) { gfAppHandleUserAction($oDb); exit; }
    // anything else: ignore and fall through to normal page rendering
}

$sSlug = isset($GLOBALS['gf_app_slug']) ? trim((string)$GLOBALS['gf_app_slug'], '/') : '';
if ($sSlug === '' && bx_get('app') !== false)
    $sSlug = trim((string)bx_get('app'));

// Which hub tab? r.php sets gf_app_tab='marketplace' for /marketplace/applications.
$sTab = isset($GLOBALS['gf_app_tab']) ? (string)$GLOBALS['gf_app_tab'] : (string)bx_get('tab');
if ($sTab !== 'marketplace') $sTab = 'apps';

if ($sSlug !== '')
    gfAppRenderDetail($oDb, $sSlug);
else
    gfAppRenderHub($oDb, $sTab);

exit;
