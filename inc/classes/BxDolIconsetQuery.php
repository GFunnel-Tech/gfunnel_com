<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

/**
 * Database queries for iconset objects.
 * @see BxDolIconset
 */
class BxDolIconsetQuery extends BxDolFactoryObjectQuery
{
    public function __construct($aObject)
    {
        parent::__construct($aObject);
    }

    static public function getObject ($sObject)
    {
        return parent::getObjectFromTable($sObject, 'sys_objects_iconset');
    }

    static public function getObjects ()
    {
        return parent::getObjectsFromTable('sys_objects_iconset');
    }
}

/** @} */
