<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Affiliate System Affiliate System
 * @ingroup     UnaModules
 *
 * @{
 */

/**
 * Create/Edit entry form
 */
class AqbAffiliateBannersFormEntry extends BxBaseModTextFormEntry
{
	protected $_sGhostTemplateCover = 'banner-preview-template.html';
    public function __construct($aInfo, $oTemplate = false)
    {
        $oCNF = &$this->_oModule->_oConfig->CNF;
        $this->MODULE = 'aqb_affiliate';
        parent::__construct($aInfo, $oTemplate);


        if (isset($this->aInputs[$oCNF['FB_ADDED']])) {
            $this->aInputs[$oCNF['FB_ADDED']]['date_filter'] = BX_DATA_INT;
            $this->aInputs[$oCNF['FB_ADDED']]['date_format'] = BX_FORMAT_DATE;
        }

        if (isset($this->aInputs[$oCNF['FB_IMG']])) {
            $this->aInputs[$oCNF['FB_IMG']]['storage_object'] = $oCNF['OBJECT_STORAGE'];
            $this->aInputs[$oCNF['FB_IMG']]['uploaders'] = !empty($this->aInputs[$oCNF['FB_IMG']]['value']) ? unserialize($this->aInputs[$oCNF['FB_IMG']]['value']) : $oCNF['OBJECT_UPLOADERS'];
            $this->aInputs[$oCNF['FB_IMG']]['images_transcoder'] = $oCNF['OBJECT_IMAGES_TRANSCODER_PREVIEW'];
            $this->aInputs[$oCNF['FB_IMG']]['storage_private'] = 0;
            $this->aInputs[$oCNF['FB_IMG']]['multiple'] = true;
            $this->aInputs[$oCNF['FB_IMG']]['content_id'] = 0;
            $this->aInputs[$oCNF['FB_IMG']]['ghost_template'] = '';
        }
    }

    function insert ($aValsToAdd = array(), $isIgnore = false)
    {
        $oCNF = &$this->_oModule->_oConfig->CNF;

        if(empty($aValsToAdd[$oCNF['FB_ADDED']])) {
            $iAdded = 0;
            if(isset($this->aInputs[$oCNF['FB_ADDED']]))
                $iAdded = $this->getCleanValue($oCNF['FB_ADDED']);

            if(empty($iAdded))
                $iAdded = time();

            $aValsToAdd[$oCNF['FB_ADDED']] = $iAdded;
        }

        $iContentId = BxDolForm::insert($aValsToAdd, $isIgnore);

        if(!empty($iContentId))
            $this->processFiles($oCNF['FB_IMG'], $iContentId, true);

        return $iContentId;
    }

    function update ($iContentId, $aValsToAdd = array(), &$aTrackTextFieldsChanges = NULL)
    {
        $oCNF = &$this->_oModule->_oConfig->CNF;

        $bUploadResult = false;
        if(!empty($iContentId))
            $bUploadResult = $this->processFiles($oCNF['FB_IMG'], $iContentId, true);

       return $bUploadResult || parent::update($iContentId, $aValsToAdd);
    }

    public function processFiles ($sFieldFile, $iContentId = 0, $isAssociateWithContent = false)
    {
        $oCNF = &$this->_oModule->_oConfig->CNF;

        if (!isset($this->aInputs[$sFieldFile]) || !isset($this->aInputs[$sFieldFile]['storage_object']))
            return true;

        $oStorage = BxDolStorage::getObjectInstance($this->aInputs[$sFieldFile]['storage_object']);
        if (!$oStorage)
            return false;

        if (!isset($_FILES[$this->aInputs[$sFieldFile]['name']]))
            return false;

        $sTempFile = $_FILES[$this->aInputs[$sFieldFile]['name']]['tmp_name'];
        $sTargetFile =  BX_DIRECTORY_PATH_TMP . $_FILES[$this->aInputs[$sFieldFile]['name']]['name'];

        $iId = false;
        if (move_uploaded_file($sTempFile, $sTargetFile)) {
            $iId = $oStorage->storeFileFromPath($sTargetFile, false, 0, $iContentId);
            $oStorage->afterUploadCleanup($iId, 0, $iContentId);
        }

        $aInfo = getimagesize($sTargetFile);
        if (isset($aInfo[0]) && isset($aInfo[1])) {
            $iWidth = (int)$aInfo[0];
            $iHeight = (int)$aInfo[1];
            $this->_oModule->_oDb->updateBanner($iContentId, $oCNF['FB_SIZE'], "{$iWidth}X{$iHeight}");
        }

        return $iId ? $this->_oModule->_oDb -> updateBanner($iContentId, $oCNF['FB_IMG'], $iId) : false;
    }

	function initChecker ($aValues = array (), $aSpecificValues = array())
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if (isset($CNF['FB_IMG']) && isset($this->aInputs[$CNF['FB_IMG']])) {
            if (!empty($aValues)) {
                $aContentInfo = $this->_oModule->_oDb->getBannerInfo($aValues[$CNF['FB_ID']]);
                $this->aInputs[$CNF['FB_IMG']]['content_id'] = $aValues[$CNF['FB_IMG']];
                $this->aInputs['icon_preview']['content'] = $this->_oModule->_oTemplate->parseHtmlByName($this->_sGhostTemplateCover, $this->_getCoverGhostTmplVars($aContentInfo));
            }
        }

        parent::initChecker ($aValues, $aSpecificValues);
    }

	protected function _getCoverGhostTmplVars($aContentInfo = array())
    {
    	$CNF = &$this->_oModule->_oConfig->CNF;

    	return array (
			'file_icon' => BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW']) -> getFileUrl($this->aInputs[$CNF['FB_IMG']]['content_id'])
		);
    }
}

/** @} */
