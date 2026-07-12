<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Listing Listing
 * @ingroup     ModzzzModules
 *
 * @{
 */

/*
 * Module database queries
 */
class MzListingDb extends BxBaseModTextDb
{
    function __construct(&$oConfig)
    {
        parent::__construct($oConfig);
    }

    public function updateContentInfoById($iContentId, $aParamsSet)
    {
        if(empty($aParamsSet))
            return false;

        $CNF = &$this->_oConfig->CNF;
        return (int)$this->query("UPDATE `" . $CNF['TABLE_ENTRIES'] . "` SET " . $this->arrayToSQL($aParamsSet) . " WHERE `" . $CNF['FIELD_ID'] . "` = :content_id", array(
            'content_id' => $iContentId
        )) > 0;
    }
 
	//begin schedule 
	public function getScheduleInfoById($iContentId)
    {
        return $this->getRow("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_SCHEDULE'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_ID'] . "` = :content_id", array(
            'content_id' => $iContentId
        ));
    }

    public function getScheduleCountByEntryId($iEntryId)
    {
        return (int)$this->getOne("SELECT COUNT(*) FROM `" . $this->_oConfig->CNF['TABLE_SCHEDULE'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_LISTING_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    }

	public function getScheduleInfoByEntryId($iEntryId)
    {
        return $this->getAll("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_SCHEDULE'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_LISTING_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    }
	//end schedule
 
	//begin announcement 
	public function getAnnouncementInfoById($iContentId)
    {
        return $this->getRow("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_ANNOUNCEMENT'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_ID'] . "` = :content_id", array(
            'content_id' => $iContentId
        ));
    }

    public function getAnnouncementCountByEntryId($iEntryId)
    {
        return (int)$this->getOne("SELECT COUNT(*) FROM `" . $this->_oConfig->CNF['TABLE_ANNOUNCEMENT'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_LISTING_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    }

	public function getAnnouncementInfoByEntryId($iEntryId)
    {
        return $this->getAll("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_ANNOUNCEMENT'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_LISTING_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    }
	//end announcement

	//begin service
	public function getServiceInfoById($iContentId)
    {
        return $this->getRow("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_SERVICES'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_ID'] . "` = :content_id", array(
            'content_id' => $iContentId
        ));
    }

    public function getServicesCountByEntryId($iEntryId)
    {
        return (int)$this->getOne("SELECT COUNT(*) FROM `" . $this->_oConfig->CNF['TABLE_SERVICES'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_LISTING_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    }

	public function getServicesInfoByEntryId($iEntryId)
    {
        return $this->getAll("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_SERVICES'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_LISTING_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    } 
	//end service

	//begin gift
	public function getGiftInfoById($iContentId)
    {
        return $this->getRow("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_GIFTS'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_ID'] . "` = :content_id", array(
            'content_id' => $iContentId
        ));
    }

    public function getGiftsCountByEntryId($iEntryId)
    {
        return (int)$this->getOne("SELECT COUNT(*) FROM `" . $this->_oConfig->CNF['TABLE_GIFTS'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_LISTING_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    }

	public function getGiftsInfoByEntryId($iEntryId)
    {
        return $this->getAll("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_GIFTS'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_LISTING_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    } 
	//end gift

	//begin reward
	public function getRewardInfoById($iContentId)
    {
        return $this->getRow("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_REWARDS'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_ID'] . "` = :content_id", array(
            'content_id' => $iContentId
        ));
    }

    public function getRewardsCountByEntryId($iEntryId)
    {
        return (int)$this->getOne("SELECT COUNT(*) FROM `" . $this->_oConfig->CNF['TABLE_REWARDS'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_LISTING_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    }

	public function getRewardsInfoByEntryId($iEntryId)
    {
        return $this->getAll("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_REWARDS'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_LISTING_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    } 
	//end reward

	//begin branch
	public function getBranchInfoById($iContentId)
    { 
        return $this->getRow("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_BRANCHES'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_ID'] . "` = :content_id", array(
            'content_id' => $iContentId
        ));
    }

    public function getBranchesCountByEntryId($iEntryId)
    {
        return (int)$this->getOne("SELECT COUNT(*) FROM `" . $this->_oConfig->CNF['TABLE_BRANCHES'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_LISTING_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    }

	public function getBranchesInfoByEntryId($iEntryId)
    {
        return $this->getAll("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_BRANCHES'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_LISTING_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    } 
	//end branch

	//begin claim
 
	public function changeClaimStatus($aParams)
    {
        $CNF = &$this->_oConfig->CNF;

		$iTime = time();
 
        $this->getAll("UPDATE `" . $CNF['TABLE_ENTRIES'] . "` SET `claim_status`=:status, `changed`=:changed WHERE `" . $CNF['FIELD_ID'] . "`=:id", array(
            'id' => $aParams['entry_id'],
			'status' => $aParams['status'],
			'changed' => $iTime
        ));
 
        return $this->query("UPDATE `" . $this->_oConfig->CNF['TABLE_CLAIMS'] . "` SET `status`=:status, `changed`=:changed WHERE `" . $CNF['FIELD_ID'] . "` = :content_id", array(
            'content_id' => $aParams['id'],
			'status' => $aParams['status'],
			'changed' => $iTime
        ));  
    }
 
	public function getClaimInfoById($iContentId)
    {
        return $this->getRow("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_CLAIMS'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_ID'] . "` = :content_id", array(
            'content_id' => $iContentId
        ));
    }

    public function getClaimsCountByEntryId($iEntryId)
    {
        return (int)$this->getOne("SELECT COUNT(*) FROM `" . $this->_oConfig->CNF['TABLE_CLAIMS'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_LISTING_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    }

	public function getClaimsInfoByEntryId($iEntryId)
    {
        return $this->getAll("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_CLAIMS'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_LISTING_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    }
	
	public function hasPendingClaim($iEntryId)
    {
        return $this->getOne("SELECT COUNT(`id`) FROM `" . $this->_oConfig->CNF['TABLE_CLAIMS'] . "` WHERE `status`='pending' AND `" . $this->_oConfig->CNF['FIELD_LISTING_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    }
	//end claim
  
 	function getAllEntries()
    {
        $CNF = &$this->_oConfig->CNF;

        return $this->getAll("SELECT * FROM `" . $CNF['TABLE_ENTRIES'] . "` WHERE `" . $CNF['FIELD_STATUS'] . "`='active'");
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
   
    public function updateCategory($aParamsSet, $aParamsWhere)
    {
        $CNF = &$this->_oConfig->CNF;

        if(empty($aParamsSet) || empty($aParamsWhere))
            return false;

        return $this->query("UPDATE `" . $CNF['TABLE_CATEGORIES'] . "` SET " . $this->arrayToSQL($aParamsSet) . " WHERE " . $this->arrayToSQL($aParamsWhere, ' AND ')) !== false;
    }
 
    public function getCategories($aParams = array())
    { 
		$sExtraWhere = '';

        $CNF = &$this->_oConfig->CNF;

        $aMethod = array('name' => 'getAll', 'params' => array(0 => 'query'));

        $sSelectClause = "`tc`.*";
        $sJoinClause = $sWhereClause = $sGroupClause = "";
        $sOrderClause = "`tc`.`order` ASC";
 
        switch($aParams['type']) {
			case 'id_full':
            case 'id':
                $aMethod['name'] = 'getRow';
                $aMethod['params'][1] = array(
                    'id' => $aParams['id']
                );

                $sWhereClause = " AND `tc`.`id`=:id";
                break;
            case 'parent_id':
                $aMethod['params'][1] = array(
                    'parent_id' => $aParams['parent_id']
                );

                $sWhereClause = " AND `tc`.`parent_id`=:parent_id";
                if(isset($aParams['with_content']) && $aParams['with_content'] === true)
                    $sWhereClause .= " AND `tc`.`items`>0";
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

            case 'collect_stats':

				if($aParams['category_type']=='subcategory'){ 
					$sCategoryField = $CNF['FIELD_SUBCATEGORY'];
					$sWhereClause = " AND `tc`.`parent_id`!=0"; 
				}else{
					$sCategoryField = $CNF['FIELD_CATEGORY'];
					$sWhereClause = " AND `tc`.`parent_id`=0";
				}

                $sSelectClause = "`tc`.`id`, COUNT(`te`.`id`) AS `count`";
                $sJoinClause = $this->prepareAsString(" LEFT JOIN `" . $CNF['TABLE_ENTRIES'] . "` AS `te` ON `tc`.`id`=`te`.`". $sCategoryField ."` AND `te`.`status`='active' AND `te`.`status_admin`='active' AND (`te`.`allow_view_to`=? OR `te`.`allow_view_to`<0)" . $sExtraWhere, BX_DOL_PG_ALL);
                $sGroupClause = "`tc`.`id`";
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

    public function isInterested($iEntryId, $iProfileId)
    {
        $CNF = &$this->_oConfig->CNF;

        return (int)$this->getOne("SELECT `id` FROM `" . $CNF['TABLE_INTERESTED_TRACK'] . "` WHERE `entry_id`=:entry_id AND `profile_id`=:profile_id LIMIT 1", array(
            'entry_id' => $iEntryId,
            'profile_id' => $iProfileId
        )) > 0;
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

    public function getContentInfoByHourId ($iHourId)
    {
        $iContentId = $this->getOne("SELECT `listing_id` FROM `mz_listing_hours` WHERE `hour_id` = :hour", array(
            'hour' => $iHourId,
        ));
        if (!$iContentId)
            return 0;
        return $this->getContentInfoById ($iContentId);
    }

	public function getViewHours($iEntryId)
    {
		$aFilteredHours = array();
        $aDbHours = $this->getAll("SELECT * FROM `mz_listing_hours` WHERE `listing_id` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
 
		foreach($aDbHours as $aHour)
		{ 
			if(!$aHour['repeat_from_day_of_week']) continue;
 
			if($aHour['repeat_to_day_of_week'] == 0)
			{
				$aFilteredHours[$aHour['repeat_from_day_of_week']] = $aHour;
				$aFilteredHours[$aHour['repeat_from_day_of_week']]['day'] = $aHour['repeat_from_day_of_week'];
 
			}elseif($aHour['repeat_from_day_of_week'] && ($aHour['repeat_to_day_of_week'] > $aHour['repeat_from_day_of_week']))
			{  
				///var_dump($aFilteredHours);exit;

				$iFrom = $aHour['repeat_from_day_of_week'];
				$iTo = $aHour['repeat_to_day_of_week'];
				for($iter=$iFrom;$iter<=$iTo;$iter++)
				{
					$aFilteredHours[$iter] = $aHour;
					$aFilteredHours[$iter]['day'] = $iter;
				}	
			}
		}
		//$aHours = ksort($aFilteredHours);

		return $aFilteredHours; 
    } 

    public function deleteHourById ($iHourId)
    {
        return $this->query("DELETE FROM `mz_listing_hours` WHERE `hour_id` = :hour", array(
            'hour' => $iHourId,
        ));
    }
    
    public function getHours ($iContentId) 
    {
        return $this->getAllWithKey("SELECT * FROM `mz_listing_hours` WHERE `listing_id` = :content_id", 'hour_id', array(
            'content_id' => $iContentId,
        ));
    }




}

/** @} */
