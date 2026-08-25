<?php defined('BX_DOL') or defined('BX_DOL_INSTALL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

/**
 * Base class for DB classes associated with object instance 
 */
class BxDolFactoryObjectQuery extends BxDolDb
{
    protected $_aObject;

    public function __construct($aObject = array())
    {
        parent::__construct();
        $this->_aObject = $aObject;
    }

    static public function getObjectFromTable($sObject, $sTable, $bUseCache = false)
    {
        $oDb = BxDolDb::getInstance();
        $aObject = false;
        if ($bUseCache) {
            $a = BxDolDb::getInstance()->fromCache($sTable, 'getAllWithKey', "SELECT * FROM `$sTable`", 'object');
            if ($a && isset($a[$sObject]))
                $aObject = $a[$sObject];
        }
        else {
            $sQuery = $oDb->prepare("SELECT * FROM `$sTable` WHERE `object` = ?", $sObject);

            $aObject = $oDb->getRow($sQuery);
            if(!$aObject || !is_array($aObject))
                $aObject = false;
        }
        return $aObject;
    }

    static public function getObjectsFromTable ($sTable)
    {
        $aObjects = BxDolDb::getInstance()->getAll("SELECT * FROM `$sTable`");
        if(empty($aObjects) || !is_array($aObjects))
            return array();

        return $aObjects;
    }
}

/** @} */
