<?php
/**
 * GFunnel — bug report endpoint.
 *
 * Receives reports from the header bug widget (template/js/gf_bug.js).
 * A report arrives as one multipart POST:
 *
 *  a=submit
 *   description    required, free text
 *   priority       low | medium | high
 *   page_url, viewport, dpr, ua, selector, xpath   auto-captured metadata
 *   recording_url  optional link to an external recording (e.g. Komodo)
 *   screenshot     main screenshot (blob/file, image/png|jpeg|webp, <= 8MB)
 *   extra_0..2     up to 3 additional screenshots, same limits
 *   recording      optional screen recording (video/webm|mp4, <= 40MB)
 *
 * Rows land in gf_bug_reports (created on first use); files are stored under
 * storage/gf_bug/ with random names and the site email gets a notification.
 * Response is JSON: {code: 0, id: N} on success, {code: >0, msg} otherwise.
 */

require_once('./inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_INC . "design.inc.php");

bx_import('BxDolLanguages');

header('Content-Type: application/json; charset=utf-8');

if(!isLogged()) {
    echo json_encode(['code' => 1, 'msg' => 'Please sign in to report a bug.']);
    exit;
}

if(getParam('gf_bug_reports') == 'off' || bx_get('a') !== 'submit' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['code' => 2, 'msg' => 'Bug reporting is unavailable.']);
    exit;
}

$oDb = BxDolDb::getInstance();

if(!$oDb->isTableExists('gf_bug_reports'))
    $oDb->query("CREATE TABLE IF NOT EXISTS `gf_bug_reports` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `account_id` int(10) unsigned NOT NULL DEFAULT 0,
        `profile_id` int(10) unsigned NOT NULL DEFAULT 0,
        `workspace_id` int(10) unsigned NOT NULL DEFAULT 0,
        `description` text NOT NULL,
        `priority` varchar(16) NOT NULL DEFAULT 'medium',
        `page_url` varchar(2048) NOT NULL DEFAULT '',
        `viewport` varchar(32) NOT NULL DEFAULT '',
        `dpr` varchar(16) NOT NULL DEFAULT '',
        `ua` varchar(512) NOT NULL DEFAULT '',
        `selector` text,
        `xpath` text,
        `recording_url` varchar(2048) NOT NULL DEFAULT '',
        `screenshot` varchar(255) NOT NULL DEFAULT '',
        `screenshots` text,
        `recording` varchar(255) NOT NULL DEFAULT '',
        `status` varchar(16) NOT NULL DEFAULT 'new',
        `added` int(10) unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `account_id` (`account_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

//--- Text fields
$sDescription = trim(bx_process_input(bx_get('description')));
if($sDescription === '') {
    echo json_encode(['code' => 3, 'msg' => 'Please describe what went wrong.']);
    exit;
}
if(function_exists('mb_substr'))
    $sDescription = mb_substr($sDescription, 0, 10000);
else
    $sDescription = substr($sDescription, 0, 10000);

$sPriority = bx_process_input(bx_get('priority'));
if(!in_array($sPriority, ['low', 'medium', 'high']))
    $sPriority = 'medium';

$fnMeta = function($sKey, $iMax) {
    $s = trim(bx_process_input(bx_get($sKey)));
    return function_exists('mb_substr') ? mb_substr($s, 0, $iMax) : substr($s, 0, $iMax);
};

$sPageUrl = $fnMeta('page_url', 2048);
$sViewport = $fnMeta('viewport', 32);
$sDpr = $fnMeta('dpr', 16);
$sUa = $fnMeta('ua', 512);
$sSelector = $fnMeta('selector', 4000);
$sXpath = $fnMeta('xpath', 4000);

// external recording link (Komodo) - http(s) only
$sRecordingUrl = $fnMeta('recording_url', 2048);
if($sRecordingUrl !== '' && !preg_match('/^https?:\/\//i', $sRecordingUrl))
    $sRecordingUrl = '';

//--- Uploaded files: random names under storage/gf_bug/, images and video only
$sStorageDir = BX_DIRECTORY_PATH_ROOT . 'storage/gf_bug/';
if(!is_dir($sStorageDir)) {
    @mkdir($sStorageDir, 0755, true);
    // no directory listing for stored evidence
    @file_put_contents($sStorageDir . 'index.html', '');
}

$aImageTypes = ['image/png' => '.png', 'image/jpeg' => '.jpg', 'image/webp' => '.webp'];
$aVideoTypes = ['video/webm' => '.webm', 'video/mp4' => '.mp4'];

$fnSaveUpload = function($sField, $aTypes, $iMaxBytes) use ($sStorageDir) {
    if(empty($_FILES[$sField]) || !is_uploaded_file($_FILES[$sField]['tmp_name']))
        return '';
    if($_FILES[$sField]['error'] !== UPLOAD_ERR_OK || $_FILES[$sField]['size'] > $iMaxBytes)
        return '';

    // trust content sniffing, not the client-supplied type
    $sMime = '';
    if(function_exists('finfo_open')) {
        $rFinfo = finfo_open(FILEINFO_MIME_TYPE);
        $sMime = (string)finfo_file($rFinfo, $_FILES[$sField]['tmp_name']);
        finfo_close($rFinfo);
    }
    else
        $sMime = (string)$_FILES[$sField]['type'];

    if(!isset($aTypes[$sMime]))
        return '';

    $sName = bin2hex(random_bytes(16)) . $aTypes[$sMime];
    if(!move_uploaded_file($_FILES[$sField]['tmp_name'], $sStorageDir . $sName))
        return '';

    @chmod($sStorageDir . $sName, 0644);
    return $sName;
};

$sScreenshot = $fnSaveUpload('screenshot', $aImageTypes, 8 * 1024 * 1024);

$aExtras = [];
for($i = 0; $i < 3; $i++) {
    $sExtra = $fnSaveUpload('extra_' . $i, $aImageTypes, 8 * 1024 * 1024);
    if($sExtra !== '')
        $aExtras[] = $sExtra;
}

$sRecording = $fnSaveUpload('recording', $aVideoTypes, 40 * 1024 * 1024);

//--- Store the report
$iAccount = (int)getLoggedId();
$iProfile = (int)bx_get_logged_profile_id();
$iWorkspace = (int)BxTemplFunctions::getInstance()->getGfActiveWorkspaceId();

$oDb->query("INSERT INTO `gf_bug_reports` SET " . $oDb->arrayToSQL([
    'account_id' => $iAccount,
    'profile_id' => $iProfile,
    'workspace_id' => $iWorkspace,
    'description' => $sDescription,
    'priority' => $sPriority,
    'page_url' => $sPageUrl,
    'viewport' => $sViewport,
    'dpr' => $sDpr,
    'ua' => $sUa,
    'selector' => $sSelector,
    'xpath' => $sXpath,
    'recording_url' => $sRecordingUrl,
    'screenshot' => $sScreenshot,
    'screenshots' => json_encode($aExtras),
    'recording' => $sRecording,
    'status' => 'new',
    'added' => time()
]));

$iReportId = (int)$oDb->lastId();

//--- Notify the site email (best effort - the report is already stored)
$sSiteEmail = trim((string)getParam('site_email'));
if($sSiteEmail !== '') {
    $oProfile = BxDolProfile::getInstance($iProfile);
    $sReporter = $oProfile ? $oProfile->getDisplayName() : ('Account #' . $iAccount);

    $sFilesBase = BX_DOL_URL_ROOT . 'storage/gf_bug/';
    $aLines = [
        '<b>Reporter:</b> ' . bx_process_output($sReporter) . ' (account #' . $iAccount . ')',
        '<b>Priority:</b> ' . ucfirst($sPriority),
        '<b>Description:</b><br />' . nl2br(bx_process_output($sDescription)),
        '<b>URL:</b> ' . bx_process_output($sPageUrl),
        '<b>Viewport:</b> ' . bx_process_output($sViewport) . ' &#183; DPR: ' . bx_process_output($sDpr),
        '<b>UA:</b> ' . bx_process_output($sUa)
    ];
    if($sSelector !== '')
        $aLines[] = '<b>Selector:</b> <code>' . bx_process_output($sSelector) . '</code>';
    if($sXpath !== '')
        $aLines[] = '<b>XPath:</b> <code>' . bx_process_output($sXpath) . '</code>';
    if($sScreenshot !== '')
        $aLines[] = '<b>Screenshot:</b> <a href="' . $sFilesBase . $sScreenshot . '">' . $sFilesBase . $sScreenshot . '</a>';
    foreach($aExtras as $iIndex => $sExtra)
        $aLines[] = '<b>Additional screenshot ' . ($iIndex + 1) . ':</b> <a href="' . $sFilesBase . $sExtra . '">' . $sFilesBase . $sExtra . '</a>';
    if($sRecording !== '')
        $aLines[] = '<b>Screen recording:</b> <a href="' . $sFilesBase . $sRecording . '">' . $sFilesBase . $sRecording . '</a>';
    if($sRecordingUrl !== '')
        $aLines[] = '<b>External recording:</b> <a href="' . bx_html_attribute($sRecordingUrl) . '">' . bx_process_output($sRecordingUrl) . '</a>';

    sendMail(
        $sSiteEmail,
        '[Bug #' . $iReportId . '] ' . ucfirst($sPriority) . ' priority report from ' . $sReporter,
        implode('<br /><br />', $aLines)
    );
}

echo json_encode(['code' => 0, 'id' => $iReportId]);
