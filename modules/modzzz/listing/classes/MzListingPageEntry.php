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

/**
 * Entry create/edit pages
 */
class MzListingPageEntry extends BxBaseModTextPageEntry
{
    public function __construct($aObject, $oTemplate = false)
    {
        $this->MODULE = 'mz_listing';

        parent::__construct($aObject, $oTemplate);
    }
 
    public function getCode ()
    {
        $sResult = parent::getCode();
        if(!empty($sResult))
            $sResult .= $this->_oModule->_oTemplate->getJsCode('entry');

        $this->_oModule->_oTemplate->addCss(array('entry.css'));
        $this->_oModule->_oTemplate->addJs(array('entry.js'));
        return $sResult;
    }
 
    protected function _setSubmenu($aParams)
    {
        parent::_setSubmenu(array_merge($aParams, array(
            'title' => '',
            'icon' => ''
        )));
    }
}

/** @} */
