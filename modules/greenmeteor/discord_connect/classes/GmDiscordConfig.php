<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) Green Meteor - https://greenmeteor.net/
 * AGPL-3.0 License - https://opensource.org/licenses/AGPL-3.0
 *
 * @defgroup    DiscordConnect Discord Connect
 * @ingroup     GreenMeteorModule
 *
 * @{
 */

class GmDiscordConfig extends BxBaseModConnectConfig
{
    public $sApiID;
    public $sApiSecret;
    public $sApiUrl = 'https://discord.com/api/v6';
    public $sOauthUrl = 'https://discord.com/api/oauth2';

    public $sFields = 'id,firstName,lastName,profilePicture(displayImage~:playableStreams)';

    public $sPageStart;
    public $sPageHandle;

    public $sScope = 'identify email';

    function __construct($aModule)
    {
        parent::__construct($aModule);

        $this -> sApiID = getParam('gm_discord_api_key');
        $this -> sApiSecret = getParam('gm_discord_secret');

        $this -> sSessionUid = 'discord_session';
        $this -> sSessionProfile = 'discord_session_profile';

        $this -> sEmailTemplatePasswordGenerated = 'gm_discord_password_generated';
        $this -> sDefaultTitleLangKey = '_gm_discord';

        $this -> sRedirectPage = getParam('gm_discord_redirect_page');
        $this -> isAlwaysConfirmEmail = (bool)getParam('gm_discord_confirm_email'); 
        $this -> isAlwaysAutoApprove = (bool)getParam('gm_discord_approve');

        $this -> sPageStart = BX_DOL_URL_ROOT . $this -> getBaseUri() . 'start';
        $this -> sPageHandle = BX_DOL_URL_ROOT . $this -> getBaseUri() . 'handle';
    }
}

/** @} */
