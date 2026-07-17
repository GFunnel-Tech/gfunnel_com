<?php
/**
 * GFunnel — Directory (Phase 1 vertical slice).
 *
 * Renders the app/company Directory as a standalone dark page (Star-Head
 * style: hero + search + category chips + card grid), the same self-contained
 * pattern as home.php / splash.php.
 *
 * DATA FLOW (see docs/directory-sync-runbook.md):
 *   Supabase Postgres `public.directory_apps`  = source of truth
 *        │  (row change -> DB webhook)
 *        ▼
 *   Edge Function `sync-directory-apps-to-mysql`  = owned one-way push
 *        │
 *        ▼
 *   MySQL `gf_directory_apps`  = local mirror  <-- THIS PAGE READS IT
 *
 * The page never talks to Supabase directly; it only reads the local mirror
 * through UNA's own DB connection (BxDolDb), so no Supabase creds live here.
 * The mirror table is auto-created on first load (same idiom as gf_bug.php),
 * so the page renders cleanly (empty state) even before the first sync runs.
 *
 * Route: /directory (wired in r.php). Optional settings (sys_options):
 *   - gf_directory  'off' disables the page (falls through to 404).
 */

require_once('./inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_INC . "design.inc.php");

bx_import('BxDolLanguages');

if(getParam('gf_directory') == 'off') {
    BxDolTemplate::getInstance()->displayPageNotFound();
    exit;
}

$oDb = BxDolDb::getInstance();

/**
 * Mirror table for Supabase public.directory_apps. Types are the MySQL
 * equivalents of the Postgres columns (uuid -> char(36), text -> varchar/text,
 * boolean -> tinyint(1), timestamptz -> datetime). `synced_at` is local
 * bookkeeping written by the Edge Function on each upsert.
 */
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
    PRIMARY KEY (`id`),
    KEY `slug` (`slug`),
    KEY `category` (`category`),
    KEY `is_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

//--- Category filter (optional, sanitized via prepared statement).
$sCat = trim((string)bx_get('cat'));
$aWhere = [];
$aBind = [];
if($sCat !== '' && strlen($sCat) <= 191) {
    $aWhere[] = "`category` = ?";
    $aBind[] = $sCat;
}
$sWhereSql = $aWhere ? (' WHERE ' . implode(' AND ', $aWhere)) : '';

//--- Featured strip (up to 6), the full grid (capped for the slice), and the
//--- category list for the chips. All reads go through the local mirror.
$aFeatured = $oDb->getAll("SELECT * FROM `gf_directory_apps` WHERE `is_featured` = 1 ORDER BY `name` LIMIT 6");
$aApps = $aBind
    ? $oDb->getAll($oDb->prepare("SELECT * FROM `gf_directory_apps`" . $sWhereSql . " ORDER BY `is_featured` DESC, `name` LIMIT 240", ...$aBind))
    : $oDb->getAll("SELECT * FROM `gf_directory_apps`" . $sWhereSql . " ORDER BY `is_featured` DESC, `name` LIMIT 240");
$aCats = $oDb->getColumn("SELECT `category` FROM `gf_directory_apps` WHERE `category` IS NOT NULL AND `category` <> '' GROUP BY `category` ORDER BY `category`");
$iTotal = (int)$oDb->getOne("SELECT COUNT(*) FROM `gf_directory_apps`");

if(!is_array($aFeatured)) $aFeatured = [];
if(!is_array($aApps)) $aApps = [];
if(!is_array($aCats)) $aCats = [];

/** Small helper: safe text for HTML output. */
function gfDirOut($s) { return bx_process_output((string)$s); }

/** Render one app card. */
function gfDirCard($a)
{
    $sName = gfDirOut($a['name']);
    $sDesc = gfDirOut($a['description']);
    if(function_exists('mb_strlen') && mb_strlen($sDesc) > 120)
        $sDesc = mb_substr($sDesc, 0, 120) . '…';
    $sCat = gfDirOut($a['category']);
    $sLogo = trim((string)$a['logo_url']);
    $sUrl = trim((string)$a['app_url']);
    $sAccess = gfDirOut($a['access_type'] ?: 'free');
    $bNative = (int)$a['is_gfunnel_native'] === 1;

    $sInitial = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string)$a['name']), 0, 1)) ?: '?';
    $sMedia = ($sLogo !== '' && preg_match('#^https?://#i', $sLogo))
        ? '<img src="' . bx_html_attribute($sLogo) . '" alt="" loading="lazy" onerror="this.style.display=\'none\';this.parentNode.classList.add(\'gfd-noimg\');">'
        : '';

    $sHref = ($sUrl !== '' && preg_match('#^https?://#i', $sUrl)) ? bx_html_attribute($sUrl) : '';
    $sTagOpen = $sHref !== '' ? '<a class="gfd-card" href="' . $sHref . '" target="_blank" rel="noopener nofollow">' : '<div class="gfd-card">';
    $sTagClose = $sHref !== '' ? '</a>' : '</div>';

    $sBadges = '';
    if($bNative) $sBadges .= '<span class="gfd-badge gfd-badge-native">GFunnel</span>';
    if($sAccess !== '' && strtolower($sAccess) !== 'free') $sBadges .= '<span class="gfd-badge">' . $sAccess . '</span>';

    return $sTagOpen
        . '<div class="gfd-logo gfd-noimg-fallback" data-initial="' . bx_html_attribute($sInitial) . '">' . $sMedia . '</div>'
        . '<div class="gfd-body">'
        .   '<div class="gfd-name">' . $sName . '</div>'
        .   ($sCat !== '' ? '<div class="gfd-cat">' . $sCat . '</div>' : '')
        .   ($sDesc !== '' ? '<div class="gfd-desc">' . $sDesc . '</div>' : '')
        .   ($sBadges !== '' ? '<div class="gfd-badges">' . $sBadges . '</div>' : '')
        . '</div>'
        . $sTagClose;
}

//--- Build markup fragments.
$sFeatured = '';
foreach($aFeatured as $a) $sFeatured .= gfDirCard($a);

$sGrid = '';
foreach($aApps as $a) $sGrid .= gfDirCard($a);
if($sGrid === '')
    $sGrid = '<div class="gfd-empty">No directory entries yet. Once the Supabase sync runs, ' . (int)$iTotal . ' apps will appear here.</div>';

$sChips = '<a class="gfd-chip' . ($sCat === '' ? ' gfd-chip-on' : '') . '" href="' . BX_DOL_URL_ROOT . 'directory">All</a>';
foreach($aCats as $c) {
    $sChips .= '<a class="gfd-chip' . ($sCat === $c ? ' gfd-chip-on' : '') . '" href="'
        . BX_DOL_URL_ROOT . 'directory?cat=' . rawurlencode($c) . '">' . gfDirOut($c) . '</a>';
}

$oTemplate = BxDolTemplate::getInstance();
$sSiteName = gfDirOut(getParam('site_title'));

$sPage = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Directory — {$sSiteName}</title>
<meta name="description" content="Explore every app, tool and company in the GFunnel Directory.">
<style>
:root{--bg:#0b0d10;--panel:#12151a;--panel2:#161a20;--line:#232833;--txt:#e7ebf0;--muted:#8a93a2;--accent:#22d3ee;--accent2:#0ea5b7}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--txt);font-family:'DM Sans',system-ui,-apple-system,Segoe UI,Roboto,sans-serif}
a{color:inherit;text-decoration:none}
.gfd-wrap{max-width:1200px;margin:0 auto;padding:0 20px}
.gfd-hero{padding:64px 0 28px;text-align:center}
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
.gfd-featured .gfd-grid{margin-bottom:20px}
.gfd-empty{grid-column:1/-1;text-align:center;color:var(--muted);padding:60px 20px;border:1px dashed var(--line);border-radius:14px}
.gfd-foot{border-top:1px solid var(--line);padding:26px 0;color:var(--muted);font-size:13px;text-align:center}
@media(max-width:640px){.gfd-hero h1{font-size:30px}}
</style>
</head>
<body>
<div class="gfd-wrap">
  <div class="gfd-hero">
    <h1>The GFunnel Directory</h1>
    <p>Every app, tool and company in the 'verse — searchable, in one place.</p>
    <div class="gfd-search">
      <input id="gfdSearch" type="text" placeholder="Search {$iTotal} apps, tools, companies…" autocomplete="off">
      <div class="gfd-count" id="gfdCount"></div>
    </div>
  </div>
HTML;

if($sFeatured !== '' && $sCat === '') {
    $sPage .= '<div class="gfd-featured"><div class="gfd-section">Featured</div><div class="gfd-grid">' . $sFeatured . '</div></div>';
}

$sPage .= '<div class="gfd-chips">' . $sChips . '</div>';
$sPage .= '<div class="gfd-grid" id="gfdGrid">' . $sGrid . '</div>';

$sYear = date('Y');
$sPage .= <<<HTML
  <div class="gfd-foot">GFunnel Directory · synced from Supabase · {$iTotal} entries</div>
</div>
<script>
(function(){
  var input=document.getElementById('gfdSearch'),grid=document.getElementById('gfdGrid'),count=document.getElementById('gfdCount');
  if(!input||!grid)return;
  var cards=[].slice.call(grid.querySelectorAll('.gfd-card'));
  function apply(){
    var q=input.value.trim().toLowerCase(),shown=0;
    cards.forEach(function(c){
      var t=c.textContent.toLowerCase(),hit=q===''||t.indexOf(q)>-1;
      c.style.display=hit?'':'none';if(hit)shown++;
    });
    count.textContent=q?shown+' match'+(shown===1?'':'es'):'';
  }
  input.addEventListener('input',apply);
})();
</script>
</body>
</html>
HTML;

echo $sPage;
