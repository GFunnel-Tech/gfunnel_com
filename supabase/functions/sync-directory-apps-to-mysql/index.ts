// GFunnel — one-way sync: Supabase Postgres `public.directory_apps` -> UNA MySQL `gf_directory_apps`.
//
// This is the "owned code" half of the pipeline. The Supabase MCP is the front
// door for get/create/update against Postgres; THIS function is what mirrors
// each change down to the MySQL database the UNA app reads from. It is NOT a
// bidirectional sync — Postgres is the single source of truth, MySQL is a
// downstream replica for exactly the `directory_apps` domain.
//
// Two modes:
//   1. Webhook (default): Supabase Database Webhook on public.directory_apps
//      POSTs {type, record, old_record}. We upsert (INSERT/UPDATE) or delete
//      (DELETE) the one affected row.
//   2. Backfill: POST {"mode":"backfill"} with the service-role bearer token.
//      We page through all rows and upsert them. Run this once after deploy to
//      seed the ~5,010 existing rows, then the webhook keeps it current.
//
// Secrets (set via `supabase secrets set` or the dashboard):
//   MYSQL_HOST, MYSQL_PORT, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE
//   SYNC_SHARED_SECRET  - required header `x-sync-secret` for every call
//   SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY - used by backfill to read source rows
//
// Deploy (with sign-off): via Supabase MCP `deploy_edge_function`, or
//   `supabase functions deploy sync-directory-apps-to-mysql --no-verify-jwt`.

import { createClient } from "jsr:@supabase/supabase-js@2";
import { Client as MySQLClient } from "https://deno.land/x/mysql@v2.12.1/mod.ts";

type DirectoryApp = {
  id: string;
  platform_app_id: string | null;
  name: string;
  slug: string | null;
  description: string | null;
  logo_url: string | null;
  app_url: string | null;
  category: string | null;
  access_type: string | null;
  is_featured: boolean | null;
  is_gfunnel_native: boolean | null;
  created_at: string | null;
};

const env = (k: string): string => Deno.env.get(k) ?? "";

function tsToMysql(ts: string | null): string | null {
  // Postgres timestamptz (ISO) -> MySQL DATETIME (UTC 'YYYY-MM-DD HH:MM:SS').
  if (!ts) return null;
  const d = new Date(ts);
  if (isNaN(d.getTime())) return null;
  return d.toISOString().slice(0, 19).replace("T", " ");
}

async function connectMysql(): Promise<MySQLClient> {
  return await new MySQLClient().connect({
    hostname: env("MYSQL_HOST"),
    port: Number(env("MYSQL_PORT") || "3306"),
    username: env("MYSQL_USER"),
    password: env("MYSQL_PASSWORD"),
    db: env("MYSQL_DATABASE"),
  });
}

const UPSERT_SQL = `
INSERT INTO gf_directory_apps
  (id, platform_app_id, name, slug, description, logo_url, app_url, category,
   access_type, is_featured, is_gfunnel_native, created_at, synced_at)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
  platform_app_id=VALUES(platform_app_id), name=VALUES(name), slug=VALUES(slug),
  description=VALUES(description), logo_url=VALUES(logo_url), app_url=VALUES(app_url),
  category=VALUES(category), access_type=VALUES(access_type),
  is_featured=VALUES(is_featured), is_gfunnel_native=VALUES(is_gfunnel_native),
  created_at=VALUES(created_at), synced_at=UTC_TIMESTAMP()`;

async function upsertRow(db: MySQLClient, r: DirectoryApp): Promise<void> {
  await db.execute(UPSERT_SQL, [
    r.id,
    r.platform_app_id,
    r.name ?? "",
    r.slug,
    r.description,
    r.logo_url,
    r.app_url,
    r.category,
    r.access_type ?? "free",
    r.is_featured ? 1 : 0,
    r.is_gfunnel_native ? 1 : 0,
    tsToMysql(r.created_at),
  ]);
}

async function deleteRow(db: MySQLClient, id: string): Promise<void> {
  await db.execute("DELETE FROM gf_directory_apps WHERE id = ?", [id]);
}

async function backfill(db: MySQLClient): Promise<number> {
  const sb = createClient(env("SUPABASE_URL"), env("SUPABASE_SERVICE_ROLE_KEY"));
  const pageSize = 1000;
  let from = 0;
  let total = 0;
  while (true) {
    const { data, error } = await sb
      .from("directory_apps")
      .select("*")
      .order("id", { ascending: true })
      .range(from, from + pageSize - 1);
    if (error) throw new Error(`source read failed: ${error.message}`);
    if (!data || data.length === 0) break;
    for (const row of data as DirectoryApp[]) await upsertRow(db, row);
    total += data.length;
    if (data.length < pageSize) break;
    from += pageSize;
  }
  return total;
}

Deno.serve(async (req) => {
  // Shared-secret gate — every call must present it.
  if (req.headers.get("x-sync-secret") !== env("SYNC_SHARED_SECRET")) {
    return new Response("forbidden", { status: 403 });
  }

  let body: Record<string, unknown> = {};
  try {
    body = await req.json();
  } catch {
    return new Response("bad request", { status: 400 });
  }

  let db: MySQLClient | null = null;
  try {
    db = await connectMysql();

    if (body.mode === "backfill") {
      const n = await backfill(db);
      return Response.json({ ok: true, mode: "backfill", synced: n });
    }

    // Database Webhook payload.
    const type = body.type as string; // INSERT | UPDATE | DELETE
    if (type === "DELETE") {
      const old = body.old_record as DirectoryApp | undefined;
      if (old?.id) await deleteRow(db, old.id);
      return Response.json({ ok: true, type, id: old?.id ?? null });
    }
    const rec = body.record as DirectoryApp | undefined;
    if (!rec?.id) return new Response("no record", { status: 400 });
    await upsertRow(db, rec);
    return Response.json({ ok: true, type: type ?? "UPSERT", id: rec.id });
  } catch (e) {
    return Response.json({ ok: false, error: String(e) }, { status: 500 });
  } finally {
    if (db) await db.close();
  }
});
