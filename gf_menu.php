<?php
/**
 * GFunnel — member menu personalization endpoint (subheader hub tabs).
 *
 * Every logged-in member keeps their own version of the subheader menu per
 * workspace (rows in gf_user_menu, keyed by account + active workspace from
 * BxTemplFunctions::getGfActiveWorkspaceId). Actions arrive by POST:
 *
 *  a=reorder  v=JSON array of item keys in the new order
 *  a=toggle   i=item key (stock tab name, or cN for a member's own link)
 *  a=add      title, url (http(s) or site-relative)
 *  a=del      i=cN
 *  a=reset    remove every saved choice for this workspace
 *
 * The response is JSON: {code: 0, html: "<re-rendered subheader>"} so the
 * client can replace the bar in place. Stock tab keys are only ever stored and
 * matched by name - a member's rows can never touch another account's rows.
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
if(!$oDb->isTableExists('gf_user_menu')) {
    echo json_encode(['code' => 2]);
    exit;
}

$oFunctions = BxTemplFunctions::getInstance();

$iAccount = (int)getLoggedId();
$iWorkspace = (int)$oFunctions->getGfActiveWorkspaceId();

/**
 * Save an override for a stock tab: update the member's existing row for this
 * workspace or create one.
 */
function gfMenuStockUpsert($oDb, $iAccount, $iWorkspace, $sItem, $aSet)
{
    $iId = (int)$oDb->getOne(
        "SELECT `id` FROM `gf_user_menu` WHERE `account_id` = :account AND `workspace_id` = :workspace AND `custom` = 0 AND `item` = :item LIMIT 1",
        ['account' => $iAccount, 'workspace' => $iWorkspace, 'item' => $sItem]
    );

    if($iId)
        $oDb->query("UPDATE `gf_user_menu` SET " . $oDb->arrayToSQL($aSet) . " WHERE `id` = " . $iId);
    else
        $oDb->query("INSERT INTO `gf_user_menu` SET " . $oDb->arrayToSQL(array_merge([
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
                    "UPDATE `gf_user_menu` SET `order` = ? WHERE `id` = ? AND `account_id` = ? AND `workspace_id` = ? AND `custom` = 1",
                    $iOrder, (int)$aMatch[1], $iAccount, $iWorkspace
                ));
            else if(preg_match('/^[A-Za-z0-9_\-]{1,128}$/', $sKey))
                gfMenuStockUpsert($oDb, $iAccount, $iWorkspace, $sKey, ['order' => $iOrder]);
            else
                continue;

            $iOrder++;
        }
        break;

    case 'toggle':
        $sKey = bx_process_input(bx_get('i'));
        if(preg_match('/^c([0-9]+)$/', $sKey, $aMatch))
            $oDb->query($oDb->prepare(
                "UPDATE `gf_user_menu` SET `hidden` = 1 - `hidden` WHERE `id` = ? AND `account_id` = ? AND `workspace_id` = ? AND `custom` = 1",
                (int)$aMatch[1], $iAccount, $iWorkspace
            ));
        else if(preg_match('/^[A-Za-z0-9_\-]{1,128}$/', $sKey)) {
            $iHidden = (int)$oDb->getOne(
                "SELECT `hidden` FROM `gf_user_menu` WHERE `account_id` = :account AND `workspace_id` = :workspace AND `custom` = 0 AND `item` = :item LIMIT 1",
                ['account' => $iAccount, 'workspace' => $iWorkspace, 'item' => $sKey]
            );
            gfMenuStockUpsert($oDb, $iAccount, $iWorkspace, $sKey, ['hidden' => $iHidden ? 0 : 1]);
        }
        break;

    case 'add':
        $sTitle = trim(bx_process_input(bx_get('title')));
        if(function_exists('mb_substr'))
            $sTitle = mb_substr($sTitle, 0, 64);
        else
            $sTitle = substr($sTitle, 0, 64);

        $sUrl = trim(bx_process_input(bx_get('url')));

        // http(s) and site-relative targets only - no other URL schemes
        $bValid = !empty($sTitle) && !empty($sUrl) && strlen($sUrl) <= 2048
            && (preg_match('/^https?:\/\//i', $sUrl) || !preg_match('/^[a-z][a-z0-9+.\-]*:/i', $sUrl));
        if(!$bValid)
            break;

        $iOrder = (int)$oDb->getOne(
            "SELECT MAX(`order`) FROM `gf_user_menu` WHERE `account_id` = :account AND `workspace_id` = :workspace",
            ['account' => $iAccount, 'workspace' => $iWorkspace]
        );

        $oDb->query("INSERT INTO `gf_user_menu` SET " . $oDb->arrayToSQL([
            'account_id' => $iAccount,
            'workspace_id' => $iWorkspace,
            'item' => '',
            'custom' => 1,
            'title' => $sTitle,
            'url' => $sUrl,
            'order' => $iOrder + 1,
            'added' => time()
        ]));
        break;

    case 'del':
        $sKey = bx_process_input(bx_get('i'));
        if(preg_match('/^c([0-9]+)$/', $sKey, $aMatch))
            $oDb->query($oDb->prepare(
                "DELETE FROM `gf_user_menu` WHERE `id` = ? AND `account_id` = ? AND `workspace_id` = ? AND `custom` = 1",
                (int)$aMatch[1], $iAccount, $iWorkspace
            ));
        break;

    case 'reset':
        $oDb->query($oDb->prepare(
            "DELETE FROM `gf_user_menu` WHERE `account_id` = ? AND `workspace_id` = ?",
            $iAccount, $iWorkspace
        ));
        break;
}

echo json_encode(['code' => 0, 'html' => $oFunctions->getGfSubheader()]);
