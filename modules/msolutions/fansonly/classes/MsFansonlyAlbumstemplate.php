<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) MSolutions
 * 
 * @defgroup    Fansonly Fansonly module
 * @ingroup     MSolutionsModules
 *
 * @{
 */ 

/*
 * Module representation.
 */

bx_import('BxAlbumsTemplate');

class MsFansonlyAlbumstemplate extends BxAlbumsTemplate //BxBaseModTextTemplate
{

    function unitMedia ($aData, $isCheckPrivateContent = true, $sTemplateName = 'unit.html', $aParams = array())
    {
        $oModule = BxDolModule::getInstance($this->MODULE);
        $CNF = &$oModule->_oConfig->CNF;

        if (!($aMediaInfo = $oModule->_oDb->getMediaInfoById($aData['id'])))
            return '';
        
        $oStorage = BxDolStorage::getObjectInstance($CNF['OBJECT_STORAGE']);
        if (!($aFile = $oStorage->getFile($aMediaInfo['file_id'])))
            return '';

        $aVars = $this->mediaVars($aMediaInfo, $CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW'], $CNF['OBJECT_VIDEOS_TRANSCODERS']['poster_preview'], $aParams);

        $aVarsTmp = $aVars['bx_if:image']['condition'] ? $aVars['bx_if:image']['content'] : $aVars['bx_if:video']['content'];

//Media Image replacement
$oModuleFansOnly = BxDolModule::getInstance('ms_fansonly');
$image = $oModuleFansOnly->_oTemplate->getPreviewImage();

        $aVars = array_merge($aVars, array (
            'class' => $this->_getUnitClass($aData, $sTemplateName),
            'module_name' => _t($CNF['T']['txt_media_single']),
            'content_url' => $aVars['url'],
            'ts' => $aFile['added'],
            'actions' => '',


            'bx_if:thumb' => array (
                'condition' => isset($aData['file_id']) && $aData['file_id'],
                'condition' => false,
                'content' => array (
                    'title' => "titlemedia", //bx_process_output($aData['title']),
                    'summary_attr' => '',
                    'content_url' => $aVarsTmp['url'],
                    'thumb_url' => $image, //$aVarsTmp['url_img'],
                    'gallery_url' => '',
                    'strecher' => '',
                ),
            ),
            'bx_if:no_thumb' => array (
                'condition' => false,
                'content' => array (),
            ),
        ));
        $title = "For fans only";

        $aVars["bx_if:image"]["content"]["url_img"] = $image;
        $aVars["bx_if:video"]["content"]["url_img"] = $image;

        return $this->parseHtmlByName($sTemplateName, $aVars);
    }
    
}

/** @} */
