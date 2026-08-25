<?php
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

require_once("./../inc/header.inc.php");

$sModule = '';
$aRequest = $aModule = [];
if(($sRequest = $_GET['r'] ?? false) !== false) {
    $aRequest = explode('/', $sRequest);

    if(($sModule = bx_process_input(array_shift($aRequest)))) {
        bx_import('BxDolModuleQuery');
        $aModule = BxDolModuleQuery::getInstance()->getModuleByUri($sModule);
    }
}

if(empty($aModule)) {
    require_once(BX_DIRECTORY_PATH_INC . "design.inc.php");
    BxDolRequest::moduleNotFound($sModule);
}

$GLOBALS['aRequest'] = $aRequest;
$GLOBALS['aModule'] = $aModule;
include(BX_DIRECTORY_PATH_MODULES . $GLOBALS['aModule']['path'] . 'request.php');

/** @} */
