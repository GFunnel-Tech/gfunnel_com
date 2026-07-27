# GFunnel Codebase Index

> **Read this first, before writing or editing any code.**
> This file is Claude's map of the repository. Everything the site does
> already lives somewhere in here. Your job before adding anything is to find
> *where* — so a change lands in the right module, following the pattern that
> module already uses, instead of inventing a new place for it.

**Platform:** UNA CMS `14.0.0` (see `inc/version.inc.php`) — a PHP community
platform — with GFunnel customizations layered on top.
**Stack:** PHP (core), MySQL, HTML templates, jQuery/vanilla JS front-end,
plus one Supabase edge function (TypeScript) for directory sync.

---

## 0. Before you add ANY code — the workflow

The features you're asked to touch almost always exist already. Follow this
order every time. **Do not skip to "create a new file."**

```
1. LOCATE  — Use §1 (decision table) + §2 (module catalog) to find the
             existing home for this concern. Grep to confirm.
2. DECIDE  — Which of these three is it?

   (a) Fits an existing module/page  → EDIT that module in place.
   (b) New feature, but a similar     → CLONE the closest module, rename
       module exists                     every prefix, then build it out. (§3)
   (c) Genuinely new + nothing close  → Create a new gfunnel module from the
                                         canonical template. (§3) — rare;
                                         say so before doing it.

3. MIRROR  — Whatever you edit or clone, match its structure, naming,
             prefixes, and style exactly. Consistency is the point.
```

**"Is this module appropriate, or do I clone a close one?"** — that is the
core question this index exists to answer. When cloning (path b/c), the rule
is: copy a working module of the *same shape* (has an API? is it a data type?
a cron job? a UI block?), then change **every** identifier — `name`,
`home_dir`, `home_uri`, `db_prefix`, `class_prefix`, class filenames, table
names — to your new module's. A half-renamed clone collides with its source.

---

## 1. "Where does my change go?" — decision table

| I want to… | Go to | Notes |
|---|---|---|
| Change a **public root page** (home, splash, workspace picker) | Root `home.php`, `splash.php`, `workspaces.php`, `default.php` | GFunnel-authored standalone pages. `index.php` routes `/` → `home.php` (logged-out) / `workspaces.php` (logged-in). |
| Modify a **GFunnel feature endpoint** (auth, onboarding, timer, bug, directory, menu) | Root `gf_*.php` — see §2C | Each is one GFunnel domain script. |
| Add a **self-contained feature** (data type, pages, its own tables) | **A module** under `modules/gfunnel/` | The right way to add real functionality. Check §2A first for a fit; else clone (§3). |
| Extend an **existing UNA feature** (persons, groups, market, events, timeline…) | That module in `modules/boonex/*` — see §2B | **Upstream** — prefer a subclass/alert hook over editing in place (§7). |
| Add a **JSON/REST endpoint** | Depends on surface — see §4 | Usually a module `service*` method (gateway A) or a signed module `api/` script (D). |
| Touch **core framework** (accounts, ACL, storage, forms, grids, cron, cache, comments, votes) | `inc/classes/BxDol*.php` — see §5 | **Upstream core.** Extend, don't edit, if at all possible. |
| Change **global chrome** (header, footer, layout, toolbar) | `template/*.html` | Module-specific templates live in each module's own `template/`. |
| Add a **scheduled job** | A module cron class — copy `modules/gfunnel/sitemap/classes/GfSiteMapCron.php` | Registers in `sys_cron_jobs`; runs via `periodic/cron.php`. |
| Run a **one-off data migration** | `custom_batch_updates/` (or `modules/database/`) | Standalone maintenance scripts (e.g. `update_member_id.php`). |
| Change **Supabase directory sync** | `supabase/functions/sync-directory-apps-to-mysql/index.ts` | TypeScript. One-way PG→MySQL mirror. See `docs/directory-sync-runbook.md`. |
| Add/change a **content monitor** (pull tutorials/docs into the directory) | `supabase/functions/fetch-app-tutorials/index.ts` (+ future docs/help monitors) | Scheduled YouTube→`app_tutorials` fetcher; flows to MySQL via the sync above. See `docs/directory-content-pipeline.md`. |
| Add/upgrade a **PHP lib** / **front-end JS lib** | `plugins/` / `plugins_public/` | Vendored deps — don't hand-edit. |
| Write/update **docs** | `docs/` — see §6 | Update the doc your change affects, same commit. |

---

## 2. Module & feature catalog — *"does an appropriate one already exist?"*

Scan this before creating anything. Find the row that matches your concern:
if it's GFunnel-owned (§2A/§2C) you edit it; if it's upstream (§2B) you extend
it; if nothing matches, clone the closest same-shape module (§3).

### 2A. GFunnel-owned modules (`modules/gfunnel/`) — **we own & edit these**

| Module | Path | Prefixes (class / db) | Shape / what it does |
|---|---|---|---|
| **Onboarding** | `gfunnel/onboarding_module/` | `BxGfunnelOnb` / `gfo_` | Full module **with an `api/` surface** (HMAC-signed endpoints). New-user onboarding flow + data collection. **Clone this when your feature needs signed HTTP endpoints.** |
| **Sitemap** | `gfunnel/sitemap/` | `GfSiteMap` (no db prefix) | **Cron + generator** shape: live XML sitemap, cached, regenerated by the `gf_sitemap` cron job. Uses `install.php` (not the standard `install/config.php`). **Clone this for a scheduled/generated-artifact feature.** |
| **Home** | `gfunnel/home/` | `BxGfHome` / `gfhome_` | **Service-block** shape: exposes each homepage section (hero, business, services, catalogs, departments, featured, marketplace, resources, community/news, cta, seasonal-html) as a native Page-Builder `service*` block. Owns no tables; renderers live in `inc/gf_home_blocks.inc.php` (shared with the standalone `home.php`). **Clone this for a UI-block-only feature.** See `docs/gfunnel-home-blocks.md`. |
| **Applications** | `gfunnel/applications/` | `BxGfApps` / `gfapp_` | **Service-block** shape: exposes the Application Hub (workspace app launcher + directory) as Page-Builder blocks `serviceBlockHub` / `serviceBlockApps` / `serviceBlockDirectory`, so it renders **inside the workspace shell**. Workspace-scoped (per active `gf_ws`). Renderers shared with the standalone `/applications` SEO page via `inc/gf_app_blocks.inc.php`; per-workspace app lists in `gf_workspace_apps`. |

### 2B. Upstream modules already installed — **extend, don't fork**

If your request is really "add to X," X probably already exists here. These are
UNA/BoonEx/third-party modules — treat them as **upstream** (§7): subclass or
hook rather than editing in place. Path is under `modules/`; `class_prefix` is
what you `grep` for to find a module's code.

**Profiles & spaces (data types — the model for any new profile-like type):**

| Module | Path | class_prefix | What it is |
|---|---|---|---|
| Persons | `boonex/persons` | `BxPersons` | Person profiles (the canonical data-type module) |
| Organizations | `boonex/organizations` | `BxOrgs` | Organization profiles |
| Groups | `boonex/groups` | `BxGroups` | Group profiles |
| Spaces | `boonex/spaces` | `BxSpaces` | Space profiles |
| Channels | `boonex/channels` | `BxCnl` | Channel profiles |
| Courses | `boonex/courses` | `BxCourses` | Course profiles (LMS) |
| Events | `boonex/events` | `BxEvents` | Event profiles |
| People | `smsoftwares/people` | `BxPeople` | 3rd-party people directory |

**Content:**

| Module | Path | class_prefix | What it is |
|---|---|---|---|
| Posts | `boonex/posts` | `BxPosts` | Blogging (canonical text-content module) |
| Files | `boonex/files` | `BxFiles` | File posts |
| Photos | `boonex/photos` | `BxPhotos` | Photo posting |
| Albums | `boonex/albums` | `BxAlbums` | Photo & video albums |
| Videos | `boonex/videos` | `BxVideos` | Video posting |
| Forum | `boonex/forum` | `BxForum` | Discussions |
| Polls | `boonex/polls` | `BxPolls` | Polls |
| Wiki | `boonex/wiki` | `BxWiki` | Wiki pages |
| Glossary | `boonex/glossary` | `BxGlsr` | Glossary |
| Timeline | `boonex/timeline` | `BxTimeline` | Activity timeline feed |
| Stream | `boonex/stream` | `BxStrm` | Live streaming |
| Ads | `boonex/ads` | `BxAds` | Classifieds / ads |
| Goal | `modzzz/goal` | `MzGoal` | Fundraising goals |
| Jobs | `modzzz/jobs` | `MzJobs` | Job listings |
| Business Listing | `modzzz/listing` | `MzListing` | Business directory listings |
| News | `modzzz/news` | `MzNews` | News |

**Commerce & payments:**

| Module | Path | class_prefix | What it is |
|---|---|---|---|
| Market | `boonex/market` | `BxMarket` | Marketplace / store |
| Payment | `boonex/payment` | `BxPayment` | Payment processing core |
| Credits | `boonex/credits` | `BxCredits` | Site credits/wallet |
| Donations | `boonex/donations` | `BxDonations` | Donations |
| Stripe Connect | `boonex/stripe_connect` | `BxStripeConnect` | Stripe Connect commerce |
| Shopify | `boonex/shopify` | `BxShopify` | Shopify integration |
| Snipcart | `boonex/snipcart` | `BxSnipcart` | Snipcart cart |
| Fans Only | `msolutions/fansonly` | `MsFansonly` | Paid subscriber content |
| Affiliate | `aqb/affiliate` | `AqbAffiliate` | Affiliate system |

**Members, access & messaging:**

| Module | Path | class_prefix | What it is |
|---|---|---|---|
| Accounts | `boonex/accounts` | `BxAccnt` | Accounts manager |
| Paid Levels | `boonex/acl` | `BxAcl` | Membership levels / access control |
| Invites | `boonex/invites` | `BxInv` | Invitations |
| Contact | `boonex/contact` | `BxContact` | Contact forms |
| Conversations | `boonex/convos` | `BxCnv` | Conversations |
| Messenger | `boonex/messenger` | `BxMessenger` | Real-time messenger |
| Notifications | `boonex/notifications` | `BxNtfs` | Notifications |
| Tasks | `boonex/tasks` | `BxTasks` | Tasks |
| Attendant | `boonex/attendant` | `BxAttendant` | Onboarding attendant/wizard |
| Complete Profile | `publicchat/complete_profile` | `PcCb` | Profile-completion nudges |
| Auto Friends | `aqb/auto_friends` | `AqbAf` | Auto-friend on join |

**Auth connectors** (all "connect via external account"):

| Module | Path | class_prefix |
|---|---|---|
| OAuth2 Server | `boonex/oauth2` | `BxOAuth` |
| Facebook | `boonex/facebook_connect` | `BxFaceBookConnect` |
| LinkedIn | `boonex/linkedin_connect` | `BxLinkedin` |
| UNA↔UNA | `boonex/una_connect` | `BxUnaCon` |
| Discord | `greenmeteor/discord_connect` | `GmDiscord` |

**API, integrations & infra:**

| Module | Path | class_prefix | What it is |
|---|---|---|---|
| API | `boonex/api` | `BxApi` | Backend API module |
| Nexus | `boonex/nexus` | `BxNexus` | Mobile/desktop app connector |
| RocketChat | `boonex/chat_plus` | `BxChatPlus` | RocketChat integration |
| ElasticSearch | `boonex/elasticsearch` | `BxEls` | Search backend |
| Analytics | `boonex/analytics` | `BxAnalytics` | Site analytics |
| Charts | `boonex/charts` | `BxCharts` | Charts |
| Google Tag Manager | `boonex/google_tagmanager` | `BxGoogleTagMan` | GTM tags |
| Marker.io | `boonex/markerio` | `BxMarkerIo` | Visual bug reporting |
| SMTP Mailer | `boonex/smtpmailer` | `BxSMTP` | Outbound SMTP |
| Antispam | `boonex/antispam` | `BxAntispam` | Antispam |
| Profiler | `boonex/profiler` | `BxProfiler` | Perf timing |
| Developer | `boonex/developer` | `BxDev` | Developer tools |
| Help Tours | `boonex/help_tours` | `BxHelpTours` | In-app guided tours |
| Advanced Menu | `aqb/advanced_menu` | `AqbAdvancedMenu` | Menu builder |
| Friendly URL | `aqb/seo_friendly` | `AqbSEOF` | SEO-friendly URLs |
| Locations Map | `aqb/locations_map` | `AqbLocationsMap` | Map of profiles |
| Auto Online | `aqb/autoonline` | `AqbAutoonline` | Keep members "online" |
| Personal Bookmarks | `aqb/personal_bookmarks` | `AqbPersonalBookmarks` | Bookmarks |
| Profile Message | `modzzz/message` | `MzMessage` | Banner message on profile |
| Font Awesome Pro | `boonex/fontawesome` | `BxFontAwesome` | Icon set |
| Plyr | `boonex/plyr` | `BxPlyr` | Media player |
| Quote of the Day | `boonex/quoteofday` | `BxQuoteOfDay` | QOTD widget |

**Design templates** (site theming): `boonex/protean` (`BxProtean`),
`boonex/lucid` (`BxLucid`), `boonex/artificer` (`BxArtificer`).
**Languages:** `boonex/english` (`BxEng`), `boonex/russian` (`BxRsn`).

> This table is a snapshot. Regenerate the authoritative list anytime:
> `find modules -path '*/install/config.php' -exec grep -H "'title'\|'class_prefix'\|'db_prefix'" {} +`

### 2C. GFunnel root feature endpoints (root `gf_*.php`)

| File | Does | | File | Does |
|---|---|---|---|---|
| `gf_auth.php` | login + create-account pages (renderer) | | `gf_applications.php` | **Application Hub** (thin dispatcher over `inc/gf_app_blocks.inc.php`): `/applications` (Apps), `/marketplace/applications` (Directory), `/application/<slug>` (detail), `?gfa_action=import\|admin` (admin sync + Manage-Apps settings, writes back to Supabase via `gf_app_config` secret). Skin: `template/css/gf_applications.css` + `template/js/gf_applications.js`. Also rendered in-shell by the `gfunnel_applications` module (§2A) |
| `gf_login.php` | `/login` → renders via `gf_auth.php` | | `gf_business.php` | Business **Directory** over `mz_listing`, `/business` |
| `gf_create_account.php` | `/create-account` → via `gf_auth.php` | | `gf_services.php` | **Services & Talent** hub (VAs/vendors), `/services` |
| `gf_onboarding.php` | post-signup onboarding (step 2) | | `gf_marketplace.php` | **Marketplace** over `bx_market`, `/marketplace` |
| `gf_bug.php` | bug-report endpoint | | `gf_resources.php` | **Resources** library (articles/guides), `/resources` |
| `gf_menu.php` | member menu personalization (hub tabs) | | `gf_timer.php` | time-tracking popup endpoint |

> These SEO landing routes (`/applications`, `/marketplace/applications`, `/business`,
> `/services`, `/marketplace`, `/resources`) are all
> dispatched from `r.php` and share the homepage skin (`template/css/gf_home.css`)
> + the shared section renderers in `inc/gf_home_blocks.inc.php`. Each has a
> `gf_<name>` sys_option kill-switch (`= off` disables it). The homepage itself is
> `home.php` (§1) → `template/page_home.html`, or a Studio Page-Builder composition
> when `gf_home_blocks_uri` is set (see `docs/gfunnel-home-blocks.md`).

---

## 3. Anatomy of a UNA module (what to clone)

New features belong in a module under `modules/gfunnel/`. Every module shares
this shape — **copy an existing one of the same shape (§2A) rather than
starting blank.**

```
modules/<vendor>/<module>/
├── install/
│   ├── config.php    ← MANIFEST — the source of truth (fields below)
│   └── installer.php ← install/uninstall hooks; install/langs/, install/sql
├── classes/          ← code, named <ClassPrefix><Role>.php:
│   │                     …Module.php (main logic / service* methods)
│   │                     …Db.php (all DB access)  …Config.php  …Template.php
│   │                     …FormEntry / …Grid… / …Menu… / …Page…  (UI pieces)
├── template/  js/     ← module HTML/images, front-end JS  (optional)
├── api/               ← HTTP/JSON endpoints (see onboarding_module)  (optional)
└── request.php        ← module entry point  (optional)
```

**Pick your base class by module shape.** Concrete modules extend an abstract
parent in `modules/base/*` (these have no manifest — they're pure superclasses).
Your new module's `…Module.php` / `…Config.php` should extend the base that
matches its shape — the same one the module you're cloning already extends:

| Your feature is a… | Extend base | Real example |
|---|---|---|
| Profile / space data type | `base/profile` → `BxBaseModProfile*` | `boonex/persons` |
| Group-like container | `base/groups` → `BxBaseModGroups*` | `boonex/groups` |
| Text/content post type | `base/text` → `BxBaseModText*` | `boonex/posts` |
| File/media post type | `base/files` → `BxBaseModFiles*` | `boonex/files` |
| Payment/commerce feature | `base/payment` → `BxBaseModPayment*` | `boonex/market` |
| External-account connector | `base/connect` → `BxBaseModConnect*` | `boonex/facebook_connect` |
| Notifications provider | `base/notifications` → `BxBaseModNotifications*` | `boonex/notifications` |
| Simple templated UI block / anything else | `base/general` or `base/template` | many |

**Manifest (`install/config.php`) fields you MUST make unique when cloning:**

| Field | Convention | Example |
|---|---|---|
| `name` | unique module id | `gfunnel_onb` |
| `vendor` | `GFunnel` for our modules | `GFunnel` |
| `home_dir` / `home_uri` | unique path & URL slug, no spaces | `gfunnel/onboarding_module/` / `gfunnel_onb` |
| `db_prefix` | prefix on **every** table | `gfo_` |
| `class_prefix` | prefix on **every** class | `BxGfunnelOnb` |
| `compatible_with` | keep `14.0.0` current | `['13.0.0','14.0.0']` |

Reference clones: **`modules/boonex/persons`** (full data-type feature),
**`modules/gfunnel/onboarding_module`** (feature + signed API),
**`modules/gfunnel/sitemap`** (cron + generated artifact).

---

## 4. API surfaces (five — pick the right one)

Detail: **`docs/api/API_CAPABILITIES.md`**, `docs/api/http-endpoints.md`.

| Surface | Entry point | Auth | Use when |
|---|---|---|---|
| **A. Service gateway** | `/api.php?r=module/method/class` | API key / allow-listed Origin | Server-to-server; call a module's `service*` methods — **the idiomatic new endpoint** |
| **B. OAuth2 server** | `/m/oauth2/*` (`modules/boonex/oauth2`) | OAuth2 bearer | Acting **as a user** |
| **C. Purpose HTTP endpoints** | root `*.php` (rss, oembed, search, storage) | mixed | Feeds, embeds, uploads |
| **D. GFunnel module API** | `modules/gfunnel/*/api/*.php` | HMAC-SHA256 token | GFunnel external integrations (onboarding precedent) |
| **E. AI / automation** | `agents.php`, `BxDolAI*`, cron | varies | Assistants, automators, webhooks |

---

## 5. Core framework classes (`inc/classes/`) — orientation

234 upstream `BxDol*` classes = the framework. **Read them to learn APIs;
extend via module subclasses; edit in place only as a last resort.** Families:

- **Data & auth:** `BxDolAccount*`, `BxDolProfile*`, `BxDolAcl*`, `BxDolDb*`
- **UI blocks:** `BxDolForm*`, `BxDolGrid*` (admin tables), `BxDolMenu*`, `BxDolPage*`, `BxDolTemplate*`
- **Content:** `BxDolCmts*` (comments), `BxDolVote*`, `BxDolScore*`, `BxDolCategories*`, `BxDolConnection*`
- **Infra:** `BxDolStorage*`, `BxDolCache*`, `BxDolQueue*`, `BxDolCron*`, `BxDolTranscoder*`, `BxDolAlerts` (**event hooks** — the clean way to react to core events without editing core)
- **AI:** `BxDolAI*` · **Comms:** `BxDolSms*`, `BxDolPush*`, `BxDolRss*`

Find behavior with `grep -ril "keyword" inc/classes/`.

---

## 6. Documentation (`docs/`) — read before related work

| Doc | Read when working on… |
|---|---|
| `docs/api/API_CAPABILITIES.md` (+ `http-endpoints`, `auth-and-oauth2`, `service-catalog`) | anything API |
| `docs/seo/SEO_PAGE_MAP.md` | any page's URL, meta, or sitemap |
| `docs/directory-sync-runbook.md`, `docs/directory-provisioning-target-model.md` | Supabase↔MySQL directory sync |
| `docs/directory-content-pipeline.md` | Monitors that enrich the directory (YouTube tutorials → `app_tutorials` → mirror) |
| `docs/organization-overview-block.md` | org overview UI block |
| `docs/persons-overview-block.md` | person profile overview block (`BxPersonsModule::serviceOverview()`) |
| `docs/workspace-overview-block.md` | workspace overview block for orgs/spaces/groups (`BxBaseModGroupsModule::serviceOverviewStructured()`) |
| `docs/audits/homepage-audit.md` | the home page |
| `docs/gfunnel-home-blocks.md`, `docs/homepage-module-mapping.md` | homepage sections, the GFunnel Home service blocks, and the section→module/data-source mapping |
| `docs/sql/*.sql` | DB schema (departments, directory apps, workspace invites) |

If your change alters behavior a doc describes, update that doc in the same commit.

---

## 7. Conventions & guardrails

- **Naming:** GFunnel code uses `gf_` / `Gf` / `BxGfunnel` and vendor `gfunnel`.
  Match the prefix of whatever you extend; keep a clone's prefixes internally consistent.
- **Secrets:** `inc/header.inc.php` (any `header.inc.php`) is gitignored — DB
  creds/keys. **Never commit it.** See `.gitignore` for the full never-commit list.
- **DB access** goes through a module's `*Db.php` class (or `BxDolDb`), never raw
  queries in page code.
- **Match the neighbors:** comment density, structure, idioms of the file you edit.
- **Upstream discipline:** editing `boonex/`, `base/`, `inc/classes/`, `plugins/`
  is overwritten on UNA upgrade — prefer a `gfunnel` module, a subclass, or a
  `BxDolAlerts` hook, and flag any unavoidable core edit in the commit message.

---

## 8. Keep this index true

When you **add/clone/rename a module, add a root page or API surface, or add a
docs file, update the matching section here in the same change.** A stale entry
is a bug — it will send the next session to the wrong place.
