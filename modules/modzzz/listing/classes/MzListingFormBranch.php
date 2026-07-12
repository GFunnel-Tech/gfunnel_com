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

class MzListingFormBranch extends BxBaseModTextFormEntry
{
    public function __construct($aInfo, $oTemplate = false)
    {
        $this->MODULE = 'mz_listing';
 
        parent::__construct($aInfo, $oTemplate);

        $CNF = &$this->_oModule->_oConfig->CNF; 

        if (isset($CNF['FIELD_LOCATION_PREFIX']) && isset($this->aInputs[$CNF['FIELD_LOCATION_PREFIX']])) {
            $this->aInputs[$CNF['FIELD_LOCATION_PREFIX']]['manual_input'] = true;
        } 

        if(isset($this->aInputs[$CNF['FIELD_PHOTO']])) {
            $this->aInputs[$CNF['FIELD_PHOTO']]['storage_object'] = $CNF['OBJECT_STORAGE_BRANCH'];
            $this->aInputs[$CNF['FIELD_PHOTO']]['images_transcoder'] = $CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW_BRANCH'];
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

        if (!empty($CNF['FIELD_LOCATION_PREFIX']) && isset($this->aInputs[$CNF['FIELD_LOCATION_PREFIX']]) && isset($aValues[$CNF['FIELD_ID']]) && !empty($CNF['OBJECT_METATAGS_BRANCH']) && ($oMetatags = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS_BRANCH'])) && $oMetatags->locationsIsEnabled())
            $this->aInputs[$CNF['FIELD_LOCATION_PREFIX']]['value'] = $oMetatags->locationsString($aValues[$CNF['FIELD_ID']], false);

        if (isset($this->aInputs[$CNF['FIELD_PHOTO']])) {
            $aBranchInfo = array();
            if ($aValues && !empty($aValues['id'])) {
                $aBranchInfo = $this->_oModule->_oDb->getBranchInfoById ($aValues['id']);
                $this->aInputs[$CNF['FIELD_PHOTO']]['content_id'] = $aValues['id'];
            }

            $this->aInputs[$CNF['FIELD_PHOTO']]['ghost_template'] = $this->_oModule->_oTemplate->parseHtmlByName('form_ghost_template_subitem.html', $this->_getPhotoGhostTmplVars($aBranchInfo));
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
        if (!empty($CNF['OBJECT_COMMENTS_BRANCH'])) {
            $o = BxDolCmts::getObjectInstance($CNF['OBJECT_COMMENTS_BRANCH'], $iContentId);
            if ($o) $o->onObjectDelete();
        }
 
        // delete db record
      
        BxDolFormQuery::removeFormField($this->id, $iContentId);
 
        return parent::delete($iContentId);
    }

  
}

/** @} */
