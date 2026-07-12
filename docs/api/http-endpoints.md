# UNA CMS 14.0.0 — Root-Level HTTP Entry Points (gfunnel.com)

**Bootstrap pattern:** nearly every file does `require_once('./inc/header.inc.php')` (framework init) then optionally `design.inc.php`. `check_logged()` = requires a logged-in member session (AJAX-friendly); `bx_require_authentication()` = redirects to login; no call = public. Most are thin dispatchers that instantiate a `BxDol*` object from a `?o=`/`?object=`/`?sys=` param and invoke `action<Name>()` / `performAction<Name>()` reflectively.

| File:line | Purpose | Key inputs | Auth | Response |
|---|---|---|---|---|
| agents.php:15-51 | **AI providers/assistants dispatcher** (`BxDolAIProvider`, `BxDolAIAssistant`). NOTE line 34-36 has a hardcoded Shopify products call `products/7433953116300.json` | GET `t`(tool,'asst'), `p`(providerId), `a`(action), `id` | public (no check_logged) | varies / print_r |
| cart.php:15-26 | Redirect to payments cart URL | GET `seller_id` | member | 302 redirect |
| chart.php:17-32 | `BxDolChart` AJAX action dispatcher | `object`, `action` | member | JSON |
| cmts.php:17-37 | Comments (`BxDolCmts`) actions / view page | `sys`, `id`, `action`, `cmt_id` | member | HTML |
| conn.php:15-32 | **Connections** (follow/friend) `BxDolConnection` actions | GET `obj`, `act`, `fmt`(json default) | member | JSON/HTML (fmt) |
| em.php:11-16 | **oEmbed-style embed data** for a URL (`BxDolPage::getEmbedData`) | GET `url` | public | JSON (rich embed) |
| embed.php:14-19 | Get embed HTML for a link (`BxDolEmbed`) | GET `a=get_link`, `l`(link) | member | JSON |
| favorite.php:17-28 | Favorites (`BxDolFavorite`) actions | `sys`, `object_id`, `action` | member | HTML |
| feature.php:17-28 | Feature/promote (`BxDolFeature`) actions | `sys`, `object_id`, `action` | member | HTML |
| form.php:16-27 | Generic form action dispatcher (`BxTemplFormView`) | GET `o`, `d`(display), `a` | public | HTML/JSON |
| **get_rss_feed.php:13-28** | **RSS feed generator** (`BxDolRss`) — programmatic | GET `object`, `id`, `member` | public | **XML/RSS** |
| grid.php:16-41 | Admin/data grid AJAX (`BxDolGrid`), CSRF-checked | GET `o`, `a`, `csrf_token` | public | JSON |
| gzip_loader.php:12-13 | Serves gzipped CSS/JS bundles (`BxDolGzip`) | GET `file` | public | gzip binary |
| image_transcoder.php | Image transcode+redirect (`BxDolTranscoderImage`) | GET `o`(object), `h`(handler), `dpx` | public | 302 to image |
| invoices.php:17-22 | Redirect to payments invoices URL | — | member | 302 |
| label.php:19-25 | Labels/tags (`BxDolLabel`) actions | `action` | member | HTML |
| **live_updates.php:13-19** | **Live updates poller** (`BxDolLiveUpdates::perform`) — programmatic | POST body | public | JSON |
| logout.php | Logs out member session, transition page | cookies | member | HTML redirect |
| **manifest.json.php:14-89** | **PWA manifest** | — | public | JSON |
| member.php:15-88 | **Login handler** + member landing. AJAX/2FA aware | POST `ID`,`passwd`,`role`,`relocate`,`rememberMe` | public→member | HTML/JSON |
| menu.php:16-37 | Menu render/actions (`BxDolMenu`) | GET `o`,`a`,`i`,`v` | public | HTML |
| **oembed.php:13-23** | **oEmbed endpoint** (`sys_oembed`) — programmatic | GET `l`(links), `html` | public | JSON/HTML |
| orders.php:17-21 | Redirect to payments orders URL | — | member | 302 |
| page.php:17-39 | **Core page renderer** + page action dispatcher | GET `i`(page uri), `o`,`a` | public | HTML |
| privacy.php:17-27 | Privacy object (`BxDolPrivacy`) actions | `object`, `action` | member | HTML |
| r.php:17-32 | **URL rewrite/permalink router** (`_q` path → service/SEO) | GET `_q` | member (check_logged) | HTML/varies |
| recommendation.php:15-29 | Recommendations (`BxDolRecommendation`) | GET `obj`,`act`,`fmt` | member | JSON/HTML |
| report.php:17-28 | Content reporting (`BxDolReport`) | `sys`,`object_id`,`action` | member | HTML |
| score.php:17-28 | Scoring/rating (`BxDolScore`) | `sys`,`id`,`action` | member | HTML |
| **searchExtended.php:17-46** | Advanced search (`BxDolSearchExtended`) actions | `object`,`action` | member | JSON |
| **searchKeyword.php:13-14** | Wrapper → sets `i=search-keyword`, includes page.php | GET (keyword etc.) | member | HTML |
| **searchKeywordContent.php:13-38** | **Keyword search results** (`BxDolSearch`/Elasticsearch `BxElsSearch`) — programmatic | GET `keyword`,`section`,`type`,`cat`,`live_search` | public | HTML fragment |
| splash.php:16-51 | Splash/landing page (join+login forms) | — | public (redirects if logged) | HTML |
| **storage.php:12-114** | **File download + upload endpoint** (`BxDolStorage`) — programmatic | GET `o`,`f`,`t`(token); `a=upload`+`$_FILES['file']` | mixed (upload needs profile) | binary/302/JSON |
| **storage_uploader.php:12-77** | **AJAX uploader** (`BxDolUploader`): forms, ghosts, reorder, delete, upload | GET `uo`,`so`,`uid`,`a`,`c`,`m`,`l`,`p`; `$_FILES['f']` | member (profile) | HTML/JSON |
| subscriptions.php:17-22 | Redirect to payments subscriptions URL | — | member | 302 |
| **sw.js.php:10-142** | **PWA service worker** JS generator | — | public | text/javascript |
| view.php:17-28 | View counter (`BxDolView`) actions | `sys`,`id`,`action` | member | HTML |
| vote.php:17-28 | Voting/likes (`BxDolVote`) actions | `sys`,`id`,`action` | member | HTML |
| index.php:10-36 | **Front controller** → splash or `page.php?i=home` | — | public | HTML |
| default.php | **Hostinger hosting placeholder** (NOT UNA code) — static HTML "account created" | — | public | HTML |

### Directories
- **timeline/request.php:17** — Timeline module request router (`BxDolRequest::processAsAction`). Reached via URL-rewrite to the module; renders/acts on timeline items. public.
- **market/request.php:16** — Market (digital goods) module request router (`BxBaseModTextRequest::processAsAction`). `check_logged()`.
- **periodic/cron.php** — **CRON runner** (defines `BX_DOL_CRON_EXECUTE`). Executes scheduled jobs + AI automators/schedulers (`BxDolAI::callAutomator`). Should be CLI/protected by `.htaccess` (present). Not a normal HTTP API.
- **studio/** — **UNA Studio = the admin panel.** ~30 PHP entry points (launcher.php, module.php, builder_*.php, designer.php, **agents.php** & **api.php** = admin UIs for AI agents and API/webhook config, audit.php, etc.). All call `bx_require_authentication(true)` → require **admin** login. HTML admin pages.
- **artificer/** — Theme/template module ("Artificer" default UNA template): only `classes/`, `template/`, `js/`, `install/` — no root HTTP entry point.
- **ampliphi/** — contains only `default.php` = **Hostinger placeholder HTML** (not functional).
- **helpdocs/** — contains only `default.php` = **Hostinger placeholder HTML** (not functional).
- **custom_batch_updates/aff_custom_batch_update.php** — **Custom site-specific CLI batch script** (not stock UNA). Hardcoded abs path `/home/u831969491/domains/gfunnel.com/public_html/inc/header.inc.php`; backfills `sys_accounts.MemberID` from `bx_srv('aqb_affiliate','get_referral_code')` (affiliate `am_id` token). Also an `error_log` file present. No auth guard — dangerous if web-accessible.

### AI agents (agents.php special note)
`agents.php` is the **public-facing AI dispatcher** (no `check_logged()`), driving `BxDolAIProvider` and `BxDolAIAssistant`. Line 34-36 unconditionally makes a live Shopify Admin API call (`$oProvider->call('products/<id>.json', ...)`) and `print_r`s the result when no action given — likely leftover debug/test code. `studio/agents.php` is the admin config UI; automators are invoked from cron (`periodic/cron.php` lines 162-172 via `BxDolAI::callAutomator`).

### Webhook receivers
No root-level webhook receiver exists. Payment/webhook ingestion is routed **through the module dispatcher**, not a root script:
- **`BxPaymentModule::actionNotify($sProvider, $mixedVendorId)`** — `modules/boonex/payment/classes/BxPaymentModule.php:1186-1192` → calls `$oProvider->notify()`. This is the generic payment IPN/webhook handler (Stripe, PayPal, Chargebee, Apple In-App). Reached via a module action URL (e.g. `.../m/bx_payment/notify/<provider>/<vendor>`), which resolves through `page.php`/module routing rather than a dedicated root file.
- `BxPaymentProviderStripeBasic::notify()` (:94-98) → `_processEvent()` then `http_response_code()`.
- `modules/boonex/stripe_connect/classes/BxStripeConnectModule.php:441` logs Stripe `Webhooks:` events (Stripe Connect handler).
- Provider webhook classes also in `BxPaymentProviderPayPalApi.php`, `BxPaymentProviderChargebee.php`, `BxPaymentProviderAppleInApp.php`. (No Shopify/Snipcart webhook receiver found; Shopify appears only as an outbound API call in `agents.php`.)

### Most useful as programmatic APIs
`get_rss_feed.php` (RSS/XML), `oembed.php` + `em.php` (oEmbed JSON), `searchKeywordContent.php` / `searchExtended.php` (search JSON/HTML), `live_updates.php` (JSON poller), `storage.php` + `storage_uploader.php` (file up/download JSON), `manifest.json.php` (PWA JSON), `sw.js.php` (service worker). The richest general-purpose REST/JSON API is `api.php`.

**Security flags worth noting:** `agents.php` runs a live Shopify API call with no auth and dumps output; `custom_batch_updates/aff_custom_batch_update.php` has no auth guard and a committed `error_log`; `r.php`/`grid.php` reflectively dispatch methods (grid is CSRF-guarded, most action dispatchers rely on regex-filtered action names + `method_exists`).
