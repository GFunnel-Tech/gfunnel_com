<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

/**
 * @see BxDolPayments
 */
class BxDolPaymentsQuery extends BxDolDb
{
	public function __construct()
    {
    	parent::__construct();
    }

    public function getObjects()
    {
    	$sQuery = "SELECT * FROM `sys_objects_payments` WHERE 1";
        $aObjects = $this->fromCache('sys_objects_payments', 'getAll', $sQuery);
        if(empty($aObjects) || !is_array($aObjects))
            return array();

        return $aObjects;
    }
}

/** @} */
