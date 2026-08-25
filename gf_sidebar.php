<?php
/**
 * GFunnel — member LEFT-SIDEBAR personalization endpoint (workspace shell rail).
 *
 * Mirrors gf_menu.php (which personalizes the horizontal hub tabs) for the
 * vertical left navigation. Every logged-in member keeps their own version of
 * the sidebar per workspace in gf_sidebar_menu (keyed by account + active
 * workspace from BxTemplFunctions::getGfActiveWorkspaceId). Actions by POST:
 *
 *  a=reorder  v=JSON array of item keys in the new order
 *  a=toggle   i=item key (stock item name, or cN for a member's own link)
 *  a=add      title, url, icon, open_mode (self|blank|iframe)
 *  a=del      i=cN
 *  a=reset    remove every saved choice for this workspace
 *
 * Home and Applications are pinned (gf_sidebar_pinned, default "home,applications"):
 * they can be reordered but never hidden or removed. Response is JSON
 * {code: 0, html: "<re-rendered sidebar>"} so the client swaps the rail in place.
 */

require_once('./inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_INC . "design.inc.php");

bx_import('BxDolLanguages');

header('Content-Type: application/json; charset=utf-8');

if(!isLogged()) {
    echo json_encode(['code' => 1]);
    exit;
}

$oDb = BxDolDb::getInstance();

// Self-provision the store on first use (same pattern as the timer / invites).
if(!$oDb->getOne("SHOW TABLES LIKE 'gf_sidebar_menu'"))
    $oDb->query("CREATE TABLE IF NOT EXISTS `gf_sidebar_menu` (
        `id` int unsigned NOT NULL AUTO_INCREMENT,
        `account_id` int unsigned NOT NULL DEFAULT 0,
        `workspace_id` int unsigned NOT NULL DEFAULT 0,
        `item` varchar(128) NOT NULL DEFAULT '',
        `custom` tinyint unsigned NOT NULL DEFAULT 0,
        `title` varchar(64) NOT NULL DEFAULT '',
        `url` varchar(2048) NOT NULL DEFAULT '',
        `icon` varchar(64) NOT NULL DEFAULT '',
        `open_mode` varchar(8) NOT NULL DEFAULT 'self',
        `order` int NOT NULL DEFAULT 0,
        `hidden` tinyint unsigned NOT NULL DEFAULT 0,
        `added` int unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `account_workspace` (`account_id`, `workspace_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if(!$oDb->getOne("SHOW TABLES LIKE 'gf_sidebar_menu'")) {
    echo json_encode(['code' => 2]);
    exit;
}

$oFunctions = BxTemplFunctions::getInstance();

$iAccount = (int)getLoggedId();
$iWorkspace = (int)$oFunctions->getGfActiveWorkspaceId();

// Pinned stock items (never hidden or removed) - matched case-insensitively.
$aPinned = array_filter(array_map('trim', explode(',', strtolower((string)getParam('gf_sidebar_pinned')))));
if(empty($aPinned))
    $aPinned = ['home', 'applications'];

function gfSidebarIsPinned($sItem, $aPinned)
{
    return in_array(strtolower((string)$sItem), $aPinned, true);
}

/**
 * Save an override for a stock item: update the member's existing row for this
 * workspace or create one. Mirrors gf_menu.php::gfMenuStockUpsert.
 */
function gfSidebarStockUpsert($oDb, $iAccount, $iWorkspace, $sItem, $aSet)
{
    $iId = (int)$oDb->getOne(
        "SELECT `id` FROM `gf_sidebar_menu` WHERE `account_id` = :account AND `workspace_id` = :workspace AND `custom` = 0 AND `item` = :item LIMIT 1",
        ['account' => $iAccount, 'workspace' => $iWorkspace, 'item' => $sItem]
    );

    if($iId)
        $oDb->query("UPDATE `gf_sidebar_menu` SET " . $oDb->arrayToSQL($aSet) . " WHERE `id` = " . $iId);
    else
        $oDb->query("INSERT INTO `gf_sidebar_menu` SET " . $oDb->arrayToSQL(array_merge([
            'account_id' => $iAccount,
            'workspace_id' => $iWorkspace,
            'item' => $sItem,
            'custom' => 0,
            'added' => time()
        ], $aSet)));
}

$sAction = bx_process_input(bx_get('a'));
switch($sAction) {
    case 'reorder':
        $aKeys = json_decode((string)bx_get('v'), true);
        if(!is_array($aKeys))
            break;

        $iOrder = 1;
        foreach($aKeys as $sKey) {
            if(!is_string($sKey))
                continue;

            $sKey = bx_process_input($sKey);
            if(preg_match('/^c([0-9]+)$/', $sKey, $aMatch))
                $oDb->query($oDb->prepare(
                    "UPDATE `gf_sidebar_menu` SET `order` = ? WHERE `id` = ? AND `account_id` = ? AND `workspace_id` = ? AND `custom` = 1",
                    $iOrder, (int)$aMatch[1], $iAccount, $iWorkspace
                ));
            else if(preg_match('/^[A-Za-z0-9_\-]{1,128}$/', $sKey))
                gfSidebarStockUpsert($oDb, $iAccount, $iWorkspace, $sKey, ['order' => $iOrder]);
            else
                continue;

            $iOrder++;
        }
        break;

    case 'toggle':
        $sKey = bx_process_input(bx_get('i'));
        if(preg_match('/^c([0-9]+)$/', $sKey, $aMatch))
            $oDb->query($oDb->prepare(
                "UPDATE `gf_sidebar_menu` SET `hidden` = 1 - `hidden` WHERE `id` = ? AND `account_id` = ? AND `workspace_id` = ? AND `custom` = 1",
                (int)$aMatch[1], $iAccount, $iWorkspace
            ));
        else if(preg_match('/^[A-Za-z0-9_\-]{1,128}$/', $sKey) && !gfSidebarIsPinned($sKey, $aPinned)) {
            $iHidden = (int)$oDb->getOne(
                "SELECT `hidden` FROM `gf_sidebar_menu` WHERE `account_id` = :account AND `workspace_id` = :workspace AND `custom` = 0 AND `item` = :item LIMIT 1",
                ['account' => $iAccount, 'workspace' => $iWorkspace, 'item' => $sKey]
            );
            gfSidebarStockUpsert($oDb, $iAccount, $iWorkspace, $sKey, ['hidden' => $iHidden ? 0 : 1]);
        }
        break;

    case 'add':
        $sTitle = trim(bx_process_input(bx_get('title')));
        $sTitle = function_exists('mb_substr') ? mb_substr($sTitle, 0, 64) : substr($sTitle, 0, 64);

        $sUrl = trim(bx_process_input(bx_get('url')));

        $sIcon = trim(bx_process_input(bx_get('icon')));
        if(!preg_match('/^[a-z0-9 \-]{0,64}$/i', $sIcon))
            $sIcon = '';
        if($sIcon === '')
            $sIcon = 'link';

        $sOpenMode = bx_process_input(bx_get('open_mode'));
        if(!in_array($sOpenMode, ['self', 'blank', 'iframe'], true))
            $sOpenMode = 'self';

        // http(s) and site-relative targets only - no other URL schemes
        $bValid = !empty($sTitle) && !empty($sUrl) && strlen($sUrl) <= 2048
            && (preg_match('/^https?:\/\//i', $sUrl) || !preg_match('/^[a-z][a-z0-9+.\-]*:/i', $sUrl));
        if(!$bValid)
            break;

        $iOrder = (int)$oDb->getOne(
            "SELECT MAX(`order`) FROM `gf_sidebar_menu` WHERE `account_id` = :account AND `workspace_id` = :workspace",
            ['account' => $iAccount, 'workspace' => $iWorkspace]
        );

        $oDb->query("INSERT INTO `gf_sidebar_menu` SET " . $oDb->arrayToSQL([
            'account_id' => $iAccount,
            'workspace_id' => $iWorkspace,
            'item' => '',
            'custom' => 1,
            'title' => $sTitle,
            'url' => $sUrl,
            'icon' => $sIcon,
            'open_mode' => $sOpenMode,
            'order' => $iOrder + 1,
            'added' => time()
        ]));
        break;

    case 'del':
        $sKey = bx_process_input(bx_get('i'));
        if(preg_match('/^c([0-9]+)$/', $sKey, $aMatch))
            $oDb->query($oDb->prepare(
                "DELETE FROM `gf_sidebar_menu` WHERE `id` = ? AND `account_id` = ? AND `workspace_id` = ? AND `custom` = 1",
                (int)$aMatch[1], $iAccount, $iWorkspace
            ));
        break;

    case 'reset':
        $oDb->query($oDb->prepare(
            "DELETE FROM `gf_sidebar_menu` WHERE `account_id` = ? AND `workspace_id` = ?",
            $iAccount, $iWorkspace
        ));
        break;
}

echo json_encode(['code' => 0, 'html' => $oFunctions->getGfSidebar()]);
