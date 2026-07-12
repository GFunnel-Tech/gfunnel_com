<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    JamiiTrade JamiiTrade
 * @ingroup     UnaModules
 *
 * @{
 */

class AqbSEOFConfig extends BxBaseModGeneralConfig
{
    function __construct($aModule)
    {
        parent::__construct($aModule);

        $this->CNF = [
            'SYMBOLS' => $aModule['db_prefix'] . 'symbols_table',

            // SEO page
            'FIELD_ID' => 'id',
            'FIELD_MODULE' => 'module',
            'FIELD_URI' => 'uri',
            'FIELD_COUNT' => 'count',
            'FIELD_LANG' => 'lang',
            'FIELD_SYMBOLS' => 'symbols',

            'OBJECT_GRID_URLS' => "{$this->_sName}_urls",
            'OBJECT_GRID_LINKS' => "{$this->_sName}_links",
            'AUTO-CONVERSION' => getParam($aModule['db_prefix'] . 'enable_autoconversation') == 'on',
            'SELECTED-LANGS' => explode(',', getParam($aModule['db_prefix'] . 'select_languages')),

            'FIELD_STATUS' => '',
            'FIELD_STATUS_ADMIN' => '',
            'NON-ASCII' => "NOT HEX(`uri`) REGEXP '^([0-7][0-9A-F])*$'",
            'T' => array(
                'filter_item_active' => '',
                'filter_item_hidden' => '',
                'filter_item_pending' => '',
                'filter_item_select_one_filter1' => ''
            ),
            'URI_EDIT_ENTRY' => ''
        ];

        $this->_aJsClasses = [
            'manage_tools' => "{$this->_sClassPrefix}ManageTools",
            'utils' => "{$this->_sClassPrefix}Utils"
        ];

        $this->_aJsObjects = [
            'manage_tools' => "o{$this->_sClassPrefix}ManageTools",
            'utils' => "o{$this->_sClassPrefix}Utils",
        ];

        $this->_aGridObjects = [
            'urls' => $this->CNF['OBJECT_GRID_URLS'],
            'links' => $this->CNF['OBJECT_GRID_LINKS']
        ];
    }


}

/** @} */
