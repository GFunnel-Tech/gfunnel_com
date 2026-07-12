<?php
/**
 * GFunnel Onboarding Module
 */

defined('BX_DOL') or die('hack attempt');

class BxGfunnelOnbModule extends BxDolModule
{
    protected $_sSecretKey = 'Hx8Km2Pq9Vn4Lw7Rt3Jc6Yf1Bd5Sg0Az';
    
    function __construct(&$aModule)
    {
        parent::__construct($aModule);
        
        // Load secret from config if available
        if (defined('GFUNNEL_SECRET_KEY')) {
            $this->_sSecretKey = GFUNNEL_SECRET_KEY;
        }
    }

    /**
     * Hook into login_after alert
     * Redirects new users to onboarding page
     */
    public function onLoginAfter($oAlert)
    {
        $aExtras = $oAlert->aExtras;
        
        if (!isset($aExtras['account']) || !isset($aExtras['url_relocate'])) {
            return true;
        }
        
        $aAccountInfo = $aExtras['account'];
        $iProfileId = $aAccountInfo['profile_id'] ?? 0;
        
        if (!$iProfileId) {
            return true;
        }
        
        // Check if onboarding is complete
        if (!$this->isOnboardingComplete($iProfileId)) {
            // Redirect to onboarding
            $aExtras['url_relocate'] = BX_DOL_URL_ROOT . 'page/onboarding';
        }
        
        return true;
    }
    
    /**
     * Check if user has completed onboarding
     */
    public function isOnboardingComplete($iProfileId)
    {
        return (bool)$this->_oDb->getOne("
            SELECT `onboarding_completed` 
            FROM `bx_persons_data` 
            WHERE `id` = ?
        ", [$iProfileId]);
    }
    
    /**
     * Mark onboarding as complete
     */
    public function markOnboardingComplete($iProfileId, $aData = [])
    {
        // Update profile flag
        $bResult = $this->_oDb->query("
            UPDATE `bx_persons_data` 
            SET `onboarding_completed` = 1,
                `onboarding_completed_at` = NOW()
            WHERE `id` = ?
        ", [$iProfileId]);
        
        // Save detailed data
        if (!empty($aData)) {
            $this->saveOnboardingData($iProfileId, $aData);
        }
        
        // Fire alert
        bx_alert('gfunnel_onb', 'onboarding_complete', $iProfileId, false, ['data' => $aData]);
        
        return $bResult !== false;
    }
    
    /**
     * Save onboarding data
     */
    public function saveOnboardingData($iProfileId, $aData)
    {
        return $this->_oDb->query("
            INSERT INTO `gfo_onboarding_data` 
            (
                `user_id`, 
                `company_name`, 
                `industry`, 
                `team_size`, 
                `goals`, 
                `services`, 
                `budget_range`, 
                `timeline`, 
                `notes`,
                `progress_step`,
                `progress_data`,
                `completed_at`
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                `company_name` = VALUES(`company_name`),
                `industry` = VALUES(`industry`),
                `team_size` = VALUES(`team_size`),
                `goals` = VALUES(`goals`),
                `services` = VALUES(`services`),
                `budget_range` = VALUES(`budget_range`),
                `timeline` = VALUES(`timeline`),
                `notes` = VALUES(`notes`),
                `progress_step` = VALUES(`progress_step`),
                `progress_data` = VALUES(`progress_data`),
                `completed_at` = VALUES(`completed_at`)
        ", [
            $iProfileId,
            $aData['company_name'] ?? '',
            $aData['industry'] ?? '',
            $aData['team_size'] ?? '',
            isset($aData['goals']) ? json_encode($aData['goals']) : null,
            isset($aData['services']) ? json_encode($aData['services']) : null,
            $aData['budget_range'] ?? '',
            $aData['timeline'] ?? '',
            $aData['notes'] ?? '',
            $aData['progress_step'] ?? 0,
            isset($aData['progress_data']) ? json_encode($aData['progress_data']) : null
        ]);
    }
    
    /**
     * Get onboarding data for a user
     */
    public function getOnboardingData($iProfileId)
    {
        $aData = $this->_oDb->getRow("
            SELECT * 
            FROM `gfo_onboarding_data` 
            WHERE `user_id` = ?
        ", [$iProfileId]);
        
        if ($aData) {
            // Decode JSON fields
            foreach (['goals', 'services', 'progress_data'] as $sField) {
                if (isset($aData[$sField]) && $aData[$sField]) {
                    $aData[$sField] = json_decode($aData[$sField], true);
                }
            }
        }
        
        return $aData ?: [];
    }
    
    /**
     * Generate secure token for iframe
     */
    public function generateToken($iProfileId)
    {
        $oProfile = BxDolProfile::getInstance($iProfileId);
        if (!$oProfile) {
            return false;
        }
        
        $aProfileInfo = $oProfile->getInfo();
        $oAccount = BxDolAccount::getInstance($aProfileInfo['account_id']);
        $aAccountInfo = $oAccount->getInfo();
        
        $aUserData = [
            'user_id' => $iProfileId,
            'email' => $aAccountInfo['email'],
            'username' => $aProfileInfo['username'],
            'fullname' => $aProfileInfo['fullname'],
            'account_id' => $aProfileInfo['account_id'],
            'timestamp' => time(),
            'action' => 'onboarding'
        ];
        
        $sToken = base64_encode(json_encode($aUserData));
        $sSignature = hash_hmac('sha256', $sToken, $this->_sSecretKey);
        
        return [
            'token' => $sToken,
            'signature' => $sSignature
        ];
    }
    
    /**
     * Verify token
     */
    public function verifyToken($sToken, $sSignature)
    {
        $sExpectedSig = hash_hmac('sha256', $sToken, $this->_sSecretKey);
        
        if (!hash_equals($sExpectedSig, $sSignature)) {
            return ['valid' => false, 'error' => 'Invalid signature'];
        }
        
        $aUserData = json_decode(base64_decode($sToken), true);
        
        if (!$aUserData) {
            return ['valid' => false, 'error' => 'Invalid token'];
        }
        
        if (!isset($aUserData['timestamp']) || (time() - $aUserData['timestamp']) > 300) {
            return ['valid' => false, 'error' => 'Token expired'];
        }
        
        $oProfile = BxDolProfile::getInstance($aUserData['user_id']);
        if (!$oProfile) {
            return ['valid' => false, 'error' => 'User not found'];
        }
        
        return ['valid' => true, 'userData' => $aUserData];
    }
    
    /**
     * Service method: Get onboarding iframe HTML
     * Called by page block
     */
    public function serviceGetOnboardingIframe()
    {
        $oProfile = BxDolProfile::getInstance();
        
        if (!$oProfile) {
            return MsgBox(_t('_sys_txt_access_denied'));
        }
        
        $iProfileId = $oProfile->id();
        
        // Generate token
        $aToken = $this->generateToken($iProfileId);
        
        if (!$aToken) {
            return MsgBox(_t('_Error'));
        }
        
        $sIframeUrl = 'https://onboarding.gfunnel.com/?token=' . urlencode($aToken['token']) . '&sig=' . urlencode($aToken['signature']);
        
        return $this->_oTemplate->parseHtmlByName('onboarding_iframe.html', [
            'iframe_url' => bx_html_attribute($sIframeUrl, BX_ESCAPE_STR_QUOTE)
        ]);
    }
}