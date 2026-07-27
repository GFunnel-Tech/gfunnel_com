<?php
/**
 * GFunnel — workspace administration helpers (member roles, ownership transfer
 * and claim). Included by workspaces.php; the logic lives here to keep the
 * picker page lean, the same split the homepage uses (inc/gf_home_blocks.inc.php).
 *
 * A "workspace" is a group-profile (organization / space / group). Roles and
 * ownership are UNA-native:
 *  - member roles live in the module's admins overlay table (CNF TABLE_ADMINS),
 *    written via the module's setRole()/unsetRole(); role definitions are the
 *    module's site-wide data list (CNF OBJECT_PRE_LIST_ROLES) — the templated +
 *    custom roles. Built-in roles: 1 = Administrator, 2 = Moderator, 0 = Member.
 *  - ownership is sys_profiles.account_id (the edit/delete gate), kept in sync
 *    with the content author (CNF FIELD_AUTHOR, a person-profile id).
 *
 * Everything here is type-aware: persons and any non-group profile have no
 * roles/admins, so gfWsGroupModule() returns null and the callers no-op.
 */

if(!defined('BX_DOL'))
    exit;

// Built-in group roles (mirror BxBaseModGroupsModule constants; redeclared as
// plain ints so this file does not depend on the module class being loaded).
if(!defined('GF_WS_ROLE_MEMBER'))        define('GF_WS_ROLE_MEMBER', 0);
if(!defined('GF_WS_ROLE_ADMINISTRATOR')) define('GF_WS_ROLE_ADMINISTRATOR', 1);
if(!defined('GF_WS_ROLE_MODERATOR'))     define('GF_WS_ROLE_MODERATOR', 2);

/**
 * The concrete group-profile module for a workspace, but only when it is a
 * manageable group type — one that carries an admins/roles overlay. Returns the
 * module instance, or null for persons and anything else (callers then no-op).
 */
function gfWsGroupModule($oWsProfile)
{
    if(!$oWsProfile)
        return null;

    $oModule = BxDolModule::getInstance($oWsProfile->getModule());
    if(!$oModule || !isset($oModule->_oConfig) || !method_exists($oModule, 'setRole'))
        return null;

    $CNF = $oModule->_oConfig->CNF;
    if(empty($CNF['TABLE_ADMINS']) || empty($CNF['OBJECT_CONNECTIONS']))
        return null;

    return $oModule;
}

/**
 * May $iPersonId manage members/roles of $oWsProfile? True for the owner (the
 * workspace belongs to the viewer's account) or a delegated admin of it.
 */
function gfWsCanManage($oWsProfile, $oAccount, $iPersonId)
{
    if(!gfWsGroupModule($oWsProfile) || !$oAccount)
        return false;

    // owner: the workspace profile belongs to the viewer's account
    if((int)$oWsProfile->getAccountId() === (int)$oAccount->id())
        return true;

    // delegated admin (social media manager, etc.)
    return (bool)BxDolService::call($oWsProfile->getModule(), 'is_admin', [$oWsProfile->id(), (int)$iPersonId]);
}

/**
 * Assignable roles for a workspace's module type as [role_id => title]. Uses the
 * module's site-wide roles data list when it is populated (the templated + any
 * custom roles a site admin defined); otherwise falls back to the two built-ins.
 * The "Member" role (0, the demote target) is added by the caller.
 */
function gfWsAvailableRoles($oModule)
{
    $CNF = $oModule->_oConfig->CNF;

    $aRoles = [];
    if(!empty($CNF['OBJECT_PRE_LIST_ROLES']))
        $aRoles = BxDolFormQuery::getDataItems($CNF['OBJECT_PRE_LIST_ROLES']);

    if(empty($aRoles) || !is_array($aRoles))
        $aRoles = [
            GF_WS_ROLE_ADMINISTRATOR => 'Administrator',
            GF_WS_ROLE_MODERATOR => 'Moderator'
        ];

    // never offer the common "Member" value as an elevated role
    unset($aRoles[GF_WS_ROLE_MEMBER]);

    return $aRoles;
}

/**
 * The owner (person profile) of a workspace — the account's profile the
 * workspace belongs to. This is the authoritative owner (sys_profiles.account_id),
 * shown as a non-editable row at the top of the members list.
 */
function gfWsOwnerProfileId($oWsProfile)
{
    $oOwner = BxDolProfile::getInstanceAccountProfile((int)$oWsProfile->getAccountId());
    return $oOwner ? (int)$oOwner->id() : 0;
}

/**
 * Member rows for a workspace's manage panel: the owner first (locked), then
 * every fan, each with their current role. Shape per row:
 *   ['id','name','thumb','url','role','role_title','is_owner']
 */
function gfWsWorkspaceMembers($oWsProfile, $oModule, $aRoles)
{
    $aOut = [];
    $aSeen = [];

    $iOwnerPid = gfWsOwnerProfileId($oWsProfile);
    if($iOwnerPid > 0 && ($oOwner = BxDolProfile::getInstance($iOwnerPid))) {
        $aOut[] = [
            'id' => $iOwnerPid,
            'name' => $oOwner->getDisplayName(),
            'thumb' => $oOwner->getThumb(),
            'url' => $oOwner->getUrl(),
            'role' => GF_WS_ROLE_ADMINISTRATOR,
            'role_title' => 'Owner',
            'is_owner' => true
        ];
        $aSeen[$iOwnerPid] = true;
    }

    $oConnection = BxDolConnection::getObjectInstance($oModule->_oConfig->CNF['OBJECT_CONNECTIONS']);
    if($oConnection) {
        // members are the profiles that connected TO this workspace (fans);
        // mutual=false so single-direction memberships are included too.
        $aMemberIds = $oConnection->getConnectedInitiators($oWsProfile->id(), false, 0, 1000);
        if(is_array($aMemberIds))
            foreach($aMemberIds as $iMemberId) {
                $iMemberId = (int)$iMemberId;
                if(isset($aSeen[$iMemberId]))
                    continue;

                $oMember = BxDolProfile::getInstance($iMemberId);
                if(!$oMember || $oMember->getModule() != 'bx_persons')
                    continue;

                $iRole = (int)$oModule->getRole($oWsProfile->id(), $iMemberId);
                $sRoleTitle = ($iRole > 0 && isset($aRoles[$iRole])) ? $aRoles[$iRole] : 'Member';

                $aOut[] = [
                    'id' => $iMemberId,
                    'name' => $oMember->getDisplayName(),
                    'thumb' => $oMember->getThumb(),
                    'url' => $oMember->getUrl(),
                    'role' => $iRole,
                    'role_title' => $sRoleTitle,
                    'is_owner' => false
                ];
                $aSeen[$iMemberId] = true;
            }
    }

    return $aOut;
}

/**
 * Assign (or clear, role 0) a member's role in a workspace. Guards: the target
 * must be an existing member (not the owner) and the role must be one the module
 * offers (or 0 to demote to plain Member).
 *
 * @return string '' on success, otherwise a human-readable error notice.
 */
function gfWsSetMemberRole($oWsProfile, $oModule, $aRoles, $iMemberPid, $iRole)
{
    $iMemberPid = (int)$iMemberPid;
    $iRole = (int)$iRole;

    if($iMemberPid <= 0)
        return 'That member could not be found.';

    if($iMemberPid === gfWsOwnerProfileId($oWsProfile))
        return "The owner's role can't be changed here — use Transfer ownership instead.";

    if($iRole !== GF_WS_ROLE_MEMBER && !isset($aRoles[$iRole]))
        return 'That role is not available for this workspace.';

    // only assign to someone who is actually a member of the workspace
    if(!(bool)BxDolService::call($oWsProfile->getModule(), 'is_fan', [$oWsProfile->id(), $iMemberPid]))
        return 'That person is not a member of this workspace.';

    if($iRole === GF_WS_ROLE_MEMBER)
        return $oModule->unsetRole($oWsProfile->id(), $iMemberPid) ? '' : 'Could not update that role.';

    return $oModule->setRole($oWsProfile->id(), $iMemberPid, $iRole) ? '' : 'Could not update that role.';
}

/**
 * Build the <option> list for a role <select>, with the member's current role
 * pre-selected. Always offers "Member" (0, the demote target) plus every
 * assignable role.
 */
function gfWsRoleOptions($aRoles, $iCurrentRole)
{
    $sOut = '<option value="' . GF_WS_ROLE_MEMBER . '"' . ((int)$iCurrentRole === GF_WS_ROLE_MEMBER ? ' selected="selected"' : '') . '>Member</option>';
    foreach($aRoles as $iRoleId => $sRoleTitle)
        $sOut .= '<option value="' . (int)$iRoleId . '"' . ((int)$iCurrentRole === (int)$iRoleId ? ' selected="selected"' : '') . '>' . bx_process_output($sRoleTitle) . '</option>';

    return $sOut;
}

/**
 * Render the management card for a workspace: the members list with per-member
 * role selectors, plus the invite-code section for owners. Returns ready HTML
 * (the picker injects it via bx_if:manage_card). $aInviteRow is the permanent
 * invite row (code) or null to hide the invite section.
 */
function gfWsBuildManageCard($oWsProfile, $oModule, $sNotice, $aInviteRow, $sJoinUrl)
{
    $oTemplate = BxDolTemplate::getInstance();

    $aRoles = gfWsAvailableRoles($oModule);
    $aMembers = gfWsWorkspaceMembers($oWsProfile, $oModule, $aRoles);

    $sManageUrl = BX_DOL_URL_ROOT . 'workspaces.php';

    $sMembersList = '';
    foreach($aMembers as $aMember) {
        $bEditable = empty($aMember['is_owner']);
        $sMembersList .= $oTemplate->parseHtmlByName('page_workspaces_member_item.html', [
            'thumb' => $aMember['thumb'],
            'url' => $aMember['url'],
            'name' => bx_process_output($aMember['name']),
            'role_title' => bx_process_output($aMember['role_title']),
            'bx_if:owner' => [
                'condition' => !empty($aMember['is_owner']),
                'content' => ['name' => bx_process_output($aMember['name'])]
            ],
            'bx_if:editable' => [
                'condition' => $bEditable,
                'content' => [
                    'action' => $sManageUrl,
                    'ws_id' => (int)$oWsProfile->id(),
                    'member_id' => (int)$aMember['id'],
                    'role_options' => gfWsRoleOptions($aRoles, (int)$aMember['role'])
                ]
            ]
        ]);
    }

    return $oTemplate->parseHtmlByName('page_workspaces_manage.html', [
        'ws_title' => bx_process_output($oWsProfile->getDisplayName()),
        'close_url' => BX_DOL_URL_ROOT,
        'members_list' => $sMembersList,
        'bx_if:notice' => [
            'condition' => $sNotice !== '',
            'content' => ['notice' => bx_process_output($sNotice)]
        ],
        'bx_if:invite' => [
            'condition' => !empty($aInviteRow) && !empty($aInviteRow['code']),
            'content' => !empty($aInviteRow) && !empty($aInviteRow['code']) ? [
                'code' => bx_process_output($aInviteRow['code']),
                'join_url' => bx_html_attribute($sJoinUrl),
                'reset_url' => BX_DOL_URL_ROOT . 'workspaces.php?manage_ws=' . (int)$oWsProfile->id() . '&invite_reset=1'
            ] : ['code' => '', 'join_url' => '', 'reset_url' => '']
        ]
    ]);
}
