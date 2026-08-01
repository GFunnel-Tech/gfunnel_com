<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

define('BX_DOL_BG_JOBS_STATUS_PENDING', 'pending');
define('BX_DOL_BG_JOBS_STATUS_PROCESSING', 'processing');
define('BX_DOL_BG_JOBS_STATUS_ERROR', 'error');

class BxDolBackgroundJobs  extends BxDolFactory implements iBxDolSingleton
{
    protected $_oQuery;

    protected $_sObjectLog;

    protected $_sParamWorkersLimit;

    protected $_iAttemptsMax;

    protected function __construct()
    {
        if (isset($GLOBALS['bxDolClasses'][get_class($this)]))
            trigger_error ('Multiple instances are not allowed for the class: ' . get_class($this), E_USER_ERROR);

        parent::__construct();

        $this->_sObjectLog = 'sys_background_jobs';

        $this->_sParamWorkersLimit = 'sys_bg_jobs_workers_limit';

        $this->_iAttemptsMax = 3;

        $this->_oQuery = new BxDolBackgroundJobsQuery();
    }

    /**
     * Get singleton instance of the class
     */
    public static function getInstance()
    {
        if(!isset($GLOBALS['bxDolClasses'][__CLASS__]))
            $GLOBALS['bxDolClasses'][__CLASS__] = new BxDolBackgroundJobs();

        return $GLOBALS['bxDolClasses'][__CLASS__];
    }

    public static function pruning()
    {
        return BxDolBackgroundJobs::getInstance()->prune(86400 * (int)getParam('sys_bg_jobs_cleanup_timeout'));
    }

    /**
     * Prevent cloning the instance
     */
    public function __clone()
    {
        if (isset($GLOBALS['bxDolClasses'][get_class($this)]))
            trigger_error('Clone is not allowed for the class: ' . get_class($this), E_USER_ERROR);
    }

    public function add($sName, $mixedServiceCall, $iPriority = 0)
    {
        if(is_array($mixedServiceCall))
            $mixedServiceCall = call_user_func_array(['BxDolService', 'getSerializedService'], $mixedServiceCall);

        if(!$this->_oQuery->addJob($sName, $mixedServiceCall, $iPriority))
            return false;

        bx_log($this->_sObjectLog, "Added: " . $sName, BX_LOG_INFO);

        return true;
    }

    public function delete($sName)
    {
        if(!$this->_oQuery->deleteJob($sName))
            return false;

        bx_log($this->_sObjectLog, "Deleted: " . $sName, BX_LOG_INFO);

        return true;
    }

    public function exists($sName)
    {
        $aJob = $this->_oQuery->getJobs([
            'sample' => 'name', 
            'name' => $sName
        ]);

        return !empty($aJob) && is_array($aJob);
    }

    public function process($mixedJob)
    {
        if(!empty($mixedJob) && !is_array($mixedJob))
            $mixedJob = $this->_oQuery->getJobs(['sample' => 'name', 'name' => $mixedJob]);

        if(empty($mixedJob) || !is_array($mixedJob))
            return false;

        if(empty($mixedJob['service_call']) || !BxDolService::isSerializedService($mixedJob['service_call']))
            return false;

        if($mixedJob['status'] != BX_DOL_BG_JOBS_STATUS_PROCESSING)
            $this->_oQuery->updateJob($mixedJob['name'], [
                'status' => BX_DOL_BG_JOBS_STATUS_PROCESSING
            ]);

        $fStart = microtime(true);
        $sError = '';
        $mixedResult = '';
        try {
            $mixedResult = BxDolService::callSerialized($mixedJob['service_call']);
        }
        catch (Throwable $e) {
            $sError = $e->getMessage();
        }
        $fEnd = microtime(true);

        if ($sError) {
            $iAvailableAt = 0;
            $sStatus = BX_DOL_BG_JOBS_STATUS_ERROR;

            $iAttempts = (int)$mixedJob['attempts'] - 1;
            if($iAttempts) {
                $iAvailableAt = time() + 60 * pow(3, ($this->_iAttemptsMax - $iAttempts));
                $sStatus = BX_DOL_BG_JOBS_STATUS_PENDING;
            }

            $this->_oQuery->updateJob($mixedJob['name'], [
                'claim_token' => '',
                'reserved_at' => 0,
                'available_at' => $iAvailableAt,
                'attempts' => $iAttempts,
                'error' => $sError . ' / result: [' . $mixedResult . ']',
                'status' => $sStatus
            ]);
        }
        else
            $this->_oQuery->deleteJob($mixedJob['name']);

        bx_log($this->_sObjectLog, "Processed: " . $mixedJob['name'] . " / timing: " . ($fEnd - $fStart) . " / memory: " . memory_get_usage(), BX_LOG_INFO);

        return true;
    }

    public function processAll($iLimit = 0)
    {
        if($this->_isWorkersLimitReached())
            return false;
     
        $sClaimToken = bin2hex(random_bytes(16));
        $this->_oQuery->claimJobs($sClaimToken, $iLimit ?: (getParam('sys_bg_jobs_process_per_run') ?: 5));

        $aJobs = $this->_oQuery->getClaimedJobs($sClaimToken);
        if(!$aJobs || !is_array($aJobs))
            return true;

        $this->_oQuery->updateJobByIds(array_keys($aJobs), [
            'reserved_at' => time()
        ]);

        $iProcessed = 0;
        foreach($aJobs as $aJob)
            if($this->process($aJob))
                $iProcessed += 1;

        bx_log($this->_sObjectLog, "Processed: all (" . $iProcessed . " from " . count($aJobs) . ")", BX_LOG_INFO);

        return true;
    }

    public function prune($iTimeout, $sStatus = BX_DOL_BG_JOBS_STATUS_ERROR)
    {
        return (int)$this->_oQuery->deleteJobs([
            'sample' => 'outdated', 
            'timeout' => $iTimeout, 
            'status' => $sStatus
        ]);
    }

    protected function _isWorkersLimitReached()
    {
        $aJobs = $this->_oQuery->getJobs(['sample' => 'running']);
        $iWorkers = $aJobs ? count($aJobs) : 0;        
        if ($iWorkers >= (int)getParam($this->_sParamWorkersLimit))
            return true;

        return false;
    }
}

/** @} */
