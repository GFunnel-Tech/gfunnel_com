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

class GmDiscordStudioPage extends BxTemplStudioModule
{
    function __construct($sModule, $mixedPageName, $sPage = '')
    {
        parent::__construct($sModule, $mixedPageName, $sPage);

        $this->aMenuItems = [
            'settings' => ['name' => 'settings', 'icon' => 'cogs', 'title' => '_adm_lmi_cpt_settings'],
            'help' => ['name' => 'help', 'icon' => 'question', 'title' => '_sys_connect_information'],
        ];
    }
    
    function getHelp ()
    {
        $oModule = BxDolModule::getInstance('gm_discord'); 
        return _t('_gm_discord_information_block', BX_DOL_URL_ROOT . $oModule -> _oConfig -> getBaseUri() . 'handle');
    }
}

/** @} */
