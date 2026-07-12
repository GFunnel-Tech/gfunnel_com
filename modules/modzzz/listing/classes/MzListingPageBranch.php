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

class MzListingPageBranch extends BxTemplPage
{
	protected $MODULE;
    protected $_oModule;

    protected $_aContentInfo;
    protected $_aBranchInfo;

    public function __construct($aObject, $oTemplate = false)
    {

        parent::__construct($aObject, $oTemplate);

        $this->MODULE = 'mz_listing';
        $this->_oModule = BxDolModule::getInstance($this->MODULE);

        $CNF = &$this->_oModule->_oConfig->CNF;

        $iBranchId = bx_process_input(bx_get('id'), BX_DATA_INT);
        if ($iBranchId)
            $this->_aBranchInfo = $this->_oModule->_oDb->getBranchInfoById($iBranchId);

        if (!empty($this->_aBranchInfo[$CNF['FIELD_LISTING_ID']]))
            $this->_aContentInfo = $this->_oModule->_oDb->getContentInfoById($this->_aBranchInfo[$CNF['FIELD_LISTING_ID']]);
 
		if ($this->_aContentInfo) {
            $sTitle = isset($this->_aContentInfo[$CNF['FIELD_TITLE']]) ? $this->_aContentInfo[$CNF['FIELD_TITLE']] : strmaxtextlen($this->_aContentInfo[$CNF['FIELD_TEXT']], 20, '...');
            $sUrl = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $this->_aContentInfo[$CNF['FIELD_ID']]);
            $this->addMarkers($this->_aContentInfo); // every field can be used as marker
            $this->addMarkers(array(
                'title' => $sTitle,
                'entry_link' => $sUrl,
            ));

            // select view entry submenu
            $oMenuSubmenu = BxDolMenu::getObjectInstance('sys_site_submenu');
            $oMenuSubmenu->setObjectSubmenu($CNF['OBJECT_MENU_SUBMENU_VIEW_BRANCH'], array (
                'title' => $sTitle,
                'link' => $sUrl,
                'icon' => $CNF['ICON'],
            ));
        }

        $this->addMarkers($this->_aBranchInfo);
    }

	public function getCode ()
    {
        // check if content exists
        if (!$this->_aContentInfo || !$this->_aBranchInfo) { 
            $this->_oTemplate->displayPageNotFound();
            exit;
        }

        // permissions check 
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->checkAllowedView($this->_aContentInfo))) {
            $this->_oTemplate->displayAccessDenied($sMsg);
            exit;
        }
  
        return parent::getCode ();
    }
 
}

/** @} */
