<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Organizations Organizations
 * @ingroup     UnaModules
 *
 * @{
 */

define('BX_ORGANIZATIONS_ACTION_SWITCH_TO_PROFILE', 'switch_to_profile');

/**
 * Organizations profiles module.
 */
class BxOrgsModule extends BxBaseModGroupsModule
{
    function __construct(&$aModule)
    {
        parent::__construct($aModule);

        $CNF = &$this->_oConfig->CNF;

        $this->_aSearchableNamesExcept[] = $CNF['FIELD_AUTHOR'];
        $this->_aSearchableNamesExcept[] = $CNF['FIELD_JOIN_CONFIRMATION'];
    }

    /**
     * Check if this module entry can be used as profile
     */
    public function serviceActAsProfile ()
    {
        return true;
    }

    public function serviceGetSearchResultUnit ($iContentId, $sUnitTemplate = '')
    {
        if(empty($sUnitTemplate))
            $sUnitTemplate = 'unit_with_cover.html';

        return parent::serviceGetSearchResultUnit($iContentId, $sUnitTemplate);
    }

    /**
     * @see BxBaseModProfileModule::serviceGetSpaceTitle
     */ 
    public function serviceGetSpaceTitle()
    {
		$aExcludeModules = explode(',', getParam('sys_hide_post_to_context_for_privacy'));
		if (in_array($this->_aModule['name'], $aExcludeModules))
              return BxBaseModProfileModule::serviceGetSpaceTitle();
		else
			return _t($this->_oConfig->CNF['T']['txt_sample_single']);
    }
    
    /**
     * @see iBxDolProfileService::serviceGetParticipatingProfiles
     */ 
    public function serviceGetParticipatingProfiles($iProfileId, $aConnectionObjects = false)
    {
		$aExcludeModules = explode(',', getParam('sys_hide_post_to_context_for_privacy'));
        if (false === $aConnectionObjects){
			if (in_array($this->_aModule['name'], $aExcludeModules)){
				$aConnectionObjects = array('sys_profiles_friends');
            }
            else{
                $aConnectionObjects = array('sys_profiles_subscriptions');
                if (isset($this->_oConfig->CNF['OBJECT_CONNECTIONS'])){
                    $aConnectionObjects = array($this->_oConfig->CNF['OBJECT_CONNECTIONS'], 'sys_profiles_subscriptions');
                }
            }
        } 
        return BxBaseModProfileModule::serviceGetParticipatingProfiles($iProfileId, $aConnectionObjects);
    }
    
    public function servicePrepareFields ($aFieldsProfile)
    {
        $a = parent::_servicePrepareFields($aFieldsProfile, array('org_cat' => 35), array(
            'org_desc' => 'description',
        ));
        if (!empty($a['fullname'])) {
            $a['org_name'] = $a['fullname'];
            unset($a['fullname']);
        }
        return $a;
    }

    public function serviceBrowseAlphabetical ($sUnitView = false, $bEmptyMessage = false, $bAjaxPaginate = true)
    {
        return $this->_serviceBrowse ('alphabetical', $sUnitView ? array('unit_view' => $sUnitView) : false, BX_DB_PADDING_DEF, $bEmptyMessage, $bAjaxPaginate);
    }

    public function serviceGetTimelineData()
    {
    	return BxBaseModProfileModule::serviceGetTimelineData();
    }

    /**
     * GFunnel — Organization Overview block.
     *
     * A lean "workspace home" for the organization profile page: a welcome /
     * assistant card wired to real GFunnel actions and a Calendar connect
     * card. Every link points at a real page — no placeholder data. Add it via
     * Studio as a Service block (module "Organizations", service "overview") or
     * see docs/organization-overview-block.md.
     *
     * @param $iContentId organization content id (auto-passed on a profile page)
     * @return string block HTML
     */
    public function serviceOverview ($iContentId = 0)
    {
        if(empty($iContentId))
            $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);

        $sTitle = '';
        $sThumb = '';
        $sUrl = BX_DOL_URL_ROOT;
        $sEditUrl = '';
        if($iContentId && ($oProfile = BxDolProfile::getInstanceByContentAndType($iContentId, $this->getName()))) {
            $sTitle = $oProfile->getDisplayName();
            $sThumb = $oProfile->getThumb();
            $sUrl = $oProfile->getUrl();
            $sEditUrl = $oProfile->getEditUrl();
        }

        $this->_oTemplate->addCss(['overview.css']);

        return $this->_oTemplate->parseHtmlByName('overview.html', [
            'title' => $sTitle !== '' ? $sTitle : 'Your workspace',
            'initial' => $sTitle !== '' ? mb_strtoupper(mb_substr($sTitle, 0, 1)) : 'W',
            'thumb' => $sThumb,
            'thumb_style' => $sThumb !== '' ? 'background-image:url(' . $sThumb . ')' : '',
            'profile_url' => $sUrl,
            'edit_url' => $sEditUrl !== '' ? $sEditUrl : $sUrl,
            'ai_url' => BX_DOL_URL_ROOT . 'agents.php',
            'post_url' => BX_DOL_URL_ROOT . 'create-post',
            'event_url' => BX_DOL_URL_ROOT . 'create-event-profile',
            'space_url' => BX_DOL_URL_ROOT . 'create-space-profile',
            'events_url' => BX_DOL_URL_ROOT . 'events-home',
        ]);
    }

    public function serviceGetNotificationsInsertData($oAlert, $aHandler, $aDataItems)
    {
        if($oAlert->sAction != 'join_request' || empty($aDataItems) || !is_array($aDataItems))
            return $aDataItems;

        $aDataItem = reset($aDataItems);
        $aDataItem['owner_id'] = $oAlert->aExtras['performer_id'];

        $aDataItems[] = $aDataItem;
        return $aDataItems;
    }

    public function serviceGetNotificationsJoinRequest($aEvent)
    {
        $CNF = &$this->_oConfig->CNF;

        $sAction = 'join';
        $oConnection = BxDolConnection::getObjectInstance($CNF['OBJECT_CONNECTIONS']);
        if($oConnection !== false && $oConnection->isConnected($aEvent['object_owner_id'], $aEvent['owner_id'], true))
            $sAction = 'accept';

        if($aEvent['owner_id'] == $aEvent['object_owner_id'])
            return $this->_serviceGetNotification($aEvent, 'join_request', $CNF['T']['txt_ntfs_' . $sAction . '_request']);

        return $this->_serviceGetNotification($aEvent, 'join_request', $CNF['T']['txt_ntfs_' . $sAction . '_request_for_owner']);
    }

    public function onFanRemovedFromAdmins($iGroupProfileId, $iProfileId)
    {
        if (!($oProfile = BxDolProfile::getInstance($iProfileId)))
            return false;
        $oAccount = $oProfile->getAccountObject();
        $oAccount->updateProfileContextAuto();
    }

    public function isAllowedActionByRole($sAction, $aDataEntry, $iGroupProfileId, $iProfileId)
    {
        $bResult = parent::isAllowedActionByRole($sAction, $aDataEntry, $iGroupProfileId, $iProfileId);
        if(!$bResult)
            return $bResult;

        $iProfileRole = $this->_oDb->getRole($iGroupProfileId, $iProfileId);

        switch($sAction) {
            case BX_ORGANIZATIONS_ACTION_SWITCH_TO_PROFILE:
                $bResult = $this->isRole($iProfileRole, BX_BASE_MOD_GROUPS_ROLE_ADMINISTRATOR) || $this->isRole($iProfileRole, BX_BASE_MOD_GROUPS_ROLE_MODERATOR);
                break;
        }

        return $bResult;
    }

    public function checkAllowedCompose (&$aDataEntry, $isPerformAction = false)
    {
        return BxBaseModProfileModule::checkAllowedCompose ($aDataEntry, $isPerformAction);
    }

    public function checkAllowedContact($aDataEntry, $isPerformAction = false)
    {
        return BxBaseModProfileModule::checkAllowedContact($aDataEntry, $isPerformAction);
    }
}

/** @} */
