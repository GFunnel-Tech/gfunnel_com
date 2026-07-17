# Application Directory Sync — Runbook

Full app-directory pipeline (list + per-app detail) across five source tables:

```
Supabase Postgres (source of truth)          rows
  directory_apps ─────────────┐              5,010   (curated list)
  platform_apps ──────────────┤              5,000   (rich detail, all enriched)
  app_tutorials ──────────────┤                  0   (lights up when populated)
  app_docs ───────────────────┤                  0
  app_help_articles ──────────┘                  0
        │  row change → Database Webhook (per table)
        ▼
Edge Function sync-directory-apps-to-mysql   (owned one-way push, PG → MySQL,
        │                                     dispatches by `table`)
        ▼
UNA MySQL mirrors: gf_directory_apps, gf_platform_apps, gf_app_tutorials,
        │           gf_app_docs, gf_app_help_articles
        ▼
gf_directory.php   /directory          → searchable app grid
                   /directory/<slug>   → per-app detail (about, use cases,
                                          automation ideas, categories,
                                          departments, tutorials, docs, help)
```

Postgres is the **single source of truth**. MySQL mirrors are read replicas for
the app-directory domain. Deliberately **not** bidirectional — no conflict
resolution, no echo loops. The Supabase MCP is the front door for
get/create/update on Postgres; the Edge Function lands those changes in MySQL.

## What's already committed (no production writes)

| Artifact | Path |
|---|---|
| UNA list + detail pages + routes | `gf_directory.php`, `r.php` (`/directory`, `/directory/<slug>`) |
| Mirror-table DDL (also auto-created by the page) | `docs/sql/gf_directory_apps.mysql.sql` |
| PG→MySQL sync Edge Function (5 tables) | `supabase/functions/sync-directory-apps-to-mysql/index.ts` |

## Execution steps (require sign-off — these touch production)

Nothing below has been run yet. Each step is idempotent.

### 1. Create the MySQL mirror table
Either deploy this branch and hit `/directory` once (the page runs
`CREATE TABLE IF NOT EXISTS`), or run `docs/sql/gf_directory_apps.mysql.sql`
against the UNA database manually. **Write scope:** one new table, no existing
data touched.

### 2. Set Edge Function secrets (Supabase)
```
MYSQL_HOST, MYSQL_PORT, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE
SYNC_SHARED_SECRET          # random string; required on every call
SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY   # for backfill mode
```
The MySQL user only needs `INSERT, UPDATE, DELETE, SELECT` on `gf_directory_apps`.

### 3. Deploy the Edge Function
Via Supabase MCP `deploy_edge_function`, or
`supabase functions deploy sync-directory-apps-to-mysql --no-verify-jwt`.

### 4. Backfill existing rows (all five tables)
```
POST https://<project>.functions.supabase.co/sync-directory-apps-to-mysql
  header x-sync-secret: <SYNC_SHARED_SECRET>
  body   {"mode":"backfill","table":"all"}
```
Expect `{ ok: true, synced: { directory_apps: ~5010, platform_apps: ~5000,
app_tutorials: 0, app_docs: 0, app_help_articles: 0 } }`. Pass a single
`"table":"platform_apps"` to backfill one. **Write scope:** upserts into the
`gf_*` mirror tables only.

### 5. Create the Database Webhooks (keeps it live)
For each source table (`directory_apps`, `platform_apps`, `app_tutorials`,
`app_docs`, `app_help_articles`): Supabase → Database → Webhooks → new webhook
for INSERT/UPDATE/DELETE, HTTP POST to the function URL, header
`x-sync-secret: <SYNC_SHARED_SECRET>`. The function dispatches by the `table`
field in the payload, so all webhooks point at the same function.

### 6. Prove it end to end
Create one row **through the MCP** (source of truth), then confirm it appears at
`/directory` within a couple seconds:
```sql
insert into public.directory_apps (name, slug, description, category, app_url, is_featured, is_gfunnel_native)
values ('Sync Smoke Test','sync-smoke-test','Created via MCP to prove the pipeline.','Testing','https://gfunnel.com', true, true);
```
Then delete it to leave production clean:
```sql
delete from public.directory_apps where slug = 'sync-smoke-test';
```
Both the insert and the delete should reflect at `/directory` (delete removes the
card), proving INSERT and DELETE propagate.

## Rollback
- Disable the page: set `sys_option` `gf_directory = off`.
- Disable sync: delete the Database Webhook.
- Full teardown: `DROP TABLE gf_directory_apps, gf_platform_apps, gf_app_tutorials,
  gf_app_docs, gf_app_help_articles;` (mirrors only — Supabase source untouched).
