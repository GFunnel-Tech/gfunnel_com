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

class MzListingPageBrowseSchedule extends BxTemplPage
{
	protected $_aEntryInfo; 

    public function __construct($aObject, $oTemplate = false)
    {
        parent::__construct($aObject, $oTemplate);

        $this->MODULE = 'mz_listing';
        $this->_oModule = BxDolModule::getInstance($this->MODULE);

        $CNF = &$this->_oModule->_oConfig->CNF;

        $iEntryId = bx_process_input(bx_get('id'), BX_DATA_INT);
        if ($iEntryId)
            $this->_aEntryInfo = $this->_oModule->_oDb->getContentInfoById($iEntryId);

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
            $oMenuSubmenu->setObjectSubmenu($CNF['OBJECT_MENU_SUBMENU_VIEW_SCHEDULE'], array (
                'title' => $sTitle,
                'link' => $sUrl,
                'icon' => $CNF['ICON'],
            ));
        }
    }
}

/** @} */
