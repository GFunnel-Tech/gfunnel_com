<?php defined('BX_DOL') or die('hack attempt');
/**
 *     copyright            : (C) 2018 AQB Soft
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
 * @defgroup    Personal Bookmarks Personal Bookmarks
 * @ingroup     UnaModules
 *
 * @{
 */


class AqbPersonalBookmarksModule extends BxBaseModGeneralModule {
    function __construct(&$aModule) {
        parent::__construct($aModule);
    }

    function serviceResponseProfileDelete($oAlert) {
        if ('account' != $oAlert->sUnit || 'delete' != $oAlert->sAction) return;

        return $this->_oDb->deleteAccountBookmarks($oAlert->iObject);
    }

    function serviceGetBookmarksFloatingBlock() {
        if (!isLogged()) return;

        $iProfileID = bx_get_logged_profile_id();
        $aCheck = checkActionModule($iProfileID, 'use', $this->_aModule['name'], false);
        if($aCheck[CHECK_ACTION_RESULT] != CHECK_ACTION_RESULT_ALLOWED) return;

        $aBookmarks = $this->_oDb->getAccountBookmarks(getLoggedId());
        return $this->_oTemplate->getFloatingBlock($aBookmarks);
    }

    function actionAddBookmark() {
        if (!isLogged()) return;

        $sUrl = trim($_REQUEST['url']);
        $sName = trim($_REQUEST['name']);

        if (!$sUrl || !$sName) return;

        if (preg_match('#^https?://#i', $sUrl) && strpos($sUrl, BX_DOL_URL_ROOT) !== 0) {
            // External absolute URL - store it unchanged so it isn't reinterpreted as an internal path on display.
        }
        else {
            // Internal page - store as a site-relative path (original behaviour).
            $sUrl = str_replace(BX_DOL_URL_ROOT, '', $sUrl);
        }

        $this->_oDb->addAccountBookmark(getLoggedId(), $sUrl, $sName);

        $aBookmarks = $this->_oDb->getAccountBookmarks(getLoggedId());
        echo $this->_oTemplate->getLinksList($aBookmarks);
    }

    function actionDeleteBookmark() {
        if (!isLogged()) return;

        $iId = intval($_REQUEST['id']);

        if (!$iId) return;

        $this->_oDb->deleteAccountBookmark(getLoggedId(), $iId);

        $aBookmarks = $this->_oDb->getAccountBookmarks(getLoggedId());
        echo $this->_oTemplate->getLinksList($aBookmarks);
    }

    function actionUpdateOrder() {
        if (!isLogged() || !$_POST['order']) return;
        $this->_oDb->updateOrder($_POST['order'], getLoggedId());
    }
}

/** @} */
