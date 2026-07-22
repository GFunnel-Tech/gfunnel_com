<?php
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

require_once('./inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_INC . "design.inc.php");

bx_import('BxDolLanguages');

check_logged();

$sRequest = ltrim($_GET['_q'], '/');
$sPath = parse_url(BX_DOL_URL_ROOT, PHP_URL_PATH);
if ($sPath && '/' != $sPath)
    $sRequest = bx_ltrim_str($sRequest, rtrim($sPath, '/'));

// GFunnel: dark-themed auth pages. Routes /login and /create-account here
// (not just via .htaccess, which some deploys don't sync). gf_auth_pages='off'
// or a logged-in visitor falls through to the stock pages / redirect.
if(getParam('gf_auth_pages') != 'off') {
    $sGfReq = strtolower(trim($sRequest, '/'));
    if(!isLogged() && ($sGfReq === 'login' || $sGfReq === 'create-account')) {
        $sGfAuthMode = $sGfReq === 'create-account' ? 'join' : 'login';
        require_once(BX_DIRECTORY_PATH_ROOT . 'gf_auth.php');
        exit;
    }
    // Post-signup profile onboarding (gfunnel_onb redirects new members to
    // page/onboarding; /welcome is the friendly alias).
    if(isLogged() && ($sGfReq === 'welcome' || $sGfReq === 'onboarding')) {
        require_once(BX_DIRECTORY_PATH_ROOT . 'gf_onboarding.php');
        exit;
    }
}

// GFunnel: Application Directory. Public pages rendering the local MySQL mirror
// of Supabase (see gf_applications.php + the sync Edge Function):
//   /applications        -> searchable app grid
//   /application/<slug>  -> per-app detail page
// gf_applications='off' disables it and falls through to normal routing.
if(getParam('gf_applications') != 'off') {
    $sGfApp = trim($sRequest, '/');
    $sGfAppLc = strtolower($sGfApp);
    if($sGfAppLc === 'applications' || $sGfAppLc === 'application') {
        $GLOBALS['gf_app_slug'] = '';
        require_once(BX_DIRECTORY_PATH_ROOT . 'gf_applications.php');
        exit;
    }
    if(strpos($sGfAppLc, 'application/') === 0) {
        $GLOBALS['gf_app_slug'] = substr($sGfApp, strlen('application/'));
        require_once(BX_DIRECTORY_PATH_ROOT . 'gf_applications.php');
        exit;
    }
}

// GFunnel: Business Directory. A branded, SEO-optimized browse page over the
// Business Listing module (mz_listing):
//   /business (or /businesses) -> searchable, paginated directory
// gf_business='off' disables it and falls through to normal routing.
if(getParam('gf_business') != 'off') {
    $sGfBiz = strtolower(trim($sRequest, '/'));
    if($sGfBiz === 'business' || $sGfBiz === 'businesses') {
        require_once(BX_DIRECTORY_PATH_ROOT . 'gf_business.php');
        exit;
    }
}

// GFunnel: Services & Talent. A branded, SEO-optimized landing for hiring verified
// VAs and vendors, GFunnel's own done-for-you services, and DIY workspaces:
//   /services -> service paths + (live) verified-vendor slice
// gf_services='off' disables it and falls through to normal routing.
if(getParam('gf_services') != 'off') {
    $sGfSvc = strtolower(trim($sRequest, '/'));
    if($sGfSvc === 'services') {
        require_once(BX_DIRECTORY_PATH_ROOT . 'gf_services.php');
        exit;
    }
}

$aRewriteRules = BxDolRewriteRulesQuery::getActiveRules();
foreach ($aRewriteRules as $a) {
    if (preg_match('#'.$a['preg'].'#i', $sRequest, $aMatches)) {
        BxDolService::callSerialized($a['service'], $aMatches);
        exit;
    }
}

if (!BxDolPage::processSeoLink($sRequest)) {
    BxDolTemplate::getInstance()->displayPageNotFound();
}

/** @} */
