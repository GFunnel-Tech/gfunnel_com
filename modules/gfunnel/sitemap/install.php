<?php
/**
 * GFunnel — sitemap module installer.
 *
 * Registers the sitemap regeneration job in `sys_cron_jobs` (idempotent) and
 * builds the first sitemap. Run once after deploying:
 *
 *   from the shell:   php modules/gfunnel/sitemap/install.php
 *   or as an admin:   https://<site>/modules/gfunnel/sitemap/install.php
 *
 * To remove the cron job and cached files (CLI only, so a forged web request
 * can never disable the sitemap):
 *
 *   php modules/gfunnel/sitemap/install.php --uninstall
 */

// bootstrap UNA from the site root
$sRootPath = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
chdir($sRootPath);
require_once($sRootPath . 'inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_ROOT . 'modules/gfunnel/sitemap/classes/GfSiteMapGenerator.php');

$bCli = 'cli' == php_sapi_name();
if (!$bCli && !isAdmin()) {
    header('HTTP/1.0 403 Forbidden');
    echo 'Administrators only.';
    exit;
}

function gfSiteMapInstallOut($s)
{
    global $bCli;
    echo $s . ($bCli ? "\n" : "<br />\n");
}

$sJobName = 'gf_sitemap';
$sJobTime = '15 */3 * * *'; // every 3 hours at :15
$sJobClass = 'GfSiteMapCron';
$sJobFile = 'modules/gfunnel/sitemap/classes/GfSiteMapCron.php';

$oDb = BxDolDb::getInstance();

// uninstall is deliberately CLI-only: the web entry point is a GET request
// with no CSRF token, so it must never be able to do anything destructive
if ($bCli && in_array('--uninstall', $GLOBALS['argv'])) {
    $oDb->query("DELETE FROM `sys_cron_jobs` WHERE `name` = :name", ['name' => $sJobName]);
    $oDb->cleanCache('sys_cron_jobs');

    GfSiteMapGenerator::getInstance()->clearCache();

    gfSiteMapInstallOut('gf_sitemap: cron job and cached files removed.');
    exit;
}

$iExists = (int)$oDb->getOne("SELECT `id` FROM `sys_cron_jobs` WHERE `name` = :name LIMIT 1", ['name' => $sJobName]);
if ($iExists) {
    gfSiteMapInstallOut('gf_sitemap: cron job already registered (id ' . $iExists . ').');
} else {
    $oDb->query(
        "INSERT INTO `sys_cron_jobs` SET `name` = :name, `time` = :time, `class` = :class, `file` = :file",
        ['name' => $sJobName, 'time' => $sJobTime, 'class' => $sJobClass, 'file' => $sJobFile]
    );
    $oDb->cleanCache('sys_cron_jobs');
    gfSiteMapInstallOut('gf_sitemap: cron job registered (' . $sJobTime . ').');
}

gfSiteMapInstallOut('gf_sitemap: building initial sitemap...');
$aStats = GfSiteMapGenerator::getInstance()->generate();
if (false === $aStats) {
    gfSiteMapInstallOut('gf_sitemap: ERROR — generation failed, check cache/ dir permissions and logs.');
    exit(1);
}

gfSiteMapInstallOut('gf_sitemap: done — ' . $aStats['urls'] . ' URLs in ' . $aStats['chunks'] . ' file(s), ' . $aStats['duration'] . 's.');
gfSiteMapInstallOut('gf_sitemap: verify at ' . BX_DOL_URL_ROOT . 'sitemap.xml');
