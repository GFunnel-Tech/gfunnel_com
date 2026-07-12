<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) Publicchat.in
 * 
 * @defgroup    Complete Profile Publicchat.in module
 * @ingroup     PublicchatModules
 *
 * @{
 */

bx_import('BxDolModule');
bx_import('BxDolProfile');

class PcCbModule extends BxDolModule 
{
    function __construct(&$aModule) 
    {
        parent::__construct($aModule);
    }

    function serviceStarting()
    {
    	
    	//Getting url of Profile picture
        $oProfile = BxDolProfile::getInstance();
		if (!$oProfile) {
		    bx_import('BxDolProfileUndefined');
		    $oProfile = BxDolProfileUndefined::getInstance();
		}

		//Getting the module name of Profile if Organization profile is there
		$PcCb_user_module_type = substr($oProfile->getModule(),3);

		if ($PcCb_user_module_type == 'organizations') {
			$PcCb_user_module_type = 'organization';
		}

		//Getting Person or Organization Profile id
		$PcCb_user_id = $oProfile->getContentId();

		//Getting If cover is uploaded by user or not
		$PcCb_text = explode('_', $oProfile->getCover());
		$PcCb_text = array_flip($PcCb_text);
		if (isset($PcCb_text['persons']) || isset($PcCb_text['organizations']) ){
			$PcCb_Cover = True;
		}
		else
		{
			$PcCb_Cover = False;
		}

		if(!$oProfile->hasImage()){
			return '<center><img src="'. $oProfile->getThumb() .'"/> <br /> <a href="'. $oProfile->getEditUrl() .'">' . _t('_publicchat_complete_profile_upload_profile_pic') . '</a></center>'; 
		}

		else if(!$PcCb_Cover)
		{
			return '<center><img src="'. $oProfile->getUnitCover() .'" style="height:64px;"/> <br /> <a href="page/edit-'. $PcCb_user_module_type .'-cover?id='. $PcCb_user_id.'">' ._t('_publicchat_complete_profile_upload_profile_cover') . '</a></center>';
		}

		else {

		}


    }
}

/** @} */
