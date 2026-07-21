-- GFunnel — MySQL table for the homepage department grid (process domains).
--
-- Mirrors the platform's `departments` table (Supabase read plane, project
-- yjneucgsaayyzoyxrlnb). That source table is membership-scoped (not anon-readable)
-- and is not synced to MySQL, so the public PHP homepage cannot read it at request
-- time. Loading this table makes home.php's department grid render live from MySQL;
-- until it exists, home.php falls back to the same values as a static snapshot
-- (gfHomeDepartmentsStatic). See docs/audits/homepage-audit.md (B1 / TODO T1).
--
-- Type mapping (Postgres -> MySQL):
--   uuid -> char(36) | text -> varchar/text | integer -> int
--
-- Seed rows below are the real 14 departments (ids + values from the source table).
-- Safe to re-run: the INSERTs upsert on primary key.

CREATE TABLE IF NOT EXISTS `gf_departments` (
  `id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `subtitle` varchar(512) DEFAULT NULL,
  `icon` varchar(128) DEFAULT NULL,
  `color` varchar(32) DEFAULT NULL,
  `color_bg` varchar(64) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `organization_id` char(36) DEFAULT NULL,
  `synced_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `gf_departments`
  (`id`, `name`, `subtitle`, `icon`, `color`, `color_bg`, `sort_order`)
VALUES
  ('c72d18c9-e4cf-4d63-846c-ac9ab05ca6f6', 'Strategy',                   'Planning, KPIs',                 'ti-target',          '#4338ca', 'rgba(99,102,241,.12)',  1),
  ('99f201f1-ddab-499b-945f-2b16ef88f652', 'Marketing',                  'Demand gen, brand, paid',        'ti-speakerphone',    '#be123c', 'rgba(244,63,94,.12)',   2),
  ('04328b72-90e5-4293-81b3-fab5a7204a3e', 'Sales / Revenue',            'Sales, Partnerships',            'ti-trending-up',     '#047857', 'rgba(16,185,129,.12)',  3),
  ('aa1a3326-6b74-43a8-855a-9b13613976eb', 'Product',                    'Roadmap, UX',                    'ti-box',             '#1d4ed8', 'rgba(59,130,246,.12)',  4),
  ('c33ee641-e520-4b3b-8a7a-ded07a1640bd', 'Creative',                   'Design, Content',                'ti-palette',         '#be185d', 'rgba(236,72,153,.12)',  5),
  ('1eef0b8f-b9b9-49e6-a73f-dca837d29397', 'Technology',                 'Dev, Integrations',              'ti-cpu',             '#6d28d9', 'rgba(124,58,237,.12)',  6),
  ('9028714a-3008-4b8c-ac89-b7f2c0aefeda', 'AI & Automation',            'Agents, Chatbots',               'ti-robot',           '#0f766e', 'rgba(20,184,166,.12)',  7),
  ('1f927a99-0e90-4928-9d9a-82fd0c1adb59', 'Delivery / Fulfillment',     'Service delivery, PM',           'ti-truck-delivery',  '#b45309', 'rgba(245,158,11,.14)',  8),
  ('51b2c1e3-4cf0-4c49-93f6-4ba3bf8eb7ad', 'Operations',                 'CRM, Workflows, RevOps',         'ti-settings',        '#475569', 'rgba(100,116,139,.14)', 9),
  ('a9b3d0d4-d6f5-425b-aaed-66d10f0d91f4', 'Data & Analytics',           'BI, Attribution',                'ti-chart-bar',       '#0e7490', 'rgba(6,182,212,.12)',   10),
  ('5992a81b-bd5b-49c6-a65c-d9dc0592c062', 'Customer Success & Support', 'Onboarding, Success',            'ti-headset',         '#7c3aed', 'rgba(124,58,237,.12)',  11),
  ('932cb093-319a-4c36-bac0-d8515786394e', 'People / HR',                'Recruiting, Culture, Payroll',   'ti-users',           '#c2410c', 'rgba(249,115,22,.12)',  12),
  ('67692f41-20b6-4281-a343-92ea9db1aead', 'Finance',                    'Billing, Accounting',            'ti-currency-dollar', '#059669', 'rgba(16,185,129,.12)',  13),
  ('2cd3987f-8dfb-4790-8058-ab73b72fd8be', 'Legal, Risk & Security',     'Contracts, Compliance, InfoSec', 'ti-shield',          '#334155', 'rgba(51,65,85,.12)',    14)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`), `subtitle` = VALUES(`subtitle`), `icon` = VALUES(`icon`),
  `color` = VALUES(`color`), `color_bg` = VALUES(`color_bg`), `sort_order` = VALUES(`sort_order`);
