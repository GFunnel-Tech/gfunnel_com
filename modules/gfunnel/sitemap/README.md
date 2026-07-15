# GFunnel Sitemap Module

Automatic XML sitemap for gfunnel.com. Replaces the old static `sitemap.xml`
(a stale, capped crawler export) with a live sitemap that always contains
every current public page — new pages, posts, events, products, profiles,
courses, discussions, etc. appear automatically, with no manual step.

## How it works

| Piece | Role |
| --- | --- |
| `classes/GfSiteMapGenerator.php` | Builds the sitemap from live data and caches it in `cache/` |
| `classes/GfSiteMapCron.php` | UNA cron job that regenerates on a schedule |
| `install.php` | One-time installer: registers the cron job, builds the first sitemap |
| `/sitemap.php` (site root) | HTTP endpoint; `/sitemap.xml` and `/sitemap-N.xml` rewrite here (`.htaccess`) |
| `/robots.php` (site root) | Serves `/robots.txt` with the correct `Sitemap:` directive |

Freshness is guaranteed two ways:

1. **Cron** — the `gf_sitemap` job in `sys_cron_jobs` (default `15 */3 * * *`,
   every 3 hours) rebuilds the cache via `periodic/cron.php`.
2. **On demand** — if the cache is older than `gf_sitemap_ttl` (default 6h,
   e.g. because cron is down), the next `/sitemap.xml` request rebuilds it
   inline, with a lock so concurrent requests never rebuild twice. Visitors
   are otherwise always served the cached copy.

## What gets included

- The site root (`/`) at priority 1.0.
- **System/builder pages** (`sys_objects_page`) that are: visible to guests
  (Non-member level), not marked `noindex`, belong to an enabled module, and
  don't require URL parameters. Auth/utility/manage/create/edit/search pages
  are excluded via a built-in deny list.
- **Content entries** of every enabled module that declares a public view page
  (`URI_VIEW_ENTRY` + `TABLE_ENTRIES` in its config). Per-entry filters, each
  applied only when the table has the column: `status = 'active'`,
  `status_admin = 'active'`, privacy `allow_view_to = Public`, and — for
  profile-based modules (people, organizations, groups, spaces, events,
  courses, channels…) — an `active` row in `sys_profiles`.
  `lastmod` comes from the entry's `changed`/`added` timestamp.
- **Extra URLs** from the `gf_sitemap_extra_urls` setting.

URLs are produced by `BxDolPermalinks`, so **SEO links** (pretty slugs from
`sys_seo_links`) are used when enabled; existing slugs are bulk-prefetched and
missing ones are created on the fly exactly as the front-end would.

Excluded by default (private/ephemeral/thin content): `bx_convos`, `bx_tasks`,
`bx_timeline`, `bx_messenger`, `bx_invites`, `bx_attendant`, `bx_quoteofday`,
`bx_donations`, `bx_stripe_connect`, `bx_stream`.

Above 45,000 URLs the module automatically switches to a
[sitemap index](https://www.sitemaps.org/protocol.html#index) at
`/sitemap.xml` pointing to `/sitemap-1.xml`, `/sitemap-2.xml`, …

## Installation (one time, on the server)

```
php modules/gfunnel/sitemap/install.php
```

(or open `https://gfunnel.com/modules/gfunnel/sitemap/install.php` as an
admin). This registers the cron job and builds the first sitemap. Verify at
`https://gfunnel.com/sitemap.xml` and `https://gfunnel.com/robots.txt`, then
resubmit the sitemap in Google Search Console / Bing Webmaster Tools.
(Google retired the sitemap "ping" endpoint in 2023 — Search Console
submission + the robots.txt directive is the supported path.)

To remove: `php modules/gfunnel/sitemap/install.php --uninstall` (CLI only —
the web entry point deliberately can't do anything destructive).

Note: prefer running the installer from the CLI on large sites — the first
build creates any missing SEO slugs (`sys_seo_links`) for existing content,
which can take a while once; later runs reuse them via a bulk prefetch.
Settings are read with `getParam()` and fall back to the defaults below when
the option doesn't exist; to change one, add the option row in Studio →
Settings (or insert it into `sys_options`) with the exact name.

## Settings (Studio → Settings, `sys_options` — all optional)

| Param | Default | Meaning |
| --- | --- | --- |
| `gf_sitemap_disable` | (empty) | `on` turns /sitemap.xml into a 404 |
| `gf_sitemap_ttl` | `21600` | max cache age (seconds) before on-demand rebuild |
| `gf_sitemap_modules_exclude` | (empty) | comma-separated module names to skip, **added to** the built-in list above |
| `gf_sitemap_modules_include` | (empty) | comma-separated module names to re-include despite the built-in list (e.g. `bx_timeline`) |
| `gf_sitemap_pages_exclude` | (empty) | comma-separated system page URIs to skip (adds to the deny list) |
| `gf_sitemap_max_per_module` | `0` | cap entries per module, 0 = unlimited |
| `gf_sitemap_extra_urls` | (empty) | one per line: `url [changefreq] [priority]` |
| `gf_robots_extra` | (empty) | raw lines appended to robots.txt |

## Admin tools

- Force rebuild + stats: `https://gfunnel.com/sitemap.php?regenerate=1` (admins only, returns JSON)
- Generation stats are logged to the `sys_cron_jobs` log channel.
