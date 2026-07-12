<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Auto Friends Auto Friends
 * @ingroup     UNAModules
 *
 * @{
 */

class AqbAfDb extends BxBaseModGeneralDb
{
	public function __construct(&$oConfig)
    {
        parent::__construct($oConfig);
    }

    public function getContentInfoById ($iContentId)
    {
        $sQuery = $this->prepare ("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_ENTRIES'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_ID'] . "` = ?", $iContentId);
        return $this->getRow($sQuery);
    }

    public function getEntries($aParams = array())
    {
        $CNF = &$this->_oConfig->CNF;

        $aMethod = array('name' => 'getAll', 'params' => array(0 => 'query'));

        $sSelectClause = "`te`.*"; 
        $sWhereClause = $sOrderClause = $sLimitClause = '';

        switch($aParams['type']) {
            case 'profile_id':
                $aMethod['name'] = 'getRow';
                $aMethod['params'][1] = array(
                    'profile_id' => $aParams['profile_id']
                );

                $sWhereClause = "AND `te`.`profile_id`=:profile_id";
                break;

            case 'all_for_add':
                $sOrderClause = "`te`.`added` ASC";
                break;

            case 'all_for_view':
                $sOrderClause = "`te`.`added` DESC";
                $sLimitClause = isset($aParams['start']) && !empty($aParams['per_page']) ? (int)$aParams['start'] . ", " . (int)$aParams['per_page'] : '';
                break;
            }

        if(!empty($sOrderClause))
            $sOrderClause = "ORDER BY " . $sOrderClause;

        if(!empty($sLimitClause))
            $sLimitClause = "LIMIT " . $sLimitClause;

        $aMethod['params'][0] = "SELECT " . $sSelectClause . " FROM `" . $this->_oConfig->CNF['TABLE_ENTRIES'] . "` AS `te` WHERE 1 " . $sWhereClause . " " . $sOrderClause . " " . $sLimitClause;

        return call_user_func_array(array($this, $aMethod['name']), $aMethod['params']);
    }

    public function getProfiles()
    {
        $CNF = &$this->_oConfig->CNF;

        $sWhereAddon = "";

        $aSupported = array_keys($this->_oConfig->getSupportedModules());
        if(!empty($aSupported) && is_array($aSupported))
            $sWhereAddon .= " AND `type` IN (" . $this->implode_escape($aSupported) . ")";

        $aDisabled = $this->_oConfig->getDisabledFor();
        if(!empty($aDisabled) && is_array($aDisabled))
            $sWhereAddon .= " AND `type` NOT IN (" . $this->implode_escape($aDisabled) . ")";

        return $this->getAll("SELECT *, `id` AS `" . $CNF['FIELD_PROFILE_ID'] . "` FROM `sys_profiles` WHERE `type`<>'system' AND `status`='active'" . $sWhereAddon . " ORDER BY `ID` ASC");
    } 
}

/** @} */
