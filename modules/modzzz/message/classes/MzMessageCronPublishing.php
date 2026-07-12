<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Profile Message Profile Message 
 * @ingroup     ModzzzModules
 *
 * @{
 */

class MzMessageCronPublishing extends BxDolCron
{
    protected $_sModule;
    protected $_oModule;

    public function __construct()
    {
        parent::__construct();

    	$this->_sModule = 'mz_message';
    	$this->_oModule = BxDolModule::getInstance($this->_sModule);
    }

    function processing()
    {
        $mixedPublishIds = $this->_oModule->_oDb->publishEntries();
        $mixedExpireIds = $this->_oModule->_oDb->expireEntries(); 
    }
}

/** @} */
