# Person profile overview block

A structured header for the **person profile page** — cover, avatar, name, bio,
location, member-since, an action row, a live stat strip and a four-tile metric
grid. It mirrors the GFunnel profile design (the same visual language as the
Organization Overview block) and ships as a service block so it can be dropped
onto the profile page from Studio without editing the database by hand.

## Files

| File | Purpose |
|------|---------|
| `modules/boonex/persons/classes/BxPersonsModule.php` | `serviceOverview()` — computes real data, renders the block |
| `modules/boonex/persons/template/overview.html` | Block markup |
| `modules/boonex/persons/template/css/overview.css` | Scoped styles (all `.gfperson-*`) |

No new module, install step, or SQL migration is required — the service is a
public method on the existing Persons module and is discovered by Studio
automatically.

## Data — real only

| Element | Source |
|---|---|
| Name / avatar / URL / edit URL | `BxDolProfile` (name, thumb, url, edit url) |
| Bio | `bx_persons_data.description` (`CNF['FIELD_TEXT']`) — rendered only if set |
| Location | `bx_persons_data.location` (`CNF['FIELD_LOCATION']`) — rendered only if set |
| Member since | `bx_persons_data.added` (`CNF['FIELD_ADDED']`), year |
| Connections | `sys_profiles_friends` mutual count |
| Followers | `sys_profiles_subscriptions` count |
| Profile views | `bx_persons_data.views` (`CNF['FIELD_VIEWS']`) |
| Edit affordances (Edit cover / Edit profile) | shown only when `isAllowedEdit()` |

**No fabricated data.** Meta items with no value are omitted (their `bx_if`
block is skipped); member-since falls back to an em-dash in the card. The cover
uses the branded gradient — a real cover image is a noted follow-up, not a fake.

## Deliberately omitted (until a real source exists)

- **Pinned team post** — the reference mockup shows a pinned banner. There is no
  per-profile pinned-post source wired here, so it is left out rather than
  filled with placeholder text.
- **Org-only metrics** (Team / Partners / Customers / rating) — those are
  organization concepts; the person block uses real person metrics instead.

## Placing it on the profile page (one-time, in Studio)

1. **Studio → Pages** → open **Persons → View persons profile**
   (page URI `view-persons-profile`).
2. **Add block → Service**.
3. Module: **Persons** (`bx_persons`). Service: **`overview`** (UNA's name for
   the `serviceOverview()` method — enter it lowercase, without the `service`
   prefix). Leave the parameter empty — the profile page passes the person id in
   automatically.
4. Drag it to the top of the main (center) column. To match the reference
   design, hide/relocate the default blocks you no longer want front-and-centre.
5. Save. Reload any person profile to see it.

To roll back, delete that one block in the Page Builder.

## Follow-ups (each isolated to `serviceOverview()` + `overview.html`)

- **Real cover image** — read `CNF['FIELD_COVER']` via the cover storage /
  transcoder and pass a `background-image` into `.gfperson-cover`.
- **Viewer-relative actions** — for non-owners, swap Edit for Connect / Message
  / Follow using the `sys_profiles_friends` / `sys_profiles_subscriptions`
  connection actions.
- **Pinned post** — wire to a real `bx_timeline` pinned item once that source is
  chosen.
