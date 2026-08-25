<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    BaseProfile Base classes for profile modules
 * @ingroup     UnaModules
 *
 * @{
 */

require_once('BxBaseModProfilePrivacyAction.php');

class BxBaseModProfilePrivacy extends BxBaseModProfilePrivacyAction
{
    protected $MODULE;

    protected $_aPrivacyParticallyVisible;

    public function __construct($aOptions, $oTemplate = false)
    {
        $this->_sModule = $this->MODULE;

        parent::__construct($aOptions, $oTemplate);

        $this->_aPrivacyParticallyVisible = array(BX_DOL_PG_FRIENDS);
    }

    public function isPartiallyVisible ($mixedPrivacy)
    {
        return in_array($mixedPrivacy, $this->_aPrivacyParticallyVisible);
    }

    public function getPartiallyVisiblePrivacyGroups ()
    {
        return $this->_aPrivacyParticallyVisible;
    }
}

/** @} */
