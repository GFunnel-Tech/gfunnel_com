<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Listing Listing
 * @ingroup     ModzzzModules
 *
 * @{
 */

/*
 * Module representation.
 */
class MzListingTemplate extends BxBaseModTextTemplate
{
    function __construct(&$oConfig, &$oDb)
    {
        $this->MODULE = 'mz_listing';

        parent::__construct($oConfig, $oDb);

        $this->aMethodsToCallAddJsCss[] = 'categories';
    }
 
	public function listing_profile_client($aData)
    {  
 		$oProfile = BxDolProfile::getInstanceMagic($aData['profile_id']); 
  
		$aInfo = $oProfile->getInfo();  
		$oPerson = BxDolModule::getInstance($aInfo['type']); 

		return $oPerson->serviceGetSearchResultUnit ($aInfo['content_id']);  
    }
   

//begin gift

	public function gift_main_unit($aData)
    {
 
        $oPermalink = BxDolPermalinks::getInstance();
 
    	$oModule = $this->getModule();

        $CNF = &$oModule->_oConfig->CNF;

        $iProfile = bx_get_logged_profile_id();
 
        $bDeleteGift = $aData[$CNF['FIELD_AUTHOR']] == $iProfile;
  
		$iId = (int)$aData[$CNF['FIELD_ID']];
  
		$oAuthor = BxDolProfile::getInstanceMagic($aData[$CNF['FIELD_AUTHOR']]);
 
        $sUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_GIFT'] . '&id=' . $iId));

        $sTitle = $this->getTitle($aData);
        $sText = $this->getText($aData);
        $sSummary = $this->getSummary($aData, $sTitle, $sText, $sUrl); 
        $sSummaryPlain = isset($CNF['PARAM_CHARS_SUMMARY_PLAIN']) && $CNF['PARAM_CHARS_SUMMARY_PLAIN'] ? BxTemplFunctions::getInstance()->getStringWithLimitedLength(strip_tags($sSummary), (int)getParam($CNF['PARAM_CHARS_SUMMARY_PLAIN'])) : '';
  
        $this->addCss('main.css');
   
        $oImagesTranscoderPrv = BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW_GIFT']);
        $bImagesTranscoderPrv = $oImagesTranscoderPrv !== false;
        $oImagesTranscoderGal = BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_IMAGES_TRANSCODER_GALLERY_GIFT']);
        $bImagesTranscoderGal = $oImagesTranscoderGal !== false;
  
		$iId = (int)$aData[$CNF['FIELD_ID']];
 
		$sPhotoThumb = $sPhotoGallery = '';
		if(!empty($CNF['FIELD_THUMB']) && $aData[$CNF['FIELD_THUMB']]) {
			if($bImagesTranscoderPrv)
				$sPhotoThumb = $oImagesTranscoderPrv->getFileUrl($aData[$CNF['FIELD_THUMB']]);

			if($bImagesTranscoderGal)
				$sPhotoGallery = $oImagesTranscoderGal->getFileUrl($aData[$CNF['FIELD_THUMB']]);
			else
				$sPhotoGallery = $sPhotoThumb;
		}
 
 		$aCatList = BxDolForm::getDataItems('mz_listing_gift_cat'); 
  
		return $this->parseHtmlByName('unit_gift.html', array( 

            'class' => $this->_getUnitClass($aData,(isset($aParams['template_name']) ? $aParams['template_name'] : '')),
			'id' => $iId,
			'title' => $sTitle,
            'title_attr' => bx_html_attribute($sTitle), 
            'cat' => $aCatList[$aData['cat']],
            'summary' => $sSummary,
            'text' => $sText, 
			'content_url' => $sUrl, 
			'bx_if:thumb' => array(
				'condition' => $sPhotoThumb,
				'content' => array(
					'content_url' => $sUrl, 
					'summary_attr' => bx_html_attribute($sSummaryPlain), 
					'thumb_url' => $sPhotoThumb ? $sPhotoThumb : '',
					'gallery_url' => $sPhotoGallery ? $sPhotoGallery : '',
					'strecher' => str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ', 40),
				)
			),
			'bx_if:no_thumb' => array(
				'condition' => !$sPhotoThumb,
				'content' => array(
                    'summary_plain' => $sSummaryPlain,
                    'content_url' => $sUrl,
                    'strecher' => mb_strlen($sSummaryPlain) > 240 ? '' : str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ', round((240 - mb_strlen($sSummaryPlain)) / 6)),
				)
			),  
		)); 
    }
  
    public function giftText ($aData, $sTemplateName = 'gift-text.html')
    {
        $CNF = &$this->_oConfig->CNF;
  
        $aVars = $aData; 
  
        $aVars['entry_title'] = isset($aData[$CNF['FIELD_GIFT_TITLE']]) ? $aData[$CNF['FIELD_GIFT_TITLE']] : '';
        $aVars['entry_text'] = $aData[$CNF['FIELD_GIFT_TEXT']];

        return $this->parseHtmlByName($sTemplateName, $aVars);
    }
 
    public function giftBreadcrumb($aGiftContentInfo, $aTmplVarsItems = array())
    {
    	$CNF = &$this->_oConfig->CNF;
 
        $aContentInfo = $this->_oDb->getContentInfoById($aGiftContentInfo[$CNF['FIELD_LISTING_ID']]);

        $oPermalink = BxDolPermalinks::getInstance();

        $aTmplVarsItems = array();
  
		if($aContentInfo[$CNF['FIELD_SUBCATEGORY']])
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_SUBCATEGORY']], $oPermalink, $aTmplVarsItems);
		else
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_CATEGORY']], $oPermalink, $aTmplVarsItems);
 
        $aTmplVarsItems = array_reverse($aTmplVarsItems);
        
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aContentInfo[$CNF['FIELD_TITLE']])
        );
 
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_GIFT'] . '&id=' . $aGiftContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aGiftContentInfo[$CNF['FIELD_TITLE']])
        );

    	return parent::entryBreadcrumb($aContentInfo, $aTmplVarsItems);
    }

//end gift

//begin reward

	public function reward_main_unit($aData)
    {
 
        $oPermalink = BxDolPermalinks::getInstance();
 
    	$oModule = $this->getModule();

        $CNF = &$oModule->_oConfig->CNF;

        $iProfile = bx_get_logged_profile_id();
 
        $bDeleteReward = $aData[$CNF['FIELD_AUTHOR']] == $iProfile;
  
		$iId = (int)$aData[$CNF['FIELD_ID']];
  
		$oAuthor = BxDolProfile::getInstanceMagic($aData[$CNF['FIELD_AUTHOR']]);
 
        $sUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_REWARD'] . '&id=' . $iId));

        $sTitle = $this->getTitle($aData);
        $sText = $this->getText($aData);
        $sSummary = $this->getSummary($aData, $sTitle, $sText, $sUrl); 
        $sSummaryPlain = isset($CNF['PARAM_CHARS_SUMMARY_PLAIN']) && $CNF['PARAM_CHARS_SUMMARY_PLAIN'] ? BxTemplFunctions::getInstance()->getStringWithLimitedLength(strip_tags($sSummary), (int)getParam($CNF['PARAM_CHARS_SUMMARY_PLAIN'])) : '';
  
        $this->addCss('main.css');
   
        $oImagesTranscoderPrv = BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW_REWARD']);
        $bImagesTranscoderPrv = $oImagesTranscoderPrv !== false;
        $oImagesTranscoderGal = BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_IMAGES_TRANSCODER_GALLERY_REWARD']);
        $bImagesTranscoderGal = $oImagesTranscoderGal !== false;
  
		$iId = (int)$aData[$CNF['FIELD_ID']];
 
		$sPhotoThumb = $sPhotoGallery = '';
		if(!empty($CNF['FIELD_THUMB']) && $aData[$CNF['FIELD_THUMB']]) {
			if($bImagesTranscoderPrv)
				$sPhotoThumb = $oImagesTranscoderPrv->getFileUrl($aData[$CNF['FIELD_THUMB']]);

			if($bImagesTranscoderGal)
				$sPhotoGallery = $oImagesTranscoderGal->getFileUrl($aData[$CNF['FIELD_THUMB']]);
			else
				$sPhotoGallery = $sPhotoThumb;
		}

		return $this->parseHtmlByName('unit_reward.html', array( 

            'class' => $this->_getUnitClass($aData,(isset($aParams['template_name']) ? $aParams['template_name'] : '')),
			'id' => $iId,
			'title' => $sTitle,
            'title_attr' => bx_html_attribute($sTitle), 
            'summary' => $sSummary,
            'text' => $sText, 
			'content_url' => $sUrl, 
			'bx_if:thumb' => array(
				'condition' => $sPhotoThumb,
				'content' => array(
					'content_url' => $sUrl, 
					'summary_attr' => bx_html_attribute($sSummaryPlain), 
					'thumb_url' => $sPhotoThumb ? $sPhotoThumb : '',
					'gallery_url' => $sPhotoGallery ? $sPhotoGallery : '',
					'strecher' => str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ', 40),
				)
			),
			'bx_if:no_thumb' => array(
				'condition' => !$sPhotoThumb,
				'content' => array(
                    'summary_plain' => $sSummaryPlain,
                    'content_url' => $sUrl,
                    'strecher' => mb_strlen($sSummaryPlain) > 240 ? '' : str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ', round((240 - mb_strlen($sSummaryPlain)) / 6)),
				)
			),  
		)); 
    }
  
    public function rewardText ($aData, $sTemplateName = 'reward-text.html')
    {
        $CNF = &$this->_oConfig->CNF;
  
        $aVars = $aData; 
  
        $aVars['entry_title'] = isset($aData[$CNF['FIELD_REWARD_TITLE']]) ? $aData[$CNF['FIELD_REWARD_TITLE']] : '';
        $aVars['entry_text'] = $aData[$CNF['FIELD_REWARD_TEXT']];

        return $this->parseHtmlByName($sTemplateName, $aVars);
    }
 
    public function rewardBreadcrumb($aRewardContentInfo, $aTmplVarsItems = array())
    {
    	$CNF = &$this->_oConfig->CNF;
 
        $aContentInfo = $this->_oDb->getContentInfoById($aRewardContentInfo[$CNF['FIELD_LISTING_ID']]);

        $oPermalink = BxDolPermalinks::getInstance();

        $aTmplVarsItems = array();
  
		if($aContentInfo[$CNF['FIELD_SUBCATEGORY']])
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_SUBCATEGORY']], $oPermalink, $aTmplVarsItems);
		else
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_CATEGORY']], $oPermalink, $aTmplVarsItems);
 
        $aTmplVarsItems = array_reverse($aTmplVarsItems);
        
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aContentInfo[$CNF['FIELD_TITLE']])
        );
 
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_REWARD'] . '&id=' . $aRewardContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aRewardContentInfo[$CNF['FIELD_TITLE']])
        );

    	return parent::entryBreadcrumb($aContentInfo, $aTmplVarsItems);
    }

//end reward
 
//begin service

	public function service_main_unit($aData)
    {
 
        $oPermalink = BxDolPermalinks::getInstance();
 
    	$oModule = $this->getModule();

        $CNF = &$oModule->_oConfig->CNF;

        $iProfile = bx_get_logged_profile_id();
 
        $bDeleteService = $aData[$CNF['FIELD_AUTHOR']] == $iProfile;
  
		$iId = (int)$aData[$CNF['FIELD_ID']];
  
		$oAuthor = BxDolProfile::getInstanceMagic($aData[$CNF['FIELD_AUTHOR']]);
 
        $sUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_SERVICE'] . '&id=' . $iId));

        $sTitle = $this->getTitle($aData);
        $sText = $this->getText($aData);
        $sSummary = $this->getSummary($aData, $sTitle, $sText, $sUrl); 
        $sSummaryPlain = isset($CNF['PARAM_CHARS_SUMMARY_PLAIN']) && $CNF['PARAM_CHARS_SUMMARY_PLAIN'] ? BxTemplFunctions::getInstance()->getStringWithLimitedLength(strip_tags($sSummary), (int)getParam($CNF['PARAM_CHARS_SUMMARY_PLAIN'])) : '';
  
        $this->addCss('main.css');
   
        $oImagesTranscoderPrv = BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW_SERVICE']);
        $bImagesTranscoderPrv = $oImagesTranscoderPrv !== false;
        $oImagesTranscoderGal = BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_IMAGES_TRANSCODER_GALLERY_SERVICE']);
        $bImagesTranscoderGal = $oImagesTranscoderGal !== false;
  
		$iId = (int)$aData[$CNF['FIELD_ID']];
 
		$sPhotoThumb = $sPhotoGallery = '';
		if(!empty($CNF['FIELD_THUMB']) && $aData[$CNF['FIELD_THUMB']]) {
			if($bImagesTranscoderPrv)
				$sPhotoThumb = $oImagesTranscoderPrv->getFileUrl($aData[$CNF['FIELD_THUMB']]);

			if($bImagesTranscoderGal)
				$sPhotoGallery = $oImagesTranscoderGal->getFileUrl($aData[$CNF['FIELD_THUMB']]);
			else
				$sPhotoGallery = $sPhotoThumb;
		}
 
 		$aCatList = BxDolForm::getDataItems('mz_listing_service_cat'); 
  
		return $this->parseHtmlByName('unit_service.html', array( 

            'class' => $this->_getUnitClass($aData,(isset($aParams['template_name']) ? $aParams['template_name'] : '')),
			'id' => $iId,
			'title' => $sTitle,
            'title_attr' => bx_html_attribute($sTitle), 
            'cat' => $aCatList[$aData['cat']],
            'summary' => $sSummary,
            'text' => $sText, 
			'content_url' => $sUrl, 
			'bx_if:thumb' => array(
				'condition' => $sPhotoThumb,
				'content' => array(
					'content_url' => $sUrl, 
					'summary_attr' => bx_html_attribute($sSummaryPlain), 
					'thumb_url' => $sPhotoThumb ? $sPhotoThumb : '',
					'gallery_url' => $sPhotoGallery ? $sPhotoGallery : '',
					'strecher' => str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ', 40),
				)
			),
			'bx_if:no_thumb' => array(
				'condition' => !$sPhotoThumb,
				'content' => array(
                    'summary_plain' => $sSummaryPlain,
                    'content_url' => $sUrl,
                    'strecher' => mb_strlen($sSummaryPlain) > 240 ? '' : str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ', round((240 - mb_strlen($sSummaryPlain)) / 6)),
				)
			),  
		)); 
    }
  
    public function serviceText ($aData, $sTemplateName = 'service-text.html')
    {
        $CNF = &$this->_oConfig->CNF;
  
        $aVars = $aData; 
  
        $aVars['entry_title'] = isset($aData[$CNF['FIELD_SERVICE_TITLE']]) ? $aData[$CNF['FIELD_SERVICE_TITLE']] : '';
        $aVars['entry_text'] = $aData[$CNF['FIELD_SERVICE_TEXT']];

        return $this->parseHtmlByName($sTemplateName, $aVars);
    }
 
    public function serviceBreadcrumb($aServiceContentInfo, $aTmplVarsItems = array())
    {
    	$CNF = &$this->_oConfig->CNF;
 
        $aContentInfo = $this->_oDb->getContentInfoById($aServiceContentInfo[$CNF['FIELD_LISTING_ID']]);

        $oPermalink = BxDolPermalinks::getInstance();

        $aTmplVarsItems = array();
  
		if($aContentInfo[$CNF['FIELD_SUBCATEGORY']])
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_SUBCATEGORY']], $oPermalink, $aTmplVarsItems);
		else
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_CATEGORY']], $oPermalink, $aTmplVarsItems);
 
        $aTmplVarsItems = array_reverse($aTmplVarsItems);
        
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aContentInfo[$CNF['FIELD_TITLE']])
        );
 
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_SERVICE'] . '&id=' . $aServiceContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aServiceContentInfo[$CNF['FIELD_TITLE']])
        );

    	return parent::entryBreadcrumb($aContentInfo, $aTmplVarsItems);
    }

//end service

 //begin branch
  
    protected function _getAuthorInfo($iAuthorId = 0)
    {
        $iUserId = $this->_getAuthorId();
        $iAuthorId = (int)$iAuthorId;
        $oProfile = $this->_getAuthorObject($iAuthorId);

        return array(
            $oProfile->getDisplayName(),
            $oProfile->getUrl(),
            $oProfile->getThumb(),
            $oProfile->getUnit(0, array('template' => 'unit_wo_info')),
            $oProfile->getBadges()
        );
    }

    protected function _getAuthorObject($iAuthorId = 0)
    {
        return BxDolProfile::getInstanceMagic($iAuthorId);
    }

    protected function _getAuthorId ()
    {
        return isMember() ? bx_get_logged_profile_id() : 0;
    }


    public function branchText ($aData, $sTemplateName = 'branch-text.html')
    {
        $CNF = &$this->_oConfig->CNF;
  
        $aVars = $aData; 
  
        $aVars['entry_title'] = isset($aData[$CNF['FIELD_BRANCH_TITLE']]) ? $aData[$CNF['FIELD_BRANCH_TITLE']] : '';
        $aVars['entry_text'] = $aData[$CNF['FIELD_BRANCH_TEXT']];

        return $this->parseHtmlByName($sTemplateName, $aVars);
    }
  
    public function branchBreadcrumb($aBranchContentInfo, $aTmplVarsItems = array())
    {
    	$CNF = &$this->_oConfig->CNF;
 
        $aContentInfo = $this->_oDb->getContentInfoById($aBranchContentInfo[$CNF['FIELD_LISTING_ID']]);

        $oPermalink = BxDolPermalinks::getInstance();

        $aTmplVarsItems = array();
  
		if($aContentInfo[$CNF['FIELD_SUBCATEGORY']])
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_SUBCATEGORY']], $oPermalink, $aTmplVarsItems);
		else
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_CATEGORY']], $oPermalink, $aTmplVarsItems);
 
        $aTmplVarsItems = array_reverse($aTmplVarsItems);
        
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aContentInfo[$CNF['FIELD_TITLE']])
        );
 
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_BRANCH'] . '&id=' . $aBranchContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aBranchContentInfo[$CNF['FIELD_TITLE']])
        );

    	return parent::entryBreadcrumb($aContentInfo, $aTmplVarsItems);
    }

	public function branch_main_unit($aData)
    {
    	$oModule = $this->getModule();

        $CNF = &$oModule->_oConfig->CNF;
  
		$iId = (int)$aData[$CNF['FIELD_ID']];
 
        $sUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_BRANCH'] . '&id=' . $aData[$CNF['FIELD_ID']]));

        $sTitle = $this->getTitle($aData);
        $sText = $this->getText($aData);
        $sSummary = $this->getSummary($aData, $sTitle, $sText, $sUrl); 
        $sSummaryPlain = isset($CNF['PARAM_CHARS_SUMMARY_PLAIN']) && $CNF['PARAM_CHARS_SUMMARY_PLAIN'] ? BxTemplFunctions::getInstance()->getStringWithLimitedLength(strip_tags($sSummary), (int)getParam($CNF['PARAM_CHARS_SUMMARY_PLAIN'])) : '';
  
        $this->addCss('main.css');
   
        $oImagesTranscoderPrv = BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW_BRANCH']);
        $bImagesTranscoderPrv = $oImagesTranscoderPrv !== false;
        $oImagesTranscoderGal = BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_IMAGES_TRANSCODER_GALLERY_BRANCH']);
        $bImagesTranscoderGal = $oImagesTranscoderGal !== false;
  
		$iId = (int)$aData[$CNF['FIELD_ID']];
 
		$sPhotoThumb = $sPhotoGallery = '';
		if(!empty($CNF['FIELD_THUMB']) && $aData[$CNF['FIELD_THUMB']]) {
			if($bImagesTranscoderPrv)
				$sPhotoThumb = $oImagesTranscoderPrv->getFileUrl($aData[$CNF['FIELD_THUMB']]);

			if($bImagesTranscoderGal)
				$sPhotoGallery = $oImagesTranscoderGal->getFileUrl($aData[$CNF['FIELD_THUMB']]);
			else
				$sPhotoGallery = $sPhotoThumb;
		}
    
		return $this->parseHtmlByName('unit_branch.html', array(  
            'class' => $this->_getUnitClass($aData,(isset($aParams['template_name']) ? $aParams['template_name'] : '')),
			'id' => $iId,
			'title' => $sTitle,
            'title_attr' => bx_html_attribute($sTitle), 
            'summary' => $sSummary,
            'text' => $sText, 
			'content_url' => $sUrl,  
 
			'bx_if:thumb' => array(
				'condition' => $sPhotoThumb,
				'content' => array(
					'content_url' => $sUrl, 
					'summary_attr' => bx_html_attribute($sSummaryPlain), 
					'thumb_url' => $sPhotoThumb ? $sPhotoThumb : '',
					'gallery_url' => $sPhotoGallery ? $sPhotoGallery : '',
					'strecher' => str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ', 40),
				)
			),
			'bx_if:no_thumb' => array(
				'condition' => !$sPhotoThumb,
				'content' => array(
                    'summary_plain' => $sSummaryPlain,
                    'content_url' => $sUrl,
                    'strecher' => mb_strlen($sSummaryPlain) > 240 ? '' : str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ', round((240 - mb_strlen($sSummaryPlain)) / 6)),
				)
			),  
		)); 
    }
 
    public function entryBranchLocation ($iContentId)
    {
        $CNF = &$this->getModule()->_oConfig->CNF;

        if (empty($CNF['OBJECT_METATAGS_BRANCH']))
            return '';

        $oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS_BRANCH']);        

        if (!($sLocationString = $oMetatags->locationsString($iContentId)))
            return '';

        $aVars = array (
            'location' => $sLocationString
        );
        return $this->parseHtmlByName('entry-location.html', $aVars);
    }

    function branchAttachments ($aData, $aParams = array())
    {
         return $this->subitemAttachmentsByStorage('branch', $this->getModule()->_oConfig->CNF['OBJECT_STORAGE_BRANCH'], $aData, $aParams);
    }

//end branch
 
    function subitemAttachmentsByStorage ($sType, $mixedStorage, $aData, $aParams = array())
    {
        if(!is_array($mixedStorage))
            $mixedStorage = array($mixedStorage);

        $aResult = array();
        foreach($mixedStorage as $sStorage) {
            $aAttachments = $this->getSubitemAttachments($sType, $sStorage, $aData, $aParams);
            if(empty($aAttachments) || !is_array($aAttachments))
                continue;

            $aResult = array_merge($aResult, $aAttachments);
        }

        if(empty($aResult) || !is_array($aResult))
            return '';

        $this->addCss(BX_DIRECTORY_PATH_PLUGINS_PUBLIC . 'flickity/|flickity.css');
    	$this->addJs(array(
            'flickity/flickity.pkgd.min.js',
            'pdfjs/build/pdf.min.js',
        ));

    	return $this->parseHtmlByName('attachments.html', array(
            'bx_repeat:attachments' => $aResult,
            'pdfjs_worker_url' => $this->getJsUrl('pdfjs/build/pdf.worker.min.js'),
        ));
    }
 
    protected function getSubitemAttachments ($sType, $sStorage, $aData, $aParams = array())
    { 
		$aTranscoder = array('service'=>'OBJECT_STORAGE_SERVICE','gift'=>'OBJECT_STORAGE_GIFT','reward'=>'OBJECT_STORAGE_REWARD','branch'=>'OBJECT_STORAGE_BRANCH');
		
		$CNF = &$this->getModule()->_oConfig->CNF;

        $oStorage = BxDolStorage::getObjectInstance($sStorage);

        list($oTranscoder, $oTranscoderPreview) = $this->getSubitemAttachmentsImagesTranscoders($sType, $sStorage);
        $oTranscoderSound = isset($CNF['OBJECT_SOUNDS_TRANSCODER']) && $CNF['OBJECT_SOUNDS_TRANSCODER'] ? BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_SOUNDS_TRANSCODER']) : null;
        $aTranscodersVideo = $this->getAttachmentsVideoTranscoders($sStorage);

        $aGhostFiles = $oStorage->getGhosts ($this->getModule()->serviceGetContentOwnerProfileId($aData[$CNF['FIELD_ID']]), $aData[$CNF['FIELD_ID']]);
        if (!$aGhostFiles)
            return false;

        $sFilterField = isset($aParams['filter_field']) ? $aParams['filter_field'] : $CNF['FIELD_THUMB'];
		if(!empty($sFilterField) && isset($aData[$sFilterField]))
	        foreach ($aGhostFiles as $k => $a) {
	            // don't show thumbnail in attachments
	            if ($a['id'] == $aData[$sFilterField])
	                unset($aGhostFiles[$k]);
	        }

        if(!$aGhostFiles)
            return false;
 
        $aAttachmnts = array();
        foreach ($aGhostFiles as $k => $a) {
            $isImage = !empty($CNF[$aTranscoder[$sType]]) && $sStorage == $CNF[$aTranscoder[$sType]] && $oTranscoder && (0 === strncmp('image/', $a['mime_type'], 6)) && $oTranscoder->isMimeTypeSupported($a['mime_type']); // preview for images, transcoder object for preview must be defined
            $isVideo = !empty($CNF['OBJECT_STORAGE_VIDEOS']) && $sStorage == $CNF['OBJECT_STORAGE_VIDEOS'] && $aTranscodersVideo && (0 === strncmp('video/', $a['mime_type'], 6)) && $aTranscodersVideo['poster']->isMimeTypeSupported($a['mime_type']); // preview for videos, transcoder object for video must be defined
            $isSound = !empty($CNF['OBJECT_STORAGE_SOUNDS']) && $sStorage == $CNF['OBJECT_STORAGE_SOUNDS'] && $oTranscoderSound && (0 === strncmp('audio/', $a['mime_type'], 6)) && $oTranscoderSound->isMimeTypeSupported($a['mime_type']); // preview for sounds, transcoder object for sounds must be defined
            $sUrlOriginal = $oStorage->getFileUrlById($a['id']);
            $sImgPopupId = 'bx-messages-atachment-popup-' . $a['id'];

            // images are displayed with preview and popup upon clicking
            $a['bx_if:image'] = array (
                'condition' => $isImage,
                'content' => array (
                    'url_original' => $isImage && $oTranscoderPreview ? $oTranscoderPreview->getFileUrl($a['id']) : $sUrlOriginal,
                    'attr_file_name' => bx_html_attribute($a['file_name']),
                    'popup_id' => $sImgPopupId,
                    'url_preview' => $isImage ? $oTranscoder->getFileUrl($a['id']) : '',
                    'popup' =>  BxTemplFunctions::getInstance()->transBox($sImgPopupId, '<img src="' . $sUrlOriginal . '" />', true, true),
                ),
            );

            $sVideoUrlHd = '';
            if($isVideo) {
                $sVideoUrl = $oStorage->getFileUrlById($a['id']);
                $aVideoSize = $aTranscodersVideo['mp4_hd']->getVideoSize($sVideoUrl);
                if(!empty($aVideoSize) && is_array($aVideoSize) && (int)$aVideoSize['h'] >= 720)
                    $sVideoUrlHd = $aTranscodersVideo['mp4_hd']->getFileUrl($a['id']);
            }

            // videos are displayed inline
            $a['bx_if:video'] = array (
                'condition' => $isVideo,
                'content' => array (
                    'video' => $isVideo && $aTranscodersVideo ? BxTemplFunctions::getInstance()->videoPlayer(
                        $aTranscodersVideo['poster']->getFileUrl($a['id']), 
                        $aTranscodersVideo['mp4']->getFileUrl($a['id']), 
                        $sVideoUrlHd,
                        false, ''
                    ) : '',
                ),
            );

            // sounds are displayed inline
            $a['bx_if:sound'] = array (
                'condition' => $isSound,
                'content' => array (
                    'sound' => $isSound && $oTranscoderSound && ($oPlayer = BxDolPlayer::getObjectInstance()) ? $this->parseHtmlByName('attachment_sound.html', array(
                        'file_name' => $a['file_name'],
                        'file_url' => $oTranscoderSound ? $oTranscoderSound->getFileUrl($a['id']) : '',
                        'player' => $oTranscoderSound && $oTranscoderSound->isFileReady($a['id']) ? 
                            $oPlayer->getCodeAudio (BX_PLAYER_STANDARD, array(
                                'mp3' => $oTranscoderSound->getFileUrl($a['id']),
                            )) : _t('_sys_txt_err_sound_not_transcoded_yet'),
                    )) : '',
                ),
            );

            // non-images are displayed as text links to original file
            $a['bx_if:not_image'] = array (
                'condition' => !$isImage && !$isVideo && !$isSound,
                'content' => array (
                    'url_original' => $sUrlOriginal,
                    'attr_file_name' => bx_html_attribute($a['file_name']),
                    'file_name' => bx_process_output($a['file_name']),
                ),
            );

            $aAttachmnts[] = $a;
        }

        return $aAttachmnts;
    }

    protected function getSubitemAttachmentsImagesTranscoders ($sType, $sStorage = '')
    { 
        $CNF = &$this->getModule()->_oConfig->CNF;

        $oTranscoder = null;
        $oTranscoderView = null;

        if(isset($CNF['OBJECT_STORAGE_'.strtoupper($sType)]) && $CNF['OBJECT_STORAGE_'.strtoupper($sType)] == $sStorage) {
            if(!empty($CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW_'.strtoupper($sType)]))
                $oTranscoder = BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW_'.strtoupper($sType)]);
            if(!empty($CNF['OBJECT_IMAGES_TRANSCODER_GALLERY_'.strtoupper($sType)]))
                $oTranscoderView = BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_IMAGES_TRANSCODER_GALLERY_'.strtoupper($sType)]);
        }
        else if(isset($CNF['OBJECT_STORAGE_FILES']) && $CNF['OBJECT_STORAGE_FILES'] == $sStorage) {
            if(!empty($CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW_FILES']))
                $oTranscoder = BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW_FILES']);
            if(!empty($CNF['OBJECT_IMAGES_TRANSCODER_GALLERY_FILES']))
                $oTranscoderView = BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_IMAGES_TRANSCODER_GALLERY_FILES']);
        }
        else
            list($oTranscoder, $oTranscoderView) = parent::getAttachmentsImagesTranscoders($sStorage);

        return array($oTranscoder, $oTranscoderView);
    }

	public function subitemText ($aData, $sType)
    {
        $CNF = &$this->_oConfig->CNF;
   
		$sPhotoThumb = ''; 
		$aTranscoder = array('service'=>'OBJECT_IMAGES_TRANSCODER_PREVIEW_SERVICE','gift'=>'OBJECT_IMAGES_TRANSCODER_PREVIEW_GIFT','reward'=>'OBJECT_IMAGES_TRANSCODER_PREVIEW_REWARD','branch'=>'OBJECT_IMAGES_TRANSCODER_PREVIEW_BRANCH','claim'=>'OBJECT_IMAGES_TRANSCODER_PREVIEW_CLAIM');

		if(isset($aTranscoder[$sType]))
		{ 
			$oImagesTranscoderPrv = BxDolTranscoderImage::getObjectInstance($CNF[$aTranscoder[$sType]]);
			$bImagesTranscoderPrv = $oImagesTranscoderPrv !== false;

			if(!empty($CNF['FIELD_THUMB']) && $aData[$CNF['FIELD_THUMB']]) {
				if($bImagesTranscoderPrv)
					$sPhotoThumb = $oImagesTranscoderPrv->getFileUrl($aData[$CNF['FIELD_THUMB']]); 
			}
		}
        $aVars = $aData;
		$aVars['bx_if:show_image'] = array(
                    'condition' => $sPhotoThumb,
                    'content' => array(
                        'entry_image' => $sPhotoThumb,
                    )
                );
 
        $aVars['entry_title'] = isset($aData[$CNF['FIELD_TITLE']]) ? $aData[$CNF['FIELD_TITLE']] : '';
        $aVars['entry_text'] = $aData[$CNF['FIELD_TEXT']];

        return $this->parseHtmlByName('subitem-text.html', $aVars);
    }

	public function businessText ($aData)
    {
        $CNF = &$this->_oConfig->CNF;
   
        $aVars = array();
		$aVars['content_url'] = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aData[$CNF['FIELD_ID']])); 
        $aVars['title'] = isset($aData[$CNF['FIELD_TITLE']]) ? $aData[$CNF['FIELD_TITLE']] : '';
        $aVars['summary'] = $aData[$CNF['FIELD_TEXT']];
  
        return $this->parseHtmlByName('business-text.html', $aVars);
    }
 
 //begin announcement

	public function announcement_main_unit($aData)
    {

        $oPermalink = BxDolPermalinks::getInstance();
 
    	$oModule = $this->getModule();

        $CNF = &$oModule->_oConfig->CNF;

        $iProfile = bx_get_logged_profile_id();
 
		$iId = (int)$aData[$CNF['FIELD_ID']];
  
		$oAuthor = BxDolProfile::getInstanceMagic($aData[$CNF['FIELD_AUTHOR']]);
 
        $sUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_ANNOUNCEMENT'] . '&id=' . $aData[$CNF['FIELD_ID']]));

        $sTitle = $this->getTitle($aData);
        $sText = $this->getText($aData);
        $sSummary = $this->getSummary($aData, $sTitle, $sText, $sUrl); 
        $sSummaryPlain = isset($CNF['PARAM_CHARS_SUMMARY_PLAIN']) && $CNF['PARAM_CHARS_SUMMARY_PLAIN'] ? BxTemplFunctions::getInstance()->getStringWithLimitedLength(strip_tags($sSummary), (int)getParam($CNF['PARAM_CHARS_SUMMARY_PLAIN'])) : '';
  
        $this->addCss('main.css');
  
		return $this->parseHtmlByName('unit_announcement.html', array( 

            'class' => $this->_getUnitClass($aData,(isset($aParams['template_name']) ? $aParams['template_name'] : '')),
			'id' => $iId,	
			'title' => $sTitle,
            'title_attr' => bx_html_attribute($sTitle), 
            'summary' => $sSummary,
            'text' => $sText, 
			'content_url' => $sUrl,  
		)); 
    }
  
    public function announcementText ($aData, $sTemplateName = 'announcement-text.html')
    {
        $CNF = &$this->_oConfig->CNF;
  
        $aVars = $aData; 
        $aVars['entry_title'] = isset($aData[$CNF['FIELD_ANNOUNCEMENT_TITLE']]) ? $aData[$CNF['FIELD_ANNOUNCEMENT_TITLE']] : '';
        $aVars['entry_text'] = $aData[$CNF['FIELD_ANNOUNCEMENT_TEXT']];

        return $this->parseHtmlByName($sTemplateName, $aVars);
    }
 
    public function announcementBreadcrumb($aAnnouncementContentInfo, $aTmplVarsItems = array())
    {
    	$CNF = &$this->_oConfig->CNF;
 
        $aContentInfo = $this->_oDb->getContentInfoById($aAnnouncementContentInfo[$CNF['FIELD_LISTING_ID']]);

        $oPermalink = BxDolPermalinks::getInstance();

        $aTmplVarsItems = array();
  
		if($aContentInfo[$CNF['FIELD_SUBCATEGORY']])
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_SUBCATEGORY']], $oPermalink, $aTmplVarsItems);
		else
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_CATEGORY']], $oPermalink, $aTmplVarsItems);
 
        $aTmplVarsItems = array_reverse($aTmplVarsItems);
        
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aContentInfo[$CNF['FIELD_TITLE']])
        );
 
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_ANNOUNCEMENT'] . '&id=' . $aAnnouncementContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aAnnouncementContentInfo[$CNF['FIELD_TITLE']])
        );

    	return parent::entryBreadcrumb($aContentInfo, $aTmplVarsItems);
    }
 
//end announcement

//begin schedule

	public function schedule_main_unit($aData)
    {

        $oPermalink = BxDolPermalinks::getInstance();
 
    	$oModule = $this->getModule();

        $CNF = &$oModule->_oConfig->CNF;

        $iProfile = bx_get_logged_profile_id();
 
        $bDeleteSchedule = $aData[$CNF['FIELD_AUTHOR']] == $iProfile;
  
		$iId = (int)$aData[$CNF['FIELD_ID']];
  
		$oAuthor = BxDolProfile::getInstanceMagic($aData[$CNF['FIELD_AUTHOR']]);
 
        $sUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_SCHEDULE'] . '&id=' . $aData[$CNF['FIELD_ID']]));

        $sTitle = $this->getTitle($aData);
        $sText = $this->getText($aData);
        $sSummary = $this->getSummary($aData, $sTitle, $sText, $sUrl); 
        $sSummaryPlain = isset($CNF['PARAM_CHARS_SUMMARY_PLAIN']) && $CNF['PARAM_CHARS_SUMMARY_PLAIN'] ? BxTemplFunctions::getInstance()->getStringWithLimitedLength(strip_tags($sSummary), (int)getParam($CNF['PARAM_CHARS_SUMMARY_PLAIN'])) : '';
  
        $this->addCss('main.css');
  
		return $this->parseHtmlByName('unit_schedule.html', array( 

            'class' => $this->_getUnitClass($aData,(isset($aParams['template_name']) ? $aParams['template_name'] : '')),
			'id' => $iId,
			'start_time' => date('M d, Y g:i A', $aData['start_time']),
            'end_time' => date('M d, Y g:i A', $aData['end_time']), 
			'title' => $sTitle,
            'title_attr' => bx_html_attribute($sTitle), 
            'summary' => $sSummary,
            'text' => $sText, 
			'content_url' => $sUrl,  
		)); 
    }
  
    public function scheduleText ($aData, $sTemplateName = 'schedule-text.html')
    {
        $CNF = &$this->_oConfig->CNF;
  
        $aVars = $aData; 
        $aVars['entry_title'] = isset($aData[$CNF['FIELD_SCHEDULE_TITLE']]) ? $aData[$CNF['FIELD_SCHEDULE_TITLE']] : '';
        $aVars['entry_text'] = $aData[$CNF['FIELD_SCHEDULE_TEXT']];

        return $this->parseHtmlByName($sTemplateName, $aVars);
    }
 
    public function scheduleBreadcrumb($aScheduleContentInfo, $aTmplVarsItems = array())
    {
    	$CNF = &$this->_oConfig->CNF;
 
        $aContentInfo = $this->_oDb->getContentInfoById($aScheduleContentInfo[$CNF['FIELD_LISTING_ID']]);

        $oPermalink = BxDolPermalinks::getInstance();

        $aTmplVarsItems = array();
  
		if($aContentInfo[$CNF['FIELD_SUBCATEGORY']])
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_SUBCATEGORY']], $oPermalink, $aTmplVarsItems);
		else
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_CATEGORY']], $oPermalink, $aTmplVarsItems);
 
        $aTmplVarsItems = array_reverse($aTmplVarsItems);
        
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aContentInfo[$CNF['FIELD_TITLE']])
        );
 
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_SCHEDULE'] . '&id=' . $aScheduleContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aScheduleContentInfo[$CNF['FIELD_TITLE']])
        );

    	return parent::entryBreadcrumb($aContentInfo, $aTmplVarsItems);
    }

//end schedule

    function serviceAttachments ($aData, $aParams = array())
    {
         return $this->subitemAttachmentsByStorage('service', $this->getModule()->_oConfig->CNF['OBJECT_STORAGE_SERVICE'], $aData, $aParams);
    }
 
    public function entryBreadcrumb($aContentInfo, $aTmplVarsItems = array())
    {
    	$CNF = &$this->_oConfig->CNF;

        $oPermalink = BxDolPermalinks::getInstance();

        $aTmplVarsItems = array();
  
		if($aContentInfo[$CNF['FIELD_SUBCATEGORY']])
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_SUBCATEGORY']], $oPermalink, $aTmplVarsItems);
		else
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_CATEGORY']], $oPermalink, $aTmplVarsItems);
 
        $aTmplVarsItems = array_reverse($aTmplVarsItems);
        
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aContentInfo[$CNF['FIELD_TITLE']])
        );

    	return parent::entryBreadcrumb($aContentInfo, $aTmplVarsItems);
    }
   
    public function categoriesList($aParams = array())
    {
        $CNF = &$this->_oConfig->CNF;

        $sResult = $this->_categoriesList(0, array(
            'cat_url' => bx_absolute_url(BxDolPermalinks::getInstance()->permalink($CNF['URL_CATEGORIES'], array('category' => ''))),
            'subcat_url' => bx_absolute_url(BxDolPermalinks::getInstance()->permalink($CNF['URL_CATEGORIES'], array('subcategory' => '')))
        ));

        if(empty($sResult) && isset($aParams['show_empty']) && $aParams['show_empty'] === true)
            $sResult = MsgBox(_t('_Empty'));

        return $sResult;
    }

    /**
     * Use Gallery image for both because currently there is no Unit types with small thumbnails.
     */
    public function getPublicUnitThumbAndGallery ($aData)
    {
        list($sPhotoThumb, $sPhotoGallery) = parent::getUnitThumbAndGallery($aData);

        return array($sPhotoGallery, $sPhotoGallery);
    }

    /**
     * Use Gallery image for both because currently there is no Unit types with small thumbnails.
     */
    protected function getUnitThumbAndGallery ($aData)
    {
        list($sPhotoThumb, $sPhotoGallery) = parent::getUnitThumbAndGallery($aData);

        return array($sPhotoGallery, $sPhotoGallery);
    }

    protected function _categoriesList($iParentId, $aParams = array())
    {
        $aCategories = $this->_oDb->getCategories(array('type' => 'parent_id', 'parent_id' => $iParentId));
 
		$iCategoryId = $iSubCategoryId = 0;
		if($iCategoryId = bx_get('category')){
			//
		}elseif($iSubCategoryId = bx_get('subcategory')){ 
			$aCategory = $this->_oDb->getCategories(array('type' => 'id', 'id' => $iSubCategoryId));
			$iCategoryId = $aCategory['parent_id'];
		}

        $aTmplVars = array();
        foreach($aCategories as $aCategory) {

			$sClass = '';

            $iItems = (int)$aCategory['items'];

            $sSibcategories = $this->_categoriesList($aCategory['id'], $aParams);
            //if($iItems == 0 && empty($sSibcategories))
            /////if(empty($sSibcategories))
                /////continue;

			if($iParentId==0){ 
				$sClass = ($iCategoryId==$aCategory['id']) ? 'bx-form-collapsable' : 'bx-form-collapsable bx-form-collapsed bx-form-section-hidden';
			}

            $aTmplVars[] = array(
                'url' => (($iParentId==0) ? $aParams['cat_url'] : $aParams['subcat_url']) . $aCategory['id'],
                'title' => _t($aCategory['title']),
 
                'bx_if:show_parentcategory1' => array(
                    'condition' => $iParentId==0,
                    'content' => array ()
                 ),
                'bx_if:show_parentcategory2' => array(
                    'condition' => $iParentId==0,
                    'content' => array ()
                 ),

				'class' => $sClass,
                'bx_if:show_icon' => array(
                    'condition' => false /*!empty($aCategory['icon'])*/,
                    'content' => array(
                        'icon' => $aCategory['icon'],
                    )
                ),
                'bx_if:show_counter' => array(
                    'condition' => true, /*$iItems != 0,*/
                    'content' => array(
                        'items' => $iItems,
                    )
                ),
                'bx_if:show_subcategories' => array(
                    'condition' => !empty($sSibcategories),
                    'content' => array(
                        'subcategories' => $sSibcategories
                    )
                )
            );
        }

        if(empty($aTmplVars))
            return '';

        return $this->parseHtmlByName('categories.html', array(
            'bx_repeat:categories' => $aTmplVars
        ));
    }

    protected function _entryBreadcrumb($iCategory, &$oPermalink, &$aTmplVarsItems)
    {
        $CNF = &$this->_oConfig->CNF;

        $aCategory = $this->_oDb->getCategories(array('type' => 'id', 'id' => $iCategory));
        if(empty($aCategory) || !is_array($aCategory))
            return;

        $sParam = empty($aCategory['parent_id']) ? $CNF['GET_PARAM_CATEGORY'] : $CNF['GET_PARAM_SUBCATEGORY'];

        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink($CNF['URL_CATEGORIES'], array($sParam => $aCategory['id']))),
            'title' => bx_process_output(_t($aCategory['title']))
        );

        if(empty($aCategory['parent_id']))
            return;

        $this->_entryBreadcrumb((int)$aCategory['parent_id'], $oPermalink, $aTmplVarsItems);
    }

    public function entryRating($aData)
    {
        $CNF = &$this->getModule()->_oConfig->CNF;

        $sVotes = '';
        $oVotes = BxDolVote::getObjectInstance($CNF['OBJECT_VOTES'], $aData['id']);
        if($oVotes) {
            $sVotes = $oVotes->getElementBlock(array('show_counter' => true, 'show_legend' => true));
            if(!empty($sVotes))
                $sVotes = $this->parseHtmlByName('entry-rating.html', array(
                'content' => $sVotes,
            ));
        }

        return $sVotes; 
    }

    /**
     * Get review_unit 
     */
    function review_unit ($aData)
    {  
        $CNF = &$this->_oConfig->CNF;
 
        $oProfile = BxDolProfile::getInstance($aData['author_id']);
        if (!$oProfile) 
            $oProfile = BxDolProfileUndefined::getInstance();

        if (!$oProfile){
             $sAuthorName = _t('_mz_listing_label_someone');
			 $sAuthorUrl = 'javascript:void(0)';
		}else{ 
			 $sAuthorName = $oProfile->getDisplayName(); 
			 $sAuthorUrl = $oProfile->getUrl();
		}
 
		$sObjectName = $this->getModule()->_oDb->getCandleObjectName($aData['object']); 

        $aVars = array (
            'caption' => _t('_mz_listing_reviewed_on'),
            'author' => $sAuthorName,
            'author_url' => $sAuthorUrl,
            'created' => bx_time_js($aData['cmt_time']),
            'thumb_url' => $this->getIconUrl($sObjectName),
        );

        return $this->parseHtmlByName('review_unit.html', $aVars); 
    }
 
    function review_main_unit ($aData)
    {   
        $CNF = &$this->_oConfig->CNF;
  
		$oCmts = BxDolCmts::getObjectInstance($CNF['OBJECT_REVIEWS'], $aData['cmt_object_id']);
		if (!$oCmts || !$oCmts->isEnabled())
			return ''; 

		$aBp = $aDp = array();  
		$aDp['view_only'] = true;
		$aDp['type'] = 'threaded';
		$aBp['type'] = '';
		$aData['cmt_unique_id'] = time();
		$aData['cmt_status_admin'] = 'active';// una 13 b4

		$oPermalink = BxDolPermalinks::getInstance();
		$sUrl = bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aData['cmt_object_id']));
		$sTitle = bx_process_output($aData[$CNF['FIELD_TITLE']]);
 
        $aVars = array ( 
            'url' => $sUrl,
            'title' => $sTitle, 
            'comment' => $oCmts->getComment($aData, $aBp, $aDp),
        );

        return $this->parseHtmlByName('review_main_unit.html', $aVars); 
    } 

    public function claimBreadcrumb($aClaimContentInfo, $aTmplVarsItems = array())
    {
    	$CNF = &$this->_oConfig->CNF;
 
        $aContentInfo = $this->_oDb->getContentInfoById($aClaimContentInfo[$CNF['FIELD_LISTING_ID']]);

        $oPermalink = BxDolPermalinks::getInstance();

        $aTmplVarsItems = array();
  
		if($aContentInfo[$CNF['FIELD_SUBCATEGORY']])
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_SUBCATEGORY']], $oPermalink, $aTmplVarsItems);
		else
			$this->_entryBreadcrumb($aContentInfo[$CNF['FIELD_CATEGORY']], $oPermalink, $aTmplVarsItems);
 
        $aTmplVarsItems = array_reverse($aTmplVarsItems);
        
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aContentInfo[$CNF['FIELD_TITLE']])
        );
 
        $aTmplVarsItems[] = array(
            'url' => bx_absolute_url($oPermalink->permalink('page.php?i=' . $CNF['URI_VIEW_CLAIM'] . '&id=' . $aClaimContentInfo[$CNF['FIELD_ID']])),
            'title' => bx_process_output($aClaimContentInfo[$CNF['FIELD_TITLE']])
        );

    	return parent::entryBreadcrumb($aContentInfo, $aTmplVarsItems);
    }

    public function entryHours ($aData, $sTemplateName = 'entry-content.html')
    {
    	$CNF = &$this->_oConfig->CNF;

        $aVars = array('content' => $aData[$CNF['FIELD_HOURS']]);

        if (empty($aData[$CNF['FIELD_HOURS']]))
            return false;
        
        return $this->parseHtmlByName($sTemplateName, $aVars);
    }
 
}

/** @} */
