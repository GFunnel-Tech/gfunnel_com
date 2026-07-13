<?php
/**
 * GFunnel — authorized root: workspace picker.
 *
 * Shown at the site root to logged-in members (see index.php). Lists the
 * account's workspace profiles (organizations, spaces, groups — any
 * non-person profile), with the single personal profile in its own card.
 *
 * Optional settings (sys_options):
 *  - gf_root_workspaces        'off' disables the whole page (root falls back to 'home')
 *  - gf_workspaces_create_url  create-workspace target, default page.php?i=create-organization
 *  - gf_workspaces_invite_url  join-with-invite-code form target; input hidden when empty
 *  - gf_workspaces_ref_url     referral link template ({id}/{display_name}); Earn card hidden when empty
 *  - gf_workspaces_support_url support link; support line hidden when empty
 */

require_once('./inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_INC . "design.inc.php");

bx_import('BxDolLanguages');

function getGfWorkspacesPageCode()
{
    $oTemplate = BxDolTemplate::getInstance();

    $oAccount = BxDolAccount::getInstance();
    $oProfile = BxDolProfile::getInstance(bx_get_logged_profile_id());
    if(!$oAccount || !$oProfile)
        return '';

    //--- Greeting by time of day
    $iHour = (int)date('G');
    if($iHour >= 5 && $iHour < 12) {
        $sGreeting = 'Good morning';
        $sTagline = 'A fresh canvas for whatever you build today.';
    }
    else if($iHour >= 12 && $iHour < 17) {
        $sGreeting = 'Good afternoon';
        $sTagline = 'Momentum looks good on you.';
    }
    else if($iHour >= 17 && $iHour < 22) {
        $sGreeting = 'Good evening';
        $sTagline = 'Wind down or wind up — your call.';
    }
    else {
        $sGreeting = 'Late night';
        $sTagline = 'The midnight blueprint is yours alone.';
    }

    $sDisplayName = $oProfile->getDisplayName();
    $sFirstName = trim(strtok($sDisplayName, ' '));
    if(empty($sFirstName))
        $sFirstName = $sDisplayName;

    //--- Account profiles: one personal profile + workspaces
    $oModuleQuery = BxDolModuleQuery::getInstance();
    $aModuleTitles = [];

    $aTmplVarsPersonal = [];
    $aTmplVarsWorkspaces = [];
    foreach($oAccount->getProfiles() as $iProfileId => $aProfileInfo) {
        if(empty($aProfileInfo['type']) || $aProfileInfo['type'] == 'system')
            continue;

        $oWsProfile = BxDolProfile::getInstance($iProfileId);
        if(!$oWsProfile)
            continue;

        $sType = $aProfileInfo['type'];
        if(!isset($aModuleTitles[$sType])) {
            $aModule = $oModuleQuery->getModuleByName($sType);
            $aModuleTitles[$sType] = !empty($aModule['title']) ? $aModule['title'] : ucfirst(str_replace(['bx_', '_'], ['', ' '], $sType));
        }

        $aUnit = [
            'url' => $oWsProfile->getUrl(),
            'title' => bx_process_output($oWsProfile->getDisplayName()),
            'thumb' => $oWsProfile->getThumb(),
            'type_label' => bx_process_output($aModuleTitles[$sType]),
            'status_active' => $aProfileInfo['status'] == 'active'
        ];

        if($sType == 'bx_persons') {
            if(empty($aTmplVarsPersonal) || $iProfileId == $oProfile->id())
                $aTmplVarsPersonal = $aUnit;
            continue;
        }

        $aTmplVarsWorkspaces[] = array_merge($aUnit, [
            'bx_if:pending' => [
                'condition' => $aProfileInfo['status'] != 'active',
                'content' => []
            ]
        ]);
    }

    //--- Optional pieces
    $oPermalink = BxDolPermalinks::getInstance();

    $sCreateUrl = trim((string)getParam('gf_workspaces_create_url'));
    if(empty($sCreateUrl))
        $sCreateUrl = 'page.php?i=create-organization';
    if(!preg_match('/^https?:\/\//i', $sCreateUrl))
        $sCreateUrl = BX_DOL_URL_ROOT . $oPermalink->permalink($sCreateUrl);

    $sInviteUrl = trim((string)getParam('gf_workspaces_invite_url'));
    if(!empty($sInviteUrl) && !preg_match('/^https?:\/\//i', $sInviteUrl))
        $sInviteUrl = BX_DOL_URL_ROOT . ltrim($sInviteUrl, '/');

    $sRefUrl = trim((string)getParam('gf_workspaces_ref_url'));
    if(!empty($sRefUrl))
        $sRefUrl = str_replace(['{id}', '{display_name}'], [$oProfile->id(), rawurlencode($sDisplayName)], $sRefUrl);

    $sSupportUrl = trim((string)getParam('gf_workspaces_support_url'));
    if(!empty($sSupportUrl) && !preg_match('/^https?:\/\//i', $sSupportUrl))
        $sSupportUrl = BX_DOL_URL_ROOT . $oPermalink->permalink($sSupportUrl);

    $sCssFile = 'template/css/gf_workspaces.css';

    return $oTemplate->parseHtmlByName('page_workspaces.html', [
        'css_url' => BX_DOL_URL_ROOT . $sCssFile . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sCssFile),
        'greeting' => $sGreeting,
        'first_name' => bx_process_output($sFirstName),
        'tagline' => $sTagline,
        'create_url' => $sCreateUrl,
        'bx_repeat:workspaces' => $aTmplVarsWorkspaces,
        'bx_if:has_workspaces' => [
            'condition' => !empty($aTmplVarsWorkspaces),
            'content' => []
        ],
        'bx_if:no_workspaces' => [
            'condition' => empty($aTmplVarsWorkspaces),
            'content' => ['create_url' => $sCreateUrl]
        ],
        'bx_if:invite' => [
            'condition' => !empty($sInviteUrl),
            'content' => ['invite_url' => $sInviteUrl]
        ],
        'bx_if:personal' => [
            'condition' => !empty($aTmplVarsPersonal),
            'content' => $aTmplVarsPersonal
        ],
        'bx_if:earn' => [
            'condition' => !empty($sRefUrl),
            'content' => ['ref_url' => bx_html_attribute($sRefUrl)]
        ],
        'bx_if:support' => [
            'condition' => !empty($sSupportUrl),
            'content' => ['support_url' => $sSupportUrl]
        ]
    ]);
}

check_logged();

if(!isLogged()) {
    header('Location: ' . BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=login'));
    exit;
}

$oTemplate = BxDolTemplate::getInstance();
$oTemplate->setPageNameIndex(BX_PAGE_DEFAULT);
$oTemplate->setPageHeader('My workspaces');
$oTemplate->setPageContent('page_main_code', getGfWorkspacesPageCode());
$oTemplate->getPageCode();

/** @} */
