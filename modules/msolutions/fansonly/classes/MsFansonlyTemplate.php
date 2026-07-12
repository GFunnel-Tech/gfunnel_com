<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) MSolutions
 * 
 * @defgroup    Fansonly Fansonly module
 * @ingroup     MSolutionsModules
 *
 * @{
 */ 

bx_import ('BxDolModuleTemplate');

class MsFansonlyTemplate extends BxBaseModTextTemplate
{    
	function __construct(&$oConfig, &$oDb) 
    {
        $this->MODULE = 'ms_fansonly';

	    parent::__construct($oConfig, $oDb);

        $this->addCss('main.css');

    }    
    
    public function entryTextOverride ($aData, $oModule, $sTemplateName = 'entry-text.html')
    {
        $CNF = &$oModule->_oConfig->CNF;

        $aVars = $oModule->_oTemplate->getTmplVarsText($aData);
        

        $username = $this->_oModule->getAuthorName($aData["author"] );

        $aVars["entry_text"] = MsgBox(_t('_ms_fansonly_txt_entry_full_text', $username, $username));

        $aVars["bx_if:show_image"] = [
            "condition"=> true,
            "content"=> [
              "entry_image"=> $this->getPreviewImage()
            ]
        ];
        
        $text = $this->parseHtmlByName($sTemplateName, $aVars);
        $div = $text;

    	$div = $this->parseHtmlByName('designbox_13.html', 
                ["designbox_content" => $text,
                "title"=> "",
                "caption_item" => ""
                ]
            );

        return $div;
    }
    
    public function getAuthorBlock ($iAuthor, $oModule = false)
    {
        //print $sFreeRequest.' : asdasd<br><br>asdasd<br><br>asdasd<br><br>asdasd<br><br>asdasd<br><br>';
        

        $bTmplVarsSectionAuthor = true;
        $bTmplVarsSectionPricing = false;
        $bTmplVarsSectionVoting = true;

        if($oModule == false)
        {        
            $oModule = $this->getModule();
        }

    	$CNF = &$oModule->_oConfig->CNF;
    	$aUnit = [];
        $oPermalinks = BxDolPermalinks::getInstance();
        
        list($sAuthorName, $sAuthorUrl, $sAuthorIcon, $sAuthorUnit, $sAuthorUnitShort) = $this->_oModule->getUserInfo($iAuthor);
        
        $aTmplVarsSectionAuthor = array();
        if($bTmplVarsSectionAuthor) {
            $bAuthorIcon = !empty($sAuthorIcon);
            $sAuthorName = $this->parseHtmlByName('author_link.html', array(
                'href' => $sAuthorUrl,
                'title' => bx_html_attribute($sAuthorName),
                'content' => $sAuthorName,
                'bx_if:show_account' => array(
                    'condition' => false,
                    'content' => array()
                )
            ));
            $sActions = '';
            
            $sFreeRequest = $this->_oModule->serviceEntityFreeRequest($iAuthor);
            
            
            $aTmplVarsSectionAuthor = array(
                'actions' => $sActions,
                'author_unit' => $sAuthorUnitShort,
                'author_width_date' => $sAuthorName,
            );

        	$aUnit = array_merge($aUnit, array(
    	    'bx_if:show_section_author' => array(
    	        'condition' => $bTmplVarsSectionAuthor,
    	        'content' => $aTmplVarsSectionAuthor
    	    ),
            'author_name' => $sAuthorName,

            'author_pricing' => $this->_oModule->serviceEntityJoin($iAuthor),

    	    'bx_if:show_free_requests' => array(
    	        'condition' => (!empty($sFreeRequest)),
    	        'content' => ['free_requests_grid'=>$sFreeRequest]
    	        ),
            
        	));
        }

        return $this->parseHtmlByName('onlyfans_author.html', $aUnit);
    }

    //This unit overrides the "private" unit
    public function getUnit ($aData, $aParams = array(), $oModule = false)
    {

        $bTmplVarsSectionAuthor = true;
        $bTmplVarsSectionPricing = false;
        $bTmplVarsSectionVoting = true;

        if($oModule == false)
        {        
            $oModule = $this->getModule();
        }
        
        $this->MODULE = $oModule->_aModule['name'];

    	$CNF = &$oModule->_oConfig->CNF;

    	$aUnit = [];

        $oPermalinks = BxDolPermalinks::getInstance();


        list($sAuthorName, $sAuthorUrl, $sAuthorIcon, $sAuthorUnit, $sAuthorUnitShort) = $oModule->getUserInfo($aData['author']);
        
        $aTmplVarsSectionAuthor = array();
        if($bTmplVarsSectionAuthor) {
            //--- Author Info
            $bAuthorIcon = !empty($sAuthorIcon);
            $sAuthorName = $this->parseHtmlByName('author_link.html', array(
                'href' => $sAuthorUrl,
                'title' => bx_html_attribute($sAuthorName),
                'content' => $sAuthorName,
                'bx_if:show_account' => array(
                    'condition' => false,
                    'content' => array()
                )
            ));

            $sActions = '';
            $sDate = $this->parseHtmlByName('snippet-date.html', array(
                'date' => bx_time_js($aData[$CNF['FIELD_ADDED']])
            ));

            $aTmplVarsSectionAuthor = array(
                'actions' => $sActions,
                'author_unit' => $sAuthorUnitShort,
                'author_width_date' => _t('_ms_fansonly_txt_author_added_unit', $sAuthorName, $sDate),
            );
        }

        //--- Main Info
        $sUrl = BX_DOL_URL_ROOT . $oPermalinks->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aData[$CNF['FIELD_ID']]);
$sLinkMore = '';
        $sSummary = 'FANS ONLY';
        $sSummaryPlain = BxTemplFunctions::getInstance()->getStringWithLimitedLength(strip_tags($sSummary), (int)getParam($CNF['PARAM_CHARS_SUMMARY_PLAIN']));
        

        //--- Icon Info
        $sIconUrl = '';

        //--- Cover Info
        $sCoverUrl = $this->getPreviewImage();


    	$aUnit = array_merge($aUnit, array(
    	    'bx_if:show_section_author' => array(
    	        'condition' => $bTmplVarsSectionAuthor,
    	        'content' => $aTmplVarsSectionAuthor
    	    ),
            'bx_if:show_icon' => array (
                'condition' => $sIconUrl,
                'content' => array(
                	'icon_url' => $sIconUrl,
                ),
            ),
            'bx_if:show_icon_empty' => array (
                'condition' => !$sIconUrl,
                'content' => array(
                    'icon' => $CNF['ICON']
                ),
            ),
            'bx_if:show_cover' => array (
                'condition' => $sCoverUrl,
                'content' => array (
                    'summary_attr' => bx_html_attribute($sSummaryPlain),
                    'content_url' => $sUrl,
                    'cover_url' => $sCoverUrl,
                    'strecher' => str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ', 40),
                ),
            ),
            'bx_if:show_cover_empty' => array (
                'condition' => !$sCoverUrl,
                'content' => array (
                    'summary_plain' => $sSummaryPlain,
                    'strecher' => mb_strlen($sSummaryPlain) > 240 ? '' : str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ', round((240 - mb_strlen($sSummaryPlain)) / 6)),
                ),
            ),
            'author_name' => $sAuthorName,
            'content_url' => $sUrl,
            'title' => (!empty($CNF['FIELD_TITLE'])) ? $aData[$CNF['FIELD_TITLE']] : '',

    	));
        

        return $this->parseHtmlByName('unit_gallery.html', $aUnit);

    }

    public function getUnitMedia ($aData, $aParams = array(), $oModule = false)
    {
        return $this->unitMedia ($aData, $isCheckPrivateContent = true, $sTemplateName = 'unit_media.html', $aParams = array(), $oModule);

        return $this->entryMediaView($aData["id"], $aData, $oModule);
        $bTmplVarsSectionAuthor = true;
        $bTmplVarsSectionPricing = false;
        $bTmplVarsSectionVoting = true;

        if($oModule == false)
        {        
            $oModule = $this->getModule();
        }
        
        $this->MODULE = $oModule->_aModule['name'];

    	$CNF = &$oModule->_oConfig->CNF;

    	$aUnit = [];

        $oPermalinks = BxDolPermalinks::getInstance();

        list($sAuthorName, $sAuthorUrl, $sAuthorIcon, $sAuthorUnit, $sAuthorUnitShort) = $oModule->getUserInfo($aData['author']);
        
        $aTmplVarsSectionAuthor = array();
        if($bTmplVarsSectionAuthor) {
            //--- Author Info
            $bAuthorIcon = !empty($sAuthorIcon);
            $sAuthorName = $this->parseHtmlByName('author_link.html', array(
                'href' => $sAuthorUrl,
                'title' => bx_html_attribute($sAuthorName),
                'content' => $sAuthorName,
                'bx_if:show_account' => array(
                    'condition' => false,
                    'content' => array()
                )
            ));

            $sActions = '';
            
            $sDate = $this->parseHtmlByName('snippet-date.html', array(
                'date' => bx_time_js($aData[$CNF['FIELD_ADDED']])
            ));

            $aTmplVarsSectionAuthor = array(
                'actions' => $sActions,
                'author_unit' => $sAuthorUnitShort,
                'author_width_date' => _t('_ms_fansonly_txt_author_added_unit', $sAuthorName, $sDate),
            );
        }

        //--- Main Info
        $sUrl = BX_DOL_URL_ROOT . $oPermalinks->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aData[$CNF['FIELD_ID']]);

        $sLinkMore = '';

        $sSummary = 'FANS ONLY';
        $sSummaryPlain = BxTemplFunctions::getInstance()->getStringWithLimitedLength(strip_tags($sSummary), (int)getParam($CNF['PARAM_CHARS_SUMMARY_PLAIN']));
        

        //--- Icon Info
        $sIconUrl = '';

        //--- Cover Info

        $sCoverUrl = $this->getPreviewImage();

    	$aUnit = array_merge($aUnit, array(
    	    'bx_if:show_section_author' => array(
    	        'condition' => $bTmplVarsSectionAuthor,
    	        'content' => $aTmplVarsSectionAuthor
    	    ),
            'bx_if:show_icon' => array (
                'condition' => $sIconUrl,
                'content' => array(
                	'icon_url' => $sIconUrl,
                ),
            ),
            'bx_if:show_icon_empty' => array (
                'condition' => !$sIconUrl,
                'content' => array(
                    'icon' => $CNF['ICON']
                ),
            ),
            'bx_if:show_cover' => array (
                'condition' => $sCoverUrl,
                'content' => array (
                    'summary_attr' => bx_html_attribute($sSummaryPlain),
                    'content_url' => $sUrl,
                    'cover_url' => $sCoverUrl,
                    'strecher' => str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ', 40),
                ),
            ),
            'bx_if:show_cover_empty' => array (
                'condition' => !$sCoverUrl,
                'content' => array (
                    'summary_plain' => $sSummaryPlain,
                    'strecher' => mb_strlen($sSummaryPlain) > 240 ? '' : str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ', round((240 - mb_strlen($sSummaryPlain)) / 6)),
                ),
            ),
            'author_name' => $sAuthorName,
            'content_url' => $sUrl,

            'title' => (!empty($CNF['FIELD_TITLE'])) ? $aData[$CNF['FIELD_TITLE']] : '',

    	));
        

        return $this->parseHtmlByName('unit_media.html', $aUnit);

    }


    //--- Albums media feature
    function unitMedia ($aData, $isCheckPrivateContent = true, $sTemplateName = 'unit_media.html', $aParams = array(), $oModule = false)
    {

        bx_import('Albumstemplate', $this->_oModule->_aModule);
        $oTemplate = new MsFansonlyAlbumstemplate($oModule->_oConfig, $oModule->_oDb);
        
        return $oTemplate->unitMedia($aData, $isCheckPrivateContent, $sTemplateName, $aParams);
        
    }


    

    public function getEntryPreviewForum($aRow, $oModule)
    {

        $CNF = &$oModule->_oConfig->CNF;

        $oProfileLast = BxDolProfile::getInstanceMagic($aRow['lr_profile_id']);

        $sTitle = strmaxtextlen($aRow[$CNF['FIELD_TITLE']], 100);

        $sText = strmaxtextlen($aRow[$CNF['FIELD_TEXT']], 100);

        $aMetas = array('main' => false, 'counters' => false, 'reply' => false);
        foreach(array_keys($aMetas) as $sMeta) {
            $sKey = 'OBJECT_MENU_SNIPPET_META_' . strtoupper($sMeta);
            if(empty($CNF[$sKey]))
                continue;

            $oMenuMeta = BxDolMenu::getObjectInstance($CNF[$sKey], $oModule->_oTemplate);
            if(!$oMenuMeta) 
                continue;

            $oMenuMeta->setContentId($aRow[$CNF['FIELD_ID']]);
            $sMenuMeta = $oMenuMeta->getCode();
            if(empty($sMenuMeta))
                continue;

            $aMetas[$sMeta] = array(
                'content' => $sMenuMeta
            );
        }

$url = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aRow[$CNF['FIELD_ID']]);
        $image = $this->getPreviewImageForumPreview();
        $sText = '<a href="'.$url.'"><img alt="Fans only" src="'.$image.'" style="display: block; width: auto;"/></a>';

        return $oModule->_oTemplate->parseHtmlByName('entry-preview.html', array(
            'bx_if:show_stick' => array(
                'condition' => (int)$aRow[$CNF['FIELD_STICK']] == 1,
                'content' => array()
            ),
            'bx_if:show_lock' => array(
                'condition' => (int)$aRow[$CNF['FIELD_LOCK']] == 1,
                'content' => array()
            ),
            'url' => $url,
            'title' => $sTitle ? $sTitle : _t('_Empty'),
            'badges' => $oModule->serviceGetBadges($aRow[$CNF['FIELD_ID']], false, true),
            'text' => $sText,
            'bx_if:meta_main' => array(
                'condition' => $aMetas['main'] !== false,
                'content' => $aMetas['main']
            ),
            'bx_if:meta_counters' => array(
                'condition' => $aMetas['counters'] !== false,
                'content' => $aMetas['counters']
            ),
            'bx_if:meta_reply' => array(
                'condition' => $aMetas['reply'] !== false,
                'content' => $aMetas['reply']
            )
        ));
    }
    public function getPreviewImage(){
        
        $sImage = getParam('ms_fansonly_unit_preview_image');
        if(!empty($sImage)){
            $oStorage = BxDolStorage::getObjectInstance('sys_images_custom'); 
            $sImage = $oStorage->getFileUrlById($sImage);
        }

        if(empty($sImage)){
            $sImage = $this->getImageUrl("fansonly.png");
        }
        return $sImage;
        
    }
    public function getPreviewImageForumPreview(){
        
        $sImage = getParam('ms_fansonly_forum_preview_image');
        if(!empty($sImage)){
            $oStorage = BxDolStorage::getObjectInstance('sys_images_custom'); 
            $sImage = $oStorage->getFileUrlById($sImage);
        }

        if(empty($sImage)){
//            $sImage = $this->getImageUrl("fansonlylittledark.png");
            $sImage = $this->getImageUrl("fansonlylittlewhite.png");
        }
        return $sImage;
        
    }
    
    
    
    
    
	public function getJsCode($sType, $aParams = array(), $bWrap = true)
    {
        $aParams = array_merge(array(
            'aHtmlIds' => $this->_oConfig->getHtmlIds()
        ), $aParams);

        return parent::getJsCode($sType, $aParams, $bWrap);
    }

}

/** @} */
