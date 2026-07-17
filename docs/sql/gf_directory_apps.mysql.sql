-- GFunnel — MySQL mirrors for the Application Directory.
-- Source of truth is Supabase Postgres; these tables are downstream replicas the
-- UNA app reads from (see gf_directory.php). gf_directory.php auto-creates them
-- on first load, so this file is a reference / manual-provision copy.
--
-- Type mapping (Postgres -> MySQL):
--   uuid  -> char(36) | text -> varchar/text | boolean -> tinyint(1)
--   timestamptz -> datetime (UTC) | text[]/jsonb -> text (JSON)

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
  PRIMARY KEY (`id`), KEY `slug` (`slug`), KEY `category` (`category`), KEY `is_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `gf_platform_apps` (
  `id` char(36) NOT NULL,
  `zapier_key` varchar(191) DEFAULT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `slug` varchar(255) DEFAULT NULL,
  `app_url` varchar(2048) DEFAULT NULL,
  `logo_url` varchar(2048) DEFAULT NULL,
  `tagline` varchar(512) DEFAULT NULL,
  `description` text,
  `gfunnel_description` text,
  `categories` text,
  `departments` text,
  `gfunnel_use_cases` text,
  `automation_ideas` text,
  `related_app_slugs` text,
  `popularity_rank` int(11) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `seo_title` varchar(512) DEFAULT NULL,
  `seo_description` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `synced_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `gf_app_tutorials` (
  `id` char(36) NOT NULL,
  `platform_app_id` char(36) DEFAULT NULL,
  `youtube_video_id` varchar(191) DEFAULT NULL,
  `title` varchar(512) DEFAULT NULL,
  `channel_name` varchar(255) DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  `thumbnail_url` varchar(2048) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `view_count` bigint(20) DEFAULT NULL,
  `synced_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `platform_app_id` (`platform_app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `gf_app_docs` (
  `id` char(36) NOT NULL,
  `platform_app_id` char(36) DEFAULT NULL,
  `title` varchar(512) DEFAULT NULL,
  `url` varchar(2048) DEFAULT NULL,
  `doc_type` varchar(64) DEFAULT NULL,
  `synced_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `platform_app_id` (`platform_app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `gf_app_help_articles` (
  `id` char(36) NOT NULL,
  `platform_app_id` char(36) DEFAULT NULL,
  `title` varchar(512) DEFAULT NULL,
  `content` text,
  `url` varchar(2048) DEFAULT NULL,
  `category` varchar(191) DEFAULT NULL,
  `synced_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `platform_app_id` (`platform_app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
