# Workspace overview block (orgs / spaces / groups)

A structured header for **workspace profile pages** — cover, avatar, name + type
tag, description, location, founded year, an action row, a live stat strip and a
four-tile metric grid. It is the org/space/group counterpart of the
[person overview block](persons-overview-block.md) and shares its visual
language.

Because Organizations, Spaces and Groups all extend `base/groups`, the block
lives **once** on the shared base and every group-type module inherits it.

## Files

| File | Purpose |
|------|---------|
| `modules/base/groups/classes/BxBaseModGroupsModule.php` | `serviceOverviewStructured()` — computes real data, renders the block |
| `modules/base/groups/template/overview_structured.html` | Block markup (shared; resolved from the base template dir) |
| `modules/base/groups/template/css/overview_structured.css` | Scoped styles (all `.gfws-*`) |

No new module, install step, or SQL migration is required.

> **Name.** The Organizations module already ships a legacy `overview` service
> (the 3-column dashboard). This block is deliberately named
> `overview_structured` so both coexist — you choose which to place.

## Data — real only

| Element | Source |
|---|---|
| Name / avatar / URL / edit URL | `BxDolProfile` |
| Type tag | the module title (Organizations / Spaces / Groups) |
| Description | `CNF['FIELD_TEXT']` (`org_desc` / `space_desc` / `group_desc`) — if set |
| Location | `CNF['FIELD_LOCATION']` — if set |
| Founded | `CNF['FIELD_ADDED']`, year |
| Members | `CNF['OBJECT_CONNECTIONS']` (fans) initiators count |
| Followers | `sys_profiles_subscriptions` count |
| Views | `CNF['FIELD_VIEWS']` |
| Cover image | `CNF['FIELD_COVER']` via `OBJECT_IMAGES_TRANSCODER_COVER` — branded gradient fallback |
| Edit affordances | shown only when `checkAllowedEdit()` allows |

**No fabricated data.** Meta items with no value are omitted; the org-only
figures from the reference mockup (Partners / Customers / rating / activity) are
replaced with real workspace metrics.

## Placing it (one-time per module, in Studio)

For each of **Organizations**, **Spaces**, **Groups**:

1. **Studio → Pages** → open the module's **View … profile** page.
2. **Add block → Service**.
3. Module: the group module (e.g. `bx_organizations`). Service:
   **`overview_structured`** (lowercase, without the `service` prefix). Leave the
   parameter empty — the profile page passes the workspace id in automatically.
4. Drag it to the top of the main column; hide/relocate the default blocks you
   no longer want front-and-centre.
5. Save.

To roll back, delete that one block in the Page Builder.

## Viewer-relative actions

Owners see **Edit profile**; everyone else sees the platform's native workspace
actions (Join / Message / Follow) rendered from
`CNF['OBJECT_MENU_ACTIONS_VIEW_ENTRY_ALL']` (e.g. `bx_organizations_view_actions_all`)
via `setContentId()` — membership, permissions and AJAX are the platform's.

## Follow-ups

- **Real cover** already wired; a pinned announcement row could follow once a
  source is chosen.
