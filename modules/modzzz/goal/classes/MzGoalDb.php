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

/*
 * Module database queries
 */
class MzGoalDb extends BxBaseModTextDb
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
 
	//begin task 
	public function getTaskInfoById($iContentId)
    {
        return $this->getRow("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_TASK'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_ID'] . "` = :content_id", array(
            'content_id' => $iContentId
        ));
    }

    public function getTaskCountByEntryId($iEntryId)
    {
        return (int)$this->getOne("SELECT COUNT(*) FROM `" . $this->_oConfig->CNF['TABLE_TASK'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_GOAL_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    }

	public function getTaskInfoByEntryId($iEntryId)
    {
        return $this->getAll("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_TASK'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_GOAL_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    }
	//end task

	//begin sponsor
	public function getSponsorInfoById($iContentId)
    {
        return $this->getRow("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_SPONSORS'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_ID'] . "` = :content_id", array(
            'content_id' => $iContentId
        ));
    }

    public function getSponsorsCountByEntryId($iEntryId)
    {
        return (int)$this->getOne("SELECT COUNT(*) FROM `" . $this->_oConfig->CNF['TABLE_SPONSORS'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_GOAL_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    }

	public function getSponsorsInfoByEntryId($iEntryId)
    {
        return $this->getAll("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_SPONSORS'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_GOAL_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    } 
	//end sponsor

	//begin news
	public function getNewsInfoById($iContentId)
    {
        return $this->getRow("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_NEWS'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_ID'] . "` = :content_id", array(
            'content_id' => $iContentId
        ));
    }

    public function getNewsCountByEntryId($iEntryId)
    {
        return (int)$this->getOne("SELECT COUNT(*) FROM `" . $this->_oConfig->CNF['TABLE_NEWS'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_GOAL_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    }

	public function getNewsInfoByEntryId($iEntryId)
    {
        return $this->getAll("SELECT * FROM `" . $this->_oConfig->CNF['TABLE_NEWS'] . "` WHERE `" . $this->_oConfig->CNF['FIELD_GOAL_ID'] . "` = :entry_id", array(
            'entry_id' => $iEntryId
        ));
    } 
	//end news
  
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

	function setGoalAchieved($aContentInfo)
	{
        $CNF = &$this->_oConfig->CNF;
 
		$iEntryId = (int)$aContentInfo['id'];
		$iTime = time();
 
		return $this->query("UPDATE `" . $CNF['TABLE_ENTRIES'] . "` SET status='achieved', `last_update`=:update WHERE `id`=:id", array('id'=>$iEntryId, 'update'=>$iTime));   
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

   function getTaskCount($iEntryId, $sStatus='')
   {
        $CNF = &$this->_oConfig->CNF;

 		$iEntryId = (int)$iEntryId;
 
		switch($sStatus){
			case 'failed':
				$sExtraSQL = " AND `progress` = 'failed'";
			break;
			case 'completed':
				$sExtraSQL = " AND `progress` = 'completed'";
			break;
			case 'upcoming':
				$sExtraSQL = " AND  `progress` = 'upcoming'";
			break;
			case 'present':
				$sExtraSQL = " AND  `progress` = 'ongoing'";
			break;
			case 'past':
				$sExtraSQL = " AND `progress` = 'outstanding'";
			break;
		}

		return (int)$this->getOne ("SELECT COUNT(`id`) FROM `" . $CNF['TABLE_TASK'] . "` WHERE `entry_id`=:id AND `status`='approved'" . $sExtraSQL, array('id'=>$iEntryId));  
    }

	function updateTaskProgressCounter ($iGoalId, $sProgress, $sAction='+')
	{  
        $CNF = &$this->_oConfig->CNF;
 
		$this->query("UPDATE `" . $CNF['TABLE_ENTRIES'] . "` SET `tasks_{$sProgress}`=`tasks_{$sProgress}` {$sAction} 1 WHERE `id`=:id", array('id'=>$iGoalId));  
	}
 
	function processGoals()
	{
        $CNF = &$this->_oConfig->CNF;

		$iTime = time();

		$this->query("UPDATE `" . $CNF['TABLE_ENTRIES'] . "` SET `status`='completed', `changed`=:changed_time WHERE `tasks_completed` < `tasks` AND `complete_by` <= :complete_time", array('changed_time'=>$iTime, 'complete_time'=>$iTime));   
 
		$this->query("UPDATE `" . $CNF['TABLE_ENTRIES'] . "` SET `status`='achieved', `changed`=:changed_time WHERE `tasks_completed` >= `tasks` AND `complete_by` <= :complete_time", array('changed_time'=>$iTime, 'complete_time'=>$iTime));   
	}

	function processTasks()
	{
        $CNF = &$this->_oConfig->CNF;

		$iTime = time();
  
		$aTasks = $this->getAll("SELECT `entry_id` FROM `" . $CNF['TABLE_TASK'] . "` WHERE `end_time` <= :time AND `progress`='ongoing'", array('time'=>$iTime)); 
		foreach($aTasks as $aEachTask){
			$iGoalId = (int)$aEachTask['entry_id'];
			$this->query("UPDATE `" . $CNF['TABLE_ENTRIES'] . "` SET `last_update`=:time WHERE `id`=:id", array('id'=>$iGoalId,'time'=>$iTime));  
		}

		$this->query("UPDATE `" . $CNF['TABLE_TASK'] . "` SET `progress`='outstanding' WHERE `end_time` <= :time AND `progress`='ongoing'", array('time'=>$iTime)); 

		$aTasks = $this->getAll("SELECT `entry_id` FROM `" . $CNF['TABLE_TASK'] . "` WHERE `start_time` <= :start_time AND `end_time` > :end_time AND `progress`='upcoming'", array('start_time'=>$iTime, 'end_time'=>$iTime)); 
		foreach($aTasks as $aEachTask){
			$iGoalId = (int)$aEachTask['entry_id'];
			$this->query("UPDATE `" . $CNF['TABLE_ENTRIES'] . "` SET `last_update`=:time WHERE `id`=:id", array('id'=>$iGoalId,'time'=>$iTime));  
		}

		$this->query("UPDATE `" . $CNF['TABLE_TASK'] . "` SET `progress`='ongoing' WHERE `start_time` <= :start_time AND `end_time` > :end_time AND `progress`='upcoming'", array('start_time'=>$iTime, 'end_time'=>$iTime)); 
	}
  
	function setTaskProgress($aTaskEntry, $sProgress)
	{
        $CNF = &$this->_oConfig->CNF;

		$iGoalId = (int)$aTaskEntry['entry_id'];
		$iTaskId = (int)$aTaskEntry['id'];
		$iTime = time();

		$sPrevProgress = $this->getOne("SELECT `progress` FROM `" . $CNF['TABLE_TASK'] . "` WHERE `id`=:id", array('id'=>$iTaskId));  

		$this->query("UPDATE `" . $CNF['TABLE_TASK'] . "` SET `progress`=:progress WHERE `id`=:id", array('id'=>$iTaskId, 'progress'=>$sProgress));  

		$this->query("UPDATE `" . $CNF['TABLE_ENTRIES'] . "` SET `last_update`=:update WHERE `id`=:id", array('id'=>$iGoalId, 'update'=>$iTime));  

		//$this->updateTaskProgressCounter ($iGoalId, $sPrevProgress, '-');  
		//$this->updateTaskProgressCounter ($iGoalId, $sProgress, '+');  

		return true;
 	} 
 
	function isTaskProgressSet($iEntryId, $sProgress)
	{ 
        $CNF = &$this->_oConfig->CNF;

		return $this->getOne("SELECT `id` FROM `" . $CNF['TABLE_TASK'] . "` WHERE `progress`=:progress AND `id`=:id", array('id'=>$iEntryId, 'progress'=>$sProgress));   
	}
 
	function processReminders() 
	{ 
        $CNF = &$this->_oConfig->CNF;

 		$iTime = time();
		$iCurYear = date('Y');
 		$SECONDS_IN_DAY = 86400; 

		//reminder for upcoming tasks
		$aEntries = $this->getAll("SELECT `id`, `start_time`, `end_time`, `author` FROM `" . $CNF['TABLE_TASK'] . "` WHERE (`start_time` - (`pre_reminder_period`*3600)) <= {$iTime} AND `end_time` > {$iTime} AND `pre_task_reminder`=1 AND `pre_task_reminder_sent`=0 AND `progress`='upcoming'");
 
		foreach ($aEntries as $aEachEntry) {
			$iEntryId = (int)$aEachEntry['id']; 
			$iRecipientId = (int)$aEachEntry['author']; 
			$iStart = (int)$aEachEntry['start_time']; 
			$iEnd = (int)$aEachEntry['end_time']; 

			$this->query("UPDATE `" . $CNF['TABLE_TASK'] . "` SET `pre_task_reminder_sent`=1  WHERE `id`=$iEntryId");  

			$this->alertOnAction('ETEMPLATE_PRE_REMINDER', $aEachEntry, $iRecipientId, $iStart, $iEnd);
		}  
		
		//reminder for outstanding tasks
		$aEntries = $this->getAll("SELECT `id`, `start_time`, `end_time`, `author` FROM `" . $CNF['TABLE_TASK'] . "` WHERE (`end_time` + (`post_reminder_period`*3600)) >= $iTime AND `post_task_reminder`=1 AND `post_task_reminder_sent`=0 AND `progress` = 'outstanding'");
		 
		foreach ($aEntries as $aEachEntry) {
			$iEntryId = (int)$aEachEntry['id']; 
			$iRecipientId = (int)$aEachEntry['author']; 
			$iStart = (int)$aEachEntry['start_time']; 
			$iEnd = (int)$aEachEntry['end_time']; 

			$this->query("UPDATE `" . $CNF['TABLE_TASK'] . "` SET `post_task_reminder_sent`=1  WHERE `id`=$iEntryId");  

			$this->alertOnAction('ETEMPLATE_POST_REMINDER', $aEachEntry, $iRecipientId, $iStart, $iEnd);
		}  
	}

	function alertOnAction($sTemplate, $aTaskInfo, $iContentAuthor, $iStart, $iEnd)
	{ 
        $CNF = &$this->_oConfig->CNF;

		$aContentInfo = $this->getContentInfoById($aTaskInfo[$CNF['FIELD_GOAL_ID']]);
       
		$sGoalUrl = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php', array(
                'i' => $CNF['URI_VIEW_ENTRY'], 
                $CNF['FIELD_ID'] => $aContentInfo[$CNF['FIELD_ID']]
            ));

		$sTaskUrl = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php', array(
                'i' => $CNF['URI_VIEW_TASK'], 
                $CNF['FIELD_ID'] => $aTaskInfo[$CNF['FIELD_ID']]
            ));

        sendMailTemplate($CNF[$sTemplate], 0, $iContentAuthor, array(
            'start_time' => date('M d, Y g:i A', $iStart),
            'end_time' => date('M d, Y g:i A', $iEnd),
            'task_name' => $aTaskInfo[$CNF['FIELD_TITLE']],
            'task_url' => $sTaskUrl,  
            'goal_name' => $aContentInfo[$CNF['FIELD_TITLE']],
            'goal_url' => $sGoalUrl,   
        )); 
    }
  
	function isGoalAchieved($iEntryId)
	{ 
        $CNF = &$this->_oConfig->CNF;

		return $this->getOne("SELECT `id` FROM `" . $CNF['TABLE_ENTRIES'] . "` WHERE `status`='achieved' AND `id`=:id", array('id'=>$iEntryId));   
	}

	function isTaskProgressUpdated($iEntryId)
	{ 
        $CNF = &$this->_oConfig->CNF;

		return $this->getOne("SELECT `id` FROM `" . $CNF['TABLE_TASK'] . "` WHERE `progress` IN ('completed','failed') AND `id`=$iEntryId");   
	}


}

/** @} */
