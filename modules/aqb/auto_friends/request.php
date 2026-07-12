<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Auto Friends Friends Finder
 * @ingroup     UNAModules
 *
 * @{
 */

require_once(BX_DIRECTORY_PATH_INC . "design.inc.php");

check_logged();

BxBaseModTextRequest::processAsAction($GLOBALS['aModule'], $GLOBALS['aRequest']);

/** @} */
