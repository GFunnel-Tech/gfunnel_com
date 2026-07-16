<?php
/**
 * GFunnel — dark-themed login & account-creation pages.
 *
 * Routed from /login and /create-account (see .htaccess). Renders the GFunnel
 * dark auth design around UNA's REAL forms + social buttons, so authentication,
 * CSRF, validation and OAuth all keep working - only the presentation changes.
 *
 * $sGfAuthMode ('login' | 'join') is set by the thin gf_login.php /
 * gf_create_account.php entry files before including this.
 *
 * Optional settings (sys_options):
 *  - gf_auth_pages   'off' falls back to UNA's default login/create-account pages
 */

require_once(dirname(__FILE__) . '/inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_INC . 'design.inc.php');

if(!isset($sGfAuthMode))
    $sGfAuthMode = 'login';

// Escape hatch: let admins revert to the stock pages without a redeploy.
if(getParam('gf_auth_pages') == 'off') {
    $sStock = $sGfAuthMode == 'join' ? 'page.php?i=create-account' : 'page.php?i=login';
    header('Location: ' . BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink($sStock));
    exit;
}

// Already signed in? Nothing to do here.
if(isLogged()) {
    header('Location: ' . BX_DOL_URL_ROOT);
    exit;
}

function getGfAuthPageCode($sMode)
{
    $oTemplate = BxDolTemplate::getInstance();
    $oPermalink = BxDolPermalinks::getInstance();

    $bJoin = $sMode == 'join';

    // Social auth buttons (Google / Facebook / Discord / ... — whatever is
    // configured in sys_objects_auths) rendered standalone so we control order.
    $sSocial = (string)BxDolService::call('system', 'member_auth_code', [], 'TemplServiceLogin');

    // UNA's real form WITHOUT its own social block (we place ours), keeping
    // CSRF, ajax, validation and the agreement line intact.
    if($bJoin) {
        $sForm = BxDolService::call('system', 'create_account_form', ['no_auth_buttons' => true], 'TemplServiceAccount');
        $sHeading = 'Create your account and start building';
        $sDivider = 'or sign up with email';
    }
    else {
        $sForm = BxDolService::call('system', 'login_form', ['no_auth_buttons no_join_text'], 'TemplServiceLogin');
        $sHeading = 'Sign in to GFunnel';
        $sDivider = 'or continue with';
    }

    // Brand logo (uses the site logo configured in Studio -> Designs).
    $sLogo = BxTemplFunctions::getInstance()->getMainLogo(['attrs' => ['class' => 'gf-auth-logo-link']]);

    $sCrossUrl = BX_DOL_URL_ROOT . $oPermalink->permalink('page.php?i=' . ($bJoin ? 'login' : 'create-account'));
    $sCssFile = 'template/css/gf_auth.css';

    $bSocial = trim(strip_tags($sSocial)) !== '' || strpos($sSocial, 'sys-auth') !== false;

    return $oTemplate->parseHtmlByName('gf_auth.html', [
        'css_url' => BX_DOL_URL_ROOT . $sCssFile . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sCssFile),
        'mode' => $bJoin ? 'join' : 'login',
        'logo' => $sLogo,
        'heading' => $sHeading,
        'form' => $sForm,
        // signup: social on top; login: social below the form (matches the design)
        'bx_if:social_top' => [
            'condition' => $bSocial && $bJoin,
            'content' => ['social' => $sSocial, 'divider' => $sDivider]
        ],
        'bx_if:social_bottom' => [
            'condition' => $bSocial && !$bJoin,
            'content' => ['social' => $sSocial, 'divider' => $sDivider]
        ],
        'bx_if:join' => [
            'condition' => $bJoin,
            'content' => ['login_url' => $sCrossUrl]
        ],
        'bx_if:login' => [
            'condition' => !$bJoin,
            'content' => ['join_url' => $sCrossUrl]
        ]
    ]);
}

$oTemplate = BxDolTemplate::getInstance();
$oTemplate->setPageNameIndex(BX_PAGE_DEFAULT);
$oTemplate->setPageType(BX_PAGE_TYPE_DEFAULT_WO_HF);
$oTemplate->setPageHeader($sGfAuthMode == 'join' ? 'Create your GFunnel account' : 'Sign in to GFunnel');
$oTemplate->setPageContent('page_main_code', getGfAuthPageCode($sGfAuthMode));
$oTemplate->getPageCode();

/** @} */
