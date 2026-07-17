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
// of Supabase (see gf_directory.php + the sync Edge Function):
//   /directory          -> searchable app grid
//   /directory/<slug>   -> per-app detail page
// gf_directory='off' disables it and falls through to normal routing.
if(getParam('gf_directory') != 'off') {
    $sGfDir = trim($sRequest, '/');
    $sGfDirLc = strtolower($sGfDir);
    if($sGfDirLc === 'directory' || strpos($sGfDirLc, 'directory/') === 0) {
        $GLOBALS['gf_directory_slug'] = ($sGfDirLc === 'directory') ? '' : substr($sGfDir, strlen('directory/'));
        require_once(BX_DIRECTORY_PATH_ROOT . 'gf_directory.php');
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
