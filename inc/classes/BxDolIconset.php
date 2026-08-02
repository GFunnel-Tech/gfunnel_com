<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

class BxDolIconset extends BxDolFactory implements iBxDolFactoryObject
{
    protected $_sObject;
    protected $_aObject;

    protected function __construct($aObject)
    {
        parent::__construct();

        $this->_sObject = $aObject['object'];
        $this->_aObject = $aObject;
    }

    /**
     * Get player object instance by object name
     * @param $sObject object name
     * @return object instance or false on error
     */
    static public function getObjectInstance($sObject = false, $oTemplate = false)
    {
        if(!$sObject)
            $sObject = getParam('sys_iconset_default');

        if(isset($GLOBALS['bxDolClasses']['BxDolIconset!'.$sObject]))
            return $GLOBALS['bxDolClasses']['BxDolIconset!'.$sObject];

        $aObject = BxDolIconsetQuery::getObject($sObject);
        if(!$aObject || !is_array($aObject))
            return false;

        if(empty($aObject['override_class_name']))
            return false;

        $sClass = $aObject['override_class_name'];
        if(!empty($aObject['override_class_file']))
            require_once(BX_DIRECTORY_PATH_ROOT . $aObject['override_class_file']);
        else
            bx_import($sClass);

        $o = new $sClass($aObject, $oTemplate);

        return ($GLOBALS['bxDolClasses']['BxDolIconset!'.$sObject] = $o);
    }

    /**
     * Get object name
     */
    public function getObjectName ()
    {
        return $this->_sObject;
    }
}

/** @} */
