<?php
/**
 * GFunnel — Time Tracking endpoint (header timer popup).
 *
 * Every logged-in member keeps their own timers, time entries, next-tasks and
 * billing preferences (rows keyed by account id). One timer may run per
 * account at any moment; stopping it produces a "recent entry" that carries
 * the activity trail (pages visited / clicks while it ran) and a billing
 * breakdown computed per workspace. Actions arrive by POST:
 *
 *  a=state         full popup payload (timer, entries, tasks, settings, workspaces)
 *  a=start         title, description, task (optional task id) - stops any running timer first
 *  a=update        title, description for the running timer
 *  a=activity      v=JSON [{u,t,w,wn,c,s}] page-visit buffer, merged into the running timer
 *  a=stop          v=activity buffer (optional), title/description (optional final values)
 *  a=discard       drop the running timer without creating an entry
 *  a=entry_add     manual time: title, description, ws, date (Y-m-d), time (H:i), duration (seconds)
 *  a=entry_save    id + the same fields - entry is re-billed after every edit
 *  a=entry_del     id
 *  a=task_add      title
 *  a=task_toggle   id
 *  a=task_del      id
 *  a=settings_save v=JSON {default:{rate,increment}, overrides:{wsId:{rate,increment}|null}}
 *
 * Billing rules:
 *  - a rate ($/hour) and a rounding increment (1s up to 15min) live per
 *    account, with optional per-workspace overrides (gf_time_settings);
 *  - while a timer runs the client reports which workspace each tracked page
 *    belonged to, so one entry can span several workspaces - each workspace
 *    segment is rounded and priced with its own rate;
 *  - the same wall-clock second is never billed twice: on save, the interval
 *    is trimmed by whatever other entries of the account already cover.
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
gfTimerEnsureTables($oDb);

$iAccount = (int)getLoggedId();
$iWorkspace = (int)BxTemplFunctions::getInstance()->getGfActiveWorkspaceId();

/*=== Schema =================================================================*/

function gfTimerEnsureTables($oDb)
{
    $oDb->query("CREATE TABLE IF NOT EXISTS `gf_time_entries` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `account_id` int(10) unsigned NOT NULL,
        `workspace_id` int(10) unsigned NOT NULL DEFAULT 0,
        `task_id` int(10) unsigned NOT NULL DEFAULT 0,
        `title` varchar(255) NOT NULL DEFAULT '',
        `description` text,
        `date_start` int(10) unsigned NOT NULL DEFAULT 0,
        `duration` int(10) unsigned NOT NULL DEFAULT 0,
        `running` tinyint(4) NOT NULL DEFAULT 0,
        `manual` tinyint(4) NOT NULL DEFAULT 0,
        `billable_seconds` int(10) unsigned NOT NULL DEFAULT 0,
        `amount` decimal(12,2) NOT NULL DEFAULT 0,
        `segments` text,
        `activity` mediumtext,
        `added` int(10) unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `account_start` (`account_id`, `date_start`),
        KEY `account_running` (`account_id`, `running`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $oDb->query("CREATE TABLE IF NOT EXISTS `gf_time_tasks` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `account_id` int(10) unsigned NOT NULL,
        `workspace_id` int(10) unsigned NOT NULL DEFAULT 0,
        `title` varchar(255) NOT NULL DEFAULT '',
        `done` tinyint(4) NOT NULL DEFAULT 0,
        `completed` int(10) unsigned NOT NULL DEFAULT 0,
        `added` int(10) unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `account` (`account_id`, `done`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $oDb->query("CREATE TABLE IF NOT EXISTS `gf_time_settings` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `account_id` int(10) unsigned NOT NULL,
        `workspace_id` int(10) unsigned NOT NULL DEFAULT 0,
        `rate` decimal(10,2) NOT NULL DEFAULT 0,
        `increment_seconds` int(10) unsigned NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`),
        UNIQUE KEY `account_workspace` (`account_id`, `workspace_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/*=== Helpers ================================================================*/

/**
 * Rounding increments a member may pick (seconds): 1s (default), 1m, 5m, 6m,
 * 10m, 15m.
 */
function gfTimerIncrements()
{
    return [1, 60, 300, 360, 600, 900];
}

function gfTimerWsName($iWs)
{
    if($iWs <= 0)
        return 'General';

    $oProfile = BxDolProfile::getInstance($iWs);
    return $oProfile ? $oProfile->getDisplayName() : 'Workspace #' . $iWs;
}

/**
 * Billing preferences: the account-wide default (workspace_id = 0) plus
 * per-workspace overrides.
 */
function gfTimerSettings($oDb, $iAccount)
{
    $aOut = ['default' => ['rate' => 0.0, 'increment' => 1], 'overrides' => []];

    $aRows = $oDb->getAll(
        "SELECT * FROM `gf_time_settings` WHERE `account_id` = :account",
        ['account' => $iAccount]
    );
    if(is_array($aRows))
        foreach($aRows as $aRow) {
            $aValue = ['rate' => (float)$aRow['rate'], 'increment' => (int)$aRow['increment_seconds']];
            if((int)$aRow['workspace_id'] == 0)
                $aOut['default'] = $aValue;
            else
                $aOut['overrides'][(int)$aRow['workspace_id']] = $aValue;
        }

    return $aOut;
}

function gfTimerResolveBilling($aSettings, $iWs)
{
    if($iWs > 0 && isset($aSettings['overrides'][$iWs]))
        return $aSettings['overrides'][$iWs];

    return $aSettings['default'];
}

/**
 * Seconds of [start, end) already covered by the account's other saved
 * entries - the part of a new interval that must not be billed again.
 */
function gfTimerOverlap($oDb, $iAccount, $iExcludeId, $iStart, $iEnd)
{
    if($iEnd <= $iStart)
        return 0;

    $aRows = $oDb->getAll($oDb->prepare(
        "SELECT `date_start`, `duration` FROM `gf_time_entries`
        WHERE `account_id` = ? AND `running` = 0 AND `id` <> ? AND `date_start` < ? AND `date_start` + `duration` > ?
        ORDER BY `date_start` ASC",
        $iAccount, $iExcludeId, $iEnd, $iStart
    ));
    if(!is_array($aRows) || empty($aRows))
        return 0;

    // merge the clipped intervals, then sum the coverage
    $iCovered = 0;
    $iCurStart = 0;
    $iCurEnd = 0;
    foreach($aRows as $aRow) {
        $iA = max($iStart, (int)$aRow['date_start']);
        $iB = min($iEnd, (int)$aRow['date_start'] + (int)$aRow['duration']);
        if($iB <= $iA)
            continue;

        if($iCurEnd == 0) {
            $iCurStart = $iA;
            $iCurEnd = $iB;
        }
        else if($iA <= $iCurEnd)
            $iCurEnd = max($iCurEnd, $iB);
        else {
            $iCovered += $iCurEnd - $iCurStart;
            $iCurStart = $iA;
            $iCurEnd = $iB;
        }
    }
    if($iCurEnd > 0)
        $iCovered += $iCurEnd - $iCurStart;

    return $iCovered;
}

/**
 * Merge a fresh activity buffer into the stored one. Items are keyed by
 * page + workspace; clicks and seconds accumulate.
 */
function gfTimerActivityMerge($aExisting, $aNew)
{
    if(!is_array($aExisting))
        $aExisting = [];
    if(!is_array($aNew))
        return $aExisting;

    $aIndex = [];
    foreach($aExisting as $iKey => $aItem)
        $aIndex[$aItem['u'] . '|' . (int)$aItem['w']] = $iKey;

    foreach($aNew as $aItem) {
        if(!is_array($aItem) || !isset($aItem['u']))
            continue;

        $sUrl = mb_substr(trim((string)$aItem['u']), 0, 512);
        $sTitle = mb_substr(trim((string)(isset($aItem['t']) ? $aItem['t'] : '')), 0, 255);
        $iWs = isset($aItem['w']) ? (int)$aItem['w'] : 0;
        $sWsName = mb_substr(trim((string)(isset($aItem['wn']) ? $aItem['wn'] : '')), 0, 255);
        $iClicks = isset($aItem['c']) ? max(0, min(100000, (int)$aItem['c'])) : 0;
        $iSeconds = isset($aItem['s']) ? max(0, min(86400, (int)$aItem['s'])) : 0;
        if($sUrl === '')
            continue;

        $sKey = $sUrl . '|' . $iWs;
        if(isset($aIndex[$sKey])) {
            $aExisting[$aIndex[$sKey]]['c'] += $iClicks;
            $aExisting[$aIndex[$sKey]]['s'] += $iSeconds;
            if($sTitle !== '')
                $aExisting[$aIndex[$sKey]]['t'] = $sTitle;
        }
        else {
            if(count($aExisting) >= 300)
                continue;

            $aExisting[] = ['u' => $sUrl, 't' => $sTitle, 'w' => $iWs, 'wn' => $sWsName, 'c' => $iClicks, 's' => $iSeconds];
            $aIndex[$sKey] = count($aExisting) - 1;
        }
    }

    return array_values($aExisting);
}

/**
 * (Re)compute an entry's billing: workspace segments from the activity trail,
 * overlap trim against the account's other entries, per-workspace rounding
 * and rates. Writes billable_seconds, amount and segments back to the row.
 */
function gfTimerRebill($oDb, $iAccount, $iEntryId)
{
    $aEntry = $oDb->getRow($oDb->prepare(
        "SELECT * FROM `gf_time_entries` WHERE `id` = ? AND `account_id` = ? LIMIT 1",
        $iEntryId, $iAccount
    ));
    if(empty($aEntry) || (int)$aEntry['running'])
        return;

    $iStart = (int)$aEntry['date_start'];
    $iDuration = (int)$aEntry['duration'];
    $iStartWs = (int)$aEntry['workspace_id'];
    $aSettings = gfTimerSettings($oDb, $iAccount);

    //--- seconds per workspace from the activity trail; time the tracker did
    //--- not see stays with the workspace the timer was started in
    $aWsSeconds = [];
    $aWsNames = [];
    $aActivity = json_decode((string)$aEntry['activity'], true);
    $iTracked = 0;
    if(is_array($aActivity))
        foreach($aActivity as $aItem) {
            $iWs = (int)$aItem['w'];
            $iSec = (int)$aItem['s'];
            $aWsSeconds[$iWs] = (isset($aWsSeconds[$iWs]) ? $aWsSeconds[$iWs] : 0) + $iSec;
            if(!empty($aItem['wn']))
                $aWsNames[$iWs] = $aItem['wn'];
            $iTracked += $iSec;
        }

    if($iTracked > $iDuration && $iTracked > 0) {
        // multiple tabs can report more page-seconds than wall-clock time - scale down
        foreach($aWsSeconds as $iWs => $iSec)
            $aWsSeconds[$iWs] = (int)floor($iSec * $iDuration / $iTracked);
    }
    else if($iTracked < $iDuration)
        $aWsSeconds[$iStartWs] = (isset($aWsSeconds[$iStartWs]) ? $aWsSeconds[$iStartWs] : 0) + ($iDuration - $iTracked);

    //--- never bill a second that another entry already covers
    $iOverlap = gfTimerOverlap($oDb, $iAccount, $iEntryId, $iStart, $iStart + $iDuration);
    $iBillableTotal = max(0, $iDuration - $iOverlap);
    $fRatio = $iDuration > 0 ? $iBillableTotal / $iDuration : 0;

    $aSegments = [];
    $fAmount = 0.0;
    $iBillableRounded = 0;
    foreach($aWsSeconds as $iWs => $iSeconds) {
        if($iSeconds <= 0)
            continue;

        $aBilling = gfTimerResolveBilling($aSettings, $iWs);
        $iIncrement = max(1, (int)$aBilling['increment']);
        $fRate = (float)$aBilling['rate'];

        $iBillable = (int)round($iSeconds * $fRatio);
        $iRounded = $iBillable > 0 ? (int)(ceil($iBillable / $iIncrement) * $iIncrement) : 0;
        $fSegmentAmount = round($iRounded / 3600 * $fRate, 2);

        $aSegments[] = [
            'ws' => $iWs,
            'ws_name' => isset($aWsNames[$iWs]) ? $aWsNames[$iWs] : gfTimerWsName($iWs),
            'seconds' => $iSeconds,
            'billable' => $iRounded,
            'rate' => $fRate,
            'increment' => $iIncrement,
            'amount' => $fSegmentAmount
        ];

        $fAmount += $fSegmentAmount;
        $iBillableRounded += $iRounded;
    }

    $oDb->query("UPDATE `gf_time_entries` SET " . $oDb->arrayToSQL([
        'billable_seconds' => $iBillableRounded,
        'amount' => round($fAmount, 2),
        'segments' => json_encode($aSegments)
    ]) . " WHERE `id` = " . (int)$iEntryId);
}

function gfTimerRunning($oDb, $iAccount)
{
    $aRow = $oDb->getRow($oDb->prepare(
        "SELECT * FROM `gf_time_entries` WHERE `account_id` = ? AND `running` = 1 ORDER BY `id` DESC LIMIT 1",
        $iAccount
    ));

    return !empty($aRow) ? $aRow : null;
}

/**
 * Finalize the running timer into a saved entry. $aActivity (optional) is a
 * last client buffer merged in before billing.
 */
function gfTimerStopRunning($oDb, $iAccount, $aActivity = null, $sTitle = null, $sDescription = null)
{
    $aTimer = gfTimerRunning($oDb, $iAccount);
    if(!$aTimer)
        return 0;

    $iId = (int)$aTimer['id'];
    $iDuration = max(1, time() - (int)$aTimer['date_start']);

    $aSet = [
        'running' => 0,
        'duration' => $iDuration
    ];
    if($sTitle !== null && $sTitle !== '')
        $aSet['title'] = $sTitle;
    if($sDescription !== null)
        $aSet['description'] = $sDescription;
    if(is_array($aActivity)) {
        $aMerged = gfTimerActivityMerge(json_decode((string)$aTimer['activity'], true), $aActivity);
        $aSet['activity'] = json_encode($aMerged);
    }

    $oDb->query("UPDATE `gf_time_entries` SET " . $oDb->arrayToSQL($aSet) . " WHERE `id` = " . $iId);
    gfTimerRebill($oDb, $iAccount, $iId);

    return $iId;
}

/*=== State payload ==========================================================*/

function gfTimerStatePayload($oDb, $iAccount, $iWorkspace)
{
    $iNow = time();
    $iTodayStart = strtotime('today');

    //--- running timer
    $aTimer = gfTimerRunning($oDb, $iAccount);
    $aTimerOut = null;
    if($aTimer)
        $aTimerOut = [
            'id' => (int)$aTimer['id'],
            'title' => $aTimer['title'],
            'description' => $aTimer['description'],
            'date_start' => (int)$aTimer['date_start'],
            'ws' => (int)$aTimer['workspace_id'],
            'ws_name' => gfTimerWsName((int)$aTimer['workspace_id'])
        ];

    //--- recent entries
    $aEntries = [];
    $aRows = $oDb->getAll($oDb->prepare(
        "SELECT * FROM `gf_time_entries` WHERE `account_id` = ? AND `running` = 0 ORDER BY `date_start` DESC LIMIT 20",
        $iAccount
    ));
    if(is_array($aRows))
        foreach($aRows as $aRow) {
            $aActivity = json_decode((string)$aRow['activity'], true);
            $aSegments = json_decode((string)$aRow['segments'], true);

            $aEntries[] = [
                'id' => (int)$aRow['id'],
                'title' => $aRow['title'],
                'description' => $aRow['description'],
                'ws' => (int)$aRow['workspace_id'],
                'ws_name' => gfTimerWsName((int)$aRow['workspace_id']),
                'date_start' => (int)$aRow['date_start'],
                'duration' => (int)$aRow['duration'],
                'manual' => (int)$aRow['manual'],
                'billable' => (int)$aRow['billable_seconds'],
                'amount' => (float)$aRow['amount'],
                'segments' => is_array($aSegments) ? $aSegments : [],
                'activity' => is_array($aActivity) ? $aActivity : []
            ];
        }

    //--- next tasks: everything open, plus what was completed in the last week
    $aTasks = [];
    $aRows = $oDb->getAll($oDb->prepare(
        "SELECT * FROM `gf_time_tasks` WHERE `account_id` = ? AND (`done` = 0 OR `completed` > ?)
        ORDER BY `done` ASC, `id` DESC LIMIT 50",
        $iAccount, $iNow - 7 * 86400
    ));
    if(is_array($aRows))
        foreach($aRows as $aRow)
            $aTasks[] = [
                'id' => (int)$aRow['id'],
                'title' => $aRow['title'],
                'done' => (int)$aRow['done'],
                'completed' => (int)$aRow['completed'],
                'added' => (int)$aRow['added']
            ];

    //--- today summary + comparison with the same window one week back
    $iTodayTotal = (int)$oDb->getOne($oDb->prepare(
        "SELECT SUM(`duration`) FROM `gf_time_entries` WHERE `account_id` = ? AND `running` = 0 AND `date_start` >= ?",
        $iAccount, $iTodayStart
    ));
    if($aTimer)
        $iTodayTotal += max(0, $iNow - max((int)$aTimer['date_start'], $iTodayStart));

    $iLastWeekTotal = (int)$oDb->getOne($oDb->prepare(
        "SELECT SUM(`duration`) FROM `gf_time_entries` WHERE `account_id` = ? AND `running` = 0 AND `date_start` >= ? AND `date_start` <= ?",
        $iAccount, $iTodayStart - 7 * 86400, $iNow - 7 * 86400
    ));

    $iVs = 0;
    if($iLastWeekTotal > 0)
        $iVs = (int)round(($iTodayTotal - $iLastWeekTotal) * 100 / $iLastWeekTotal);
    else if($iTodayTotal > 0)
        $iVs = 100;

    $iTasksDone = (int)$oDb->getOne($oDb->prepare(
        "SELECT COUNT(*) FROM `gf_time_tasks` WHERE `account_id` = ? AND `done` = 1 AND `completed` >= ?",
        $iAccount, $iTodayStart
    ));

    //--- billable total for today (already-rounded per entry)
    $fTodayAmount = (float)$oDb->getOne($oDb->prepare(
        "SELECT SUM(`amount`) FROM `gf_time_entries` WHERE `account_id` = ? AND `running` = 0 AND `date_start` >= ?",
        $iAccount, $iTodayStart
    ));

    //--- the member's workspaces (for billing overrides + manual entry form)
    $aWorkspaces = [['id' => 0, 'name' => 'General']];
    $oAccount = BxDolAccount::getInstance();
    if($oAccount)
        foreach($oAccount->getProfiles() as $iProfileId => $aProfileInfo) {
            if(empty($aProfileInfo['type']) || $aProfileInfo['type'] == 'system')
                continue;

            $oWsProfile = BxDolProfile::getInstance($iProfileId);
            if(!$oWsProfile)
                continue;

            $aWorkspaces[] = [
                'id' => (int)$iProfileId,
                'name' => $oWsProfile->getDisplayName()
            ];
        }

    return [
        'code' => 0,
        'now' => $iNow,
        'ws' => $iWorkspace,
        'ws_name' => gfTimerWsName($iWorkspace),
        'timer' => $aTimerOut,
        'today' => [
            'total' => $iTodayTotal,
            'amount' => round($fTodayAmount, 2),
            'tasks' => $iTasksDone,
            'vs' => $iVs
        ],
        'entries' => $aEntries,
        'tasks' => $aTasks,
        'settings' => gfTimerSettings($oDb, $iAccount),
        'increments' => gfTimerIncrements(),
        'workspaces' => $aWorkspaces
    ];
}

/*=== Actions ================================================================*/

$sAction = bx_process_input(bx_get('a'));
switch($sAction) {
    case 'start':
        $sTitle = mb_substr(trim(bx_process_input(bx_get('title'))), 0, 255);
        $sDescription = mb_substr(trim(bx_process_input(bx_get('description'))), 0, 5000);
        $iTask = (int)bx_get('task');

        // one running timer per account - never double count
        gfTimerStopRunning($oDb, $iAccount);

        if($iTask) {
            $aTask = $oDb->getRow($oDb->prepare(
                "SELECT * FROM `gf_time_tasks` WHERE `id` = ? AND `account_id` = ? LIMIT 1",
                $iTask, $iAccount
            ));
            if(!empty($aTask) && $sTitle === '')
                $sTitle = $aTask['title'];
            if(empty($aTask))
                $iTask = 0;
        }

        if($sTitle === '')
            $sTitle = 'Untitled work';

        $oDb->query("INSERT INTO `gf_time_entries` SET " . $oDb->arrayToSQL([
            'account_id' => $iAccount,
            'workspace_id' => $iWorkspace,
            'task_id' => $iTask,
            'title' => $sTitle,
            'description' => $sDescription,
            'date_start' => time(),
            'running' => 1,
            'added' => time()
        ]));
        break;

    case 'update':
        $aTimer = gfTimerRunning($oDb, $iAccount);
        if(!$aTimer)
            break;

        $sTitle = mb_substr(trim(bx_process_input(bx_get('title'))), 0, 255);
        $sDescription = mb_substr(trim(bx_process_input(bx_get('description'))), 0, 5000);

        $oDb->query("UPDATE `gf_time_entries` SET " . $oDb->arrayToSQL([
            'title' => $sTitle !== '' ? $sTitle : $aTimer['title'],
            'description' => $sDescription
        ]) . " WHERE `id` = " . (int)$aTimer['id']);
        break;

    case 'activity':
        $aTimer = gfTimerRunning($oDb, $iAccount);
        if(!$aTimer)
            break;

        $aBuffer = json_decode((string)bx_get('v'), true);
        if(!is_array($aBuffer))
            break;

        $aMerged = gfTimerActivityMerge(json_decode((string)$aTimer['activity'], true), $aBuffer);
        $oDb->query("UPDATE `gf_time_entries` SET " . $oDb->arrayToSQL([
            'activity' => json_encode($aMerged)
        ]) . " WHERE `id` = " . (int)$aTimer['id']);
        break;

    case 'stop':
        $aBuffer = json_decode((string)bx_get('v'), true);
        $sTitle = mb_substr(trim(bx_process_input(bx_get('title'))), 0, 255);
        $mixedDescription = bx_get('description');
        $sDescription = $mixedDescription !== false ? mb_substr(trim(bx_process_input($mixedDescription)), 0, 5000) : null;

        gfTimerStopRunning($oDb, $iAccount, is_array($aBuffer) ? $aBuffer : null, $sTitle, $sDescription);
        break;

    case 'discard':
        $oDb->query($oDb->prepare(
            "DELETE FROM `gf_time_entries` WHERE `account_id` = ? AND `running` = 1",
            $iAccount
        ));
        break;

    case 'entry_add':
    case 'entry_save':
        $sTitle = mb_substr(trim(bx_process_input(bx_get('title'))), 0, 255);
        $sDescription = mb_substr(trim(bx_process_input(bx_get('description'))), 0, 5000);
        $iWs = (int)bx_get('ws');
        $iDuration = (int)bx_get('duration');
        $sDate = bx_process_input(bx_get('date'));
        $sTime = bx_process_input(bx_get('time'));

        if($sTitle === '' || $iDuration <= 0)
            break;

        $iDuration = min($iDuration, 7 * 86400);

        $iStart = 0;
        if(preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$sDate) && preg_match('/^\d{1,2}:\d{2}$/', (string)$sTime))
            $iStart = (int)strtotime($sDate . ' ' . $sTime);
        if($iStart < 946684800 || $iStart > time() + 86400)
            break;

        if($sAction == 'entry_add') {
            $oDb->query("INSERT INTO `gf_time_entries` SET " . $oDb->arrayToSQL([
                'account_id' => $iAccount,
                'workspace_id' => $iWs,
                'title' => $sTitle,
                'description' => $sDescription,
                'date_start' => $iStart,
                'duration' => $iDuration,
                'manual' => 1,
                'added' => time()
            ]));
            gfTimerRebill($oDb, $iAccount, (int)$oDb->lastId());
        }
        else {
            $iId = (int)bx_get('id');
            $aEntry = $oDb->getRow($oDb->prepare(
                "SELECT * FROM `gf_time_entries` WHERE `id` = ? AND `account_id` = ? AND `running` = 0 LIMIT 1",
                $iId, $iAccount
            ));
            if(empty($aEntry))
                break;

            $oDb->query("UPDATE `gf_time_entries` SET " . $oDb->arrayToSQL([
                'title' => $sTitle,
                'description' => $sDescription,
                'workspace_id' => $iWs,
                'date_start' => $iStart,
                'duration' => $iDuration
            ]) . " WHERE `id` = " . $iId);
            gfTimerRebill($oDb, $iAccount, $iId);
        }
        break;

    case 'entry_del':
        $oDb->query($oDb->prepare(
            "DELETE FROM `gf_time_entries` WHERE `id` = ? AND `account_id` = ? AND `running` = 0",
            (int)bx_get('id'), $iAccount
        ));
        break;

    case 'task_add':
        $sTitle = mb_substr(trim(bx_process_input(bx_get('title'))), 0, 255);
        if($sTitle === '')
            break;

        $oDb->query("INSERT INTO `gf_time_tasks` SET " . $oDb->arrayToSQL([
            'account_id' => $iAccount,
            'workspace_id' => $iWorkspace,
            'title' => $sTitle,
            'added' => time()
        ]));
        break;

    case 'task_toggle':
        $iId = (int)bx_get('id');
        $aTask = $oDb->getRow($oDb->prepare(
            "SELECT * FROM `gf_time_tasks` WHERE `id` = ? AND `account_id` = ? LIMIT 1",
            $iId, $iAccount
        ));
        if(empty($aTask))
            break;

        $bDone = !(int)$aTask['done'];
        $oDb->query("UPDATE `gf_time_tasks` SET " . $oDb->arrayToSQL([
            'done' => $bDone ? 1 : 0,
            'completed' => $bDone ? time() : 0
        ]) . " WHERE `id` = " . $iId);
        break;

    case 'task_del':
        $oDb->query($oDb->prepare(
            "DELETE FROM `gf_time_tasks` WHERE `id` = ? AND `account_id` = ?",
            (int)bx_get('id'), $iAccount
        ));
        break;

    case 'settings_save':
        $aData = json_decode((string)bx_get('v'), true);
        if(!is_array($aData))
            break;

        $fnClean = function($aValue) {
            if(!is_array($aValue))
                return null;

            $fRate = isset($aValue['rate']) ? (float)$aValue['rate'] : 0;
            $iIncrement = isset($aValue['increment']) ? (int)$aValue['increment'] : 1;
            if($fRate < 0 || $fRate > 1000000)
                $fRate = 0;
            if(!in_array($iIncrement, gfTimerIncrements()))
                $iIncrement = 1;

            return ['rate' => round($fRate, 2), 'increment' => $iIncrement];
        };

        $fnUpsert = function($iWs, $aValue) use ($oDb, $iAccount) {
            $iId = (int)$oDb->getOne(
                "SELECT `id` FROM `gf_time_settings` WHERE `account_id` = :account AND `workspace_id` = :workspace LIMIT 1",
                ['account' => $iAccount, 'workspace' => $iWs]
            );

            $aSet = ['rate' => $aValue['rate'], 'increment_seconds' => $aValue['increment']];
            if($iId)
                $oDb->query("UPDATE `gf_time_settings` SET " . $oDb->arrayToSQL($aSet) . " WHERE `id` = " . $iId);
            else
                $oDb->query("INSERT INTO `gf_time_settings` SET " . $oDb->arrayToSQL(array_merge([
                    'account_id' => $iAccount,
                    'workspace_id' => $iWs
                ], $aSet)));
        };

        $aDefault = $fnClean(isset($aData['default']) ? $aData['default'] : null);
        if($aDefault !== null)
            $fnUpsert(0, $aDefault);

        if(isset($aData['overrides']) && is_array($aData['overrides']))
            foreach($aData['overrides'] as $mixedWs => $mixedValue) {
                $iWs = (int)$mixedWs;
                if($iWs <= 0)
                    continue;

                if($mixedValue === null || $mixedValue === '') {
                    $oDb->query($oDb->prepare(
                        "DELETE FROM `gf_time_settings` WHERE `account_id` = ? AND `workspace_id` = ?",
                        $iAccount, $iWs
                    ));
                    continue;
                }

                $aValue = $fnClean($mixedValue);
                if($aValue !== null)
                    $fnUpsert($iWs, $aValue);
            }
        break;
}

echo json_encode(gfTimerStatePayload($oDb, $iAccount, $iWorkspace));
