# GFunnel — Directory & Provisioning: Target Model & Build Plan

Status: proposed target. Consolidates the tangled app-catalog / assignment schema
(see `directorymarketplacedatamodel.md`) into one coherent model, then lays out the
ordered PRs to make it fully functional.

Implementation home: the **Supabase + Next.js** app (this is where the catalogs,
hooks, edge functions, and auth live). The UNA/PHP `/applications` page remains a
**public, read-only mirror** for logged-out visitors only.

---

## 1. Principles

1. **One catalog per resource type.** No more three overlapping app catalogs.
2. **One provisioning engine for everything.** The org→dept→role→user cascade
   provisions *any* resource type — apps, SOPs, trainings, courses, documents —
   through a single `assignments` table and a single resolver.
3. **Identity is unified.** `organizations.id === profiles.id`; a person and an org
   are the same identity type. An individual provisioning their own apps is just a
   one-member org. No separate "personal vs org" code path.
4. **Catalog ≠ Marketplace.** The free catalog (browse + add) is separate from the
   paid Marketplace (listings + purchases + reviews). They stay distinct.
5. **Referential integrity in the resolver.** The polymorphic `resource_id` is
   validated in the app layer (documented trade-off for "build once, provision all").

---

## 2. Identity layer (unchanged — the anchor)

| Table | Role |
|---|---|
| `profiles` | Master identity. PK `id`. `profile_type` = person \| organization. |
| `organizations` | 1:1 extension: `organizations.id === profiles.id`. |
| `org_members` | Membership graph. Carries `department` (text) and `role` (text) per member — this is what the cascade resolves against. |

No changes here beyond confirming `org_members.department` / `role` are the canonical
placement fields.

---

## 3. Catalogs (one per resource type)

| resource_type | Canonical catalog | Children | Notes |
|---|---|---|---|
| `app` | **`platform_apps`** (5,000 enriched) | `app_tutorials`, `app_docs`, `app_help_articles` | The one app catalog. |
| `sop` | `sops` (new, or `content_articles` w/ type) | revisions | The knowledge/SOP pillar. |
| `training` / `course` | `content_objects` (content_type='course') | lessons | Existing content system. |
| `document` | `documents` | — | Files/resources. |

**Demoted / retired:**
- `directory_apps` → **demoted to a curated view** over `platform_apps`
  (a `is_curated` / `hub_featured` flag + optional ordering), *not* a second catalog.
  Live shape is the minimal v2 (`platform_app_id`, `slug`, `logo_url`, `app_url`,
  `access_type`, `is_gfunnel_native`) — reconcile toward "curated pointer to
  `platform_apps`."
- `app_directory` (Catalog C) → **catalog role retired**; keep only its
  **AI-telemetry ring** (`app_connection_data`, `app_events`, `app_connection_health`,
  `app_dept_mapping`) which feeds the assistant. Its catalog columns are superseded by
  `platform_apps`.

---

## 4. The unified assignment cascade (the core)

### Table

```sql
create table assignments (
  id            uuid primary key default gen_random_uuid(),
  org_id        uuid not null references profiles(id) on delete cascade, -- owning identity (person OR org)
  scope         text not null check (scope in ('org','department','role','user')),
  scope_ref     text,           -- null for 'org'; dept name | role | member_profile_id otherwise
  resource_type text not null check (resource_type in ('app','sop','training','course','document')),
  resource_id   uuid not null,  -- validated in app layer (polymorphic; no FK)
  source        text not null default 'manual'
                  check (source in ('org_default','plan','directory','manual','bundle')),
  is_required   boolean not null default false,
  is_locked     boolean not null default false,  -- member can't remove
  created_by    uuid references profiles(id),
  created_at    timestamptz not null default now(),
  unique (org_id, scope, scope_ref, resource_type, resource_id)
);
```

### Resolver (one function, every resource type)

```sql
-- Effective resources for a member, of a given type.
create or replace function effective_resources(p_member uuid, p_type text)
returns setof uuid language sql stable as $$
  select distinct a.resource_id
  from org_members m
  join assignments a
    on a.org_id = m.org_id
   and a.resource_type = p_type
   and (
        a.scope = 'org'
     or (a.scope = 'department' and a.scope_ref = m.department)
     or (a.scope = 'role'       and a.scope_ref = m.role)
     or (a.scope = 'user'       and a.scope_ref = m.member_profile_id::text)
   )
  where m.member_profile_id = p_member;
$$;
```

- **Individual** (no org): they are their own org (`org_id = own profile id`,
  a single `org_members` row, or a `user`-scope row keyed to self). Same query.
- The `+` button in the App Directory writes a `('user', member_profile_id, 'app', …,
  source='directory')` row. Org admins write `org`/`department`/`role` scope rows.
- `is_locked` = admin-assigned, member can't remove. `is_required` = always on.

### Replaces (delete after migration)

| Old | New |
|---|---|
| `org_app_assignments` | `assignments` scope='org' |
| `department_app_assignments` **and** `department_app_mappings` | `assignments` scope='department' |
| `role_app_assignments` **and** `role_app_mappings` | `assignments` scope='role' |
| `user_app_assignments` (v1 **and** v2) | `assignments` scope='user' |

This kills the forked `*_mappings` vs `*_assignments` split (UI wrote one, resolver read
the other) and the two `user_app_assignments` shapes.

---

## 5. Marketplace (kept separate, unchanged)

`marketplace_listings` / `marketplace_purchases` / `marketplace_reviews` stay as the
paid engine (30/70 split, Stripe, review-trigger). Route `/marketplace`. The App
Directory `/applications` never touches these. Installing a *purchased* module may
create an `assignments` row (source='bundle'/'plan') — that's the only bridge.

---

## 6. PR roadmap (ordered, dependency-aware)

Legend: **[D]** data/migration · **[B]** backend/API · **[F]** frontend · risk noted.

### Phase 1 — Data foundation
- **PR 1 [D] · Unified `assignments` table + resolver.** Create table + `effective_resources()`. RLS: members read their org's rows; admins (owner/admin) write. *No deletes yet.* Low risk (additive).
- **PR 2 [D] · Backfill into `assignments`.** Migrate `org_app_assignments`, both `user_app_assignments` defs, and `*_assignments`/`*_mappings` into `assignments`. Verify counts. Medium risk (data move; keep sources until PR 8).
- **PR 3 [D] · Catalog consolidation.** Make `platform_apps` canonical; convert `directory_apps` to a curated flag/view over it; retire `app_directory` catalog columns (keep telemetry). Backfill curated set. Medium risk.

### Phase 2 — Backend
- **PR 4 [B] · Provisioning API.** One resolver endpoint (`GET effective apps/sops/... for me`) replacing the two conflicting resolvers. Add/remove personal assignment; org/dept/role assignment for admins; enforce `is_locked`/`is_required`. Low-med risk.
- **PR 5 [B] · Catalog read API.** Single `usePlatformApps`-backed catalog endpoint (list + filter by category + search + detail with tutorials/docs/help). Deprecate `useAppDirectory`/`directory_apps` reads. Low risk.

### Phase 3 — Frontend (the screenshot)
- **PR 6 [F] · App Directory page (authenticated).** `/applications` — Apps tab, category pills (All, My Plan, Popular, + categories), search, cards with `+`/open. "My Plan" = `effective_resources('app')`. `+` calls the add API. In-app shell. Med risk.
- **PR 7 [F] · Marketplace separation + dead-link cleanup.** Ensure `/applications` (catalog+add) vs `/marketplace` (paid) are distinct; fix orphaned views/dead links noted in the audit. Low risk.

### Phase 4 — Generalize + public + cleanup
- **PR 8 [D] · Retire forked tables.** After PRs 1–7 ship and nothing reads them: drop `*_mappings`, the duplicate `user_app_assignments`/`org_app_assignments`, and `app_directory` catalog cols. Reversible via backup. Med risk (destructive — gated on zero references).
- **PR 9 [D+B+F] · Generalize to SOPs & Trainings.** Add `sop`/`training` catalogs; surface them through the *same* resolver + assignment UI. Proves "build once, provision everything." Med risk.
- **PR 10 [F] · Public/logged-out experience.** Hero-led public catalog (Next.js public route and/or the UNA `/applications` read-only mirror). SEO/JSON-LD. Low risk.

### Dependency graph
```
PR1 ─┬─ PR2 ─┬───────────── PR8 (retire)
     │       └─ PR4 ─┬─ PR6 ─┬─ PR7
PR3 ─┴─ PR5 ─────────┘       └─ PR9 (generalize)
                                 PR10 (public)  [independent, any time after PR5]
```

---

## 7. What "fully functional" means (acceptance)

- A member sees exactly the apps their org + department + role + personal adds grant.
- Adding an app via `+` persists and shows in "My Plan/Assets" immediately.
- An org admin assigns an app to the "Sales" department; every Sales member gets it;
  the resolver (one, not two) reflects it.
- The same assignment UI, pointed at `resource_type='sop'`, provisions SOPs with zero
  new provisioning code.
- Individuals and orgs use the identical flow.
- `/applications` (catalog) and `/marketplace` (paid) are cleanly separate.
