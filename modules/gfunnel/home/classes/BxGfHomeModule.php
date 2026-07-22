<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    GFunnelHome GFunnel Home
 * @ingroup     UnaModules
 *
 * GFunnel Home — exposes each homepage section as a UNA service so it can be dropped
 * onto any page as a native "Service" block in Studio -> Page Builder (add / reorder /
 * toggle in the GUI). The HTML comes from the shared renderers in
 * inc/gf_home_blocks.inc.php, so blocks and the standalone home.php stay in sync.
 *
 * Service blocks (Page Builder -> Add block -> Service):
 *   module = gfunnel_home
 *   method = block_hero | block_catalogs | block_departments | block_featured
 *          | block_community_news | block_cta | block_seasonal_html
 *
 * block_seasonal_html takes positional params: (html, active_from, active_to, season)
 * and renders only inside that date/season window — this is the seasonal "code block".
 *
 * @{
 */

class BxGfHomeModule extends BxDolModule
{
    function __construct($aModule)
    {
        parent::__construct($aModule);
    }

    /**
     * Load the homepage stylesheet (+ fonts) into the page head once per request, so
     * blocks render styled wherever they are placed.
     */
    protected function _assets()
    {
        static $bDone = false;
        if ($bDone)
            return;
        $bDone = true;

        $oTemplate = BxDolTemplate::getInstance();
        $sCss = 'template/css/gf_home.css';
        $oTemplate->addCss(array(
            'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Sora:wght@600;700;800&family=Space+Mono:wght@400;700&display=swap',
            BX_DOL_URL_ROOT . $sCss . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sCss),
        ));
    }

    /** Load the shared homepage block renderers. */
    protected function _blocks()
    {
        require_once(BX_DIRECTORY_PATH_INC . 'gf_home_blocks.inc.php');
    }

    // --- Section service blocks -------------------------------------------------

    public function serviceBlockHero()
    {
        $this->_assets();
        $this->_blocks();
        return gfHomeSectionHero();
    }

    public function serviceBlockCatalogs()
    {
        $this->_assets();
        $this->_blocks();
        return gfHomeSectionCatalogs();
    }

    public function serviceBlockBusiness()
    {
        $this->_assets();
        $this->_blocks();
        return gfHomeBusinessSection();
    }

    public function serviceBlockDepartments()
    {
        $this->_assets();
        $this->_blocks();
        return gfHomeSectionDepartments();
    }

    public function serviceBlockFeatured()
    {
        $this->_assets();
        $this->_blocks();
        return gfHomeSectionFeatured();
    }

    public function serviceBlockCommunityNews()
    {
        $this->_assets();
        $this->_blocks();
        return gfHomeSectionCommunityNews();
    }

    public function serviceBlockCta()
    {
        $this->_assets();
        $this->_blocks();
        return gfHomeSectionCta();
    }

    /**
     * Seasonal HTML block — renders admin markup only within a date/season window.
     * Params (positional, as stored in the Page Builder service block):
     *   $sHtml       trusted HTML/CSS/JS markup to render
     *   $sActiveFrom 'YYYY-MM-DD' (or any date) window start, empty = no start bound
     *   $sActiveTo   'YYYY-MM-DD' window end, empty = no end bound
     *   $sSeason     'spring'|'summer'|'fall'|'winter'|'any' (or comma list), empty = any
     */
    public function serviceBlockSeasonalHtml($sHtml = '', $sActiveFrom = '', $sActiveTo = '', $sSeason = '')
    {
        $this->_assets();
        $this->_blocks();
        return gfHomeSeasonalHtml(array(
            'html' => $sHtml,
            'active_from' => $sActiveFrom,
            'active_to' => $sActiveTo,
            'season' => $sSeason,
        ));
    }
}

/** @} */
