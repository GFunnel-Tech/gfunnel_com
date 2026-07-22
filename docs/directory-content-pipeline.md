# Directory content pipeline — monitors that keep apps fresh

How the app directory stays alive: app rows come from Supabase, and **monitors**
periodically enrich them with YouTube tutorials (and, later, docs/help). Everything
flows one way into the MySQL mirror the site reads.

```
YouTube Data API
      │  (fetch-app-tutorials edge fn, on a schedule)
      ▼
Supabase Postgres  ── app_tutorials / app_docs / app_help_articles ──┐
   (source of truth)                                                 │
      │  row change → Database Webhook                               │  backfill
      ▼                                                              ▼
sync-directory-apps-to-mysql (edge fn)  ───────────────────►  MySQL mirror
                                                              gf_app_tutorials …
                                                                     │
                                                                     ▼
                                                     gf_applications.php detail page
                                                     (/application/<slug> → Tutorials)
```

Postgres is always the single source of truth; MySQL is a downstream replica for
the app-directory domain only (see `gf_applications.php` header +
`docs/directory-sync-runbook.md`).

---

## 1. The monitor: `fetch-app-tutorials`

`supabase/functions/fetch-app-tutorials/index.ts`. For a batch of `platform_apps`
it searches the YouTube Data API for `"<app name> tutorial"`, drops Shorts
(<60s), keeps the top few by view count, and **inserts only new** videos into
`public.app_tutorials` (idempotent — it reads the app's existing
`youtube_video_id`s first).

**Request** (POST JSON, header `x-sync-secret: <SYNC_SHARED_SECRET>`):

| Body | Effect |
|---|---|
| `{}` | refresh the 25 most-popular apps |
| `{"limit":50}` | refresh the top 50 by `popularity_rank` |
| `{"app_slugs":["notion","slack"]}` | refresh specific apps |
| `{"max_per_app":4}` | videos kept per app (default 4, max 8) |
| `{"dry_run":true}` | fetch + report, write nothing |

**Secrets required** (Supabase → Project settings → Edge Functions):
`SUPABASE_URL`, `SUPABASE_SERVICE_ROLE_KEY`, `SYNC_SHARED_SECRET`,
`YOUTUBE_API_KEY` (a YouTube Data API v3 key).

### Deploy (needs sign-off)

```bash
supabase functions deploy fetch-app-tutorials --no-verify-jwt
supabase secrets set YOUTUBE_API_KEY=... SYNC_SHARED_SECRET=...
```

### Smoke test

```bash
curl -s -X POST "$SUPABASE_URL/functions/v1/fetch-app-tutorials" \
  -H "x-sync-secret: $SYNC_SHARED_SECRET" -H "content-type: application/json" \
  -d '{"app_slugs":["notion"],"dry_run":true}'
```

---

## 2. Scheduling it (the "monitor" part)

Any scheduler that can POST works. In-database with **pg_cron + pg_net**:

```sql
-- once: enable the extensions (Supabase → Database → Extensions, or:)
create extension if not exists pg_cron;
create extension if not exists pg_net;

-- refresh a rolling batch every 6 hours
select cron.schedule(
  'fetch-app-tutorials',
  '0 */6 * * *',
  $$
  select net.http_post(
    url    := 'https://<project-ref>.functions.supabase.co/fetch-app-tutorials',
    headers:= jsonb_build_object('content-type','application/json','x-sync-secret','<SYNC_SHARED_SECRET>'),
    body   := jsonb_build_object('limit', 40)
  );
  $$
);
```

(Prefer Vault for the secret rather than inlining it. Unschedule with
`select cron.unschedule('fetch-app-tutorials');`.)

---

## 3. Getting it onto the site (MySQL mirror)

`fetch-app-tutorials` writes to Postgres. It reaches the site two ways:

1. **Live** — a Supabase **Database Webhook** on `app_tutorials` (INSERT/UPDATE/
   DELETE) already POSTs to `sync-directory-apps-to-mysql`, which upserts
   `gf_app_tutorials`. New videos appear on the next page load.
2. **One-time / catch-up backfill** — push every existing row down:

   ```bash
   curl -s -X POST "$SUPABASE_URL/functions/v1/sync-directory-apps-to-mysql" \
     -H "x-sync-secret: $SYNC_SHARED_SECRET" -H "content-type: application/json" \
     -d '{"mode":"backfill","table":"all"}'
   ```

   `"table":"all"` covers `directory_apps`, `platform_apps`, `app_tutorials`,
   `app_docs`, `app_help_articles`. Use this the **first time** to fill an empty
   mirror (the webhook only fires on *new* changes, so pre-existing rows need one
   backfill).

---

## 4. Roadmap (same shape)

- **Docs monitor** → `app_docs` (crawl each app's `app_url` for `/docs`, `/api`).
- **Help monitor** → `app_help_articles` (RSS/KB ingest).
- **Enrichment** → fill missing `logo_url` / `gfunnel_description` on
  `directory_apps` / `platform_apps`.

Each is another edge function on the same auth + schedule + sync path.
