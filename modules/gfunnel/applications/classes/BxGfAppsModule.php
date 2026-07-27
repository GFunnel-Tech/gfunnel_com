<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    GFunnelApplications GFunnel Applications
 * @ingroup     UnaModules
 *
 * GFunnel Applications — exposes the Application Hub as a UNA service so it can be
 * dropped onto any page as a native "Service" block in Studio -> Page Builder, and
 * so it renders inside the workspace shell (unlike the standalone /applications SEO
 * page). The HTML comes from the shared renderers in inc/gf_app_blocks.inc.php, so
 * the block and the standalone page stay in sync (same pattern as gfunnel_home).
 *
 * Service blocks (Page Builder -> Add block -> Service):
 *   module = gfunnel_applications
 *   method = block_hub        -> the full hub (Apps + Marketplace tabs, in a box)
 *          | block_apps       -> the App Launcher only (Core Applications + hubs)
 *          | block_directory  -> the App Directory only (search + grid)
 *
 * The hub is workspace-aware: it shows the active workspace's app collection
 * (BxTemplFunctions::getGfActiveWorkspaceId), empty until apps are added.
 *
 * @{
 */

class BxGfAppsModule extends BxDolModule
{
    function __construct($aModule)
    {
        parent::__construct($aModule);
    }

    /** Load the hub stylesheet + JS (+ fonts) into the page head, once per request. */
    protected function _assets()
    {
        static $bDone = false;
        if ($bDone)
            return;
        $bDone = true;

        $oTemplate = BxDolTemplate::getInstance();
        $sCssHome = 'template/css/gf_home.css';
        $sCssApps = 'template/css/gf_applications.css';
        $sJsHome = 'template/js/gf_home.js';
        $sJsApps = 'template/js/gf_applications.js';

        $oTemplate->addCss(array(
            'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Sora:wght@600;700;800&family=Space+Mono:wght@400;700&display=swap',
            BX_DOL_URL_ROOT . $sCssHome . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sCssHome),
            BX_DOL_URL_ROOT . $sCssApps . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sCssApps),
        ));
        $oTemplate->addJs(array(
            BX_DOL_URL_ROOT . $sJsHome . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sJsHome),
            BX_DOL_URL_ROOT . $sJsApps . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sJsApps),
        ));
    }

    /** Load the shared hub renderers + ensure the local tables exist. */
    protected function _hub()
    {
        require_once(BX_DIRECTORY_PATH_INC . 'gf_app_blocks.inc.php');
        $oDb = BxDolDb::getInstance();
        gfDirEnsureTables($oDb);
        gfWsAppsEnsureTable($oDb);
        return $oDb;
    }

    // --- Service blocks ---------------------------------------------------------

    /** Full hub: Apps + Marketplace tabs in a self-contained box. */
    public function serviceBlockHub($sTab = 'apps')
    {
        $this->_assets();
        $oDb = $this->_hub();
        return gfAppHubInner($oDb, gfAppMember(), $sTab, true);
    }

    /** App Launcher only: the active workspace's Core Applications + hub cards. */
    public function serviceBlockApps()
    {
        $this->_assets();
        $oDb = $this->_hub();
        $aMember = gfAppMember();
        $aMine = $aMember['logged'] ? gfWsAppIds($oDb, gfAppScope($aMember)) : array();
        return '<div class="gfh gfh-js gfa-box" id="gfa" data-gfa-logged="' . ($aMember['logged'] ? '1' : '0') . '" data-gfa-endpoint="' . bx_html_attribute(BX_DOL_URL_ROOT . 'applications') . '">'
            . gfAppAdminBar($aMember)
            . gfAppAppsPanel($oDb, $aMember, $aMine)
            . '</div>';
    }

    /** App Directory only: search + category filter + grid. */
    public function serviceBlockDirectory()
    {
        $this->_assets();
        $oDb = $this->_hub();
        $aMember = gfAppMember();
        $aMineSet = $aMember['logged'] ? array_flip(gfWsAppIds($oDb, gfAppScope($aMember))) : array();
        return '<div class="gfh gfh-js gfa-box" id="gfa" data-gfa-logged="' . ($aMember['logged'] ? '1' : '0') . '" data-gfa-endpoint="' . bx_html_attribute(BX_DOL_URL_ROOT . 'applications') . '">'
            . gfAppMarketplacePanel($oDb, $aMember, $aMineSet)
            . '</div>';
    }
}

/** @} */
