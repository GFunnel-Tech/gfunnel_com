<?php
/**
 * GFunnel — dynamic robots.txt endpoint.
 *
 * /robots.txt is rewritten here (see .htaccess) so the Sitemap directive
 * always points at the canonical site URL configured in UNA, and utility
 * endpoints stay out of search indexes.
 *
 * Optional settings (sys_options):
 *  - gf_robots_extra  raw lines appended verbatim (e.g. extra Disallow rules)
 */

require_once('./inc/header.inc.php');

header('Content-Type: text/plain; charset=utf-8');

$aDisallow = [
    '/searchKeyword.php',
    '/searchExtended.php',
    '/r.php',
    '/storage.php',
    '/cmts.php',
    '/vote.php',
    '/score.php',
    '/favorite.php',
    '/report.php',
    '/form.php',
    '/embed.php',
    '/acl-view',
    '/cmts-view',
    '/login',
    '/logout',
    '/create-account',
    '/forgot-password',
    '/page/login',
    '/page/create-account',
    '/page/forgot-password',
];

echo "User-agent: *\n";
foreach ($aDisallow as $s)
    echo "Disallow: " . $s . "\n";

$sExtra = trim((string)getParam('gf_robots_extra'));
if ('' !== $sExtra)
    echo $sExtra . "\n";

// don't advertise the sitemap while it's disabled (it would 404)
if (getParam('gf_sitemap_disable') != 'on')
    echo "\nSitemap: " . BX_DOL_URL_ROOT . "sitemap.xml\n";
