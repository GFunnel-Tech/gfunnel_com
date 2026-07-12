<?php
// Bootstrap UNA framework using absolute path
require_once('/home/u831969491/domains/gfunnel.com/public_html/inc/header.inc.php');

error_log("Script started at " . date('Y-m-d H:i:s') . "\n", 3, '/home/u831969491/logs/batch_cron.log');

// Set REQUEST_URI to suppress warnings in CLI mode
$_SERVER['REQUEST_URI'] = '/';

// Database instance
$oDb = BxDolDb::getInstance();

// Fetch all accounts with null MemberID
$aAccounts = $oDb->getAll("SELECT `id`, `profile_id` FROM `sys_accounts` WHERE `MemberID` IS NULL");

foreach ($aAccounts as $aAccount) {
    $iAccountId = $aAccount['id'];
    $iProfileId = $aAccount['profile_id'];
    
    // Generate full referral URL for MemberID
    $sFullReferral = bx_srv('aqb_affiliate', 'get_referral_code', [$iProfileId]);
    
    // Parse the URL to extract the am_id token
    $sMemberId = $sFullReferral; // Fallback to full string if parsing fails
    $aUrlParts = parse_url($sFullReferral);
    if (isset($aUrlParts['query'])) {
        parse_str($aUrlParts['query'], $aQuery);
        if (isset($aQuery['am_id'])) {
            $sMemberId = $aQuery['am_id'];
        }
    }
    
    if ($sMemberId) {
        // Update only the MemberID field with extracted token
        $bUpdateSuccess = $oDb->query("UPDATE `sys_accounts` SET `MemberID` = :member_id WHERE `id` = :account_id", [
            'member_id' => $sMemberId,
            'account_id' => $iAccountId
        ]);
    }
}

// Completion notification
echo "Batch update completed.\n";

error_log("Script completed at " . date('Y-m-d H:i:s') . "\n", 3, '/home/u831969491/logs/batch_cron.log');
?>