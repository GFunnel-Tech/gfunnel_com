<?php
/**
 * GFunnel — Resources (/resources).
 *
 * A branded, SEO-optimized library landing: articles, guides, playbooks, templates
 * and help. Same self-contained shell as gf_services.php, skinned with the homepage
 * design system (template/css/gf_home.css). Access-first — the hub renders whether or
 * not any content is loaded yet: the hero search routes into the site keyword search
 * (always functional), topic chips are preset searches, and the "Latest" grid pulls
 * real rows from gf_content_objects when present, else a designed empty state. No
 * fabricated articles.
 *
 * Routed by r.php (/resources). Set gf_resources = off (sys_options) to disable.
 */

require_once('./inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_INC . 'design.inc.php');
require_once(BX_DIRECTORY_PATH_INC . 'gf_home_blocks.inc.php');

bx_import('BxDolLanguages');

if (getParam('gf_resources') == 'off') {
    BxDolTemplate::getInstance()->displayPageNotFound();
    exit;
}

// ---- urls / meta ------------------------------------------------------------
$sSelf = BX_DOL_URL_ROOT . 'resources';
$sSearch = BX_DOL_URL_ROOT . 'searchKeyword.php';
$sCourses = gfHomeUrl('courses-home');
$sJoinUrl = gfHomeUrl('create-account');
$sLoginUrl = gfHomeUrl('login');
$sSiteName = gfHomeOut(getParam('site_title'));
$sCss = 'template/css/gf_home.css';
$sCssUrl = BX_DOL_URL_ROOT . $sCss . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sCss);
$sJs = 'template/js/gf_home.js';
$sJsUrl = BX_DOL_URL_ROOT . $sJs . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sJs);

$sTitle = 'Resources &mdash; Articles, Guides &amp; Playbooks | ' . $sSiteName;
$sDesc = 'Learn how to run every part of your business: articles, guides, playbooks, templates and courses. Search the GFunnel library.';
$sCanonical = $sSelf;

// topic chips = preset searches (always functional)
$aChips = array('Articles', 'Guides', 'Playbooks', 'Templates', 'Help');
$sChips = '';
foreach ($aChips as $sChip)
    $sChips .= '<a class="gfh-res-chip" href="' . gfHomeSearchUrl($sChip) . '">' . gfHomeOut($sChip) . '</a>';

// latest real articles (gf_content_objects), else designed empty state — no fake rows
$oDb = BxDolDb::getInstance();
$aRows = array();
if ($oDb->getOne("SHOW TABLES LIKE 'gf_content_objects'"))
    $aRows = $oDb->getAll("SELECT `title`, `excerpt`, `published_at`, `canonical_url` FROM `gf_content_objects` WHERE `status` = 'published' ORDER BY `published_at` DESC LIMIT 12");
if (!is_array($aRows)) $aRows = array();

if (!empty($aRows)) {
    $sBody = '<div class="gfh-res-grid">';
    foreach ($aRows as $a) {
        $sT = gfHomeOut((string)$a['title']);
        $sExcerpt = gfHomeOut((string)$a['excerpt']);
        $sWhen = gfHomeFeedDate($a['published_at']);
        $sUrl = (string)$a['canonical_url'];
        $bLink = (bool)preg_match('#^https?://#i', $sUrl);
        $sOpen = $bLink ? '<a class="gfh-res-card" href="' . bx_html_attribute($sUrl) . '">' : '<div class="gfh-res-card">';
        $sClose = $bLink ? '</a>' : '</div>';
        $sBody .= $sOpen
            . '<span class="gfh-res-kind">Article</span>'
            . '<span class="gfh-res-title">' . $sT . '</span>'
            . ($sExcerpt !== '' ? '<span class="gfh-res-excerpt">' . $sExcerpt . '</span>' : '')
            . ($sWhen !== '' ? '<span class="gfh-res-meta">' . $sWhen . '</span>' : '')
            . $sClose;
    }
    $sBody .= '</div>';
} else {
    $sBody = gfHomeFeedEmpty('The library is filling up', 'Articles, guides and playbooks will appear here as they&rsquo;re published. Search above or browse the courses in the meantime.');
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
<meta property="og:title" content="<?php echo bx_html_attribute('Resources — GFunnel'); ?>" />
<meta property="og:description" content="<?php echo bx_html_attribute($sDesc); ?>" />
<meta property="og:type" content="website" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Sora:wght@600;700;800&family=Space+Mono:wght@400;700&display=swap" />
<link rel="stylesheet" href="<?php echo bx_html_attribute($sCssUrl); ?>" />
<style>
.gfh-dir-hero { padding: 68px 24px 74px; }
.gfh-dir-title { font-family: var(--gfh-font-head); font-weight: 800; font-size: clamp(30px,5vw,46px); line-height: 1.1; letter-spacing: -.03em; color: #fff; margin-bottom: 12px; }
</style>
</head>
<body>
<div class="gfh" id="gfh">
    <?php echo gfHomeNav(array('active' => 'resources')); ?>

    <main id="gfh-main">
        <section class="gfh-hero gfh-dir-hero">
            <div class="gfh-hero-glow" aria-hidden="true"></div>
            <div class="gfh-hero-grid" aria-hidden="true"></div>
            <div class="gfh-hero-inner">
                <span class="gfh-hero-kicker">Resources</span>
                <h1 class="gfh-dir-title">Learn how to run every part of it.</h1>
                <p class="gfh-hero-sub">Articles, guides, courses and help &mdash; search the library, or browse the latest.</p>
                <form class="gfh-hero-search" action="<?php echo $sSearch; ?>" method="get" role="search">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" name="keyword" placeholder="Search articles, guides, courses &amp; help..." autocomplete="off" aria-label="Search resources" />
                    <button type="submit">Search</button>
                </form>
            </div>
            <div class="gfh-hero-curve" aria-hidden="true"><svg viewBox="0 0 1440 72" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg"><path d="M0,72 L0,42 Q720,-10 1440,42 L1440,72 Z" fill="#F3F4F7"/></svg></div>
        </section>

        <section class="gfh-sec">
            <div class="gfh-container">
                <div class="gfh-res-chips"><?php echo $sChips; ?></div>
                <?php echo $sBody; ?>
                <div class="gfh-sec-foot"><a class="gfh-link-more" href="<?php echo $sCourses; ?>">Browse courses &amp; the University <span aria-hidden="true">&rarr;</span></a></div>
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
                        <a href="<?php echo $sCourses; ?>">Courses</a>
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
