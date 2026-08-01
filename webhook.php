<?php

define('BX_API', true);

require_once('./inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_INC . "profiles.inc.php");
require_once(BX_DIRECTORY_PATH_INC . "design.inc.php");

bx_import('BxDolLanguages');

header('Content-Type: application/json');

$aHeaders = function_exists('getallheaders') ? getallheaders() : false;
if ($aHeaders) {
    $sAuthHeader = isset($aHeaders['Authorization']) ? $aHeaders['Authorization'] : (isset($aHeaders['authorization']) ? $aHeaders['authorization'] : false);
}
else {
    $sAuthHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : false;
}
$sKey = str_replace('Bearer ', '', $sAuthHeader);

$oAi = BxDolAI::getInstance();
$aAgent = $sKey ? $oAi->getAgentByTriggerWebhookKey($sKey) : false;
if (!$aAgent) {
    header('HTTP/1.0 403 Forbidden');
    BxDolLanguages::getInstance();
    echo json_encode(['status' => 403, 'error' => _t("_Access denied")]);
    exit;
}

$aParams = $_REQUEST;
$aParams['trigger'] = 'webhook';

if ($aAgent['async']) {
    BxDolBackgroundJobs::getInstance()->add(bin2hex(random_bytes(16)), [
        'system', 'call_agent', 
        ['webhook', $aAgent, $aParams], 
        'TemplServices'
    ]);
    echo json_encode(['result' => 'scheduled']);
}
else {
    $mixed = $oAi->callAgent('webhook', $aAgent, $aParams);
    echo json_encode($mixed);
}
