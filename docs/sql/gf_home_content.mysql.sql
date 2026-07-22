-- GFunnel — MySQL tables that drive the homepage News/guides and Community feeds.
--
-- The homepage renderers (home.php: gfHomeNewsFeed / gfHomeCommunityFeed) read these
-- tables when present and fall back to a designed empty state otherwise. The read-plane
-- sources (`content_objects`, `posts_posts`) are not anon-readable and not mirrored
-- (see docs/audits/homepage-audit.md B4/B5), so these local tables are the reachable,
-- editable source for the public PHP homepage.
--
-- HOW TO ADD AN ARTICLE (shows up immediately on the homepage, newest first):
--   INSERT INTO `gf_content_objects` (`id`,`title`,`excerpt`,`canonical_url`,`status`,`published_at`)
--   VALUES (UUID(), 'Your headline', 'One-line summary.', 'https://gfunnel.com/news/slug', 'published', NOW());
-- Only rows with status='published' render. Set canonical_url to make the card a link
-- (leave it empty for a non-linked entry). Column names mirror the Supabase
-- `content_objects` type so a future sync (TODO T2) can populate this table directly.

CREATE TABLE IF NOT EXISTS `gf_content_objects` (
  `id` char(36) NOT NULL,
  `title` varchar(512) NOT NULL DEFAULT '',
  `slug` varchar(255) DEFAULT NULL,
  `excerpt` text,
  `body_md` mediumtext,
  `cover_image_url` varchar(2048) DEFAULT NULL,
  `canonical_url` varchar(2048) DEFAULT NULL,
  `content_type` varchar(64) DEFAULT 'article',
  `status` varchar(32) NOT NULL DEFAULT 'draft',
  `author_profile_id` varchar(191) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `synced_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status_published` (`status`, `published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- HOW TO ADD A COMMUNITY HIGHLIGHT:
--   INSERT INTO `gf_community_posts` (`id`,`title`,`author_name`,`url`,`status`,`published_at`)
--   VALUES (UUID(), 'What someone shared', 'Author Name', 'https://gfunnel.com/p/123', 'active', NOW());
-- Only rows with status='active' render, newest first. Column shape mirrors the
-- Supabase `posts_posts` feed so a future mirror (TODO T3) can populate it directly.

CREATE TABLE IF NOT EXISTS `gf_community_posts` (
  `id` char(36) NOT NULL,
  `title` varchar(512) NOT NULL DEFAULT '',
  `excerpt` text,
  `author_name` varchar(255) DEFAULT NULL,
  `url` varchar(2048) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `published_at` datetime DEFAULT NULL,
  `synced_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status_published` (`status`, `published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
