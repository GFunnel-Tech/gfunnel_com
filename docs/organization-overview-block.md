# Organization Overview block

A branded "Organization Overview" for the organization **profile page** —
matching the GFunnel org-home design (Ask-AI panel, Departments grid, Activity,
Network & Partners, and a Calendar + Quick Links rail).

It ships as a UNA **service block** so it can be dropped onto the profile page
from Studio without touching the database by hand.

## Files

| File | Purpose |
|------|---------|
| `modules/boonex/organizations/classes/BxOrgsModule.php` | `serviceOverview()` — renders the block |
| `modules/boonex/organizations/template/overview.html` | Block markup |
| `modules/boonex/organizations/template/css/overview.css` | Scoped styles (all `.gforg-*`) |

No new module, install step, or SQL migration is required — the service is a
public method on the existing Organizations module and is discovered by Studio
automatically.

## Placing it on the profile page (one-time, in Studio)

The set of blocks on the org profile page lives in the site database (the
Studio Page Builder), not in these files — so the block has to be added once
per instance:

1. **Studio → Pages** → open **Organizations → View organization profile**
   (page URI `view-organization-profile`).
2. **Add block → Service**.
3. Module: **Organizations**. Method: **Overview** (`serviceOverview`).
   Leave the parameter empty — the profile page passes the organization id in
   automatically.
4. Drag it to the top of the main (center) column. To make the profile look
   like the reference design, you can hide/relocate the default blocks you no
   longer want front-and-center (About, etc.); the **Members** and **Cover**
   blocks are left as-is.
5. Save. Reload any organization profile to see it.

To roll back, delete that one block in the Page Builder.

## Making the content data-driven (optional follow-ups)

The layout renders live where it is cheap (org title/url, section links) and
uses GFunnel defaults for the rest. Natural next steps, each isolated to
`serviceOverview()` + `overview.html`:

- **Departments** — currently a static 6-tile grid. Wire to real
  sub-spaces/teams by iterating a data source and building the tiles in
  `serviceOverview()` (pass an already-rendered `departments` HTML string into
  the template instead of the inline markup).
- **Network & Partners** — replace the two sample rows with the org's
  `bx_organizations_fans` connections (see `BxOrgsGridConnections`).
- **Quick Links** — back with a small settings table or profile custom field.
- **Activity** — the placeholder rows can be swapped for the real
  `bx_timeline` block, or leave Activity as its own native block below.

## Notes

- All CSS is prefixed `.gforg-` and scoped under a single `.gforg` root, so it
  cannot collide with UNA core or other modules.
- The block is theme-agnostic (plain markup + its own CSS); it renders the same
  inside Protean/Lucid and inside the app chrome shown in the design.
