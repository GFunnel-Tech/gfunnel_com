# GFunnel left navigation (sidebar)

The GFunnel-owned, DB-backed, customizable left navigation — the workspace/app
sidebar. Built the same way as the top nav (`getGfToolbar`): our own markup +
config, rendered into the app layout in place of the stock `sys_site_panel`
menu. No DB-configured menu, no dependency on the upstream design template.

## Files

| File | Purpose |
|------|---------|
| `template/scripts/BxBaseFunctions.php` | `getGfSidebar()` — the always-loaded renderer (reads the tables, merges per-member overrides, falls back to built-in defaults). Wired via the `gf_sidebar` component key. |
| `template/_page_sidebar.html` | Sidebar markup |
| `template/css/gf_header.css` | `.gf-side*` styles (dark rail, orange active state, footer) — loaded on every app-layout page |
| `modules/boonex/artificer/data/template/system/pt_application.html` | Stock `sys_site_panel` replaced with `__gf_sidebar__` |
| `modules/gfunnel/nav/` | The `gfunnel_nav` module: manifest, install SQL, `BxGfNavModule` (`serviceSidebar` / `serviceSaveMenu`), `BxGfNavDb` (customization writes) |
| `custom_batch_updates/gf_nav_install.php` | Reliable install migration (creates + seeds the tables) |

## Tables

| Table | Purpose |
|---|---|
| `gfn_items` | Global default items (`key`, `title`, `url`, `icon`, `order`, `active`, `system`). Admin-editable — the base menu everyone sees. |
| `gfn_user_items` | Per-member, per-workspace overrides: hidden defaults, reordered items, and the member's own custom links (`custom=1`, `item` NULL). |

`url` is stored **relative** and resolved at render (`http(s)://` as-is,
`page.php` via permalink, else under the site root; empty = root / Home).
`icon` is a **key** mapped to an inline SVG in `_gfNavIcon()` /
`BxGfNavModule::getIcon()`.

## How rendering works

`getGfSidebar()`:
1. Reads `gfn_items` if the table exists; otherwise uses `_gfSidebarDefaults()`
   (so the nav renders **before/without** the install migration).
2. Reads the viewer's `gfn_user_items` (if the table exists) and merges:
   hidden items dropped, order overridden, custom links appended — the same
   model as the subheader hub tabs (`getGfSubheader` + `gf_user_menu`).
3. Marks the active item from the request path and renders `_page_sidebar.html`.

## Install

```
php custom_batch_updates/gf_nav_install.php
```
Creates `gfn_items` + `gfn_user_items` and seeds the 12 defaults. Idempotent.
(The `gfunnel_nav` module can also be installed via Studio, which runs
`install/sql/install.sql` — but the migration is the reliable path on this
stack, where the Studio install-SQL mechanism is not used by any module.)

Deploy on this server (OpenLiteSpeed + LSMCD): `git pull` →
`systemctl restart lshttpd` → restart `lsmcd` → `rm -rf cache/* cache_public/*`.

## Changing the nav

- **Items (global):** edit `gfn_items` (SQL/admin) — or, before the migration,
  the `_gfSidebarDefaults()` array. Keys, titles, links, order, icon, active.
- **Per-member customization:** `BxGfNavModule::serviceSaveMenu($action, $data)`
  handles `hide` / `show` / `add` / `delete` / `reorder` / `reset`, writing to
  `gfn_user_items`. (The interactive edit UI in the sidebar is a follow-on; the
  data layer + schema are in place.)
- **Link overrides:** each default link is also overridable via a `gf_nav_*_url`
  sys_option.

## Icon keys

`home · message · sales · memory · apps · social · explore · communities ·
market · calendar · learning · partners · settings · dot` (fallback).
