<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    News News
 * @ingroup     ModzzzModules
 *
 * @{
 */

/*
 * Module representation.
 */
class MzNewsTemplate extends BxBaseModTextTemplate
{
    function __construct(&$oConfig, &$oDb)
    {
        $this->MODULE = 'mz_news';

        parent::__construct($oConfig, $oDb);
    }
/*
    public function entryBreadcrumb($aContentInfo, $aTmplVarsItems = array())
    { 
        if(!empty($aTmplVarsItems) && is_array($aTmplVarsItems))
            return parent::entryBreadcrumb($aContentInfo, $aTmplVarsItems);

    	$CNF = &$this->getModule()->_oConfig->CNF;
 
        $aTmplVarsItems = array();
        if(!empty($CNF['OBJECT_CATEGORY']) && !empty($CNF['FIELD_CATEGORY'])) {
            $oCategory = BxDolCategory::getObjectInstance($CNF['OBJECT_CATEGORY']);

            $aTmplVarsItems[] = array(
                'url' => $oCategory->getCategoryUrl($aContentInfo[$CNF['FIELD_CATEGORY']]),
                'title' => $oCategory->getCategoryTitle($aContentInfo[$CNF['FIELD_CATEGORY']])
            );
        }

    	$aTmplVarsItems[] = array(
            'url' => BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aContentInfo[$CNF['FIELD_ID']]),
            'title' => bx_process_output($aContentInfo[$CNF['FIELD_TITLE']])
        );

    	return parent::entryBreadcrumb($aContentInfo, $aTmplVarsItems);
    }
*/


    /**
     * Use Gallery image for both because currently there is no Unit types with small thumbnails.
     */
    protected function getUnitThumbAndGallery ($aData)
    {
        list($sPhotoThumb, $sPhotoGallery) = parent::getUnitThumbAndGallery($aData);

        return array($sPhotoGallery, $sPhotoGallery);
    }

	//una 13 beta
    protected function getUnit ($aData, $aParams = array())
    {
        $CNF = &$this->getModule()->_oConfig->CNF;

        $sUnitName = $this->_getUnitName($aData, isset($aParams['template_name']) ? $aParams['template_name'] : '');
        if(in_array($sUnitName, ['unit_gallery', 'unit_showcase']))
            $aParams['badges_compact'] = false;

        $aVars = parent::getUnit($aData, $aParams);

		if($CNF['PARAM_SOURCE_REDIRECT'] && $aData[$CNF['FIELD_SOURCE']])
		{
			$aVars['content_url']  = $aData[$CNF['FIELD_SOURCE']];
			$aVars['bx_if:thumb']['content']['content_url']  = $aData[$CNF['FIELD_SOURCE']];
			$aVars['bx_if:no_thumb']['content']['content_url']  = $aData[$CNF['FIELD_SOURCE']];
		}
			 
		return $aVars;
    }
 
    public function getTmplVarsText($aData)
    { 
        $CNF = &$this->getModule()->_oConfig->CNF;

		$aVars = parent::getTmplVarsText($aData);
 
        $sStylePrefix = $this->_oConfig->getPrefix('style'); 
        $aVars['bx_if:source'] = [
            'condition' => $aData[$CNF['FIELD_SOURCE']],
            'content' => [
                'style_prefix' => $sStylePrefix,
                'source' => $aData[$CNF['FIELD_SOURCE']]
            ]
        ];

        return $aVars;
	}
 

}

/** @} */
