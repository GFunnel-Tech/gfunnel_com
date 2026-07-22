# Homepage → module mapping (reuse vs. clone)

Principle: **use an existing module if it fits; otherwise clone the closest one and follow
its structure** (the `modzzz` suite — `mz_listing`, `mz_jobs`, `mz_news` — are already
built this way, so cloning is low-risk). Each homepage section reads a real module and
degrades gracefully if that module isn't installed.

| Homepage capability | Existing module (table) | Fit? | Action |
|---|---|---|---|
| **Business Database** | `mz_listing` — Business Listing (`mz_listing_entries`, `mz_listing_claims`) | ✅ | **Reuse — wired.** Count, featured cards, `claim_status`, claim CTA → `listing-claim`. |
| **Marketplace** (templates, snapshots, paid software) | `bx_market` — Market (`bx_market_products`) | ✅ | **Reuse — wired.** Featured products, price, → `view-product` / `market-home`. |
| **Software / apps / integrations** | app directory mirror (`gf_directory_apps`) + `/applications` | ✅ | **Reuse — wired.** Featured apps + count. |
| **Resources — News** | `mz_news` — News | ✅ | **Reuse (next).** Point the News feed at `mz_news` instead of the generic content table. |
| **Resources — articles/guides** | Blogs (`bx_posts`/Blogs) + `bx_courses` — Courses | ✅ | **Reuse (next).** Latest from Blogs; "Browse courses" → `courses-home` (already linked). |
| **Community** | Timeline (`posts_posts`) + Groups + Channels + Spaces | ✅ | **Reuse (next).** Wire the community feed to Timeline / Groups. |
| **Events** | `bx_events` — Events | ✅ | Available — add an Events strip if wanted. |
| **Departments** | (Next.js/Supabase native) → `gf_departments` MySQL / static snapshot | ◑ | **Reuse — wired.** No UNA module; table + fallback. |
| **Partners / affiliate** | Partner Program module | ✅ | Wire the Earn/Partners destination when public. |
| **Verified Vendors** | `mz_listing` | ✅ *extend* | **Extend, don't clone:** add a `verified` field + an admin verification step to `mz_listing`; filter/badge on it. |
| **VA / Talent marketplace** (hire/verify/manage/pay VAs) | `mz_jobs` — Jobs (closest) | ⚠ | **Decide:** reuse `mz_jobs` for gig/talent, **or** clone `mz_jobs` (or `mz_listing`) into a dedicated `mz_talent` module following the same structure if the flow (verify, book, pay) diverges. |
| **Industry Snapshots** | `bx_market` (as products) | ✅ | Model snapshots as Market products / a category; deploy-to-workspace on purchase. |

## The two real gaps (everything else is reuse)

1. **Verified Vendors** — no new module. Add `verified` (+ verified_at / verifier) to
   `mz_listing`, an admin/claim verification action, and a "Verified" badge + filter.
   Vendors are just businesses that passed vetting.

2. **VA / Talent marketplace** — `mz_jobs` is the closest existing module. If its
   post-a-job / apply flow covers "hire a VA," reuse it. If we need gig packages +
   verification + booking + payout, **clone `mz_jobs` → `mz_talent`** (copy the module,
   rename `mz_jobs_*` → `mz_talent_*`, adjust fields/forms) — same proven structure,
   isolated from Jobs.

## Cloning checklist (when a new module is justified)

Copy `modules/modzzz/<closest>` → `modules/modzzz/<new>` and, following that module's
own files: rename in `install/config.php` (`name`, `home_uri`, `db_prefix`,
`class_prefix`), rename the `classes/Bx…`/`Mz…` class files + class names, update
`install/sql` table prefixes, and `install/langs`. Install/enable in Studio, verify, then
add a homepage block renderer + a `serviceBlock*` in `modules/gfunnel/home` so it appears
as a Page-Builder block like the others.
