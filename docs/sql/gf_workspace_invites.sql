-- GFunnel workspace invite codes (phase 2).
-- Run once on the live database. The workspaces page detects the table and
-- enables the invite features automatically; without it they stay hidden.

CREATE TABLE IF NOT EXISTS `gf_workspace_invites` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `workspace_id` int unsigned NOT NULL,               -- sys_profiles.id of the org/space/group
  `code` varchar(8) NOT NULL,                          -- 8 chars, alphabet ABCDEFGHJKMNPQRSTUVWXYZ23456789
  `email` varchar(255) NOT NULL DEFAULT '',            -- set for email-addressed invites
  `role` varchar(32) NOT NULL DEFAULT 'member',        -- member | admin
  `type` varchar(16) NOT NULL DEFAULT 'permanent',     -- permanent | email | limited
  `status` varchar(16) NOT NULL DEFAULT 'pending',     -- pending | accepted | declined | revoked
  `max_uses` int unsigned NOT NULL DEFAULT 0,          -- 0 = unlimited
  `uses` int unsigned NOT NULL DEFAULT 0,
  `expires_at` int unsigned NOT NULL DEFAULT 0,        -- unixtime, 0 = never
  `created_by` int unsigned NOT NULL DEFAULT 0,        -- profile id
  `created_at` int unsigned NOT NULL DEFAULT 0,
  `accepted_by` int unsigned NOT NULL DEFAULT 0,
  `accepted_at` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `workspace_status` (`workspace_id`, `status`),
  KEY `email_status` (`email`(64), `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
