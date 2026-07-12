<?php
/**
 * Auto-save onboarding progress
 */

require_once('../../../../inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_INC . 'design.inc.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://onboarding.gfunnel.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$input = json_decode(file_get_contents('php://input'), true);
$iUserId = $input['user_id'] ?? 0;
$aData = $input['data'] ?? [];

$oProfile = BxDolProfile::getInstance();
if (!$oProfile || $oProfile->id() != $iUserId) {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

try {
    $oModule = BxDolModule::getInstance('gfunnel_onb');
    $oModule->saveOnboardingData($iUserId, $aData);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false]);
}
?>