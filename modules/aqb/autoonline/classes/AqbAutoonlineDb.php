<?php defined('BX_DOL') or die('hack attempt');
/**
 *     copyright            : (C) 2018 AQB Soft
 *     website              : http://www.aqbsoft.com
 *
 * IMPORTANT: This is a commercial product made by AQB Soft. It cannot be modified for other than personal usage.
 * The "personal usage" means the product can be installed and set up for ONE domain name ONLY.
 * To be able to use this product for another domain names you have to order another copy of this product (license).
 *
 * This product cannot be redistributed for free or a fee without written permission from AQB Soft.
 *
 * This notice may not be removed from the source code.
 *
 * @defgroup    Autoonline Autoonline
 * @ingroup     UnaModules
 *
 * @{
 */


class AqbAutoonlineDb extends BxBaseModGeneralDb {
	function __construct(&$oConfig) {
		parent::__construct($oConfig);
    }

    function getAccountsTotal() {
    	return $this->getOne("SELECT COUNT(*) FROM `sys_accounts`");
    }

    function getAccountsOnline() {
    	$sQuery = $this->prepare("SELECT COUNT(DISTINCT `sys_accounts`.`id`) FROM `sys_accounts` JOIN `sys_sessions` ON `sys_sessions`.`user_id` = `sys_accounts`.`id` WHERE `sys_sessions`.`date` > (UNIX_TIMESTAMP() - 60 * ?)", (int)getParam('sys_account_online_time'));
    	return $this->getOne($sQuery);
    }

    function execAllRules() {
    	$aRules = $this->getAll("SELECT * FROM `".$this->getPrefix()."_rules`");

    	if ($aRules)
    	foreach ($aRules as $aRule) {
            if ($aRule['weekdays'] && $aRule['weekdays'] != 127) {
                $iDayOfWeek = 1 << (date('N') - 1);

                if (!($aRule['weekdays'] & $iDayOfWeek)) continue;
            }

            if ($aRule['time'] && $aRule['time'] != '00:00 - 23:59') {
                $iMinuteOfDay = date('G')*60 + intval(date('i'));
                preg_match('/(\d+):(\d+) - (\d+):(\d+)/', $aRule['time'], $matches);
                $iStart = intval($matches[1])*60 + intval($matches[2]);
                $iEnd = intval($matches[3])*60 + intval($matches[4]);
                if ($iMinuteOfDay < $iStart || $iMinuteOfDay > $iEnd) continue;
            }

            $this->execRule($aRule);
        }
    }

    function execRule($mRule) {
    	if (!is_array($mRule)) {
    		$sQuery = $this->prepare("SELECT * FROM `".$this->getPrefix()."_rules` WHERE `id` = ?", $mRule);
    		$mRule = $this->getRow($sQuery);
    	}

    	if (!$mRule) return 0;

    	static $aPicsJoins = array();
    	static $aPicsWhere = array();

    	if (!$aPicsJoins || !$aPicsWhere) {
	    	$aModules = BxDolModuleQuery::getInstance()->getModulesBy(array('type' => 'modules', 'active' => 1));
	    	foreach($aModules as $aModule) {
				if (BxDolRequest::serviceExists($aModule['name'], 'act_as_profile') && bx_srv($aModule['name'], 'act_as_profile')) {
					$oModule = bx_instance($aModule['class_prefix'].'Module');
					$aPicsJoins[] = "LEFT JOIN `{$oModule->_oConfig->CNF['TABLE_ENTRIES']}` ON `sys_profiles`.`type` = '{$aModule['name']}' AND `sys_profiles`.`content_id` = `{$oModule->_oConfig->CNF['TABLE_ENTRIES']}`.`{$oModule->_oConfig->CNF['FIELD_ID']}`";
					$aPicsWhere[] = "`sys_profiles`.`type` = '{$aModule['name']}' AND `{$oModule->_oConfig->CNF['TABLE_ENTRIES']}`.`{$oModule->_oConfig->CNF['FIELD_PICTURE']}` <> 0";
				}
	        }
	    }

        if ($mRule['accounts_count'] && empty($aPicsJoins)) return 0; //no profile modules found

        $iOnlineTime = getParam('sys_account_online_time');
		list($from, $to) = explode('-', $mRule['accounts_count']);
        $iCount = rand($from, $to);
        $iTime = $mRule['online_time'] - $iOnlineTime;

    	$sQuery = "
    		INSERT INTO `sys_sessions` (`id`, `user_id`, `data`, `date`)
    		SELECT MD5(`sys_accounts`.`id`) AS `id`, `sys_accounts`.`id`, 'a:0:{}' AS `data`, UNIX_TIMESTAMP() + 60 * {$iTime} AS `date`
    		FROM `sys_accounts`
    		INNER JOIN `sys_profiles` ON `sys_accounts`.`profile_id` = `sys_profiles`.`id`
    		".($mRule['with_ava_only'] ? implode(' ', $aPicsJoins) : '')."
    		WHERE
    			`sys_profiles`.`status` = 'active'
    			AND `sys_accounts`.`id` NOT IN (
    				SELECT DISTINCT `sys_accounts`.`id` FROM `sys_accounts` JOIN `sys_sessions` ON `sys_sessions`.`user_id` = `sys_accounts`.`id` WHERE `sys_sessions`.`date` > (UNIX_TIMESTAMP() - 60 * {$iOnlineTime})
    			)
    			AND (".($mRule['with_ava_only'] ? implode(' OR ', $aPicsWhere) : '1').")
    		ORDER BY RAND()
    		LIMIT {$iCount}
    		ON DUPLICATE KEY UPDATE `date` = UNIX_TIMESTAMP() + 60 * {$iTime}
    	";

    	return $this->query($sQuery);
    }
}

/** @} */
