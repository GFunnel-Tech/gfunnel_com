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

class GmDiscordAlerts extends BxBaseModConnectAlerts
{
    function __construct()
    {
        parent::__construct();
        $this -> oModule = BxDolModule::getInstance('gm_discord');
    }
}

/** @} */
