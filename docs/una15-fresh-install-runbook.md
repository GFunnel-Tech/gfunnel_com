# UNA 15 Fresh-Install Runbook (GFunnel)

> **Why this exists.** The 14 → 15 in-place upgrade path is fragile (it broke the
> live site twice — see PR history around #82/#84). UNA ships no reliable 14→15
> SQL migration in-tree; its real migration runs through the Studio auto-updater.
> The clean, repeatable path is a **fresh 15 install on a new database**, then
> install our modules on top. This branch (`main`, `15.0.0-RC1`) is already the
> complete, deploy-ready file tree for that: pristine UNA 15 core **plus** every
> GFunnel and third-party module. Nothing is missing from the files — a fresh
> start is a **database + install-sequence** job, not a code job.
>
> **Decision on record:** the site starts **empty**. Existing 14 users/content
> stay parked in the old 14 database (kept as a backup) and are re-added by hand
> afterward — not migrated.

---

## Inventory this branch already carries (nothing to add to the files)

- **UNA 15.0.0-RC1 core** — clean, no in-place core edits.
- **GFunnel modules (5):** `gfunnel/onboarding_module`, `gfunnel/home`,
  `gfunnel/applications`, `gfunnel/sitemap`, `gfunnel/shell`.
- **Root GFunnel pages (13):** all `gf_*.php` (auth, login, create-account,
  onboarding, bug, menu, sidebar, timer, applications, business, services,
  marketplace, resources).
- **Third-party modules (16), each needs its vendor license re-entered:**
  - `modzzz/`: goal, jobs, listing, message, news
  - `aqb/`: advanced_menu, affiliate, auto_friends, autoonline, locations_map,
    personal_bookmarks, seo_friendly
  - `smsoftwares/people`, `msolutions/fansonly`,
    `publicchat/complete_profile`, `greenmeteor/discord_connect`

All 79 non-core modules were verified to have both `install/config.php` and
`install/installer.php`, so each builds its own tables on install. Exceptions:
`gfunnel/sitemap` installs via its own `install.php` (not the standard Studio
flow); `gfunnel/shell` has no standard manifest — install/enable it last and
verify by hand.

---

## Phase 0 — Protect existing data (one safety step)

1. Create a **new empty MySQL database** (e.g. `gfunnel_15`) with its own user.
2. **Leave the existing 14 database untouched.** This makes "fresh start"
   cost-free to reverse — the old data is still there if you ever want it.

## Phase 1 — Install UNA 15 core

1. Deploy current `main` to the web root (clean 15 + all module code — already
   correct; no build step).
2. Rename `inc/header.inc.php` → `inc/header.inc.php.bak`. **The installer only
   runs when that file is absent.** (It is gitignored, so a clean checkout won't
   have it anyway — a redeploy that preserved it is the case to watch for; a
   *missing* `header.inc.php` on the current broken deploy is itself a common
   cause of the 500-everywhere.)
3. Make these writable by the web user: `cache/ cache_public/ tmp/ logs/
   storage/` (and `inc/` so the installer can write the new `header.inc.php`).
4. Browse to `https://gfunnel.com/install/` and run the wizard:
   - point it at the **new** database from Phase 0;
   - set the admin account;
   - it builds the 15 schema and installs the **bundled boonex core modules**.
5. When it finishes, **delete or lock the `install/` directory** — UNA refuses
   to run while it exists.

At this point the site boots on 15 with a stock feature set.

## Phase 2 — Install our modules (Studio → Modules)

Install in this order. UNA resolves most ordering itself; this order avoids
surprises. Each install runs that module's installer (creates its tables,
registers pages/blocks/cron).

1. **Design template** — confirm the active template (protean/artificer/lucid)
   is installed and selected.
2. **boonex data-types the site uses** — persons, organizations, groups, spaces,
   channels, courses, events; then content/commerce the site uses (posts, files,
   photos, albums, videos, market, payment, credits, stripe_connect, etc.).
   Install what the site actually uses; you don't have to install every bundled
   module.
3. **Third-party modules — re-enter each vendor license in Studio:**
   - modzzz: listing, jobs, goal, news, message
   - aqb: seo_friendly, advanced_menu, locations_map, personal_bookmarks,
     auto_friends, autoonline, affiliate
   - smsoftwares/people, msolutions/fansonly, publicchat/complete_profile,
     greenmeteor/discord_connect
4. **GFunnel modules:** onboarding_module → home → applications.
   Then **sitemap** (via its own `install.php`), and **shell** last (verify by
   hand — no standard manifest).

After each phase, load the homepage and one workspace to catch a bad install
early rather than at the end.

## Phase 3 — Restore secrets & integrations

The installer wrote a fresh `inc/header.inc.php` (DB creds + site keys). Re-add
everything that lived outside stock config:

- Payment/commerce keys: Stripe / Stripe Connect, Shopify, Snipcart.
- Mail: SMTP (`boonex/smtpmailer`) credentials.
- Analytics/tags: Google Tag Manager, Analytics.
- **Supabase directory sync:** restore the `gf_app_config` secret and re-point
  `supabase/functions/sync-directory-apps-to-mysql` at the new DB. See
  `docs/directory-sync-runbook.md`.
- Any GFunnel `sys_option` kill-switches you rely on (`gf_*` on/off flags for
  the SEO landing routes).

## Phase 4 — Re-apply the old core customizations as overrides (follow-up)

The clean-15 rebuild intentionally dropped the ~24 in-place core edits (that was
the whole point — they are what made upgrades fragile). The site runs fine
without them; re-add each the upstream-safe way, incrementally:

- module subclass, or
- a `template/` override, or
- a `BxDolAlerts` hook.

Track these in the roadmap (`docs/roadmap/PLATFORM_ROADMAP.md`) and do them one
at a time behind the now-working site — never as fresh edits to `boonex/`,
`base/`, or `inc/classes/`.

## Phase 5 — Verify

Confirm each returns 200 and renders:

- `/` (home), `/login`, `/create-account`, workspaces picker
- `/applications`, `/marketplace/applications`, `/business`, `/services`,
  `/marketplace`, `/resources`
- One workspace shell (sidebar, applications hub in-shell)
- Cron runs (`periodic/cron.php`) — sitemap regenerates.

If any page 500s, check `logs/` and the PHP error log; a missing module install
or an un-restored secret is the usual cause at this stage.
