# GFunnel Platform — Reconstruction Roadmap

> **Purpose.** This repo (`GFunnel-Tech/platform`) starts from a **fresh, clean
> UNA 15.0.0-RC1** base. This document is the ordered checklist for rebuilding
> the full GFunnel platform on top of it — every module, page, integration, and
> customization that the site needs.
>
> **Reference source.** Everything listed here already exists, working, in the
> `GFunnel-Tech/gfunnel_com` repo (clean 15 tree + all GFunnel code). Each item
> below names its source path there, so "re-add" means **copy the proven code
> across and install it** — not rewrite from scratch.
>
> **Ground rules for the rebuild**
> - Base stays **pristine UNA 15**. Never hand-edit `boonex/`, `base/`,
>   `inc/classes/`, or `plugins/` — every GFunnel behavior is added as a module,
>   a subclass, a `template/` override, or a `BxDolAlerts` hook.
> - Bring things over **in the phase order below** and verify the site boots
>   after each phase, so a bad step is caught immediately.
> - The site starts with an **empty database** (decision on record); content is
>   re-added by hand afterward.

---

## Phase 0 — Base: fresh UNA 15.0.0-RC1  ☐

- [ ] Web root seeded with clean UNA 15 (this repo's base).
- [ ] New **empty** MySQL database created (old data not migrated).
- [ ] Runtime dirs writable: `cache/ cache_public/ tmp/ logs/ storage/ inc/`.
- [ ] Run `/install/` wizard → builds schema + writes `inc/header.inc.php`.
- [ ] Delete/lock `install/` afterward.
- [ ] Site boots on stock 15. ✅ checkpoint

## Phase 1 — Design template + core module selection  ☐

- [ ] Confirm active design template installed & selected
      (`boonex/protean` / `boonex/artificer` / `boonex/lucid`).
- [ ] Install the stock boonex data-type & content modules the site uses:
      persons, organizations, groups, spaces, channels, courses, events,
      posts, files, photos, albums, videos, market, payment, credits,
      stripe_connect, accounts, acl, invites, notifications, messenger,
      timeline. *(Install what the site uses; not every bundled module.)*
- [ ] Site still boots. ✅ checkpoint

## Phase 2 — Third-party (licensed) modules  ☐

Source: `modules/<vendor>/<name>/` in `gfunnel_com`. **Each needs its vendor
license re-entered in Studio.**

- [ ] `modzzz/listing`   — Business Listing directory
- [ ] `modzzz/jobs`      — Job listings
- [ ] `modzzz/goal`      — Fundraising goals
- [ ] `modzzz/news`      — News
- [ ] `modzzz/message`   — Profile banner message
- [ ] `aqb/seo_friendly` — SEO-friendly URLs *(install early; routing depends on it)*
- [ ] `aqb/advanced_menu`
- [ ] `aqb/locations_map`
- [ ] `aqb/personal_bookmarks`
- [ ] `aqb/auto_friends`
- [ ] `aqb/autoonline`
- [ ] `aqb/affiliate`
- [ ] `smsoftwares/people`        — people directory
- [ ] `msolutions/fansonly`       — paid subscriber content
- [ ] `publicchat/complete_profile`
- [ ] `greenmeteor/discord_connect`
- [ ] Site still boots. ✅ checkpoint

## Phase 3 — GFunnel modules  ☐

Source: `modules/gfunnel/<name>/` in `gfunnel_com`. Prefixes must stay intact.

- [ ] `gfunnel/onboarding_module` (`BxGfunnelOnb` / `gfo_`) — onboarding + signed API
- [ ] `gfunnel/home` (`BxGfHome` / `gfhome_`) — homepage service blocks
- [ ] `gfunnel/applications` (`BxGfApps` / `gfapp_`) — Application Hub blocks
- [ ] `gfunnel/sitemap` (`GfSiteMap`) — install via its own `install.php`; registers `gf_sitemap` cron
- [ ] `gfunnel/shell` — install/enable last; verify by hand (non-standard manifest)
- [ ] Site still boots. ✅ checkpoint

## Phase 4 — GFunnel root pages & shared renderers  ☐

These are plain files — copy them into the web root from `gfunnel_com`.

- [ ] **Standalone root pages (3):** `home.php`, `splash.php`, `workspaces.php`
      (+ `default.php`, `index.php` routing logic).
- [ ] **Feature endpoints (13):** `gf_auth.php`, `gf_login.php`,
      `gf_create_account.php`, `gf_onboarding.php`, `gf_bug.php`, `gf_menu.php`,
      `gf_sidebar.php`, `gf_timer.php`, `gf_applications.php`, `gf_business.php`,
      `gf_services.php`, `gf_marketplace.php`, `gf_resources.php`.
- [ ] **Shared renderers:** `inc/gf_home_blocks.inc.php`,
      `inc/gf_app_blocks.inc.php`, `inc/gf_workspace_admin.inc.php`.
- [ ] **Router:** `r.php` entries for the SEO landing routes.
- [ ] **Skin assets:** `template/css/gf_*.css`, `template/js/gf_*.js`,
      `template/_gf_sidebar.html`, `template/page_home.html`.
- [ ] `sys_option` kill-switches set correctly (`gf_*` on/off flags).
- [ ] All routes 200: `/`, `/login`, `/create-account`, workspaces,
      `/applications`, `/marketplace/applications`, `/business`, `/services`,
      `/marketplace`, `/resources`. ✅ checkpoint

## Phase 5 — Integrations & secrets  ☐

Re-enter everything that lived outside stock config (installer made a fresh
`header.inc.php`):

- [ ] Stripe / Stripe Connect keys
- [ ] Shopify, Snipcart
- [ ] SMTP mailer (`boonex/smtpmailer`)
- [ ] Google Tag Manager / Analytics
- [ ] **Supabase directory sync** — `gf_app_config` secret; re-point
      `supabase/functions/sync-directory-apps-to-mysql` at the new DB
      (see `docs/directory-sync-runbook.md` in `gfunnel_com`).
- [ ] **Directory content pipeline** — `supabase/functions/fetch-app-tutorials`
      (see `docs/directory-content-pipeline.md`).
- [ ] Cron running (`periodic/cron.php`): sitemap regenerates.

## Phase 6 — Re-apply the ~24 core customizations as overrides  ☐

The old install had ~24 in-place edits to UNA core — the exact thing that made
upgrades fragile. **Do NOT re-apply them as core edits.** Reconstruct each the
upstream-safe way and check it off here as you go:

- [ ] Derive the list: diff `gfunnel_com`'s pre-clean history against pristine
      14.0.0 to enumerate each core edit (this was in-flight as the
      "24 customizations" analysis).
- [ ] For each: re-implement as a **module subclass**, a **`template/` override**,
      or a **`BxDolAlerts` hook**. One at a time, behind the working site.
- [ ] Track them individually below (fill in as identified):
  - [ ] …
  - [ ] …

## Phase 7 — Data & content  ☐

- [ ] Re-create admin + staff accounts.
- [ ] Re-add workspaces / directory apps (`gf_workspace_apps`, directory sync).
- [ ] Re-add pages/content as needed.
- [ ] Final smoke test of every route + one full workspace shell.

---

## Appendix — full inventory carried by `gfunnel_com` (the reference)

| Category | Count | Location |
|---|---|---|
| UNA core | — | pristine 15.0.0-RC1 |
| GFunnel modules | 5 | `modules/gfunnel/*` |
| Third-party modules | 16 | `modules/{modzzz,aqb,smsoftwares,msolutions,publicchat,greenmeteor}/*` |
| GFunnel root pages | 13 | `gf_*.php` |
| Standalone root pages | 3 | `home.php`, `splash.php`, `workspaces.php` |
| Shared renderers | 3 | `inc/gf_*.inc.php` |
| Supabase functions | 2 | `supabase/functions/*` |
| Batch/migration scripts | 3 | `custom_batch_updates/*` |
| Docs | 24 | `docs/*` |

> Keep this roadmap current: check items off as they land, and add any newly
> discovered dependency in the phase where it belongs.
