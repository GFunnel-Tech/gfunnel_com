<?php
/**
 * GFunnel Nav module — the GFunnel-owned left navigation.
 *
 * serviceSidebar() renders the sidebar by merging the global default items
 * (`gfn_items`) with the viewer's per-workspace overrides (`gfn_user_items`) —
 * the same merge model the subheader hub tabs use (getGfSubheader + gf_user_menu).
 * Icons are keys mapped to inline SVG here; URLs are stored relative and
 * resolved at render. The customization endpoint is serviceSaveMenu().
 */

defined('BX_DOL') or die('hack attempt');

class BxGfNavModule extends BxDolModule
{
    function __construct(&$aModule)
    {
        parent::__construct($aModule);
    }

    /**
     * @page service Service Calls
     * @section bx_gfunnel_nav GFunnel Nav
     * Render the left navigation. Called by BxBaseFunctions::getGfSidebar()
     * (the gf_sidebar component key in the app layout).
     *
     * @return string sidebar HTML, or '' for visitors.
     */
    public function serviceSidebar()
    {
        if(!isLogged())
            return '';

        $iAccount = (int)getLoggedId();
        $iWorkspace = (int)BxTemplFunctions::getInstance()->getGfActiveWorkspaceId();

        $aItems = $this->_oDb->getItems();
        $aUser = $this->_oDb->getUserItems($iAccount, $iWorkspace);

        //--- Index the member's overrides by item key; collect custom rows.
        $aOverride = array();
        $aCustom = array();
        foreach($aUser as $aRow) {
            if((int)$aRow['custom'] == 1)
                $aCustom[] = $aRow;
            elseif(!empty($aRow['item']))
                $aOverride[$aRow['item']] = $aRow;
        }

        //--- Merge defaults (with per-member hide/order) + the member's own links.
        $aMerged = array();
        foreach($aItems as $iIndex => $aItem) {
            $aOv = isset($aOverride[$aItem['key']]) ? $aOverride[$aItem['key']] : false;
            if($aOv && (int)$aOv['hidden'])
                continue;

            $aMerged[] = array(
                'sort' => ($aOv && (int)$aOv['order'] > 0) ? (int)$aOv['order'] : (int)$aItem['order'],
                'title' => $aItem['title'],
                'url' => $this->_resolveUrl($aItem['url']),
                'icon' => $aItem['icon'],
                'match' => $aItem['key'],
            );
        }
        foreach($aCustom as $aRow) {
            $aMerged[] = array(
                'sort' => (int)$aRow['order'],
                'title' => $aRow['title'],
                'url' => $this->_resolveUrl($aRow['url']),
                'icon' => !empty($aRow['icon']) ? $aRow['icon'] : 'dot',
                'match' => '',
            );
        }

        usort($aMerged, function($a, $b) {
            return $a['sort'] == $b['sort'] ? 0 : ($a['sort'] < $b['sort'] ? -1 : 1);
        });

        //--- Active item from the current request path.
        $sReq = (string)(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
        $sPath = (string)parse_url($sReq, PHP_URL_PATH);

        $aRepeat = array();
        foreach($aMerged as $aM) {
            $bActive = false;
            if($aM['match'] === 'home')
                $bActive = ($sPath === '/' || $sPath === '' || $sPath === '/home');
            elseif($aM['match'] !== '' && (strpos($sPath, '/' . $aM['match']) !== false || strpos($sReq, 'i=' . $aM['match']) !== false))
                $bActive = true;

            $aRepeat[] = array(
                'title' => bx_html_attribute($aM['title']),
                'url' => bx_html_attribute($aM['url']),
                'icon' => $this->getIcon($aM['icon']),
                'class_active' => $bActive ? 'gf-side-item-active' : '',
            );
        }

        return $this->_oTemplate->parseHtmlByName('sidebar.html', array(
            'bx_repeat:items' => $aRepeat,
            'settings_url' => bx_html_attribute($this->_resolveUrl($this->_url('gf_nav_settings_url', 'page.php?i=settings'))),
            'rules_url' => bx_html_attribute($this->_resolveUrl($this->_url('gf_nav_rules_url', 'page.php?i=rules'))),
            'privacy_url' => bx_html_attribute($this->_resolveUrl($this->_url('gf_nav_privacy_url', 'page.php?i=privacy'))),
            'terms_url' => bx_html_attribute($this->_resolveUrl($this->_url('gf_nav_terms_url', 'page.php?i=terms-of-service'))),
            'year' => (int)date('Y'),
        ));
    }

    /**
     * Customization endpoint. Dispatches a member's sidebar edits to the DB.
     * $sAction: hide | show | add | delete | reorder | reset.
     *
     * @return array {ok:bool}
     */
    public function serviceSaveMenu($sAction, $aData = array())
    {
        if(!isLogged())
            return array('ok' => false);

        $iAccount = (int)getLoggedId();
        $iWorkspace = (int)BxTemplFunctions::getInstance()->getGfActiveWorkspaceId();
        $bOk = false;

        switch($sAction) {
            case 'hide':
                $bOk = $this->_oDb->setHidden($iAccount, $iWorkspace, (string)($aData['item'] ?? ''), true);
                break;
            case 'show':
                $bOk = $this->_oDb->setHidden($iAccount, $iWorkspace, (string)($aData['item'] ?? ''), false);
                break;
            case 'add':
                $sTitle = trim((string)($aData['title'] ?? ''));
                $sUrl = trim((string)($aData['url'] ?? ''));
                if($sTitle !== '' && $sUrl !== '')
                    $bOk = $this->_oDb->addCustom($iAccount, $iWorkspace, $sTitle, $sUrl);
                break;
            case 'delete':
                $bOk = $this->_oDb->deleteUserRow($iAccount, $iWorkspace, (int)($aData['id'] ?? 0));
                break;
            case 'reorder':
                $aKeys = isset($aData['keys']) && is_array($aData['keys']) ? $aData['keys'] : array();
                $bOk = $this->_oDb->setOrder($iAccount, $iWorkspace, $aKeys);
                break;
            case 'reset':
                $bOk = $this->_oDb->resetUser($iAccount, $iWorkspace);
                break;
        }

        return array('ok' => (bool)$bOk);
    }

    /**
     * Resolve a stored relative URL: empty => site root; absolute http(s) as-is;
     * page.php via permalink; otherwise under the site root.
     */
    protected function _resolveUrl($sUrl)
    {
        $sUrl = trim((string)$sUrl);
        if($sUrl === '')
            return BX_DOL_URL_ROOT;
        if(preg_match('/^https?:\/\//i', $sUrl))
            return $sUrl;
        if(strncmp($sUrl, 'page.php', 8) === 0)
            return BxDolPermalinks::getInstance()->permalink($sUrl);
        return BX_DOL_URL_ROOT . ltrim($sUrl, '/');
    }

    /**
     * A per-link URL override via sys_option (defaults to $sDefault).
     */
    protected function _url($sParam, $sDefault)
    {
        $sVal = trim((string)getParam($sParam));
        return $sVal !== '' ? $sVal : $sDefault;
    }

    /**
     * Icon key -> inline SVG. Unknown keys fall back to a dot.
     */
    public function getIcon($sKey)
    {
        $aPaths = array(
            'home'        => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
            'message'     => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
            'sales'       => '<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>',
            'memory'      => '<path d="M12 5a3 3 0 1 0-5.997.125 4 4 0 0 0-2.526 5.77 4 4 0 0 0 .556 6.588A4 4 0 1 0 12 18Z"/><path d="M12 5a3 3 0 1 1 5.997.125 4 4 0 0 1 2.526 5.77 4 4 0 0 1-.556 6.588A4 4 0 1 1 12 18Z"/>',
            'apps'        => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
            'social'      => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
            'explore'     => '<circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/>',
            'communities' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
            'market'      => '<path d="M3 9h18l-1 11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><path d="M3 9l2-5h14l2 5"/><path d="M9 13a3 3 0 0 0 6 0"/>',
            'calendar'    => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            'learning'    => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
            'partners'    => '<path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"/><path d="m21 3-9.24 9.24"/>',
            'settings'    => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
            'dot'         => '<circle cx="12" cy="12" r="3"/>',
        );
        $sBody = isset($aPaths[$sKey]) ? $aPaths[$sKey] : $aPaths['dot'];
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $sBody . '</svg>';
    }
}
