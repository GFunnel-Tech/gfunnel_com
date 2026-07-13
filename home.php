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
 *  - gf_root_home  'off' disables the page (root falls back to splash/home)
 */

require_once('./inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_INC . "design.inc.php");

bx_import('BxDolLanguages');

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
        'market_url' => $fnPageUrl('market-home'),
        'events_url' => $fnPageUrl('events-home'),
        'learn_url' => $fnPageUrl('courses-home'),
        'partners_url' => $fnPageUrl('affiliate-activities'),
        'pricing_url' => $fnPageUrl('pricing'),

        //--- Company
        'about_url' => $fnPageUrl('about'),
        'contact_url' => $fnPageUrl('contact'),
        'terms_url' => $fnPageUrl('terms'),
        'privacy_url' => $fnPageUrl('privacy')
    ]);
}

check_logged();

$oTemplate = BxDolTemplate::getInstance();
$oTemplate->setPageNameIndex(BX_PAGE_DEFAULT);
$oTemplate->setPageType(BX_PAGE_TYPE_DEFAULT_WO_HF);
$oTemplate->setPageHeader(bx_replace_markers(_t('_sys_page_title_home'), array('site_title' => getParam('site_title'))));
$oTemplate->setPageContent('page_main_code', getGfHomePageCode());
$oTemplate->getPageCode();

/** @} */
