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

bx_import('BxDolLanguages');

if (getParam('gf_applications') == 'off') {
    BxDolTemplate::getInstance()->displayPageNotFound();
    exit;
}

$oDb = BxDolDb::getInstance();
gfDirEnsureTables($oDb);
gfUserAppsEnsureTable($oDb);

// JSON endpoints (POST ?gfa_action=...):
//   import            -> pull the directory from Supabase into the MySQL mirror
//                        (admin session, or ?key=<gf_dir_import_token> for cron)
//   add|remove|list   -> the signed-in member's personal app collection
if (bx_get('gfa_action') !== false) {
    if (strtolower((string)bx_get('gfa_action')) === 'import') {
        gfAppRunImport($oDb);
        exit;
    }
    gfAppHandleUserAction($oDb);
    exit;
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

// ============================================================================
// Schema (MySQL mirror of Supabase — created if missing).
// ============================================================================

/** Create all mirror tables if missing (types map Postgres -> MySQL). */
function gfDirEnsureTables($oDb)
{
    $oDb->query("CREATE TABLE IF NOT EXISTS `gf_directory_apps` (
        `id` char(36) NOT NULL,
        `platform_app_id` char(36) DEFAULT NULL,
        `name` varchar(255) NOT NULL DEFAULT '',
        `slug` varchar(255) DEFAULT NULL,
        `description` text,
        `logo_url` varchar(2048) DEFAULT NULL,
        `app_url` varchar(2048) DEFAULT NULL,
        `category` varchar(191) DEFAULT NULL,
        `access_type` varchar(64) DEFAULT 'free',
        `is_featured` tinyint(1) NOT NULL DEFAULT 0,
        `is_gfunnel_native` tinyint(1) NOT NULL DEFAULT 0,
        `created_at` datetime DEFAULT NULL,
        `synced_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`), KEY `slug` (`slug`), KEY `category` (`category`), KEY `is_featured` (`is_featured`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $oDb->query("CREATE TABLE IF NOT EXISTS `gf_platform_apps` (
        `id` char(36) NOT NULL,
        `zapier_key` varchar(191) DEFAULT NULL,
        `name` varchar(255) NOT NULL DEFAULT '',
        `slug` varchar(255) DEFAULT NULL,
        `app_url` varchar(2048) DEFAULT NULL,
        `logo_url` varchar(2048) DEFAULT NULL,
        `tagline` varchar(512) DEFAULT NULL,
        `description` text,
        `gfunnel_description` text,
        `categories` text,
        `departments` text,
        `gfunnel_use_cases` text,
        `automation_ideas` text,
        `related_app_slugs` text,
        `popularity_rank` int(11) DEFAULT NULL,
        `is_featured` tinyint(1) NOT NULL DEFAULT 0,
        `seo_title` varchar(512) DEFAULT NULL,
        `seo_description` text,
        `created_at` datetime DEFAULT NULL,
        `updated_at` datetime DEFAULT NULL,
        `synced_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`), KEY `slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $oDb->query("CREATE TABLE IF NOT EXISTS `gf_app_tutorials` (
        `id` char(36) NOT NULL,
        `platform_app_id` char(36) DEFAULT NULL,
        `youtube_video_id` varchar(191) DEFAULT NULL,
        `title` varchar(512) DEFAULT NULL,
        `channel_name` varchar(255) DEFAULT NULL,
        `duration_seconds` int(11) DEFAULT NULL,
        `thumbnail_url` varchar(2048) DEFAULT NULL,
        `published_at` datetime DEFAULT NULL,
        `view_count` bigint(20) DEFAULT NULL,
        `synced_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`), KEY `platform_app_id` (`platform_app_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $oDb->query("CREATE TABLE IF NOT EXISTS `gf_app_docs` (
        `id` char(36) NOT NULL,
        `platform_app_id` char(36) DEFAULT NULL,
        `title` varchar(512) DEFAULT NULL,
        `url` varchar(2048) DEFAULT NULL,
        `doc_type` varchar(64) DEFAULT NULL,
        `synced_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`), KEY `platform_app_id` (`platform_app_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $oDb->query("CREATE TABLE IF NOT EXISTS `gf_app_help_articles` (
        `id` char(36) NOT NULL,
        `platform_app_id` char(36) DEFAULT NULL,
        `title` varchar(512) DEFAULT NULL,
        `content` text,
        `url` varchar(2048) DEFAULT NULL,
        `category` varchar(191) DEFAULT NULL,
        `synced_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`), KEY `platform_app_id` (`platform_app_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ============================================================================
// Small helpers.
// ============================================================================

/** Safe HTML text. */
function gfDirOut($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** Decode a JSON-array text column into a PHP array of strings. */
function gfDirJson($s)
{
    if ($s === null || $s === '') return array();
    $a = json_decode((string)$s, true);
    return is_array($a) ? $a : array();
}

/** Internal detail URL for an app (by slug, falling back to id). */
function gfDirUrl($aApp)
{
    $sSlug = trim((string)$aApp['slug']);
    if ($sSlug === '') $sSlug = (string)$aApp['id'];
    return BX_DOL_URL_ROOT . 'application/' . rawurlencode($sSlug);
}

/** First initial for logo fallback. */
function gfDirInitial($sName)
{
    $s = preg_replace('/[^A-Za-z0-9]/', '', (string)$sName);
    return $s !== '' ? strtoupper(substr($s, 0, 1)) : '?';
}

/** <img> tag for a logo, or '' if none / not http(s). */
function gfDirImg($sLogo, $sClass = '')
{
    $sLogo = trim((string)$sLogo);
    if ($sLogo === '' || !preg_match('#^https?://#i', $sLogo)) return '';
    return '<img' . ($sClass ? ' class="' . $sClass . '"' : '') . ' src="' . bx_html_attribute($sLogo)
        . '" alt="" loading="lazy" onerror="this.style.display=\'none\';this.parentNode.classList.add(\'gfa-noimg\');">';
}

/** Info about the current logged-in member (id/name/initial), or empty. */
function gfAppMember()
{
    $a = array('logged' => false, 'id' => 0, 'name' => '', 'initial' => '', 'admin' => false);
    if (!function_exists('isLogged') || !isLogged()) return $a;
    $a['logged'] = true;
    $a['admin'] = function_exists('isAdmin') && isAdmin();
    if (class_exists('BxDolProfile')) {
        $o = BxDolProfile::getInstance();
        if ($o) {
            $a['id'] = (int)$o->id();
            $sName = strip_tags((string)$o->getDisplayName());
            $a['name'] = $sName;
            $a['initial'] = gfDirInitial($sName);
        }
    }
    return $a;
}

// ============================================================================
// Directory import: pull Supabase -> local MySQL mirror (server-side "pull",
// the Hostinger-friendly counterpart to the edge-function "push"). Runs on the
// site's own server so it reaches MySQL over localhost; only needs outbound
// HTTPS to Supabase's public REST API (tables are public-read).
// ============================================================================

/** Supabase REST config. Public anon key + URL; override via sys_options. */
function gfAppImportConfig()
{
    $sUrl = trim((string)getParam('gf_supabase_url'));
    if ($sUrl === '') $sUrl = 'https://yjneucgsaayyzoyxrlnb.supabase.co';
    $sKey = trim((string)getParam('gf_supabase_anon_key'));
    if ($sKey === '') $sKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InlqbmV1Y2dzYWF5eXpveXhybG5iIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzMzODg1MjMsImV4cCI6MjA4ODk2NDUyM30.sRRYcRV7BE3i5upSqDdlmhC9Lf_uEw3Trqni0uEn03w';
    return array('url' => rtrim($sUrl, '/'), 'key' => $sKey);
}

/** GET a page of rows from a Supabase table via PostgREST. */
function gfAppSbFetch($sBase, $sKey, $sTable, $iOffset, $iLimit)
{
    $sUrl = $sBase . '/rest/v1/' . rawurlencode($sTable) . '?select=*&order=id.asc&limit=' . (int)$iLimit . '&offset=' . (int)$iOffset;
    $rCh = curl_init($sUrl);
    curl_setopt_array($rCh, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => array('apikey: ' . $sKey, 'Authorization: Bearer ' . $sKey, 'Accept: application/json'),
    ));
    $sBody = curl_exec($rCh);
    $iCode = (int)curl_getinfo($rCh, CURLINFO_HTTP_CODE);
    $sErr = curl_error($rCh);
    curl_close($rCh);
    if ($sBody === false || $iCode >= 400) return array('err' => ($sErr ?: ('HTTP ' . $iCode)), 'rows' => null);
    $a = json_decode($sBody, true);
    return array('err' => is_array($a) ? '' : 'bad json', 'rows' => is_array($a) ? $a : null);
}

/** Import registry: mirror table -> [ mysqlCol => [srcKey, type] ]. */
function gfAppImportRegistry()
{
    return array(
        'directory_apps' => array('mirror' => 'gf_directory_apps', 'cols' => array(
            'id' => array('id', 't'), 'platform_app_id' => array('platform_app_id', 't'),
            'name' => array('name', 't0'), 'slug' => array('slug', 't'), 'description' => array('description', 't'),
            'logo_url' => array('logo_url', 't'), 'app_url' => array('app_url', 't'), 'category' => array('category', 't'),
            'access_type' => array('access_type', 'tfree'), 'is_featured' => array('is_featured', 'b'),
            'is_gfunnel_native' => array('is_gfunnel_native', 'b'), 'created_at' => array('created_at', 'ts'),
        )),
        'platform_apps' => array('mirror' => 'gf_platform_apps', 'cols' => array(
            'id' => array('id', 't'), 'zapier_key' => array('zapier_key', 't'), 'name' => array('name', 't0'),
            'slug' => array('slug', 't'), 'app_url' => array('app_url', 't'), 'logo_url' => array('logo_url', 't'),
            'tagline' => array('tagline', 't'), 'description' => array('description', 't'),
            'gfunnel_description' => array('gfunnel_description', 't'), 'categories' => array('categories', 'j'),
            'departments' => array('departments', 'j'), 'gfunnel_use_cases' => array('gfunnel_use_cases', 'j'),
            'automation_ideas' => array('automation_ideas', 'j'), 'related_app_slugs' => array('related_app_slugs', 'j'),
            'popularity_rank' => array('popularity_rank', 'n'), 'is_featured' => array('is_featured', 'b'),
            'seo_title' => array('seo_title', 't'), 'seo_description' => array('seo_description', 't'),
            'created_at' => array('created_at', 'ts'), 'updated_at' => array('updated_at', 'ts'),
        )),
        'app_tutorials' => array('mirror' => 'gf_app_tutorials', 'cols' => array(
            'id' => array('id', 't'), 'platform_app_id' => array('platform_app_id', 't'),
            'youtube_video_id' => array('youtube_video_id', 't'), 'title' => array('title', 't'),
            'channel_name' => array('channel_name', 't'), 'duration_seconds' => array('duration_seconds', 'n'),
            'thumbnail_url' => array('thumbnail_url', 't'), 'published_at' => array('published_at', 'ts'),
            'view_count' => array('view_count', 'n'),
        )),
        'app_docs' => array('mirror' => 'gf_app_docs', 'cols' => array(
            'id' => array('id', 't'), 'platform_app_id' => array('platform_app_id', 't'),
            'title' => array('title', 't'), 'url' => array('url', 't'), 'doc_type' => array('doc_type', 't'),
        )),
        'app_help_articles' => array('mirror' => 'gf_app_help_articles', 'cols' => array(
            'id' => array('id', 't'), 'platform_app_id' => array('platform_app_id', 't'),
            'title' => array('title', 't'), 'content' => array('content', 't'),
            'url' => array('url', 't'), 'category' => array('category', 't'),
        )),
    );
}

/** Coerce a source value to its MySQL-bound form. */
function gfAppCoerce($mVal, $sType)
{
    switch ($sType) {
        case 'b':   return $mVal ? 1 : 0;
        case 'n':   return ($mVal === null || $mVal === '') ? null : 0 + $mVal;
        case 'ts':
            if (!$mVal) return null;
            $iT = strtotime((string)$mVal);
            return $iT ? date('Y-m-d H:i:s', $iT) : null;
        case 'j':   return ($mVal === null) ? null : (is_string($mVal) ? $mVal : json_encode($mVal));
        case 't0':  return ($mVal === null) ? '' : (string)$mVal;
        case 'tfree': $s = ($mVal === null || $mVal === '') ? 'free' : (string)$mVal; return $s;
        default:    return ($mVal === null) ? null : (string)$mVal; // 't'
    }
}

/** Upsert one page of rows into a mirror table. Returns count written. */
function gfAppImportPage($oDb, $aDef, $aRows)
{
    if (empty($aRows)) return 0;
    $aCols = array_keys($aDef['cols']);
    $sColList = '`' . implode('`,`', $aCols) . '`,`synced_at`';
    $sUpd = array();
    foreach ($aCols as $c) if ($c !== 'id') $sUpd[] = "`$c`=VALUES(`$c`)";
    $sUpd[] = '`synced_at`=NOW()';

    $aTuples = array();
    foreach ($aRows as $r) {
        if (!isset($r['id'])) continue;
        $aVals = array();
        foreach ($aDef['cols'] as $sMy => $aSpec) {
            $mV = isset($r[$aSpec[0]]) ? $r[$aSpec[0]] : null;
            $mC = gfAppCoerce($mV, $aSpec[1]);
            $aVals[] = ($mC === null) ? 'NULL' : "'" . $oDb->escape((string)$mC) . "'";
        }
        $aVals[] = 'NOW()';
        $aTuples[] = '(' . implode(',', $aVals) . ')';
    }
    if (empty($aTuples)) return 0;
    $sSql = "INSERT INTO `{$aDef['mirror']}` ($sColList) VALUES " . implode(',', $aTuples)
        . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $sUpd);
    $oDb->query($sSql);
    return count($aTuples);
}

/** Run the full import (admin session OR matching cron token). Emits JSON. */
function gfAppRunImport($oDb)
{
    header('Content-Type: application/json; charset=utf-8');
    // Auth: admin session, or ?key=<gf_dir_import_token> (for cron / curl).
    $aMember = gfAppMember();
    $sToken = trim((string)getParam('gf_dir_import_token'));
    $sGiven = trim((string)bx_get('key'));
    $bOk = ($aMember['admin']) || ($sToken !== '' && hash_equals($sToken, $sGiven));
    if (!$bOk) { http_response_code(403); echo json_encode(array('ok' => false, 'error' => 'forbidden')); return; }

    @set_time_limit(0);
    $aCfg = gfAppImportConfig();
    $aReg = gfAppImportRegistry();
    $sOnly = trim((string)bx_get('table')); // optional single-table import
    $iLimit = 200; // rows per fetch + per multi-row upsert (safe for max_allowed_packet)
    $aResult = array();
    $aErrors = array();

    foreach ($aReg as $sTable => $aDef) {
        if ($sOnly !== '' && $sOnly !== 'all' && $sOnly !== $sTable) continue;
        $iOffset = 0; $iCount = 0;
        while (true) {
            $aPage = gfAppSbFetch($aCfg['url'], $aCfg['key'], $sTable, $iOffset, $iLimit);
            if ($aPage['rows'] === null) { $aErrors[$sTable] = $aPage['err']; break; }
            if (empty($aPage['rows'])) break;
            $iCount += gfAppImportPage($oDb, $aDef, $aPage['rows']);
            if (count($aPage['rows']) < $iLimit) break;
            $iOffset += $iLimit;
        }
        $aResult[$sTable] = $iCount;
    }

    echo json_encode(array('ok' => empty($aErrors), 'synced' => $aResult, 'errors' => $aErrors));
}

/** Platform-owned table for each member's personal app collection (NOT synced). */
function gfUserAppsEnsureTable($oDb)
{
    $oDb->query("CREATE TABLE IF NOT EXISTS `gf_user_apps` (
        `member_id` int(11) NOT NULL,
        `app_id` char(36) NOT NULL,
        `display_order` int(11) NOT NULL DEFAULT 0,
        `added_at` datetime DEFAULT NULL,
        PRIMARY KEY (`member_id`, `app_id`), KEY `member` (`member_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** IDs of the apps a member has added, in their chosen order. */
function gfUserAppIds($oDb, $iMember)
{
    if ((int)$iMember <= 0) return array();
    $a = $oDb->getColumn($oDb->prepare("SELECT `app_id` FROM `gf_user_apps` WHERE `member_id` = ? ORDER BY `display_order`, `added_at`", (int)$iMember));
    return is_array($a) ? $a : array();
}

/** Handle the add/remove/list JSON endpoint (members only). */
function gfAppHandleUserAction($oDb)
{
    header('Content-Type: application/json; charset=utf-8');
    // Writes require POST (blocks GET/img-tag CSRF); 'list' may be GET.
    $sAct0 = strtolower((string)bx_get('gfa_action'));
    if ($sAct0 !== 'list' && (empty($_SERVER['REQUEST_METHOD']) || strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST')) {
        http_response_code(405);
        echo json_encode(array('ok' => false, 'error' => 'method'));
        return;
    }
    $aMember = gfAppMember();
    if (!$aMember['logged'] || $aMember['id'] <= 0) {
        http_response_code(401);
        echo json_encode(array('ok' => false, 'error' => 'auth', 'guest' => true));
        return;
    }
    $iMember = (int)$aMember['id'];
    $sAction = strtolower((string)bx_get('gfa_action'));
    $sApp = trim((string)bx_get('app'));

    if ($sAction === 'list') {
        echo json_encode(array('ok' => true, 'apps' => array_values(gfUserAppIds($oDb, $iMember))));
        return;
    }
    // add / remove require a valid app id that exists in the directory mirror.
    if ($sApp === '' || strlen($sApp) > 36) { http_response_code(400); echo json_encode(array('ok' => false, 'error' => 'app')); return; }
    $bExists = (int)$oDb->getOne($oDb->prepare("SELECT COUNT(*) FROM `gf_directory_apps` WHERE `id` = ?", $sApp)) > 0;
    if (!$bExists) { http_response_code(404); echo json_encode(array('ok' => false, 'error' => 'unknown')); return; }

    if ($sAction === 'add') {
        $iOrder = (int)$oDb->getOne($oDb->prepare("SELECT COALESCE(MAX(`display_order`),0)+1 FROM `gf_user_apps` WHERE `member_id` = ?", $iMember));
        $oDb->query($oDb->prepare("INSERT INTO `gf_user_apps` (`member_id`,`app_id`,`display_order`,`added_at`) VALUES (?,?,?,NOW()) ON DUPLICATE KEY UPDATE `added_at` = `added_at`", $iMember, $sApp, $iOrder));
        echo json_encode(array('ok' => true, 'added' => true, 'app' => $sApp));
        return;
    }
    if ($sAction === 'remove') {
        $oDb->query($oDb->prepare("DELETE FROM `gf_user_apps` WHERE `member_id` = ? AND `app_id` = ?", $iMember, $sApp));
        echo json_encode(array('ok' => true, 'added' => false, 'app' => $sApp));
        return;
    }
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'action'));
}

// ============================================================================
// Shared page chrome (head + dark nav + footer + scripts).
// ============================================================================

/**
 * Render a full standalone HTML document around $sInner.
 * $aMeta keys: title, desc, canonical (optional), member (assoc from gfAppMember).
 */
function gfAppShell($sInner, $aMeta)
{
    $sSiteName = gfHomeOut(getParam('site_title'));
    $sTitle = isset($aMeta['title']) ? $aMeta['title'] : ('Applications | ' . $sSiteName);
    $sDesc = isset($aMeta['desc']) ? $aMeta['desc'] : 'Every app and tool in the GFunnel ecosystem — in one place.';
    $sCanonical = isset($aMeta['canonical']) ? $aMeta['canonical'] : (BX_DOL_URL_ROOT . 'applications');
    $aMember = isset($aMeta['member']) ? $aMeta['member'] : gfAppMember();

    $sCss1 = 'template/css/gf_home.css';
    $sCss2 = 'template/css/gf_applications.css';
    $sJs = 'template/js/gf_applications.js';
    $sCssUrl1 = BX_DOL_URL_ROOT . $sCss1 . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sCss1);
    $sCssUrl2 = BX_DOL_URL_ROOT . $sCss2 . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sCss2);
    $sJsUrl = BX_DOL_URL_ROOT . $sJs . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sJs);

    $sLoginUrl = gfHomeUrl('login');
    $sJoinUrl = gfHomeUrl('create-account');

    // Nav right: login-aware.
    if ($aMember['logged']) {
        $sNavRight = '<a class="gfh-signin" href="' . BX_DOL_URL_ROOT . '">Dashboard</a>'
            . '<a class="gfh-btn gfh-btn-orange gfh-btn-sm" href="' . BX_DOL_URL_ROOT . '">My Workspace</a>';
    } else {
        $sNavRight = '<a class="gfh-signin" href="' . $sLoginUrl . '">Sign in</a>'
            . '<a class="gfh-btn gfh-btn-orange gfh-btn-sm" href="' . $sJoinUrl . '">Get started</a>';
    }

    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?php echo $sTitle; ?></title>
<meta name="description" content="<?php echo bx_html_attribute($sDesc); ?>" />
<link rel="canonical" href="<?php echo bx_html_attribute($sCanonical); ?>" />
<meta property="og:title" content="<?php echo bx_html_attribute($sTitle); ?>" />
<meta property="og:description" content="<?php echo bx_html_attribute($sDesc); ?>" />
<meta property="og:type" content="website" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Sora:wght@600;700;800&family=Space+Mono:wght@400;700&display=swap" />
<link rel="stylesheet" href="<?php echo bx_html_attribute($sCssUrl1); ?>" />
<link rel="stylesheet" href="<?php echo bx_html_attribute($sCssUrl2); ?>" />
</head>
<body>
<div class="gfh gfh-js" id="gfa">
    <header class="gfh-nav" id="gfh-header">
        <div class="gfh-container gfh-nav-in">
            <a class="gfh-brand" href="<?php echo BX_DOL_URL_ROOT; ?>" aria-label="GFunnel home">
                <span class="gfh-brand-mark" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 48 48" fill="none"><path d="M24 3 42 13.5v21L24 45 6 34.5v-21L24 3z" stroke="#94A3B8" stroke-width="2.5" stroke-linejoin="round"/><path d="M6 13.5 24 24l18-10.5M24 24v21" stroke="#CBD5E1" stroke-width="1.5" stroke-linejoin="round"/><path d="m12 22 9 5v8l-9-5v-8z" fill="#EA580C"/><path d="m19 17 9 5v8l-9-5v-8z" fill="#F97316"/><path d="m26 12 9 5v8l-9-5v-8z" fill="#FB923C"/></svg></span>
                GFunnel
            </a>
            <nav class="gfh-nav-links" aria-label="Primary">
                <a href="<?php echo BX_DOL_URL_ROOT; ?>business">Businesses</a>
                <a href="<?php echo BX_DOL_URL_ROOT; ?>services">Services</a>
                <a href="<?php echo BX_DOL_URL_ROOT; ?>applications" aria-current="page">Software</a>
                <a href="<?php echo BX_DOL_URL_ROOT; ?>marketplace">Marketplace</a>
                <a href="<?php echo BX_DOL_URL_ROOT; ?>resources">Resources</a>
            </nav>
            <div class="gfh-nav-right"><?php echo $sNavRight; ?></div>
            <button class="gfh-burger" id="gfh-burger" aria-label="Menu" aria-expanded="false"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg></button>
        </div>
        <div class="gfh-mobile" id="gfh-mobile">
            <a href="<?php echo BX_DOL_URL_ROOT; ?>business">Businesses</a>
            <a href="<?php echo BX_DOL_URL_ROOT; ?>services">Services</a>
            <a href="<?php echo BX_DOL_URL_ROOT; ?>applications">Software</a>
            <a href="<?php echo BX_DOL_URL_ROOT; ?>marketplace">Marketplace</a>
            <a href="<?php echo BX_DOL_URL_ROOT; ?>resources">Resources</a>
            <a class="gfh-mobile-cta" href="<?php echo $aMember['logged'] ? BX_DOL_URL_ROOT : $sJoinUrl; ?>"><?php echo $aMember['logged'] ? 'My Workspace' : 'Get started'; ?></a>
        </div>
    </header>

    <main id="gfh-main">
<?php echo $sInner; ?>
    </main>

    <footer class="gfh-footer">
        <div class="gfh-container">
            <div class="gfh-footer-bottom">
                <div class="gfh-footer-bottom-in">
                    <span>&copy; <?php echo date('Y'); ?> GFunnel, Inc. All rights reserved.</span>
                    <nav class="gfh-footer-legal" aria-label="Legal">
                        <a href="<?php echo BX_DOL_URL_ROOT; ?>">Home</a>
                        <a href="<?php echo BX_DOL_URL_ROOT; ?>applications">Software</a>
                        <a href="<?php echo BX_DOL_URL_ROOT; ?>marketplace/applications">App Directory</a>
                        <a href="<?php echo $aMember['logged'] ? BX_DOL_URL_ROOT : $sJoinUrl; ?>"><?php echo $aMember['logged'] ? 'My Workspace' : 'Create workspace'; ?></a>
                    </nav>
                </div>
            </div>
        </div>
    </footer>

    <button class="gfh-top-btn" id="gfh-top-btn" aria-label="Back to top"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg></button>
</div>
<script>window.GFA = { logged: <?php echo $aMember['logged'] ? 'true' : 'false'; ?>, endpoint: <?php echo json_encode(BX_DOL_URL_ROOT . 'applications'); ?> };</script>
<script src="<?php echo BX_DOL_URL_ROOT; ?>template/js/gf_home.js?v=<?php echo (int)@filemtime(BX_DIRECTORY_PATH_ROOT . 'template/js/gf_home.js'); ?>"></script>
<script src="<?php echo bx_html_attribute($sJsUrl); ?>"></script>
</body>
</html>
    <?php
}

// ============================================================================
// HUB (Apps + Marketplace tabs).
// ============================================================================

function gfAppRenderHub($oDb, $sTab)
{
    $aMember = gfAppMember();
    $sAppsUrl = BX_DOL_URL_ROOT . 'applications';
    $sMktUrl = BX_DOL_URL_ROOT . 'marketplace/applications';

    // Member's saved app collection (empty for guests → client uses localStorage).
    $aMine = $aMember['logged'] ? gfUserAppIds($oDb, $aMember['id']) : array();
    $aMineSet = array_flip($aMine);

    // --- tab bar (shared) ----------------------------------------------------
    $sTabBar = gfAppTabBar($sTab, $sAppsUrl, $sMktUrl);

    if ($sTab === 'marketplace') {
        $sInner = gfAppMarketplaceTab($oDb, $sTabBar, $aMember, $aMineSet);
        $aMeta = array(
            'title' => 'App Directory — Every Tool in the GFunnel Ecosystem | ' . gfHomeOut(getParam('site_title')),
            'desc'  => 'Browse and search every app in the GFunnel ecosystem. Filter by category, open any tool, and build your own software stack.',
            'canonical' => $sMktUrl,
            'member' => $aMember,
        );
    } else {
        $sInner = gfAppAppsTab($oDb, $sTabBar, $aMember, $aMine);
        $aMeta = array(
            'title' => 'Applications — Your Ecosystem, One Hub | ' . gfHomeOut(getParam('site_title')),
            'desc'  => 'Quick access to your ecosystem tools and applications: launch apps, discover new software, and keep learning — all in one hub.',
            'canonical' => $sAppsUrl,
            'member' => $aMember,
        );
    }
    gfAppShell($sInner, $aMeta);
}

/** The Apps | Marketplace tab bar + Video Chat action. */
function gfAppTabBar($sTab, $sAppsUrl, $sMktUrl)
{
    $svgGrid = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>';
    $svgStore = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9h18l-1.5-4.5A2 2 0 0 0 17.6 3H6.4a2 2 0 0 0-1.9 1.5L3 9z"/><path d="M4 9v10a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9"/><path d="M9 22V12h6v10"/></svg>';
    $svgVideo = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 8-6 4 6 4V8z"/><rect x="2" y="6" width="14" height="12" rx="2"/></svg>';
    $svgGear = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>';

    return '<div class="gfa-tabbar">'
        . '<div class="gfa-tabs">'
        .   '<a class="gfa-tab' . ($sTab === 'apps' ? ' gfa-on' : '') . '" href="' . $sAppsUrl . '">' . $svgGrid . 'Apps</a>'
        .   '<a class="gfa-tab' . ($sTab === 'marketplace' ? ' gfa-on' : '') . '" href="' . $sMktUrl . '">' . $svgStore . 'Marketplace</a>'
        . '</div>'
        . '<div class="gfa-tabbar-actions">'
        .   '<div style="position:relative;">'
        .     '<button class="gfa-videochat" id="gfa-videochat" type="button">' . $svgVideo . '<span>Video Chat</span></button>'
        .     '<div class="gfa-vc-pop gfa-hidden" id="gfa-vc-pop" style="position:absolute;right:0;top:46px;width:266px;background:#fff;border:1px solid var(--gfh-line);border-radius:14px;box-shadow:var(--gfh-shadow-lg);padding:14px;z-index:60;">'
        .       '<div style="font-family:var(--gfh-font-head);font-weight:700;font-size:14px;color:var(--gfh-ink);">Video Chat</div>'
        .       '<div style="font-size:12px;color:var(--gfh-muted);margin-bottom:12px;">Start a meeting instantly</div>'
        .       '<a href="https://meet.google.com/new" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:10px;border:1px solid var(--gfh-line);border-radius:10px;padding:10px 12px;margin-bottom:10px;color:var(--gfh-ink);font-weight:600;font-size:13px;">📹 Google Meet</a>'
        .       '<input id="gfa-vc-input" type="url" placeholder="Paste your Zoom, Teams, or other link…" style="width:100%;border:1px solid var(--gfh-line);border-radius:9px;padding:9px 11px;font-size:13px;margin-bottom:8px;" />'
        .       '<button id="gfa-vc-save" type="button" class="gfh-btn gfh-btn-orange gfh-btn-sm" style="width:100%;">Save &amp; open</button>'
        .     '</div>'
        .   '</div>'
        .   '<a class="gfa-gear" href="' . BX_DOL_URL_ROOT . 'marketplace/applications" aria-label="Browse the App Directory" title="Browse the App Directory">' . $svgGear . '</a>'
        . '</div>'
        . '</div>';
}

/** Optional signed-in context chip. */
function gfAppContextBar($aMember)
{
    if (!$aMember['logged']) return '';
    $sAv = $aMember['initial'] !== '' ? gfDirOut($aMember['initial']) : 'G';
    $sName = $aMember['name'] !== '' ? gfDirOut($aMember['name']) : 'My Workspace';
    return '<div class="gfa-context">'
        . '<span class="gfa-chip"><span class="gfa-chip-av">' . $sAv . '</span>'
        .   '<span><span class="gfa-chip-name">' . $sName . '</span><br><span class="gfa-chip-role">owner</span></span></span>'
        . '<div class="gfa-context-actions">'
        .   '<a class="gfa-ghost" href="' . BX_DOL_URL_ROOT . 'applications#gfa-core"><span>My Software</span></a>'
        .   '<a class="gfa-ghost" href="' . BX_DOL_URL_ROOT . 'marketplace/applications"><span>Browse Apps</span></a>'
        . '</div></div>';
}

/** Admin-only toolbar: one-click "pull the directory from Supabase". */
function gfAppAdminBar($aMember)
{
    if (empty($aMember['admin'])) return '';
    $sSync = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>';
    return '<div class="gfa-adminbar">'
        . '<span class="gfa-adminbar-label">Admin</span>'
        . '<button type="button" class="gfa-ghost" id="gfa-sync">' . $sSync . '<span>Sync directory from Supabase</span></button>'
        . '<span class="gfa-adminbar-status" id="gfa-sync-status"></span>'
        . '</div>';
}

/** Apps tab: welcome hero + Core Applications icon grid + hub cards. */
function gfAppAppsTab($oDb, $sTabBar, $aMember, $aMine = array())
{
    // Icons: a member's own saved apps (in their order) come first; otherwise
    // the featured set. Cap for a tidy launcher.
    $bMine = false;
    $aApps = array();
    if (!empty($aMine)) {
        $aIds = array_slice($aMine, 0, 40);
        $aPh = implode(',', array_fill(0, count($aIds), '?'));
        $aRows = $oDb->getAll($oDb->prepare("SELECT `id`,`name`,`slug`,`logo_url` FROM `gf_directory_apps` WHERE `id` IN ($aPh)", ...$aIds));
        if (is_array($aRows) && $aRows) {
            $aById = array();
            foreach ($aRows as $r) $aById[(string)$r['id']] = $r;
            foreach ($aMine as $sId) if (isset($aById[$sId])) $aApps[] = $aById[$sId]; // preserve member order
            $bMine = true;
        }
    }
    if (empty($aApps)) {
        $aApps = $oDb->getAll("SELECT `id`,`name`,`slug`,`logo_url` FROM `gf_directory_apps` ORDER BY `is_featured` DESC, `name` LIMIT 23");
        if (!is_array($aApps)) $aApps = array();
    }
    $sCoreSub = $bMine ? 'Your apps — launch, add, or manage them anytime.' : 'Quick Access to Your Ecosystem Tools and Applications.';

    // --- welcome hero --------------------------------------------------------
    $sGetStarted = $aMember['logged'] ? (BX_DOL_URL_ROOT . 'welcome') : (BX_DOL_URL_ROOT . 'create-account');
    $sSlides = '';
    for ($i = 1; $i <= 4; $i++) {
        $sImg = BX_DOL_URL_ROOT . 'template/images/gf_app_banner_' . $i . '.jpg';
        $sSlides .= '<div class="gfa-hero-slide' . ($i === 1 ? ' gfa-on' : '') . '" style="background-image:url(\'' . bx_html_attribute($sImg) . '\')"></div>';
    }
    $sDots = '';
    for ($i = 0; $i < 4; $i++)
        $sDots .= '<button class="gfa-hero-dot' . ($i === 0 ? ' gfa-on' : '') . '" type="button" aria-label="Slide ' . ($i + 1) . '"></button>';

    $sMySoftware = $aMember['logged']
        ? '<a class="gfh-btn gfh-btn-sm gfa-hero-glass" href="#gfa-core">My Software <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></a>'
        : '';

    $sHero = '<section class="gfa-hero" id="gfa-hero">'
        . '<div class="gfa-hero-slides">' . $sSlides . '</div>'
        . '<div class="gfa-hero-scrim"></div>'
        . '<div class="gfa-hero-inner">'
        .   '<h1 class="gfa-hero-title">Welcome!</h1>'
        .   '<p class="gfa-hero-date" id="gfa-hero-date">Your ecosystem, all in one place.</p>'
        .   '<div class="gfa-hero-ctas">'
        .     '<a class="gfh-btn gfh-btn-orange gfh-btn-sm" href="' . $sGetStarted . '">Getting Started <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></a>'
        .     $sMySoftware
        .   '</div>'
        . '</div>'
        . '<div class="gfa-hero-dots">' . $sDots . '</div>'
        . '</section>';

    // --- Core Applications ---------------------------------------------------
    $sGridIcon = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>';

    $sIcons = '<a class="gfa-icon gfa-icon-add" href="' . BX_DOL_URL_ROOT . 'marketplace/applications">'
        . '<span class="gfa-icon-tile"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg></span>'
        . '<span class="gfa-icon-label">Add App</span></a>';
    foreach ($aApps as $a) {
        $sIni = gfDirInitial($a['name']);
        $sImg = gfDirImg($a['logo_url']);
        $sInner = $sImg !== '' ? $sImg : '<span class="gfa-icon-ini">' . gfDirOut($sIni) . '</span>';
        $sIcons .= '<a class="gfa-icon" href="' . bx_html_attribute(gfDirUrl($a)) . '" title="' . bx_html_attribute($a['name']) . '">'
            . '<span class="gfa-icon-tile gfa-noimg-fallback" data-initial="' . bx_html_attribute($sIni) . '">' . $sInner . '</span>'
            . '<span class="gfa-icon-label">' . gfDirOut($a['name']) . '</span></a>';
    }
    if (empty($aApps)) {
        // Empty mirror: still show the Add tile + a hint.
        $sIcons .= '<div style="grid-column:2/-1;align-self:center;color:var(--gfh-muted);font-size:13.5px;">Your apps will appear here once the directory sync runs. <a class="gfh-link-more" href="' . BX_DOL_URL_ROOT . 'marketplace/applications">Browse the directory →</a></div>';
    }

    $sCore = '<section class="gfa-sec" id="gfa-core">'
        . '<div class="gfa-sec-head"><span class="gfa-sec-ico">' . $sGridIcon . '</span>'
        .   '<div><div class="gfa-sec-title">Core Applications</div>'
        .   '<div class="gfa-sec-sub">' . gfDirOut($sCoreSub) . '</div></div></div>'
        . '<div class="gfa-grid">' . $sIcons . '</div>'
        . '</section>';

    // --- hub cards -----------------------------------------------------------
    $sCore .= gfAppHubCards();

    // --- scroll indicator ----------------------------------------------------
    $sCore .= '<div class="gfa-scroll"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7 6 5 5 5-5"/><path d="m7 13 5 5 5-5"/></svg><span>Scroll to discover more</span></div>';

    return '<section class="gfa-main"><div class="gfh-container">'
        . gfAppContextBar($aMember)
        . gfAppAdminBar($aMember)
        . $sHero
        . $sTabBar
        . $sCore
        . '</div></section>';
}

/** The three Learning / Software / Help hub cards. */
function gfAppHubCards()
{
    $arrow = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
    $icoBook = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>';
    $icoBrief = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>';
    $icoUsers = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>';

    $card = function ($sMod, $sIco, $sTitle, $sDesc, $sCta, $sHref) {
        return '<a class="gfa-hub gfa-hub-' . $sMod . '" href="' . $sHref . '">'
            . '<span class="gfa-hub-ico">' . $sIco . '</span>'
            . '<div class="gfa-hub-title">' . $sTitle . '</div>'
            . '<div class="gfa-hub-desc">' . $sDesc . '</div>'
            . '<span class="gfa-hub-cta">' . $sCta . '</span></a>';
    };

    return '<section class="gfa-sec"><div class="gfa-hubs">'
        . $card('learn', $icoBook, 'Learning Hub', 'Boost your skills with curated courses and expert-led tutorials.', 'Start Learning ' . $arrow, BX_DOL_URL_ROOT . 'resources')
        . $card('soft', $icoBrief, 'Software Hub', 'Build your own software, request a custom build, or schedule a discovery call.', 'Explore ' . $arrow, BX_DOL_URL_ROOT . 'marketplace')
        . $card('help', $icoUsers, 'Help Center', 'Get support, find answers, and connect with our team.', 'Get Help ' . $arrow, BX_DOL_URL_ROOT . 'services')
        . '</div></section>';
}

/** Marketplace tab: full App Directory (search + category chips + grid + pager). */
function gfAppMarketplaceTab($oDb, $sTabBar, $aMember, $aMineSet = array())
{
    $sSelf = BX_DOL_URL_ROOT . 'marketplace/applications';

    // params
    $sQ = trim((string)bx_get('q'));
    if (strlen($sQ) > 100) $sQ = substr($sQ, 0, 100);
    $sCat = trim((string)bx_get('cat'));
    if (strlen($sCat) > 191) $sCat = '';
    $iPerPage = 48;
    $iPage = max(1, (int)bx_get('p'));
    $iOffset = ($iPage - 1) * $iPerPage;

    $sWhere = '1=1';
    $aBind = array();
    if ($sQ !== '') { $sWhere .= " AND (`name` LIKE ? OR `description` LIKE ?)"; $aBind[] = '%' . $sQ . '%'; $aBind[] = '%' . $sQ . '%'; }
    if ($sCat === '__featured') { $sWhere .= " AND `is_featured` = 1"; }
    elseif ($sCat !== '') { $sWhere .= " AND `category` = ?"; $aBind[] = $sCat; }

    $sCountSql = "SELECT COUNT(*) FROM `gf_directory_apps` WHERE $sWhere";
    $iTotal = (int)($aBind ? $oDb->getOne($oDb->prepare($sCountSql, ...$aBind)) : $oDb->getOne($sCountSql));
    $iPages = max(1, (int)ceil($iTotal / $iPerPage));
    if ($iPage > $iPages) { $iPage = $iPages; $iOffset = ($iPage - 1) * $iPerPage; }

    $sListSql = "SELECT `id`,`name`,`slug`,`description`,`logo_url`,`app_url`,`category`,`access_type`,`is_featured`,`is_gfunnel_native` "
        . "FROM `gf_directory_apps` WHERE $sWhere ORDER BY `is_featured` DESC, `name` LIMIT " . (int)$iOffset . ", " . (int)$iPerPage;
    $aRows = $aBind ? $oDb->getAll($oDb->prepare($sListSql, ...$aBind)) : $oDb->getAll($sListSql);
    if (!is_array($aRows)) $aRows = array();

    $aCats = $oDb->getColumn("SELECT `category` FROM `gf_directory_apps` WHERE `category` IS NOT NULL AND `category` <> '' GROUP BY `category` ORDER BY `category` LIMIT 40");
    if (!is_array($aCats)) $aCats = array();

    // page-url builder (preserves q + cat)
    $fnUrl = function ($aOver = array()) use ($sSelf, $sQ, $sCat) {
        $aP = array();
        $q = isset($aOver['q']) ? $aOver['q'] : $sQ;
        $c = array_key_exists('cat', $aOver) ? $aOver['cat'] : $sCat;
        $p = isset($aOver['p']) ? (int)$aOver['p'] : 0;
        if ($q !== '') $aP[] = 'q=' . rawurlencode($q);
        if ($c !== '') $aP[] = 'cat=' . rawurlencode($c);
        if ($p > 1) $aP[] = 'p=' . $p;
        return $sSelf . (empty($aP) ? '' : '?' . implode('&', $aP));
    };

    // --- category chips ------------------------------------------------------
    $sChips = '<a class="gfh-appd-chip' . ($sCat === '' ? ' gfh-on' : '') . '" href="' . $fnUrl(array('cat' => '')) . '">All</a>'
        . '<a class="gfh-appd-chip' . ($sCat === '__featured' ? ' gfh-on' : '') . '" href="' . $fnUrl(array('cat' => '__featured')) . '">⭐ Popular</a>';
    foreach ($aCats as $c)
        $sChips .= '<a class="gfh-appd-chip' . ($sCat === $c ? ' gfh-on' : '') . '" href="' . $fnUrl(array('cat' => $c)) . '">' . gfDirOut($c) . '</a>';

    // --- cards ---------------------------------------------------------------
    $extSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>';
    $plusSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>';

    // "Add New App" dashed card (first cell)
    $sAddHref = $aMember['logged'] ? (BX_DOL_URL_ROOT . 'services') : gfHomeUrl('create-account');
    $sCards = '<a class="gfh-appd-card gfh-appd-add" href="' . $sAddHref . '">'
        . '<div class="gfh-appd-head"><span class="gfh-appd-logo gfh-appd-plus">+</span>'
        .   '<span class="gfh-appd-name">Add New App</span></div>'
        . '<p class="gfh-appd-desc">Add an existing app, your own software, or request a custom build.</p></a>';

    foreach ($aRows as $a) {
        $sName = trim((string)$a['name']);
        if ($sName === '') continue;
        $sHref = gfDirUrl($a);
        $sIni = gfDirInitial($sName);
        $sImg = gfDirImg($a['logo_url']);
        $sLogoInner = $sImg !== '' ? $sImg : '<span class="gfh-appd-ini">' . gfDirOut($sIni) . '</span>';
        $sDesc = trim((string)$a['description']);
        if (function_exists('mb_strlen') && mb_strlen($sDesc) > 150) $sDesc = mb_substr($sDesc, 0, 150) . '…';
        $sCatRaw = trim((string)$a['category']);
        $sSearch = strtolower($sName . ' ' . $sDesc . ' ' . $sCatRaw);

        // badge: GFunnel native > Popular(featured) > paid
        $sBadge = '';
        if ((int)$a['is_gfunnel_native'] === 1) $sBadge = '<span class="gfh-appd-badge">GFunnel</span>';
        elseif ((int)$a['is_featured'] === 1) $sBadge = '<span class="gfh-appd-badge gfa-appd-badge-pop">Popular</span>';
        elseif ($a['access_type'] !== '' && strtolower((string)$a['access_type']) !== 'free') $sBadge = '<span class="gfh-appd-badge gfa-appd-badge-paid">' . gfDirOut($a['access_type']) . '</span>';

        // external open button (only if a real app_url)
        $sAppUrl = trim((string)$a['app_url']);
        $sExt = ($sAppUrl !== '' && preg_match('#^https?://#i', $sAppUrl))
            ? '<a class="gfa-appd-iconbtn" href="' . bx_html_attribute($sAppUrl) . '" target="_blank" rel="noopener nofollow" title="Visit site" onclick="event.stopPropagation()">' . $extSvg . '</a>'
            : '';

        // reflect the member's saved collection server-side
        $bAdded = isset($aMineSet[(string)$a['id']]);
        $sAddInner = $bAdded
            ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>'
            : $plusSvg;

        $sCards .= '<div class="gfh-appd-card gfa-appd-card' . ($bAdded ? ' gfa-added' : '') . '" data-search="' . bx_html_attribute($sSearch) . '">'
            . '<div class="gfh-appd-head">'
            .   '<a class="gfh-appd-logo gfa-noimg-fallback" href="' . bx_html_attribute($sHref) . '" data-initial="' . bx_html_attribute($sIni) . '">' . $sLogoInner . '</a>'
            .   '<a class="gfh-appd-name" href="' . bx_html_attribute($sHref) . '">' . gfDirOut($sName) . '</a>'
            .   $sBadge
            .   '<span class="gfa-appd-actions">' . $sExt
            .     '<button class="gfa-appd-add-btn" type="button" data-app="' . bx_html_attribute($a['id']) . '" aria-pressed="' . ($bAdded ? 'true' : 'false') . '" title="' . ($bAdded ? 'Remove from My Apps' : 'Add to My Apps') . '">' . $sAddInner . '</button>'
            .   '</span>'
            . '</div>'
            . ($sDesc !== '' ? '<p class="gfh-appd-desc">' . gfDirOut($sDesc) . '</p>' : '<p class="gfh-appd-desc" style="color:var(--gfh-muted)">' . gfDirOut($sCatRaw) . '</p>')
            . '</div>';
    }

    $sEmptyServer = '';
    if (empty($aRows)) {
        $sEmptyServer = '<div class="gfa-dir-empty-js" style="display:block">'
            . ($iTotal === 0 && $sQ === '' && $sCat === ''
                ? 'No directory entries yet. Apps appear here once the Supabase sync runs.'
                : 'No apps match your search. <a class="gfh-link-more" href="' . $sSelf . '">Clear filters →</a>')
            . '</div>';
    }

    // --- pager ---------------------------------------------------------------
    $sPager = '';
    if ($iPages > 1) {
        $sPager .= '<nav class="gfh-pager" aria-label="Pagination">';
        if ($iPage > 1) $sPager .= '<a class="gfh-pager-btn" href="' . $fnUrl(array('p' => $iPage - 1)) . '" rel="prev">&larr; Prev</a>';
        $iFrom = max(1, $iPage - 3); $iTo = min($iPages, $iPage + 3);
        if ($iFrom > 1) $sPager .= '<a class="gfh-pager-num" href="' . $fnUrl(array('p' => 1)) . '">1</a>' . ($iFrom > 2 ? '<span class="gfh-pager-gap">&hellip;</span>' : '');
        for ($i = $iFrom; $i <= $iTo; $i++)
            $sPager .= '<a class="gfh-pager-num' . ($i == $iPage ? ' gfh-on' : '') . '" href="' . $fnUrl(array('p' => $i)) . '">' . $i . '</a>';
        if ($iTo < $iPages) $sPager .= ($iTo < $iPages - 1 ? '<span class="gfh-pager-gap">&hellip;</span>' : '') . '<a class="gfh-pager-num" href="' . $fnUrl(array('p' => $iPages)) . '">' . $iPages . '</a>';
        if ($iPage < $iPages) $sPager .= '<a class="gfh-pager-btn" href="' . $fnUrl(array('p' => $iPage + 1)) . '" rel="next">Next &rarr;</a>';
        $sPager .= '</nav>';
    }

    // --- count line ----------------------------------------------------------
    $iShownFrom = $iTotal ? $iOffset + 1 : 0;
    $iShownTo = min($iOffset + $iPerPage, $iTotal);
    $sCountLine = $sQ !== ''
        ? number_format($iTotal) . ' result' . ($iTotal == 1 ? '' : 's') . ' for &ldquo;' . gfDirOut($sQ) . '&rdquo;'
        : 'Showing ' . number_format($iShownFrom) . '&ndash;' . number_format($iShownTo) . ' of ' . number_format($iTotal) . ' apps';

    $searchSvg = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>';

    return '<section class="gfa-main"><div class="gfh-container">'
        . gfAppContextBar($aMember)
        . gfAppAdminBar($aMember)
        . $sTabBar
        . '<div class="gfa-sec" style="margin-bottom:0">'
        .   '<div class="gfh-appd-bar">'
        .     '<h2 class="gfh-appd-title">App Directory</h2>'
        .     '<form class="gfh-biz-search" action="' . $sSelf . '" method="get" role="search" style="margin:0;max-width:300px;">'
        .       $searchSvg
        .       '<input id="gfa-dir-search" type="text" name="q" value="' . bx_html_attribute($sQ) . '" placeholder="Search apps..." autocomplete="off" aria-label="Search apps" />'
        .       ($sCat !== '' ? '<input type="hidden" name="cat" value="' . bx_html_attribute($sCat) . '" />' : '')
        .     '</form>'
        .   '</div>'
        .   '<div class="gfh-appd-chips">' . $sChips . '</div>'
        .   '<div class="gfh-dir-count" style="margin-bottom:16px;color:var(--gfh-muted);font-size:13.5px;">' . $sCountLine . '</div>'
        .   '<div class="gfh-appd-grid" id="gfa-dir-grid">' . $sCards
        .     '<div class="gfa-dir-empty-js" id="gfa-dir-empty" style="display:none">No apps match your search.</div>'
        .     $sEmptyServer
        .   '</div>'
        .   $sPager
        . '</div>'
        . '</div></section>';
}

// ============================================================================
// DETAIL (per-app page) — reskinned to the shared design system.
// ============================================================================

function gfAppRenderDetail($oDb, $sSlug)
{
    $aApp = $oDb->getRow($oDb->prepare("SELECT * FROM `gf_directory_apps` WHERE `slug` = ? LIMIT 1", $sSlug));
    if (!$aApp)
        $aApp = $oDb->getRow($oDb->prepare("SELECT * FROM `gf_directory_apps` WHERE `id` = ? LIMIT 1", $sSlug));

    $sPid = $aApp ? (string)$aApp['platform_app_id'] : '';
    $aRich = null;
    if ($sPid !== '')
        $aRich = $oDb->getRow($oDb->prepare("SELECT * FROM `gf_platform_apps` WHERE `id` = ? LIMIT 1", $sPid));
    if (!$aRich)
        $aRich = $oDb->getRow($oDb->prepare("SELECT * FROM `gf_platform_apps` WHERE `slug` = ? LIMIT 1", $sSlug));

    if (!$aApp && !$aRich) {
        BxDolTemplate::getInstance()->displayPageNotFound();
        return;
    }
    if ($sPid === '' && $aRich) $sPid = (string)$aRich['id'];

    $get = function ($k, $default = '') use ($aApp, $aRich) {
        if ($aApp && isset($aApp[$k]) && $aApp[$k] !== '' && $aApp[$k] !== null) return $aApp[$k];
        if ($aRich && isset($aRich[$k]) && $aRich[$k] !== '' && $aRich[$k] !== null) return $aRich[$k];
        return $default;
    };

    $sName = gfDirOut($get('name'));
    $sTagline = gfDirOut($aRich ? $aRich['tagline'] : '');
    $sAbout = $aRich ? ($aRich['gfunnel_description'] ?: $aRich['description']) : $get('description');
    $sLogo = trim((string)$get('logo_url'));
    $sAppUrl = trim((string)$get('app_url'));
    $aCats = $aRich ? gfDirJson($aRich['categories']) : array();
    $aDepts = $aRich ? gfDirJson($aRich['departments']) : array();
    $aUse = $aRich ? gfDirJson($aRich['gfunnel_use_cases']) : array();
    $aAuto = $aRich ? gfDirJson($aRich['automation_ideas']) : array();
    $aRelated = $aRich ? gfDirJson($aRich['related_app_slugs']) : array();

    $aTut = $sPid !== '' ? $oDb->getAll($oDb->prepare("SELECT * FROM `gf_app_tutorials` WHERE `platform_app_id` = ? ORDER BY `view_count` DESC LIMIT 12", $sPid)) : array();
    $aHelp = $sPid !== '' ? $oDb->getAll($oDb->prepare("SELECT * FROM `gf_app_help_articles` WHERE `platform_app_id` = ? LIMIT 24", $sPid)) : array();
    $aDocs = $sPid !== '' ? $oDb->getAll($oDb->prepare("SELECT * FROM `gf_app_docs` WHERE `platform_app_id` = ? ORDER BY `doc_type` LIMIT 24", $sPid)) : array();
    if (!is_array($aTut)) $aTut = array();
    if (!is_array($aHelp)) $aHelp = array();
    if (!is_array($aDocs)) $aDocs = array();

    $sIni = gfDirInitial($get('name'));
    $sImg = gfDirImg($sLogo);
    $sLogoInner = $sImg !== '' ? $sImg : '<span class="gfa-icon-ini" style="font-size:30px">' . gfDirOut($sIni) . '</span>';

    $fnList = function ($aItems) {
        $s = '';
        foreach ($aItems as $v) { $v = trim((string)$v); if ($v !== '') $s .= '<li>' . gfDirOut($v) . '</li>'; }
        return $s !== '' ? '<ul class="gfd-list">' . $s . '</ul>' : '';
    };
    $fnChips = function ($aItems) {
        $s = '';
        foreach ($aItems as $v) { $v = trim((string)$v); if ($v !== '') $s .= '<span class="gfh-appd-chip" style="pointer-events:none">' . gfDirOut($v) . '</span>'; }
        return $s;
    };

    // --- build inner ---------------------------------------------------------
    $s = '<section class="gfa-main"><div class="gfh-container" style="max-width:1040px">';
    $s .= '<a class="gfh-link-more" style="margin-bottom:18px" href="' . BX_DOL_URL_ROOT . 'marketplace/applications">&larr; All apps</a>';

    // detail hero
    $s .= '<div style="display:flex;gap:20px;align-items:center;margin:14px 0 8px;flex-wrap:wrap">'
        . '<span class="gfa-icon-tile gfa-noimg-fallback" data-initial="' . bx_html_attribute($sIni) . '" style="width:76px;height:76px;border-radius:18px">' . $sLogoInner . '</span>'
        . '<div style="min-width:0"><h1 class="gfh-appd-title" style="font-size:30px">' . ($sName !== '' ? $sName : 'App') . '</h1>'
        . ($sTagline !== '' ? '<div class="gfh-sub" style="margin-top:4px">' . $sTagline . '</div>' : '')
        . '</div></div>';
    if ($sAppUrl !== '' && preg_match('#^https?://#i', $sAppUrl))
        $s .= '<a class="gfh-btn gfh-btn-orange gfh-btn-sm" style="margin:6px 0 8px" href="' . bx_html_attribute($sAppUrl) . '" target="_blank" rel="noopener nofollow">Visit site ↗</a>';

    $s .= '<div class="gfd-cols" style="display:grid;grid-template-columns:1fr 300px;gap:28px;margin:24px 0 10px">';
    // main col
    $s .= '<div>';
    if (trim((string)$sAbout) !== '')
        $s .= '<div class="gfd-block"><h2 class="gfh-eyebrow">About</h2><div style="font-size:15px;line-height:1.7;color:var(--gfh-slate)">' . gfDirOut($sAbout) . '</div></div>';
    if ($sUse = $fnList($aUse))
        $s .= '<div class="gfd-block" style="margin-top:22px"><h2 class="gfh-eyebrow">Use cases in GFunnel</h2>' . $sUse . '</div>';
    if ($sAuto = $fnList($aAuto))
        $s .= '<div class="gfd-block" style="margin-top:22px"><h2 class="gfh-eyebrow">Automation ideas</h2>' . $sAuto . '</div>';
    if (!empty($aTut)) {
        $sTut = '';
        foreach ($aTut as $t) {
            $sVid = trim((string)$t['youtube_video_id']);
            $sThumb = trim((string)$t['thumbnail_url']);
            if ($sThumb === '' && $sVid !== '') $sThumb = 'https://i.ytimg.com/vi/' . rawurlencode($sVid) . '/hqdefault.jpg';
            $sHref = $sVid !== '' ? 'https://www.youtube.com/watch?v=' . rawurlencode($sVid) : $sThumb;
            $sTut .= '<a href="' . bx_html_attribute($sHref) . '" target="_blank" rel="noopener nofollow" class="gfh-appd-card" style="padding:0;overflow:hidden">'
                . '<div style="aspect-ratio:16/9;background:#F4F6FA center/cover;background-image:url(\'' . bx_html_attribute($sThumb) . '\')"></div>'
                . '<div style="padding:10px 12px;font-size:13px;line-height:1.35">' . gfDirOut($t['title']) . '</div></a>';
        }
        $s .= '<div class="gfd-block" style="margin-top:22px"><h2 class="gfh-eyebrow">Tutorials</h2><div class="gfh-appd-grid">' . $sTut . '</div></div>';
    }
    if (!empty($aHelp)) {
        $sH = '';
        foreach ($aHelp as $h) {
            $sU = trim((string)$h['url']);
            $sInner = gfDirOut($h['title']);
            $sH .= ($sU !== '' && preg_match('#^https?://#i', $sU))
                ? '<a class="gfd-link" href="' . bx_html_attribute($sU) . '" target="_blank" rel="noopener nofollow">' . $sInner . '</a>'
                : '<div class="gfd-link">' . $sInner . '</div>';
        }
        $s .= '<div class="gfd-block" style="margin-top:22px"><h2 class="gfh-eyebrow">Help articles</h2>' . $sH . '</div>';
    }
    $s .= '</div>';

    // side col
    $s .= '<div>';
    if ($sCatsChips = $fnChips($aCats))
        $s .= '<div class="gfd-block"><h2 class="gfh-eyebrow">Categories</h2><div class="gfh-appd-chips">' . $sCatsChips . '</div></div>';
    if ($sDeptChips = $fnChips($aDepts))
        $s .= '<div class="gfd-block" style="margin-top:18px"><h2 class="gfh-eyebrow">Departments</h2><div class="gfh-appd-chips">' . $sDeptChips . '</div></div>';
    if (!empty($aDocs)) {
        $sD = '';
        foreach ($aDocs as $d) {
            $sU = trim((string)$d['url']);
            if ($sU === '' || !preg_match('#^https?://#i', $sU)) continue;
            $sLabel = gfDirOut($d['title'] ?: ($d['doc_type'] ?: 'Documentation'));
            $sD .= '<a class="gfd-link" href="' . bx_html_attribute($sU) . '" target="_blank" rel="noopener nofollow">' . $sLabel . '</a>';
        }
        if ($sD !== '') $s .= '<div class="gfd-block" style="margin-top:18px"><h2 class="gfh-eyebrow">Docs &amp; API</h2>' . $sD . '</div>';
    }
    if (!empty($aRelated)) {
        $sR = '';
        foreach ($aRelated as $sRel) {
            $sRel = trim((string)$sRel);
            if ($sRel === '') continue;
            $sR .= '<a class="gfh-appd-chip" href="' . BX_DOL_URL_ROOT . 'application/' . rawurlencode($sRel) . '">' . gfDirOut($sRel) . '</a>';
        }
        if ($sR !== '') $s .= '<div class="gfd-block" style="margin-top:18px"><h2 class="gfh-eyebrow">Related</h2><div class="gfh-appd-chips">' . $sR . '</div></div>';
    }
    $s .= '</div>';

    $s .= '</div></div></section>';

    // minimal detail-only styles (reuses gfd-list / gfd-link from the block)
    $s .= '<style>'
        . '.gfd-list{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px}'
        . '.gfd-list li{padding:12px 14px;border:1px solid var(--gfh-line);border-radius:10px;background:var(--gfh-card);font-size:14px;line-height:1.5}'
        . '.gfd-list li::before{content:"▸ ";color:var(--gfh-orange-600)}'
        . '.gfd-link{display:block;padding:11px 14px;border:1px solid var(--gfh-line);border-radius:10px;background:var(--gfh-card);font-size:14px;margin-bottom:8px;color:var(--gfh-ink)}'
        . '.gfd-link:hover{border-color:var(--gfh-orange)}'
        . '.gfd-block h2{margin-bottom:12px}'
        . '@media(max-width:820px){.gfd-cols{grid-template-columns:1fr !important}}'
        . '</style>';

    $aMeta = array(
        'title' => ($sName !== '' ? $sName : 'App') . ' — App Directory | ' . gfHomeOut(getParam('site_title')),
        'desc'  => $sTagline !== '' ? $sTagline : ('Learn how ' . ($sName !== '' ? strip_tags($sName) : 'this app') . ' works inside the GFunnel ecosystem.'),
        'canonical' => gfDirUrl($aApp ?: array('slug' => $sSlug, 'id' => $sSlug)),
        'member' => gfAppMember(),
    );
    gfAppShell($s, $aMeta);
}
