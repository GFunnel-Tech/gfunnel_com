<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

/**
 * @see BxDolBackgroundJobs
 */
class BxDolBackgroundJobsQuery extends BxDolDb
{
    public function __construct()
    {
    	parent::__construct();
    }

    public function getClaimedJobs($sClasimToken)
    {
        return $this->getAll("
            SELECT * FROM `sys_background_jobs` 
            WHERE `claim_token` = :claim_token AND `status` = '" . BX_DOL_BG_JOBS_STATUS_PENDING . "' 
            ORDER BY `priority` DESC, `added` ASC", [
                'claim_token' => $sClasimToken
            ]
        );
    }

    public function claimJobs($sClasimToken, $iLimit)
    {
        return $this->query("UPDATE `sys_background_jobs` 
            SET `reserved_at`=UNIX_TIMESTAMP(), `claim_token`=:claim_token 
            WHERE claim_token = '' AND `reserved_at`='0' AND (`available_at`='0' OR `available_at`<=UNIX_TIMESTAMP()) AND `status`='" . BX_DOL_BG_JOBS_STATUS_PENDING . "' 
            ORDER BY `priority` DESC, `added` ASC 
            LIMIT :limit", [
                'claim_token' => $sClasimToken,
                'limit' => (int)$iLimit
        ]) !== false;
    }

    public function getJobs($aParams = [])
    {
        $aMethod = ['name' => 'getAll', 'params' => [0 => 'query']];

    	$sSelectClause = "*";
    	$sJoinClause = $sWhereClause = $sGroupClause = $sOrderClause = $sLimitClause = "";
        switch($aParams['sample']) {
            case 'name':
                $aMethod['name'] = 'getRow';
            	$aMethod['params'][1] = [
                    'name' => $aParams['name']
                ];

                $sWhereClause = " AND `name`=:name";
                break;

            case 'running':
                $aMethod['name'] = 'getAllWithKey';
                $aMethod['params'][1] = 'id';

                $sWhereClause = " AND `status`='" . BX_DOL_BG_JOBS_STATUS_PROCESSING . "'";
                break;

            case 'process':
                $aMethod['name'] = 'getAllWithKey';
                $aMethod['params'][1] = 'id';

                $sWhereClause = " AND `reserved_at`='0' AND (`available_at`='0' OR `available_at`<=UNIX_TIMESTAMP()) AND `status`='pending'";

                $sOrderClause = "`added` ASC";
                if(isset($aParams['with_priority']) && $aParams['with_priority'] === true)
                    $sOrderClause = "`priority` DESC, " . $sOrderClause;

                if(!empty($aParams['limit']))
                    $sLimitClause = $aParams['limit'];
                break;
        }

        $sOrderClause = !empty($sOrderClause) ? "ORDER BY " . $sOrderClause : $sOrderClause;
        $sLimitClause = !empty($sLimitClause) ? "LIMIT " . $sLimitClause : $sLimitClause;

        $aMethod['params'][0] = "SELECT
                " . $sSelectClause . "
            FROM `sys_background_jobs` " . $sJoinClause . "
            WHERE 1" . $sWhereClause . " " . $sGroupClause . " " . $sOrderClause . " " . $sLimitClause;

        return call_user_func_array([$this, $aMethod['name']], $aMethod['params']);
    }

    public function addJob($sName, $sServiceCall, $iPriority = 0)
    {
        return $this->query("INSERT INTO `sys_background_jobs` SET `name` = :name, `added`=UNIX_TIMESTAMP(), `priority` = :priority, `service_call`=:service_call ON DUPLICATE KEY UPDATE `added`=UNIX_TIMESTAMP(), `priority` = :priority, `service_call`=:service_call", [
            'name' => $sName,
            'priority' => $iPriority,
            'service_call' => $sServiceCall
        ]) !== false;
    }

    public function updateJob($sName, $aParamsSet)
    {
        if(empty($sName))
            return false;

        return $this->updateJobExt($aParamsSet, [
            'name' => $sName
        ]);
    }

    public function updateJobByIds($mixedIds, $aParamsSet)
    {
        if(empty($mixedIds))
            return false;
        
        if(!is_array($mixedIds))
            $mixedIds = [(int)$mixedIds];

        return $this->query("UPDATE `sys_background_jobs` SET " . $this->arrayToSQL($aParamsSet) . " WHERE `id` IN (" . $this->implode_escape($mixedIds) . ")");
    }

    public function updateJobExt($aParamsSet, $aParamsWhere)
    {
        if(empty($aParamsSet) || empty($aParamsWhere))
            return false;

        return $this->query("UPDATE `sys_background_jobs` SET " . $this->arrayToSQL($aParamsSet) . " WHERE " . $this->arrayToSQL($aParamsWhere, " AND "));
    }

    public function deleteJobs($aParams = [])
    {
        $aMethod = ['name' => 'query', 'params' => [0 => 'query']];

        $sWhereClause = $sLimitClause = "";
        switch($aParams['sample']) {
            case 'name':
                $aMethod['params'][1] = [
                    'name' => $aParams['name']
                ];

                $sWhereClause = " AND `name` = :name";
                break;

            case 'outdated':
                $aMethod['params'][1] = [
                    'timeout' => $aParams['timeout'],
                    'status' => $aParams['status'],
                ];

                $sWhereClause = " AND `added` + :timeout < UNIX_TIMESTAMP() AND `status` = :status";
                break;
        }

        $sLimitClause = !empty($sLimitClause) ? "LIMIT " . $sLimitClause : $sLimitClause;

        $aMethod['params'][0] = "DELETE FROM `sys_background_jobs` WHERE 1" . $sWhereClause . " " . $sLimitClause;

        return call_user_func_array([$this, $aMethod['name']], $aMethod['params']);
    }

    public function deleteJob($sName)
    {
        return $this->deleteJobs(['sample' => 'name', 'name' => $sName]) !== false;
    }
}

/** @} */
