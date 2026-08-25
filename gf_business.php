<?php
/**
 * GFunnel — Business Directory (/business).
 *
 * A branded, SEO-optimized browse page over the Business Listing module
 * (mz_listing / mz_listing_entries) — the same self-contained pattern as
 * gf_applications.php, skinned with the homepage design system (template/css/gf_home.css)
 * so it matches "The Business Operating Hub". Search + pagination run server-side so it
 * scales from 31k listings toward every business; per-business detail lives on the
 * module's own view-listing page, and "Claim your business" routes into listing-claim.
 *
 * Routed by r.php (/business, /businesses). Set gf_business = off (sys_options) to disable.
 */

require_once('./inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_INC . 'design.inc.php');
require_once(BX_DIRECTORY_PATH_INC . 'gf_home_blocks.inc.php');

bx_import('BxDolLanguages');

if (getParam('gf_business') == 'off') {
    BxDolTemplate::getInstance()->displayPageNotFound();
    exit;
}

$oDb = BxDolDb::getInstance();
if (!$oDb->getOne("SHOW TABLES LIKE 'mz_listing_entries'")) {
    BxDolTemplate::getInstance()->displayPageNotFound();
    exit;
}

// ---- params -----------------------------------------------------------------
$sQ = trim((string)bx_get('q'));
if (strlen($sQ) > 100) $sQ = substr($sQ, 0, 100);
$iPerPage = 24;
$iPage = max(1, (int)bx_get('p'));
$iOffset = ($iPage - 1) * $iPerPage;

// ---- query (prepared for the search terms; LIMIT is cast-int inlined) --------
$sWhere = "`status` = 'active'";
$aBind = array();
if ($sQ !== '') {
    $sWhere .= " AND (`title` LIKE ? OR `subcategory` LIKE ? OR `location` LIKE ?)";
    $sLike = '%' . $sQ . '%';
    $aBind = array($sLike, $sLike, $sLike);
}

$sCountSql = "SELECT COUNT(*) FROM `mz_listing_entries` WHERE $sWhere";
$iTotal = (int)($aBind ? $oDb->getOne($oDb->prepare($sCountSql, ...$aBind)) : $oDb->getOne($sCountSql));

$iPages = max(1, (int)ceil($iTotal / $iPerPage));
if ($iPage > $iPages) { $iPage = $iPages; $iOffset = ($iPage - 1) * $iPerPage; }

$sListSql = "SELECT `id`, `title`, `location`, `claim_status`, `website` "
    . "FROM `mz_listing_entries` WHERE $sWhere ORDER BY `featured` DESC, `views` DESC "
    . "LIMIT " . (int)$iOffset . ", " . (int)$iPerPage;
$aRows = $aBind ? $oDb->getAll($oDb->prepare($sListSql, ...$aBind)) : $oDb->getAll($sListSql);
if (!is_array($aRows)) $aRows = array();

// ---- urls / meta ------------------------------------------------------------
$sSelf = BX_DOL_URL_ROOT . 'business';
$sClaimUrl = gfHomeListingUrl('listing-claim');
$sLoginUrl = gfHomeUrl('login');
$sJoinUrl = gfHomeUrl('create-account');
$sSiteName = gfHomeOut(getParam('site_title'));
$sCss = 'template/css/gf_home.css';
$sCssUrl = BX_DOL_URL_ROOT . $sCss . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sCss);
$sJs = 'template/js/gf_home.js';
$sJsUrl = BX_DOL_URL_ROOT . $sJs . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sJs);

$sTitle = 'Business Directory &mdash; Find &amp; Claim Your Business | ' . $sSiteName;
$sDesc = 'Search the GFunnel business directory, find your business, claim it, and connect it to your workspace. Every business, in one place.';
$sCanonical = $sSelf . ($iPage > 1 ? '?p=' . $iPage : '');

// build a page URL preserving the search
$fnPage = function($i) use ($sSelf, $sQ) {
    $aP = array();
    if ($sQ !== '') $aP[] = 'q=' . rawurlencode($sQ);
    if ($i > 1) $aP[] = 'p=' . (int)$i;
    return $sSelf . (empty($aP) ? '' : '?' . implode('&', $aP));
};

// ---- cards ------------------------------------------------------------------
$sCards = '';
foreach ($aRows as $a) {
    $sNameRaw = trim((string)$a['title']);
    if ($sNameRaw === '') continue;
    $sName = gfHomeOut($sNameRaw);
    $sHref = gfHomeListingUrl('view-listing', array('id' => (int)$a['id']));
    $sLoc = gfHomeListingLocation($a['location']);
    $sMeta = $sLoc !== '' ? gfHomeOut($sLoc) : '';
    $sClaim = (strtolower(trim((string)$a['claim_status'])) === 'claimable') ? '<span class="gfh-biz-claim">Claimable</span>' : '';
    $sIni = gfHomeOut(mb_strtoupper(mb_substr($sNameRaw, 0, 1)));
    $sCards .= '<a class="gfh-biz-card" href="' . $sHref . '">'
        . '<span class="gfh-biz-logo">' . $sIni . '</span>'
        . '<span class="gfh-biz-info"><span class="gfh-biz-name">' . $sName . $sClaim . '</span>'
        . ($sMeta !== '' ? '<span class="gfh-biz-meta">' . $sMeta . '</span>' : '')
        . '</span></a>';
}
if ($sCards === '')
    $sCards = '<div class="gfh-dir-empty">No businesses match &ldquo;' . gfHomeOut($sQ) . '&rdquo;. Try a different search &mdash; or <a href="' . $sClaimUrl . '">add your business</a>.</div>';

// ---- pagination -------------------------------------------------------------
$sPager = '';
if ($iPages > 1) {
    $sPager .= '<nav class="gfh-pager" aria-label="Pagination">';
    if ($iPage > 1)
        $sPager .= '<a class="gfh-pager-btn" href="' . $fnPage($iPage - 1) . '" rel="prev">&larr; Prev</a>';
    $iFrom = max(1, $iPage - 3);
    $iTo = min($iPages, $iPage + 3);
    if ($iFrom > 1) $sPager .= '<a class="gfh-pager-num" href="' . $fnPage(1) . '">1</a>' . ($iFrom > 2 ? '<span class="gfh-pager-gap">&hellip;</span>' : '');
    for ($i = $iFrom; $i <= $iTo; $i++)
        $sPager .= '<a class="gfh-pager-num' . ($i == $iPage ? ' gfh-on' : '') . '" href="' . $fnPage($i) . '">' . $i . '</a>';
    if ($iTo < $iPages) $sPager .= ($iTo < $iPages - 1 ? '<span class="gfh-pager-gap">&hellip;</span>' : '') . '<a class="gfh-pager-num" href="' . $fnPage($iPages) . '">' . $iPages . '</a>';
    if ($iPage < $iPages)
        $sPager .= '<a class="gfh-pager-btn" href="' . $fnPage($iPage + 1) . '" rel="next">Next &rarr;</a>';
    $sPager .= '</nav>';
}

$iShownFrom = $iTotal ? $iOffset + 1 : 0;
$iShownTo = min($iOffset + $iPerPage, $iTotal);
$sCountLine = $sQ !== ''
    ? number_format($iTotal) . ' result' . ($iTotal == 1 ? '' : 's') . ' for &ldquo;' . gfHomeOut($sQ) . '&rdquo;'
    : 'Showing ' . number_format($iShownFrom) . '&ndash;' . number_format($iShownTo) . ' of ' . number_format($iTotal) . ' businesses';

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
<meta property="og:title" content="<?php echo bx_html_attribute('Business Directory — GFunnel'); ?>" />
<meta property="og:description" content="<?php echo bx_html_attribute($sDesc); ?>" />
<meta property="og:type" content="website" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Sora:wght@600;700;800&family=Space+Mono:wght@400;700&display=swap" />
<link rel="stylesheet" href="<?php echo bx_html_attribute($sCssUrl); ?>" />
<style>
.gfh-dir-hero { padding: 68px 24px 74px; }
.gfh-dir-title { font-family: var(--gfh-font-head); font-weight: 800; font-size: clamp(30px,5vw,46px); line-height: 1.1; letter-spacing: -.03em; color: #fff; margin-bottom: 12px; }
.gfh-dir-toolbar { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
.gfh-dir-count { font-size: 14px; color: var(--gfh-slate); font-weight: 600; }
.gfh-dir-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
.gfh-dir-empty { grid-column: 1/-1; text-align: center; color: var(--gfh-slate); padding: 40px 0; }
.gfh-dir-empty a { color: var(--gfh-orange-600); font-weight: 700; }
.gfh-pager { display: flex; align-items: center; justify-content: center; gap: 6px; flex-wrap: wrap; margin-top: 40px; }
.gfh-pager-num, .gfh-pager-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; border: 1px solid var(--gfh-line); background: var(--gfh-card); border-radius: 10px; font-size: 14px; font-weight: 700; color: var(--gfh-slate); transition: all .15s ease; }
.gfh-pager-num:hover, .gfh-pager-btn:hover { border-color: var(--gfh-orange); color: var(--gfh-orange-600); }
.gfh-pager-num.gfh-on { background: var(--gfh-orange); border-color: var(--gfh-orange); color: #fff; }
.gfh-pager-gap { padding: 0 4px; color: var(--gfh-muted); }
</style>
</head>
<body>
<div class="gfh" id="gfh">
    <?php echo gfHomeNav(array('active' => 'business', 'cta_label' => 'Claim your business', 'cta_url' => $sClaimUrl)); ?>

    <main id="gfh-main">
        <section class="gfh-hero gfh-dir-hero">
            <div class="gfh-hero-glow" aria-hidden="true"></div>
            <div class="gfh-hero-grid" aria-hidden="true"></div>
            <div class="gfh-hero-inner">
                <span class="gfh-hero-kicker">Business Database</span>
                <h1 class="gfh-dir-title">Find &amp; claim your business.</h1>
                <p class="gfh-hero-sub">Search <?php echo number_format(max($iTotal, 0)); ?> businesses &mdash; on the way to every business on earth. Claim yours and connect it to your workspace.</p>
                <form class="gfh-hero-search" action="<?php echo $sSelf; ?>" method="get" role="search">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" name="q" value="<?php echo bx_html_attribute($sQ); ?>" placeholder="Search by name, category, or location..." autocomplete="off" aria-label="Search businesses" />
                    <button type="submit">Search</button>
                </form>
            </div>
            <div class="gfh-hero-curve" aria-hidden="true"><svg viewBox="0 0 1440 72" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg"><path d="M0,72 L0,42 Q720,-10 1440,42 L1440,72 Z" fill="#F3F4F7"/></svg></div>
        </section>

        <section class="gfh-sec">
            <div class="gfh-container">
                <div class="gfh-dir-toolbar">
                    <span class="gfh-dir-count"><?php echo $sCountLine; ?></span>
                    <?php if ($sQ !== '') { ?><a class="gfh-link-more" href="<?php echo $sSelf; ?>">Clear search</a><?php } ?>
                </div>
                <div class="gfh-dir-grid"><?php echo $sCards; ?></div>
                <?php echo $sPager; ?>
            </div>
        </section>
    </main>

    <footer class="gfh-footer">
        <div class="gfh-container">
            <div class="gfh-footer-bottom">
                <div class="gfh-footer-bottom-in">
                    <span>&copy; <?php echo date('Y'); ?> GFunnel, Inc. All rights reserved.</span>
                    <nav class="gfh-footer-legal" aria-label="Legal">
                        <a href="<?php echo BX_DOL_URL_ROOT; ?>">Home</a>
                        <a href="<?php echo $sClaimUrl; ?>">Claim a business</a>
                        <a href="<?php echo $sJoinUrl; ?>">Create workspace</a>
                    </nav>
                </div>
            </div>
        </div>
    </footer>
</div>
<script src="<?php echo bx_html_attribute($sJsUrl); ?>"></script>
</body>
</html>
<?php
exit;
