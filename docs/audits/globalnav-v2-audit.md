# GlobalNav v2 — Step 0 Audit (PHP platform)

> **Provenance.** The driving spec (`PR152 — GlobalNav v2`) was written against a
> **Next.js / React App Router** repo (`src/components/shell/*.tsx`, Radix,
> `@supabase/ssr`, Tailwind, Lucide, cmdk). **That repo is not this codebase.**
> This codebase is the GFunnel PHP platform. This audit therefore does two
> things at once: it answers every line of the Step 0 template, and it maps each
> React concept onto the PHP surface that already implements it. Where the spec
> says "build X," the honest finding is usually "X already exists here — extend
> it." Nothing below is invented; every path was read.

## TL;DR — the spec's premise is already largely built here

| PR152 assumes it must build… | In this repo it already exists as… |
|---|---|
| `src/components/shell/GlobalNav.tsx` | `getGfToolbar()` in `template/scripts/BxBaseFunctions.php` → `template/_page_toolbar_auth.html` |
| A `NavPopover` primitive + provider | The platform's native **menu-preview popover** (`BxTemplMenuCustom` / `menu_preview.html`) + `$.dolPopup` popup engine |
| A command palette (`⌘K`) | Already wired: `bx_site_search_show()` + `Cmd/Ctrl+K` handler in `template/_page_toolbar_auth_js.html` |
| Notifications *migrated from slide-over → popover* | Already a popover: `BxNtfsMenuPreview extends BxTemplMenuCustom`, rendered in `sys_toolbar_member` |
| Supabase panel tables (mostly missing in the React repo) | Mostly **present**: `gf_time_entries`, `bx_messenger`, news module, `gf_bug.php`, `agents.php`, notifications module |

The React spec's hardest problems (no popover primitive, missing tables, no
command palette) are **already solved** on this platform. The real work here is
a **layout/interaction refactor of one existing template + its driver + CSS/JS**,
not a from-scratch build.

---

## STEP 0 REPORT

```
GlobalNav.tsx        : template/_page_toolbar_auth.html, driven by
                       getGfToolbar() @ template/scripts/BxBaseFunctions.php:115.
                       Left-to-right order today:
                         [mobile hamburger .gf-hamburger] [sidebar togglers
                         #toggleSidebar / #toggleSidebarMobile (app layout only)]
                         [logo #bx-logo-container] [search pill ⌘K]
                         [timer pill] [What's New <a>] [Bug button] [AI <a>]
                         [Messages <a> + badge] [member menu: notifications+account]

Collapse control     : THREE coupled pieces —
                         1. Markup: bx_if:app_togglers block, buttons
                            #toggleSidebar + #toggleSidebarMobile, and the
                            mobile .gf-hamburger block
                            — template/_page_toolbar_auth.html:10-29
                         2. Driver: $bApp param + 'bx_if:app_togglers' condition
                            — BxBaseFunctions.php:115, 169-172; set true only by
                            the 'gf_toolbar_app' case (BxBaseFunctions.php:98-99)
                         3. JS: toggler show/hide logic
                            — template/_page_toolbar_auth_js.html:79-85
                         4. CSS: .gf-sidebar-toggler / #toggleSidebar* /
                            .gf-hamburger rules — template/css/gf_header.css:62-131

Popover primitive    : EXISTS (native). Two layers:
                         - Menu-preview popover: BxTemplMenuCustom +
                           template/menu_preview.html (+ menu_interactive*.html) —
                           this is what the bell/account already use.
                         - Popup engine: $.dolPopup / dolPopupAjax (jQuery.dolPopup)
                           for larger anchored/centered panels.
                       Decision: WRAP these. Do NOT port Radix. A thin
                       gf_navpopover.js helper standardises trigger a11y
                       (aria-haspopup/expanded), outside-click, Escape→focus
                       return, and one-open-at-a-time across the cluster.

Command palette      : EXISTS. bx_site_search_show(pill) opens the search box;
                       Cmd/Ctrl+K bound @ _page_toolbar_auth_js.html:87-98;
                       pill markup + kbd hint @ _page_toolbar_auth.html:39-48.
                       Backed by BxTemplSearch live search (case 'sys_toolbar_search'
                       @ BxBaseFunctions.php:77-84). Footer/route target: search page.

Notifications        : popover (native menu-preview). BxNtfsMenuPreview extends
                       BxTemplMenuCustom @ modules/boonex/notifications/classes/.
                       Rendered inside the sys_toolbar_member menu
                       (bx_menu:sys_toolbar_member @ _page_toolbar_auth.html:91).
                       Mark-as-read via BxNtfsDb last_read; data in BxNtfsDb.
                       NOTE: there is NO slide-over here. PR152's "migrate slide-over
                       → popover" is a no-op on this platform — the container is
                       already a popover. Work = footer link + shared chrome parity.

Badge counts         : Messages — BxDolService::call('bx_messenger','get_unread_lots',…)
                         counted in getGfToolbar() @ BxBaseFunctions.php:139-144,
                         rendered as .gf-hdr-badge (_page_toolbar_auth.html:85).
                       Notifications — native unread from BxNtfsDb (last_read) via
                         the menu-preview object.

PANEL DATA SOURCES
Timer      : gf_time_entries (MySQL, created lazily by gf_timer.php). Boot state
             embedded via getGfTimerBoot() @ BxBaseFunctions.php:257-296. Endpoint
             gf_timer.php (start/stop/list). PRESENT (conditional table).
What's New : news module (modzzz/news → mz_news); default URL page.php?i=news-home
             (getParam gf_header_whats_new_url) @ BxBaseFunctions.php:134. PRESENT.
Report     : gf_bug.php endpoint (already a launcher button + gf_bug.js panel);
             external Komodo referral (gf_bug_komodo_url). PRESENT — do NOT invent
             an API client; reuse gf_bug.php.
AI         : agents.php (BxDolAI*); default gf_header_ai_url = agents.php
             @ BxBaseFunctions.php:135. PRESENT (route). No per-user "recent
             sessions" table confirmed → RECENT SESSIONS group = empty state
             unless a source is found in the AI wave.
Messages   : bx_messenger module (get_unread_lots service; page.php?i=messenger).
             PRESENT.
Bell       : boonex/notifications module (BxNtfsDb / BxNtfsMenuPreview). PRESENT.

BLOCKERS
  B1 (scope/telemetry, not a stopper): PR152 targets React files that do not
     exist here. Implemented as a PHP refactor of the existing authenticated
     chrome. All acceptance criteria re-expressed in PHP terms (below).
  B2 (shared-chrome risk): the file being refactored (_page_toolbar_auth.html /
     getGfToolbar) wraps EVERY authenticated page, across the app / protean /
     lucid / workspace-picker layouts. Changes must stay layout-agnostic and be
     verified with `php -l` + a grep sweep, since there is no `npm run build`.
  B3 (D1 behavior change): removing the sidebar togglers means the app-layout
     #sidebar is always expanded. This is the locked decision D1; it changes
     live behavior on the application layout. Confirmed intended.
  B4: "GitScrum intake" (Report panel footer) has no client in this repo. Per
     spec, wire intake to the existing gf_bug.php flow and note it — do not
     fabricate a GitScrum client.
```

---

## AC translation: React → PHP

No `npm run build` exists. Build/lint AC is re-expressed as:

- **`npm run build` clean** → `php -l` clean on every touched `.php`; template
  compiler parses (`bx_if`/`bx_repeat`/`bx_menu` well-formed); browser console
  clean.
- **Zero `any`, Lucide-only, Tailwind-only, `@supabase/ssr`** → not applicable
  to this stack. The equivalent house rules here: DB access through `*Db.php` /
  `BxDolDb`; inline SVG icons matching the set already in `_page_toolbar_auth.html`;
  styling in `template/css/gf_header.css` (no new inline `style=` beyond the
  dynamic width/offset a popover genuinely needs); platform escaping
  (`bx_html_attribute`, template auto-escape) — no raw echo of user data.
- **One panel open at a time / outside-click / Escape / focus-trap / accessible
  name** → owned by the shared `gf_navpopover.js` helper wrapping `$.dolPopup`,
  applied uniformly to every cluster control.
- **No fabricated data; empty states** → every panel renders an honest empty
  state when its source is absent or empty (esp. AI recent-sessions, and Timer
  when `gf_time_entries` does not yet exist).

---

## Files in scope (the refactor surface)

| File | Role | Change |
|---|---|---|
| `template/_page_toolbar_auth.html` | GlobalNav markup | logo-left; delete collapse/hamburger; reorder right cluster; divider; convert What's New / AI / Messages triggers to popovers; panel mount points |
| `template/_page_toolbar_auth_js.html` | header JS | delete toggler show/hide (79-85); add nothing that re-introduces a collapse control |
| `template/scripts/BxBaseFunctions.php` | driver | remove `$bApp`/`app_togglers` plumbing (115, 169-172); keep the 'gf_toolbar_app' case pointing at the classic app template unchanged, but stop emitting togglers; feed the new panels |
| `template/css/gf_header.css` | styling | logo-left flex; divider `w-px h-[22px]`; icon-button spec parity; delete `.gf-sidebar-toggler` / `.gf-hamburger` collapse rules; panel chrome |
| `template/js/gf_navpopover.js` *(new)* | primitive | thin wrapper over `$.dolPopup` giving a11y + one-open-at-a-time |
| Panels | reuse | Timer (`gf_timer.js`/`gf_timer.php`), Bug (`gf_bug.js`/`gf_bug.php`), Notifications (`BxNtfsMenuPreview`), Messages (`bx_messenger`), What's New (news), AI (`agents.php`) |

## Out of scope (unchanged from PR152)

- The subheader / hub tabs (`_page_toolbar_auth_subheader.html`, `gf_menu.php`) —
  that is SecondaryNav, a separate PR.
- Left sidebar **contents**.
- Building `/time`, `/communications`, `/memory`, `/notifications`, `/search`
  destinations — only link to them.
- Focus mode (not present in this chrome; do not add).
