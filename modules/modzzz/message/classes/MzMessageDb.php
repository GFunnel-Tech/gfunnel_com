<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) Modzzz
 * 
 * @defgroup    Profile Message Profile Message 
 * @ingroup     ModzzzModules
 *
 * @{
 */
 
class MzMessageDb extends BxBaseModTextDb
{
	function __construct(&$oConfig) 
    {
		parent::__construct($oConfig);	
    }
 
    function purgeMessages($iProfileId=0)
    {
        $CNF = &$this->_oConfig->CNF;
 
		return $this->query("DELETE FROM `" . $CNF['TABLE_ENTRIES'] . "` WHERE `" . $CNF['FIELD_AUTHOR'] . "` = :author",  array('author' => $iProfileId)); 
	}

    public function getInfoByProfileId ($iProfileId, $bCheckActive=false)
    {
		$sExtra = '';
        $CNF = &$this->_oConfig->CNF;

        if(empty($CNF['TABLE_ENTRIES']) || empty($CNF['FIELD_AUTHOR']))
            return array();

		if($bCheckActive) 
			$sExtra = "`" . $CNF['FIELD_STATUS'] . "` = 'active' AND ";
 
        return $this->getRow ("SELECT * FROM `" . $CNF['TABLE_ENTRIES'] . "` WHERE {$sExtra}  (`" . $CNF['FIELD_PUBLISHED'] . "` <= :end_time  AND (`" . $CNF['FIELD_EXPIRE'] . "` = 0 OR `" . $CNF['FIELD_EXPIRE'] . "` >= :end_time)) AND `" . $CNF['FIELD_AUTHOR'] . "` = :profile ORDER BY RAND() LIMIT 1", array('end_time'=>time(), 'profile'=>$iProfileId)); 
    }

    public function expireEntries()
    {
        $CNF = $this->_oConfig->CNF;

        $aEntries = $this->getAll("SELECT `id`, `" . $CNF['FIELD_EXPIRE'] . "`, FROM_UNIXTIME(`" . $CNF['FIELD_EXPIRE'] . "`)  FROM `" . $CNF['TABLE_ENTRIES'] . "` WHERE `" . $CNF['FIELD_EXPIRE'] . "` > `" . $CNF['FIELD_ADDED'] . "` AND `" . $CNF['FIELD_STATUS'] . "` = 'active'");
        if(empty($aEntries) || !is_array($aEntries))
            return false;

        $iNow = time();
        $aResult = array();
        foreach($aEntries as $aEntry)
            if($aEntry[$CNF['FIELD_EXPIRE']] <= $iNow) 
                $aResult[] = $aEntry[$CNF['FIELD_ID']];
 
        return count($aResult) == (int)$this->query("DELETE FROM `" . $CNF['TABLE_ENTRIES'] . "` WHERE `id` IN (" . $this->implode_escape($aResult) . ")") ? $aResult : false;
    }



 
}

/** @} */
