<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaBaseView UNA Base Representation Classes
 * @{
 */

class BxBaseFunctions extends BxDolFactory implements iBxDolSingleton
{
    protected $_oTemplate;

    protected $_sDesignBoxMenuTmplDefault;

    /**
     * Pages may switch the subheader (hub tabs + clock) off before the page is
     * rendered, e.g. the workspace picker shows the 48px header bar only:
     * BxTemplFunctions::$bGfToolbarSubheader = false;
     */
    public static $bGfToolbarSubheader = true;

    protected $_sDesignBoxMenuIcon;
    protected $_sDesignBoxMenuIconType;
    protected $_sDesignBoxMenuClick;

    protected function __construct($oTemplate)
    {
        parent::__construct();

        $this->_oTemplate = $oTemplate ? $oTemplate : BxDolTemplate::getInstance();

        $this->_sDesignBoxMenuTmplDefault = 'menu_block_submenu_ver.html';

        $this->_sDesignBoxMenuIcon = 'ellipsis-v';
        $this->_sDesignBoxMenuIconType = 'icon';
        $this->_sDesignBoxMenuClick = "bx_menu_popup_inline('#{design_box_menu}', this, " . json_encode(array(
            'moveToDocRoot' => false
        )) . ")";
    }

    /**
     * Prevent cloning the instance
     */
    public function __clone()
    {
        if (isset($GLOBALS['bxDolClasses'][get_class($this)]))
            trigger_error('Clone is not allowed for the class: ' . get_class($this), E_USER_ERROR);
    }

    public static function getInstanceWithTemplate($oTemplate)
    {
        $sClassName = 'BxTemplFunctions';
        if ($oTemplate){
            $sClassName .= get_class($oTemplate);
        }
        if(!isset($GLOBALS['bxDolClasses'][$sClassName]))
            $GLOBALS['bxDolClasses'][$sClassName] = new BxTemplFunctions($oTemplate);

        return $GLOBALS['bxDolClasses'][$sClassName];
    }
    
    public static function getInstance()
    {
        return self::getInstanceWithTemplate(null);
    }

    function TemplPageAddComponent($sKey)
    {
        $mixedResult = false; // if you have not such component, return false!

        switch($sKey) {
            case 'sys_header_width':
                $mixedResult = 'bx-def-page-width';
                break;

            case 'sys_toolbar_search':
                $oSearch = new BxTemplSearch();
                $oSearch->setLiveSearch(true);

                $mixedResult = $this->_oTemplate->parseHtmlByName('_page_toolbar_search.html', [
                    'sys_site_search' => $oSearch->getForm(BX_DB_PADDING_DEF, false, true) . $oSearch->getResultsContainer()
                ]);
                break;

            case 'gf_toolbar':
                $mixedResult = $this->getGfToolbar();
                break;

            case 'gf_sidebar':
                $mixedResult = $this->getGfSidebar();
                break;

            case 'gf_toolbar_protean':
                $mixedResult = $this->getGfToolbar('_page_toolbar_classic_protean.html', 'gf-flow');
                break;

            case 'gf_toolbar_lucid':
                $mixedResult = $this->getGfToolbar('_page_toolbar_classic_lucid.html', 'gf-flow');
                break;

            case 'gf_toolbar_app':
                // The application layout is the org/workspace experience (it is
                // the only layout with the left sidebar). The workspace selector
                // shows here, not on the gfunnel.com main-site layouts.
                $mixedResult = $this->getGfToolbar('_page_toolbar_classic_app.html', 'gf-fixed', true);
                break;
        }

        return $mixedResult;
    }

    /**
     * GFunnel toolbar: logged-in members get the two-bar (header + subheader) chrome,
     * visitors keep the classic toolbar.
     *
     * @param string $sClassicTemplate per-template fallback markup shown to visitors
     * @param string $sChromeClass 'gf-fixed' for templates whose toolbar is fixed and
     *               compensated with content padding, 'gf-flow' for in-flow (sticky) toolbars
     * @param boolean $bWorkspaceCtx workspace/app layout: render the workspace
     *               selector (hidden on the gfunnel.com main-site layouts)
     */
    public function getGfToolbar($sClassicTemplate = '_page_toolbar_classic.html', $sChromeClass = 'gf-fixed', $bWorkspaceCtx = false)
    {
        if(!isLogged())
            return $this->_oTemplate->parseHtmlByName($sClassicTemplate, []);

        // remember the workspace the member is in (picker links carry ?gf_ws=N)
        $this->getGfActiveWorkspaceId();

        // The subheader lives in its own sub-template: the compiled-template
        // engine can't nest bx_repeat inside bx_if, so it's parsed separately
        // and passed as ready HTML (empty when the page opted out).
        $sSubheader = '';
        if(self::$bGfToolbarSubheader)
            $sSubheader = $this->getGfSubheader();
        else
            $sChromeClass .= ' gf-no-subheader';

        // Header action buttons: enabled with working defaults; each setting
        // overrides the destination, and the value 'off' hides the button.
        $sWhatsNewUrl = $this->_getGfHeaderUrl(getParam('gf_header_whats_new_url'), 'page.php?i=news-home');
        $sAiUrl = $this->_getGfHeaderUrl(getParam('gf_header_ai_url'), 'agents.php');
        $sMessagesUrl = $this->_getGfHeaderUrl(getParam('gf_header_messages_url'), 'page.php?i=messenger');

        // unread badge: conversations with unread messages from the messenger module
        $iUnreadMessages = 0;
        if(!empty($sMessagesUrl) && BxDolRequest::serviceExists('bx_messenger', 'get_unread_lots')) {
            $aUnreadLots = BxDolService::call('bx_messenger', 'get_unread_lots', [bx_get_logged_profile_id()]);
            if(is_array($aUnreadLots))
                $iUnreadMessages = count($aUnreadLots);
        }

        $sSearchPlaceholder = getParam('gf_header_search_placeholder');
        if(empty($sSearchPlaceholder))
            $sSearchPlaceholder = 'Ask anything';

        $sCssFile = 'template/css/gf_header.css';
        $sTimerCssFile = 'template/css/gf_timer.css';
        $sTimerJsFile = 'template/js/gf_timer.js';

        // Bug report widget: on by default, gf_bug_reports = 'off' disables it;
        // gf_bug_komodo_url overrides the external-recording referral target.
        $bBug = getParam('gf_bug_reports') != 'off';
        $sBugKomodoUrl = trim((string)getParam('gf_bug_komodo_url'));
        if(empty($sBugKomodoUrl))
            $sBugKomodoUrl = 'https://kommodo.gfunnel.com';

        $sBugCssFile = 'template/css/gf_bug.css';
        $sBugJsFile = 'template/js/gf_bug.js';

        return $this->_oTemplate->parseHtmlByName('_page_toolbar_auth.html', [
            'chrome_class' => $sChromeClass,
            'timer_css_url' => BX_DOL_URL_ROOT . $sTimerCssFile . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sTimerCssFile),
            'timer_js_url' => BX_DOL_URL_ROOT . $sTimerJsFile . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sTimerJsFile),
            'timer_boot' => $this->getGfTimerBoot(),
            'ws_selector' => $bWorkspaceCtx ? $this->getGfWorkspaceSelector() : '',
            'css_url' => BX_DOL_URL_ROOT . $sCssFile . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sCssFile),
            'search_placeholder' => bx_html_attribute($sSearchPlaceholder),
            'subheader' => $sSubheader,
            'bx_if:whats_new' => [
                'condition' => !empty($sWhatsNewUrl),
                'content' => [
                    'whats_new_url' => $sWhatsNewUrl,
                    'whats_new_title' => "What's New"
                ]
            ],
            'bx_if:bug' => [
                'condition' => $bBug,
                'content' => [
                    'bug_css_url' => BX_DOL_URL_ROOT . $sBugCssFile . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sBugCssFile),
                    'bug_js_url' => BX_DOL_URL_ROOT . $sBugJsFile . '?v=' . (int)@filemtime(BX_DIRECTORY_PATH_ROOT . $sBugJsFile),
                    'bug_endpoint' => BX_DOL_URL_ROOT . 'gf_bug.php',
                    'bug_h2c_url' => BX_DOL_URL_ROOT . 'plugins_public/html2canvas/html2canvas.min.js',
                    'bug_komodo_url' => bx_html_attribute($sBugKomodoUrl)
                ]
            ],
            'bx_if:ai' => [
                'condition' => !empty($sAiUrl),
                'content' => [
                    'ai_url' => $sAiUrl,
                    'ai_title' => 'Ask AI'
                ]
            ],
            'bx_if:messages' => [
                'condition' => !empty($sMessagesUrl),
                'content' => [
                    'messages_url' => $sMessagesUrl,
                    'messages_badge' => $iUnreadMessages > 0 ? '<span class="gf-hdr-badge">' . ($iUnreadMessages > 99 ? '99+' : $iUnreadMessages) . '</span>' : '',
                    'messages_panel_empty' => $iUnreadMessages > 0
                        ? ('You have ' . (int)$iUnreadMessages . ' unread conversation' . ($iUnreadMessages == 1 ? '' : 's') . '.')
                        : "You're all caught up."
                ]
            ]
        ]);
    }

    /**
     * GFunnel left navigation (the workspace/app sidebar).
     *
     * GFunnel-owned, exactly like the top nav (getGfToolbar): our own markup +
     * our own item config, rendered into the app layout in place of the stock
     * sys_site_panel menu - so it is not tied to a DB-configured menu or the
     * upstream design template. Dark rail, icon+label rows, orange active state,
     * Settings + footer pinned at the bottom.
     *
     * Edit $aItems below to change the nav. Each item: key, title, url, icon
     * (inline SVG). `match` is the URL substring that marks the item active.
     */
    public function getGfSidebar()
    {
        if(!isLogged())
            return '';

        $sRoot = BX_DOL_URL_ROOT;

        // --- Nav items (our config - edit here to change the sidebar). ---
        $aItems = array(
            array('key' => 'home',           'title' => 'Home',           'match' => '',                'url' => $sRoot,
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>'),
            array('key' => 'communications', 'title' => 'Communications', 'match' => 'communications',   'url' => $this->_getGfHeaderUrl(getParam('gf_nav_communications_url'), 'page.php?i=messenger'),
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>'),
            array('key' => 'sales',          'title' => 'Sales',          'match' => 'sales',            'url' => $this->_getGfHeaderUrl(getParam('gf_nav_sales_url'), 'sales'),
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>'),
            array('key' => 'memory',         'title' => 'Memory',         'match' => 'memory',           'url' => $this->_getGfHeaderUrl(getParam('gf_nav_memory_url'), 'agents.php'),
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5a3 3 0 1 0-5.997.125 4 4 0 0 0-2.526 5.77 4 4 0 0 0 .556 6.588A4 4 0 1 0 12 18Z"/><path d="M12 5a3 3 0 1 1 5.997.125 4 4 0 0 1 2.526 5.77 4 4 0 0 1-.556 6.588A4 4 0 1 1 12 18Z"/></svg>'),
            array('key' => 'applications',   'title' => 'Applications',   'match' => 'applications',     'url' => $sRoot . 'applications',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>'),
            array('key' => 'social',         'title' => 'Social',         'match' => 'social',           'url' => $this->_getGfHeaderUrl(getParam('gf_nav_social_url'), 'page.php?i=timeline'),
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>'),
            array('key' => 'explore',        'title' => 'Explore',        'match' => 'explore',          'url' => $sRoot . 'explore',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>'),
            array('key' => 'communities',    'title' => 'Communities',    'match' => 'communities',      'url' => $sRoot . 'communities',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>'),
            array('key' => 'marketplace',    'title' => 'Marketplace',    'match' => 'marketplace',      'url' => $sRoot . 'marketplace',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9h18l-1 11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><path d="M3 9l2-5h14l2 5"/><path d="M9 13a3 3 0 0 0 6 0"/></svg>'),
            array('key' => 'events',         'title' => 'Events',         'match' => 'events',           'url' => $this->_getGfHeaderUrl(getParam('gf_nav_events_url'), 'page.php?i=events-home'),
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>'),
            array('key' => 'learning',       'title' => 'Learning',       'match' => 'learning',         'url' => $this->_getGfHeaderUrl(getParam('gf_nav_learning_url'), 'page.php?i=courses-home'),
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>'),
            array('key' => 'partners',       'title' => 'Partners',       'match' => 'partners',         'url' => $sRoot . 'partners',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"/><path d="m21 3-9.24 9.24"/></svg>'),
        );

        // --- Active item: match the current request path. ---
        $sReq = (string)($_SERVER['REQUEST_URI'] ?? '');
        $sReqPath = (string)parse_url($sReq, PHP_URL_PATH);
        $aRepeat = array();
        foreach($aItems as $aItem) {
            $bActive = false;
            if($aItem['match'] === '')
                $bActive = ($sReqPath === '/' || $sReqPath === '' || $sReqPath === '/home');
            elseif(strpos($sReqPath, '/' . $aItem['match']) !== false || strpos($sReq, 'i=' . $aItem['match']) !== false)
                $bActive = true;

            $aRepeat[] = array(
                'title' => bx_html_attribute($aItem['title']),
                'url' => bx_html_attribute($aItem['url']),
                'icon' => $aItem['icon'],
                'class_active' => $bActive ? 'gf-side-item-active' : ''
            );
        }

        // --- Footer links (edit here). ---
        $sSettingsUrl = $this->_getGfHeaderUrl(getParam('gf_nav_settings_url'), 'page.php?i=settings');
        $iYear = (int)date('Y');

        return $this->_oTemplate->parseHtmlByName('_page_sidebar.html', array(
            'bx_repeat:items' => $aRepeat,
            'settings_url' => bx_html_attribute($sSettingsUrl),
            'rules_url' => bx_html_attribute($this->_getGfHeaderUrl(getParam('gf_nav_rules_url'), 'page.php?i=rules')),
            'privacy_url' => bx_html_attribute($this->_getGfHeaderUrl(getParam('gf_nav_privacy_url'), 'page.php?i=privacy')),
            'terms_url' => bx_html_attribute($this->_getGfHeaderUrl(getParam('gf_nav_terms_url'), 'page.php?i=terms-of-service')),
            'year' => $iYear
        ));
    }

    protected function _getGfHeaderUrl($sUrl, $sDefault = '')
    {
        $sUrl = trim((string)$sUrl);
        if($sUrl == 'off')
            return '';
        if(empty($sUrl))
            $sUrl = $sDefault;
        if(empty($sUrl))
            return '';

        if(preg_match('/^https?:\/\//i', $sUrl))
            return $sUrl;

        if(strncmp($sUrl, 'page.php', 8) === 0)
            $sUrl = BxDolPermalinks::getInstance()->permalink($sUrl);

        return BX_DOL_URL_ROOT . ltrim($sUrl, '/');
    }

    /**
     * GFunnel active workspace (a workspace is a profile: organization, space,
     * group or the member's own person profile). The workspace picker appends
     * ?gf_ws=<profile_id> to its Launch links; the first request carrying it
     * pins the workspace in the session, so every later page - and the
     * gf_menu.php endpoint - knows which workspace's menu preferences apply.
     *
     * @return int workspace profile id, 0 before any workspace was launched.
     */
    public function getGfActiveWorkspaceId()
    {
        $oSession = BxDolSession::getInstance();

        $iWorkspace = (int)bx_get('gf_ws');
        if($iWorkspace > 0)
            $oSession->setValue('gf_active_workspace', $iWorkspace);
        else
            $iWorkspace = (int)$oSession->getValue('gf_active_workspace');

        return $iWorkspace;
    }

    /**
     * GFunnel workspace selector for the top nav (right of the logo).
     *
     * Lists the account's workspaces - owned profiles (personal + workspaces)
     * plus workspaces joined through each group module's fans connections -
     * exactly as the /workspaces picker enumerates them. Each entry links with
     * ?gf_ws=<id> so launching from the top nav pins the workspace in the
     * session just like the picker does. The active workspace is shown on the
     * trigger. Returns '' for visitors or when there is nothing to switch to.
     *
     * @return string ready selector HTML (button + dropdown), or ''
     */
    public function getGfWorkspaceSelector()
    {
        if(!isLogged())
            return '';

        $oProfile = BxDolProfile::getInstance(bx_get_logged_profile_id());
        if(!$oProfile || !($oAccount = $oProfile->getAccountObject()))
            return '';

        $iActive = $this->getGfActiveWorkspaceId();

        $aItems = array();
        $aOwnedIds = array();

        //--- Owned profiles on this account (personal profile + owned workspaces).
        foreach($oAccount->getProfiles() as $iProfileId => $aProfileInfo) {
            if(empty($aProfileInfo['type']) || $aProfileInfo['type'] == 'system')
                continue;

            $oWs = BxDolProfile::getInstance((int)$iProfileId);
            if(!$oWs)
                continue;

            $aOwnedIds[] = (int)$iProfileId;
            $aItems[(int)$iProfileId] = array(
                'id' => (int)$iProfileId,
                'title' => $oWs->getDisplayName(),
                'thumb' => $oWs->getThumb(),
                'url' => bx_append_url_params($oWs->getUrl(), array('gf_ws' => (int)$iProfileId))
            );
        }

        //--- Joined workspaces: membership lives in each group module's fans connections.
        $sWorkspaceModules = trim((string)getParam('gf_workspace_modules'));
        if(empty($sWorkspaceModules))
            $sWorkspaceModules = 'bx_organizations,bx_spaces,bx_groups';

        foreach(explode(',', $sWorkspaceModules) as $sWsModule) {
            $sWsModule = trim($sWsModule);
            if($sWsModule === '' || !($oWsModule = BxDolModule::getInstance($sWsModule)) || empty($oWsModule->_oConfig->CNF['OBJECT_CONNECTIONS']))
                continue;

            $oConnection = BxDolConnection::getObjectInstance($oWsModule->_oConfig->CNF['OBJECT_CONNECTIONS']);
            if(!$oConnection || !is_array($aJoinedIds = $oConnection->getConnectedContent($oProfile->id())))
                continue;

            foreach($aJoinedIds as $iJoinedId) {
                $iJoinedId = (int)$iJoinedId;
                if($iJoinedId <= 0 || isset($aItems[$iJoinedId]) || in_array($iJoinedId, $aOwnedIds))
                    continue;

                $oJoined = BxDolProfile::getInstance($iJoinedId);
                if(!$oJoined || $oJoined->getModule() != $sWsModule)
                    continue;

                $aItems[$iJoinedId] = array(
                    'id' => $iJoinedId,
                    'title' => $oJoined->getDisplayName(),
                    'thumb' => $oJoined->getThumb(),
                    'url' => bx_append_url_params($oJoined->getUrl(), array('gf_ws' => $iJoinedId))
                );
            }
        }

        if(empty($aItems))
            return '';

        //--- Current workspace shown on the trigger (falls back to the first).
        $aActive = isset($aItems[$iActive]) ? $aItems[$iActive] : reset($aItems);

        $aRepeat = array();
        foreach($aItems as $aItem)
            $aRepeat[] = array(
                'url' => bx_html_attribute($aItem['url']),
                'title' => bx_html_attribute($aItem['title']),
                'initial' => mb_strtoupper(mb_substr($aItem['title'] !== '' ? $aItem['title'] : 'W', 0, 1)),
                'thumb_style' => $aItem['thumb'] !== '' ? 'background-image:url(' . bx_html_attribute($aItem['thumb']) . ')' : '',
                'class_active' => $aItem['id'] == $aActive['id'] ? 'gf-ws-item-active' : ''
            );

        return $this->_oTemplate->parseHtmlByName('_page_toolbar_ws.html', array(
            'active_title' => bx_html_attribute($aActive['title']),
            'active_initial' => mb_strtoupper(mb_substr($aActive['title'] !== '' ? $aActive['title'] : 'W', 0, 1)),
            'active_thumb_style' => $aActive['thumb'] !== '' ? 'background-image:url(' . bx_html_attribute($aActive['thumb']) . ')' : '',
            'bx_repeat:items' => $aRepeat
        ));
    }

    /**
     * GFunnel time tracking: initial state for the header pill + popup
     * (template/js/gf_timer.js), embedded as JSON so no extra request is
     * needed on page load. Tables are created lazily by gf_timer.php, so
     * until the first popup action this degrades to "no running timer".
     */
    public function getGfTimerBoot()
    {
        $iWorkspace = $this->getGfActiveWorkspaceId();

        $sWsName = 'General';
        if($iWorkspace > 0 && ($oWsProfile = BxDolProfile::getInstance($iWorkspace)))
            $sWsName = $oWsProfile->getDisplayName();

        $aTimer = null;
        $oDb = BxDolDb::getInstance();
        if($oDb->isTableExists('gf_time_entries')) {
            $aRow = $oDb->getRow($oDb->prepare(
                "SELECT * FROM `gf_time_entries` WHERE `account_id` = ? AND `running` = 1 ORDER BY `id` DESC LIMIT 1",
                (int)getLoggedId()
            ));
            if(!empty($aRow)) {
                $sTimerWsName = 'General';
                if((int)$aRow['workspace_id'] > 0 && ($oTimerWsProfile = BxDolProfile::getInstance((int)$aRow['workspace_id'])))
                    $sTimerWsName = $oTimerWsProfile->getDisplayName();

                // raw values: the popup JS escapes everything at render time
                $aTimer = [
                    'id' => (int)$aRow['id'],
                    'title' => $aRow['title'],
                    'description' => $aRow['description'],
                    'date_start' => (int)$aRow['date_start'],
                    'ws' => (int)$aRow['workspace_id'],
                    'ws_name' => $sTimerWsName
                ];
            }
        }

        return json_encode([
            'url' => BX_DOL_URL_ROOT . 'gf_timer.php',
            'now' => time(),
            'ws' => $iWorkspace,
            'ws_name' => $sWsName,
            'timer' => $aTimer
        ]);
    }

    /**
     * GFunnel subheader: the hub tabs from the shared menu object, personalized
     * per member and per workspace from the gf_user_menu table (hidden tabs,
     * custom order, member's own links), plus the customize panel. Reused by
     * gf_menu.php to re-render the bar in place after every edit.
     */
    public function getGfSubheader()
    {
        //--- Stock tabs are taken from a regular UNA menu object.
        $sTabsMenu = getParam('gf_header_tabs_menu');
        if(empty($sTabsMenu))
            $sTabsMenu = 'sys_site';

        $aStock = [];
        $oTabsMenu = BxDolMenu::getObjectInstance($sTabsMenu);
        if($oTabsMenu && is_array($aItems = $oTabsMenu->getMenuItems()))
            foreach($aItems as $aItem) {
                if(isset($aItem['name']) && in_array($aItem['name'], ['search', 'more-auto']))
                    continue;

                $aStock[] = $aItem;
            }

        //--- The member's saved choices for the active workspace. The feature
        //--- degrades to the stock tabs until the gf_user_menu table exists.
        $oDb = BxDolDb::getInstance();
        $bPrefs = $oDb->isTableExists('gf_user_menu');

        $aPrefs = [];
        $aCustom = [];
        if($bPrefs) {
            $aRows = $oDb->getAll(
                "SELECT * FROM `gf_user_menu` WHERE `account_id` = :account AND `workspace_id` = :workspace",
                ['account' => getLoggedId(), 'workspace' => $this->getGfActiveWorkspaceId()]
            );
            if(is_array($aRows))
                foreach($aRows as $aRow)
                    if((int)$aRow['custom'])
                        $aCustom[] = $aRow;
                    else
                        $aPrefs[$aRow['item']] = $aRow;
        }

        //--- Merge: stock tabs with per-member overrides, then the member's own links.
        //--- Unsaved items keep their natural position after every explicitly ordered one.
        $aAll = [];
        foreach($aStock as $iIndex => $aTab) {
            $sName = !empty($aTab['name']) ? $aTab['name'] : 'tab' . $iIndex;
            $aPref = isset($aPrefs[$sName]) ? $aPrefs[$sName] : false;

            $aAll[] = [
                'key' => $sName,
                'tab' => $aTab,
                'title' => isset($aTab['title']) ? $aTab['title'] : $sName,
                'hidden' => $aPref ? (int)$aPref['hidden'] : 0,
                'order' => $aPref && (int)$aPref['order'] > 0 ? (int)$aPref['order'] : 10000 + $iIndex,
                'custom' => 0
            ];
        }

        foreach($aCustom as $iIndex => $aRow)
            $aAll[] = [
                'key' => 'c' . $aRow['id'],
                'tab' => $this->_getGfMenuCustomTab($aRow),
                'title' => bx_process_output($aRow['title']),
                'hidden' => (int)$aRow['hidden'],
                'order' => (int)$aRow['order'] > 0 ? (int)$aRow['order'] : 20000 + $iIndex,
                'custom' => 1
            ];

        usort($aAll, function($a, $b) {
            return $a['order'] - $b['order'];
        });

        $aTabs = [];
        $aEditItems = [];
        foreach($aAll as $aItem) {
            if(!$aItem['hidden'])
                $aTabs[] = $aItem['tab'];

            $aEditItems[] = [
                'key' => bx_html_attribute($aItem['key']),
                'title' => $aItem['title'],
                'class_off' => $aItem['hidden'] ? 'gf-mrow-off' : '',
                'bx_if:custom' => [
                    'condition' => (bool)$aItem['custom'],
                    'content' => ['key' => bx_html_attribute($aItem['key'])]
                ]
            ];
        }

        return $this->_oTemplate->parseHtmlByName('_page_toolbar_auth_subheader.html', [
            'bx_repeat:tabs' => $aTabs,
            'bx_repeat:edit_items' => $aEditItems,
            'bx_if:editor' => [
                'condition' => $bPrefs,
                'content' => ['editor' => 1] // non-empty content required by the template compiler
            ]
        ]);
    }

    /**
     * Build a member's own link (gf_user_menu row) as a tab item compatible
     * with the stock items produced by BxBaseMenu::_getMenuItem.
     */
    protected function _getGfMenuCustomTab($aRow)
    {
        $sTitle = bx_process_output($aRow['title']);
        $sTitleAttr = bx_html_attribute($aRow['title']);

        $sUrl = trim((string)$aRow['url']);
        $bExternal = preg_match('/^https?:\/\//i', $sUrl) && strncasecmp($sUrl, BX_DOL_URL_ROOT, strlen(BX_DOL_URL_ROOT)) != 0;
        if(!preg_match('/^https?:\/\//i', $sUrl))
            $sUrl = BX_DOL_URL_ROOT . ltrim($sUrl, '/');

        return [
            'name' => 'c' . $aRow['id'],
            'link' => bx_html_attribute($sUrl),
            'title' => $sTitle,
            'title_attr' => $sTitleAttr,
            'class_add' => 'gf-tab-custom',
            'attrs' => $bExternal ? 'target="_blank" rel="noopener"' : '',
            'bx_if:icon' => ['condition' => true, 'content' => ['icon' => $bExternal ? 'external-link-alt' : 'link']],
            'bx_if:image' => ['condition' => false, 'content' => ['icon_url' => '']],
            'bx_if:icon-html' => ['condition' => false, 'content' => ['icon' => '']],
            'bx_if:title' => ['condition' => true, 'content' => ['title' => $sTitle, 'title_attr' => $sTitleAttr]],
            'bx_if:onclick' => ['condition' => false, 'content' => ['onclick' => '']]
        ];
    }

    function msgBox($sText, $iTimer = 0, $sOnClose = "")
    {
        $iId = time() . mt_rand(1, 1000);

        if($iTimer > 0)
            BxDolTemplate::getInstance()->addJs(array('jquery.anim.js'));

        return $this->_oTemplate->parseHtmlByName('messageBox.html', array(
            'id' => $iId,
            'msgText' => $sText,
            'bx_if:timer' => array(
                'condition' => $iTimer > 0,
                'content' => array(
                    'id' => $iId,
                    'time' => 1000 * $iTimer,
                    'on_close' => $sOnClose,
                )
            )
        ));
    }

    /**
     * Get standard popup box with title.
     *
     * @param  string $sName    - unique name
     * @param  string $sTitle   - translated title
     * @param  string $sContent - content of the box
     * @return HTML   string
     */
    function popupBox($sName, $sTitle, $sContent, $isHiddenByDefault = false)
    {
        $iId = !empty($sName) ? $sName : time();

        return $this->_oTemplate->parseHtmlByName('popup_box.html', array(
            'id' => $iId,
            'wrapper_style' => $isHiddenByDefault ? 'display:none;' : '',
            'title' => $sTitle,
            'content' => $sContent
        ));
    }

    /**
     * Get popup box without title.
     *
     * @param  string $sName    - unique name
     * @param  string $sContent - content of the box
     * @return HTML   string
     */
    function transBox($sName, $sContent, $isHiddenByDefault = false, $isPlaceInCenter = false)
    {
    	return $this->simpleBox($sName, $sContent, $isHiddenByDefault, $isPlaceInCenter, 'popup_trans.html');
    }

    function slideBox($sName, $sContent, $isHiddenByDefault = false)
    {
    	return $this->simpleBox($sName, $sContent, $isHiddenByDefault, false, 'popup_slide.html');
    }

    function inlineBox($sName, $sContent, $isHiddenByDefault = false)
    {
        return $this->simpleBox($sName, $sContent, $isHiddenByDefault, false, 'popup_inline.html');
    }

    protected function simpleBox($sName, $sContent, $isHiddenByDefault, $isPlaceInCenter, $sTemplate) 
    {
    	$iId = !empty($sName) ? $sName : time();

        if(!is_array($sContent))
            $sContent = array('content' => $sContent);

        $sContent = $this->_oTemplate->parseHtmlByName($sTemplate, array_merge(array(
            'id' => $iId,
            'wrapper_class' => '',
            'wrapper_style' => $isHiddenByDefault ? 'display:none;' : '',
            'content' => ''
        ), $sContent));

        if($isPlaceInCenter)
            $sContent = '<div class="login_ajax_wrap">' . $sContent . '</div>';

        return $sContent;
    }

    function simpleBoxContent($sContent, $bWithIndent = true)
    {
        if(!$bWithIndent)
            return $sContent;

        return $this->_oTemplate->parseHtmlByName('popup_content_indent.html', array(
            'content' => $sContent
        ));
    }

    function getIcon($sCode, $aAttrs = array())
    {
        $sIconFont = false;
        $sIconA = false;
        $sIconUrl = false;
        $sIconHtml = false;
        $sIconFontWithHtml = false;

        $sClass = '';
        if(!empty($aAttrs['class'])) {
            $sClass = ' ' . $aAttrs['class'] .' ';
            unset($aAttrs['class']);
        }

        $sAttrs = '';
        foreach($aAttrs as $sKey => $sValue)
            $sAttrs .= ' ' . $sKey . '="' . bx_html_attribute($sValue) . '"';

        if (!empty($sCode)) {
            if (is_numeric($sCode) && (int)$sCode > 0) {
                $oStorage = BxDolStorage::getObjectInstance(BX_DOL_STORAGE_OBJ_IMAGES);
                $sIconUrl = $oStorage ? $oStorage->getFileUrlById((int)$sCode) : false;
            } 
            else {

                if (strpos($sCode, '&lt;img') !== false || strpos($sCode, '<img') !== false) {
                    if(strpos($sCode, '&lt;img') !== false)
                        $sIconHtml = htmlspecialchars_decode($sCode);
                    else
                        $sIconHtml = $sCode;

                    //--- Process Inline SVG
                } else if (strpos($sCode, '&lt;svg') !== false || strpos($sCode, '<svg') !== false) {
                        if(strpos($sCode, '&lt;svg') !== false)
                            $sIconHtml = htmlspecialchars_decode($sCode);
                        else
                            $sIconHtml = $sCode;

                        $sClass .= 'sys-icon sys-icon-svg ';


                        $sIconHtmlClear = strip_tags($sIconHtml, '<svg>');
                        if ($sClass != '' && strpos($sIconHtmlClear, 'class="') !== false)
                            $sIconHtml = str_replace('class="', 'class="' . $sClass, $sIconHtml);
                        else
                            $sIconHtml = str_replace('<svg', '<svg class="' . $sClass . '" ', $sIconHtml);

                        if ($sAttrs != '')
                            $sIconHtml = str_replace('<svg', '<svg ' . $sAttrs . ' ', $sIconHtml);
                }
                else {
                    $sEmojIsRegex =
                        '/[\x{0080}-\x{02AF}'
                        .'\x{0300}-\x{03FF}'
                        .'\x{0600}-\x{06FF}'
                        .'\x{0C00}-\x{0C7F}'
                        .'\x{1DC0}-\x{1DFF}'
                        .'\x{1E00}-\x{1EFF}'
                        .'\x{2000}-\x{209F}'
                        .'\x{20D0}-\x{214F}'
                        .'\x{2190}-\x{23FF}'
                        .'\x{2460}-\x{25FF}'
                        .'\x{2600}-\x{27EF}'
                        .'\x{2900}-\x{29FF}'
                        .'\x{2B00}-\x{2BFF}'
                        .'\x{2C60}-\x{2C7F}'
                        .'\x{2E00}-\x{2E7F}'
                        .'\x{3000}-\x{303F}'
                        .'\x{A490}-\x{A4CF}'
                        .'\x{E000}-\x{F8FF}'
                        .'\x{FE00}-\x{FE0F}'
                        .'\x{FE30}-\x{FE4F}'
                        .'\x{1F000}-\x{1F02F}'
                        .'\x{1F0A0}-\x{1F0FF}'
                        .'\x{1F100}-\x{1F64F}'
                        .'\x{1F680}-\x{1F6FF}'
                        .'\x{1F910}-\x{1F96B}'
                        .'\x{1F980}-\x{1F9E0}]/u';

                    //--- Process Emoji
                    if(preg_match($sEmojIsRegex, $sCode, $aTmp))
                        $sIconHtml = $this->_oTemplate->parseHtmlByName('icon_emoji.html', array(
                            'icon' => $sCode, 
                            'class' => $sClass, 
                            'attrs' => $sAttrs
                        ));
                    else {
                        if (strpos($sCode, '.') === false) {
                            //--- Process animated icon
                            if (strncmp($sCode, 'a:', 2) === 0)
                                $sIconA = substr($sCode, 2);
                            //--- Process font icons
                            else {
                                $sIconFont = $sCode;
                                $sIconFontWithHtml = $this->getFontIconAsHtml($sIconFont, $sClass, $sAttrs);
                            }
                        } 
                        else {
                            //--- Process common image
                            if((!preg_match('/^https?:\/\//', $sCode)))
                                $sIconUrl = $this->_oTemplate->getIconUrl($sCode);
                            else
                                $sIconUrl = $sCode;
                        }
                    }
                }
            }
        }

        return array ($sIconFont, $sIconUrl, $sIconA, $sIconHtml, $sIconFontWithHtml);
    }

    function getFontIconAsHtml($sIconFont, $sClass = '', $sAttrs = '')
    {
        return  '<i class="sys-icon ' . $sIconFont .' ' . $sClass . '"' . $sAttrs . '></i>';
    }

    function getIconAsHtml($sCode, $aAttrs = array())
    {
        $aIcons = $this->getIcon($sCode, $aAttrs);
        return $aIcons[3] . $aIcons[4]; 
    }

    function getTemplateIcon($sName)
    {
        $sUrl = $this->_oTemplate->getIconUrl($sName);
        return !empty($sUrl) ? $sUrl : $this->_oTemplate->getIconUrl('spacer.gif');
    }

    function getTemplateImage($sName)
    {
        $sUrl = $this->_oTemplate->getImageUrl($sName);
        return !empty($sUrl) ? $sUrl : $this->_oTemplate->getImageUrl('spacer.gif');
    }

    function sysIcon ($sIcon, $sName, $sUrl = '', $iWidth = 0)
    {
        return '<div class="sys_icon">' . ($sUrl ? '<a title="'.$sName.'" href="'.$sUrl.'">' : '') . '<img alt="'.$sName.'" src="'.$sIcon.'" '.($iWidth ? 'width='.$iWidth : '').' />' . ($sUrl ? '</a>' : '') . '</div>';
    }

    /**
     * functions for limiting maximal string length
     */
    function getStringWithLimitedLength($sString, $iWidth = 45, $isPopupOnOverflow = false, $bReturnString = true)
    {
        if (empty($sString) || mb_strlen($sString, 'UTF-8') <= $iWidth)
            return $bReturnString ? $sString : array($sString);

        $sResult = '';
        $aWords = mb_split("[\s\r\n]", $sString);
        $iPosition = 0;
        $iWidthReal = $iWidth - 3;
        $iWidthMin = $iWidth - 15;
        foreach($aWords as $sWord) {
            $sWord = trim($sWord);
            $iWord = mb_strlen($sWord, 'UTF-8');
            if ($iPosition + $iWord > $iWidthReal)
                break;

            // add word and continue
            $sResult .= ' ' . $sWord;
            $iPosition += 1 + $iWord;
        }

        // last word is too long, cut it
        if(!$iPosition || $iPosition < $iWidthMin)
            $sResult .= ' ' . mb_substr($sWord, 0, $iWidthReal - $iPosition - $iWord, 'UTF-8');
        $sResult = trim($sResult);

        // add tripple dot
        if(!$isPopupOnOverflow) {
            $sResult .= '...';
            return $bReturnString ? $sResult : array($sResult);
        }

        // add button width popup
        $sId = 'bx-str-limit-' . rand(1, PHP_INT_MAX);
        $sPopup = '<span class="bx-str-limit" onclick="$(\'#' . $sId . '\').dolPopup({pointer:{el:$(this), offset:\'10 1\'}})"/><i class="sys-icon ellipsis-h"></i></span>';
        $sPopup .= '<div id="' . $sId . '" style="display:none;">' . BxTemplFunctions::getInstance()->transBox('', '<div class="bx-def-padding">'.$sString.'</div>') . '</div>';

        return $bReturnString ? $sResult . $sPopup : array($sResult, $sPopup);
    }

    /**
     * Display design box with specified title, template, content and menu.
     * @param $sTitle - design box title, please note that some templates don't use title.
     * @param $sContent - design box content.
     * @param $iTemplateNum - number of design box template, use predefined contants only, default is BX_DB_DEF.
     * @param $mixedMenu - design box menu, it can be:
     *      - object: instance of BxTemplMenu class
     *      - string: menu object identifier
     *      - array: array of menu links to create menu from
     * @param $mixedButtons - design box menu representation, it can be:
     *      - false: design box menu will be used as horizontal menu (tabs)
     *      - array: array of menu links to create menu from. If empty array is used and 'design box menu' isn't empty, then 'design box menu' will be added as one of menu items automatically. If non-empty array is used and 'design box menu' isn't empty then it should be added as one of menu items. Use array('menu' => 1) to define menu item for 'design box menu'.
     * @return string
     *
     * @see BX_DB_CONTENT_ONLY
     * @see BX_DB_DEF
     * @see BX_DB_EMPTY
     * @see BX_DB_NO_CAPTION
     * @see BX_DB_PADDING_CONTENT_ONLY
     * @see BX_DB_PADDING_DEF
     * @see BX_DB_PADDING_NO_CAPTION
     */
    function designBoxContent ($sTitle, $sContent, $iTemplateNum = BX_DB_DEF, $mixedMenu = false, $mixedButtons = array())
    {
        return $this->_oTemplate->parseHtmlByName('designbox_' . (int)$iTemplateNum . '.html', array(
            'title' => $sTitle,
            'designbox_content' => $sContent,
            'caption_item' => $this->designBoxMenu($mixedMenu, $mixedButtons),
        ));
    }

    function designBoxMenu ($mixedMenu, $mixedButtons = array())
    {
        $bUseTabs = is_bool($mixedButtons) && $mixedButtons === true;

        $sClass = $sMenu = '';
        if(!empty($mixedMenu)) {
            if(is_string($mixedMenu)) {
                if(($oMenu = BxTemplMenu::getObjectInstance($mixedMenu)) !== false) {
                    $oMenu->setTemplateById($bUseTabs ? BX_DB_MENU_TEMPLATE_TABS : BX_DB_MENU_TEMPLATE_POPUP);

                    $sMenu = $oMenu->getCode();
                }
                else
                    $sMenu = $mixedMenu;
            } 
            else if(is_array($mixedMenu)) {
                if(isset($mixedMenu['template']) && isset($mixedMenu['menu_items']))
                    $aMenu = $mixedMenu;
                else
                    $aMenu = array('template' => $this->_sDesignBoxMenuTmplDefault, 'menu_items' => $mixedMenu);

                if(($oMenu = new BxTemplMenu($aMenu, $this->_oTemplate)) !== false) {
                    $oMenu->setTemplateById($bUseTabs ? BX_DB_MENU_TEMPLATE_TABS : BX_DB_MENU_TEMPLATE_POPUP);

                    $sMenu = $oMenu->getCode();
                }
                else
                    $sMenu = '';
            }
            else if(is_object($mixedMenu) && is_a($mixedMenu, 'BxTemplMenu')) {
                $mixedMenu->setTemplateById($bUseTabs ? BX_DB_MENU_TEMPLATE_TABS : BX_DB_MENU_TEMPLATE_POPUP);
                if(($mixedMenu instanceof BxBaseMenuMoreAuto) && $mixedMenu->isMoreAuto())
                    $sClass = ' bx-db-menu-tab-more-auto';

                $sMenu = $mixedMenu->getCode();
            }
        }
        $bMenu = !empty($sMenu);

        $sResult = '';
        if($bUseTabs && $bMenu)
            $sResult = $sMenu;
        else if(is_array($mixedButtons)) {
            $sPopup = '';

            if($bMenu) {
                $aButton = array();
                if(empty($mixedButtons))
                    list($aButton, $sPopup) = $this->_designBoxMenuButton($sMenu);
                //--- For backward compatibility
                else if(!empty($mixedButtons['menu']) && is_array($mixedButtons['menu'])) {
                    list($aButton, $sPopup) = $this->_designBoxMenuButton($sMenu, $mixedButtons['menu']);
                    unset($mixedButtons['menu']);
                }

                if(!empty($aButton))
                    $mixedButtons[] = $aButton;
            }

            foreach($mixedButtons as $aButton) {
                if($bMenu && isset($aButton['menu'])) {
                    if(is_numeric($aButton['menu']) && (int)$aButton['menu'] == 1)
                        list($aButton, $sPopup) = $this->_designBoxMenuButton($sMenu);
                    else if(is_array($aButton['menu']))
                        list($aButton, $sPopup) = $this->_designBoxMenuButton($sMenu, $aButton['menu']);

                    if(isset($aButton['menu']))
                        continue;
                }

                $aAttrs = array();
                if(!empty($aButton['onclick']))
                    $aAttrs['onclick'] = $aButton['onclick'];
                
                $aAttrs['class'] = 'bx-btn bx-btn-small';
                if(!empty($aButton['class']))
                    $aAttrs['class'] .= ' ' . trim($aButton['class']);

                $bTmplVarsButtonIcon = !empty($aButton['icon']);
                $aTmplVarsButtonIcon = !$bTmplVarsButtonIcon ? array() : array(
                    'icon' => $aButton['icon']
                );

                $bTmplVarsButtonIconA = !empty($aButton['icon-a']);
                $aTmplVarsButtonIconA = !$bTmplVarsButtonIconA ? array() : array(
                    'icon_a' => $aButton['icon-a']
                );

                $bTmplVarsButtonTitle = !empty($aButton['title']);
                $aTmplVarsButtonTitle = !$bTmplVarsButtonTitle ? array() : array(
                    'title' => $aButton['title']
                );

                $sResult .= $this->_oTemplate->parseHtmlByName('designbox_menu_button.html', array(
                    'attrs' => bx_convert_array2attrs($aAttrs),
                    'bx_if:show_icon' => array(
                        'condition' => $bTmplVarsButtonIcon,
                        'content' => $aTmplVarsButtonIcon
                    ),
                    'bx_if:show_icon_a' => array(
                        'condition' => $bTmplVarsButtonIconA,
                        'content' => $aTmplVarsButtonIconA
                    ),
                    'bx_if:show_title' => array(
                        'condition' => $bTmplVarsButtonTitle,
                        'content' => $aTmplVarsButtonTitle
                    )
                ));
            }

            $sResult .= $sPopup;
        }

        if(!empty($sResult))
            $sResult = $this->_oTemplate->parseHtmlByName('designbox_menu.html', array(
                'class' => $sClass,
                'content' => $sResult
            ));

        return $sResult;
    }

    protected function _designBoxMenuId ()
    {
        return 'bx-menu-db-' . time() . rand(0, PHP_INT_MAX);
    }

    protected function _designBoxMenuButton ($sMenu, $aParams = array())
    {
        $sId = $this->_designBoxMenuId();
        $aButton = array($this->_sDesignBoxMenuIconType => $this->_sDesignBoxMenuIcon, 'onclick' => $this->_sDesignBoxMenuClick);

        if(!empty($aParams)) {
            if(!empty($aParams['id']))
                $sId = $aParams['id'];

            $aButton = array_merge($aButton, $aParams);
        }

        $aButton['onclick'] = bx_replace_markers($aButton['onclick'], array(
            'design_box_menu' => $sId
        ));

        $sMenu = $this->_oTemplate->parseHtmlByName('designbox_menu_popup.html', array(
            'content' => $sMenu
        ));

        return array($aButton, $this->transBox($sId, $sMenu, true));
    }

    /**
     * Get logo URL.
     * @return string
     */
    function getMainLogoUrl()
    {
        return BxDolDesigns::getInstance()->getSiteLogoUrl();
    }

    /**
     * Get mark URL.
     * @return string
     */
    function getMainMarkUrl()
    {
        return BxDolDesigns::getInstance()->getSiteMarkUrl();
    }

    /**
     * Get logo HTML.
     * @return string
     */
    function getMainLogo($aParams = array())
    {
        $oDesigns = BxDolDesigns::getInstance();

        $sAlt = $oDesigns->getSiteLogoAlt();
        if(empty($sAlt))
            $sAlt = getParam('site_title');

        $aLogoImages = [];
        if(($mixedFileUrl = $this->getMainLogoUrl()) && !empty($mixedFileUrl)) {
            if(!is_array($mixedFileUrl))
                $mixedFileUrl = ['light' => $mixedFileUrl];

            foreach($mixedFileUrl as $sName => $sFileUrl) {
                $iLogoWidth = (int)$oDesigns->getSiteLogoWidth();
                $sWidth = $iLogoWidth > 0 ? 'width:' . round($iLogoWidth/16, 3) . 'rem;' : '';

                $iLogoHeight = (int)$oDesigns->getSiteLogoHeight();
                $sHeight = $iLogoHeight > 0 ? 'height:' . round($iLogoHeight/16, 3) . 'rem;' : '';

                $aLogoImages[] = [
                    'id' => 'bx-logo', 
                    'src' => $sFileUrl, 
                    'alt' => bx_html_attribute($sAlt, BX_ESCAPE_STR_QUOTE),
                    'style' => $sWidth . ' ' . $sHeight
                ];
            }
        }
        $bLogoImages = !empty($aLogoImages) && is_array($aLogoImages);

        $aAttrs = array(
            'class' => 'bx-def-font-contrasted',
            'href' => BX_DOL_URL_ROOT,
            'title' => bx_html_attribute($sAlt, BX_ESCAPE_STR_QUOTE)
        );
        if(!empty($aParams['attrs']) && is_array($aParams['attrs']))
            $aAttrs = array_merge($aAttrs, $aParams['attrs']);

        $sTmplName = 'logo_main.html';
        $aTmplVars = [
            'attrs' => bx_convert_array2attrs($aAttrs),
             'bx_if:show_title' => [
                'condition' => !$bLogoImages,
                'content' => [
                    'logo' => $sAlt,
                ]
            ],
            'bx_if:show_logo' => [
                'condition' => $bLogoImages,
                'content' => [
                    'bx_repeat:images' => $aLogoImages,
                ]
            ],
        ];

        bx_alert('system', 'get_logo', 0, 0, [
            'tmpl_name' => &$sTmplName,
            'tmpl_vars' => &$aTmplVars
        ]);

        return $this->_oTemplate->parseHtmlByName($sTmplName, $aTmplVars);
    }

    /**
     * Get HTML code for manifests.
     * @return HTML string to insert into HEAD section
     */
    function getManifests()
    {
        return '<link rel="manifest" href="' . BX_DOL_URL_ROOT . 'manifest.json.php" crossorigin="use-credentials" />';
    }

    /**
     * Get HTML code for meta icons.
     * @return HTML string to insert into HEAD section
     */
    function getMetaIcons()
    {
        // favicon icon
        $sImageUrlFav = '';
        if(($iId = (int)getParam('sys_site_icon')) != 0)
            $sImageUrlFav = BxDolStorage::getObjectInstance(BX_DOL_STORAGE_OBJ_FILES)->getFileUrlById($iId);          

        // svg icon
        $sImageUrlSvg = '';
        if(($iId = (int)getParam('sys_site_icon_svg')) != 0)
            $sImageUrlSvg = BxDolStorage::getObjectInstance(BX_DOL_STORAGE_OBJ_IMAGES)->getFileUrlById($iId);

        if(empty($sImageUrlFav) && empty($sImageUrlSvg))
            $sImageUrlFav = $sImageUrlSvg = $this->_oTemplate->getIconUrl('favicon.svg');

        // apple device icon
        $sImageUrlApl = '';
        if(($iId = (int)getParam('sys_site_icon_apple')) != 0)
            $sImageUrlApl = BxDolTranscoderImage::getObjectInstance(BX_DOL_TRANSCODER_OBJ_ICON_APPLE)->getFileUrl($iId);
        if(empty($sImageUrlApl))
            $sImageUrlApl = $this->_oTemplate->getIconUrl('apple-touch-icon.png');

/* 
 * TODO: 
 * 1. Remove commented code later if it won't be used.
 * 2. Remove 'sys_icon_favicon' and 'sys_icon_facebook' transcoders and related transcoder filters.
 * 
        // facebook icon
        $sImageUrlFcb = '';
        $oTranscoder = BxDolTranscoderImage::getObjectInstance(BX_DOL_TRANSCODER_OBJ_ICON_FACEBOOK);
        $sImageUrlFcb = $oTranscoder->getFileUrl($iId);
        if(empty($sImageUrlFcb))
            $sImageUrlFcb = $this->_oTemplate->getIconUrl('facebook-icon.png');
*/

        $sRet = '';
        if($sImageUrlFav)
            $sRet .= '<link rel="icon" href="' . $sImageUrlFav . '" sizes="any" />';
        if($sImageUrlFav)
            $sRet .= '<link rel="icon" href="' . $sImageUrlSvg . '" type="image/svg+xml" />';
        $sRet .= '<link rel="apple-touch-icon" href="' . $sImageUrlApl . '" />';

/*
        $sRet .= '<link rel="image_src" sizes="100x100" href="' . $sImageUrlFcb . '" />';
*/

        return $sRet;
    }

    function getInjectionHead() 
    {
        return $this->getInjection('getInjHead');
    }

    function getInjectionHeader() 
    {
        return $this->getInjection('getInjHeader');
    }

    function getInjectionFooter() 
    {
        return $this->getInjection('getInjFooter');
    }

    public function getPopupAlert()
    {
        return $this->transBox('bx-popup-alert', $this->_oTemplate->parseHtmlByName('popup_trans_alert_cnt.html', array()), true);
    }

    public function getPopupConfirm()
    {
        return $this->transBox('bx-popup-confirm', $this->_oTemplate->parseHtmlByName('popup_trans_confirm_cnt.html', array()), true);
    }

    public function getPopupPrompt()
    {
        $sInputText = 'bx-popup-prompt-value';
        $aInputText = array(
            'type' => 'text',
            'name' => $sInputText,
            'attrs' => array(
                'id' => $sInputText,
            ),
            'value' => '',
            'caption' => ''
        );

        $oForm = new BxTemplFormView(array(), $this->_oTemplate);
        return $this->transBox('bx-popup-prompt', $this->_oTemplate->parseHtmlByName('popup_trans_prompt_cnt.html', array(
            'input' => $oForm->genRow($aInputText)
        )), true);
    }

    /**
     * Output time wrapped in <time> tag in HTML.
     * Then time is autoformatted using JS upon page load, this is aumatically converted to user's timezone and
     * updated in realtime in case of short periods of 'from now' time format.
     *
     * Short version of this function:
     * @see bx_time_js
     *
     * @param $iUnixTimestamp time as unixtimestamp
     * @param $sFormatIdentifier output format identifier
     *     @see BX_FORMAT_DATE
     *     @see BX_FORMAT_TIME
     *     @see BX_FORMAT_DATE_TIME
     * @param $bForceFormat force provided format and don't use "from now" time autoformat.
     */
    function timeForJs ($iUnixTimestamp, $sFormatIdentifier = BX_FORMAT_DATE, $bForceFormat = false)
    {
        $sDateUTC = bx_time_utc ($iUnixTimestamp);
        return $this->timeForJsFullDate ($sDateUTC, $sFormatIdentifier, $bForceFormat);
    }

    /**
     * Same as @see timeForJs but instead of unxtimestamp full date format is used (ex: 2005-08-15T15:52:01) as passec date param
     */ 
    function timeForJsFullDate ($sDateUTC, $sFormatIdentifier = BX_FORMAT_DATE, $bForceFormat = false, $bUTC = false)
    {
        return '<time datetime="' . $sDateUTC . '" data-bx-format="' . getParam($sFormatIdentifier) . '" data-bx-autoformat="' . ($bForceFormat ? 0 : getParam('sys_format_timeago')) . '" data-bx-utc="' . ($bUTC ? 1 : 0) . '">' . $sDateUTC . '</time>';
    }
    
    function statusOnOff ($mixed, $isMsg = false)
    {
        if ((is_bool($mixed) && !$mixed) || (is_string($mixed) && 'fail' == $mixed))
            return '<i class="sys-icon circle col-red2"></i> ' . ($isMsg ? _t('_sys_off') : '');
        elseif (is_string($mixed) && 'warn' == $mixed)
            return '<i class="sys-icon circle col-red3"></i> ' . ($isMsg ? _t('_sys_warn') : '');
        elseif (is_string($mixed) && 'undef' == $mixed)
            return '<i class="sys-icon circle col-gray"></i> ' . ($isMsg ? _t('_undefined') : '');
        else
            return '<i class="sys-icon circle col-green1"></i> ' . ($isMsg ? _t('_sys_on') : '');
    }

    /**
     * Ouputs HTML5 video player.
     * @param $sUrlPoster video poster image
     * @param $sUrlMP4 .mp4 video
     * @param $sUrlMP4Hd .mp4 video in better quality
     * @param $aAttrs custom attributes, defaults are: controls="" preload="none" autobuffer=""
     * @param $sStyles custom styles, defaults are: width:100%; height:auto;
     */
    function videoPlayer ($sUrlPoster, $sUrlMP4, $sUrlMP4Hd = '', $aAttrs = false, $sStyles = false, $bDynamicMode = false)
    {
        $oPlayer = BxDolPlayer::getObjectInstance();
        if(!$oPlayer)
            return '';

        if($sStyles === false)
            $sStyles = 'width:100%; height:auto;';

        return $oPlayer->getCodeVideo (BX_PLAYER_STANDARD, array(
            'poster' => $sUrlPoster,
            'mp4' => array('sd' => $sUrlMP4, 'hd' => $sUrlMP4Hd),
            'attrs' => $aAttrs,
            'styles' => $sStyles,
        ), $bDynamicMode);
    }

    protected function getInjection($sPrefix)
    {
        $sContent = '';

        $aMethods = get_class_methods($this);
        foreach($aMethods as $sMethod)
            if(preg_match("/^(" . $sPrefix . ")[A-Z].+$/", $sMethod))
                $sContent .= $this->$sMethod();

        return $sContent;
    }

    protected function getInjHeadLiveUpdates() 
    {
        $sContent = '';

        if(($oLiveUpdates = BxDolLiveUpdates::getInstance()) !== false)
            $sContent .= $oLiveUpdates->init();

        return $sContent;
    }

    protected function getInjHeaderPushNotifications() 
    {
        $sResult = '';

        if(($oPush = BxDolPush::getObjectInstance()) !== false)
            $sResult = $oPush->getCode('page_header');

        return $sResult;
    }

    protected function getInjHeaderPopupLoading() 
    {
        return $this->transBox('bx-popup-loading', $this->_oTemplate->parsePageByName('popup_loading.html', array()), true);  
    }
    
    protected function getInjFooterMenuLoading() 
    {
        return $this->_oTemplate->parsePageByName('menu_loading.html', array());  
    }

    protected function getInjFooterPopupMenus() 
    {
        $sContent = '';

        $oSearch = new BxTemplSearch();
        $oSearch->setLiveSearch(true);
        $sContent .= $this->_oTemplate->parsePageByName('search.html', array(
            'search_form' => $oSearch->getForm(BX_DB_CONTENT_ONLY),
            'results' => $oSearch->getResultsContainer(),
        ));

        $sContent .= $this->_oTemplate->getMenu ('sys_site');
        if(isLogged()) {
            $sContent .= $this->_oTemplate->getMenu ('sys_add_content');
            $sContent .= $this->_oTemplate->getMenu ('sys_account_popup');
        }

        return $sContent;
    }
    
    protected function getInjFooterPopupApps() 
    {
        if($this->_oTemplate->getPageType() != BX_PAGE_TYPE_APPLICATION) 
            return '';

        $oMenu = BxDolMenu::getObjectInstance('sys_homepage');
        if(!$oMenu) 
            return '';

        $this->_oTemplate->addJs(['popper.js']);
        return $this->_oTemplate->parsePageByName('menu_apps.html', [
            'name' => 'sys-menu-apps',
            'title' => _t('_apps'),
            'bx_repeat:menu_items' => $oMenu->getMenuItems(),
        ]);
    }

    protected function getInjFooterPopups() 
    {
        $sContent = '';

        $sContent .= $this->getPopupAlert();
        $sContent .= $this->getPopupConfirm();
        $sContent .= $this->getPopupPrompt();

        return $sContent;
    }

    protected function getInjFooterEmbed() 
    {    
        // Load embed files
        $oEmbed = BxDolEmbed::getObjectInstance(false);
        if ($oEmbed) 
            return $oEmbed->addJsCss() . $oEmbed->addProcessLinkMethod();

        return '';
    }
}

/** @} */
