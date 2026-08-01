<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    BaseGroups Base classes for groups modules
 * @ingroup     UnaModules
 *
 * @{
 */

class BxBaseModGroupsSearchResult extends BxBaseModProfileSearchResult
{    
    public function __construct($sMode = '', $aParams = array())
    {
        if (!isset($aParams['unit_views'])) {
            if (!is_array($aParams))
                $aParams = [];
            $aParams['unit_views'] = array('gallery' => 'unit.html', 'showcase' => 'unit_with_cover_showcase.html');
        }
        parent::__construct($sMode, $aParams);
    }

    public function displaySearchBox ($sContent, $sPaginate = '')
    {
        $aResult = parent::displaySearchBox($sContent, $sPaginate);

        if(!empty($aResult['content']) && $this->oModule->_oConfig->getJsClass('main') != '') {
            $this->oModule->_oTemplate->addJs(['modules/base/groups/js/|main.js', 'main.js']);

            $aResult['content'] = $this->oModule->_oTemplate->getJsCode('main', [
                'sModule' => $this->oModule->getName()
            ], [
                'wrap' => true,
                'mask' => "if(window['{object}'] == undefined) window['{object}'] = new {class}({params});"
            ]) . $aResult['content'];
        }
        
        return $aResult;
    }

    public function processingAPI ($bForceGetData = false) 
    {
        $aResult = parent::processingAPI($bForceGetData);

        switch($this->_sMode) {
            case 'created_entries':
                if(isset($this->_aParams['author']))
                    $aResult['params']['author'] = $this->_aParams['author'];
                break;

            case 'joined_entries':
                if(isset($this->_aParams['joined_profile']))
                    $aResult['params']['joined_profile'] = $this->_aParams['joined_profile'];
                break;

            case 'followed_entries':
                if(isset($this->_aParams['followed_profile']))
                    $aResult['params']['followed_profile'] = $this->_aParams['followed_profile'];
                break;

            case 'context':
                if(isset($this->_aParams['context']))
                    $aResult['params']['context'] = $this->_aParams['context'];
                break;

            case 'connections':
                foreach(['object', 'type', 'profile', 'profile2', 'mutual'] as $sParam)
                    if(isset($this->_aParams[$sParam]))
                        $aResult['params'][$sParam] = $this->_aParams[$sParam];
                break;
        }
        
        return $aResult;
    }

    protected function addConditionsForPrivateContent($CNF, $oProfile, $aCustomGroup = array()) 
    {
        if(empty($CNF['OBJECT_PRIVACY_VIEW']))
            return;

        $oPrivacy = BxDolPrivacy::getObjectInstance($CNF['OBJECT_PRIVACY_VIEW']);
        if(!$oPrivacy)
            return;

        parent::addConditionsForPrivateContent($CNF, $oProfile, array_merge($aCustomGroup, $oPrivacy->getPartiallyVisiblePrivacyGroups()));
    }

    protected function addConditionsForAuthorStatus($CNF)
    {
        return;
    }

    protected function addConditionsForCf($CNF)
    {
        if(empty($CNF['FIELD_CF']))
            return;

        $oCf = BxDolContentFilter::getInstance();
        if(!$oCf->isEnabled()) 
            return;

        $aConditions = $oCf->getConditions($this->aCurrent['join']['profile']['table'], $CNF['FIELD_CF']);
        if(!empty($aConditions) && is_array($aConditions))
            $this->aCurrent['restriction'] = array_merge($this->aCurrent['restriction'], $aConditions);
    }

    protected function _setAuthorConditions($sMode, $aParams, &$oProfileAuthor)
    {
        $CNF = &$this->oModule->_oConfig->CNF;

        if(!($oProfileAuthor = BxDolProfile::getInstance((int)$aParams['author']))) 
            return !($this->isError = true);

        $iProfileAuthor = $oProfileAuthor->id();
        $this->aCurrent['restriction']['owner']['value'] = $iProfileAuthor;

        if(!empty($aParams['per_page']))
            $this->aCurrent['paginate']['perPage'] = is_numeric($aParams['per_page']) ? (int)$aParams['per_page'] : (int)getParam($aParams['per_page']);

        $this->sBrowseUrl = 'page.php?i=' . $CNF['URI_JOINED_ENTRIES'] . '&profile_id={profile_id}';
        $this->aCurrent['title'] = _t($CNF['T']['txt_all_entries_by_author']);
        $this->aCurrent['rss']['link'] = 'modules/?r=' . $this->oModule->_oConfig->getUri() . '/rss/' . $sMode . '/' . $iProfileAuthor;

        return true;
    }

    protected function _setFavoriteConditions($sMode, $aParams, &$oProfileAuthor)
    {
        $CNF = &$this->oModule->_oConfig->CNF;

        if(!($oProfileAuthor = BxDolProfile::getInstance((int)$aParams['user']))) 
            return !($this->isError = true);

        $iListId = 0;
        if(isset($aParams['list_id']))
            $iListId = (int)$aParams['list_id'];

        $iProfileAuthor = $oProfileAuthor->id();
        $oFavorite = $this->oModule->getObjectFavorite();
        if(!$oFavorite || (!$oFavorite->isPublic() && $iProfileAuthor != bx_get_logged_profile_id())) 
            return !($this->isError = true);

        $aConditions = $oFavorite->getConditionsTrack($CNF['TABLE_ENTRIES'], 'id', $iProfileAuthor, $iListId);
        if(!empty($aConditions) && is_array($aConditions)) {
            if(empty($this->aCurrent['restriction']) || !is_array($this->aCurrent['restriction']))
                $this->aCurrent['restriction'] = [];
            $this->aCurrent['restriction'] = array_merge($this->aCurrent['restriction'], $aConditions['restriction']);

            if(empty($this->aCurrent['join']) || !is_array($this->aCurrent['join']))
                $this->aCurrent['join'] = [];
            $this->aCurrent['join'] = array_merge($this->aCurrent['join'], $aConditions['join']);
        }

        $this->sBrowseUrl = 'page.php?i=' . $CNF['URI_JOINED_ENTRIES'] . '&profile_id={profile_id}';
        $this->aCurrent['title'] = _t($CNF['T']['txt_all_entries_by_author']);
        $this->aCurrent['rss']['link'] = 'modules/?r=' . $this->oModule->_oConfig->getUri() . '/rss/' . $sMode . '/' . $iProfileAuthor;

        return true;
    }

    protected function _updateCurrentForJoinedEntries($sMode, $aParams, &$oJoinedProfile)
    {
        $CNF = &$this->oModule->_oConfig->CNF;

        if(!($oJoinedProfile = BxDolProfile::getInstance((int)$aParams['joined_profile'])))
            return !($this->isError = true);

        $sDbPrefix = $this->oModule->_oConfig->getDbPrefix();

        $this->aCurrent['join']['fans'] = [
            'type' => 'INNER',
            'table' => $sDbPrefix . 'fans',
            'mainField' => 'id',
            'onField' => 'content',
            'joinFields' => ['initiator'],
        ];

        $this->aCurrent['restriction']['fans'] = ['value' => $oJoinedProfile->id(), 'field' => 'initiator', 'operator' => '=', 'table' => $sDbPrefix . 'fans'];

        $this->sBrowseUrl = 'page.php?i=' . $CNF['URI_JOINED_ENTRIES'] . '&profile_id={profile_id}';
        $this->aCurrent['title'] = ($sKey = 'txt_joined_entries') && !empty($CNF['T'][$sKey]) ? _t($CNF['T'][$sKey]) : '';
        $this->aCurrent['rss']['link'] = 'modules/?r=' . $this->oModule->_oConfig->getUri() . '/rss/' . $sMode . '/' . $oJoinedProfile->id();

        return true;
    }

    protected function _updateCurrentForContext($sMode, $aParams, &$oProfileContext)
    {
        $CNF = &$this->oModule->_oConfig->CNF;

        if(!($oProfileContext = BxDolProfile::getInstance((int)$aParams['context'])))
            return !($this->isError = true);

        $iProfileIdContext = $oProfileContext->id();
        $this->aCurrent['restriction']['context'] = [
            'value' => -$iProfileIdContext,
            'field' => $CNF['FIELD_ALLOW_VIEW_TO'],
            'operator' => '=',
            'table' => $CNF['TABLE_ENTRIES']
        ];

        if(!empty($aParams['per_page']))
            $this->aCurrent['paginate']['perPage'] = is_numeric($aParams['per_page']) ? (int)$aParams['per_page'] : (int)getParam($aParams['per_page']);

        $this->sBrowseUrl = 'page.php?i=' . $CNF['URI_ENTRIES_BY_CONTEXT'] . '&profile_id={profile_id}';
        $this->aCurrent['title'] = _t($CNF['T']['txt_all_entries_by_context']);
        $this->aCurrent['rss']['link'] = 'modules/?r=' . $this->oModule->_oConfig->getUri() . '/rss/' . $sMode . '/' . $iProfileIdContext;

        return true;
    }
    
    protected function _updateCurrentForFollowedEntries($sMode, $aParams, &$oJoinedProfile)
    {
        $CNF = &$this->oModule->_oConfig->CNF;

        if(!($oJoinedProfile = BxDolProfile::getInstance((int)$aParams['followed_profile'])))
            return !($this->isError = true);

        $this->aCurrent['join']['followed'] = [
            'type' => 'INNER',
            'table' => 'sys_profiles_conn_subscriptions',
            'mainField' => 'id',
            'onField' => 'content',
            'joinFields' => ['initiator'],
        ];

        $this->aCurrent['restriction']['followed'] = ['value' => $oJoinedProfile->id(), 'field' => 'initiator', 'operator' => '=', 'table' => 'sys_profiles_conn_subscriptions'];

        $this->sBrowseUrl = 'page.php?i=' . $CNF['URI_FOLLOWED_ENTRIES'] . '&profile_id={profile_id}';
        $this->aCurrent['title'] = ($sKey = 'txt_followed_entries') && !empty($CNF['T'][$sKey]) ? _t($CNF['T'][$sKey]) : '';
        $this->aCurrent['rss']['link'] = 'modules/?r=' . $this->oModule->_oConfig->getUri() . '/rss/' . $sMode . '/' . $oJoinedProfile->id();

        return true;
    }
}

/** @} */
