# GFunnel Home — block-based homepage (UNA Page Builder)

The homepage can be composed from **native UNA page blocks** you manage in
**Studio → Page Builder** (add, reorder, show/hide) — including custom **HTML/CSS/JS**
blocks and **seasonal** blocks that appear only inside a date window.

This is delivered by the **GFunnel Home** module (`modules/gfunnel/home`). The module
adds no tables and touches no core page rows on install — it only exposes each homepage
section as a **service** you can drop into any page. The standalone `home.php` keeps
working exactly as before, whether or not the module is installed, so nothing on the
live site breaks.

> Apply this on a **staging instance first** and verify in Studio before production.
> These are GUI steps in Studio; no manual SQL is required.

## 1. Install the module

1. Copy `modules/gfunnel/home` onto the server (it deploys with the repo).
2. Studio → **Dashboard / Modules** → find **GFunnel Home** → **Install**, then
   **Enable**. (Or Studio → Apps Market → Local.)
3. That's it — the module registers its service blocks. No page is changed yet.

## 2. Add the blocks to a page

Studio → **Page Builder** → open the page you want (e.g. a new page, or the home page):

**Add block → Service**, and set:

- **Module:** `GFunnel Home`
- **Method:** one of:
  | Method | Renders |
  |---|---|
  | `block_hero` | Dark hero: wordmark + global search + real stat line |
  | `block_catalogs` | Catalogs row (Departments / Marketplace / Integrations / Templates / Learn / Partners) |
  | `block_departments` | The 14-department process grid |
  | `block_featured` | Featured apps & modules, live from the directory mirror |
  | `block_community_news` | News & Community feeds |
  | `block_cta` | Closing call-to-action |
  | `block_seasonal_html` | Your own markup, shown only in a date/season window (see §3) |

Give each block the **“Content only” design box** (no border/caption) and place it in a
**full-width** cell so the dark, full-bleed sections render edge to edge. Drag to
reorder; toggle visibility per member level as usual. The homepage stylesheet is loaded
automatically by the blocks.

## 3. Seasonal / custom-code blocks (`block_seasonal_html`)

Add a **Service** block with method `block_seasonal_html`. Its **parameters** are
positional:

1. **html** — your trusted HTML/CSS/JS markup (a banner, promo, custom layout, …)
2. **active_from** — `YYYY-MM-DD` start (empty = no start bound)
3. **active_to** — `YYYY-MM-DD` end (empty = no end bound)
4. **season** — `spring` | `summer` | `fall` | `winter` | `any` (or a comma list; empty = any)

The block renders **only** when today is inside the window; otherwise it outputs nothing
and simply disappears. Examples:

- **Holiday banner, Dec 1–31:** html = `<div class="gfh"><section class="gfh-cta">…Happy Holidays…</section></div>`, active_from = `2026-12-01`, active_to = `2026-12-31`.
- **Summer-only promo:** season = `summer` (no dates).
- **Black Friday:** active_from = `2026-11-27`, active_to = `2026-11-30`.

You can add several seasonal blocks with different windows and stack them; each shows
only in its own window. Wrap markup in `<div class="gfh">…</div>` to reuse the homepage
styles (buttons `gfh-btn gfh-btn-orange`, sections `gfh-sec`, etc.).

## 4. What stays dynamic

- **Departments** — edits to the `gf_departments` table (or the static fallback).
- **Featured apps** — live from the synced `gf_directory_apps` mirror.
- **News / Community** — rows in `gf_content_objects` / `gf_community_posts`
  (`docs/sql/gf_home_content.mysql.sql`).
- **Counts** — computed live; shown only where real.

## 5. Making the block page the site root (optional)

The root currently renders the standalone `home.php` for logged-out visitors
(`index.php` → `home.php`). To serve the block-based page at the root instead, point that
branch of `index.php` at your Page-Builder page (e.g. `page.php?i=<your-uri>`), or set the
page as the site’s home in Studio. Keep `home.php` as a fallback until the block page is
verified in staging.

## Uninstall / rollback

Studio → disable & uninstall **GFunnel Home**. Because the module owns no tables and
altered no core page rows on install, removing it just removes the service blocks; any
page you built from them falls back to whatever else is on that page. `home.php` is
unaffected throughout.
