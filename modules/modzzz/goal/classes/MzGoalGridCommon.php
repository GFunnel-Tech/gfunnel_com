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

class MzGoalGridCommon extends BxBaseModTextGridCommon
{
    public function __construct ($aOptions, $oTemplate = false)
    {
    	$this->MODULE = 'mz_goal';

        parent::__construct ($aOptions, $oTemplate);
   
        $CNF = &$this->_oModule->_oConfig->CNF;

        $this->_aFilter1Values = array(
        	'active' => $CNF['T']['filter_item_active'],
            'hidden' => $CNF['T']['filter_item_hidden'], 
            'completed' => $CNF['T']['filter_item_completed'],
            'achieved' => $CNF['T']['filter_item_achieved'],
		); 
    }
  
    protected function _getFilterControls111()
    {
        parent::_getFilterControls();

        return  $this->_getFilterSelectOne($this->_sFilter1Name, $this->_sFilter1Value, $this->_aFilter1Values) . $this->_getSearchInput();
    }

    protected function _getCellSwitcher ($mixedValue, $sKey, $aField, $aRow)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if(isset($CNF['FIELD_STATUS']) && isset($aRow[$CNF['FIELD_STATUS']]) && !in_array($aRow[$CNF['FIELD_STATUS']], array('active', 'hidden','completed','achieved'))) {
            $sStatusKey = '_sys_status_' . $aRow[$CNF['FIELD_STATUS']];
            if(!empty($CNF['T']['txt_status_' . $aRow[$CNF['FIELD_STATUS']]]))
                $sStatusKey = $CNF['T']['txt_status_' . $aRow[$CNF['FIELD_STATUS']]];

            return parent::_getCellDefault(_t($sStatusKey), $sKey, $aField, $aRow);
        }

        return parent::_getCellSwitcher ($mixedValue, $sKey, $aField, $aRow);
    }




}

/** @} */
