<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Persons Persons
 * @ingroup     UnaModules
 *
 * @{
 */

/**
 * Person profiles module.
 */
class BxPersonsModule extends BxBaseModProfileModule
{
    function __construct(&$aModule)
    {
        parent::__construct($aModule);

        $this->_aSearchableNamesExcept[] = $this->_oConfig->CNF['FIELD_AUTHOR'];
    }

    public function servicePrepareFields ($aFieldsProfile)
    {
        $a = parent::_servicePrepareFields($aFieldsProfile, array(), array());
        if (empty($a['fullname']))
            $a['fullname'] = $aFieldsProfile['name'];
        return $a;
    }

    /**
     * GFunnel - structured profile overview (service block).
     *
     * Rendered on the person profile page via Studio Page Builder
     * (Add block -> Service -> Persons -> overview). Real data only: name,
     * avatar, bio (description), location and member-since come from the
     * profile row; the stat strip and card grid are live connection and view
     * counts. The cover uses the branded gradient unless a real cover image is
     * set. Sections with no real source (e.g. a pinned team post) are omitted
     * rather than filled with placeholder content.
     *
     * @param int $iContentId person content id (the profile page passes it in)
     * @return string block HTML, or empty string when the profile is missing
     */
    public function serviceOverview ($iContentId = 0)
    {
        $CNF = &$this->_oConfig->CNF;

        if(empty($iContentId))
            $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);

        $aContentInfo = $iContentId ? $this->_oDb->getContentInfoById((int)$iContentId) : array();
        $oProfile = BxDolProfile::getInstanceByContentAndType((int)$iContentId, $this->getName());
        if(empty($aContentInfo) || !$oProfile)
            return '';

        $iProfileId = (int)$oProfile->id();
        $sTitle = $oProfile->getDisplayName();
        $sThumb = $oProfile->getThumb();
        $sUrl = $oProfile->getUrl();
        $sEditUrl = $oProfile->getEditUrl();

        // Owner / editor: only they see the edit affordances.
        $bEditable = $oProfile->isAllowedEdit();

        // Bio, location, member-since - rendered only when the field is real.
        $sBio = isset($aContentInfo[$CNF['FIELD_TEXT']]) ? trim(strip_tags((string)$aContentInfo[$CNF['FIELD_TEXT']])) : '';
        $sLocation = isset($aContentInfo[$CNF['FIELD_LOCATION']]) ? trim((string)$aContentInfo[$CNF['FIELD_LOCATION']]) : '';
        $iAdded = isset($aContentInfo[$CNF['FIELD_ADDED']]) ? (int)$aContentInfo[$CNF['FIELD_ADDED']] : 0;
        $sMemberSince = $iAdded > 0 ? date('Y', $iAdded) : '';

        // Live counts: friends (mutual) and subscribers, plus profile views.
        $iConnections = 0;
        if(($oConnFriends = BxDolConnection::getObjectInstance('sys_profiles_friends')))
            $iConnections = (int)$oConnFriends->getConnectedInitiatorsCount($iProfileId, true);

        $iFollowers = 0;
        if(($oConnSubs = BxDolConnection::getObjectInstance('sys_profiles_subscriptions')))
            $iFollowers = (int)$oConnSubs->getConnectedInitiatorsCount($iProfileId);

        $iViews = isset($aContentInfo[$CNF['FIELD_VIEWS']]) ? (int)$aContentInfo[$CNF['FIELD_VIEWS']] : 0;

        // Real cover image (transcoded). Falls back to the branded gradient
        // (in overview.css) when no cover file is set.
        $sCoverStyle = '';
        if(!empty($CNF['FIELD_COVER']) && !empty($aContentInfo[$CNF['FIELD_COVER']]) && !empty($CNF['OBJECT_IMAGES_TRANSCODER_COVER'])) {
            $oCoverTranscoder = BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_IMAGES_TRANSCODER_COVER']);
            if($oCoverTranscoder && ($sCoverUrl = $oCoverTranscoder->getFileUrl((int)$aContentInfo[$CNF['FIELD_COVER']])))
                $sCoverStyle = 'background-image:url(' . bx_html_specialchars($sCoverUrl) . ')';
        }

        $this->_oTemplate->addCss(array('overview.css'));

        return $this->_oTemplate->parseHtmlByName('overview.html', array(
            'title' => bx_html_specialchars($sTitle),
            'cover_style' => $sCoverStyle,
            'initial' => mb_strtoupper(mb_substr($sTitle !== '' ? $sTitle : 'P', 0, 1)),
            'thumb_style' => $sThumb !== '' ? 'background-image:url(' . bx_html_specialchars($sThumb) . ')' : '',
            'profile_url' => bx_html_specialchars($sUrl),
            'bx_if:editable' => array(
                'condition' => $bEditable,
                'content' => array(
                    'edit_url' => bx_html_specialchars($sEditUrl !== '' ? $sEditUrl : $sUrl)
                )
            ),
            'bx_if:bio' => array(
                'condition' => $sBio !== '',
                'content' => array('bio' => bx_html_specialchars($sBio))
            ),
            'bx_if:location' => array(
                'condition' => $sLocation !== '',
                'content' => array('location' => bx_html_specialchars($sLocation))
            ),
            'bx_if:member_since' => array(
                'condition' => $sMemberSince !== '',
                'content' => array('member_since' => bx_html_specialchars($sMemberSince))
            ),
            'connections' => number_format($iConnections),
            'followers' => number_format($iFollowers),
            'views' => number_format($iViews),
            'member_since_card' => $sMemberSince !== '' ? bx_html_specialchars($sMemberSince) : '&mdash;'
        ));
    }

    /**
     * @page service Service Calls
     * @section bx_persons Persons 
     * @subsection bx_persons-other Other
     * @subsubsection bx_persons-get_search_options
     * 
     * @code bx_srv('bx_persons', 'get_search_options', [...]); @endcode
     * 
     * Get options for searchable fields
     * 
     * @return array - params or false.
     * 
     * @see BxPersonsModule::serviceGetSearchOptions
     */
    /** 
     * @ref bx_persons-get_search_options "get_search_options"
     */
    public function serviceGetSearchOptions ($sField, $sFieldType, $sSearchType)
    {
        $CNF = $this->_oConfig->CNF;
        if (isset($CNF['OBJECT_FORM_ENTRY']) && isset($CNF['OBJECT_FORM_ENTRY_DISPLAY_ADD'])){
            $oForm = BxDolForm::getObjectInstance($CNF['OBJECT_FORM_ENTRY'], $CNF['OBJECT_FORM_ENTRY_DISPLAY_ADD'], $this->_oTemplate);
            foreach ($oForm->aInputs as $aFld) {
                if ($aFld['name'] == $sField && $aFld['type'] == $sFieldType && isset($aFld['checker']) && isset($aFld['checker']['params'])){
                    return $aFld['checker']['params'];
                }
            }
        }
        
        if (isset($CNF['OBJECT_FORM_ENTRY']) && isset($CNF['OBJECT_FORM_ENTRY_DISPLAY_EDIT'])){
            $oForm = BxDolForm::getObjectInstance($CNF['OBJECT_FORM_ENTRY'], $CNF['OBJECT_FORM_ENTRY_DISPLAY_EDIT'], $this->_oTemplate);
            foreach ($oForm->aInputs as $aFld) {
                if ($aFld['name'] == $sField && $aFld['type'] == $sFieldType && isset($aFld['checker']) && isset($aFld['checker']['params'])){
                    return $aFld['checker']['params'];
                }
            }
        }
        
        return false;
    }

    public function getSubtypes()
    {
        return pow(2, BX_DOL_MODULE_SUBTYPE_PROFILE);
    }

    public function getProfileName ($aContentInfo)
    {
        return bx_process_output($aContentInfo[$this->_oConfig->CNF['FIELD_NAME']] . ($aContentInfo[$this->_oConfig->CNF['FIELD_LAST_NAME']] != '' ? ' ' . $aContentInfo[$this->_oConfig->CNF['FIELD_LAST_NAME']] : ''));
    }
    
}

/** @} */
