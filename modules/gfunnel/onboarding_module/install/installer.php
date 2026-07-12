<?php
/**
 * GFunnel Onboarding Module Installer
 */

class BxGfunnelOnbInstaller extends BxDolStudioInstaller
{
    function __construct($aConfig)
    {
        parent::__construct($aConfig);
    }
    
    /**
     * Actions after successful installation
     */
    public function actionAfterInstall()
    {
        // Mark all existing users as onboarded
        $this->oDb->query("
            UPDATE `bx_persons_data` 
            SET `onboarding_completed` = 1,
                `onboarding_completed_at` = NOW()
            WHERE `id` > 0 AND `onboarding_completed` = 0
        ");
        
        return parent::actionAfterInstall();
    }
}