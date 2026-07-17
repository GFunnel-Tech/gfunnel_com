-- GFunnel — MySQL mirror of Supabase `public.directory_apps`.
-- Source of truth is Postgres; this table is a downstream replica the UNA app
-- reads from (see gf_directory.php). gf_directory.php auto-creates this table on
-- first load, so this file is a reference / manual-provision copy.
--
-- Type mapping (Postgres -> MySQL):
--   uuid           -> char(36)
--   text           -> varchar / text
--   boolean        -> tinyint(1)
--   timestamptz    -> datetime (stored UTC)

CREATE TABLE IF NOT EXISTS `gf_directory_apps` (
  `id` char(36) NOT NULL,
  `platform_app_id` char(36) DEFAULT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `slug` varchar(255) DEFAULT NULL,
  `description` text,
  `logo_url` varchar(2048) DEFAULT NULL,
  `app_url` varchar(2048) DEFAULT NULL,
  `category` varchar(191) DEFAULT NULL,
  `access_type` varchar(64) DEFAULT 'free',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_gfunnel_native` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `synced_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `slug` (`slug`),
  KEY `category` (`category`),
  KEY `is_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
