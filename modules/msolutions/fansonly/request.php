<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) MSolutions
 * 
 * @defgroup    Fansonly Fansonly module
 * @ingroup     MSolutionsModules
 *
 * @{
 */

require_once(BX_DIRECTORY_PATH_INC . "design.inc.php");

check_logged();

bx_import('BxDolRequest');

BxDolRequest::processAsAction($GLOBALS['aModule'], $GLOBALS['aRequest']);



/** @} */
