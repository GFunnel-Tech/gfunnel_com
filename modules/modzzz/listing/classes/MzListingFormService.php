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

class MzListingFormService extends BxBaseModTextFormEntry
{
    public function __construct($aInfo, $oTemplate = false)
    {
        $this->MODULE = 'mz_listing';
 
        parent::__construct($aInfo, $oTemplate);

        $CNF = &$this->_oModule->_oConfig->CNF; 

        if(isset($this->aInputs[$CNF['FIELD_PHOTO']])) {
            $this->aInputs[$CNF['FIELD_PHOTO']]['storage_object'] = $CNF['OBJECT_STORAGE_SERVICE'];
            $this->aInputs[$CNF['FIELD_PHOTO']]['images_transcoder'] = $CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW_SERVICE'];
            $this->aInputs[$CNF['FIELD_PHOTO']]['upload_buttons_titles'] = array(
            	'Simple' => _t('_mz_listing_form_input_pictures_uploader_simple_title'), 
            	'HTML5' => _t('_mz_listing_form_input_pictures_uploader_html5_title')
            );
            $this->aInputs[$CNF['FIELD_PHOTO']]['multiple'] = true;
        } 
    }
 
    function initChecker ($aValues = array (), $aSpecificValues = array())
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
 
        if (isset($this->aInputs[$CNF['FIELD_PHOTO']])) {
            $aServiceInfo = array();
            if ($aValues && !empty($aValues['id'])) {
                $aServiceInfo = $this->_oModule->_oDb->getServiceInfoById ($aValues['id']);
                $this->aInputs[$CNF['FIELD_PHOTO']]['content_id'] = $aValues['id'];
            }

            $this->aInputs[$CNF['FIELD_PHOTO']]['ghost_template'] = $this->_oModule->_oTemplate->parseHtmlByName('form_ghost_template_subitem.html', $this->_getPhotoGhostTmplVars($aServiceInfo));
        }

        return BxTemplFormView::initChecker($aValues, $aSpecificValues);
    }
  
    function delete ($iContentId, $aContentInfo = array())
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
/*
        // delete associated files

        $a = array('OBJECT_STORAGE', 'OBJECT_STORAGE_FILES', 'OBJECT_STORAGE_PHOTOS', 'OBJECT_STORAGE_VIDEOS', 'OBJECT_STORAGE_SOUNDS');
        foreach ($a as $k) {
            if (!empty($CNF[$k])) {
                $oStorage = BxDolStorage::getObjectInstance($CNF[$k]);
                if ($oStorage)
                    $oStorage->queueFilesForDeletionFromGhosts($aContentInfo[$CNF['FIELD_AUTHOR']], $iContentId);
            }
        }

        // delete associated objects data
*/ 
        if (!empty($CNF['OBJECT_COMMENTS_SERVICE'])) {
            $o = BxDolCmts::getObjectInstance($CNF['OBJECT_COMMENTS_SERVICE'], $iContentId);
            if ($o) $o->onObjectDelete();
        }
 
        // delete db record
      
        BxDolFormQuery::removeFormField($this->id, $iContentId);
 
        return parent::delete($iContentId);
    }


  
}

/** @} */
