<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaBaseView UNA Base Representation Classes
 * @{
 */

/**
 * WonderPush integration.
 * @see BxDolPush
 */
class BxBasePushWonderPush extends BxDolPushWonderPush
{
    protected function _getCodePageHeader()
    {
        if($this->_bCodeAdded || !$this->_sAppId)
            return '';

        $iProfileId = bx_get_logged_profile_id();
        if(empty($iProfileId))
            return '';

        $aTags = self::getTags($iProfileId);
        if(!$aTags)
            return '';

        $this->_bCodeAdded = true;
        return $this->_oTemplate->parseHtmlByName('wonderpush.html', [
            'params' => json_encode([
                'webKey' => $this->_sWebKey,
                'userId' => $aTags['user_hash'],
                'applicationName' => getParam('site_title'),                
            ]),
            'tags' => json_encode($aTags),
        ]);
    }
}

/** @} */
