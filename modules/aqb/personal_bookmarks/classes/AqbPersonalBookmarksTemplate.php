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


class AqbPersonalBookmarksTemplate extends BxBaseModGeneralTemplate {
	function __construct(&$oConfig, &$oDb) {
	    parent::__construct($oConfig, $oDb);
    }

    function getFloatingBlock($aBookmarks) {
    	$this->addJs(array('plugins_public/jquery-ui/|jquery.ui.core.min.js', 'plugins_public/jquery-ui/|jquery.ui.widget.min.js', 'plugins_public/jquery-ui/|jquery.ui.mouse.min.js', 'plugins_public/jquery-ui/|jquery.ui.sortable.min.js', 'plugins_public/jquery-ui/|jquery.ui.draggable.min.js', 'jquery.ui.touch-punch.min.js'));
		$this->addJs(array('main.js', 'jquery.hoverIntent.min.js', 'jquery.mb.flipText.js', 'mbExtruder.js'));
		$this->addCss(array('main.css', 'mbExtruder.css'));
		//$this->addJsTranslation('_aqb_personal_bookmarks_name');


		return $this->parseHtmlByName('floating_block.html', array(
			'js_lang_name' => _t('_aqb_personal_bookmarks_name'),
			'links' => $this->getLinksList($aBookmarks),
			'vpos' => $this->_oDb->getParam('aqb_personal_bookmarks_vpos'),
			'width' => $this->_oDb->getParam('aqb_personal_bookmarks_width'),
		));
	}

	function getLinksList($aBookmarks) {
		if ($aBookmarks)
		foreach($aBookmarks as $iBookmark => $aBookmark) {
			$aBookmarks[$iBookmark]['name'] = htmlspecialchars($aBookmarks[$iBookmark]['name']);
		}

		return $this->parseHtmlByName('links.html', array('bx_repeat:links' => $aBookmarks));
	}
}

/** @} */
