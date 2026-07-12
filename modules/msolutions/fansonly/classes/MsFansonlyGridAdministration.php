<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) MSolutions
 * 
 * @defgroup    Fansonly Fansonly module
 * @ingroup     MSolutionsModules
 *
 * @{
 */ 

require_once('MsFansonlyGridConnections.php');

class MsFansonlyGridAdministration  extends MsFansonlyGridConnections //BxDolGridConnections
{
    public function __construct ($aOptions, $oTemplate = false)
    {
        parent::__construct ($aOptions, $oTemplate);

        $join = '';
        $where = '';

        $this->addMarkers(array(
            'join_connections' => "LEFT JOIN `sys_profiles` AS `tp` ON `tp`.`id` = `mv`.`profile_id` LEFT JOIN `sys_accounts` AS `ta` ON `tp`.`account_id`=`ta`.`id` LEFT JOIN `sys_modules` AS `tm` ON `tp`.`type`=`tm`.`name` ". $join . $where,
            
        )); 
    }
    
}

/** @} */
