-- GFunnel — place the structured overview service blocks on the profile pages.
--
-- Prefer the idempotent PHP migration:
--     php custom_batch_updates/gf_place_overview_blocks.php
-- It resolves each page object, prints existing blocks, and won't double-insert.
--
-- This SQL is the manual equivalent ("give me the SQL"). Each statement resolves
-- the page `object` from its uri via sys_objects_page, so no id is hard-coded.
-- Columns: type='service' (dispatch -> _getBlockService), designbox_id=0
-- (BX_DB_CONTENT_ONLY, no caption box), cell_id=1 order=0 (top of main column),
-- content = the platform's serialized service call (module + method).
--
-- Run each block ONCE (these have no idempotency guard — MySQL forbids
-- referencing the target table in a NOT EXISTS on the same INSERT). Re-running
-- duplicates the row; delete extras by id if that happens.
--
-- `module` is required (NOT NULL, no default) — it is the owning module. If
-- your schema has OTHER NOT-NULL columns without a default, prefer the PHP
-- migration: it introspects the table and fills every required column, so it
-- can't fail on a column not named here.

-- Persons -> bx_persons / overview
INSERT INTO `sys_pages_blocks`
    (`object`, `module`, `cell_id`, `order`, `active`, `active_api`, `type`, `designbox_id`, `title`, `content`)
SELECT `object`, 'bx_persons', 1, 0, 1, 1, 'service', 0, 'Profile overview (GFunnel)',
       'a:2:{s:6:"module";s:10:"bx_persons";s:6:"method";s:8:"overview";}'
FROM `sys_objects_page` WHERE `uri` = 'view-persons-profile';

-- Organizations -> bx_organizations / overview_structured
INSERT INTO `sys_pages_blocks`
    (`object`, `module`, `cell_id`, `order`, `active`, `active_api`, `type`, `designbox_id`, `title`, `content`)
SELECT `object`, 'bx_organizations', 1, 0, 1, 1, 'service', 0, 'Workspace overview (GFunnel)',
       'a:2:{s:6:"module";s:16:"bx_organizations";s:6:"method";s:19:"overview_structured";}'
FROM `sys_objects_page` WHERE `uri` = 'view-organization-profile';

-- Spaces -> bx_spaces / overview_structured
INSERT INTO `sys_pages_blocks`
    (`object`, `module`, `cell_id`, `order`, `active`, `active_api`, `type`, `designbox_id`, `title`, `content`)
SELECT `object`, 'bx_spaces', 1, 0, 1, 1, 'service', 0, 'Workspace overview (GFunnel)',
       'a:2:{s:6:"module";s:9:"bx_spaces";s:6:"method";s:19:"overview_structured";}'
FROM `sys_objects_page` WHERE `uri` = 'view-space-profile';

-- Groups -> bx_groups / overview_structured
INSERT INTO `sys_pages_blocks`
    (`object`, `module`, `cell_id`, `order`, `active`, `active_api`, `type`, `designbox_id`, `title`, `content`)
SELECT `object`, 'bx_groups', 1, 0, 1, 1, 'service', 0, 'Workspace overview (GFunnel)',
       'a:2:{s:6:"module";s:9:"bx_groups";s:6:"method";s:19:"overview_structured";}'
FROM `sys_objects_page` WHERE `uri` = 'view-group-profile';

-- NOTE: the workspace uris above (view-organization-profile / view-space-profile
-- / view-group-profile) are the stock defaults; if a module was customised the
-- real uri is CNF['URI_VIEW_ENTRY'] — the PHP migration reads it automatically.
--
-- Removing a block:
--     DELETE FROM `sys_pages_blocks` WHERE `type`='service'
--       AND `title` IN ('Profile overview (GFunnel)','Workspace overview (GFunnel)');
--
-- If the native cover/header now duplicates the block, either hide those blocks
-- in Studio Page Builder, or turn the page cover off:
--     UPDATE `sys_objects_page` SET `cover` = 0 WHERE `uri` = 'view-persons-profile';
