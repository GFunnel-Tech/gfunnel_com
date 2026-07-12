<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    News News
 * @ingroup     ModzzzModules
 *
 * @{
 */

class MzNewsCronPublishing extends BxDolCron
{
    protected $_sModule;
    protected $_oModule;

    public function __construct()
    {
        parent::__construct();

    	$this->_sModule = 'mz_news';
    	$this->_oModule = BxDolModule::getInstance($this->_sModule);
    }

    function processing()
    {
        $mixedIds = $this->_oModule->_oDb->publishEntries();
        if($mixedIds === false)
            return;

        foreach($mixedIds as $iId)
            $this->_oModule->onPublished($iId);
    }
}

/** @} */
