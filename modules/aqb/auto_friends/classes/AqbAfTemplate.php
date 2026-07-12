<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Auto Friends Auto Friends
 * @ingroup     UNAModules
 *
 * @{
 */

class AqbAfTemplate extends BxBaseModGeneralTemplate
{
    public function __construct(&$oConfig, &$oDb)
    {
        $this->MODULE = 'aqb_auto_friends';
        parent::__construct($oConfig, $oDb);
    }

    public function getBlockProfiles($sType, $iStart = -1, $iPerPage = -1, $bShowTabs = true)
    {
        $CNF = &$this->_oConfig->CNF;

        if($iStart <= 0)
            $iStart = 0;

        if($iPerPage <= 0)
            $iPerPage = $this->_oConfig->getPerPage();

        $oMenu = null;
        if($bShowTabs) {
            $sViewAllLink = BxDolPermalinks::getInstance()->permalink($CNF['URL_VIEW']);

            $aMenu = array(
                    array('id' => 'aqb-view-all', 'name' => 'aqb-view-all', 'class' => '', 'link' => $sViewAllLink, 'onclick' => '', 'target' => '_self', 'title' => _t('_aqb_auto_friends_menu_item_title_view_all'), 'active' => 1)
            );

            $oMenu = new BxTemplMenuInteractive(array('template' => 'menu_vertical.html', 'menu_id'=> $this->_oConfig->getHtmlId('profiles_menu'), 'menu_items' => $aMenu));
        }

        $sContent = $this->parseHtmlByName('profiles_block.html', array(
            'html_id' => $this->_oConfig->getHtmlId('profiles'), 
            'style_prefix' => $this->_oConfig->getPrefix('style'),
            'type' => $sType, 
            'profiles' => $this->getProfiles($sType, $iStart, $iPerPage),
            'script' => $this->getJsCode('main', array(
                'aHtmlIds' => $this->_oConfig->getHtmlId(),
                'iStart' => $iStart,
                'iPerPage' => $iPerPage
            ))
        ));

        $this->addJs(array('jquery.anim.js', 'main.js'));
        $this->addCss(array('main.css'));
        return array('content' => $sContent, 'menu' => $oMenu);
    }

    public function getProfiles($sType, $iStart = -1, $iPerPage = -1)
    {
        $CNF = &$this->_oConfig->CNF;

        $aEntries = $this->_oDb->getEntries(array('type' => 'all_for_view', 'start' => $iStart, 'per_page' => $iPerPage + 1));
        if(empty($aEntries) || !is_array($aEntries))	
                return MsgBox(_t('_Empty'));

        $sStylePrefix = $this->_oConfig->getPrefix('style');
        $sJsObject = $this->_oConfig->getJsObject('main');

        $oPaginate = new BxTemplPaginate(array(
            'start' => $iStart,
            'per_page' => $iPerPage,
            'on_change_page' => $sJsObject . ".changePage(this, '" . $sType . "', {start}, {per_page});"
        ), $this);
        $oPaginate->setNumFromDataArray($aEntries);

        $aTmplVarsProfiles = array();
        foreach($aEntries as $aEntry) {
            $oProfile = $this->_oConfig->getProfile($aEntry[$CNF['FIELD_PROFILE_ID']]);
            if($oProfile instanceof BxDolProfileUndefined) 
                continue;

            $sProfileName = $oProfile->getDisplayName();
            $sProfileDescription = $aEntry[$CNF['FIELD_DESCRIPTION']];
            if($sType == AQB_AF_BROWSE_SHORT)
                $sProfileDescription = strmaxtextlen($sProfileDescription, $this->_oConfig->getSnippetLenth());

            $aTmplVarsProfiles[] = array(
                'style_prefix' => $sStylePrefix,
                'profile_unit' => $oProfile->getUnit(0, array('template' => 'unit_wo_info')),
                'profile_url' => $oProfile->getUrl(),
                'profile_title' => bx_html_attribute($sProfileName),
                'profile_name' => $sProfileName,
                'profile_info' => $sProfileDescription
            );
        }

        return $this->parseHtmlByName('profiles.html', array(
            'style_prefix' => $sStylePrefix,
            'bx_repeat:profiles' => $aTmplVarsProfiles,
            'paginate' => $oPaginate->getSimplePaginate()
        ));
    }

    public function getJsCode($sType, $aParams = array(), $bWrap = true)
    {
    	$aParams = array_merge($aParams, array(
            'aHtmlIds' => $this->_oConfig->getHtmlId()
    	));

    	return parent::getJsCode($sType, $aParams, $bWrap);
    }
}

/** @} */
