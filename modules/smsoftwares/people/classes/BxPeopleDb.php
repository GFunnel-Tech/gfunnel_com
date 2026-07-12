<?php defined('BX_DOL') or die('hack attempt');


class BxPeopleDb extends BxDolModuleDb
{
   public function __construct(&$oConfig)
    {
        parent::__construct($oConfig);
    }
	
	public function getContentInfoById ($iContentId)
    {
		
        $sQuery = $this->prepare ("SELECT `c`.*, `p`.`account_id`, `p`.`id` AS `profile_id`, `a`.`email` AS `profile_email`, `a`.`ip` AS `profile_ip`, `p`.`status` AS `profile_status` FROM `" . $this->_oConfig->CNF['TABLE_ENTRIES'] . "` AS `c` INNER JOIN `sys_profiles` AS `p` ON (`p`.`content_id` = `c`.`id` AND `p`.`type` = ?) INNER JOIN `sys_accounts` AS `a` ON (`p`.`account_id` = `a`.`id`) WHERE `c`.`id` = ?", 'bx_persons', $iContentId);
        $aInfo = $this->getRow($sQuery);
        bx_alert('profile', 'content_info_by_id', $iContentId, 0, array('module' => $this->_oConfig->getName(), 'info' => &$aInfo));
        return $aInfo;
    }
	
	public function getAppByuserId ($uid) {
		
		$sQuery = $this->prepare ("SELECT * FROM `sys_menu_items` WHERE set_name = 'sys_homepage' AND `active`=1 AND `author`=?", $uid);
        $aInfo = $this->getAll($sQuery);
		
		return $aInfo;
	}
	
	public function getAppById ($id) {
		
		$sQuery = $this->prepare ("SELECT * FROM `sys_menu_items` WHERE set_name = 'sys_homepage' AND `active`=1 AND `id`=?", $id);
        $aInfo = $this->getRow($sQuery);
		
		return $aInfo;
	}
}

/** @} */
