<?php defined('BX_DOL') or die('hack attempt');

class BxPeoplePrivacy extends BxBaseModFilesPrivacy
{
    public function __construct($aOptions, $oTemplate = false)
    {
		$this->MODULE = 'bx_people';
        parent::__construct($aOptions, $oTemplate);
    }
}

/** @} */
