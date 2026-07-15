<?php
/**
 * GFunnel — dynamic XML sitemap endpoint.
 *
 * /sitemap.xml and /sitemap-N.xml are rewritten here (see .htaccess), so the
 * sitemap always reflects live data — every new page, post, event, product,
 * profile, etc. appears automatically. Generation and caching live in
 * modules/gfunnel/sitemap/classes/GfSiteMapGenerator.php; a cron job
 * (see modules/gfunnel/sitemap/install.php) keeps the cache warm and this
 * endpoint regenerates on demand if the cache is older than gf_sitemap_ttl.
 *
 * Admins can force a rebuild with /sitemap.php?regenerate=1
 */

require_once('./inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_ROOT . 'modules/gfunnel/sitemap/classes/GfSiteMapGenerator.php');

$oGenerator = GfSiteMapGenerator::getInstance();

if (bx_get('regenerate') && isAdmin()) {
    $aStats = $oGenerator->generate();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(false !== $aStats ? $aStats : ['error' => 'generation failed or already in progress — see sys_cron_jobs log']);
    exit;
}

$oGenerator->serve(bx_get('chunk'));
