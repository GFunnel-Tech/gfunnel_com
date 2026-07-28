# Workspace administration — roles, transfer & claim

Members administer their workspaces (organizations / spaces / groups) from the
**workspace picker** (`workspaces.php`, the logged-in site root). The logic lives
in **`inc/gf_workspace_admin.inc.php`** (kept out of the picker page the same way
the homepage keeps its section renderers in `inc/gf_home_blocks.inc.php`).

Everything here is **type-aware** and builds on UNA-native primitives — no core
edits. A workspace that is not a manageable group type (a person profile, or any
module without an admins/roles overlay) makes every helper below a no-op:
`gfWsGroupModule()` returns `null`.

## Acting identity — the personal account is always present

Entering a workspace does **not** change who you are. Launching a workspace
(`workspaces.php?gf_switch=<id>`, or the top-nav workspace selector's
`?gf_ws=<id>`) keeps the member's **personal profile as the acting identity**
and merely *scopes* the site to that workspace — `gf_ws` pins the per-workspace
menu, timer and context (`BxBaseFunctions::getGfActiveWorkspaceId`), which is
independent of the acting profile. The `gf_switch` handler proactively resets
the acting context to the personal profile on entry, so any lingering act-as
(e.g. from UNA's native profile switcher) is dropped — the personal account is
always present.

Two workspace capabilities remain, both **deliberate and role-gated**, never
assumed on entry:

- **Edit / manage the workspace** (members, roles, settings, invite code) —
  gated by owner/admin via `gfWsCanManage`, evaluated against the member's role
  regardless of acting context. This is the "Manage" panel in the picker.
- **Act as the workspace** (post/comment *as* the org, etc.) — the explicit
  identity switch, still performed via `gfWsSwitchContext` / UNA's native
  profile switcher, and still limited to the owner and delegated admins of a
  type that supports `act_as_profile` (organizations, persons).

## Concepts (UNA-native)

- **Owner** = `sys_profiles.account_id` — the authoritative edit/delete gate.
  Kept in sync with the **content author** (`CNF['FIELD_AUTHOR']`, a person
  profile id) which grants implicit admin.
- **Member** = a fan (the module's `CNF['OBJECT_CONNECTIONS']` connection).
- **Role** = a row in the module's admins overlay (`CNF['TABLE_ADMINS']`), written
  by the module's `setRole()` / `unsetRole()`. Built-ins: `1` Administrator,
  `2` Moderator, `0` Member. Role *definitions* (templated + custom roles) are the
  module's site-wide data list `CNF['OBJECT_PRE_LIST_ROLES']`
  (`sys_form_pre_values`), edited in Studio.

## Who can do what

| Action | URL (on `workspaces.php`) | Allowed to | Notes |
|---|---|---|---|
| Open manage panel | `?manage_ws=<id>` | owner **or** admin (`gfWsCanManage`) | members + roles; invite code for owners |
| Assign / change a member's role | `?manage_ws=<id>&set_role=<pid>&role=<r>` | owner or admin | `gfWsSetMemberRole`; owner's role is locked (use transfer) |
| Transfer ownership | `?manage_ws=<id>&transfer_to=<pid>` | **owner only** | `gfWsTransferOwnership`; new owner must be a member under their profile limit; previous owner kept as admin |
| Claim ownership | `?claim_ws=<id>` | any member of a claimable workspace | `gfWsClaimWorkspace`; see below |

All three write actions use POST-redirect-GET with a session flash
(`gf_ws_flash`) so a refresh never re-submits. This mirrors the existing invite
actions' GET-link convention.

Transfer and claim both funnel through `gfWsApplyOwnership()`, which reproduces
the exact writes UNA makes at creation — for the new owner:
`account_id` (`BxDolProfile::move`) → content `author` → fan + administrator
(`setRole`).

## Claim — provisioning dependency

Claim is **dormant by default**. Directory-imported orgs live in the flat
`gf_directory_apps` mirror table (see `docs/directory-sync-runbook.md`), **not**
as org profiles, and UNA has no native "unowned" state. So a workspace is only
claimable when:

1. the sys_option **`gf_workspace_placeholder_account`** is set to a GFunnel
   placeholder/system account id, **and**
2. the workspace profile is currently owned by that account
   (`gfWsIsClaimable()`).

Until imported orgs are provisioned as real org profiles under such a placeholder
account (the org→dept→role→user direction sketched in
`docs/directory-provisioning-target-model.md`), nothing is claimable and the
Claim affordance never appears. Once they are, claim lights up automatically and
hands the workspace from the placeholder to the claimer.

Claim is **instant** for the first claimer today; a verification gate (email-domain
match, admin approval) is the intended next layer and belongs inside
`gfWsClaimWorkspace()`.

## Files

- `inc/gf_workspace_admin.inc.php` — all helpers (detection, roles, transfer, claim, card renderer).
- `workspaces.php` — the `manage_ws` / `claim_ws` handlers and the picker wiring.
- `template/page_workspaces_manage.html`, `template/page_workspaces_member_item.html` — manage card + member row.
- `template/page_workspaces_item.html` — the Manage / Claim affordances on each workspace row.
- `template/css/gf_workspaces.css` — manage-panel styles.
