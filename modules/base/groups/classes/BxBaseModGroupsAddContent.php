<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    BaseGroups Base classes for groups modules
 * @ingroup     UnaModules
 *
 * @{
 */

class BxBaseModGroupsAddContent extends BxBaseMenu
{
    protected $_sModule;
    protected $_oModule;

    protected $_iContentId;
    protected $_aContentInfo;
    protected $_oContentProfile;

    public function __construct($aObject, $oTemplate = false)
    {
        parent::__construct($aObject, $oTemplate);
        
        $this->_oModule = BxDolModule::getInstance($this->_sModule);

        $iContentId = bx_process_input(bx_get('id'), BX_DATA_INT);
        if(empty($iContentId) && bx_get('profile_id') !== false)
            $iContentId = BxDolProfile::getInstance(bx_process_input(bx_get('profile_id'), BX_DATA_INT))->getContentId();

        if(!empty($iContentId))
            $this->setContentId($iContentId);
    }

    public function setContentId($iContentId)
    {
        $this->_iContentId = $iContentId;
        $this->_aContentInfo = $this->_oModule->_oDb->getContentInfoById($this->_iContentId);
        if(empty($this->_aContentInfo) || !is_array($this->_aContentInfo))
            return;

        $this->_oContentProfile = BxDolProfile::getInstanceByContentAndType($this->_iContentId, $this->_sModule);
        if(!$this->_oContentProfile) 
            return;

        $this->addMarkers(array(
            'profile_id' => $this->_oContentProfile->id()
        ));
    }

    protected function getMenuItemsRaw ()
    {
        $aMenuItems = parent::getMenuItemsRaw();       

        $iContextPid = $this->_oContentProfile->id();

        $aResult = [];
    	foreach($aMenuItems as $iKey => $aMenuItem) {
            if((int)$aMenuItem['active'] == 0)
                continue;

            if(bx_srv($aMenuItem['module'], 'check_allowed_add_in_context', [$this->_sModule]) !== CHECK_ACTION_RESULT_ALLOWED)
                continue;

            if(bx_srv($this->_sModule, 'check_allowed_post_in_profile', [$this->_iContentId, $aMenuItem['module']]) !== CHECK_ACTION_RESULT_ALLOWED)
                continue;

            $aResult[$iKey] = array_merge($aMenuItem, [
                'link' => bx_append_url_params($aMenuItem['link'], ['context_pid' => $iContextPid])
            ]);
    	}

        return $aResult;
    }
}

/** @} */
