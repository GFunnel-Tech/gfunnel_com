<?php
/**
 * GFunnel one-off migration: place the structured overview service blocks onto
 * the profile pages, so the structured look renders without hand-adding each
 * block in Studio.
 *
 *   Persons              -> service bx_persons / overview
 *   Organizations/Spaces/Groups -> service <module> / overview_structured
 *
 * The block is inserted at the top of the main cell (cell 1), content-only (no
 * caption box), idempotent (re-running does nothing). It resolves each page
 * object at runtime from the module's CNF, so nothing is hard-coded.
 *
 * It does NOT hide the native cover/header blocks: on a live page that would be
 * a blind guess. Instead it PRINTS every block already on each page (id, cell,
 * order, type, title, active) and the page cover flag - share that output (or
 * check in-browser) and the exact one-line deactivations can be added here.
 *
 * Run:  php custom_batch_updates/gf_place_overview_blocks.php
 * Undo: delete the inserted rows (their ids are printed), or in Studio Page
 *       Builder remove the "Overview (GFunnel)" block.
 */

require_once(dirname(__DIR__) . '/inc/header.inc.php');

$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';

$oDb = BxDolDb::getInstance();

$iDesignbox = defined('BX_DB_CONTENT_ONLY') ? BX_DB_CONTENT_ONLY : 0; // content only, no caption box

$aTargets = array(
    array('module' => 'bx_persons',       'method' => 'overview',            'title' => 'Profile overview (GFunnel)'),
    array('module' => 'bx_organizations', 'method' => 'overview_structured', 'title' => 'Workspace overview (GFunnel)'),
    array('module' => 'bx_spaces',        'method' => 'overview_structured', 'title' => 'Workspace overview (GFunnel)'),
    array('module' => 'bx_groups',        'method' => 'overview_structured', 'title' => 'Workspace overview (GFunnel)'),
);

function gfLine($s = '') { echo $s . "\n"; }

foreach($aTargets as $aTarget) {
    $sModule = $aTarget['module'];
    gfLine(str_repeat('=', 64));
    gfLine('MODULE: ' . $sModule . '  (service ' . $aTarget['method'] . ')');

    $oModule = BxDolModule::getInstance($sModule);
    if(!$oModule) { gfLine('  ! module not installed - skipped'); continue; }

    $CNF = &$oModule->_oConfig->CNF;
    $sUri = !empty($CNF['URI_VIEW_ENTRY']) ? $CNF['URI_VIEW_ENTRY'] : '';
    if($sUri === '') { gfLine('  ! no URI_VIEW_ENTRY - skipped'); continue; }

    // Resolve the profile page object from its uri.
    $sObject = $oDb->getOne($oDb->prepare("SELECT `object` FROM `sys_objects_page` WHERE `uri` = ?", $sUri));
    if(empty($sObject)) { gfLine('  ! no page object for uri "' . $sUri . '" - skipped'); continue; }

    $iCover = (int)$oDb->getOne($oDb->prepare("SELECT `cover` FROM `sys_objects_page` WHERE `object` = ?", $sObject));
    gfLine('  page object : ' . $sObject . '   (uri ' . $sUri . ', page cover flag = ' . $iCover . ')');

    // --- Current blocks on this page (diagnostic; nothing is changed here). ---
    gfLine('  existing blocks (id | cell | order | active | type | title):');
    $aBlocks = $oDb->getAll($oDb->prepare("SELECT `id`,`cell_id`,`order`,`active`,`type`,`title` FROM `sys_pages_blocks` WHERE `object` = ? ORDER BY `cell_id`,`order`", $sObject));
    foreach($aBlocks as $aB)
        gfLine(sprintf('      #%-5s c%-2s o%-3s a%-1s  %-10s %s', $aB['id'], $aB['cell_id'], $aB['order'], $aB['active'], $aB['type'], $aB['title']));

    // --- Insert the overview service block at the top of cell 1 (idempotent). ---
    $sContent = BxDolService::getSerializedService($sModule, $aTarget['method'], array(), '');

    $bExists = (bool)$oDb->getOne($oDb->prepare("SELECT `id` FROM `sys_pages_blocks` WHERE `object` = ? AND `type` = 'service' AND `title` = ?", $sObject, $aTarget['title']));
    if($bExists) {
        gfLine('  = block already present - left as is');
        continue;
    }

    $aRow = array(
        'object'       => $sObject,
        'cell_id'      => 1,
        'order'        => 0,          // sort first in the cell
        'active'       => 1,
        'active_api'   => 1,
        'type'         => 'service',
        'designbox_id' => $iDesignbox,
        'title'        => $aTarget['title'],
        'content'      => $sContent,
    );
    $bOk = $oDb->query("INSERT INTO `sys_pages_blocks` SET " . $oDb->arrayToSQL($aRow));
    gfLine($bOk ? ('  + inserted block id ' . $oDb->lastId() . ' (cell 1, order 0)') : '  ! INSERT failed');
}

gfLine(str_repeat('=', 64));
gfLine('Done. Reload a profile page to see the structured block at the top.');
gfLine('If the native cover/header now duplicates it, share the "existing blocks"');
gfLine('list above (or set the page cover flag to 0) and the exact hide can be added.');
