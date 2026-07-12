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

require_once(BX_DIRECTORY_PATH_INC . 'profiles.inc.php');

check_logged();

BxDolRequest::processAsAction($GLOBALS['aModule'], $GLOBALS['aRequest']);

/** @} */
