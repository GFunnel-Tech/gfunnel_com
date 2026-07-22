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

require_once(BX_DIRECTORY_PATH_INC . 'gf_home_blocks.inc.php'); // shared homepage block renderers (also used by the GFunnel Home module)


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
        'version_badge' => gfHomeVersionBadge(),
        'hero_stats' => gfHomeHeroStats(),
        'business_section' => gfHomeBusinessSection(),
        'services_section' => gfHomeServicesSection(),
        'catalog_cards' => gfHomeCatalogCards($fnPageUrl),
        'departments_grid' => gfHomeDepartmentsGrid(),
        'featured_section' => gfHomeFeaturedSection(),
        'resources_section' => gfHomeResourcesSection(),
        'community_feed' => gfHomeCommunityFeed(),
        'news_feed' => gfHomeNewsFeed()
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
