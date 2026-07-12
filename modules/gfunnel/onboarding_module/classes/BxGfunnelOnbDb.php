<?php
/**
 * GFunnel Onboarding Database Class
 */

defined('BX_DOL') or die('hack attempt');

class BxGfunnelOnbDb extends BxDolModuleDb
{
    function __construct(&$oConfig)
    {
        parent::__construct($oConfig);
    }
    
    // Additional database methods can go here if needed
}