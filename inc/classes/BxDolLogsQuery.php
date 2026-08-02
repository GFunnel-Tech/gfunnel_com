<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

class BxDolLogsQuery extends BxDolDb
{
    protected $_aObject;

    public function __construct($aObject = [])
    {
        parent::__construct();

        $this->_aObject = $aObject;
    }

    static public function getLogsObject($sObject)
    {
        $oDb = BxDolDb::getInstance();
        $a = $oDb->fromCache('sys_objects_logs', 'getAllWithKey', "SELECT * FROM `sys_objects_logs`", 'object');
        if ($a && isset($a[$sObject]))
            return $a[$sObject];

        return false;
    }
}

/** @} */
