<?php defined('BX_DOL') or die('hack attempt');

/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Affiliate System Affiliate System
 * @ingroup     UnaModules
 *
 * @{
 */

require_once('AqbAffiliateDb.php');

class AqbAffiliateCloserTable extends AqbAffiliateDb {
    private $_oCNF = null;
    function __construct($oConfig){
        parent::__construct($oConfig);
        $this->_oCNF = &$oConfig->CNF;
    }

    private function nameMethodGetter($nameProperty, $obj)
    {
        if (preg_match('/^[a-z]{1}[0-9A-Za-z_]+$/', $nameProperty)) {
            $nameMethod = 'get'.ucfirst($nameProperty);
            if (method_exists($obj, $nameMethod)) {
                return $nameMethod;
            }
        }

        return false;
    }

    private function getProperty($nameProperty, ClosureTableData $obj)
    {
        $nameMethod = $this->nameMethodGetter($nameProperty, $obj);

        return ($nameMethod !== false)
            ? $obj->$nameMethod()
            : null;
    }

    /**
     * Return all profile's matrices as array
     * @param $iProfileId
     * @return array
     */
    public function getProfileMatrices($iProfileId){
        $aUsers = $this -> getMatrixUsersInfoByProfileId($iProfileId);
        if (empty($aUsers) || !$this -> hasEntry($iProfileId))
            return array();

        $aMatrices = array();
        foreach($aUsers as &$aUser) {
            $aMatrices[$aUser[$this->_oCNF['FPTREE_ID']]] = array(
                $this->_oCNF['FPTREE_MEM_LEVEL'] => $aUser[$this->_oCNF['FPTREE_MEM_LEVEL']],
                $this->_oCNF['BRANCH'] => $this->getProfileBranch($aUser[$this->_oCNF['FPTREE_ID']])
            );
        }

        return $aMatrices;
    }

    private function getMatrixUsersInfoByProfileId($iProfileId, $bFilled = null){
        $aWhere = array('profile_id' => $iProfileId);
        $sWhere = '';
        if (!is_null($bFilled)) {
            $aWhere[$this->_oCNF['FPTREE_FILLED']] = +$bFilled;
            $sWhere = "AND `{$this->_oCNF['FPTREE_FILLED']}`=:{$this->_oCNF['FPTREE_FILLED']}";
        }

        return $this -> getAll("SELECT * 
                                       FROM `{$this->_oCNF['TREE_PROFILES']}`
                                       WHERE `{$this->_oCNF['FPTREE_PROFILE_ID']}`=:profile_id {$sWhere}
                                       ORDER BY `{$this->_oCNF['FPTREE_ID']}`",
                                       $aWhere);
    }

    public function getMatrixUserInfo($iUserId){
        return $this -> getRow("SELECT *
                                       FROM `{$this->_oCNF['TREE_PROFILES']}`
                                       WHERE `{$this->_oCNF['FPTREE_ID']}`=:user_id
                                       LIMIT 1",
            array('user_id' => $iUserId));
    }

    private function getMatrixUserIdByProfileId($iProfileId, $bFilled = false){
        $aProfiles = $this -> getMatrixUsersInfoByProfileId($iProfileId, $bFilled);
        if (empty($aProfiles))
            return false;

        return current($aProfiles)[$this->_oCNF['FPTREE_ID']];
    }

    public function getProfileIdByUserId($iUserId){
        return $this -> getOne("SELECT `{$this->_oCNF['FPTREE_PROFILE_ID']}`
                                       FROM `{$this->_oCNF['TREE_PROFILES']}`
                                       WHERE `{$this->_oCNF['FPTREE_ID']}`=:user_id
                                       LIMIT 1",
                                       array('user_id' => $iUserId));
    }

    public function getUserId($iProfileId, $iMembershipLevel){
        return $this -> getOne("SELECT `{$this->_oCNF['FPTREE_ID']}`
                                           FROM `{$this->_oCNF['TREE_PROFILES']}`
                                           WHERE `{$this->_oCNF['FPTREE_PROFILE_ID']}`=:profile_id AND `{$this->_oCNF['FPTREE_MEM_LEVEL']}`=:mem_level  
                                           LIMIT 1",
            array(
                    'profile_id' => $iProfileId,
                    'mem_level' => $iMembershipLevel
                ));
    }

    public function hasEntry($idEntry)
    {
         if (!(int)$idEntry)
            return false;

        $iId = $this->getMatrixUserIdByProfileId($idEntry);
        if (!$iId)
            return false;

        $sSql = "SELECT COUNT(*)
                 FROM `{$this->_oCNF['TREE']}`
                 WHERE `{$this->_oCNF['FTREE_DES_ID']}`=:id";

        return $this -> getOne($sSql, array('id' => $iId));
    }

    public function getLevel($idEntry)
    {
         return (int)$this -> getOne("SELECT 
                        `{$this->_oCNF['FTREE_LEVEL']}`
                        FROM `{$this->_oCNF['TREE']}`
                        WHERE `{$this->_oCNF['FTREE_ANC_ID']}` = `{$this->_oCNF['FTREE_DES_ID']}` AND `{$this->_oCNF['FTREE_DES_ID']}` =:entry LIMIT 1", array('entry' => $idEntry));

    }

    /** Return the profile with the fewest number of users in matrix on defined level
     * @param $iParentProfileId main parent of the matrix
     * @param $iLevel
     * @return int|string|null
     */
    public function getNodeWithFewestEntries($iParentProfileId, $iLevel){
        $iUserId = $this->getMatrixUserIdByProfileId($iParentProfileId);
        $aAncestorList = $this -> getProfileBranch($iUserId, $iLevel /* + $iRootLevelValue*/); // because we need to use matrix's level value instead of real level
        $iMinProfileId = $iMinNumber = null;
        foreach($aAncestorList as $iProfileID => $iUserID) {
            $iProfilesCount = $this->getLevelProfilesCount($iProfileID, 1, true);
            if (!$iProfilesCount)
                 return $iProfileID;
            else {
                if (is_null($iMinNumber) || ($iProfilesCount < $iMinNumber)) {
                    $iMinNumber = $iProfilesCount;
                    $iMinProfileId = $iProfileID;
                }
            }
         }

        return $iMinProfileId;
    }

    /**
     * @param $idEntry Profile Id
     * @param int $iLevel real user's level or max level when you need to get the all levels of the matrix with count
     * @param bool $bExact if to return only selected level, otherwise the levels less then defined
     * @return mixed int/array profile number on exact level or the all profiles levels with profiles number on each of them, where level id it is normalized id, but not matrix id
     */
    public function getLevelProfilesCount($idEntry, $iLevel = 0, $bExact = false)
    {
        $iUserId = $this -> getMatrixUserIdByProfileId($idEntry);
        $iStartLevel = $this -> getLevel($iUserId);

        $aParams = array('entry_id' => $iUserId);
        $sFunc = 'getPairs';
        $sHaving = $sMax = '';
        if ($iLevel) {
            $aParams['max'] = $iLevel;
            if ($bExact)
                return (int)$this->getOne("SELECT COUNT(`{$this->_oCNF['FTREE_LEVEL']}`)                                                     
                                                   FROM `{$this->_oCNF['TREE']}`
                                                   WHERE `{$this->_oCNF['FTREE_ANC_ID']}`=:entry_id 
                                                          AND `{$this->_oCNF['FTREE_DES_ID']}` != `{$this->_oCNF['FTREE_ANC_ID']}` AND (`{$this->_oCNF['FTREE_LEVEL']}` - $iStartLevel)=:max
                                                   GROUP BY `{$this->_oCNF['FTREE_LEVEL']}`", $aParams);
            else
                $sHaving = "HAVING `{$this->_oCNF['FTREE_LEVEL']}` <=:max";
        }

        return $this -> $sFunc("SELECT 
                                 COUNT(`{$this->_oCNF['FTREE_LEVEL']}`) as `profiles`, 
                                 (`{$this->_oCNF['FTREE_LEVEL']}` - $iStartLevel) as `{$this->_oCNF['FTREE_LEVEL']}`  
                                 FROM `{$this->_oCNF['TREE']}`
                                 WHERE `{$this->_oCNF['FTREE_ANC_ID']}`=:entry_id AND `{$this->_oCNF['FTREE_DES_ID']}` != `{$this->_oCNF['FTREE_ANC_ID']}` {$sMax}
                                 GROUP BY `{$this->_oCNF['FTREE_LEVEL']}`
                                 {$sHaving}", $this->_oCNF['FTREE_LEVEL'], 'profiles', $aParams);
    }

   // public function getDes
    public function getNodeToAdd($iReferralId, &$aProgramInfo){
        $CNF = &$this->_oConfig->CNF;
        $aUserInfo = $this->getMatrixUsersInfoByProfileId($iReferralId);
        if (empty($aUserInfo) || ((int)$aUserInfo[$CNF['FPTREE_TOTAL']] < $aProgramInfo[$CNF['FMP_WIDTH']]))
            return $iReferralId;

        $iUserId = $aUserInfo[$CNF['FPTREE_ID']];
        $iParentLevel = $this->getLevel($iUserId);
        $aLevels = $this->getLevelProfilesCount($iUserId, /*$aProgramInfo[$CNF['FMP_DEEP']]*/ $CNF['MATRIX_WIDTH']);
        foreach($aLevels as &$aLevel){
            $iRealLevel = $aLevel[$CNF['FTREE_LEVEL']] - $iParentLevel;
            if (pow($aProgramInfo[$CNF['FMP_WIDTH']], $iRealLevel) > $aLevel['count']){
                $iProfileIdToAdd = $this->getNodeWithFewestEntries($iUserId, $aLevel[$CNF['FTREE_LEVEL']]);
            }
        }
    }
    private function createMatrixUser($iProfileId)
    {
        if (!$iProfileId)
            return false;

        $aMembershipInfo = BxDolAcl::getInstance()->getMemberMembershipInfo($iProfileId);
        if ($this->query("INSERT INTO `{$this->_oCNF['TREE_PROFILES']}`
                                      SET 
                                          `{$this->_oCNF['FPTREE_PROFILE_ID']}`=:profile,
                                          `{$this->_oCNF['FPTREE_MEM_LEVEL']}`=:mem_level            
                                ", array('profile' => $iProfileId, 'mem_level' => $aMembershipInfo['id']))) {
            $iPID = $this->lastId();
            $sSql = $this->prepare("INSERT INTO `{$this->_oCNF['TREE']}`
                            (
                                `{$this->_oCNF['FTREE_ANC_ID']}`, 
                                `{$this->_oCNF['FTREE_DES_ID']}`, 
                                `{$this->_oCNF['FTREE_N_ANC_ID']}`,  
                                `{$this->_oCNF['FTREE_SUBJECT_ID']}`,                                
                                `{$this->_oCNF['FTREE_LEVEL']}`
                            ) 
                            VALUES(?,?,?,?,?)", $iPID, $iPID, $iPID, $iPID, 0);

            return $this->query($sSql) ? $iPID : false;
        }
    }

    private function getMatrixUserId($iProfileId){
        return !$this->hasEntry($iProfileId)
                  ? $this->createMatrixUser($iProfileId)
                  : $this->getMatrixUserIdByProfileId($iProfileId);
    }

    public function getProfileMatrix($iUserId, $mixedLevelLimit = 0){
        $CNF = &$this->_oConfig->CNF;
        return $this -> getAll("SELECT * 
                                        FROM `{$CNF['TREE']}`
                                        LEFT JOIN `{$CNF['TREE_PROFILES']}` ON `{$CNF['FPTREE_ID']}` = `{$CNF['FTREE_DES_ID']}` 
                                        WHERE `{$CNF['FTREE_ANC_ID']}`=:user",
                                        array('user' => $iUserId));
    }

    /**
     * Mark profile's matrix as filled and set total number of profiles in it
     * @param $iProfileId real profile id
     * @param $iMemLevel membership level in moment when matrix is updated
     * @param $iWidth matrix width according to the settings
     * @param $iDeep max levels allowed for the matrix according to the settings
     * @return bool|int
     */
    public function markAsFilled($iProfileId, $iMemLevel, $iWidth, $iDeep){
        $aLevels = $this->getLevelProfilesCount($iProfileId, $iDeep);
        if (count($aLevels) !== $iDeep)
            return false;

        $iTotal = 0;
        foreach($aLevels as $iLevel => $iProfilesNumber){
            if (pow($iWidth, $iLevel) !== (int)$iProfilesNumber)
                return false;
            else
                $iTotal += $iProfilesNumber;
        }

        return $this->query("UPDATE `{$this->_oCNF['TREE_PROFILES']}` SET 
                                             `{$this->_oCNF['FPTREE_FILLED']}`=1,
                                             `{$this->_oCNF['FPTREE_TOTAL']}`=:total,
                                             `{$this->_oCNF['FPTREE_MEM_LEVEL']}`=:mem_level,
                                             `{$this->_oCNF['FPTREE_UPDATE']}`= NOW()
                                          WHERE `{$this->_oCNF['FPTREE_ID']}`=:profile_id
                                         ", array(
                                                    'total' => $iTotal,
                                                    'mem_level' => $iMemLevel,
                                                    'profile_id' => $this->getMatrixUserIdByProfileId($iProfileId))
                            );
    }
    /**
     * Add node under real profile id
     * @param $iReferral referral profile id
     * @param $iInvitee invited person's profile id
     * @param $idSubject
     * @return mixed operation subject
     */

    public function add($iReferral, $iInvitee, $idSubject = 0)
    {
        $iMReferral = $this -> getMatrixUserId($iReferral);
        $iMInvitee = $this -> getMatrixUserId($iInvitee);

        if (self::$_rLink->beginTransaction()) {
                $iLevel = $this->getLevel($iMReferral) + 1;
                if ($iLevel)
                {
                        $sSql = "REPLACE INTO `{$this->_oCNF['TREE']}`
                                (
                                    `{$this->_oCNF['FTREE_ANC_ID']}`, 
                                    `{$this->_oCNF['FTREE_DES_ID']}`, 
                                    `{$this->_oCNF['FTREE_N_ANC_ID']}`,  
                                    `{$this->_oCNF['FTREE_SUBJECT_ID']}`, 
                                    `{$this->_oCNF['FTREE_LEVEL']}`
                                )
                                SELECT `{$this->_oCNF['FTREE_ANC_ID']}`, {$iMInvitee}, {$iMReferral}, {$idSubject}, {$iLevel}
                                FROM `{$this->_oCNF['TREE']}`
                                WHERE `{$this->_oCNF['FTREE_DES_ID']}` = {$iMReferral}
                                UNION ALL
                                SELECT {$iMInvitee}, {$iMInvitee},{$iMReferral},{$idSubject},{$iLevel}";

                        if ($this->query($sSql))
                            return self::$_rLink-> commit();
                }
        }

        return self::$_rLink->rollBack();
    }

    /**
     * @param int $iUserId parent user Id
     * @param int $iLevel get descendants only from specified level
     * @return mixed
     */
    public function getProfileBranch($iUserId, $iLevel = 0)
    {
        $CNF = &$this->_oConfig->CNF;

        if (!$iUserId)
            return [];

        $aWhere = array('entry' => $iUserId);

        $iCurrentLevel = $this->getLevel($iUserId);
        $aUserInfo = $this->getMatrixUserInfo($iUserId);
        $aMatrixSettings = $this->getProgramByMembership($aUserInfo[$CNF['FPTREE_MEM_LEVEL']]); // get matrix settings according to matrix user settings but not using current profiles membership level, because they may be differ
        if (empty($aMatrixSettings))
            return [];

        $iMatrixDeep = $aMatrixSettings[$CNF['FMP_DEEP']];
        if (!(int)$iMatrixDeep)
            return [];

        $iMaxLevel = $iCurrentLevel + $iMatrixDeep;
        $sLevel = '';
        if ($iLevel){
          $sLevel = "AND `tree`.`{$this->_oCNF['FTREE_LEVEL']}`=:level" ;
          $aWhere['level'] = $iLevel + $iCurrentLevel;
        }

        $aDescendants = $this->getAllWithKey("SELECT 
                                                            `profiles`.`{$this->_oCNF['FPTREE_PROFILE_ID']}`, 
                                                            `profiles`.`{$this->_oCNF['FPTREE_ID']}`,
                                                            `{$this->_oCNF['FTREE_N_ANC_ID']}`,
                                                            `parent`.`{$this->_oCNF['FPTREE_PROFILE_ID']}` as `parent_id`
                                                        FROM `{$this->_oCNF['TREE']}` as `tree`
                                                        LEFT JOIN `{$this->_oCNF['TREE_PROFILES']}` as `profiles` ON `profiles`.`{$this->_oCNF['FPTREE_ID']}` = `tree`.`{$this->_oCNF['FTREE_DES_ID']}`
                                                        LEFT JOIN `{$this->_oCNF['TREE_PROFILES']}` as `parent` ON `parent`.`{$this->_oCNF['FPTREE_ID']}` = `tree`.`{$this->_oCNF['FTREE_N_ANC_ID']}`
                                                        WHERE `{$this->_oCNF['FTREE_ANC_ID']}`=:entry AND `{$this->_oCNF['FTREE_DES_ID']}` != `{$this->_oCNF['FTREE_ANC_ID']}` 
                                                               AND `tree`.`{$this->_oCNF['FTREE_LEVEL']}` <= {$iMaxLevel} {$sLevel}
                                                        ORDER BY `{$this->_oCNF['FTREE_N_ANC_ID']}`, `profiles`.`{$this->_oCNF['FPTREE_ID']}`", $this->_oCNF['FPTREE_PROFILE_ID'], $aWhere); //


        return !empty($aDescendants) ? $aDescendants : [];
    }

    /**
     * @param $iProfileId - profile id of the member from `sys_profiles`
     * @return array
     */
    public function getProfileAncestors($iProfileId)
    {
        $iUserId = $this->getMatrixUserIdByProfileId($iProfileId);
        return $iUserId ? $this->getAll("SELECT 
                                      `p`.`{$this->_oCNF['FPTREE_ID']}`, 
                                      `p`.`{$this->_oCNF['FPTREE_PROFILE_ID']}`,                                       
                                      (`t`.`{$this->_oCNF['FTREE_LEVEL']}` - `t2`.`{$this->_oCNF['FTREE_LEVEL']}`) as `{$this->_oCNF['FTREE_LEVEL']}`
                                      FROM `{$this->_oCNF['TREE']}` as `t`
                                      LEFT JOIN `{$this->_oCNF['TREE_PROFILES']}` as `p` ON `p`.`{$this->_oCNF['FPTREE_ID']}` = `t`.`{$this->_oCNF['FTREE_ANC_ID']}`
                                      INNER JOIN `{$this->_oCNF['TREE']}` as `t2` on `p`.`{$this->_oCNF['FPTREE_ID']}` = `t2`.`{$this->_oCNF['FTREE_ANC_ID']}` AND `t2`.`{$this->_oCNF['FTREE_DES_ID']}` = `t`.`{$this->_oCNF['FTREE_ANC_ID']}`
                                      WHERE `t`.`{$this->_oCNF['FTREE_DES_ID']}`=:entry AND `t`.`{$this->_oCNF['FTREE_DES_ID']}` != `t`.`{$this->_oCNF['FTREE_ANC_ID']}`", array('entry' => $iUserId))
        : array();
    }

    public function deleteAllUserSubTree($iUserId){
        return $this->query("DELETE FROM `{$this->_oCNF['TREE_PROFILES']}` 
                                        WHERE `{$this->_oCNF['FPTREE_ID']}` IN (
                                            SELECT `{$this->_oCNF['FTREE_DES_ID']}` 
                                            FROM `{$this->_oCNF['TREE']}` as `t`
                                            JOIN `{$this->_oCNF['TREE_PROFILES']}` as `p` ON `p`.`{$this->_oCNF['FPTREE_ID']}` = `t`.`{$this->_oCNF['FTREE_DES_ID']}`
                                            WHERE `t`.`{$this->_oCNF['FTREE_ANC_ID']}` = :id )", array('id' => $iUserId));
    }

    public function deleteUser($iUserId){
         return $this->query("DELETE FROM `{$this->_oCNF['TREE_PROFILES']}` WHERE `{$this->_oCNF['FPTREE_ID']}` = :id", array('id' => $iUserId));
    }

    public function deleteUserFromTree($iUserId){
        return $this->query("DELETE FROM `{$this->_oCNF['TREE']}` 
                                       WHERE `{$this->_oCNF['FTREE_DES_ID']}`=:id OR `{$this->_oCNF['FTREE_ANC_ID']}`=:id", array('id' => $iUserId));
    }

    public function deleteProfile($iProfileId)
    {
        $aProfiles = $this -> getMatrixUsersInfoByProfileId($iProfileId);
        if (empty($aProfiles))
            return false;

        foreach($aProfiles as &$aUser){
            $iUserId = (int)$aUser[$this->_oCNF['FPTREE_ID']];
            if ($iUserId) {
                $this->deleteUser($iUserId);
                $this->deleteUserFromTree($iUserId);
            }
        }
    }

    public function deleteProfileFromTheSponsorTree($iProfileId, $iSponsor){
        $iDesProfile = $this->getOne("
                                            SELECT `{$this->_oCNF['FTREE_DES_ID']}`
                                            FROM `{$this->_oCNF['TREE']}` as `t`
                                            JOIN `{$this->_oCNF['TREE_PROFILES']}` as `p` ON `p`.`{$this->_oCNF['FPTREE_ID']}` = `t`.`{$this->_oCNF['FTREE_ANC_ID']}`
                                            JOIN `{$this->_oCNF['TREE_PROFILES']}` as `p1` ON `p1`.`{$this->_oCNF['FPTREE_ID']}` = `t`.`{$this->_oCNF['FTREE_DES_ID']}`
                                            WHERE `p`.`{$this->_oCNF['FPTREE_PROFILE_ID']}` = :sponsor AND `p1`.`{$this->_oCNF['FPTREE_PROFILE_ID']}` = :profile", array('sponsor' => $iSponsor, 'profile' => $iProfileId));


        return $iDesProfile ? $this->deleteUserFromTree($iDesProfile) : false;
    }
}
