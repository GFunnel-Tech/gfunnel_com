<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Goal Goal
 * @ingroup     ModzzzModules
 *
 * @{
 */

require_once(BX_DIRECTORY_PATH_INC . "design.inc.php");

class MzGoalCron extends BxDolCron
{
    protected $_sModule;
    protected $_oModule;

    public function __construct()
    {
        $this->_sModule = 'mz_goal';
        $this->_oModule = BxDolModule::getInstance($this->_sModule);

        parent::__construct();
    }

    function processing()
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

		$this->_oModule->_oDb->processGoals(); 
		$this->_oModule->_oDb->processTasks();
		$this->_oModule->_oDb->processReminders();  
    }
}

/** @} */
