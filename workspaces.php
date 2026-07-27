<?php
/**
 * GFunnel — authorized root: workspace picker.
 *
 * Shown at the site root to logged-in members (see index.php). Lists the
 * account's workspace profiles (organizations, spaces, groups — any
 * non-person profile), with the single personal profile in its own card.
 *
 * Launching a workspace (?gf_switch=<profile_id>) switches the account's acting
 * profile context to it — the member "uses the site as" that workspace, UNA's
 * native profile switch — then lands on the workspace's page. Both the OWNER of
 * a workspace and its delegated ADMINS (e.g. a social media manager) may act as
 * it; a plain member only visits the page (see gfWsSwitchContext). Loading this
 * picker itself (the site root) resets the context back to the member's personal
 * profile, so returning to gfunnel.com/ exits any workspace.
 *
 * Optional settings (sys_options):
 *  - gf_root_workspaces        'off' disables the whole page (root falls back to 'home')
 *  - gf_workspaces_create_url  create-workspace target, default page.php?i=create-organization
 *  - gf_workspace_modules      comma-separated group modules whose memberships are
 *                              listed as joined workspaces (default: organizations,
 *                              spaces, groups)
 *  - gf_workspaces_ref_url     referral link template ({id}/{display_name}); Earn card hidden when empty
 *
 * Invite codes require the gf_workspace_invites table
 * (docs/sql/gf_workspace_invites.sql); all invite UI hides without it.
 *  - gf_workspaces_support_url support link; support line hidden when empty
 */

require_once('./inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_INC . "design.inc.php");

bx_import('BxDolLanguages');

// The picker keeps the plain 48px header (logo, search, timer, What's New and
// the member icons) - no hub-tabs subheader on this page.
BxTemplFunctions::$bGfToolbarSubheader = false;

/*
 * ---------------------------------------------------------------------------
 * Workspace invite codes (phase 2). The gf_workspace_invites table is created
 * automatically on first use (same self-provisioning pattern as the timer and
 * bug modules), so no manual SQL is required; docs/sql/gf_workspace_invites.sql
 * is kept for reference. 8-char ambiguity-free codes, pending/expiry/max-uses
 * validation, permanent codes never flip to accepted.
 * ---------------------------------------------------------------------------
 */

function gfWsInvitesEnabled()
{
    static $bEnabled = null;
    if($bEnabled !== null)
        return $bEnabled;

    $oDb = BxDolDb::getInstance();
    if(!$oDb->getOne("SHOW TABLES LIKE 'gf_workspace_invites'"))
        $oDb->query("CREATE TABLE IF NOT EXISTS `gf_workspace_invites` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `workspace_id` int unsigned NOT NULL,
            `code` varchar(8) NOT NULL,
            `email` varchar(255) NOT NULL DEFAULT '',
            `role` varchar(32) NOT NULL DEFAULT 'member',
            `type` varchar(16) NOT NULL DEFAULT 'permanent',
            `status` varchar(16) NOT NULL DEFAULT 'pending',
            `max_uses` int unsigned NOT NULL DEFAULT 0,
            `uses` int unsigned NOT NULL DEFAULT 0,
            `expires_at` int unsigned NOT NULL DEFAULT 0,
            `created_by` int unsigned NOT NULL DEFAULT 0,
            `created_at` int unsigned NOT NULL DEFAULT 0,
            `accepted_by` int unsigned NOT NULL DEFAULT 0,
            `accepted_at` int unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `code` (`code`),
            KEY `workspace_status` (`workspace_id`, `status`),
            KEY `email_status` (`email`(64), `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $bEnabled = (bool)$oDb->getOne("SHOW TABLES LIKE 'gf_workspace_invites'");
    return $bEnabled;
}

function gfWsGenerateInviteCode()
{
    $sAlphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    $sCode = '';
    for($i = 0; $i < 8; $i++)
        $sCode .= $sAlphabet[random_int(0, strlen($sAlphabet) - 1)];

    return $sCode;
}

function gfWsOwnedWorkspaceIds($oAccount)
{
    $aIds = [];
    foreach($oAccount->getProfiles() as $iProfileId => $aProfileInfo)
        if(!empty($aProfileInfo['type']) && !in_array($aProfileInfo['type'], ['system', 'bx_persons']))
            $aIds[] = (int)$iProfileId;

    return $aIds;
}

/**
 * The account's personal (person) profile id - the "home" workspace the member
 * always falls back to. 0 if the account has no person profile.
 */
function gfWsPersonalProfileId($oAccount)
{
    if(!$oAccount)
        return 0;

    foreach($oAccount->getProfiles() as $iProfileId => $aProfileInfo)
        if(!empty($aProfileInfo['type']) && $aProfileInfo['type'] == 'bx_persons')
            return (int)$iProfileId;

    return 0;
}

/**
 * Switch the account's acting profile context to $oProfile - i.e. actually
 * "use the site as" that workspace, the same operation as UNA's native profile
 * switcher (page.php?i=account-profile-switcher).
 *
 * Who may act as a workspace:
 *  - the OWNER: any profile the account created (personal person profile +
 *    organizations it owns) - the account_id matches.
 *  - a delegated ADMIN: a member who is an admin of the workspace (e.g. a
 *    social media manager of GFunnel) may act as it too, even though it belongs
 *    to another account. Membership/admin status is checked against the member's
 *    personal profile via the module's native `is_admin` service.
 *
 * A plain member (non-admin) or a non-member is NOT permitted - for them this is
 * a no-op and the workspace is only visited, never acted as. Note that only
 * modules whose `act_as_profile` is true can ever be acted as (organizations and
 * persons; spaces/groups return false), so this is inherently org-scoped.
 *
 * @return bool true when the context is (now) $oProfile, false when the switch
 *              is not permitted.
 */
function gfWsSwitchContext($oProfile, $oAccount)
{
    if(!$oProfile || !$oAccount)
        return false;

    // the target module must support being acted as (organizations + persons)
    if(!BxDolService::call($oProfile->getModule(), 'act_as_profile'))
        return false;

    $bOwned = ((int)$oProfile->getAccountId() === (int)$oAccount->id());

    // Delegated acting-as: an admin of the workspace (social media manager, etc.)
    // may act as it even though the workspace is owned by another account. The
    // admin relationship is on the member's PERSONAL profile, not whatever they
    // are currently acting as, so resolve that explicitly. Regular members and
    // non-members fall through and are only allowed to visit.
    $bAdmin = false;
    if(!$bOwned && ($iPersonalId = gfWsPersonalProfileId($oAccount)) > 0)
        $bAdmin = (bool)BxDolService::call($oProfile->getModule(), 'is_admin', [$oProfile->id(), $iPersonalId]);

    if(!$bOwned && !$bAdmin)
        return false;

    // already the active context - nothing to do
    if((int)$oProfile->id() === (int)bx_get_logged_profile_id())
        return true;

    // updateProfileContext clears the cached "current profile" for the account,
    // so bx_get_logged_profile_id() reflects the new context later in this same
    // request (fires the before_switch_context / switch_context hooks too).
    return (bool)$oAccount->updateProfileContext($oProfile->id());
}

/**
 * The 'fans' connection object of a group-profile module (organizations,
 * spaces, groups, ...) - membership lives in these connections.
 */
function gfWsFansObject($sModule)
{
    $oModule = BxDolModule::getInstance($sModule);
    if($oModule && isset($oModule->_oConfig->CNF['OBJECT_CONNECTIONS']))
        return $oModule->_oConfig->CNF['OBJECT_CONNECTIONS'];

    return '';
}

function gfWsIsMember($oWsProfile, $iProfileId)
{
    $sConnObj = gfWsFansObject($oWsProfile->getModule());
    if(!$sConnObj || !($oConnection = BxDolConnection::getObjectInstance($sConnObj)))
        return false;

    return $oConnection->isConnected($iProfileId, $oWsProfile->id(), true) || $oConnection->isConnected($oWsProfile->id(), $iProfileId, true);
}

function gfWsJoinWorkspace($oWsProfile, $iProfileId, $sRole = 'member')
{
    $sModule = $oWsProfile->getModule();
    $sConnObj = gfWsFansObject($sModule);
    if(!$sConnObj || !($oConnection = BxDolConnection::getObjectInstance($sConnObj)))
        return false;

    // an invite is a pre-approval: establish both directions of the mutual
    // 'fans' connection, then let the module fire its fan-added side effects
    $oConnection->addConnection($iProfileId, $oWsProfile->id());
    $oConnection->addConnection($oWsProfile->id(), $iProfileId);

    if(BxDolRequest::serviceExists($sModule, 'add_mutual_connection'))
        BxDolService::call($sModule, 'add_mutual_connection', [$oWsProfile->id(), $iProfileId]);

    // admin invites: best effort via the module's admins connection
    if($sRole == 'admin' && ($oAdmins = BxDolConnection::getObjectInstance(str_replace('_fans', '_admins', $sConnObj))))
        $oAdmins->addConnection($oWsProfile->id(), $iProfileId);

    return true;
}

/**
 * Redeem an invite row for the logged-in member.
 * @return string redirect URL on success, '' on failure ($sNotice explains)
 */
function gfWsExecuteInvite($aInvite, $oAccount, $oProfile, &$sNotice)
{
    if((int)$aInvite['expires_at'] > 0 && time() > (int)$aInvite['expires_at']) {
        $sNotice = 'This invite has expired.';
        return '';
    }

    if((int)$aInvite['max_uses'] > 0 && (int)$aInvite['uses'] >= (int)$aInvite['max_uses']) {
        $sNotice = 'This invite has reached its usage limit.';
        return '';
    }

    $oWsProfile = BxDolProfile::getInstance((int)$aInvite['workspace_id']);
    if(!$oWsProfile) {
        $sNotice = 'This workspace no longer exists.';
        return '';
    }

    if(in_array($oWsProfile->id(), gfWsOwnedWorkspaceIds($oAccount))) {
        $sNotice = 'You own ' . $oWsProfile->getDisplayName() . ' already.';
        return '';
    }

    if(gfWsIsMember($oWsProfile, $oProfile->id())) {
        $sNotice = 'You are already a member of ' . $oWsProfile->getDisplayName() . '.';
        return '';
    }

    if(!gfWsJoinWorkspace($oWsProfile, $oProfile->id(), $aInvite['role'])) {
        $sNotice = 'Joining failed — this workspace type does not support invites.';
        return '';
    }

    // Credit the inviter's affiliate account for this member. New signups already
    // have referredby set from the am_id on the invite link (captured at register
    // time); this also covers existing members joining via a code, and is a no-op
    // when the referral already points at the inviter.
    $iInviter = (int)$aInvite['created_by'];
    if($iInviter > 0 && $iInviter != $oProfile->id() && BxDolRequest::serviceExists('aqb_affiliate', 'set_referral'))
        BxDolService::call('aqb_affiliate', 'set_referral', [$oProfile->id(), $iInviter]);

    // bookkeeping: permanent codes stay pending forever, everything else flips
    // to accepted once its uses are spent (email invites default to single-use)
    $oDb = BxDolDb::getInstance();
    $iUses = (int)$aInvite['uses'] + 1;

    $sExtra = '';
    if($aInvite['type'] != 'permanent' && $iUses >= max(1, (int)$aInvite['max_uses']))
        $sExtra = ", `status` = 'accepted', `accepted_by` = " . (int)$oProfile->id() . ", `accepted_at` = " . time();

    $oDb->query("UPDATE `gf_workspace_invites` SET `uses` = " . $iUses . $sExtra . " WHERE `id` = :id", ['id' => (int)$aInvite['id']]);

    return bx_append_url_params($oWsProfile->getUrl(), ['gf_ws' => $oWsProfile->id()]);
}

function gfWsHandleJoinByCode($sCode, $oAccount, $oProfile, &$sNotice)
{
    $sCode = strtoupper(trim((string)$sCode));
    if(!preg_match('/^[A-Z0-9]{8}$/', $sCode)) {
        $sNotice = "That invite code doesn't look right — codes are 8 letters and numbers.";
        return '';
    }

    $aInvite = BxDolDb::getInstance()->getRow("SELECT * FROM `gf_workspace_invites` WHERE `code` = :code AND `status` = 'pending' LIMIT 1", ['code' => $sCode]);
    if(!$aInvite) {
        $sNotice = 'This invite code is not valid.';
        return '';
    }

    return gfWsExecuteInvite($aInvite, $oAccount, $oProfile, $sNotice);
}

/**
 * Get-or-create the permanent invite code of an owned workspace.
 */
function gfWsPermanentInvite($iWorkspaceId, $iProfileId, $bReset = false)
{
    $oDb = BxDolDb::getInstance();

    if($bReset)
        $oDb->query("UPDATE `gf_workspace_invites` SET `status` = 'revoked' WHERE `workspace_id` = :ws AND `type` = 'permanent' AND `status` = 'pending'", ['ws' => $iWorkspaceId]);

    $aRow = $oDb->getRow("SELECT * FROM `gf_workspace_invites` WHERE `workspace_id` = :ws AND `type` = 'permanent' AND `status` = 'pending' LIMIT 1", ['ws' => $iWorkspaceId]);
    if($aRow)
        return $aRow;

    for($i = 0; $i < 5; $i++) {
        $sCode = gfWsGenerateInviteCode();
        if($oDb->getOne("SELECT `id` FROM `gf_workspace_invites` WHERE `code` = :code", ['code' => $sCode]))
            continue;

        $oDb->query(
            "INSERT INTO `gf_workspace_invites` SET `workspace_id` = :ws, `code` = :code, `role` = 'member', `type` = 'permanent', `status` = 'pending', `created_by` = :by, `created_at` = :now",
            ['ws' => $iWorkspaceId, 'code' => $sCode, 'by' => $iProfileId, 'now' => time()]
        );
        break;
    }

    return $oDb->getRow("SELECT * FROM `gf_workspace_invites` WHERE `workspace_id` = :ws AND `type` = 'permanent' AND `status` = 'pending' LIMIT 1", ['ws' => $iWorkspaceId]);
}

/**
 * Affiliate URL params ([am_id => hash]) for the given inviter profile, so the
 * shared invite/join link carries the inviter's affiliate attribution. Returns
 * an empty array when the Affiliate System is absent (link stays code-only).
 */
function gfWsAffiliateParams($iProfileId)
{
    $iProfileId = (int)$iProfileId;
    if($iProfileId <= 0 || !BxDolRequest::serviceExists('aqb_affiliate', 'get_referral_code'))
        return [];

    $mixedParams = BxDolService::call('aqb_affiliate', 'get_referral_code', [$iProfileId, false]);
    return is_array($mixedParams) ? $mixedParams : [];
}

function gfWsOpenInvites($sEmail)
{
    if(empty($sEmail))
        return [];

    $aRows = BxDolDb::getInstance()->getAll(
        "SELECT * FROM `gf_workspace_invites` WHERE `email` = :email AND `status` = 'pending' AND (`expires_at` = 0 OR `expires_at` > :now) ORDER BY `created_at` DESC",
        ['email' => strtolower(trim($sEmail)), 'now' => time()]
    );

    return is_array($aRows) ? $aRows : [];
}

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
            // Launching a workspace switches the acting profile context to it
            // (gfWsSwitchContext, handled by the gf_switch redirect below) and
            // then lands on its page with gf_ws set, which pins the member's
            // per-workspace menu (see BxBaseFunctions::getGfActiveWorkspaceId).
            'url' => BX_DOL_URL_ROOT . 'workspaces.php?gf_switch=' . (int)$iProfileId,
            'title' => bx_process_output($oWsProfile->getDisplayName()),
            'thumb' => $oWsProfile->getThumb()
        ];

        if($sType == 'bx_persons') {
            if(empty($aTmplVarsPersonal) || $iProfileId == $oProfile->id())
                $aTmplVarsPersonal = $aUnit;
            continue;
        }

        $sMeta = bx_process_output($aModuleTitles[$sType]) . ' &#183; owner';
        if($aProfileInfo['status'] != 'active')
            $sMeta .= ' &#183; pending';

        $aTmplVarsWorkspaces[] = array_merge($aUnit, [
            'meta' => $sMeta,
            'manage_id' => $iProfileId // owners can manage this workspace's invite code
        ]);
    }

    // The personal workspace is NOT listed here - it lives in its own side card.

    //--- Joined workspaces: membership lives in each group module's 'fans' connections
    $aOwnedIds = [];
    foreach($aTmplVarsWorkspaces as $aWs)
        $aOwnedIds[] = (int)$aWs['manage_id'];

    $sWorkspaceModules = trim((string)getParam('gf_workspace_modules'));
    if(empty($sWorkspaceModules))
        $sWorkspaceModules = 'bx_organizations,bx_spaces,bx_groups';

    foreach(explode(',', $sWorkspaceModules) as $sWsModule) {
        $sWsModule = trim($sWsModule);
        $sConnObj = $sWsModule !== '' ? gfWsFansObject($sWsModule) : '';
        if(!$sConnObj || !($oConnection = BxDolConnection::getObjectInstance($sConnObj)))
            continue;

        $oAdmins = BxDolConnection::getObjectInstance(str_replace('_fans', '_admins', $sConnObj));

        $aJoinedIds = $oConnection->getConnectedContent($oProfile->id());
        if(!is_array($aJoinedIds))
            continue;

        foreach($aJoinedIds as $iJoinedId) {
            $iJoinedId = (int)$iJoinedId;
            if(in_array($iJoinedId, $aOwnedIds) || $iJoinedId == $oProfile->id())
                continue;

            $oJoined = BxDolProfile::getInstance($iJoinedId);
            if(!$oJoined || $oJoined->getModule() != $sWsModule)
                continue;

            if(!isset($aModuleTitles[$sWsModule])) {
                $aModule = $oModuleQuery->getModuleByName($sWsModule);
                $aModuleTitles[$sWsModule] = !empty($aModule['title']) ? $aModule['title'] : ucfirst(str_replace(['bx_', '_'], ['', ' '], $sWsModule));
            }

            $bAdmin = $oAdmins && ($oAdmins->isConnected($iJoinedId, $oProfile->id()) || $oAdmins->isConnected($oProfile->id(), $iJoinedId));

            $aTmplVarsWorkspaces[] = [
                // joined workspaces belong to another account, so gfWsSwitchContext
                // is a no-op for them - the gf_switch handler just opens the page
                // with gf_ws (menu pin), which is the correct "visit as member".
                'url' => BX_DOL_URL_ROOT . 'workspaces.php?gf_switch=' . (int)$iJoinedId,
                'title' => bx_process_output($oJoined->getDisplayName()),
                'thumb' => $oJoined->getThumb(),
                'meta' => bx_process_output($aModuleTitles[$sWsModule]) . ' &#183; ' . ($bAdmin ? 'admin' : 'member'),
                'manage_id' => 0
            ];
        }
    }

    $bInvites = gfWsInvitesEnabled();

    // Pre-render the rows: nested bx_repeat inside bx_if isn't supported by the
    // compiled-template engine, so each row is parsed separately and passed as HTML.
    $sWorkspacesList = '';
    foreach($aTmplVarsWorkspaces as $aTmplVarsWorkspace) {
        $iManageId = (int)$aTmplVarsWorkspace['manage_id'];
        unset($aTmplVarsWorkspace['manage_id']);

        $sWorkspacesList .= $oTemplate->parseHtmlByName('page_workspaces_item.html', array_merge($aTmplVarsWorkspace, [
            'bx_if:manage' => [
                'condition' => $bInvites && $iManageId > 0,
                'content' => ['manage_url' => BX_DOL_URL_ROOT . 'workspaces.php?invite_ws=' . $iManageId]
            ]
        ]));
    }

    //--- Open invites addressed to the account's email
    $aOpenInvites = $bInvites ? gfWsOpenInvites((string)$oAccount->getEmail()) : [];
    $sInvitesList = '';
    $iOpenInvites = 0;
    foreach($aOpenInvites as $aOpenInvite) {
        $oInviteWs = BxDolProfile::getInstance((int)$aOpenInvite['workspace_id']);
        if(!$oInviteWs)
            continue;

        $iOpenInvites++;
        $sInvitesList .= $oTemplate->parseHtmlByName('page_workspaces_invite_item.html', [
            'title' => bx_process_output($oInviteWs->getDisplayName()),
            'thumb' => $oInviteWs->getThumb(),
            'meta' => 'Invited as ' . bx_process_output($aOpenInvite['role']),
            'accept_url' => BX_DOL_URL_ROOT . 'workspaces.php?accept_invite=' . (int)$aOpenInvite['id'],
            'decline_url' => BX_DOL_URL_ROOT . 'workspaces.php?decline_invite=' . (int)$aOpenInvite['id']
        ]);
    }

    //--- Optional pieces
    $oPermalink = BxDolPermalinks::getInstance();

    $sCreateUrl = trim((string)getParam('gf_workspaces_create_url'));
    if(empty($sCreateUrl))
        $sCreateUrl = 'page.php?i=create-organization';
    if(!preg_match('/^https?:\/\//i', $sCreateUrl))
        $sCreateUrl = BX_DOL_URL_ROOT . $oPermalink->permalink($sCreateUrl);

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
            'condition' => $bInvites,
            'content' => ['invite_url' => BX_DOL_URL_ROOT]
        ],
        'bx_if:notice' => [
            'condition' => !empty($GLOBALS['gfWsNotice']),
            'content' => ['notice' => bx_process_output($GLOBALS['gfWsNotice'])]
        ],
        'bx_if:invite_card' => [
            'condition' => !empty($GLOBALS['gfWsInviteCard']),
            'content' => !empty($GLOBALS['gfWsInviteCard']) ? $GLOBALS['gfWsInviteCard'] : ['ws_title' => '', 'code' => '', 'join_url' => '', 'reset_url' => '', 'close_url' => '']
        ],
        // The invites tab label and the invites pane are two separate template
        // blocks; they MUST use distinct bx_if names. The template engine matches
        // each bx_if with a greedy /<bx_if:NAME>(.*)<\/bx_if:NAME>/s regex, so
        // reusing one name across two blocks makes the match span everything in
        // between (the whole workspaces list), which then re-parses with the wrong
        // variable set and leaks __workspaces_list__ / raw bx_if tags to the page.
        'bx_if:invites_tab' => [
            'condition' => $bInvites,
            'content' => [
                'invites_count_badge' => $iOpenInvites > 0 ? '<span class="gf-ws-tab-badge">' . $iOpenInvites . '</span>' : ''
            ]
        ],
        'bx_if:invites_pane' => [
            'condition' => $bInvites,
            'content' => [
                'invites_list' => $sInvitesList !== '' ? $sInvitesList : '<div class="gf-ws-invites-empty">No open invites right now.</div>'
            ]
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

// Onboarding gate: new members funnel through the workspace picker, so route
// anyone who hasn't finished profile setup to /welcome here - reliable no matter
// what the post-activation redirect does. Only NEW profiles are gated (recent
// created-time), so existing members are never pulled into onboarding.
if(getParam('gf_auth_pages') != 'off' && getParam('gf_onboarding_gate') != 'off') {
    $oGfOnbGate = BxDolModule::getInstance('gfunnel_onb');
    if($oGfOnbGate && method_exists($oGfOnbGate, 'isOnboardingComplete') && !$oGfOnbGate->isOnboardingComplete(bx_get_logged_profile_id())) {
        // "new" = profile added within the window (default 30 days); pre-existing
        // members fall outside it and are treated as already onboarded.
        $iGfWindow = (int)getParam('gf_onboarding_new_days');
        if($iGfWindow <= 0)
            $iGfWindow = 30;

        // Account creation time (sys_accounts.added). If unreadable, $iGfAdded
        // stays 0 and the gate never fires - fail-safe, no disruption.
        $aGfAcc = BxDolAccount::getInstance()->getInfo();
        $iGfAdded = isset($aGfAcc['added']) ? (int)$aGfAcc['added'] : 0;

        if($iGfAdded > 0 && $iGfAdded >= (time() - $iGfWindow * 86400)) {
            header('Location: ' . BX_DOL_URL_ROOT . 'gf_onboarding.php');
            exit;
        }
    }
}

//--- Invite actions (join by code, accept/decline, manage a workspace's code)
$GLOBALS['gfWsNotice'] = '';
$GLOBALS['gfWsInviteCard'] = [];

$oGfAccount = BxDolAccount::getInstance();
$oGfProfile = BxDolProfile::getInstance(bx_get_logged_profile_id());

//--- Launch a workspace: switch the acting profile context (for owned
//--- workspaces) and open the workspace's page with gf_ws set. Joined
//--- workspaces can't be acted as, so this just opens their page.
if(($iGfSwitch = (int)bx_get('gf_switch')) > 0 && $oGfAccount) {
    $oGfSwitchProfile = BxDolProfile::getInstance($iGfSwitch);
    if($oGfSwitchProfile) {
        gfWsSwitchContext($oGfSwitchProfile, $oGfAccount);
        header('Location: ' . bx_append_url_params($oGfSwitchProfile->getUrl(), ['gf_ws' => $oGfSwitchProfile->id()]));
        exit;
    }
    // invalid target id: fall through and render the picker
}

//--- Coming home: any plain load of the picker (the site root for logged-in
//--- members) resets the acting profile context back to the member's personal
//--- profile. This is what "go to gfunnel.com/ to get out of a workspace" does
//--- - a member who was acting as a workspace is dropped back to themselves,
//--- while the picker still renders here as the front door. (Workspace launches
//--- redirect away above, so they never reach this reset.) Everything below -
//--- invite actions and the render - then runs as the personal profile.
if($oGfAccount) {
    $iGfPersonalId = gfWsPersonalProfileId($oGfAccount);
    if($iGfPersonalId > 0) {
        if(($oGfPersonal = BxDolProfile::getInstance($iGfPersonalId)))
            gfWsSwitchContext($oGfPersonal, $oGfAccount);

        // keep the per-workspace menu context aligned with the personal profile
        BxDolSession::getInstance()->setValue('gf_active_workspace', $iGfPersonalId);

        // re-resolve to the (now personal) context for the actions/render below
        $oGfProfile = BxDolProfile::getInstance(bx_get_logged_profile_id());
    }
}

if(gfWsInvitesEnabled() && $oGfAccount && $oGfProfile) {
    $sGfEmail = strtolower(trim((string)$oGfAccount->getEmail()));

    if(($sCode = bx_get('code')) !== false && trim($sCode) !== '') {
        $sRedirect = gfWsHandleJoinByCode($sCode, $oGfAccount, $oGfProfile, $GLOBALS['gfWsNotice']);
        if($sRedirect !== '') {
            header('Location: ' . $sRedirect);
            exit;
        }
    }
    // A member who registered via an invite link: the code was stashed in a cookie
    // before login (index.php), because root drops query params through the
    // register/login/onboarding flow. Redeem it now that they've landed here. The
    // cookie is cleared either way so a failed/expired code is not retried forever.
    else if(!empty($_COOKIE['gf_ws_invite'])) {
        $sCookieCode = (string)$_COOKIE['gf_ws_invite'];

        $aGfUrl = parse_url(BX_DOL_URL_ROOT);
        setcookie('gf_ws_invite', '', time() - 86400, !empty($aGfUrl['path']) ? $aGfUrl['path'] : '/', '', false, true /* http only */);
        unset($_COOKIE['gf_ws_invite']);

        $sRedirect = gfWsHandleJoinByCode($sCookieCode, $oGfAccount, $oGfProfile, $GLOBALS['gfWsNotice']);
        if($sRedirect !== '') {
            header('Location: ' . $sRedirect);
            exit;
        }
    }

    if(($iAccept = (int)bx_get('accept_invite')) > 0) {
        $aInvite = BxDolDb::getInstance()->getRow("SELECT * FROM `gf_workspace_invites` WHERE `id` = :id AND `email` = :email AND `status` = 'pending' LIMIT 1", ['id' => $iAccept, 'email' => $sGfEmail]);
        if($aInvite) {
            $sRedirect = gfWsExecuteInvite($aInvite, $oGfAccount, $oGfProfile, $GLOBALS['gfWsNotice']);
            if($sRedirect !== '') {
                header('Location: ' . $sRedirect);
                exit;
            }
        }
        else
            $GLOBALS['gfWsNotice'] = 'This invite is no longer available.';
    }

    if(($iDecline = (int)bx_get('decline_invite')) > 0) {
        BxDolDb::getInstance()->query("UPDATE `gf_workspace_invites` SET `status` = 'declined' WHERE `id` = :id AND `email` = :email AND `status` = 'pending'", ['id' => $iDecline, 'email' => $sGfEmail]);
        $GLOBALS['gfWsNotice'] = 'Invite declined.';
    }

    if(($iInviteWs = (int)bx_get('invite_ws')) > 0 && in_array($iInviteWs, gfWsOwnedWorkspaceIds($oGfAccount))) {
        $aInviteRow = gfWsPermanentInvite($iInviteWs, $oGfProfile->id(), bx_get('invite_reset') == '1');
        $oInviteWsProfile = BxDolProfile::getInstance($iInviteWs);

        if($aInviteRow && $oInviteWsProfile)
            $GLOBALS['gfWsInviteCard'] = [
                'ws_title' => bx_process_output($oInviteWsProfile->getDisplayName()),
                'code' => $aInviteRow['code'],
                // the join link carries the inviter's affiliate am_id so a new
                // member who registers through it credits the inviter
                'join_url' => bx_append_url_params(BX_DOL_URL_ROOT . '?code=' . $aInviteRow['code'], gfWsAffiliateParams((int)$aInviteRow['created_by'] ?: $oGfProfile->id())),
                'reset_url' => BX_DOL_URL_ROOT . 'workspaces.php?invite_ws=' . $iInviteWs . '&invite_reset=1',
                'close_url' => BX_DOL_URL_ROOT
            ];
    }
}

$oTemplate = BxDolTemplate::getInstance();
$oTemplate->setPageNameIndex(BX_PAGE_DEFAULT);
$oTemplate->setPageHeader('My workspaces');
$oTemplate->setPageContent('page_main_code', getGfWorkspacesPageCode());
$oTemplate->getPageCode();

/** @} */
