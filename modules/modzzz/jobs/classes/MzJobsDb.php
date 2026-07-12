<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Jobs Jobs
 * @ingroup     ModzzzModules
 *
 * @{
 */

/*
 * Module database queries
 */
class MzJobsDb extends BxBaseModTextDb
{
    function __construct(&$oConfig)
    {
        parent::__construct($oConfig);
    }
 
	function updateJobType($iEntryId, $sType='seeking')
	{ 
        $CNF = &$this->_oConfig->CNF;

        $this->query("UPDATE `" . $CNF['TABLE_ENTRIES'] . "` SET `job_type`=:type WHERE `id`=:entry_id", array(
            'entry_id' => $iEntryId,
            'type' => $sType
        )); 
	}

    function getEntriesBy($aParams = array())
    {
        $CNF = &$this->_oConfig->CNF;

    	$aMethod = array('name' => 'getAll', 'params' => array(0 => 'query', 1 => array()));
        $sSelectClause = $sJoinClause = $sWhereClause = $sOrderClause = $sLimitClause = "";

        $sSelectClause = "`" . $CNF['TABLE_ENTRIES'] . "`.*";

        switch($aParams['type']) {
            case 'expired':
                $aMethod['params'][1]['days'] = 86400 * (int)$aParams['days'];

                $sWhereClause .= " AND UNIX_TIMESTAMP() - `" . $CNF['TABLE_ENTRIES'] . "`.`" . $CNF['FIELD_CHANGED'] . "` > :days";
                break;

            default:
                return parent::getEntriesBy($aParams);
        }

        if(!empty($sOrderClause))
            $sOrderClause = 'ORDER BY ' . $sOrderClause;

        if(!empty($sLimitClause))
            $sLimitClause = 'LIMIT ' . $sLimitClause;

        $aMethod['params'][0] = "SELECT " . $sSelectClause . " FROM `" . $CNF['TABLE_ENTRIES'] . "` " . $sJoinClause . " WHERE 1 " . $sWhereClause . " " . $sOrderClause . " " . $sLimitClause;
            return call_user_func_array(array($this, $aMethod['name']), $aMethod['params']);
    }

    public function insertCategoryType($aParamsSet)
    {
        $CNF = &$this->_oConfig->CNF;

        if(empty($aParamsSet))
            return 0;

        if((int)$this->query("INSERT INTO `" . $CNF['TABLE_CATEGORIES_TYPES'] . "` SET " . $this->arrayToSQL($aParamsSet)) <= 0)
            return 0;

        return (int)$this->lastId();
    }

    public function deleteCategoryType($aParamsWhere)
    {
        $CNF = &$this->_oConfig->CNF;

        if(empty($aParamsWhere))
            return false;

        return (int)$this->query("DELETE FROM `" . $CNF['TABLE_CATEGORIES_TYPES'] . "` WHERE " . $this->arrayToSQL($aParamsWhere, ' AND ')) > 0;
    }

    public function getCategoryTypes($aParams = array())
    {
        $CNF = &$this->_oConfig->CNF;

        $aMethod = array('name' => 'getAll', 'params' => array(0 => 'query'));

        $sSelectClause = "`tct`.*";
        $sWhereClause = $sOrderClause = "";
        
        switch($aParams['type']) {
            case 'id':
                $aMethod['name'] = 'getRow';
                $aMethod['params'][1] = array(
                    'id' => $aParams['id']
                );

                $sWhereClause = " AND `tct`.`id`=:id";
                break;
            
            case 'all':
                break;
        }

        if(!empty($sOrderClause))
            $sOrderClause = "ORDER BY " . $sOrderClause;

        $aMethod['params'][0] = "SELECT " . $sSelectClause . " 
            FROM `" . $CNF['TABLE_CATEGORIES_TYPES'] . "` AS `tct`
            WHERE 1" . $sWhereClause . " " . $sOrderClause;

        return call_user_func_array(array($this, $aMethod['name']), $aMethod['params']);
    }

    public function updateCategory($aParamsSet, $aParamsWhere)
    {
        $CNF = &$this->_oConfig->CNF;

        if(empty($aParamsSet) || empty($aParamsWhere))
            return false;

        return $this->query("UPDATE `" . $CNF['TABLE_CATEGORIES'] . "` SET " . $this->arrayToSQL($aParamsSet) . " WHERE " . $this->arrayToSQL($aParamsWhere, ' AND ')) !== false;
    }

    public function getCategories($aParams = array())
    {
        $CNF = &$this->_oConfig->CNF;

        $aMethod = array('name' => 'getAll', 'params' => array(0 => 'query'));

        $sSelectClause = "`tc`.*";
        $sJoinClause = $sWhereClause = $sGroupClause = "";
        $sOrderClause = "`tc`.`order` ASC";

        switch($aParams['type']) {
            case 'id':
                $aMethod['name'] = 'getRow';
                $aMethod['params'][1] = array(
                    'id' => $aParams['id']
                );

                $sWhereClause = " AND `tc`.`id`=:id";
                break;

            case 'id_full':
                $aMethod['name'] = 'getRow';
                $aMethod['params'][1] = array(
                    'id' => $aParams['id']
                );

                $sSelectClause .= ", `tct`.`name` AS `type_name`, `tct`.`display_add` AS `type_display_add`, `tct`.`display_edit` AS `type_display_edit`, `tct`.`display_view` AS `type_display_view`";
                $sJoinClause = " LEFT JOIN `" . $CNF['TABLE_CATEGORIES_TYPES'] . "` AS `tct` ON `tc`.`type`=`tct`.`id`";
                $sWhereClause = " AND `tc`.`id`=:id";
                break;

            case 'parent_id':
                $aMethod['params'][1] = array(
                    'parent_id' => $aParams['parent_id']
                );
 
                $sJoinClause = " LEFT JOIN `" . $CNF['TABLE_CATEGORIES_TYPES'] . "` AS `tct` ON `tc`.`type`=`tct`.`id`";
                $sWhereClause = " AND `tc`.`parent_id`=:parent_id";
                if(isset($aParams['with_content']) && $aParams['with_content'] === true)
                    $sWhereClause .= " AND `tc`.`items`>0";

                if(isset($aParams['category_type']) && $aParams['category_type']) 
					$sWhereClause .= " AND `tct`.`name` = '". $aParams['category_type'] . "'"; 
                break;

            case 'parent_id_count':
                $aMethod['name'] = 'getOne';
                $aMethod['params'][1] = array(
                    'parent_id' => $aParams['parent_id']
                );

                $sSelectClause = "COUNT(`tc`.`id`)";
                $sWhereClause = " AND `tc`.`parent_id`=:parent_id";
                break;
        
            case 'parent_id_order':
                $aMethod['name'] = 'getOne';
                $aMethod['params'][1] = array(
                    'parent_id' => $aParams['parent_id']
                );

                $sSelectClause = "MAX(`tc`.`order`)";
                $sWhereClause = " AND `tc`.`parent_id`=:parent_id";
                break;
/*
            case 'collect_stats':
                $sSelectClause = "`tc`.`id`, COUNT(`te`.`id`) AS `count`";
                $sJoinClause = $this->prepareAsString(" LEFT JOIN `" . $CNF['TABLE_ENTRIES'] . "` AS `te` ON `tc`.`id`=`te`.`category` AND `te`.`status`='active' AND `te`.`status_admin`='active' AND (`te`.`allow_view_to`=? OR `te`.`allow_view_to`<0)", BX_DOL_PG_ALL);
                $sGroupClause = "`tc`.`id`";
                break;
*/
            case 'collect_stats':
                $aMethod['params'][1] = [];

                $sCountClause = "SELECT COUNT(`te`.`id`) FROM `" . $CNF['TABLE_ENTRIES'] . "` AS `te` INNER JOIN `sys_profiles` AS `p` ON (`p`.`id` = `te`.`" . $CNF['FIELD_AUTHOR'] . "` AND `p`.`status` = 'active') WHERE `tc`.`id`=`te`.`" . $CNF['FIELD_CATEGORY'] . "` AND `te`.`" . $CNF['FIELD_STATUS'] . "`='active' AND `te`.`" . $CNF['FIELD_STATUS_ADMIN'] . "`='active' AND (`te`.`" . $CNF['FIELD_ALLOW_VIEW_TO'] . "`=" . BX_DOL_PG_ALL . " OR `te`.`" . $CNF['FIELD_ALLOW_VIEW_TO'] . "`<0)";
                $sSelectClause = "`tc`.`id`, (" . $sCountClause . ") AS `count`";

                if(isset($aParams['category_id'])) {
                    $aMethod['params'][1]['category_id'] = $aParams['category_id'];

                    $sWhereClause = " AND `tc`.`id`=:category_id";
                }
                break; 
        }

        if(!empty($sGroupClause))
            $sGroupClause = "GROUP BY " . $sGroupClause;

        if(!empty($sOrderClause))
            $sOrderClause = "ORDER BY " . $sOrderClause;
  
        $aMethod['params'][0] = "SELECT " . $sSelectClause . " 
            FROM `" . $CNF['TABLE_CATEGORIES'] . "` AS `tc`" . $sJoinClause . " 
            WHERE 1" . $sWhereClause . " " . $sGroupClause . " " . $sOrderClause;

        return call_user_func_array(array($this, $aMethod['name']), $aMethod['params']);
    }

    public function getDisplays($sDisplayPrefix = '', $mixedDisplayType = '')
    {
        $sWhereClause = "";
        $aBindings = array(
            'display_prefix' => '%' . (!empty($sDisplayPrefix) ? $sDisplayPrefix : $this->_oConfig->getName()) . '%'
        );

        if(!empty($mixedDisplayType)) {
            if(is_string($mixedDisplayType)) {
                $sWhereClause = " AND `display_name` LIKE :display_type";

                $aBindings['display_type'] = '%' . $mixedDisplayType . '%';
            }
            else if(is_array($mixedDisplayType)) {
                $aWhereClauseOr = array();
                foreach($mixedDisplayType as $iIndex => $sValue) {
                    $aWhereClauseOr[] = "`display_name` LIKE :display_type_" . $iIndex;

                    $aBindings['display_type_' . $iIndex] = '%' . $sValue . '%';
                }

                $sWhereClause = " AND (" . implode(" OR ", $aWhereClauseOr) . ")";
            }
        }

        return $this->getAll("SELECT * FROM `sys_form_displays` WHERE `display_name` LIKE :display_prefix" . $sWhereClause, $aBindings);
    }

    public function cloneDisplay($sDisplayName, $sNewDisplayName, $sNewDisplayTitle)
    {
        $aDisplay = $this->getRow("SELECT * FROM `sys_form_displays` WHERE `display_name`=:display_name", array('display_name' => $sDisplayName));
        if(empty($aDisplay) || !is_array($aDisplay))
            return false;
        
        unset($aDisplay['id']);
        $aDisplay['display_name'] = $sNewDisplayName;
        $aDisplay['title'] = $sNewDisplayTitle;

        if((int)$this->query("INSERT INTO `sys_form_displays` SET " . $this->arrayToSQL($aDisplay)) <= 0)
            return false;

        $iNewDisplayId = (int)$this->lastId();

        if((int)$this->query("INSERT INTO `sys_form_display_inputs` SELECT NULL, '" . $sNewDisplayName . "', `input_name`, `visible_for_levels`, `active`, `order` FROM `sys_form_display_inputs` WHERE `display_name`=:display_name AND `active`='1'", array('display_name' => $sDisplayName)) <= 0)
            return false;

        return true;
    }
 
    public function getCompanies()
    {
        $CNF = &$this->_oConfig->CNF;
 
        $aData = $this->getAll("SELECT DISTINCT `company_uri`, `company` FROM `" . $CNF['TABLE_ENTRIES'] . "` WHERE `status`='active' AND `company`!=''");
    
        return $aData;
    }
 
	public function getCompanyByUri($sUri)
	{ 
        $CNF = &$this->_oConfig->CNF;

        $sQuery = $this->prepare("SELECT DISTINCT `company` FROM `" . $CNF['TABLE_ENTRIES'] . "` WHERE `company_uri` = ? AND `company`!='' AND `status`='active' LIMIT 1", $sUri); 

		return $this->getOne($sQuery);
	}

    public function insertInterested($aParamsSet)
    {
        $CNF = &$this->_oConfig->CNF;

        if(empty($aParamsSet))
            return 0;

        $sSetClause = $this->arrayToSQL($aParamsSet);
        if(!isset($aParamsSet['date']))
            $sSetClause .= ", `date`=UNIX_TIMESTAMP()";

        if((int)$this->query("INSERT INTO `" . $CNF['TABLE_INTERESTED_TRACK'] . "` SET " . $sSetClause) <= 0)
            return 0;

        return (int)$this->lastId();
    }
 
    public function isInterested($iEntryId, $iProfileId)
    {
        $CNF = &$this->_oConfig->CNF;

        return (int)$this->getOne("SELECT `id` FROM `" . $CNF['TABLE_INTERESTED_TRACK'] . "` WHERE `entry_id`=:entry_id AND `profile_id`=:profile_id LIMIT 1", array(
            'entry_id' => $iEntryId,
            'profile_id' => $iProfileId
        )) > 0;
    }

    public function removeInterested($aParamsSet)
    {
        $CNF = &$this->_oConfig->CNF;

        if(empty($aParamsSet))
            return 0;

        $sSetClause = $this->arrayToSQL($aParamsSet, ' AND ');

		return $this->query("DELETE FROM `" . $CNF['TABLE_INTERESTED_TRACK'] . "` WHERE " . $sSetClause);
    }

    public function getInterested($iEntryId)
    {
        $CNF = &$this->_oConfig->CNF;
 
        return $this->getAll("SELECT `profile_id` FROM `" . $CNF['TABLE_INTERESTED_TRACK'] . "` INNER JOIN `sys_profiles` ON (`sys_profiles`.`id` = ABS(`" . $CNF['TABLE_INTERESTED_TRACK'] . "`.`profile_id`)) WHERE `sys_profiles`.`status` = 'active' AND `" . $CNF['TABLE_INTERESTED_TRACK'] . "`.`entry_id`=:entry_id", array(
            'entry_id' => $iEntryId 
         ));
    }

    protected function _getEntriesBySearchIds($aParams, &$aMethod, &$sSelectClause, &$sJoinClause, &$sWhereClause, &$sOrderClause, &$sLimitClause)
    {
        $CNF = &$this->_oConfig->CNF;

        foreach($aParams['search_params'] as $sSearchParam => $aSearchParam) {
            if($aSearchParam['operator'] != 'between')
                continue;
            
            if(!is_array($aSearchParam['value']) || count($aSearchParam['value']) != 2) 
                continue;

            foreach($aSearchParam['value'] as $iIndex => $sValue) {
                switch($sSearchParam) {
                    case $CNF['FIELD_MIN_SALARY']:
                    case $CNF['FIELD_MAX_SALARY']:
                        $sValue = (float)$sValue;
                        break; 
                }

                $aParams['search_params'][$sSearchParam]['value'][$iIndex] = $sValue;
            }
        }

        $this->_getParentEntriesBySearchIds($aParams, $aMethod, $sSelectClause, $sJoinClause, $sWhereClause, $sOrderClause, $sLimitClause);
    }
 
    protected function _getParentEntriesBySearchIds($aParams, &$aMethod, &$sSelectClause, &$sJoinClause, &$sWhereClause, &$sOrderClause, &$sLimitClause)
    {
        $CNF = &$this->_oConfig->CNF;

        $aMethod['name'] = 'getColumn';

        $sSelectClause = " DISTINCT `" . $CNF['TABLE_ENTRIES'] . "`.`" . $CNF['FIELD_ID'] . "`";

        if (!empty($aParams['start']) && !empty($aParams['per_page']))
            $sLimitClause = $this->prepareAsString("?, ?", $aParams['start'], $aParams['per_page']);
        elseif (!empty($aParams['per_page']))
            $sLimitClause = $this->prepareAsString("?", $aParams['per_page']);

        $sWhereConditions = "1";
        foreach($aParams['search_params'] as $sSearchParam => $aSearchParam) {
            if(empty($aSearchParam['operator']))
                continue;

            $sSearchValue = "";
            switch ($aSearchParam['operator']) {
                case 'like':
                    if(is_array($aSearchParam['value'])) {
                        $sSubCondition = "0";
                        foreach($aSearchParam['value'] as $sValue)
                            if(!empty($sValue))
                                $sSubCondition .= " OR `" . $CNF['TABLE_ENTRIES'] . "`.`" . $sSearchParam . "` LIKE " . $this->_getEbsiLike($sValue);

                        if($sSubCondition != "0")
                            $sWhereConditions .= " AND (" . $sSubCondition . ")";
                    }
                    else
                        $sSearchValue = " LIKE " . $this->_getEbsiLike($aSearchParam['value']);
                    break;

                case 'in':
                    $sSearchValue = " IN (" . $this->implode_escape($aSearchParam['value']) . ")";
                    break;

                case 'not in':
                    $sSearchValue = " NOT IN (" . $this->implode_escape($aSearchParam['value']) . ")";
                    break;	
                    
                case 'and':
                    $iResult = 0;
                    if (is_array($aSearchParam['value']))
                        foreach ($aSearchParam['value'] as $iValue)
                            $iResult |= pow (2, $iValue - 1);
                    else 
                        $iResult = (int)$aSearchParam['value'];

                    $sSearchValue = " & " . $iResult . "";
                    break;

                case 'locate':
                    if(!isset($CNF['OBJECT_METATAGS']))
                        break;
                    if ($aSearchParam['type'] == 'location_radius'){
                        if ($aSearchParam['value']['string'] != ''){
                            list($fLatitude, $fLongitude, $sCountry, $sState, $sCity, $sZip, $sStreet, $sStreetNumber, $iRadius) = $aSearchParam['value']['array'];
                            $aBounds = bx_get_location_bounds_latlng($fLatitude, $fLongitude, $iRadius);   
                            $aSql = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS'])->locationsGetAsSQLPart($CNF['TABLE_ENTRIES'], $CNF['FIELD_ID'], '', '', '', '', $aBounds);
                        }
                    }
                    else{
                        list($fLatitude, $fLongitude, $sCountry, $sState, $sCity, $sZip) = $aSearchParam['value']['array'];
                        $aSql = BxDolMetatags::getObjectInstance($CNF['OBJECT_METATAGS'])->locationsGetAsSQLPart($CNF['TABLE_ENTRIES'], $CNF['FIELD_ID'], $sCountry, $sState, $sCity, $sZip);
                    }
                    if(!empty($aSql['where'])) {
                        $sWhereConditions .= $aSql['where'];

                        if(!empty($aSql['join']))
                            $sJoinClause .= $aSql['join'];
                    }
                    break;
                    
                case 'between':
                    if(!is_array($aSearchParam['value']) || count($aSearchParam['value']) != 2) 
                        break;

                    list($mixedMin, $mixedMax) = $aSearchParam['value'];

                    if(!empty($mixedMin)) { 
						$sWhereConditions .= " AND `" . $CNF['TABLE_ENTRIES'] . "`.`" . $sSearchParam . "` >= :" . $sSearchParam . "_from";
                
                        $aMethod['params'][1][$sSearchParam . "_from"] = $mixedMin; 
                    }

                    if(!empty($mixedMax)) {
 
						$sWhereConditions .= " AND `" . $CNF['TABLE_ENTRIES'] . "`.`" . $sSearchParam . "` <= :" . $sSearchParam . "_to";
				 
                        $aMethod['params'][1][$sSearchParam . "_to"] = $mixedMax; 
                    }
                    break;

                default:
                    $sSearchValue = " " . $aSearchParam['operator'] . " :" . $sSearchParam;
                    $aMethod['params'][1][$sSearchParam] = $aSearchParam['value'];
            }

            if(!empty($sSearchValue))
                $sWhereConditions .= " AND `" . $CNF['TABLE_ENTRIES'] . "`.`" . $sSearchParam . "`" . $sSearchValue;
        }

        $sWhereClause .= " AND (" . $sWhereConditions . ")"; 

        $this->_getEntriesBySearchIdsOrder($aParams, $sOrderClause);
    }
 

}

/** @} */
