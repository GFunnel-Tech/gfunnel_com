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

class MzListingMenuBranch extends BxTemplMenu
{
    protected $_sModule;
    protected $_oModule;

    protected $_aContentInfo;

    public function __construct($aObject, $oTemplate = false)
    {
        parent::__construct($aObject, $oTemplate);

        $this->_sModule = 'mz_listing';
        $this->_oModule = BxDolModule::getInstance($this->_sModule);

		$iId = bx_get('content_id') ? bx_get('content_id') : bx_get('id');
        $this->setContentId(bx_process_input($iId, BX_DATA_INT));
    }

    public function setContentId($iContentId)
    {
        $this->_aContentInfo = $this->_oModule->_oDb->getBranchInfoById($iContentId);
        if ($this->_aContentInfo)
            $this->addMarkers(array('content_id' => (int)$iContentId));
    }

    public function isVisible()
    {
    	if(!isset($this->_aObject['menu_items']))
            $this->_aObject['menu_items'] = $this->_oQuery->getMenuItems();

    	$bVisible = false;
    	foreach ($this->_aObject['menu_items'] as $a) {
            if((isset($a['active']) && !$a['active']) || (isset($a['visible_for_levels']) && !$this->_isVisible($a)))
                continue;

            $bVisible = true;
            break;
    	}

    	return $bVisible;
    }

    protected function _isVisible ($a)
    {
        if(!parent::_isVisible($a))
            return false;

        $sCheckFuncName = '';
        $aCheckFuncParams = array($this->_aContentInfo);
        switch ($a['name']) {
            case 'edit-listing-branch':
                $sCheckFuncName = 'checkAllowedEditBranch';
                break;

            case 'delete-listing-branch':
                $sCheckFuncName = 'checkAllowedDeleteBranch';
                break;
        }

        if(!$sCheckFuncName || !method_exists($this->_oModule, $sCheckFuncName))
            return true;

        return call_user_func_array(array($this->_oModule, $sCheckFuncName), $aCheckFuncParams) === CHECK_ACTION_RESULT_ALLOWED;
    }
}

/** @} */
