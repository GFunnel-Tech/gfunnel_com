// GFunnel — content monitor: pull YouTube tutorials for platform apps.
//
// The "keep the directory fresh" half of the pipeline. Runs on a schedule
// (pg_cron -> pg_net POST, or any external cron) and, for a batch of apps that
// have few/no tutorials, searches the YouTube Data API for "<app> tutorial"
// videos and upserts them into public.app_tutorials. The existing
// sync-directory-apps-to-mysql function then mirrors those rows down to MySQL
// (gf_app_tutorials), where gf_applications.php renders them on each app's
// detail page. Postgres stays the single source of truth.
//
// Modes (POST JSON):
//   {} or {"limit":25}                 -> refresh the N apps most in need
//   {"app_slugs":["notion","slack"]}   -> refresh specific apps
//   {"max_per_app":4}                  -> how many videos to keep per app (default 4)
//   {"dry_run":true}                   -> fetch + report, write nothing
//
// Auth: header  x-sync-secret: <SYNC_SHARED_SECRET>  (same secret as the sync fn).
// Secrets: SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY, SYNC_SHARED_SECRET,
//          YOUTUBE_API_KEY.
//
// Deploy (with sign-off):
//   supabase functions deploy fetch-app-tutorials --no-verify-jwt
// then set YOUTUBE_API_KEY (and the shared secret) in the project's secrets.

import { createClient } from "jsr:@supabase/supabase-js@2";

const env = (k: string): string => Deno.env.get(k) ?? "";

// ISO8601 duration (PT#H#M#S) -> seconds.
function isoDurationToSeconds(iso: string): number | null {
  if (!iso) return null;
  const m = iso.match(/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/);
  if (!m) return null;
  return (Number(m[1] || 0) * 3600) + (Number(m[2] || 0) * 60) + Number(m[3] || 0);
}

type YtVideo = {
  youtube_video_id: string;
  title: string;
  channel_name: string | null;
  thumbnail_url: string | null;
  published_at: string | null;
  duration_seconds: number | null;
  view_count: number | null;
};

// Search + hydrate one query's worth of videos.
async function youtubeSearch(query: string, max: number): Promise<YtVideo[]> {
  const key = env("YOUTUBE_API_KEY");
  if (!key) throw new Error("YOUTUBE_API_KEY not set");

  const searchUrl = new URL("https://www.googleapis.com/youtube/v3/search");
  searchUrl.search = new URLSearchParams({
    key, part: "snippet", q: query, type: "video", maxResults: String(Math.min(max * 2, 15)),
    relevanceLanguage: "en", videoEmbeddable: "true", safeSearch: "strict", order: "relevance",
  }).toString();

  const sr = await fetch(searchUrl);
  if (!sr.ok) throw new Error(`youtube search ${sr.status}: ${await sr.text()}`);
  const sj = await sr.json();
  const ids: string[] = (sj.items || []).map((it: any) => it?.id?.videoId).filter(Boolean);
  if (!ids.length) return [];

  // Hydrate with contentDetails (duration) + statistics (views).
  const vidUrl = new URL("https://www.googleapis.com/youtube/v3/videos");
  vidUrl.search = new URLSearchParams({
    key, part: "snippet,contentDetails,statistics", id: ids.join(","),
  }).toString();
  const vr = await fetch(vidUrl);
  if (!vr.ok) throw new Error(`youtube videos ${vr.status}: ${await vr.text()}`);
  const vj = await vr.json();

  const out: YtVideo[] = (vj.items || []).map((v: any) => {
    const sn = v.snippet || {};
    const th = sn.thumbnails || {};
    return {
      youtube_video_id: v.id,
      title: sn.title || "",
      channel_name: sn.channelTitle || null,
      thumbnail_url: (th.high || th.medium || th.default || {}).url || null,
      published_at: sn.publishedAt || null,
      duration_seconds: isoDurationToSeconds(v.contentDetails?.duration || ""),
      view_count: v.statistics?.viewCount ? Number(v.statistics.viewCount) : null,
    };
  });
  // Prefer real tutorials: drop shorts (<60s), sort by views, keep `max`.
  return out
    .filter((v) => v.duration_seconds === null || v.duration_seconds >= 60)
    .sort((a, b) => (b.view_count || 0) - (a.view_count || 0))
    .slice(0, max);
}

Deno.serve(async (req) => {
  if (req.headers.get("x-sync-secret") !== env("SYNC_SHARED_SECRET")) {
    return new Response("forbidden", { status: 403 });
  }
  let body: Record<string, unknown> = {};
  try { body = await req.json(); } catch { /* empty body ok */ }

  const limit = Math.min(Number(body.limit) || 25, 100);
  const maxPerApp = Math.min(Number(body.max_per_app) || 4, 8);
  const dryRun = body.dry_run === true;
  const appSlugs = Array.isArray(body.app_slugs) ? (body.app_slugs as string[]) : null;

  const sb = createClient(env("SUPABASE_URL"), env("SUPABASE_SERVICE_ROLE_KEY"));

  // Pick the apps to refresh: explicit slugs, else those with the fewest
  // tutorials (popularity_rank ascending so the best-known apps go first).
  let apps: Array<{ id: string; name: string; slug: string | null }> = [];
  if (appSlugs && appSlugs.length) {
    const { data, error } = await sb.from("platform_apps").select("id,name,slug").in("slug", appSlugs);
    if (error) return Response.json({ ok: false, error: error.message }, { status: 500 });
    apps = data || [];
  } else {
    const { data, error } = await sb
      .from("platform_apps").select("id,name,slug")
      .order("popularity_rank", { ascending: true, nullsFirst: false })
      .limit(limit);
    if (error) return Response.json({ ok: false, error: error.message }, { status: 500 });
    apps = data || [];
  }

  const report: Array<Record<string, unknown>> = [];
  let inserted = 0;

  for (const app of apps) {
    try {
      // Existing videos for this app (idempotency).
      const { data: existing } = await sb
        .from("app_tutorials").select("youtube_video_id").eq("platform_app_id", app.id);
      const seen = new Set((existing || []).map((r: any) => r.youtube_video_id));

      const found = await youtubeSearch(`${app.name} tutorial`, maxPerApp);
      const fresh = found.filter((v) => !seen.has(v.youtube_video_id));

      if (!dryRun && fresh.length) {
        const rows = fresh.map((v) => ({
          id: crypto.randomUUID(),
          platform_app_id: app.id,
          youtube_video_id: v.youtube_video_id,
          title: v.title,
          channel_name: v.channel_name,
          duration_seconds: v.duration_seconds,
          thumbnail_url: v.thumbnail_url,
          published_at: v.published_at,
          view_count: v.view_count,
        }));
        const { error: insErr } = await sb.from("app_tutorials").insert(rows);
        if (insErr) throw new Error(insErr.message);
        inserted += rows.length;
      }
      report.push({ app: app.slug || app.name, found: found.length, added: dryRun ? 0 : fresh.length });
    } catch (e) {
      report.push({ app: app.slug || app.name, error: String(e) });
    }
    // Gentle pacing to stay well under YouTube quota bursts.
    await new Promise((r) => setTimeout(r, 120));
  }

  return Response.json({ ok: true, mode: dryRun ? "dry_run" : "write", apps: apps.length, inserted, report });
});
