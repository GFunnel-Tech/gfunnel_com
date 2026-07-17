<?php
/**
 * GFunnel — Directory (app directory: list + per-app detail).
 *
 * Star-Head-style application directory rendered standalone (dark theme), the
 * same self-contained pattern as home.php / splash.php.
 *
 *   /applications         -> searchable grid of apps  (gf_directory_apps)
 *   /application/<slug>   -> per-app detail page       (gf_platform_apps + children)
 *
 * DATA FLOW (see docs/directory-sync-runbook.md):
 *   Supabase Postgres  = source of truth
 *        │  row change -> Database Webhook
 *        ▼
 *   Edge Function sync-directory-apps-to-mysql  = owned one-way push (PG -> MySQL)
 *        │
 *        ▼
 *   MySQL mirrors  <-- THIS PAGE READS THEM (via UNA's own BxDolDb connection)
 *     gf_directory_apps      (list)          <- public.directory_apps
 *     gf_platform_apps       (rich detail)   <- public.platform_apps
 *     gf_app_tutorials       (detail)        <- public.app_tutorials
 *     gf_app_docs            (detail)        <- public.app_docs
 *     gf_app_help_articles   (detail)        <- public.app_help_articles
 *
 * No Supabase creds live here; mirror tables auto-create on first load so the
 * page renders cleanly (empty state) before the first sync runs.
 *
 * Routes wired in r.php. Setting `gf_applications = off` (sys_options) disables it.
 */

require_once('./inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_INC . "design.inc.php");

bx_import('BxDolLanguages');

if(getParam('gf_applications') == 'off') {
    BxDolTemplate::getInstance()->displayPageNotFound();
    exit;
}

$oDb = BxDolDb::getInstance();
gfDirEnsureTables($oDb);

$sSlug = isset($GLOBALS['gf_app_slug']) ? trim((string)$GLOBALS['gf_app_slug'], '/') : '';
if($sSlug === '' && bx_get('app') !== false)
    $sSlug = trim((string)bx_get('app'));

if($sSlug !== '')
    gfDirRenderDetail($oDb, $sSlug);
else
    gfDirRenderList($oDb);

exit;

// ----------------------------------------------------------------------------

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

/** Safe HTML text. */
function gfDirOut($s) { return bx_process_output((string)$s); }

/** Decode a JSON-array text column into a PHP array of strings. */
function gfDirJson($s)
{
    if($s === null || $s === '') return [];
    $a = json_decode((string)$s, true);
    return is_array($a) ? $a : [];
}

/** Internal detail URL for an app (by slug, falling back to id). */
function gfDirUrl($aApp)
{
    $sSlug = trim((string)$aApp['slug']);
    if($sSlug === '') $sSlug = (string)$aApp['id'];
    return BX_DOL_URL_ROOT . 'application/' . rawurlencode($sSlug);
}

/** Shared page chrome. */
function gfDirCss()
{
    return <<<CSS
<style>
:root{--bg:#0b0d10;--panel:#12151a;--panel2:#161a20;--line:#232833;--txt:#e7ebf0;--muted:#8a93a2;--accent:#22d3ee;--accent2:#0ea5b7}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--txt);font-family:'DM Sans',system-ui,-apple-system,Segoe UI,Roboto,sans-serif}
a{color:inherit;text-decoration:none}
.gfd-wrap{max-width:1200px;margin:0 auto;padding:0 20px}
.gfd-hero{padding:56px 0 24px;text-align:center}
.gfd-hero h1{font-size:40px;margin:0 0 10px;letter-spacing:-.5px}
.gfd-hero p{color:var(--muted);font-size:17px;margin:0 auto;max-width:560px}
.gfd-search{margin:26px auto 0;max-width:640px;position:relative}
.gfd-search input{width:100%;padding:15px 18px;border-radius:12px;border:1px solid var(--line);background:var(--panel);color:var(--txt);font-size:15px;outline:none}
.gfd-search input:focus{border-color:var(--accent2)}
.gfd-count{color:var(--muted);font-size:13px;margin-top:10px}
.gfd-section{margin:34px 0 10px;font-size:13px;letter-spacing:.12em;text-transform:uppercase;color:var(--muted)}
.gfd-chips{display:flex;flex-wrap:wrap;gap:8px;margin:18px 0 8px}
.gfd-chip{padding:7px 14px;border-radius:999px;border:1px solid var(--line);background:var(--panel);color:var(--muted);font-size:13px}
.gfd-chip:hover{color:var(--txt);border-color:var(--accent2)}
.gfd-chip-on{background:var(--accent);color:#04212a;border-color:var(--accent);font-weight:600}
.gfd-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px;margin:16px 0 60px}
.gfd-card{display:flex;gap:12px;padding:16px;border-radius:14px;border:1px solid var(--line);background:var(--panel);transition:.15s}
.gfd-card:hover{border-color:var(--accent2);background:var(--panel2);transform:translateY(-2px)}
.gfd-logo{flex:0 0 44px;width:44px;height:44px;border-radius:10px;background:var(--panel2);display:flex;align-items:center;justify-content:center;overflow:hidden;border:1px solid var(--line)}
.gfd-logo img{width:100%;height:100%;object-fit:contain}
.gfd-logo.gfd-noimg::before,.gfd-noimg-fallback::before{content:attr(data-initial);font-weight:700;color:var(--accent);font-size:18px}
.gfd-logo img+*{display:none}
.gfd-body{min-width:0}
.gfd-name{font-weight:600;font-size:15px;line-height:1.25;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gfd-cat{color:var(--accent);font-size:11px;text-transform:uppercase;letter-spacing:.06em;margin:3px 0 6px}
.gfd-desc{color:var(--muted);font-size:13px;line-height:1.4}
.gfd-badges{margin-top:9px;display:flex;gap:6px;flex-wrap:wrap}
.gfd-badge{font-size:10px;padding:2px 8px;border-radius:999px;background:var(--panel2);border:1px solid var(--line);color:var(--muted);text-transform:capitalize}
.gfd-badge-native{background:rgba(34,211,238,.12);border-color:rgba(34,211,238,.35);color:var(--accent)}
.gfd-empty{grid-column:1/-1;text-align:center;color:var(--muted);padding:60px 20px;border:1px dashed var(--line);border-radius:14px}
.gfd-foot{border-top:1px solid var(--line);padding:26px 0;color:var(--muted);font-size:13px;text-align:center}
/* detail */
.gfd-back{display:inline-block;margin:26px 0 0;color:var(--muted);font-size:14px}
.gfd-back:hover{color:var(--accent)}
.gfd-dhero{display:flex;gap:20px;align-items:center;padding:22px 0 8px}
.gfd-dlogo{flex:0 0 76px;width:76px;height:76px;border-radius:16px;background:var(--panel2);border:1px solid var(--line);display:flex;align-items:center;justify-content:center;overflow:hidden}
.gfd-dlogo img{width:100%;height:100%;object-fit:contain}
.gfd-dlogo.gfd-noimg::before,.gfd-dlogo.gfd-noimg-fallback::before{content:attr(data-initial);font-weight:700;color:var(--accent);font-size:30px}
.gfd-dtitle h1{margin:0;font-size:30px;letter-spacing:-.4px}
.gfd-dtag{color:var(--muted);font-size:16px;margin-top:6px}
.gfd-open{display:inline-block;margin:14px 0 0;padding:11px 20px;border-radius:10px;background:var(--accent);color:#04212a;font-weight:600;font-size:14px}
.gfd-open:hover{background:#67e8f9}
.gfd-cols{display:grid;grid-template-columns:1fr 300px;gap:28px;margin:24px 0 60px}
@media(max-width:820px){.gfd-cols{grid-template-columns:1fr}}
.gfd-block{margin:0 0 26px}
.gfd-block h2{font-size:14px;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin:0 0 12px}
.gfd-prose{color:var(--txt);font-size:15px;line-height:1.7}
.gfd-list{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px}
.gfd-list li{padding:12px 14px;border:1px solid var(--line);border-radius:10px;background:var(--panel);font-size:14px;line-height:1.5}
.gfd-list li::before{content:'▸ ';color:var(--accent)}
.gfd-side .gfd-block{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:16px}
.gfd-tut{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}
.gfd-tut a{border:1px solid var(--line);border-radius:12px;overflow:hidden;background:var(--panel);display:block}
.gfd-tut a:hover{border-color:var(--accent2)}
.gfd-tut .thumb{aspect-ratio:16/9;background:var(--panel2) center/cover}
.gfd-tut .t{padding:10px 12px;font-size:13px;line-height:1.35}
.gfd-link{display:block;padding:11px 14px;border:1px solid var(--line);border-radius:10px;background:var(--panel);font-size:14px;margin-bottom:8px}
.gfd-link:hover{border-color:var(--accent2)}
.gfd-muted{color:var(--muted);font-size:13px}
@media(max-width:640px){.gfd-hero h1{font-size:30px}.gfd-dtitle h1{font-size:24px}}
</style>
CSS;
}

/** One app card (links to the internal detail page). */
function gfDirCard($a)
{
    $sName = gfDirOut($a['name']);
    $sDesc = gfDirOut($a['description']);
    if(function_exists('mb_strlen') && mb_strlen($sDesc) > 120)
        $sDesc = mb_substr($sDesc, 0, 120) . '…';
    $sCat = gfDirOut($a['category']);
    $sLogo = trim((string)$a['logo_url']);
    $sAccess = gfDirOut($a['access_type'] ?: 'free');
    $bNative = (int)$a['is_gfunnel_native'] === 1;

    $sInitial = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string)$a['name']), 0, 1)) ?: '?';
    $sMedia = ($sLogo !== '' && preg_match('#^https?://#i', $sLogo))
        ? '<img src="' . bx_html_attribute($sLogo) . '" alt="" loading="lazy" onerror="this.style.display=\'none\';this.parentNode.classList.add(\'gfd-noimg\');">'
        : '';

    $sBadges = '';
    if($bNative) $sBadges .= '<span class="gfd-badge gfd-badge-native">GFunnel</span>';
    if($sAccess !== '' && strtolower($sAccess) !== 'free') $sBadges .= '<span class="gfd-badge">' . $sAccess . '</span>';

    return '<a class="gfd-card" href="' . bx_html_attribute(gfDirUrl($a)) . '">'
        . '<div class="gfd-logo gfd-noimg-fallback" data-initial="' . bx_html_attribute($sInitial) . '">' . $sMedia . '</div>'
        . '<div class="gfd-body">'
        .   '<div class="gfd-name">' . $sName . '</div>'
        .   ($sCat !== '' ? '<div class="gfd-cat">' . $sCat . '</div>' : '')
        .   ($sDesc !== '' ? '<div class="gfd-desc">' . $sDesc . '</div>' : '')
        .   ($sBadges !== '' ? '<div class="gfd-badges">' . $sBadges . '</div>' : '')
        . '</div>'
        . '</a>';
}

/** LIST view: hero + search + category chips + grid. */
function gfDirRenderList($oDb)
{
    $sCat = trim((string)bx_get('cat'));
    $aBind = [];
    $sWhere = '';
    if($sCat !== '' && strlen($sCat) <= 191) { $sWhere = " WHERE `category` = ?"; $aBind[] = $sCat; }

    $aFeatured = $oDb->getAll("SELECT * FROM `gf_directory_apps` WHERE `is_featured` = 1 ORDER BY `name` LIMIT 6");
    $aApps = $aBind
        ? $oDb->getAll($oDb->prepare("SELECT * FROM `gf_directory_apps`" . $sWhere . " ORDER BY `is_featured` DESC, `name` LIMIT 240", ...$aBind))
        : $oDb->getAll("SELECT * FROM `gf_directory_apps` ORDER BY `is_featured` DESC, `name` LIMIT 240");
    $aCats = $oDb->getColumn("SELECT `category` FROM `gf_directory_apps` WHERE `category` IS NOT NULL AND `category` <> '' GROUP BY `category` ORDER BY `category`");
    $iTotal = (int)$oDb->getOne("SELECT COUNT(*) FROM `gf_directory_apps`");
    if(!is_array($aFeatured)) $aFeatured = [];
    if(!is_array($aApps)) $aApps = [];
    if(!is_array($aCats)) $aCats = [];

    $sFeatured = '';
    foreach($aFeatured as $a) $sFeatured .= gfDirCard($a);
    $sGrid = '';
    foreach($aApps as $a) $sGrid .= gfDirCard($a);
    if($sGrid === '')
        $sGrid = '<div class="gfd-empty">No directory entries yet. Once the Supabase sync runs, ' . $iTotal . ' apps will appear here.</div>';

    $sChips = '<a class="gfd-chip' . ($sCat === '' ? ' gfd-chip-on' : '') . '" href="' . BX_DOL_URL_ROOT . 'applications">All</a>';
    foreach($aCats as $c)
        $sChips .= '<a class="gfd-chip' . ($sCat === $c ? ' gfd-chip-on' : '') . '" href="' . BX_DOL_URL_ROOT . 'applications?cat=' . rawurlencode($c) . '">' . gfDirOut($c) . '</a>';

    $sCss = gfDirCss();
    $sSite = gfDirOut(getParam('site_title'));
    echo "<!DOCTYPE html><html lang=\"en\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title>Directory — {$sSite}</title><meta name=\"description\" content=\"Explore every app, tool and company in the GFunnel Directory.\">{$sCss}</head><body><div class=\"gfd-wrap\">";
    echo '<div class="gfd-hero"><h1>Application Directory</h1><p>Every app and tool in the \'verse — searchable, in one place.</p>'
        . '<div class="gfd-search"><input id="gfdSearch" type="text" placeholder="Search ' . $iTotal . ' apps, tools, companies…" autocomplete="off"><div class="gfd-count" id="gfdCount"></div></div></div>';
    if($sFeatured !== '' && $sCat === '')
        echo '<div class="gfd-featured"><div class="gfd-section">Featured</div><div class="gfd-grid">' . $sFeatured . '</div></div>';
    echo '<div class="gfd-chips">' . $sChips . '</div>';
    echo '<div class="gfd-grid" id="gfdGrid">' . $sGrid . '</div>';
    echo '<div class="gfd-foot">GFunnel · Application Directory · synced from Supabase · ' . $iTotal . ' entries</div></div>';
    echo <<<JS
<script>(function(){var i=document.getElementById('gfdSearch'),g=document.getElementById('gfdGrid'),c=document.getElementById('gfdCount');if(!i||!g)return;var cards=[].slice.call(g.querySelectorAll('.gfd-card'));i.addEventListener('input',function(){var q=i.value.trim().toLowerCase(),n=0;cards.forEach(function(x){var h=q===''||x.textContent.toLowerCase().indexOf(q)>-1;x.style.display=h?'':'none';if(h)n++;});c.textContent=q?n+' match'+(n===1?'':'es'):'';});})();</script>
JS;
    echo '</body></html>';
}

/** DETAIL view: rich per-app page. */
function gfDirRenderDetail($oDb, $sSlug)
{
    // Resolve the app: prefer the curated directory row, fall back to platform_apps.
    $aApp = $oDb->getRow($oDb->prepare("SELECT * FROM `gf_directory_apps` WHERE `slug` = ? LIMIT 1", $sSlug));
    if(!$aApp)
        $aApp = $oDb->getRow($oDb->prepare("SELECT * FROM `gf_directory_apps` WHERE `id` = ? LIMIT 1", $sSlug));

    $sPid = $aApp ? (string)$aApp['platform_app_id'] : '';
    $aRich = null;
    if($sPid !== '')
        $aRich = $oDb->getRow($oDb->prepare("SELECT * FROM `gf_platform_apps` WHERE `id` = ? LIMIT 1", $sPid));
    if(!$aRich)
        $aRich = $oDb->getRow($oDb->prepare("SELECT * FROM `gf_platform_apps` WHERE `slug` = ? LIMIT 1", $sSlug));

    if(!$aApp && !$aRich) {
        BxDolTemplate::getInstance()->displayPageNotFound();
        return;
    }
    if($sPid === '' && $aRich) $sPid = (string)$aRich['id'];

    $get = function($k, $default = '') use ($aApp, $aRich) {
        if($aApp && isset($aApp[$k]) && $aApp[$k] !== '' && $aApp[$k] !== null) return $aApp[$k];
        if($aRich && isset($aRich[$k]) && $aRich[$k] !== '' && $aRich[$k] !== null) return $aRich[$k];
        return $default;
    };

    $sName = gfDirOut($get('name'));
    $sTagline = gfDirOut($aRich ? $aRich['tagline'] : '');
    $sAbout = gfDirOut($aRich ? ($aRich['gfunnel_description'] ?: $aRich['description']) : $get('description'));
    $sLogo = trim((string)$get('logo_url'));
    $sAppUrl = trim((string)$get('app_url'));
    $aCats = $aRich ? gfDirJson($aRich['categories']) : [];
    $aDepts = $aRich ? gfDirJson($aRich['departments']) : [];
    $aUse = $aRich ? gfDirJson($aRich['gfunnel_use_cases']) : [];
    $aAuto = $aRich ? gfDirJson($aRich['automation_ideas']) : [];
    $aRelated = $aRich ? gfDirJson($aRich['related_app_slugs']) : [];

    // Detail child content (empty today; lights up when synced).
    $aTut = $sPid !== '' ? $oDb->getAll($oDb->prepare("SELECT * FROM `gf_app_tutorials` WHERE `platform_app_id` = ? ORDER BY `view_count` DESC LIMIT 12", $sPid)) : [];
    $aDocs = $sPid !== '' ? $oDb->getAll($oDb->prepare("SELECT * FROM `gf_app_docs` WHERE `platform_app_id` = ? ORDER BY `doc_type` LIMIT 24", $sPid)) : [];
    $aHelp = $sPid !== '' ? $oDb->getAll($oDb->prepare("SELECT * FROM `gf_app_help_articles` WHERE `platform_app_id` = ? LIMIT 24", $sPid)) : [];
    if(!is_array($aTut)) $aTut = [];
    if(!is_array($aDocs)) $aDocs = [];
    if(!is_array($aHelp)) $aHelp = [];

    $sInitial = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string)$get('name')), 0, 1)) ?: '?';
    $sMedia = ($sLogo !== '' && preg_match('#^https?://#i', $sLogo))
        ? '<img src="' . bx_html_attribute($sLogo) . '" alt="" onerror="this.style.display=\'none\';this.parentNode.classList.add(\'gfd-noimg\');">' : '';

    $fnList = function($aItems) {
        $s = '';
        foreach($aItems as $v) { $v = trim((string)$v); if($v !== '') $s .= '<li>' . gfDirOut($v) . '</li>'; }
        return $s !== '' ? '<ul class="gfd-list">' . $s . '</ul>' : '';
    };
    $fnChips = function($aItems) {
        $s = '';
        foreach($aItems as $v) { $v = trim((string)$v); if($v !== '') $s .= '<span class="gfd-badge">' . gfDirOut($v) . '</span>'; }
        return $s;
    };

    $sCss = gfDirCss();
    $sSite = gfDirOut(getParam('site_title'));
    $sTitle = $sName !== '' ? $sName : 'App';
    echo "<!DOCTYPE html><html lang=\"en\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title>{$sTitle} — Directory — {$sSite}</title>";
    if($sTagline !== '') echo "<meta name=\"description\" content=\"{$sTagline}\">";
    echo "{$sCss}</head><body><div class=\"gfd-wrap\">";
    echo '<a class="gfd-back" href="' . BX_DOL_URL_ROOT . 'applications">← All apps</a>';

    // Hero
    echo '<div class="gfd-dhero">'
        . '<div class="gfd-dlogo gfd-noimg-fallback" data-initial="' . bx_html_attribute($sInitial) . '">' . $sMedia . '</div>'
        . '<div class="gfd-dtitle"><h1>' . $sTitle . '</h1>'
        . ($sTagline !== '' ? '<div class="gfd-dtag">' . $sTagline . '</div>' : '')
        . '</div></div>';
    if($sAppUrl !== '' && preg_match('#^https?://#i', $sAppUrl))
        echo '<a class="gfd-open" href="' . bx_html_attribute($sAppUrl) . '" target="_blank" rel="noopener nofollow">Open app ↗</a>';

    echo '<div class="gfd-cols"><div class="gfd-main">';
    if($sAbout !== '')
        echo '<div class="gfd-block"><h2>About</h2><div class="gfd-prose">' . $sAbout . '</div></div>';
    if($sUse = $fnList($aUse))
        echo '<div class="gfd-block"><h2>Use cases in GFunnel</h2>' . $sUse . '</div>';
    if($sAuto = $fnList($aAuto))
        echo '<div class="gfd-block"><h2>Automation ideas</h2>' . $sAuto . '</div>';

    // Tutorials (empty today)
    if(!empty($aTut)) {
        $sTut = '';
        foreach($aTut as $t) {
            $sVid = trim((string)$t['youtube_video_id']);
            $sThumb = trim((string)$t['thumbnail_url']);
            if($sThumb === '' && $sVid !== '') $sThumb = 'https://i.ytimg.com/vi/' . rawurlencode($sVid) . '/hqdefault.jpg';
            $sHref = $sVid !== '' ? 'https://www.youtube.com/watch?v=' . rawurlencode($sVid) : trim((string)$t['thumbnail_url']);
            $sTut .= '<a href="' . bx_html_attribute($sHref) . '" target="_blank" rel="noopener nofollow">'
                . '<div class="thumb" style="background-image:url(\'' . bx_html_attribute($sThumb) . '\')"></div>'
                . '<div class="t">' . gfDirOut($t['title']) . '</div></a>';
        }
        echo '<div class="gfd-block"><h2>Tutorials</h2><div class="gfd-tut">' . $sTut . '</div></div>';
    }
    // Help articles (empty today)
    if(!empty($aHelp)) {
        $sH = '';
        foreach($aHelp as $h) {
            $sU = trim((string)$h['url']);
            $sInner = gfDirOut($h['title']);
            $sH .= ($sU !== '' && preg_match('#^https?://#i', $sU))
                ? '<a class="gfd-link" href="' . bx_html_attribute($sU) . '" target="_blank" rel="noopener nofollow">' . $sInner . '</a>'
                : '<div class="gfd-link">' . $sInner . '</div>';
        }
        echo '<div class="gfd-block"><h2>Help articles</h2>' . $sH . '</div>';
    }

    echo '</div><div class="gfd-side">';
    if($sCatsChips = $fnChips($aCats))
        echo '<div class="gfd-block"><h2>Categories</h2><div class="gfd-badges">' . $sCatsChips . '</div></div>';
    if($sDeptChips = $fnChips($aDepts))
        echo '<div class="gfd-block"><h2>Departments</h2><div class="gfd-badges">' . $sDeptChips . '</div></div>';
    // Docs (empty today)
    if(!empty($aDocs)) {
        $sD = '';
        foreach($aDocs as $d) {
            $sU = trim((string)$d['url']);
            if($sU === '' || !preg_match('#^https?://#i', $sU)) continue;
            $sLabel = gfDirOut($d['title'] ?: ($d['doc_type'] ?: 'Documentation'));
            $sD .= '<a class="gfd-link" href="' . bx_html_attribute($sU) . '" target="_blank" rel="noopener nofollow">' . $sLabel . '</a>';
        }
        if($sD !== '') echo '<div class="gfd-block"><h2>Docs &amp; API</h2>' . $sD . '</div>';
    }
    // Related apps
    if(!empty($aRelated)) {
        $sR = '';
        foreach($aRelated as $sRel) {
            $sRel = trim((string)$sRel);
            if($sRel === '') continue;
            $sR .= '<a class="gfd-chip" href="' . BX_DOL_URL_ROOT . 'application/' . rawurlencode($sRel) . '">' . gfDirOut($sRel) . '</a>';
        }
        if($sR !== '') echo '<div class="gfd-block"><h2>Related</h2><div class="gfd-chips">' . $sR . '</div></div>';
    }
    echo '</div></div>';

    echo '<div class="gfd-foot">GFunnel · Application Directory · synced from Supabase</div></div></body></html>';
}
