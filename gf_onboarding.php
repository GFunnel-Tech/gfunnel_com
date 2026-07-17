<?php
/**
 * GFunnel — post-signup onboarding (dark theme). Step 2 of the signup flow:
 * profile setup (Name, Bio, Profile Image). Step 1 is /create-account; the
 * pricing step follows in a later pass.
 *
 * Routed from /welcome and /onboarding (and page/onboarding, which the
 * gfunnel_onb module's onLoginAfter hook redirects new members to). Embeds
 * UNA's NATIVE persons edit form, so name/bio/avatar use the real validation
 * and storage - nothing custom to break. Completion is gated by the
 * bx_persons_data.onboarding_completed flag (via gfunnel_onb).
 *
 * Optional settings:
 *  - gf_auth_pages 'off' bypasses onboarding entirely (straight to workspaces)
 */

require_once(dirname(__FILE__) . '/inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_INC . 'design.inc.php');

check_logged();

if(!isLogged()) {
    header('Location: ' . BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=login'));
    exit;
}

$oGfOnbProfile = BxDolProfile::getInstance(bx_get_logged_profile_id());
$oGfOnbModule = BxDolModule::getInstance('gfunnel_onb');

function gfOnbDone($oProfile, $oModule)
{
    if($oModule && method_exists($oModule, 'markOnboardingComplete'))
        $oModule->markOnboardingComplete($oProfile->id());

    header('Location: ' . BX_DOL_URL_ROOT . 'workspaces.php');
    exit;
}

// Escape hatch + skip / finish actions.
if(getParam('gf_auth_pages') == 'off')
    gfOnbDone($oGfOnbProfile, $oGfOnbModule);

if(bx_get('gf_done') !== false)
    gfOnbDone($oGfOnbProfile, $oGfOnbModule);

// Already onboarded? Move along.
if($oGfOnbModule && method_exists($oGfOnbModule, 'isOnboardingComplete') && $oGfOnbModule->isOnboardingComplete($oGfOnbProfile->id())) {
    header('Location: ' . BX_DOL_URL_ROOT . 'workspaces.php');
    exit;
}

function getGfOnboardingPageCode($oProfile)
{
    $oTemplate = BxDolTemplate::getInstance();

    // Native persons edit form (name, bio, avatar) - real update path.
    $sForm = '';
    if(BxDolRequest::serviceExists($oProfile->getModule(), 'entity_edit'))
        $sForm = (string)BxDolService::call($oProfile->getModule(), 'entity_edit', [$oProfile->getContentId()]);

    $sLogo = BxTemplFunctions::getInstance()->getMainLogo(['attrs' => ['class' => 'gf-auth-logo-link']]);
    $sCssFile = 'template/css/gf_onboarding.css';
    $sAuthCssFile = 'template/css/gf_auth.css';

    return $oTemplate->parseHtmlByName('gf_onboarding.html', [
        'auth_css_url' => BX_DOL_URL_ROOT . $sAuthCssFile . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sAuthCssFile),
        'css_url' => BX_DOL_URL_ROOT . $sCssFile . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sCssFile),
        'logo' => $sLogo,
        'form' => $sForm,
        'done_url' => BX_DOL_URL_ROOT . 'gf_onboarding.php?gf_done=1'
    ]);
}

$oTemplate = BxDolTemplate::getInstance();
$oTemplate->setPageNameIndex(BX_PAGE_DEFAULT);
$oTemplate->setPageType(BX_PAGE_TYPE_DEFAULT_WO_HF);
$oTemplate->setPageHeader('Set up your profile');
$oTemplate->setPageContent('page_main_code', getGfOnboardingPageCode($oGfOnbProfile));
$oTemplate->getPageCode();

/** @} */
