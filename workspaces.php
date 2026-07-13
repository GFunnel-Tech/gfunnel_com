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

// The picker keeps the plain 48px header (logo, search, timer, What's New and
// the member icons) - no hub-tabs subheader on this page.
BxTemplFunctions::$bGfToolbarSubheader = false;

function getGfWorkspacesPageCode()
{
    $oTemplate = BxDolTemplate::getInstance();

    $oAccount = BxDolAccount::getInstance();
    $oProfile = BxDolProfile::getInstance(bx_get_logged_profile_id());
    if(!$oAccount || !$oProfile)
        return '';

    /*
     * Greeting, affirmation and hero theme per the Workspaces dynamic-phrases
     * spec. Server time renders the initial state; the page JS re-evaluates
     * with the viewer's local clock (and every 60s) for full fidelity.
     */
    $iHour = (int)date('G');

    // phrase buckets: boundaries 3/6/12/17/21 (21:00-02:59 wraps to late-night)
    if($iHour < 3) $sBucket = 'late-night';
    else if($iHour < 6) $sBucket = 'early-morning';
    else if($iHour < 12) $sBucket = 'morning';
    else if($iHour < 17) $sBucket = 'afternoon';
    else if($iHour < 21) $sBucket = 'evening';
    else $sBucket = 'late-night';

    $aGreetings = [
        'late-night' => 'Late night',
        'early-morning' => 'Early morning',
        'morning' => 'Good morning',
        'afternoon' => 'Good afternoon',
        'evening' => 'Good evening'
    ];

    // deterministic affirmation: bank[(1-based day of year) % 7]
    $aAffirmations = [
        'late-night' => ['Builders work when the world rests.', 'Late, but never alone.', 'Tomorrow rewards what you do tonight.', 'Quiet hours, sharper thoughts.', 'The midnight blueprint is yours alone.', 'Patience is built one late hour at a time.', 'Correctness compounds — even at this hour.'],
        'early-morning' => ['The world is yours before it wakes.', 'Pre-dawn is a competitive advantage.', 'Whatever you build now, no one can take from you.', 'First light, first move.', 'The early hour is the honest hour.', 'Discipline before sunrise is a head start on the day.', 'Quiet hours, sharp thinking.'],
        'morning' => ["Make today's first move count.", 'What you do before noon shapes your week.', 'One thoughtful decision compounds.', 'Start with the hardest thing.', 'Mornings are for the work only you can do.', 'Build with patience. Ship with intent.', 'Equilibrium isn’t found. It’s built.'],
        'afternoon' => ['Pace beats panic.', 'Three solid hours can change a quarter.', 'Stay sharp.', 'Momentum is built one block at a time.', 'The afternoon belongs to depth, not speed.', 'Resilience is showing up at hour seven.', 'Quiet focus is loud over time.'],
        'evening' => ['Wrap with intent.', "Today's work is tomorrow's leverage.", 'Close the loops that matter.', 'Finish what you started.', 'End the day proud of one thing you shipped.', 'Reflect, then rest. Both are work.', 'Whatever you carried today, you can put down now.']
    ];

    $sGreeting = $aGreetings[$sBucket];
    $sTagline = $aAffirmations[$sBucket][((int)date('z') + 1) % 7];

    // banner theme uses its own boundaries: 5/12/17/22
    if($iHour < 5 || $iHour >= 22) $sDaypart = 'night';
    else if($iHour < 12) $sDaypart = 'morning';
    else if($iHour < 17) $sDaypart = 'afternoon';
    else $sDaypart = 'evening';

    // first-name resolution: never surface a slug or the email prefix as a name
    $sDisplayName = $oProfile->getDisplayName();
    $sEmail = (string)$oAccount->getEmail();
    $sFirstName = '';
    $sRaw = trim((string)$sDisplayName);
    if($sRaw !== '') {
        $aTokens = preg_split('/\s+/', $sRaw);
        $sFirst = isset($aTokens[0]) ? $aTokens[0] : '';
        $sEmailPrefix = $sEmail !== '' ? strtolower(explode('@', $sEmail)[0]) : '';

        $bReject = $sFirst === '';
        if(!$bReject && $sEmailPrefix !== '' && strtolower($sFirst) === $sEmailPrefix && !preg_match('/\s/', $sRaw))
            $bReject = true;
        if(!$bReject && preg_match('/^[a-z][a-z0-9_-]*$/', $sFirst) && $sFirst === $sRaw)
            $bReject = true;

        if(!$bReject)
            $sFirstName = $sFirst;
    }

    $sGreetingFull = $sFirstName !== '' ? $sGreeting . ', ' . $sFirstName : $sGreeting;

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
            // gf_ws pins the launched workspace in the session, so the member's
            // per-workspace menu (gf_user_menu) follows them (see
            // BxBaseFunctions::getGfActiveWorkspaceId)
            'url' => bx_append_url_params($oWsProfile->getUrl(), ['gf_ws' => $iProfileId]),
            'title' => bx_process_output($oWsProfile->getDisplayName()),
            'thumb' => $oWsProfile->getThumb()
        ];

        if($sType == 'bx_persons') {
            if(empty($aTmplVarsPersonal) || $iProfileId == $oProfile->id())
                $aTmplVarsPersonal = $aUnit;
            continue;
        }

        // joined-workspace roles come with the membership phase; owned profiles are 'owner'
        $sMeta = bx_process_output($aModuleTitles[$sType]) . ' &#183; owner';
        if($aProfileInfo['status'] != 'active')
            $sMeta .= ' &#183; pending';

        $aTmplVarsWorkspaces[] = array_merge($aUnit, ['meta' => $sMeta]);
    }

    // The personal workspace is NOT listed here - it lives in its own side card.

    // Pre-render the rows: nested bx_repeat inside bx_if isn't supported by the
    // compiled-template engine, so each row is parsed separately and passed as HTML.
    $sWorkspacesList = '';
    foreach($aTmplVarsWorkspaces as $aTmplVarsWorkspace)
        $sWorkspacesList .= $oTemplate->parseHtmlByName('page_workspaces_item.html', $aTmplVarsWorkspace);

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

    //--- Referral link: the Affiliate System module provides the member's real link;
    //--- gf_workspaces_ref_url overrides it, 'off' hides the Earn card entirely.
    $sRefUrl = trim((string)getParam('gf_workspaces_ref_url'));
    if($sRefUrl == 'off')
        $sRefUrl = '';
    else if(!empty($sRefUrl))
        $sRefUrl = str_replace(['{id}', '{display_name}'], [$oProfile->id(), rawurlencode($sDisplayName)], $sRefUrl);
    else if(BxDolRequest::serviceExists('aqb_affiliate', 'get_referral_code'))
        $sRefUrl = (string)BxDolService::call('aqb_affiliate', 'get_referral_code', [$oProfile->id()]);

    $sSupportUrl = trim((string)getParam('gf_workspaces_support_url'));
    if(!empty($sSupportUrl) && !preg_match('/^https?:\/\//i', $sSupportUrl))
        $sSupportUrl = BX_DOL_URL_ROOT . $oPermalink->permalink($sSupportUrl);

    $sPartnerUrl = trim((string)getParam('gf_workspaces_partner_url'));
    if(empty($sPartnerUrl))
        $sPartnerUrl = 'page.php?i=affiliate';
    if(!preg_match('/^https?:\/\//i', $sPartnerUrl))
        $sPartnerUrl = BX_DOL_URL_ROOT . $oPermalink->permalink($sPartnerUrl);

    $sCssFile = 'template/css/gf_workspaces.css';

    return $oTemplate->parseHtmlByName('page_workspaces.html', [
        'css_url' => BX_DOL_URL_ROOT . $sCssFile . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sCssFile),
        'greeting_full' => bx_process_output($sGreetingFull),
        'first_name' => bx_html_attribute($sFirstName),
        'tagline' => bx_process_output($sTagline),
        'daypart' => $sDaypart,
        'user_name' => bx_process_output($sDisplayName),
        'user_thumb' => $oProfile->getThumb(),
        'create_url' => $sCreateUrl,
        'bx_if:has_workspaces' => [
            'condition' => !empty($aTmplVarsWorkspaces),
            'content' => [
                'workspaces_list' => $sWorkspacesList,
                'create_url' => $sCreateUrl
            ]
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
            // content must stay non-empty even when hidden - the compiled-template
            // engine refuses to compile bx_if blocks with an empty content array
            'content' => !empty($aTmplVarsPersonal) ? $aTmplVarsPersonal : ['url' => '', 'thumb' => '', 'title' => '']
        ],
        'bx_if:earn' => [
            'condition' => !empty($sRefUrl),
            'content' => [
                'ref_url' => bx_html_attribute($sRefUrl),
                'partner_url' => $sPartnerUrl
            ]
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
