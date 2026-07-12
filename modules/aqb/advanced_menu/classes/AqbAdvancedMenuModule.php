<?php defined('BX_DOL') or die('hack attempt');
/**
 *     copyright            : (C) 2019 AQB Soft
 *     website              : http://www.aqbsoft.com
 *
 * IMPORTANT: This is a commercial product made by AQB Soft. It cannot be modified for other than personal usage.
 * The "personal usage" means the product can be installed and set up for ONE domain name ONLY.
 * To be able to use this product for another domain names you have to order another copy of this product (license).
 *
 * This product cannot be redistributed for free or a fee without written permission from AQB Soft.
 *
 * This notice may not be removed from the source code.
 *
 * @defgroup    Advanced Menu Advanced Menu
 * @ingroup     UnaModules
 *
 * @{
 */


class AqbAdvancedMenuModule extends BxBaseModGeneralModule {
    public function __construct(&$aModule) {
        parent::__construct($aModule);
    }

    private function callHandler(&$sCode, &$aMenuItem, &$sMenuName, &$sModule, &$iContentId, &$oMenuObject, &$aContentData) {
        return eval($sCode);
    }

    private function getUnitMetaItemNamedValue($sText, $sValue, $sLink = '') {
        if (!empty($sLink))
            return '<span class="bx-def-font-grayed">'.$sText.'</span>: <a href="'.$sLink.'">'.bx_process_output($sValue).'</a>';
        else
            return '<span class="bx-def-font-grayed">'.$sText.'</span>: '.bx_process_output($sValue);
    }

    public function serviceResponseMenuCustomItem($oAlert) {
        $aMenuItem = $oAlert->aExtras['item'];
        $sMenuName = $oAlert->aExtras['menu'];
        $sModule = $oAlert->aExtras['module'];
        $iContentId = $oAlert->aExtras['content_id'];
        $oMenuObject = $oAlert->aExtras['menu_object'];
        $aContentData = $oAlert->aExtras['content_data'];
        $sRes = false;

        static $aItemsToProcess;
        if (is_null($aItemsToProcess)) $aItemsToProcess = $this->_oDb->getActiveEntries();

        if (isset($aItemsToProcess[$aMenuItem['title']])) {
            $sHandlerCode = $aItemsToProcess[$aMenuItem['title']];
            $sRes = $this->callHandler($sHandlerCode,$aMenuItem, $sMenuName, $sModule, $iContentId, $oMenuObject, $aContentData);
        }

        if ($sRes) $oAlert->aExtras['res'] = $sRes;
    }

}

/** @} */