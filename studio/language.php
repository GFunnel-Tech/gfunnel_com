<?php
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaEndAdmin UNA Studio End Admin Pages
 * @ingroup     UnaStudio
 * @{
 */

require_once('./../inc/header.inc.php');
require_once(BX_DIRECTORY_PATH_INC . 'design.inc.php');

bx_import('BxDolLanguages');

bx_require_authentication(true);

$sName = bx_get('name');
if($sName === false)
    $sName = bx_get('lang_value');
$sName = $sName !== false ? bx_process_input($sName) : '';

$sPage = bx_get('page');
$sPage = $sPage !== false ? bx_process_input($sPage) : '';

$oPage = BxTemplStudioLanguage::getObjectInstance($sName, $sPage);
BxDolStudioTemplate::getInstance()->displayPage($oPage);
/** @} */
