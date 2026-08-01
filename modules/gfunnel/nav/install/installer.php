<?php
/**
 * GFunnel Nav module installer. Table creation + seeding is handled by
 * install/sql/install.sql (execute_sql) when installed via Studio, or by the
 * reliable migration custom_batch_updates/gf_nav_install.php.
 */

defined('BX_DOL') or die('hack attempt');

class BxGfNavInstaller extends BxDolStudioInstaller
{
    function __construct($aConfig)
    {
        parent::__construct($aConfig);
    }
}
