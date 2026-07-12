<?php
/**
 * Verify onboarding token
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
$sToken = $input['token'] ?? '';
$sSignature = $input['signature'] ?? '';

if (empty($sToken) || empty($sSignature)) {
    echo json_encode(['valid' => false, 'error' => 'Missing token or signature']);
    exit;
}

try {
    $oModule = BxDolModule::getInstance('gfunnel_onb');
    
    if (!$oModule) {
        throw new Exception('Module not found');
    }
    
    $aResult = $oModule->verifyToken($sToken, $sSignature);
    echo json_encode($aResult);
    
} catch (Exception $e) {
    error_log('Token verification error: ' . $e->getMessage());
    echo json_encode(['valid' => false, 'error' => 'Server error']);
}
?>