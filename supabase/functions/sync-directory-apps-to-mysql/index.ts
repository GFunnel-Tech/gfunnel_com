// GFunnel — one-way sync: Supabase Postgres -> UNA MySQL (application directory).
//
// The "owned code" half of the pipeline. The Supabase MCP is the front door for
// get/create/update against Postgres; THIS function mirrors each change down to
// the MySQL tables the UNA app reads. Postgres is the single source of truth;
// MySQL is a downstream replica for the app-directory domain only. Not
// bidirectional — no conflict resolution, no echo loops.
//
// Handles all five source tables via one registry (same "common build" shape
// as the rest of the system):
//   directory_apps     -> gf_directory_apps
//   platform_apps      -> gf_platform_apps
//   app_tutorials      -> gf_app_tutorials
//   app_docs           -> gf_app_docs
//   app_help_articles  -> gf_app_help_articles
//
// Modes:
//   1. Webhook (default): a Supabase Database Webhook on any of the tables POSTs
//      {type, table, record, old_record}. We dispatch by `table` and upsert
//      (INSERT/UPDATE) or delete (DELETE) the affected row.
//   2. Backfill: POST {"mode":"backfill","table":"platform_apps"} (or
//      "table":"all") to page through and upsert existing rows.
//
// Secrets: MYSQL_HOST, MYSQL_PORT, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE,
//          SYNC_SHARED_SECRET, SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY.
//
// Deploy (with sign-off): Supabase MCP `deploy_edge_function`, or
//   `supabase functions deploy sync-directory-apps-to-mysql --no-verify-jwt`.

import { createClient } from "jsr:@supabase/supabase-js@2";
import { Client as MySQLClient } from "https://deno.land/x/mysql@v2.12.1/mod.ts";

const env = (k: string): string => Deno.env.get(k) ?? "";

// -- value coercers (Postgres -> MySQL) -------------------------------------
const tsToMysql = (v: unknown): string | null => {
  if (!v) return null;
  const d = new Date(v as string);
  return isNaN(d.getTime()) ? null : d.toISOString().slice(0, 19).replace("T", " ");
};
const boolToTiny = (v: unknown): number => (v ? 1 : 0);
const jsonText = (v: unknown): string | null =>
  v === null || v === undefined ? null : JSON.stringify(v); // arrays/jsonb -> text
const asText = (v: unknown): string | null => (v === null || v === undefined ? null : String(v));
const asNum = (v: unknown): number | null => (v === null || v === undefined || v === "" ? null : Number(v));

type Col = { name: string; map: (v: unknown) => unknown };

// -- table registry: source table -> mirror table + column mapping ----------
const SYNCS: Record<string, { mirror: string; cols: Col[] }> = {
  directory_apps: {
    mirror: "gf_directory_apps",
    cols: [
      { name: "id", map: asText },
      { name: "platform_app_id", map: asText },
      { name: "name", map: (v) => asText(v) ?? "" },
      { name: "slug", map: asText },
      { name: "description", map: asText },
      { name: "logo_url", map: asText },
      { name: "app_url", map: asText },
      { name: "category", map: asText },
      { name: "access_type", map: (v) => asText(v) ?? "free" },
      { name: "is_featured", map: boolToTiny },
      { name: "is_gfunnel_native", map: boolToTiny },
      { name: "created_at", map: tsToMysql },
    ],
  },
  platform_apps: {
    mirror: "gf_platform_apps",
    cols: [
      { name: "id", map: asText },
      { name: "zapier_key", map: asText },
      { name: "name", map: (v) => asText(v) ?? "" },
      { name: "slug", map: asText },
      { name: "app_url", map: asText },
      { name: "logo_url", map: asText },
      { name: "tagline", map: asText },
      { name: "description", map: asText },
      { name: "gfunnel_description", map: asText },
      { name: "categories", map: jsonText },
      { name: "departments", map: jsonText },
      { name: "gfunnel_use_cases", map: jsonText },
      { name: "automation_ideas", map: jsonText },
      { name: "related_app_slugs", map: jsonText },
      { name: "popularity_rank", map: asNum },
      { name: "is_featured", map: boolToTiny },
      { name: "seo_title", map: asText },
      { name: "seo_description", map: asText },
      { name: "created_at", map: tsToMysql },
      { name: "updated_at", map: tsToMysql },
    ],
  },
  app_tutorials: {
    mirror: "gf_app_tutorials",
    cols: [
      { name: "id", map: asText },
      { name: "platform_app_id", map: asText },
      { name: "youtube_video_id", map: asText },
      { name: "title", map: asText },
      { name: "channel_name", map: asText },
      { name: "duration_seconds", map: asNum },
      { name: "thumbnail_url", map: asText },
      { name: "published_at", map: tsToMysql },
      { name: "view_count", map: asNum },
    ],
  },
  app_docs: {
    mirror: "gf_app_docs",
    cols: [
      { name: "id", map: asText },
      { name: "platform_app_id", map: asText },
      { name: "title", map: asText },
      { name: "url", map: asText },
      { name: "doc_type", map: asText },
    ],
  },
  app_help_articles: {
    mirror: "gf_app_help_articles",
    cols: [
      { name: "id", map: asText },
      { name: "platform_app_id", map: asText },
      { name: "title", map: asText },
      { name: "content", map: asText },
      { name: "url", map: asText },
      { name: "category", map: asText },
    ],
  },
};

function upsertSql(mirror: string, cols: Col[]): string {
  const names = cols.map((c) => c.name);
  const placeholders = names.map(() => "?").join(",");
  const updates = names
    .filter((n) => n !== "id")
    .map((n) => `${n}=VALUES(${n})`)
    .concat("synced_at=UTC_TIMESTAMP()")
    .join(", ");
  return `INSERT INTO ${mirror} (${names.join(",")}, synced_at)
          VALUES (${placeholders}, UTC_TIMESTAMP())
          ON DUPLICATE KEY UPDATE ${updates}`;
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

async function upsert(db: MySQLClient, table: string, record: Record<string, unknown>) {
  const s = SYNCS[table];
  const values = s.cols.map((c) => c.map(record[c.name]));
  await db.execute(upsertSql(s.mirror, s.cols), values);
}

async function remove(db: MySQLClient, table: string, id: unknown) {
  await db.execute(`DELETE FROM ${SYNCS[table].mirror} WHERE id = ?`, [asText(id)]);
}

async function backfill(db: MySQLClient, table: string): Promise<number> {
  const sb = createClient(env("SUPABASE_URL"), env("SUPABASE_SERVICE_ROLE_KEY"));
  const pageSize = 1000;
  let from = 0, total = 0;
  while (true) {
    const { data, error } = await sb.from(table).select("*").order("id", { ascending: true }).range(from, from + pageSize - 1);
    if (error) throw new Error(`source read failed (${table}): ${error.message}`);
    if (!data || data.length === 0) break;
    for (const row of data) await upsert(db, table, row as Record<string, unknown>);
    total += data.length;
    if (data.length < pageSize) break;
    from += pageSize;
  }
  return total;
}

Deno.serve(async (req) => {
  if (req.headers.get("x-sync-secret") !== env("SYNC_SHARED_SECRET")) {
    return new Response("forbidden", { status: 403 });
  }
  let body: Record<string, unknown> = {};
  try { body = await req.json(); } catch { return new Response("bad request", { status: 400 }); }

  let db: MySQLClient | null = null;
  try {
    db = await connectMysql();

    if (body.mode === "backfill") {
      const which = (body.table as string) || "all";
      const tables = which === "all" ? Object.keys(SYNCS) : [which];
      const result: Record<string, number> = {};
      for (const t of tables) {
        if (!SYNCS[t]) return Response.json({ ok: false, error: `unknown table ${t}` }, { status: 400 });
        result[t] = await backfill(db, t);
      }
      return Response.json({ ok: true, mode: "backfill", synced: result });
    }

    // Database Webhook payload.
    const table = body.table as string;
    if (!table || !SYNCS[table]) return Response.json({ ok: false, error: `unhandled table ${table}` }, { status: 400 });
    const type = body.type as string; // INSERT | UPDATE | DELETE
    if (type === "DELETE") {
      const old = body.old_record as Record<string, unknown> | undefined;
      if (old?.id) await remove(db, table, old.id);
      return Response.json({ ok: true, table, type, id: old?.id ?? null });
    }
    const rec = body.record as Record<string, unknown> | undefined;
    if (!rec?.id) return new Response("no record", { status: 400 });
    await upsert(db, table, rec);
    return Response.json({ ok: true, table, type: type ?? "UPSERT", id: rec.id });
  } catch (e) {
    return Response.json({ ok: false, error: String(e) }, { status: 500 });
  } finally {
    if (db) await db.close();
  }
});
