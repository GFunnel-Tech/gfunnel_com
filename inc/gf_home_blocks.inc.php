<?php
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * GFunnel — homepage block renderers (shared).
 *
 * Single source of truth for the homepage's section HTML. Required by BOTH:
 *   - home.php            (the standalone public homepage), and
 *   - the "GFunnel Home" module's services (modules/gfunnel/home) so each section
 *     is available as a NATIVE UNA page-builder block (add/reorder/toggle in Studio).
 *
 * Pure functions over UNA's always-available BxDolDb / getParam / BxDolPermalinks —
 * no module dependency, so the standalone page keeps working whether or not the
 * GFunnel Home module is installed.
 *
 * Data sources & the no-fake-data rules are documented in
 * docs/audits/homepage-audit.md. Counts are real-or-omitted; feeds are
 * real-or-empty-state; the department set reads gf_departments (else a static
 * real snapshot); featured apps read the gf_directory_apps mirror.
 */

if (!defined('BX_DOL')) { exit('Restricted access'); }

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

    return '<div class="gfh-hero-stats">' . $s . '</div>';
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
 * Platform status badge for the nav (Star-Head-style "LIVE" pill). Shows a real
 * version only if `gf_platform_version` (sys_options) is set — never a fabricated
 * number; otherwise just an honest live-status pill.
 */
function gfHomeVersionBadge()
{
    $sVer = trim((string)getParam('gf_platform_version'));
    $s = '<span class="gfh-live" title="Platform status">'
        . '<span class="gfh-live-dot" aria-hidden="true"></span>';
    if ($sVer !== '')
        $s .= '<b>' . htmlspecialchars($sVer, ENT_QUOTES, 'UTF-8') . '</b>';
    $s .= 'Live</span>';
    return $s;
}

/** Inline SVG for a catalog-card icon. */
function gfHomeCatIcon($sName)
{
    $aMap = [
        'grid'   => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'apps'   => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
        'plug'   => '<path d="M9 2v6M15 2v6"/><path d="M7 8h10v3a5 5 0 0 1-10 0V8z"/><path d="M12 16v6"/>',
        'layout' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
        'book'   => '<path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"/><path d="M4 19a2 2 0 0 0 2 2h13"/>',
        'share'  => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="18" r="3"/><path d="M8.5 7.5 15.5 16M9 18h6"/>',
    ];
    $sInner = isset($aMap[$sName]) ? $aMap[$sName] : $aMap['grid'];
    return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $sInner . '</svg>';
}

/**
 * Catalog cards — the Star-Head-style "Database" row: the ecosystem's browsable
 * surfaces. Counts are shown only where a real number is reachable (apps from the
 * directory mirror; the department count) — everything else is description-only, no
 * fabricated totals. Partners is gated behind gf_home_earn_enabled (audit B6/T4).
 */
function gfHomeCatalogCards($fnPageUrl)
{
    $sMarket = BX_DOL_URL_ROOT . 'applications';
    $sApps = gfHomeCountPlus(gfHomeAppsCount());
    $iDepts = count(gfHomeDepartments());
    $bEarn = getParam('gf_home_earn_enabled') == 'on';

    $aCards = [
        ['ico' => 'grid',   'title' => 'Departments',  'desc' => 'Run every function of your business, from Strategy and Sales to Operations, Finance and Legal.', 'href' => '#departments',           'count' => ($iDepts > 0 ? (string)$iDepts : '')],
        ['ico' => 'apps',   'title' => 'Marketplace',  'desc' => 'Apps and integrations, ready to install straight into your workspace.',                        'href' => $sMarket,                 'count' => $sApps],
        ['ico' => 'plug',   'title' => 'Integrations', 'desc' => 'Connect the tools you already use — Stripe, Slack, Google, OpenAI and many more.',              'href' => $sMarket,                 'count' => ''],
        ['ico' => 'layout', 'title' => 'Templates',    'desc' => 'Deploy proven funnels, sites and automations instead of building from zero.',                  'href' => $sMarket,                 'count' => ''],
        ['ico' => 'book',   'title' => 'Learn',        'desc' => 'Guides, courses and the GFunnel University, organized by department.',                         'href' => $fnPageUrl('courses-home'), 'count' => ''],
        ['ico' => 'share',  'title' => 'Partners',     'desc' => 'The white-label and affiliate network for agencies and partners.',                             'href' => ($bEarn ? $fnPageUrl('affiliate-activities') : ''), 'count' => '', 'soon' => !$bEarn],
    ];

    $s = '';
    foreach ($aCards as $aCard) {
        $bSoon = !empty($aCard['soon']);
        $sTitle = htmlspecialchars($aCard['title'], ENT_QUOTES, 'UTF-8');
        $sDesc = htmlspecialchars($aCard['desc'], ENT_QUOTES, 'UTF-8');
        $sBadge = '';
        if ($aCard['count'] !== '')
            $sBadge = '<span class="gfh-cat-count">' . htmlspecialchars($aCard['count'], ENT_QUOTES, 'UTF-8') . '</span>';
        elseif ($bSoon)
            $sBadge = '<span class="gfh-cat-count gfh-cat-soon">Soon</span>';

        $sInner = '<span class="gfh-cat-ico">' . gfHomeCatIcon($aCard['ico']) . '</span>'
            . '<span class="gfh-cat-head"><span class="gfh-cat-title">' . $sTitle . '</span>' . $sBadge . '</span>'
            . '<span class="gfh-cat-desc">' . $sDesc . '</span>';

        if ($bSoon)
            $s .= '<div class="gfh-cat-card gfh-cat-card-soon">' . $sInner . '</div>';
        else
            $s .= '<a class="gfh-cat-card" href="' . htmlspecialchars($aCard['href'], ENT_QUOTES, 'UTF-8') . '">' . $sInner . '<span class="gfh-cat-go" aria-hidden="true">&rarr;</span></a>';
    }
    return $s;
}

/**
 * Featured apps & modules — pulled LIVE from the directory mirror `gf_directory_apps`
 * (the platform's real apps/modules, synced from Supabase; see gf_applications.php).
 * Featured rows first, falling back to the most recent. Returns the whole <section>,
 * or '' so the section is omitted entirely when the mirror is absent/empty — the page
 * stays honest with no placeholder apps. Cards deep-link to /application/<slug>.
 */
function gfHomeFeaturedSection()
{
    $oDb = BxDolDb::getInstance();
    if (!$oDb->getOne("SHOW TABLES LIKE 'gf_directory_apps'"))
        return '';

    $sCols = "`name`, `slug`, `description`, `logo_url`, `category`, `is_gfunnel_native`";
    $aApps = $oDb->getAll("SELECT $sCols FROM `gf_directory_apps` WHERE `is_featured` = 1 ORDER BY `name` LIMIT 8");
    if (empty($aApps))
        $aApps = $oDb->getAll("SELECT $sCols FROM `gf_directory_apps` ORDER BY `created_at` DESC LIMIT 8");
    if (empty($aApps))
        return '';

    $sBase = BX_DOL_URL_ROOT . 'application/';
    $sCards = '';
    foreach ($aApps as $a) {
        $sNameRaw = trim((string)$a['name']);
        $sName = htmlspecialchars($sNameRaw, ENT_QUOTES, 'UTF-8');
        $sSlug = trim((string)$a['slug']);
        $sHref = $sSlug !== '' ? $sBase . rawurlencode($sSlug) : BX_DOL_URL_ROOT . 'applications';
        $sCat = htmlspecialchars((string)$a['category'], ENT_QUOTES, 'UTF-8');
        $sDesc = htmlspecialchars((string)$a['description'], ENT_QUOTES, 'UTF-8');
        $sLogo = trim((string)$a['logo_url']);
        $sMedia = preg_match('#^https?://#i', $sLogo)
            ? '<img src="' . htmlspecialchars($sLogo, ENT_QUOTES, 'UTF-8') . '" alt="" loading="lazy" />'
            : '<span class="gfh-app-ini">' . htmlspecialchars(mb_strtoupper(mb_substr($sNameRaw, 0, 1)), ENT_QUOTES, 'UTF-8') . '</span>';
        $sBadge = !empty($a['is_gfunnel_native']) ? '<span class="gfh-app-badge">Native</span>' : '';
        $sCards .= '<a class="gfh-app-card" href="' . $sHref . '">'
            . '<span class="gfh-app-logo">' . $sMedia . '</span>'
            . '<span class="gfh-app-info">'
            . '<span class="gfh-app-name">' . $sName . $sBadge . '</span>'
            . ($sCat !== '' ? '<span class="gfh-app-cat">' . $sCat . '</span>' : '')
            . ($sDesc !== '' ? '<span class="gfh-app-desc">' . $sDesc . '</span>' : '')
            . '</span></a>';
    }

    $sMarket = BX_DOL_URL_ROOT . 'applications';
    return '<section class="gfh-sec" id="apps">'
        . '<div class="gfh-container">'
        . '<div class="gfh-sec-head gfh-sec-head-left gfh-sec-head-row gfh-reveal">'
        . '<div><span class="gfh-eyebrow">Featured</span><h2 class="gfh-h2">Apps &amp; modules, ready to plug in.</h2>'
        . '<p class="gfh-sub">Pulled live from the GFunnel directory &mdash; install any of them into your workspace.</p></div>'
        . '<a class="gfh-link-more" href="' . $sMarket . '">Browse all apps <span aria-hidden="true">&rarr;</span></a>'
        . '</div>'
        . '<div class="gfh-app-grid">' . $sCards . '</div>'
        . '</div></section>';
}

/** Shared designed empty state for a homepage feed column (no fabricated rows). */
function gfHomeFeedEmpty($sTitle, $sSub)
{
    return '<div class="gfh-feed-empty">'
        . '<span class="gfh-feed-empty-ico" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v12H8l-4 4z"/></svg></span>'
        . '<p class="gfh-feed-empty-title">' . htmlspecialchars($sTitle, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p class="gfh-feed-empty-sub">' . htmlspecialchars($sSub, ENT_QUOTES, 'UTF-8') . '</p>'
        . '</div>';
}

/**
 * News &amp; guides feed. Renders real published content from a `gf_content_objects`
 * MySQL table when present (newest first); otherwise a designed empty state.
 * The read-plane `content_objects` type is not anon-readable and not mirrored, so
 * live rows require loading/syncing that table (audit B5 / TODO T2) — no fake rows.
 */
function gfHomeNewsFeed()
{
    $oDb = BxDolDb::getInstance();
    $aRows = [];
    if ($oDb->getOne("SHOW TABLES LIKE 'gf_content_objects'"))
        $aRows = $oDb->getAll("SELECT `title`, `excerpt`, `published_at`, `canonical_url` FROM `gf_content_objects` WHERE `status` = 'published' ORDER BY `published_at` DESC LIMIT 5");

    if (empty($aRows))
        return gfHomeFeedEmpty('Guides are on the way', 'Product updates and how-to guides will appear here as they’re published.');

    $s = '<ul class="gfh-feed-list">';
    foreach ($aRows as $aRow) {
        $sTitle = htmlspecialchars((string)$aRow['title'], ENT_QUOTES, 'UTF-8');
        $sExcerpt = htmlspecialchars((string)$aRow['excerpt'], ENT_QUOTES, 'UTF-8');
        $sWhen = gfHomeFeedDate($aRow['published_at']);
        $sUrl = (string)$aRow['canonical_url'];
        $bLink = (bool)preg_match('#^https?://#i', $sUrl);
        $sOpen = $bLink ? '<a class="gfh-feed-item" href="' . htmlspecialchars($sUrl, ENT_QUOTES, 'UTF-8') . '">' : '<div class="gfh-feed-item">';
        $sClose = $bLink ? '</a>' : '</div>';
        $s .= $sOpen
            . '<span class="gfh-feed-item-title">' . $sTitle . '</span>'
            . ($sExcerpt !== '' ? '<span class="gfh-feed-item-sub">' . $sExcerpt . '</span>' : '')
            . ($sWhen !== '' ? '<span class="gfh-feed-item-meta">' . $sWhen . '</span>' : '')
            . $sClose;
    }
    return $s . '</ul>';
}

/**
 * Community feed. Renders recent public posts from a `gf_community_posts` MySQL table
 * when present; otherwise a designed empty state. The read-plane `posts_posts` feed is
 * not anon-readable and not mirrored (audit B4 / TODO T3), so live rows require a
 * mirror or a native UNA-posts wire — never fabricated posts.
 */
function gfHomeCommunityFeed()
{
    $oDb = BxDolDb::getInstance();
    $aRows = [];
    if ($oDb->getOne("SHOW TABLES LIKE 'gf_community_posts'"))
        $aRows = $oDb->getAll("SELECT `title`, `excerpt`, `author_name`, `published_at`, `url` FROM `gf_community_posts` WHERE `status` = 'active' ORDER BY `published_at` DESC LIMIT 5");

    if (empty($aRows))
        return gfHomeFeedEmpty('The community is warming up', 'Highlights from the GFunnel community will show up here. Jump in and start the conversation.');

    $s = '<ul class="gfh-feed-list">';
    foreach ($aRows as $aRow) {
        $sTitle = htmlspecialchars((string)$aRow['title'], ENT_QUOTES, 'UTF-8');
        $sAuthor = htmlspecialchars((string)$aRow['author_name'], ENT_QUOTES, 'UTF-8');
        $sWhen = gfHomeFeedDate($aRow['published_at']);
        $sMeta = trim($sAuthor . ($sAuthor !== '' && $sWhen !== '' ? ' &middot; ' : '') . $sWhen);
        $sUrl = (string)$aRow['url'];
        $bLink = (bool)preg_match('#^https?://#i', $sUrl);
        $sOpen = $bLink ? '<a class="gfh-feed-item" href="' . htmlspecialchars($sUrl, ENT_QUOTES, 'UTF-8') . '">' : '<div class="gfh-feed-item">';
        $sClose = $bLink ? '</a>' : '</div>';
        $s .= $sOpen
            . '<span class="gfh-feed-item-title">' . $sTitle . '</span>'
            . ($sMeta !== '' ? '<span class="gfh-feed-item-meta">' . $sMeta . '</span>' : '')
            . $sClose;
    }
    return $s . '</ul>';
}

/** Format a feed timestamp ('M j, Y'); returns '' if unparseable. */
function gfHomeFeedDate($sWhen)
{
    $sWhen = trim((string)$sWhen);
    if ($sWhen === '')
        return '';
    $iTs = strtotime($sWhen);
    return $iTs ? date('M j, Y', $iTs) : '';
}

/* ==================================================================
 * URL + season helpers
 * ================================================================== */

/** Permalinked UNA page URL (page.php?i=<uri>). */
function gfHomeUrl($sUri)
{
    return BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=' . $sUri);
}

/** The app directory / marketplace route (see r.php + gf_applications.php). */
function gfHomeMarketUrl()
{
    return BX_DOL_URL_ROOT . 'applications';
}

/** Current meteorological season (Northern hemisphere) from the server date. */
function gfHomeCurrentSeason()
{
    $iMonth = (int)date('n');
    if ($iMonth >= 3 && $iMonth <= 5)  return 'spring';
    if ($iMonth >= 6 && $iMonth <= 8)  return 'summer';
    if ($iMonth >= 9 && $iMonth <= 11) return 'fall';
    return 'winter';
}

/**
 * Whether a scheduled block should be visible now. Params (all optional):
 *  - 'active_from' / 'active_to': 'Y-m-d' (or any strtotime value) window bounds.
 *  - 'season': 'spring'|'summer'|'fall'|'winter'|'any' (or a comma list).
 * Empty/absent constraints mean "always". This is how seasonality works, since
 * UNA page blocks have no native date window (see docs/audits/homepage-audit.md).
 */
function gfHomeSeasonalVisible($aParams)
{
    $iNow = time();
    $sFrom = isset($aParams['active_from']) ? trim((string)$aParams['active_from']) : '';
    $sTo = isset($aParams['active_to']) ? trim((string)$aParams['active_to']) : '';
    if ($sFrom !== '' && ($iF = strtotime($sFrom)) && $iNow < $iF)
        return false;
    if ($sTo !== '' && ($iT = strtotime($sTo)) && $iNow > $iT)
        return false;

    $sSeason = isset($aParams['season']) ? strtolower(trim((string)$aParams['season'])) : '';
    if ($sSeason !== '' && $sSeason !== 'any') {
        $aSeasons = preg_split('/[\s,]+/', $sSeason, -1, PREG_SPLIT_NO_EMPTY);
        if (!in_array(gfHomeCurrentSeason(), $aSeasons, true))
            return false;
    }
    return true;
}

/**
 * Seasonal HTML block — renders admin-provided markup only within its date/season
 * window. This is the "code within a block that changes by season" surface: a
 * page-builder block whose params carry the HTML + window. Returns '' when out of
 * window so the block simply disappears.
 * Params: 'html' (trusted markup), plus the gfHomeSeasonalVisible() window params.
 */
function gfHomeSeasonalHtml($aParams)
{
    if (!gfHomeSeasonalVisible($aParams))
        return '';
    $sHtml = isset($aParams['html']) ? (string)$aParams['html'] : '';
    if (trim($sHtml) === '')
        return '';
    return '<div class="gfh gfh-block-seasonal">' . $sHtml . '</div>';
}

/* ==================================================================
 * Full-section wrappers (self-contained) — used by the module's
 * service blocks so each homepage section is one native page block.
 * ================================================================== */

/** Hero section (wordmark + global search + real stat line). */
function gfHomeSectionHero()
{
    $sSearch = BX_DOL_URL_ROOT . 'searchKeyword.php';
    return '<section class="gfh gfh-hero">'
        . '<div class="gfh-hero-glow" aria-hidden="true"></div><div class="gfh-hero-grid" aria-hidden="true"></div>'
        . '<div class="gfh-hero-inner">'
        . '<span class="gfh-hero-mark" aria-hidden="true"><svg width="72" height="72" viewBox="0 0 48 48" fill="none"><path d="M24 3 42 13.5v21L24 45 6 34.5v-21L24 3z" stroke="#94A3B8" stroke-width="2.5" stroke-linejoin="round"/><path d="M6 13.5 24 24l18-10.5M24 24v21" stroke="#CBD5E1" stroke-width="1.5" stroke-linejoin="round"/><path d="m12 22 9 5v8l-9-5v-8z" fill="#EA580C"/><path d="m19 17 9 5v8l-9-5v-8z" fill="#F97316"/><path d="m26 12 9 5v8l-9-5v-8z" fill="#FB923C"/></svg></span>'
        . '<h1 class="gfh-hero-word">GFunnel</h1>'
        . '<p class="gfh-hero-sub">The operating hub for how your business runs. Departments, apps, integrations and automation &mdash; one searchable workspace.</p>'
        . '<form class="gfh-hero-search" action="' . $sSearch . '" method="get" role="search">'
        . '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>'
        . '<input type="text" name="keyword" placeholder="Search departments, apps, integrations, guides..." autocomplete="off" aria-label="Search GFunnel" />'
        . '<button type="submit">Search</button></form>'
        . gfHomeHeroStats()
        . '</div></section>';
}

/** Explore / catalogs section. */
function gfHomeSectionCatalogs()
{
    $fn = 'gfHomeUrl';
    return '<section class="gfh gfh-sec" id="explore"><div class="gfh-container">'
        . '<div class="gfh-sec-head"><h2 class="gfh-h2">Explore the ecosystem</h2>'
        . '<p class="gfh-sub">Every surface of GFunnel, in one hub &mdash; browse it, search it, and deploy it into your workspace.</p></div>'
        . '<div class="gfh-cat-grid">' . gfHomeCatalogCards($fn) . '</div>'
        . '</div></section>';
}

/** Department grid section. */
function gfHomeSectionDepartments()
{
    return '<section class="gfh gfh-sec gfh-sec-alt" id="departments"><div class="gfh-container">'
        . '<div class="gfh-sec-head"><span class="gfh-eyebrow">The Operating Model</span>'
        . '<h2 class="gfh-h2">Every department your business runs on.</h2>'
        . '<p class="gfh-sub">GFunnel is organized the way a business actually works &mdash; as departments, each with the processes and tools it needs.</p></div>'
        . '<div class="gfh-dept-grid">' . gfHomeDepartmentsGrid() . '</div>'
        . '</div></section>';
}

/** Featured apps/modules section (already self-contained; wrapped for namespacing). */
function gfHomeSectionFeatured()
{
    $s = gfHomeFeaturedSection();
    return $s === '' ? '' : '<div class="gfh">' . $s . '</div>';
}

/** News & Community section. */
function gfHomeSectionCommunityNews()
{
    $sCommunity = gfHomeUrl('home');
    $sLearn = gfHomeUrl('courses-home');
    return '<section class="gfh gfh-sec" id="community"><div class="gfh-container">'
        . '<div class="gfh-sec-head gfh-sec-head-left"><h2 class="gfh-h2">News &amp; Community</h2>'
        . '<p class="gfh-sub">What&rsquo;s happening across the GFunnel ecosystem.</p></div>'
        . '<div class="gfh-feeds">'
        . '<div class="gfh-feed-col"><div class="gfh-feed-head"><h3 class="gfh-feed-title">From the community</h3>'
        . '<a class="gfh-link-more" href="' . $sCommunity . '">Open community <span aria-hidden="true">&rarr;</span></a></div>' . gfHomeCommunityFeed() . '</div>'
        . '<div class="gfh-feed-col"><div class="gfh-feed-head"><h3 class="gfh-feed-title">News &amp; guides</h3>'
        . '<a class="gfh-link-more" href="' . $sLearn . '">Browse all <span aria-hidden="true">&rarr;</span></a></div>' . gfHomeNewsFeed() . '</div>'
        . '</div></div></section>';
}

/** Closing CTA section. */
function gfHomeSectionCta()
{
    $sJoin = gfHomeUrl('create-account');
    $sPricing = gfHomeUrl('pricing');
    return '<section class="gfh gfh-cta"><div class="gfh-cta-inner">'
        . '<h2>Start your workspace.</h2>'
        . '<p>Free to start. Every department, app and automation connected from day one.</p>'
        . '<div class="gfh-hero-ctas"><a class="gfh-btn gfh-btn-orange" href="' . $sJoin . '">Create A Workspace</a>'
        . '<a class="gfh-btn gfh-btn-ghost" href="' . $sPricing . '">Explore Pricing</a></div>'
        . '<p class="gfh-hero-note">No credit card required &middot; Free forever plan</p>'
        . '</div></section>';
}
