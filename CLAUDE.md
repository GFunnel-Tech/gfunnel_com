# CLAUDE.md — gfunnel.com

Guidance for Claude sessions working on this repository.

## What this is

This repo is the deployed codebase of **gfunnel.com**, a community site built on
**UNA CMS** (unacms.com), hosted on Hostinger. The active theme/template is
**Artificer** (`modules/boonex/artificer/`); pages render through the base page
shell `template/_header.html` / `template/_footer.html`.

GFunnel also runs a separate app at **www.gfunnel.com** (a HighLevel/LeadConnector
white-label). That app is NOT in this repo — only the UNA community site is.

## CRITICAL: code vs. database

UNA stores almost all site *content and configuration* in its **MySQL database**,
not in these files. This repo contains only the platform code. Things that live in
the database and CANNOT be found or edited in this repo:

| Content | Where to edit it (UNA Studio, at gfunnel.com/studio) |
|---|---|
| Page content & blocks (HTML blocks, embeds, third-party scripts pasted into pages) | **Studio → Pages → [select page] → blocks**. Example: the homepage has an HTML block named **"AI Assistant"** which contained a ClosedGPT chat widget loader script (`*.apiii.co`). |
| Head/body/footer script & CSS injections | **Studio → Designer → Injections**. These fill the `<bx_injection:injection_head />` etc. placeholders in `template/_header.html` / `template/_footer.html`. Injections have per-visibility settings, so a snippet may render for logged-in members only. |
| Custom site-wide CSS | **Studio → Designer → Styles → Custom Styles** (Artificer template). |
| Site settings, module settings (e.g. Google Tag Manager container ID) | **Studio → [module]** (GTM ID is a setting of the `google_tagmanager` module). |
| Menus, forms, permissions, languages | Studio's respective sections. |

**Implication:** if asked to find/remove/change something visible on the site and it
is not in this repo's files, it is almost certainly in the database via one of the
Studio locations above. Grep the repo first, but don't conclude it doesn't exist —
check live page HTML (`curl https://gfunnel.com/...`) and remember that member-only
injections/blocks do not appear in logged-out HTML.

## Access available to Claude sessions

- Claude sessions have the **repo only** — no database credentials
  (`inc/header.inc.php` holds DB config and is gitignored, never committed),
  no Studio login, no server SSH.
- Editing database-stored content requires the user to either do it in Studio,
  or provide a (temporary) Studio admin login / UNA REST API credentials
  (`modules/boonex/api`) / DB access.

## Deployment & cache

- Pushing to GitHub does **not** auto-deploy. The Hostinger server must pull the code.
- UNA caches compiled templates and CSS/JS bundles in `cache/` and `cache_public/`
  (gitignored). After ANY template or content change, clear the cache:
  **Studio → Dashboard → (gear) → Clear cache**, then hard-refresh the browser.

## Chat widget history (July 2026)

The site had two third-party chat widgets:

1. **LeadConnector (HighLevel) chat widget** — `<chat-widget>` element /
   `.lc_text-widget` class, loader from `widgets.leadconnectorhq.com` /
   `msgsndr.com`. Was hidden with CSS via a Designer injection rather than removed.
2. **ClosedGPT AI chatbot ("Emma")** — loader script from
   `https://<hash>.apiii.co/api/widget/<id>`, pasted into the homepage's
   **"AI Assistant"** HTML block (Studio → Pages → Homepage). Rendered for
   logged-in members.

A defense-in-depth blocker was added at the top of `template/_header.html`
(branch `claude/remove-chat-widget-6bsnpr`, PR #1): it blocks script loads from
`apiii.co`, `leadconnectorhq.com`, `msgsndr.com/.net` (via an
`HTMLScriptElement.src` setter intercept + MutationObserver) and removes/hides
the widgets' markup. The authoritative removal is still deleting the snippets in
Studio (the "AI Assistant" homepage block and any related Designer injections).
