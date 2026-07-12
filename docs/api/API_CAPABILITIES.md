# GFunnel API Capabilities — Full Analysis

> Platform: **UNA CMS 14.0.0** (`inc/version.inc.php`) with BoonEx, GFunnel, and third‑party apps.
> Scope: every programmatic capability the site exposes, how it is authenticated, and what it means for completing our API docs and building an MCP server.
> Audience: engineers writing the public API reference and/or an MCP integration.

---

## 1. Executive summary

GFunnel runs on UNA, which ships **two distinct, independently‑authenticated API surfaces**, plus a set of purpose‑built HTTP endpoints and one custom GFunnel integration:

| # | Surface | Entry point | Auth | Format | Best for |
|---|---------|-------------|------|--------|----------|
| **A** | **Service gateway** | `/api.php?r=module/method/class` | API key (Bearer) **or** allow‑listed Origin | JSON | Server‑to‑server automation, the decoupled front‑end |
| **B** | **OAuth2 server** | `/m/oauth2/{token,auth,revoke,api/*,com/*}` | OAuth2 (opaque bearer tokens) | JSON | Acting **as a user** — login, profile, friends, per‑user service calls |
| **C** | **Purpose HTTP endpoints** | root `*.php` (RSS, oEmbed, search, storage, live updates…) | mixed (public / member session) | XML / JSON / binary | Feeds, embeds, uploads, search widgets |
| **D** | **GFunnel onboarding API** | `modules/gfunnel/onboarding_module/api/*.php` | HMAC‑SHA256 signed token | JSON | The existing external‑integration precedent (onboarding.gfunnel.com) |
| **E** | **Core AI / automation** | `agents.php`, `BxDolAI*` + cron | varies | varies | Assistants, automators, webhooks, scheduler |

**Key facts for docs & MCP:**
- The service gateway (A) is the richest surface: **~2,000 `service*` methods exist**, but only a **few hundred are callable by default** — those declared "safe" or "public" per module. A single admin flag (`sys_api_access_unsafe_services`) unlocks *all* of them.
- The OAuth2 server (B) is the only surface that authenticates **as a specific user/profile** and is the right foundation for user‑scoped MCP tools.
- UNA already carries **Doxygen‑style API doc annotations** (`@page` / `@section`) inside the OAuth2 classes — our public docs can be generated/seeded from those rather than written from scratch.
- **Security flags to resolve before publishing docs** are listed in §8.

---

## 2. Surface A — the service gateway (`/api.php`)

**File:** `api.php` (107 lines). **Router:** `?r=<module>/<method>[/<class>]`, params via `?params=[...]` (JSON array) or repeated `params`.

### 2.1 Request flow
1. `bx_api_check_access()` — gate (see §4).
2. Parse `r` into `sModule`, `sMethod`, `sClass` (class defaults to `Module`; only `[A-Za-z0-9_-]` allowed).
3. `BxDolRequest::serviceExists(...)` — the method must exist.
4. **Safe/public gate** (`api.php:53‑70`): unless `sys_api_access_unsafe_services` is on, the method is callable only if `is_safe_service()` **or** `is_public_service()` returns true; otherwise `403`.
5. `BxDolService::call($sModule, $sMethod, $aParams, $sClass)` → result serialized as JSON.

### 2.2 Naming convention
A request method `foo_bar` maps to PHP `serviceFooBar` (via `bx_gen_method_name`) on class `{class_prefix}{sClass}`. Special case: `sModule=system` + `Module` is forced to the `BaseServices` class (`BxBaseServices` in `template/scripts/`).

### 2.3 Config endpoint
`GET /api.php?cnf=1` → `{ "data": <sys_api_config>, "hash": md5(...) }`. The `hash` is echoed on every response so clients can detect config drift.

### 2.4 Response envelope
```json
{ "status": 200, "module": "system", "method": "get_menu",
  "params": [...], "data": <result>, "hash": "<md5 of sys_api_config>" }
```
Errors return `status` + `error` (+ optional `code`, `data`) with the matching HTTP status.

### 2.5 What's callable by default (the "safe/public" allow‑lists)
The gate is implemented in `inc/classes/BxDolModule.php:169‑191` (mirrored in `template/scripts/BxBaseServices.php`). Each module overrides `serviceGetSafeServices()` / `serviceGetPublicServices()` to opt methods in. Highlights (full inventory in `docs/api/service-catalog.md`):

- **system / core (`BxBaseServices`)** — the most sensitive surface: account create/settings/delete, `login_form`, `logout`, `member_auth_code`, `switch_profile`, `forgot_password`, `confirm_email`, menus, forms, `keyword_search`, profile browse/befriend, charts, cart/order counts, vote/favorite/report `perform`. Public (logged‑out): `get_products_names`, `get_page_by_request`.
- **base content modules** (`BxBaseModGeneral/Text/Profile/Groups`) inherit a content CRUD list: `entity_create/edit/delete`, `browse*`, `entity_info*`, `get_create_post_form`, `update_image`, `get_profiles`, etc. Any module extending these (most boonex + modzzz + fansonly + aqb) inherits it.
- **`bx_api`** adds logged‑out `create_account`, `reset_password_send_request/check_code`, `test`, `get_page`; safe `delete_page`, `change_account_password`, `switch_profile`.
- **`bx_payment`** — checkout: `initialize_checkout_api`, `stripe_v3_create_session_api`, cart/subscription blocks.
- **`bx_messenger`** — DMs: `get_convos_list`, `get_convo_messages`, `get_send_form`, `find/leave/delete_convo`, `search_users`.
- **`bx_timeline`** — feed: `get_posts`, post/view blocks, repost element.
- **`bx_notifications`, bx_invites, bx_market, bx_albums, bx_elasticsearch, bx_forum, bx_courses, bx_wiki, modzzz/*** — see catalog.
- **Modules extending `BxDolModule` directly** (`smsoftwares/people`, `gfunnel/onboarding_module`, `publicchat/complete_profile`) inherit an **empty** safe list → their `service*` methods are **not** callable unless the unsafe flag is on.

### 2.6 The `Api`‑suffix (decoupled front‑end) methods
UNA 14's React/native front‑end calls the *same* gateway using methods with an **`Api` suffix** (not a prefix): e.g. `system/get_data_search_api`, grid `perfom_action_api`, `bx_payment` `initialize_checkout_api` / `stripe_v3_create_session_api`, `bx_timeline` `get_repost_element_block_api`. They branch on `bx_is_api()` and return `bx_api_get_block(...)` structures. `system/get_page` renders a whole page as JSON.

---

## 3. Surface B — the OAuth2 server (`/m/oauth2/`)

**Module:** `modules/boonex/oauth2` (`bx_oauth`, v14.0.1, URI `oauth2`, uses the bshaffer OAuth2 library). **`bx_api` depends on it.** This is the only surface that authenticates **as a user/profile**.

### 3.1 Endpoints (verified from `BxOAuthModule` + `BxOAuthAPI`)
| Route | Handler | Purpose |
|-------|---------|---------|
| `POST /m/oauth2/token` | `actionToken` | Issue access/refresh tokens |
| `GET /m/oauth2/auth` | `actionAuth` | Authorization‑code consent screen |
| `POST /m/oauth2/revoke` | `actionRevoke` | Revoke a token |
| `GET /m/oauth2/api/me` | `BxOAuthAPI::me` | Current profile |
| `GET /m/oauth2/api/user?id=` | `user` | Another profile (access‑checked) |
| `GET /m/oauth2/api/friends` | `friends` | Friends list |
| `GET /m/oauth2/api/market` | `market` | Market data |
| `.../api/service` | `service` | Call any **safe** service as the token's user |
| `.../api/com/<method>` | `com` | Call a **public** `bx_api` service (logged‑out capable) |

### 3.2 Scopes (`BxOAuthAPI::aAction2Scope`)
`me/user/friends` → `basic,market,service,api`; `service` → `service`; `market` → `market`; `api` → `api`.

### 3.3 Token model
- Grant types are per‑client: `client_credentials`, `authorization_code`, `password`, `refresh_token`.
- Tokens are **opaque DB strings, not JWT** (the JWT tables are intentionally blank).
- An access token's `user_id` is a **profile id**; a `service`/`com` call performs `bx_login()` so the service runs authenticated as that user.
- Clients live in `bx_oauth_clients` (auto‑generated `client_id`/`client_secret`); per‑client CORS via `bx_oauth_allowed_origins`.
- The `service`/`com` calls reuse the **same safe/public allow‑list** as Surface A (`BxOAuthAPI` calls `is_public_service`/`is_safe_service`).

### 3.4 Built‑in docs
`BxOAuthAPI.php` and `BxOAuthModule.php` contain `@page`/`@section` Doxygen blocks with request headers, sample responses, and scope tables for every endpoint above — **the seed for our public API reference.**

---

## 4. Authentication & configuration

### 4.1 `bx_api_check_access()` (`inc/utils.inc.php:2568`)
1. Requires `sys_api_enable` (else `403`).
2. **API‑key mode:** if an `Authorization` header is present **and** `sys_api_access_by_key` is on → `BxDolApiQuery::getKey(<key>)` must match a row in `sys_api_keys` (strips `Bearer `). With key mode + `sys_api_access_unsafe_services`, `?r=q&q=<sql>` hits a **raw‑SQL passthrough** (`bx_api_get_sql`).
3. **Origin mode:** if `sys_api_access_by_origin` is on and an `Origin` header is present and cross‑host → must match `sys_api_origins`; emits `Access-Control-Allow-Origin` + `Allow-Credentials`, and answers `OPTIONS` preflight.
4. Same‑origin requests bypass both checks.
5. **Note:** the logged‑state block is commented out (`utils.inc.php:2631‑2633`) — the gateway currently runs anonymous; user context comes only from Surface B.

### 4.2 Relevant system settings (`sys_api_*`)
`sys_api_enable`, `sys_api_access_by_key`, `sys_api_access_by_origin`, `sys_api_access_unsafe_services`, `sys_api_config` (served by `?cnf=1`), `sys_api_url_root_email`, `sys_api_url_root_push`, `sys_api_cookie_*`. OAuth2 adds `bx_oauth2_refresh_token_lifetime` and friends. Backing lookups: `inc/classes/BxDolApiQuery.php` (`sys_api_keys`, `sys_api_origins`).

### 4.3 CORS
Implemented **twice, independently** — the gateway via `sys_api_origins`, OAuth2 via `bx_oauth_allowed_origins`. Same‑origin bypasses both.

### 4.4 "Connect" modules are OAuth2 *clients*, not API guards
`una_connect` (login via a remote UNA `/m/oauth2/`), `facebook_connect` (FB Graph), `linkedin_connect` (LinkedIn OAuth2), `greenmeteor/discord_connect` (Discord), `stripe_connect` (payments) — these let users log **into** the site; they don't secure our API.

---

## 5. Surface C — purpose‑built HTTP endpoints
Full table in `docs/api/http-endpoints.md`. The ones useful as a programmatic API:

| Endpoint | Purpose | Auth | Format |
|----------|---------|------|--------|
| `get_rss_feed.php?object=&id=&member=` | RSS feed generation | public | XML/RSS |
| `oembed.php?l=&html=` / `em.php?url=` | oEmbed / rich embed data | public | JSON |
| `searchKeywordContent.php?keyword=&section=&type=` | Keyword search (Elasticsearch‑backed) | public | HTML fragment |
| `searchExtended.php?object=&action=` | Advanced search actions | member | JSON |
| `live_updates.php` | Long‑poll live updates | public | JSON |
| `storage.php` / `storage_uploader.php` | File download / AJAX upload | mixed / member | binary / JSON |
| `conn.php?obj=&act=&fmt=json` | Follow/friend connections | member | JSON |
| `recommendation.php?obj=&act=&fmt=` | Recommendations | member | JSON |
| `manifest.json.php`, `sw.js.php` | PWA manifest / service worker | public | JSON / JS |

Member‑session action dispatchers (HTML): `vote.php`, `score.php`, `favorite.php`, `feature.php`, `report.php`, `view.php`, `cmts.php`, `label.php`, `privacy.php`, `chart.php`, `menu.php`, `form.php`, `page.php`, `grid.php` (CSRF‑guarded). Payment redirects: `cart.php`, `orders.php`, `invoices.php`, `subscriptions.php`.

**Webhooks** are routed *through the module dispatcher*, not a root file: `BxPaymentModule::actionNotify($provider, $vendor)` (`modules/boonex/payment/classes/BxPaymentModule.php:1186`) is the generic payment IPN handler (Stripe/PayPal/Chargebee/Apple In‑App). Reached via a module action URL (`.../m/bx_payment/notify/<provider>/<vendor>`).

---

## 6. Surface D — GFunnel onboarding API (custom)
**`modules/gfunnel/onboarding_module`** (`gfunnel_onb`) — the one bespoke external integration in the codebase, and the pattern to reuse:
- Endpoints: `api/save-onboarding.php`, `api/complete-onboarding.php`, `api/verify-token.php`.
- CORS restricted to `onboarding.gfunnel.com`.
- **HMAC‑SHA256 signed tokens**, 5‑minute expiry; secret in `BxGfunnelOnbModule.php:10`, overridable via `GFUNNEL_SECRET_KEY` env var.
- Writes to `gfo_onboarding_data` + `bx_persons_data`.
- ⚠️ Ships with a **hardcoded fallback secret** — see §8.

---

## 7. Surface E — AI / automation (core, not a module)
UNA's AI lives in `inc/classes/` (the modules named `artificer`/`protean`/`lucid` are *design templates*, not AI):
- `BxDolAI.php` — orchestrator: models, assistants, assistant chats, **automators** (`callAutomator`, `evalCode`), **event‑triggered** automators (`getAutomatorsEvent`), **scheduler** (`getAutomatorsScheduler`), **webhook** automators (`getAutomatorsWebhook`).
- `BxDolAIAssistant.php` — `processActionAsk`, `processActionAddKnowledge` (RAG Q&A).
- `BxDolAIModel*.php` — **OpenAI Assistants API** integration (threads/runs/messages/files/vector_stores; gpt‑4o + gpt‑3.5).
- `BxDolAIProvider.php` + `BxDolAIProviderShopifyAdmin.php` — provider/webhook framework (Shopify admin automation).
- Public dispatcher `agents.php`; automators run from `periodic/cron.php`.

This subsystem is directly relevant to the MCP goal: UNA already has an assistant/automator/webhook layer we can hook an MCP server into rather than build from zero.

---

## 8. Security items to resolve before publishing docs
1. **`sys_api_access_unsafe_services`** — collapses ~2,000 `service*` methods into callable *and* enables the raw‑SQL passthrough (`?r=q`). Docs must never present this as a normal mode; confirm it is **off** in production.
2. **`agents.php`** — public (no `check_logged()`) and, when called with no action, makes a live Shopify Admin API call and `print_r`s the result (looks like leftover debug code). Should be gated or removed.
3. **`custom_batch_updates/aff_custom_batch_update.php`** — no auth guard, hardcoded absolute path, and a committed `error_log`. Ensure it is not web‑reachable.
4. **Onboarding module hardcoded HMAC secret fallback** — must be forced to `GFUNNEL_SECRET_KEY` in production.
5. **Commented‑out login state** in `bx_api_check_access()` — confirm this is intentional and documented (the gateway is anonymous by design; user context is OAuth2‑only).

---

## 9. Recommendations

### 9.1 To complete the API docs
1. **Generate the reference from the code annotations.** The OAuth2 classes already use Doxygen `@page`/`@section` with sample requests/responses — run Doxygen (or a small extractor) over `modules/boonex/oauth2` and `modules/boonex/api` to seed the reference, then expand.
2. **Publish the safe/public catalog as the canonical endpoint list.** `docs/api/service-catalog.md` (in this PR) is the machine‑derived source of truth for what Surface A exposes by default.
3. **Document the two auth flows separately and clearly** — API key/Origin for server‑to‑server (A), OAuth2 for user‑scoped (B) — with copy‑paste `curl` examples and the exact settings each requires.
4. **Version note:** the `bx_api` module reports v13.0.1 on a 14.0.0 core; call out any behavioral differences during doc QA.

### 9.2 For an MCP server
- **Wrap Surface B (OAuth2 `service`/`com`)** as the primary transport — it gives per‑user auth and reuses the safe‑service allow‑list, so tools are automatically scoped to what's already been vetted as safe.
- **One MCP tool per safe service**, generated from the catalog, grouped by domain: profiles/accounts, content CRUD (`entity_*`, `browse_*`), messaging (`bx_messenger`), commerce (`bx_payment`/`bx_market`/`bx_credits`), search (`bx_elasticsearch`), timeline/feed.
- **Do not expose the unsafe surface or `?r=q`.** Keep the MCP server strictly inside the safe/public set unless a capability is explicitly promoted.
- **Reuse the onboarding HMAC pattern** for any machine‑to‑machine tool that shouldn't ride a user token.
- Consider surfacing the **AI automator/webhook** layer (§7) as MCP tools for triggering assistants and scheduled automations.

---

## Appendix — companion files in this directory
- `service-catalog.md` — full per‑module safe/public service inventory (Surface A).
- `http-endpoints.md` — full root HTTP entry‑point table (Surface C).
- `auth-and-oauth2.md` — detailed auth internals (Surfaces A & B) and OAuth2 endpoint reference.
