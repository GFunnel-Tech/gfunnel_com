# Directory Sync — Phase 1 Runbook

Vertical slice proving the full pipeline for **one** entity (`directory_apps`):

```
Supabase Postgres public.directory_apps   (source of truth, ~5,010 rows)
      │  row change → Database Webhook
      ▼
Edge Function sync-directory-apps-to-mysql  (owned one-way push, PG → MySQL)
      │
      ▼
UNA MySQL gf_directory_apps                 (downstream mirror)
      │
      ▼
gf_directory.php  → /directory              (Star-Head-style page, reads mirror)
```

Postgres is the **single source of truth**. MySQL is a read replica for this one
domain. This is deliberately **not** bidirectional — no conflict resolution, no
echo loops. The Supabase MCP is the front door for get/create/update on Postgres;
the Edge Function is what lands those changes in MySQL.

## What's already committed (no production writes)

| Artifact | Path |
|---|---|
| UNA read/render page + route | `gf_directory.php`, `r.php` (`/directory`) |
| Mirror-table DDL (also auto-created by the page) | `docs/sql/gf_directory_apps.mysql.sql` |
| PG→MySQL sync Edge Function | `supabase/functions/sync-directory-apps-to-mysql/index.ts` |

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

### 4. Backfill the existing ~5,010 rows
```
POST https://<project>.functions.supabase.co/sync-directory-apps-to-mysql
  header x-sync-secret: <SYNC_SHARED_SECRET>
  body   {"mode":"backfill"}
```
Expect `{ ok: true, synced: ~5010 }`. **Write scope:** upserts into
`gf_directory_apps` only.

### 5. Create the Database Webhook (keeps it live)
Supabase → Database → Webhooks → new webhook on `public.directory_apps` for
INSERT/UPDATE/DELETE, HTTP POST to the function URL, with header
`x-sync-secret: <SYNC_SHARED_SECRET>`.

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
- Full teardown: `DROP TABLE gf_directory_apps;` (mirror only — source untouched).
