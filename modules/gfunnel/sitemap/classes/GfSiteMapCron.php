<?php defined('BX_DOL') or die('hack attempt');
/**
 * GFunnel — sitemap regeneration cron job.
 *
 * Registered in `sys_cron_jobs` by modules/gfunnel/sitemap/install.php and
 * executed by periodic/cron.php on the schedule stored there (default
 * '15 *\/3 * * *' — every 3 hours at :15). Keeps /sitemap.xml fresh without
 * a visitor ever paying the regeneration cost; /sitemap.php also regenerates
 * on demand if the cache goes stale (e.g. cron is down).
 */
require_once(BX_DIRECTORY_PATH_ROOT . 'modules/gfunnel/sitemap/classes/GfSiteMapGenerator.php');

class GfSiteMapCron extends BxDolCron
{
    public function processing()
    {
        $oGenerator = GfSiteMapGenerator::getInstance();
        if (!$oGenerator->isEnabled())
            return;

        $oGenerator->generate();
    }
}
