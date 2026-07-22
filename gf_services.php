<?php
/**
 * GFunnel — Services & Talent (/services).
 *
 * A branded, SEO-optimized landing for the services offering: hire verified VAs,
 * find verified vendors (agencies, sales/CSO teams), bring in GFunnel's own
 * done-for-you team, or do it yourself in a workspace. Same self-contained shell
 * as gf_business.php / gf_applications.php, skinned with template/css/gf_home.css.
 *
 * Search routes into the site keyword search; the four service paths share one
 * source with the homepage section (gfHomeServiceCards) so they never drift.
 * Where a real vendor listing module (mz_listing) is present, a live slice of
 * verified vendors is shown; otherwise the page stays access-first with no
 * fabricated talent.
 *
 * Routed by r.php (/services). Set gf_services = off (sys_options) to disable.
 */

require_once('./inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_INC . 'design.inc.php');
require_once(BX_DIRECTORY_PATH_INC . 'gf_home_blocks.inc.php');

bx_import('BxDolLanguages');

if (getParam('gf_services') == 'off') {
    BxDolTemplate::getInstance()->displayPageNotFound();
    exit;
}

// ---- urls / meta ------------------------------------------------------------
$sSelf = BX_DOL_URL_ROOT . 'services';
$sSearch = BX_DOL_URL_ROOT . 'searchKeyword.php';
$sContact = gfHomeUrl('contact');
$sJoinUrl = gfHomeUrl('create-account');
$sLoginUrl = gfHomeUrl('login');
$sSiteName = gfHomeOut(getParam('site_title'));
$sCss = 'template/css/gf_home.css';
$sCssUrl = BX_DOL_URL_ROOT . $sCss . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sCss);

$sTitle = 'Services &amp; Talent &mdash; Hire Verified VAs &amp; Vendors | ' . $sSiteName;
$sDesc = 'Hire vetted virtual assistants, find verified marketing agencies and service vendors, or bring in GFunnel\'s own done-for-you team — then run it all from your workspace.';
$sCanonical = $sSelf;

$sCards = gfHomeServiceCards();

// Real, verified vendor slice — only when the Business Listing module is present.
$oDb = BxDolDb::getInstance();
$sVendorBlock = '';
if ($oDb->getOne("SHOW TABLES LIKE 'mz_listing_entries'")) {
    $aVendors = $oDb->getAll(
        "SELECT `id`, `title`, `location` FROM `mz_listing_entries` "
        . "WHERE `status` = 'active' ORDER BY `featured` DESC, `views` DESC LIMIT 8"
    );
    if (is_array($aVendors) && !empty($aVendors)) {
        $sVendorCards = '';
        foreach ($aVendors as $a) {
            $sNameRaw = trim((string)$a['title']);
            if ($sNameRaw === '') continue;
            $sHref = gfHomeListingUrl('view-listing', array('id' => (int)$a['id']));
            $sLoc = gfHomeListingLocation($a['location']);
            $sMeta = $sLoc !== '' ? gfHomeOut($sLoc) : '';
            $sIni = gfHomeOut(mb_strtoupper(mb_substr($sNameRaw, 0, 1)));
            $sVendorCards .= '<a class="gfh-biz-card" href="' . $sHref . '">'
                . '<span class="gfh-biz-logo">' . $sIni . '</span>'
                . '<span class="gfh-biz-info"><span class="gfh-biz-name">' . gfHomeOut($sNameRaw) . '</span>'
                . ($sMeta !== '' ? '<span class="gfh-biz-meta">' . $sMeta . '</span>' : '')
                . '</span></a>';
        }
        if ($sVendorCards !== '') {
            $sBrowse = gfHomeListingUrl('listing-home');
            $sVendorBlock = '<section class="gfh-sec gfh-sec-alt"><div class="gfh-container">'
                . '<div class="gfh-sec-head"><span class="gfh-eyebrow">Verified Vendors</span>'
                . '<h2 class="gfh-h2">Trusted agencies &amp; service vendors.</h2>'
                . '<p class="gfh-sub">A live look at businesses in the directory. Every vendor is vetted before it&rsquo;s listed.</p></div>'
                . '<div class="gfh-dir-grid">' . $sVendorCards . '</div>'
                . '<div class="gfh-sec-foot"><a class="gfh-link-more" href="' . $sBrowse . '">Browse all vendors <span aria-hidden="true">&rarr;</span></a></div>'
                . '</div></section>';
        }
    }
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
<meta property="og:title" content="<?php echo bx_html_attribute('Services & Talent — GFunnel'); ?>" />
<meta property="og:description" content="<?php echo bx_html_attribute($sDesc); ?>" />
<meta property="og:type" content="website" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Sora:wght@600;700;800&family=Space+Mono:wght@400;700&display=swap" />
<link rel="stylesheet" href="<?php echo bx_html_attribute($sCssUrl); ?>" />
<style>
.gfh-dir-hero { padding: 68px 24px 74px; }
.gfh-dir-title { font-family: var(--gfh-font-head); font-weight: 800; font-size: clamp(30px,5vw,46px); line-height: 1.1; letter-spacing: -.03em; color: #fff; margin-bottom: 12px; }
.gfh-dir-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
</style>
</head>
<body>
<div class="gfh" id="gfh">
    <header class="gfh-nav">
        <div class="gfh-container gfh-nav-in">
            <a class="gfh-brand" href="<?php echo BX_DOL_URL_ROOT; ?>" aria-label="GFunnel home">
                <span class="gfh-brand-mark" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 48 48" fill="none"><path d="M24 3 42 13.5v21L24 45 6 34.5v-21L24 3z" stroke="#94A3B8" stroke-width="2.5" stroke-linejoin="round"/><path d="M6 13.5 24 24l18-10.5M24 24v21" stroke="#CBD5E1" stroke-width="1.5" stroke-linejoin="round"/><path d="m12 22 9 5v8l-9-5v-8z" fill="#EA580C"/><path d="m19 17 9 5v8l-9-5v-8z" fill="#F97316"/><path d="m26 12 9 5v8l-9-5v-8z" fill="#FB923C"/></svg></span>
                GFunnel
            </a>
            <nav class="gfh-nav-links" aria-label="Primary">
                <a href="<?php echo BX_DOL_URL_ROOT; ?>business">Businesses</a>
                <a href="<?php echo $sSelf; ?>" aria-current="page">Services</a>
                <a href="<?php echo BX_DOL_URL_ROOT; ?>applications">Software</a>
                <a href="<?php echo BX_DOL_URL_ROOT; ?>marketplace">Marketplace</a>
                <a href="<?php echo BX_DOL_URL_ROOT; ?>resources">Resources</a>
            </nav>
            <div class="gfh-nav-right">
                <a class="gfh-signin" href="<?php echo $sLoginUrl; ?>">Sign in</a>
                <a class="gfh-btn gfh-btn-orange gfh-btn-sm" href="<?php echo $sJoinUrl; ?>">Get started</a>
            </div>
        </div>
    </header>

    <main id="gfh-main">
        <section class="gfh-hero gfh-dir-hero">
            <div class="gfh-hero-glow" aria-hidden="true"></div>
            <div class="gfh-hero-grid" aria-hidden="true"></div>
            <div class="gfh-hero-inner">
                <span class="gfh-hero-kicker">Services &amp; Talent</span>
                <h1 class="gfh-dir-title">Get it done &mdash; with talent you can trust.</h1>
                <p class="gfh-hero-sub">Hire a verified VA, find a trusted agency or vendor, or bring in our own team &mdash; then run it all from your workspace. Free to start.</p>
                <form class="gfh-hero-search" action="<?php echo $sSearch; ?>" method="get" role="search">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" name="keyword" placeholder="Search verified VAs, agencies &amp; vendors by skill or industry..." autocomplete="off" aria-label="Search services and talent" />
                    <button type="submit">Search</button>
                </form>
            </div>
            <div class="gfh-hero-curve" aria-hidden="true"><svg viewBox="0 0 1440 72" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg"><path d="M0,72 L0,42 Q720,-10 1440,42 L1440,72 Z" fill="#F3F4F7"/></svg></div>
        </section>

        <section class="gfh-sec">
            <div class="gfh-container">
                <p class="gfh-svc-trust"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7 3v5c0 4.4-3 7.6-7 9-4-1.4-7-4.6-7-9V6l7-3z"/><path d="m9 12 2 2 4-4"/></svg> Every VA and vendor is verified before it&rsquo;s listed &mdash; so you always get the most trusted resource.</p>
                <div class="gfh-svc-grid"><?php echo $sCards; ?></div>
            </div>
        </section>

        <?php echo $sVendorBlock; ?>

        <section class="gfh-sec">
            <div class="gfh-container">
                <div class="gfh-cta">
                    <h2 class="gfh-cta-title">Not sure where to start?</h2>
                    <p class="gfh-cta-sub">Tell us what you need done and we&rsquo;ll match you to the right VA, vendor, or GFunnel team.</p>
                    <div class="gfh-cta-actions">
                        <a class="gfh-btn gfh-btn-orange" href="<?php echo $sContact; ?>">Talk to us</a>
                        <a class="gfh-btn gfh-btn-ghost" href="<?php echo $sJoinUrl; ?>">Create a workspace</a>
                    </div>
                </div>
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
                        <a href="<?php echo BX_DOL_URL_ROOT; ?>business">Businesses</a>
                        <a href="<?php echo BX_DOL_URL_ROOT; ?>applications">Software</a>
                        <a href="<?php echo $sContact; ?>">Contact</a>
                    </nav>
                </div>
            </div>
        </div>
    </footer>
</div>
</body>
</html>
<?php
exit;
