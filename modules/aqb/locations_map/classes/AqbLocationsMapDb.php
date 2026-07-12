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
 * @defgroup    Locations Map Locations Map
 * @ingroup     UnaModules
 *
 * @{
 */


class AqbLocationsMapDb extends BxBaseModGeneralDb {
	function __construct(&$oConfig) {
		parent::__construct($oConfig);
    }

    function setDefaultTypesOption() {
        $aTypes = $this->getPossibleLocationsTypes();
        $this->setParam('aqb_locations_map_content_types', implode(',', array_keys($aTypes)));
        $this->setParam('aqb_locations_map_content_types_selected', implode(',', array_keys($aTypes)));
    }

    function getPossibleLocationsTypes() {
        $aResult = array();

        $aLocationsTables = $this->getColumn("SHOW TABLES LIKE '%meta_locations'");
        if ($aLocationsTables)
        foreach ($aLocationsTables as $sTableName) {
            $sModuleName = substr($sTableName, 0, -15);
            $oModule = BxDolModule::getInstance($sModuleName);
            if ($oModule) $aResult[$sModuleName] = _t('_'.$sModuleName);
        }

        $aResult['online_profiles'] = _t('_aqb_locations_map_online_profiles');
        //$aResult['custom_locations'] = _t('_aqb_locations_map_custom_locations');
        return $aResult;
    }

    function getLocations($iRandSeed) {
        if (bx_get('init')) {
            $aTypes = explode(',', $this->getParam('aqb_locations_map_content_types'));
            $aTypesSelected = explode(',', $this->getParam('aqb_locations_map_content_types_selected'));

            $aParts = array();
            $aPartsSelected = array();

            $aLocationsTables = $this->getColumn("SHOW TABLES LIKE '%meta_locations'");
            if ($aLocationsTables) {
                foreach ($aLocationsTables as $sTableName) {
                    $sModuleName = substr($sTableName, 0, -15);
                    if (in_array($sModuleName, $aTypes))
                        $aParts[] = "SELECT `lat`, `lng`, '{$sModuleName}' AS `type`, `object_id` FROM `{$sTableName}` WHERE 1 __where__";
                    if (in_array($sModuleName, $aTypesSelected))
                        $aPartsSelected[] = "SELECT `lat`, `lng`, '{$sModuleName}' AS `type`, `object_id` FROM `{$sTableName}` WHERE 1 __where__";
                }
            }

            if (in_array('online_profiles', $aTypes)) $aParts[] = $this->getOnlineProfilesQuery();
            if (in_array('online_profiles', $aTypesSelected)) $aPartsSelected[] = $this->getOnlineProfilesQuery();

            if (!$aParts) {
                return ['locations' => 0, 'total' => 0, 'all' => 1];
            }

            $sQuery = implode(' UNION ALL ', $aParts);
            $sQuerySelected = implode(' UNION ALL ', $aPartsSelected);

            $aWhere = array();
            $aWhere[] = '`lat` >= '.min(doubleval($_REQUEST['lat_min']), doubleval($_REQUEST['lat_max']));
            $aWhere[] = '`lat` <= '.max(doubleval($_REQUEST['lat_min']), doubleval($_REQUEST['lat_max']));
            $aWhere[] = '`lng` >= '.min(doubleval($_REQUEST['lng_min']), doubleval($_REQUEST['lng_max']));
            $aWhere[] = '`lng` <= '.max(doubleval($_REQUEST['lng_min']), doubleval($_REQUEST['lng_max']));
            $sBounds = ' AND '.implode(' AND ', $aWhere).' ';

            //if this is an initial load then first try to load ALL locations at once to not generate redundant requests later
            $sQueryCnt = str_replace('__where__', '', $sQuery);
            $sQueryCnt = "SELECT COUNT(*) FROM ({$sQueryCnt}) AS `tmp`";
            $iTotal = $this->getOne($sQueryCnt);
            if ($iTotal <= $this->getParam('aqb_locations_map_max_locations')) {
                //if there are less than max number then load all at once
                $sQuery = str_replace('__where__', '', $sQuery);
                $sQuery = "SELECT * FROM ({$sQuery}) AS `tmp`";
                $aResult = $this->getAll($sQuery);
                return ['locations' => $aResult, 'total' => count($aResult), 'all' => 1];
            } else {
                //otherwise load just what fits to viewport
                $sQuery = str_replace('__where__', $sBounds, $sQuerySelected);
                $sQuery = "SELECT * FROM ({$sQuery}) AS `tmp` ORDER BY RAND({$iRandSeed}) LIMIT ".$this->getParam('aqb_locations_map_max_locations');
                $aResult = $this->getAll($sQuery);
                return ['locations' => $aResult, 'total' => count($aResult), 'all' => 0];
            }

        } else {
            $aTypes = explode(',', isset($_REQUEST['filter']) && $_REQUEST['filter'] ? $_REQUEST['filter'] : $this->getParam('aqb_locations_map_content_types'));

            $aParts = array();

            $aLocationsTables = $this->getColumn("SHOW TABLES LIKE '%meta_locations'");
            if ($aLocationsTables) {
                foreach ($aLocationsTables as $sTableName) {
                    $sModuleName = substr($sTableName, 0, -15);
                    if (in_array($sModuleName, $aTypes))
                        $aParts[] = "SELECT `lat`, `lng`, '{$sModuleName}' AS `type`, `object_id` FROM `{$sTableName}` WHERE 1 __where__";
                }
            }

            if (in_array('online_profiles', $aTypes)) $aParts[] = $this->getOnlineProfilesQuery();

            if (!$aParts) {
                return ['locations' => 0, 'total' => 0, 'all' => 0];
            }

            $sQuery = implode(' UNION ALL ', $aParts);

            $aWhere = array();
            $aWhere[] = '`lat` >= '.min(doubleval($_REQUEST['lat_min']), doubleval($_REQUEST['lat_max']));
            $aWhere[] = '`lat` <= '.max(doubleval($_REQUEST['lat_min']), doubleval($_REQUEST['lat_max']));
            $aWhere[] = '`lng` >= '.min(doubleval($_REQUEST['lng_min']), doubleval($_REQUEST['lng_max']));
            $aWhere[] = '`lng` <= '.max(doubleval($_REQUEST['lng_min']), doubleval($_REQUEST['lng_max']));
            $sBounds = ' AND '.implode(' AND ', $aWhere).' ';

            //if this is not an initial load then just return locations within viewport
            $sQuery = str_replace('__where__', $sBounds, $sQuery);
            $sQuery = "SELECT * FROM ({$sQuery}) AS `tmp` ORDER BY RAND({$iRandSeed}) LIMIT ".$this->getParam('aqb_locations_map_max_locations');
            $aResult = $this->getAll($sQuery);
            return ['locations' => $aResult, 'total' => count($aResult), 'all' => 0];
        }
    }

    function getOnlineProfilesQuery() {
        $aLocationsTables = $this->getColumn("SHOW TABLES LIKE '%meta_locations'");
        $aParts = array();

        $aModules = BxDolModuleQuery::getInstance()->getModulesBy(array('type' => 'modules', 'active' => 1));
        foreach($aModules as $aModule) {
            if (BxDolRequest::serviceExists($aModule['name'], 'act_as_profile') && bx_srv($aModule['name'], 'act_as_profile')) {
                $oModule = bx_instance($aModule['class_prefix'].'Module');
                if (!in_array($aModule['name'].'_meta_locations', $aLocationsTables)) continue; //no known locations for this module

                $aParts[] = "
                    SELECT `lat`, `lng`, 'online_profiles' AS `type`, `OnlineProfiles`.`id` AS `object_id`
                    FROM `{$aModule['name']}_meta_locations`
                    JOIN (
                        SELECT `sys_profiles`.`id`, `sys_profiles`.`content_id`
                        FROM `sys_accounts`
                        JOIN `sys_sessions` ON `sys_sessions`.`user_id` = `sys_accounts`.`id`
                        JOIN `sys_profiles` ON `sys_profiles`.`id` = `sys_accounts`.`profile_id`
                        WHERE `sys_sessions`.`date` > (UNIX_TIMESTAMP() - 60 * ".getParam('sys_account_online_time').") AND `sys_profiles`.`status` = 'active' AND `sys_profiles`.`type` = '{$aModule['name']}'
                    ) AS `OnlineProfiles`
                    ON `OnlineProfiles`.`content_id` = `{$aModule['name']}_meta_locations`.`object_id`
                    WHERE 1 __where__
                ";
            }
        }

        $sQuery = implode(' UNION ALL ', $aParts);

        return $sQuery;
    }

    function getSameLocations($fLat, $fLng, $sFilter) {
        $aTypes = explode(',', !empty($sFilter) ? $sFilter : $this->getParam('aqb_locations_map_content_types'));
        $aParts = array();

        $aLocationsTables = $this->getColumn("SHOW TABLES LIKE '%meta_locations'");
        if ($aLocationsTables) {
            foreach ($aLocationsTables as $sTableName) {
                $sModuleName = substr($sTableName, 0, -15);
                if (in_array($sModuleName, $aTypes))
                    $aParts[] = "SELECT '{$sModuleName}' AS `type`, `object_id` FROM `{$sTableName}` WHERE ABS(`lat` - {$fLat}) < 0.0000001 AND ABS(`lng` - {$fLng}) < 0.0000001";
            }
        }

        if (in_array('online_profiles', $aTypes)) $aParts[] = $this->getSameOnlineProfilesQuery($fLat, $fLng);

        if (!$aParts) {
            $iTotal = 0;
            return array();
        }

        $sQuery = implode(' UNION ', $aParts);

        $sQuery = "SELECT DISTINCT * FROM ({$sQuery}) AS `tmp`";
        return $this->getAll($sQuery);
    }

    function getSameOnlineProfilesQuery($fLat, $fLng) {
        $aLocationsTables = $this->getColumn("SHOW TABLES LIKE '%meta_locations'");
        $aParts = array();

        $aModules = BxDolModuleQuery::getInstance()->getModulesBy(array('type' => 'modules', 'active' => 1));
        foreach($aModules as $aModule) {
            if (BxDolRequest::serviceExists($aModule['name'], 'act_as_profile') && bx_srv($aModule['name'], 'act_as_profile')) {
                $oModule = bx_instance($aModule['class_prefix'].'Module');
                if (!in_array($aModule['name'].'_meta_locations', $aLocationsTables)) continue; //no known locations for this module

                $aParts[] = "
                    SELECT 'online_profiles' AS `type`, `OnlineProfiles`.`id` AS `object_id`
                    FROM `{$aModule['name']}_meta_locations`
                    JOIN (
                        SELECT `sys_profiles`.`id`, `sys_profiles`.`content_id`
                        FROM `sys_accounts`
                        JOIN `sys_sessions` ON `sys_sessions`.`user_id` = `sys_accounts`.`id`
                        JOIN `sys_profiles` ON `sys_profiles`.`id` = `sys_accounts`.`profile_id`
                        WHERE `sys_sessions`.`date` > (UNIX_TIMESTAMP() - 60 * ".getParam('sys_account_online_time').") AND `sys_profiles`.`status` = 'active' AND `sys_profiles`.`type` = '{$aModule['name']}'
                    ) AS `OnlineProfiles`
                    ON `OnlineProfiles`.`content_id` = `{$aModule['name']}_meta_locations`.`object_id`
                    WHERE ABS(`{$aModule['name']}_meta_locations`.`lat` - {$fLat}) < 0.0000001 AND ABS(`{$aModule['name']}_meta_locations`.`lng` - {$fLng}) < 0.0000001
                ";
            }
        }

        $sQuery = implode(' UNION ', $aParts);

        return $sQuery;
    }

    function getProfileInfo($iId) {
        $sQuery = $this->prepare('SELECT `type`, `content_id` FROM `sys_profiles` WHERE `id` = ?', $iId);
        return $this->getRow($sQuery);
    }

    function getLocationInfo($sWhat, $sFrom, $sFieldID, $iId) {
        $sQuery = $this->prepare("SELECT `{$sWhat}` FROM `{$sFrom}` WHERE `{$sFieldID}` = ?", intval($iId));
        return $this->getOne($sQuery);
    }

}

/** @} */
