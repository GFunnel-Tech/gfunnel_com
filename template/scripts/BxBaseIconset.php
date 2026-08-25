<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaBaseView UNA Base Representation Classes
 * @{
 */

/**
 * Basic iconset representation.
 * @see BxDolIconset
 */
class BxBaseIconset extends BxDolIconset
{
    protected $_oTemplate;

    public function __construct ($aObject, $oTemplate)
    {
        parent::__construct ($aObject);

        if ($oTemplate)
            $this->_oTemplate = $oTemplate;
        else
            $this->_oTemplate = BxDolTemplate::getInstance();
    }

    public function getPreloaderCss()
    {
        return false;
    }

    public function getPreloaderJs()
    {
        return false;
    }

    public function getIcon($sIcon)
    {
        return $sIcon;
    }

    public function getCode()
    {
        return false;
    }
}

/** @} */
