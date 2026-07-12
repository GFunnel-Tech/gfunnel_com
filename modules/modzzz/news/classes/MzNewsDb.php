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

/*
 * Module database queries
 */
class MzNewsDb extends BxBaseModTextDb
{
    function __construct(&$oConfig)
    {
        parent::__construct($oConfig);
    }
 
    public function getItemsNumInArchive ($iYear, $iMonth=0, $bPublicOnly = true)
    { 
        $sWhere = '';
 
        $CNF = &$this->_oConfig->CNF;

        if ($bPublicOnly) { 
            if(isset($CNF['FIELD_ALLOW_VIEW_TO'])) {
                bx_import('BxDolPrivacy');
                $a = isLogged() ? array(BX_DOL_PG_ALL, BX_DOL_PG_MEMBERS) : array(BX_DOL_PG_ALL);
                $sWhere .= ' AND `' . $CNF['TABLE_ENTRIES'] . '`.`' . $CNF['FIELD_ALLOW_VIEW_TO'] . '` IN(' . $this->implode_escape($a) . ') ';
            }
		}

		if(isset($CNF['FIELD_STATUS']))
			$sWhere .= ' AND `' . $CNF['TABLE_ENTRIES'] . '`.`' . $CNF['FIELD_STATUS'] . '` = \'active\' ';
 
		if (isset($CNF['FIELD_LANGUAGE']) && $this->_oConfig->_isMultiLanguage)
		{ 
			 $sLang = bx_lang_name(); 
			 $sWhere .= ' AND `' . $CNF['TABLE_ENTRIES']  . '`.`' . $CNF['FIELD_LANGUAGE'] . '` IN (\'\', \'all\', \''. $sLang . '\') '; 
		}
 
 		if($iMonth)
		{
			$sSQLMonthWhere = ' AND MONTH(FROM_UNIXTIME( `' . $CNF['TABLE_ENTRIES'] . '`.`published`)) = :month ';
			return $this->getOne("SELECT COUNT(*) FROM `" . $CNF['TABLE_ENTRIES'] . "` WHERE YEAR(FROM_UNIXTIME(`" . $CNF['TABLE_ENTRIES'] . "`.`published`)) = :year " . $sSQLMonthWhere . $sWhere, array('year' => $iYear, 'month' => $iMonth));
		}else{  
			return $this->getOne("SELECT COUNT(*) FROM `" . $CNF['TABLE_ENTRIES'] . "` WHERE YEAR(FROM_UNIXTIME(`" . $CNF['TABLE_ENTRIES'] . "`.`published`)) = :year " . $sWhere, array('year' => $iYear));
		}
    }

    public function removeBreakingNews()
    {
        $CNF = $this->_oConfig->CNF;

        $aEntries = $this->getAll("SELECT `id`, `" . $CNF['FIELD_BREAKING_THRESHOLD'] . "`, FROM_UNIXTIME(`" . $CNF['FIELD_BREAKING_THRESHOLD'] . "`)  FROM `" . $CNF['TABLE_ENTRIES'] . "` WHERE `" . $CNF['FIELD_BREAKING_THRESHOLD'] . "` > `" . $CNF['FIELD_ADDED'] . "`");
        if(empty($aEntries) || !is_array($aEntries))
            return false;

        $iNow = time();
        $aResult = array();
        foreach($aEntries as $aEntry)
            if($aEntry[$CNF['FIELD_BREAKING_THRESHOLD']] <= $iNow) 
                $aResult[] = $aEntry[$CNF['FIELD_ID']];

        $sSet = "`" . $CNF['FIELD_BREAKING_NEWS'] . "`=0, `" . $CNF['FIELD_BREAKING_THRESHOLD'] . "`=0";
     
        return count($aResult) == (int)$this->query("UPDATE `" . $CNF['TABLE_ENTRIES'] . "` SET " . $sSet . " WHERE `id` IN (" . $this->implode_escape($aResult) . ")") ? $aResult : false;
    }
 
	public function updateBreakingNews($iContentId, $aContentInfo=array())
	{
        $CNF = $this->_oConfig->CNF;
 
		if(empty($aContentInfo))
			$aContentInfo = $this->getContentInfoById($iContentId);

		if($aContentInfo[$CNF['FIELD_BREAKING_NEWS']]) {

			if($aContentInfo[$CNF['FIELD_BREAKING_THRESHOLD']]==0)  {
				
				$iTime = $aContentInfo[$CNF['FIELD_ADDED']] + ((int)getParam('mz_news_breaking_hours') * 3600);
				return (int)$this->query("UPDATE `" . $CNF['TABLE_ENTRIES'] . "` SET `" . $CNF['FIELD_BREAKING_THRESHOLD'] . "`= " . $iTime . " WHERE `id` = " . $iContentId);  
			}

		}else{
			return (int)$this->query("UPDATE `" . $CNF['TABLE_ENTRIES'] . "` SET `" . $CNF['FIELD_BREAKING_THRESHOLD'] . "`=0 WHERE `id` = " . $iContentId); 
		}

		return false;
	}

    public function getItemsNumInCategory ($aObject, $sCategoryValue, $bPublicOnly = true)
    {
        //$oDb = BxDolDb::getInstance();
        $sWhere = '';
        // TODO: in the future add 'module' field to categories object
        if ($bPublicOnly && ($oModule = BxDolModule::getInstance($aObject['search_object']))) {
            $CNF = &$this->_oConfig->CNF;

            if(isset($CNF['FIELD_ALLOW_VIEW_TO'])) {
                bx_import('BxDolPrivacy');
                $a = isLogged() ? array(BX_DOL_PG_ALL, BX_DOL_PG_MEMBERS) : array(BX_DOL_PG_ALL);
                $sWhere .= ' AND `' . $aObject['table'] . '`.`' . $CNF['FIELD_ALLOW_VIEW_TO'] . '` IN(' . $this->implode_escape($a) . ') ';
            }

            if(isset($CNF['FIELD_STATUS']))
                $sWhere .= ' AND `' . $aObject['table'] . '`.`' . $CNF['FIELD_STATUS'] . '` = \'active\' ';

            if(isset($CNF['FIELD_STATUS_ADMIN']))
                $sWhere .= ' AND `' . $aObject['table'] . '`.`' . $CNF['FIELD_STATUS_ADMIN'] . '` = \'active\' ';
 
			if (isset($CNF['FIELD_LANGUAGE']) && $this->_oConfig->_isMultiLanguage){ 
				 $sLang = bx_lang_name(); 
				 $sWhere .= ' AND `' . $aObject['table'] . '`.`' . $CNF['FIELD_LANGUAGE'] . '` IN (\'\', \'all\', \''. $sLang . '\') '; 
			}
		}
 
        return $this->getOne("SELECT COUNT(*) FROM `" . $aObject['table'] . "` " . $aObject['join'] . " WHERE `" . $aObject['field'] . "` = :cat " . $aObject['where'] . $sWhere, array('cat' => $sCategoryValue));
    }


}

/** @} */
