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

class MzListingFormClaim extends BxBaseModTextFormEntry
{
    public function __construct($aInfo, $oTemplate = false)
    {
        $this->MODULE = 'mz_listing';
 
        parent::__construct($aInfo, $oTemplate);

        $CNF = &$this->_oModule->_oConfig->CNF;  

        if(isset($this->aInputs[$CNF['FIELD_PHOTO']])) {
            $this->aInputs[$CNF['FIELD_PHOTO']]['storage_object'] = $CNF['OBJECT_STORAGE_CLAIM'];
            $this->aInputs[$CNF['FIELD_PHOTO']]['images_transcoder'] = $CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW_CLAIM'];
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
            $aClaimInfo = array();
            if ($aValues && !empty($aValues['id'])) {
                $aClaimInfo = $this->_oModule->_oDb->getClaimInfoById ($aValues['id']);
                $this->aInputs[$CNF['FIELD_PHOTO']]['content_id'] = $aValues['id'];
            }

            $this->aInputs[$CNF['FIELD_PHOTO']]['ghost_template'] = $this->_oModule->_oTemplate->parseHtmlByName('form_ghost_template_subitem.html', $this->_getPhotoGhostTmplVars($aClaimInfo));
        }

        return BxTemplFormView::initChecker($aValues, $aSpecificValues);
    }
 
    function delete ($iContentId, $aContentInfo = array())
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
  
        // delete associated files
  
		$oStorage = BxDolStorage::getObjectInstance($CNF['OBJECT_STORAGE_CLAIM']);
		if ($oStorage)
			$oStorage->queueFilesForDeletionFromGhosts($aContentInfo[$CNF['FIELD_AUTHOR']], $iContentId);
 
        // delete db record
      
        BxDolFormQuery::removeFormField($this->id, $iContentId);
 
        $oChecker = new $this->_sChecker($this->_sCheckerHelper);
        $oChecker->setFormMethod($this->aFormAttrs['method'], $this->_aSpecificValues);
        $sSql = $oChecker->dbDelete($iContentId, $this->aParams['db'], $this->aInputs);
        if (!$sSql)
            return false;

        return BxDolDb::getInstance()->query($sSql);
    }


    function _deleteFile ($iFileId, $sStorage = '')
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
 
        if (!$iFileId)
            return true;

		$sStorage = $CNF['OBJECT_STORAGE_CLAIM'];
		$oStorage = BxDolStorage::getObjectInstance($sStorage);
        if (!$oStorage)
            return false;

        if (!$oStorage->getFile($iFileId))
            return true;

        $iProfileId = bx_get_logged_profile_id();
        return $oStorage->deleteFile($iFileId, $iProfileId);
    }




  
}

/** @} */
