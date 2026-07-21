<?php
/**
 * GFunnel — public home page.
 *
 * Shown at the site root to logged-out visitors (see index.php). Renders the
 * marketing home page ("Every Tool. Every Action. One Platform.") standalone
 * from template/page_home.html, with its own header/footer chrome — the same
 * pattern as splash.php / workspaces.php.
 *
 * Optional settings (sys_options):
 *  - gf_root_home              'off' disables the page (root falls back to splash/home)
 *  - gf_home_meta_description  overrides the meta/og description
 *  - gf_org_same_as            newline/comma-separated official social profile
 *                              URLs, emitted as Organization sameAs (knowledge panel)
 */

require_once('./inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_INC . "design.inc.php");

bx_import('BxDolLanguages');

/**
 * The GFunnel operating model — the departments a business runs on, rendered as
 * process domains on the homepage. Values mirror the platform's `departments` table
 * (Supabase read plane, project yjneucgsaayyzoyxrlnb): 14 rows ordered by sort_order,
 * with the live name/subtitle/icon/color/color_bg values.
 *
 * Data source resolution (see docs/audits/homepage-audit.md, B1 + TODO T1):
 *   1. If a `gf_departments` MySQL table exists, read it live (ordered by sort_order).
 *      Load it with docs/sql/gf_departments.mysql.sql — that makes the grid live.
 *   2. Otherwise fall back to the static real snapshot below, so the page always
 *      renders correctly before the table is loaded.
 * The source table is membership-scoped in the read plane and not anon-readable, so a
 * local MySQL table (loaded by an admin, or later populated by a sync on the
 * directory-apps pattern) is the reachable path for this public PHP page.
 *
 * `icon` is a Tabler (ti-*) name mapped to an inline SVG by gfHomeDeptIcon().
 * Names/subtitles are plain text here and HTML-escaped at render time.
 */
function gfHomeDepartments()
{
    $oDb = BxDolDb::getInstance();
    if ($oDb->getOne("SHOW TABLES LIKE 'gf_departments'")) {
        $aRows = $oDb->getAll("SELECT `name`, `subtitle`, `icon`, `color`, `color_bg` FROM `gf_departments` ORDER BY `sort_order` ASC, `name` ASC");
        if (!empty($aRows))
            return $aRows;
    }
    return gfHomeDepartmentsStatic();
}

/** Real 14-department snapshot used when the gf_departments table is not loaded. */
function gfHomeDepartmentsStatic()
{
    return [
        ['name' => 'Strategy',                       'subtitle' => 'Planning, KPIs',                 'icon' => 'ti-target',          'color' => '#4338ca', 'color_bg' => 'rgba(99,102,241,.12)'],
        ['name' => 'Marketing',                      'subtitle' => 'Demand gen, brand, paid',        'icon' => 'ti-speakerphone',    'color' => '#be123c', 'color_bg' => 'rgba(244,63,94,.12)'],
        ['name' => 'Sales / Revenue',                'subtitle' => 'Sales, Partnerships',            'icon' => 'ti-trending-up',     'color' => '#047857', 'color_bg' => 'rgba(16,185,129,.12)'],
        ['name' => 'Product',                        'subtitle' => 'Roadmap, UX',                    'icon' => 'ti-box',             'color' => '#1d4ed8', 'color_bg' => 'rgba(59,130,246,.12)'],
        ['name' => 'Creative',                       'subtitle' => 'Design, Content',                'icon' => 'ti-palette',         'color' => '#be185d', 'color_bg' => 'rgba(236,72,153,.12)'],
        ['name' => 'Technology',                     'subtitle' => 'Dev, Integrations',              'icon' => 'ti-cpu',             'color' => '#6d28d9', 'color_bg' => 'rgba(124,58,237,.12)'],
        ['name' => 'AI & Automation',                'subtitle' => 'Agents, Chatbots',               'icon' => 'ti-robot',           'color' => '#0f766e', 'color_bg' => 'rgba(20,184,166,.12)'],
        ['name' => 'Delivery / Fulfillment',         'subtitle' => 'Service delivery, PM',           'icon' => 'ti-truck-delivery',  'color' => '#b45309', 'color_bg' => 'rgba(245,158,11,.14)'],
        ['name' => 'Operations',                     'subtitle' => 'CRM, Workflows, RevOps',         'icon' => 'ti-settings',        'color' => '#475569', 'color_bg' => 'rgba(100,116,139,.14)'],
        ['name' => 'Data & Analytics',               'subtitle' => 'BI, Attribution',                'icon' => 'ti-chart-bar',       'color' => '#0e7490', 'color_bg' => 'rgba(6,182,212,.12)'],
        ['name' => 'Customer Success & Support',     'subtitle' => 'Onboarding, Success',            'icon' => 'ti-headset',         'color' => '#7c3aed', 'color_bg' => 'rgba(124,58,237,.12)'],
        ['name' => 'People / HR',                    'subtitle' => 'Recruiting, Culture, Payroll',   'icon' => 'ti-users',           'color' => '#c2410c', 'color_bg' => 'rgba(249,115,22,.12)'],
        ['name' => 'Finance',                        'subtitle' => 'Billing, Accounting',            'icon' => 'ti-currency-dollar', 'color' => '#059669', 'color_bg' => 'rgba(16,185,129,.12)'],
        ['name' => 'Legal, Risk & Security',         'subtitle' => 'Contracts, Compliance, InfoSec', 'icon' => 'ti-shield',          'color' => '#334155', 'color_bg' => 'rgba(51,65,85,.12)'],
    ];
}

/**
 * Real count of directory apps, read from the MySQL mirror `gf_directory_apps`
 * (populated by the sync-directory-apps-to-mysql Edge Function; see gf_applications.php).
 * Returns 0 if the mirror table does not exist yet (pre first sync) so the hero stat
 * is omitted rather than showing a fake or zero number.
 */
function gfHomeAppsCount()
{
    $oDb = BxDolDb::getInstance();
    if (!$oDb->getOne("SHOW TABLES LIKE 'gf_directory_apps'"))
        return 0;
    return (int)$oDb->getOne("SELECT COUNT(*) FROM `gf_directory_apps`");
}

/** Format a real count as a conservative "N,000+" figure (floor, never inflate). */
function gfHomeCountPlus($iN)
{
    $iN = (int)$iN;
    if ($iN >= 1000) return number_format((int)floor($iN / 1000) * 1000) . '+';
    if ($iN >= 100)  return number_format((int)floor($iN / 100) * 100) . '+';
    return $iN > 0 ? number_format($iN) : '';
}

/**
 * Hero stat chips — real numbers only. The app count comes from the directory mirror;
 * the department count is the length of the real operating-model snapshot. Any stat
 * whose source is empty is omitted (no zero/placeholder chips).
 */
function gfHomeHeroStats()
{
    $aStats = [];

    $sApps = gfHomeCountPlus(gfHomeAppsCount());
    if ($sApps !== '')
        $aStats[] = ['num' => $sApps, 'label' => 'apps &amp; integrations'];

    $iDepts = count(gfHomeDepartments());
    if ($iDepts > 0)
        $aStats[] = ['num' => (string)$iDepts, 'label' => 'departments, one workspace'];

    if (empty($aStats))
        return '';

    $s = '';
    foreach ($aStats as $aStat)
        $s .= '<span class="gfh-hero-stat"><b>' . $aStat['num'] . '</b>' . $aStat['label'] . '</span>';

    return '<div class="gfh-hero-stats" role="list">' . $s . '</div>';
}

/**
 * Inline SVG for a Tabler (ti-*) icon name used by the departments snapshot, drawn in
 * the same stroke style as the rest of the homepage. Falls back to a neutral grid glyph.
 */
function gfHomeDeptIcon($sName)
{
    $aMap = [
        'ti-target'          => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none"/>',
        'ti-speakerphone'    => '<path d="M3 10v4h3l7 4V6L6 10H3z"/><path d="M16 9a3 3 0 0 1 0 6"/>',
        'ti-trending-up'     => '<path d="M3 17l6-6 4 4 8-8"/><path d="M17 7h4v4"/>',
        'ti-box'             => '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path d="M4 7.5 12 12l8-4.5M12 12v9"/>',
        'ti-palette'         => '<circle cx="12" cy="12" r="9"/><circle cx="8.5" cy="9" r="1"/><circle cx="15" cy="8" r="1"/><circle cx="16.5" cy="12.5" r="1"/>',
        'ti-cpu'             => '<rect x="6" y="6" width="12" height="12" rx="1"/><rect x="9" y="9" width="6" height="6"/><path d="M9 3v2M15 3v2M9 19v2M15 19v2M3 9h2M3 15h2M19 9h2M19 15h2"/>',
        'ti-robot'           => '<rect x="5" y="8" width="14" height="11" rx="2"/><path d="M12 4v4"/><circle cx="12" cy="3" r="1" fill="currentColor" stroke="none"/><circle cx="9.5" cy="13" r="1" fill="currentColor" stroke="none"/><circle cx="14.5" cy="13" r="1" fill="currentColor" stroke="none"/><path d="M9.5 16.5h5"/>',
        'ti-truck-delivery'  => '<path d="M3 6h11v9H3z"/><path d="M14 9h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/>',
        'ti-settings'        => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8 2 2 0 1 1-2.8 2.8 1.6 1.6 0 0 0-2.7 1.1 2 2 0 1 1-4 0 1.6 1.6 0 0 0-2.7-1.1 2 2 0 1 1-2.8-2.8A1.6 1.6 0 0 0 4 15a2 2 0 1 1 0-4 1.6 1.6 0 0 0 1-2.7 2 2 0 1 1 2.8-2.8A1.6 1.6 0 0 0 10 4a2 2 0 1 1 4 0 1.6 1.6 0 0 0 2.7 1 2 2 0 1 1 2.8 2.8A1.6 1.6 0 0 0 20 11a2 2 0 1 1 0 4 1.6 1.6 0 0 0-.6 0z"/>',
        'ti-chart-bar'       => '<path d="M3 3v18h18"/><path d="M7 16v-4M12 16V8M17 16v-6"/>',
        'ti-headset'         => '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="2" y="13" width="4" height="6" rx="1"/><rect x="18" y="13" width="4" height="6" rx="1"/><path d="M20 19a4 4 0 0 1-4 4h-2"/>',
        'ti-users'           => '<circle cx="9" cy="7" r="4"/><path d="M2 21v-2a7 7 0 0 1 14 0v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'ti-currency-dollar' => '<path d="M12 2v20"/><path d="M16 6.5C16 4.8 14.2 3.5 12 3.5S8 4.8 8 6.5 9.8 9.5 12 10s4 1.3 4 3.5-1.8 3.5-4 3.5-4-1.3-4-3"/>',
        'ti-shield'          => '<path d="M12 3l8 3v6c0 4.5-3.2 7.8-8 9-4.8-1.2-8-4.5-8-9V6l8-3z"/>',
    ];
    $sInner = isset($aMap[$sName]) ? $aMap[$sName] : '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>';
    return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $sInner . '</svg>';
}

/**
 * Department grid — the homepage's process-domain centerpiece. Renders the real
 * operating-model snapshot (gfHomeDepartments) as cards using each row's live
 * color/color_bg tint. No per-department process count is shown: the read plane has
 * no real count source (modules/templates are stubs — audit B2), so inventing one is
 * forbidden. Each card links to the app directory (the real "tools for running this"
 * surface); dedicated per-department routes are a documented follow-up (audit T-dept).
 */
function gfHomeDepartmentsGrid()
{
    $sMarketUrl = BX_DOL_URL_ROOT . 'applications';
    $s = '';
    foreach (gfHomeDepartments() as $aDept) {
        $sName = htmlspecialchars((string)$aDept['name'], ENT_QUOTES, 'UTF-8');
        $sSub = htmlspecialchars((string)$aDept['subtitle'], ENT_QUOTES, 'UTF-8');
        // colors land in a style attribute, so only allow hex / rgb(a) / named tokens
        $sColor = preg_replace('/[^A-Za-z0-9#.,()%\s]/', '', (string)$aDept['color']);
        $sColorBg = preg_replace('/[^A-Za-z0-9#.,()%\s]/', '', (string)$aDept['color_bg']);
        $s .= '<a class="gfh-dept-card gfh-reveal" href="' . $sMarketUrl . '">'
            . '<span class="gfh-dept-ico" style="color:' . $sColor . ';background:' . $sColorBg . '">'
            . gfHomeDeptIcon($aDept['icon']) . '</span>'
            . '<span class="gfh-dept-name">' . $sName . '</span>'
            . '<span class="gfh-dept-sub">' . $sSub . '</span>'
            . '</a>';
    }
    return $s;
}

/**
 * Earn card for the Build / Buy / Earn band. Affiliate/partner attribution lives in
 * the UNA write plane, which this read-only public homepage cannot drive yet
 * (audit B6 / TODO T4). The destination is gated behind `gf_home_earn_enabled`
 * (sys_options): off by default -> render the card with a "coming soon" state instead
 * of linking to a partners page that may not be public.
 */
function gfHomeEarnCard($fnPageUrl)
{
    $bEnabled = getParam('gf_home_earn_enabled') == 'on';
    $sIco = '<span class="gfh-path-ico" style="color:#0f766e;background:rgba(20,184,166,.12)">'
        . '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="18" r="3"/><path d="M8.5 7.5 15.5 16M9 18h6"/></svg></span>';

    if ($bEnabled) {
        return '<a class="gfh-path-card gfh-reveal" href="' . $fnPageUrl('affiliate-activities') . '">'
            . $sIco
            . '<span class="gfh-path-kicker">Earn</span>'
            . '<h3>Refer, resell, and earn</h3>'
            . '<p>Bring partners into your workspace and turn referrals into recurring income &mdash; tracked and paid where the work already happens.</p>'
            . '<span class="gfh-link-more">Explore partners <span aria-hidden="true">&rarr;</span></span>'
            . '</a>';
    }

    return '<div class="gfh-path-card gfh-reveal gfh-path-soon">'
        . $sIco
        . '<span class="gfh-path-kicker">Earn <span class="gfh-soon">Coming soon</span></span>'
        . '<h3>Refer, resell, and earn</h3>'
        . '<p>Partner and affiliate payouts run on the GFunnel account layer. The public earn surface is on the way &mdash; it isn&rsquo;t wired into this page yet.</p>'
        . '<span class="gfh-link-more gfh-link-disabled" aria-disabled="true">In the works <span aria-hidden="true">&rarr;</span></span>'
        . '</div>';
}

function getGfHomePageCode()
{
    $oTemplate = BxDolTemplate::getInstance();
    $oPermalink = BxDolPermalinks::getInstance();

    $fnPageUrl = function($sUri) use ($oPermalink) {
        return BX_DOL_URL_ROOT . $oPermalink->permalink('page.php?i=' . $sUri);
    };

    $sCssFile = 'template/css/gf_home.css';
    $sJsFile = 'template/js/gf_home.js';

    return $oTemplate->parseHtmlByName('page_home.html', [
        'css_url' => BX_DOL_URL_ROOT . $sCssFile . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sCssFile),
        'js_url' => BX_DOL_URL_ROOT . $sJsFile . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sJsFile),
        'site_url' => BX_DOL_URL_ROOT,
        'search_url' => BX_DOL_URL_ROOT . 'searchKeyword.php',
        'year' => date('Y'),

        //--- Auth
        'login_url' => $fnPageUrl('login'),
        'join_url' => $fnPageUrl('create-account'),

        //--- Audiences
        'personal_url' => $fnPageUrl('persons-home'),
        'business_url' => $fnPageUrl('organizations-home'),
        'agencies_url' => $fnPageUrl('spaces-home'),

        //--- Explore
        'community_url' => $fnPageUrl('home'),
        'applications_url' => BX_DOL_URL_ROOT . 'applications',
        'market_url' => $fnPageUrl('market-home'),
        'events_url' => $fnPageUrl('events-home'),
        'learn_url' => $fnPageUrl('courses-home'),
        'partners_url' => $fnPageUrl('affiliate-activities'),
        'pricing_url' => $fnPageUrl('pricing'),

        //--- Marketplace / directory (real route, see r.php + gf_applications.php)
        'marketplace_url' => BX_DOL_URL_ROOT . 'applications',

        //--- Company
        'about_url' => $fnPageUrl('about'),
        'contact_url' => $fnPageUrl('contact'),
        'terms_url' => $fnPageUrl('terms'),
        'privacy_url' => $fnPageUrl('privacy'),
        'dmca_url' => $fnPageUrl('dmca'),
        'cookies_url' => $fnPageUrl('cookies'),

        //--- Real, computed content (no placeholders — see docs/audits/homepage-audit.md)
        'hero_stats' => gfHomeHeroStats(),
        'departments_grid' => gfHomeDepartmentsGrid(),
        'earn_card' => gfHomeEarnCard($fnPageUrl)
    ]);
}

/**
 * Organization + WebSite JSON-LD for the site root — feeds Google's brand
 * knowledge panel and the sitelinks search box. Emitted only here (Google
 * wants Organization markup on one representative page), escaped as valid
 * JSON so it can't break the page.
 */
function getGfHomeStructuredData($sDescription)
{
    $oPermalink = BxDolPermalinks::getInstance();
    $sSiteName = getParam('site_title');

    // official logo, reusing the same site-icon storage the meta tags fall back to
    $sLogo = '';
    if (($oImgStorage = BxDolStorage::getObjectInstance(BX_DOL_STORAGE_OBJ_IMAGES)) !== false)
        foreach (['icon_apple', 'icon_android', 'icon_android_splash'] as $sIcon)
            if (($iIcon = (int)getParam('sys_site_' . $sIcon)) != 0 && ($sUrl = $oImgStorage->getFileUrlById($iIcon))) {
                $sLogo = $sUrl;
                break;
            }

    $aOrganization = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $sSiteName,
        'url' => BX_DOL_URL_ROOT,
        'description' => $sDescription,
    ];
    if ($sLogo)
        $aOrganization['logo'] = $sLogo;

    // official social profiles → sameAs (helps entity disambiguation)
    $aSameAs = preg_split('/[\s,]+/', trim((string)getParam('gf_org_same_as')), -1, PREG_SPLIT_NO_EMPTY);
    if (!empty($aSameAs))
        $aOrganization['sameAs'] = array_values($aSameAs);

    $sSearchResults = BX_DOL_URL_ROOT . $oPermalink->permalink('page.php?i=search-keyword');
    $aWebSite = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $sSiteName,
        'url' => BX_DOL_URL_ROOT,
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => $sSearchResults . '?keyword={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];

    $iFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG;
    return '<script type="application/ld+json">' . json_encode($aOrganization, $iFlags) . '</script>'
         . '<script type="application/ld+json">' . json_encode($aWebSite, $iFlags) . '</script>';
}

check_logged();

$sMetaDescription = trim((string)getParam('gf_home_meta_description'));
if ('' === $sMetaDescription)
    $sMetaDescription = 'GFunnel is the operating hub entrepreneurs run their businesses on — community, CRM, funnels, courses, events and marketplace, from the first idea to launch to scale. Every tool. Every action. One platform.';

$oTemplate = BxDolTemplate::getInstance();
$oTemplate->setPageNameIndex(BX_PAGE_DEFAULT);
$oTemplate->setPageType(BX_PAGE_TYPE_DEFAULT_WO_HF);
$oTemplate->setPageHeader(bx_replace_markers(_t('_sys_page_title_home'), array('site_title' => getParam('site_title'))));
$oTemplate->setPageDescription($sMetaDescription);
$oTemplate->setPageUrl(BX_DOL_URL_ROOT); // canonical: the site root
$oTemplate->addInjection('meta_info', 'text', getGfHomeStructuredData($sMetaDescription)); // Organization + WebSite JSON-LD
$oTemplate->setPageContent('page_main_code', getGfHomePageCode());
$oTemplate->getPageCode();

/** @} */
