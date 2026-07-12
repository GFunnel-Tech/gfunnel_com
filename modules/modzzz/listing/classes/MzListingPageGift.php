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

class MzListingPageGift extends BxTemplPage
{
	protected $MODULE;
    protected $_oModule;

    protected $_aEntryInfo;
    protected $_aGiftInfo;

    public function __construct($aObject, $oTemplate = false)
    {
        parent::__construct($aObject, $oTemplate);

        $this->MODULE = 'mz_listing';
        $this->_oModule = BxDolModule::getInstance($this->MODULE);

        $CNF = &$this->_oModule->_oConfig->CNF;

        $iGiftId = bx_process_input(bx_get('id'), BX_DATA_INT);
        if ($iGiftId)
            $this->_aGiftInfo = $this->_oModule->_oDb->getGiftInfoById($iGiftId);

        if (!empty($this->_aGiftInfo[$CNF['FIELD_LISTING_ID']]))
            $this->_aEntryInfo = $this->_oModule->_oDb->getContentInfoById($this->_aGiftInfo[$CNF['FIELD_LISTING_ID']]);

		if ($this->_aEntryInfo) {
            $sTitle = isset($this->_aEntryInfo[$CNF['FIELD_TITLE']]) ? $this->_aEntryInfo[$CNF['FIELD_TITLE']] : strmaxtextlen($this->_aEntryInfo[$CNF['FIELD_TEXT']], 20, '...');
            $sUrl = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $this->_aEntryInfo[$CNF['FIELD_ID']]);
            $this->addMarkers($this->_aEntryInfo); // every field can be used as marker
            $this->addMarkers(array(
                'title' => $sTitle,
                'entry_link' => $sUrl,
            ));

            // select view entry submenu
            $oMenuSubmenu = BxDolMenu::getObjectInstance('sys_site_submenu');
            $oMenuSubmenu->setObjectSubmenu($CNF['OBJECT_MENU_SUBMENU_VIEW_GIFT'], array (
                'title' => $sTitle,
                'link' => $sUrl,
                'icon' => $CNF['ICON'],
            ));
        }
    }

	public function getCode ()
    {
        // check if content exists
        if (!$this->_aEntryInfo || !$this->_aGiftInfo) {
            $this->_oTemplate->displayPageNotFound();
            exit;
        }

        // permissions check 
        if (CHECK_ACTION_RESULT_ALLOWED !== ($sMsg = $this->_oModule->checkAllowedView($this->_aEntryInfo))) {
            $this->_oTemplate->displayAccessDenied($sMsg);
            exit;
        }

        return parent::getCode ();
    }
 
}

/** @} */
