<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaBaseView UNA Base Representation Classes
 * @{
 */

/**
 * Basic Font Awesome iconset representation.
 * @see BxDolIconset
 */
class BxBaseIconsetFontAwesome extends BxBaseIconset
{
    public function __construct ($aObject, $oTemplate)
    {
        parent::__construct ($aObject, $oTemplate);
    }

    public function getPreloaderCss()
    {
        return 'icons.css';
    }
}

/** @} */
