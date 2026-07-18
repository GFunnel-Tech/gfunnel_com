<?php
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

if (!file_exists("./inc/header.inc.php")) {
    // this is dynamic page - send headers to not cache this page
    $now = gmdate('D, d M Y H:i:s') . ' GMT';
    header("Expires: $now");
    header("Last-Modified: $now");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");

    echo "It seems to be script is <b>not</b> installed.<br />\n";
    if ( file_exists( "install/index.php" ) ) {
        echo "Please, wait. Redirecting you to installation form...<br />\n";
        echo "<script language=\"javascript\">location.href = 'install/index.php';</script>\n";
    }
    exit;
}

require_once('./inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_INC . "profiles.inc.php");

// A workspace invite link is BX_DOL_URL_ROOT . '?code=XXXXXXXX' (root). When the
// invitee is not yet a member, root sends them to home/splash and the register
// flow drops query params, so stash the code in a cookie. workspaces.php redeems
// it once the new member lands back on the picker (see gfWsInvitesEnabled block).
// The inviter's affiliate am_id (also on the link) is captured separately by the
// Affiliate System, so affiliate attribution survives the same flow.
if (!isLogged() && !empty($_GET['code']) && preg_match('/^[A-Za-z0-9]{8}$/', $_GET['code'])) {
    $aGfUrl = parse_url(BX_DOL_URL_ROOT);
    setcookie('gf_ws_invite', strtoupper($_GET['code']), time() + 86400 * 14, !empty($aGfUrl['path']) ? $aGfUrl['path'] : '/', '', false, true /* http only */);
}

if (!isLogged() && getParam('gf_root_home') != 'off' && false === strpos($_SERVER['HTTP_USER_AGENT'], 'UNAMobileApp')) {
    require_once("./home.php");
    exit;
}

if (!isLogged() && getParam('sys_site_splash_enabled') && false === strpos($_SERVER['HTTP_USER_AGENT'], 'UNAMobileApp')) {
    require_once("./splash.php");
    exit;
}

if (isLogged() && getParam('gf_root_workspaces') != 'off' && false === strpos($_SERVER['HTTP_USER_AGENT'], 'UNAMobileApp')) {
    require_once("./workspaces.php");
    exit;
}

$_GET['i'] = 'home';
require_once("./page.php");

/** @} */
