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

class MsFansonlyGridCommon  extends MsFansonlyGridConnections //BxDolGridConnections
{

    public function __construct ($aOptions, $oTemplate = false)
    {
        parent::__construct ($aOptions, $oTemplate);

        $join = '';
        $iProfileId = bx_get_logged_profile_id();

        $where = ' WHERE `mv`.`profile_id` = '. $iProfileId;

        $this->_aQueryAppendExclude[] = 'join_connections';
        $this->addMarkers(array(
            'join_connections' => "LEFT JOIN `sys_profiles` AS `tp` ON `tp`.`id` = `mv`.`profile_id` LEFT JOIN `sys_accounts` AS `ta` ON `tp`.`account_id`=`ta`.`id` LEFT JOIN `sys_modules` AS `tm` ON `tp`.`type`=`tm`.`name` ". $join . $where,
        ));
    }
    protected function _getDataSqCounter($sQuery, $sFilter)
    {
        $oDb = BxDolDb::getInstance();
        return $oDb->getOne("SELECT COUNT(*) " . substr($sQuery, strpos($sQuery, " FROM" )));
    }
    protected function _isVisibleGrid($a){
        if (!empty(bx_get_logged_profile_id()))
            return true;
            
        return false;
    }
    
}

/** @} */
