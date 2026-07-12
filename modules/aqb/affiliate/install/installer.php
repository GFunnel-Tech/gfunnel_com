<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    DolphinMigration  Dolphin Migration
 * @ingroup     UnaModules
 *
 * @{
 */

class AqbAffiliateInstaller extends BxBaseModTemplateInstaller
{
    function __construct($aConfig)
    {
        parent::__construct($aConfig);
    }

    protected function getMarkersForDb()
    {
        return array(
            'from' =>  '{domain}',
            'to'   => BX_DOL_URL_ROOT
        );
    }
}

/** @} */
