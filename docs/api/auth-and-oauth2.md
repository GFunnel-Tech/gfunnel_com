# UNA CMS 14.0.0 — API Authentication & Gateway Architecture

There are TWO distinct API entry points in this codebase:

1. **`/api.php`** — a lightweight "service gateway" (key/origin auth) — `BxDolService::call` fronted by `bx_api_check_access()`.
2. **`modules/boonex/oauth2/`** — a full OAuth2 server (bshaffer library) exposing `/m/oauth2/*` endpoints (token/authorize/api/com). The `bx_api` module is a thin service-provider that plugs into it.

---

## 1. The `/api.php` gateway
File: `/home/user/gfunnel_com/api.php`

- Defines `BX_API`, includes header/profiles/design, forces `Content-Type: application/json`.
- Line 11: calls `bx_api_check_access()` (the gate).
- Line 13-17: `?cnf=1` returns the public API config: `{data: getParam('sys_api_config'), hash: md5(...)}`.
- Request routing (line 21-39): `?r=sModule/sMethod/sClass` split on `/`. Each part validated against `/^[A-Za-z0-9_-]+$/`. `sClass` defaults to `'Module'`. Missing module/method → 404.
- Line 43: `BxDolRequest::serviceExists($sModule,$sMethod,$sClass)` else 404.
- **Safe/public gate (line 53-68):** unless `sys_api_access_unsafe_services` is on, the service must be declared *safe* (`is_safe_service`) OR *public* (`is_public_service`) by the target module (for `system` module it uses `TemplServices` class). Otherwise 403.
- Line 72-80: `params` GET/POST param, JSON-decoded if it looks like `[...]`, then `BxDolService::call($sModule,$sMethod,$aParams,$sClass)`.
- Output (line 84-107): errors → HTTP 500 with `{status,error,data,code}`; success → `{status:200, module, method, params, data, hash: md5(sys_api_config)}`.

**Example:**
```
curl 'https://gfunnel.com/api.php?r=system/get_page&params=%7B%22uri%22:%22home%22%7D' \
     -H 'Authorization: Bearer <API_KEY_FROM_sys_api_keys>'
```

---

## 2. `bx_api_check_access()` — the gateway auth function
File: `/home/user/gfunnel_com/inc/utils.inc.php:2568-2634`

Logic:
1. **`sys_api_enable`** must be truthy, else 403 (line 2570).
2. Reads `Origin` and `Authorization` headers via `getallheaders()` (fallback `$_SERVER['HTTP_ORIGIN']` / `HTTP_AUTHORIZATION`) (line 2577-2585).
3. **Key auth (line 2587-2597):** if an `Authorization` header is present AND `sys_api_access_by_key` is on:
   - `str_replace('Bearer ', '', $sAuthHeader)` → looked up via `BxDolApiQuery::getInstance()->getKey(...)`. Not found → 403.
   - Special case: `?r=q&q=...` with `sys_api_access_unsafe_services` on → `bx_api_get_sql(bx_get('q'))` (a whitelisted raw-SQL passthrough, see §6).
4. **Origin auth (line 2598-2622):** elseif `sys_api_access_by_origin` on AND an `Origin` header:
   - If origin host ≠ site host, must exist in `sys_api_origins` (`getOrigin`), else 403.
   - Emits **CORS headers**: `Access-Control-Allow-Origin: <origin>` + `Access-Control-Allow-Credentials: true`. Handles `OPTIONS` preflight with `Allow-Methods: POST, GET` and a fixed `Allow-Headers` list, then exits.
5. **Else → 403** (line 2624-2629).
6. NOTE (line 2631-2633): logged-state (`bx_login`/`check_logged`) is **commented out** — `api.php` calls run *anonymously*; only "safe"/"public" services are reachable. Session/cookie login is NOT wired into `api.php` (that path lives in the oauth2 module instead).

**So `api.php` supports two auth modes: (a) API key via `Authorization: Bearer <key>` matched against `sys_api_keys`; (b) CORS Origin allow-listing via `sys_api_origins`.** No password/token-to-profile mapping here.

### Backing store: `BxDolApiQuery`
File: `/home/user/gfunnel_com/inc/classes/BxDolApiQuery.php`
- `getOrigin($url)` → `SELECT url FROM sys_api_origins WHERE url=:url` (line 46).
- `getKey($key)` → `SELECT key FROM sys_api_keys WHERE key=:key` (line 51).
- Keys/origins are managed in Studio: `studio/classes/BxDolStudioApiKeys.php` (table `sys_api_keys`) and `studio/classes/BxDolStudioApiOrigins.php` (table `sys_api_origins`).

---

## 3. `bx_api` module (modules/boonex/api)
Config: `/home/user/gfunnel_com/modules/boonex/api/install/config.php`
- name `bx_api`, **home_uri `api`**, db_prefix `bx_api_`, class_prefix `BxApi`, version 13.0.1.
- **Depends on the `oauth2` module** (line 65-67) — it provides the service methods that oauth2's `com` endpoint exposes; it is not itself a standalone HTTP endpoint for auth.
- `request.php` just does `check_logged()` then processes as file/action (standard module page handling).

Class `BxApiModule` (`classes/BxApiModule.php`) declares which of its services are safe/public:
- **Safe services** (line 19-27): `DeletePage`, `ChangeAccountPassword`, `SwitchProfile` (require a valid access token / logged profile).
- **Public services** (line 29-39): `Test`, `GetPage`, `ResetPasswordSendRequest`, `ResetPasswordCheckCode`, `CreateAccount` (only need a valid `client_id`).
- These are invoked as **`/m/oauth2/com/<method>`** (the docblocks say e.g. `/m/oauth2/com/test`, `/m/oauth2/com/get_page`, `/m/oauth2/com/create_account`). So `bx_api` services are surfaced *through* the oauth2 `actionCom` router, not through `api.php`.
- Notable methods: `serviceGetPage` (returns `BxDolPage::getPageAPI()`), `serviceCreateAccount` (builds `bx_accounts_account_create` form with CSRF disabled, creates account + auto-profile), `serviceResetPasswordSendRequest`/`CheckCode` (uses `BxDolKey` email codes), `serviceChangeAccountPassword`, `serviceSwitchProfile`.

---

## 4. OAuth2 server (modules/boonex/oauth2)
Config: `/home/user/gfunnel_com/modules/boonex/oauth2/install/config.php` — name `bx_oauth`, **home_uri `oauth2`**, db_prefix `bx_oauth_`, class_prefix `BxOAuth`, version 14.0.1.
Underlying library: **bshaffer/oauth2-server-php** at `/home/user/gfunnel_com/plugins/bshaffer/oauth2-server-php/`.
`request.php` → `check_logged()` then `BxDolRequest::processAsAction()` (URL parts after `oauth2/` map to `actionXxx`).

### Endpoints (class `BxOAuthModule`, `classes/BxOAuthModule.php`)
| URL | Method | Purpose |
|-----|--------|---------|
| `/m/oauth2/token` | `actionToken` (line 193) | Token endpoint. `handleTokenRequest`. |
| `/m/oauth2/auth` | `actionAuth` (line 322) | Authorization endpoint (interactive; requires site login, renders `oauth-authorization` page / profile switcher). |
| `/m/oauth2/revoke` | `actionRevoke` (line 233) | Token revocation. |
| `/m/oauth2/api/<action>` | `actionApi` (line 290) | Token-protected API (me/user/friends/service/market). |
| `/m/oauth2/com/<method>` | `actionCom` (line 242) | Public/safe service dispatch into `bx_api` (or any module). |

### Storage / DB config (line 118-129)
`BxOAuthUserCredentialsStorage extends OAuth2\Storage\Pdo`. Tables:
`bx_oauth_clients`, `bx_oauth_access_tokens`, `bx_oauth_refresh_tokens`, `bx_oauth_authorization_codes`, `bx_oauth_scopes`, `bx_oauth_allowed_origins` (join used in `getClientByAllowedOriginUrl`), user table = `sys_accounts`. JWT/JTI/public-key tables are **empty strings → JWT bearer tokens are NOT enabled**; tokens are opaque random strings stored in DB. (No install SQL ships in the module dir; tables are provisioned at platform install time.)

### Grant types (line 137-156) — enabled per-client based on `grant_types` column
- `client_credentials` → `OAuth2\GrantType\ClientCredentials`
- `authorization_code` → `OAuth2\GrantType\AuthorizationCode` (default when client/grant unset, line 108-111)
- `password` → `OAuth2\GrantType\UserCredentials` (recommended for API per docblock line 169)
- `refresh_token` → `OAuth2\GrantType\RefreshToken`, `always_issue_new_refresh_token` from `bx_oauth2_always_issue_new_refresh_token`, refresh lifetime `bx_oauth2_refresh_token_lifetime` (default 7,779,000s ≈ 90 days), `require_exact_redirect_uri => false`.

### Username/password → profile mapping
`BxOAuthUserCredentialsStorage` (line 12-57):
- `checkUserCredentials($login,$pw)` → `bx_check_password()`.
- `getUserDetails($login)` → resolves `BxDolAccount` → `BxDolProfile::getInstanceByAccount()`, returns `['user_id' => $oProfile->id()]`. Access token's `user_id` = a **profile id**, not account id.
- On a `service` call, `BxOAuthAPI::service()` does `bx_login($oProfile->getAccountId(), false); check_logged();` (line 304-305) — i.e. the bearer token is exchanged into a real logged-in session for the duration of the call.

### Token → client resolution
`getClientIdFromAccessTokenHeader()` (line 438-451): reads `HTTP_AUTHORIZATION` / `REDIRECT_HTTP_AUTHORIZATION`, requires `Bearer`, `substr(...,6)`, looks up `bx_oauth_access_tokens.access_token` → `client_id` via `BxOAuthDb::getClientIdByAccessToken` (`classes/BxOAuthDb.php:74`).

### Scopes (class `BxOAuthAPI`, `classes/BxOAuthAPI.php:18-25`)
`aAction2Scope`: `me/user/friends → basic,market,service,api`; `service → service`; `market → market`; `api → api`. `actionApi`/`actionCom` enforce scope via `array_intersect($tokenScopes,$requiredScopes)` → else 403 `insufficient_scope`.
Default scope on new client = `basic` (`BxOAuthFormAdd::insert` line 94) or `market` (`serviceAddClient` line 402-403).

### Private API methods (`BxOAuthAPI`)
- `me($aToken)` `/m/oauth2/api/me` — current profile info (adds `owner` flag, followers/following/friends, all account profiles).
- `user($aToken)` `/m/oauth2/api/user?id=` — other profile (public fields unless admin).
- `friends($aToken)` `/m/oauth2/api/friends?id=` — `sys_profiles_friends` connections.
- `service($aToken)` `/m/oauth2/api/service?module=&method=&params=&class=` — generic `BxDolService::call` after `bx_login`. Accepts params as array / PHP-serialized / JSON.
- `market($aToken)` — `service` pinned to module `bx_market_api`.
- `com($sMethod,$aToken,$bPublic)` `/m/oauth2/api/com/<method>` — dispatch into `bx_api` (module param default `bx_api`). `actionCom` (BxOAuthModule:242): public services need a valid `client_id` only; safe services require a verified access token AND `api` scope.

### Client registration (`BxOAuthModule`)
`serviceAddClient` (line 386): auto-generates `client_id` (`genRndPwd(10)`) and `client_secret` (`genRndPwd(32)`), default scope `market`, `user_id = logged profile`. Studio-managed via `BxOAuthGrid` / `BxOAuthFormAdd` (`bx_oauth_clients` table, add form asks title + `redirect_uri` must match `^https?://`). `serviceGetClientsBy/UpdateClientsBy/DeleteClientsBy` for management.

### CORS in oauth2
`checkAllowedOrigins()` (line 70-91): if `HTTP_ORIGIN` host ≠ site host, client must own that origin in `bx_oauth_allowed_origins` (`BxOAuthDb::getClientByAllowedOriginUrl`), else 403. Emits `Access-Control-Allow-Origin`, and on `OPTIONS` returns `Allow-Methods: POST, GET` + `Allow-Headers: Authorization, Content-Type, X-Custom-Header, X-Requested-With`.

**Token request example (password grant):**
```
curl -X POST https://gfunnel.com/m/oauth2/token \
  -d grant_type=password -d username=user@example.com \
  -d password=secret -d client_id=<CLIENT_ID>
# → {"access_token":"...","expires_in":3600,"token_type":"Bearer","scope":"basic","refresh_token":"..."}

curl https://gfunnel.com/m/oauth2/api/me -H 'Authorization: Bearer <access_token>'
```

---

## 5. System settings (`sys_api_*`) — labels from `modules/boonex/*/data/langs/system/en.xml:1780+`
| Param | Meaning |
|-------|---------|
| `sys_api_enable` | Master on/off for `api.php` (checked first in `bx_api_check_access`). |
| `sys_api_access_by_origin` | Enable CORS Origin-based auth via `sys_api_origins`. |
| `sys_api_access_by_key` | Enable `Authorization: Bearer <key>` auth via `sys_api_keys`. |
| `sys_api_access_unsafe_services` | If on, bypass safe/public gate in `api.php` (allows ANY service) AND enables the `?r=q` raw-SQL passthrough. **Security-sensitive.** |
| `sys_api_config` | JSON app config blob served by `?cnf=1`; its md5 is the `hash` in every response (client cache-busting). Edited in Studio (`BxBaseStudioAPI` / `BxBaseStudioOptionsApi`). |
| `sys_api_cookie_path` / `sys_api_cookie_secure` / `sys_api_cookie_samesite` | Cookie attributes used when API context sets `memberSession`/`memberPassword` cookies (`inc/utils.inc.php:2496-2519`). |
| `sys_api_url_root_email` / `sys_api_url_root_push` | Base site URL substituted into email/push links for API/app clients (`bx_api_get_base_url`, utils.inc.php:2636). |
| `sys_api_menu_top`, `sys_api_search_sections`, `sys_api_comments_flat`, `sys_api_comments_modal`, `sys_api_extended_units`, `sys_api_conn_in_prof_units` | Content/rendering options for the app-facing API (menus, search scope, comment style, extended unit payloads). |
| `bx_oauth2_refresh_token_lifetime`, `bx_oauth2_always_issue_new_refresh_token` | OAuth2 refresh-token behavior. |
| `bx_unacon_api_key`/`_secret`/`_url`/`_url_rewrite` | una_connect client credentials to a remote UNA oauth2 server. |

---

## 6. Token / key classes and `bx_api_*` helpers (inc/)
- **`BxDolApiQuery`** (`inc/classes/BxDolApiQuery.php`) — the `sys_api_keys` / `sys_api_origins` lookups for `api.php`.
- **`BxDolKey`** (`inc/classes/BxDolKey.php`) — generic one-time key store (NOT OAuth). `getNewKey($data,$iExpire=604800,$salt)` line 75, `getNewKeyNumeric` line 89, `isKeyExists` line 109, `getKeyData` line 119, `removeKey`. Used by `bx_api` password-reset flow and email confirmation — these are opaque hash keys with data + expiry, not bearer tokens.
- **`bx_api_check_access()`** utils.inc.php:2568 (see §2).
- **`bx_api_get_sql($q)`** utils.inc.php:2549 — **whitelisted** raw-SQL passthrough. Only recognizes `q=accounts_count` → `SELECT Count(*) FROM sys_accounts`; anything else is ignored. Reached from `api.php?r=q&q=...` when key-auth + `sys_api_access_unsafe_services` are on.
- **`bx_is_api()`** utils.inc.php:2544 — true when `BX_API` defined or `?api` present; toggles cookie SameSite/secure handling and unit rendering across the platform.
- **`bx_api_get_base_url()`** / **`bx_api_get_relative_url()`** utils.inc.php:2636+ — URL rewriting for app clients.
- **JWT:** the bshaffer library supports JWT bearer tokens, but UNA config sets `jwt_table`/`jti_table`/`public_key_table` to `''` (BxOAuthModule:124-127), so **tokens are opaque DB-stored strings, not JWTs**. No first-party JWT usage in this stack.
- Auth primitives used: `bx_check_password()`, `bx_login()`, `check_logged()`, `isLogged()`, `BxDolAccount`, `BxDolProfile`.

---

## 7. Social/federated connect modules
All are OAuth2 *clients* (they authenticate users INTO the site; they don't guard the API):
- **una_connect** (`bx_unacon`, uri `unacon`): OAuth2 client to *another UNA site's* oauth2 server. `BxUnaConConfig.php:27-29` builds `sApiUrl = <remote>/m/oauth2/` (or `/modules/?r=oauth2/`) using `bx_unacon_api_key`/`bx_unacon_secret`. `BxUnaConModule.php`: redirects to remote authorize, exchanges code at `token`, stores `access_token` in session, fetches identity from remote `api/me` (line 93). Effectively "Login with UNA".
- **facebook_connect** (`bx_facebook`, uri `facebook_connect`, v14.0.0): Facebook Graph OAuth. `BxFaceBookConnectModule.php:108` calls `/me/picture`, line 149-151 redirects to FB login with `redirect_uri`. Uses FB SDK + app id/secret.
- **linkedin_connect** (`bx_linkedin`): `BxLinkedinConfig.php:16-17` — `sApiUrl=https://api.linkedin.com/v2`, `sOauthUrl=https://www.linkedin.com/oauth/v2`. Standard LinkedIn OAuth2 authorization_code flow.
- **stripe_connect** — Stripe Connect (payments), not identity auth.

Each maps the remote identity to a local `BxDolAccount`/`BxDolProfile` and logs the user in via the standard session; they do not issue API tokens.

---

## 8. CORS summary
Two independent CORS implementations:
1. **api.php path** (`bx_api_check_access`, utils.inc.php:2609-2621): allow-list from `sys_api_origins`; sets `Allow-Origin` + `Allow-Credentials: true`; preflight `Allow-Methods: POST, GET`, broad `Allow-Headers`.
2. **oauth2 path** (`BxOAuthModule::checkAllowedOrigins`, line 70-91): per-client allow-list from `bx_oauth_allowed_origins`; sets `Allow-Origin`; preflight `Allow-Methods: POST, GET`, `Allow-Headers: Authorization, Content-Type, X-Custom-Header, X-Requested-With`.
Same-origin requests bypass both checks.
