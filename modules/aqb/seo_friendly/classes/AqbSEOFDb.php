<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    JamiiTrade JamiiTrade
 * @ingroup     UnaModules
 *
 * @{
 */

class AqbSEOFDb extends BxBaseModGeneralDb
{
    function __construct(&$oConfig)
    {
        parent::__construct($oConfig);
    }

    function findNotLatinUrls($sModule = ''){
        $sWhereModule = '';
        if ($sModule)
            $sWhereModule = " AND `module`='" . bx_process_input($sModule) . "'";

        return $this->getAll("SELECT * FROM `sys_seo_links` WHERE " . $this->_oConfig->CNF['NON-ASCII'] . $sWhereModule);
    }

    function getLangs($sLang = ''){
        $CNF = &$this->_oConfig->CNF;

        $aResult = [];
        if ($sLang) {
            $sData = $this->getOne("SELECT `{$CNF['FIELD_SYMBOLS']}` FROM `{$CNF['SYMBOLS']}` WHERE `{$CNF['FIELD_LANG']}`=:lang LIMIT 1", ['lang' => $sLang]);
            $aResult[$sLang] = unserialize($sData);
        } else
        {
            $aLangs = $this->getPairs("SELECT * FROM `{$CNF['SYMBOLS']}`", $CNF['FIELD_LANG'], $CNF['FIELD_SYMBOLS']);
            foreach ($aLangs as $sLang => $sValue)
                $aResult[$sLang] = unserialize($sValue);
        }

        return $aResult;
    }

    function getUriById($iId){
        return $this->getRow("SELECT * FROM `sys_seo_links` WHERE `id`=:id", ['id' => $iId]);
    }

    function getUriByUri($sUri){
        return $this->getOne("SELECT COUNT(*) FROM `sys_seo_links` WHERE `uri`=:uri", ['uri' => $sUri]);
    }

    function getPageByModulePageUri($sModule, $sPageUri, $sUri){
        return $this->getRow("SELECT * FROM `sys_seo_links` WHERE `module`=:module AND `page_uri`=:page AND `uri`=:uri LIMIT 1", ['module' => $sModule, 'page' => $sPageUri, 'uri'=> $sUri]);
    }

    function updateUriById($sUri, $iId){
        return $this->query("UPDATE `sys_seo_links` SET `uri`=:uri WHERE `id`=:id", ['id' => $iId, 'uri' => $sUri]);
    }

    function updateUriByUri($sOldUri, $sNewUri){
        return $this->query("UPDATE `sys_seo_links` SET `uri`=:newuri WHERE `uri`=:olduri", ['newuri' => $sNewUri, 'olduri' => $sOldUri]);
    }

    function updateUri($sUri, $sModule, $sValue){
        return $this->query("UPDATE `sys_seo_links` SET `uri`=:value WHERE `module`=:module AND `uri`=:uri", ['module' => $sModule, 'uri' => $sUri, 'value' => $sValue]);
    }

    function removeUriById($iId){
        return $this->query("DELETE FROM `sys_seo_links` WHERE `id`=:id", ['id' => $iId]);
    }

    function removeUriByIdForUriAndModule($iId){
        $aData = $this->getUriById($iId);
        if (empty($aData))
            return false;

        return $this->query("DELETE FROM `sys_seo_links` WHERE `uri`=:uri AND `module`=:module", ['uri' => $aData['uri'], 'module' => $aData['module']]);
    }

    function updateUriByIdForUriAndModule($iId){
        $aData = $this->getUriById($iId);
        if (empty($aData))
            return false;

        $sUri = $this->convertToLatin($aData['uri']);
        return $this->query("UPDATE `sys_seo_links` SET `uri`=:value WHERE `uri`=:uri AND `module`=:module", ['value' => $sUri,'uri' => $aData['uri'], 'module' => $aData['module']]);
    }

    function getContentAsSQLPart($aFilter = [])
    {
        $aWhere = [];
        if (isset($aFilter['mode']) && (int)$aFilter['mode'])
            $aWhere[] = $this->_oConfig->CNF['NON-ASCII'];//"NOT HEX(`uri`) REGEXP '^([0-7][0-9A-F])*$'";

        if (isset($aFilter['module']) && $aFilter['module'])
            $aWhere[] = "`module`='" . bx_process_input($aFilter['module']) . "'";

        $sWhere = '';
        if (!empty($aWhere))
            $sWhere = 'AND ' . implode(' AND ', $aWhere);

        return ['where' => $sWhere];
    }

    function getModulesList(){
        $aModules = $this->getColumn("SELECT `m`.`name` FROM (SELECT `module` FROM `sys_seo_links`                                 
                                        GROUP BY `module`) as `l` 
                                        LEFT JOIN `sys_modules` as `m` on `m`.`name`=`l`.`module`
                                        WHERE `m`.`enabled` = 1 ORDER BY `m`.`name`");

        $aResult = [];
        foreach($aModules as &$sModule)
            $aResult[$sModule] = $sModule === 'system' ? _t('_adm_txt_module_system') : _t('_' . $sModule);

        return $aResult;
    }

    function convertToLatin($sUri, $sLang = '')
    {
        $aValues = $this->getLangs($sLang);
        if (empty($aValues))
            return false;

        $sStr = $sUri = mb_strtolower($sUri);
        $aAvailLangs = $this->_oConfig->CNF['SELECTED-LANGS'];
        foreach($aValues as $sKey => $aData) {
            if (in_array($sKey, $aAvailLangs))
               $sStr = strtr($sStr, $aData);
        }

        if (strcmp($sStr, $sUri) === 0)
            return $sStr;

        $sUri = $sStr;
        $sUri = mb_ereg_replace('[^-0-9a-z]', '-', $sUri);
        $sUri = mb_ereg_replace('[-]+', '-', $sUri);
        $sUri = trim($sUri, '-');

        return $sUri;
    }
}

/** @} */
