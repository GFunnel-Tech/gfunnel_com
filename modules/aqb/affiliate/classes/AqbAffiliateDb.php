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

bx_import('BxDolDb');

class AqbAffiliateDb extends BxBaseModGeneralDb
{	
	public function __construct(&$oConfig)
	{
		parent::__construct($oConfig);			
	}

    function getContentAsSQLPart($sType = 'history', $iProfileId = 0)
    {
        $CNF = &$this->_oConfig->CNF;

        $sJoin = $sFields = '';
        $sWhere = "WHERE ";
        switch($sType){
            case 'history':
                $sJoin = "LEFT JOIN `{$CNF['INFO']}` as `i` ON `i`.`{$CNF['FI_PROFILE']}` = `p`.`id`";
                $sJoin .= "LEFT JOIN `{$CNF['REFERRALS']}` as `r` ON `r`.`{$CNF['FR_INVITEE']}` = `p`.`id`";
                $sFields = ",`r`.`{$CNF['FR_REFERRAL']}` as `referral`
                            ,`i`.`{$CNF['FI_REFERRED']}` as `referred`
                            ,`i`.`{$CNF['FI_IMPRESSIONS']}` as `impressions`
                            ,`i`.`{$CNF['FI_CLICKS']}` as `clicks`
                            ,`i`.`{$CNF['FI_UPGRADES']}` as `upgrades`
                            ,`i`.`{$CNF['FI_PURCHASES']}` as `purchases`
                            ,`i`.`{$CNF['FI_MARKETS']}` as `markets`
                            ,`i`.`{$CNF['FI_COMMISSIONS']}` as `commission`
                            ,`i`.`{$CNF['FI_COMMISSIONS']}` as `earned`
                            ,`i`.`{$CNF['FI_POINTS']}` as `points`
                            ,`i`.`{$CNF['FI_CREDITS']}` as `credits`";

                break;
            case 'activities':
                $sJoin = "LEFT JOIN `sys_profiles` AS `p` ON `p`.`id` = `h`.`performer`
                          LEFT JOIN `{$CNF['BANNERS']}` AS `b` ON `b`.`{$CNF['FB_ID']}` = `h`.`{$CNF['FH_PERFORMER']}` AND `h`.`{$CNF['FH_TYPE']}` = '" . AQB_AFF_TYPE_BANNER . "'
                          LEFT JOIN `sys_accounts` AS `a` ON `a`.`id` = `p`.`account_id`";

                $sFields = ", IF (`a`.`email` IS NULL, 'unknown', `a`.`email`) as `email`, 
                              IF (`h`.`{$CNF['FH_TYPE']}` != '" . AQB_AFF_TYPE_BANNER . "', '', `b`.`{$CNF['FB_TITLE']}`) as `banner_title`";
                $aWhereQuery[] = " `h`.`{$CNF['FH_OWNER']}`= {profile_id} ";
                break;

            case 'referrals':
                $sJoin = "RIGHT JOIN `{$CNF['REFERRALS']}` as `r` ON `r`.`{$CNF['FR_INVITEE']}` = `p`.`id`";
                $sJoin .= "RIGHT JOIN `sys_accounts` as `a` ON `a`.`id` = `p`.`account_id`";
                $sFields = ",`r`.`{$CNF['FR_ID']}` as `ref_id`
                            ,`r`.`{$CNF['FR_INVITEE']}` as `referred`
                            ,`r`.`{$CNF['FR_ADDED']}` as `added`
                            ,`r`.`{$CNF['FR_REFERRAL']}` as `referral`
                            ,`r`.`{$CNF['FR_BANNER']}` as `banner`
                            ,`a`.`email` as `email`                            
                            ,`r`.`{$CNF['FR_TYPE']}` as `ref_type`";

                $aWhereQuery[] = "`r`.`{$CNF['FR_REFERRAL']}`={profile_id}";
                break;

            case 'requests':
                $sJoin = "INNER JOIN `{$CNF['TRANSACTIONS']}` as `t` ON `p`.`id` = `t`.`{$CNF['FC_PROFILE_ID']}`";
                $sFields = ", `t`.`{$CNF['FC_ID']}` as `id`,
                              `t`.`{$CNF['FC_POINTS']}`, 
                              `t`.`{$CNF['FC_AMOUNT']}`,
                              `t`.`{$CNF['FC_CREDITS']}`,
                              `t`.`{$CNF['FC_STATUS']}`, 
                              `t`.`{$CNF['FC_ADDED']}`,
                              `t`.`{$CNF['FC_PAID']}`,
                              `t`.`{$CNF['FC_TXN']}`
                              ";
                break;
        }

        /* Join with persons and organizations */
        $aPersonModules = array();
        if ($this -> isModuleByName('bx_persons')) {
            $sJoin .= "LEFT JOIN `bx_persons_data` as `pr` ON `pr`.`id` = `p`.`content_id`";
            $aPersonModules[] = "bx_persons";
        }

        if ($this -> isModuleByName('bx_organizations')) {
            $sJoin .= "LEFT JOIN `bx_organizations_data` as `org` ON `org`.`id` = `p`.`content_id`";
            $aPersonModules[] = "bx_organizations";
        }

        if (!empty($aPersonModules)) {
            if ($sType !== 'activities')
                $aWhereQuery[] = "(`p`.`type` IN ('" . implode("','", $aPersonModules) . "') OR `p`.`type` IS NULL)";

            if (count($aPersonModules) > 1)
                $sFields .= ", IF (`p`.`type` = 'bx_organizations', `org`.`org_name`, `pr`.`fullname`) as `fullname`";
            elseif (in_array('bx_persons', $aPersonModules))
                $sFields .= ", IF (`pr`.`fullname` IS NULL, 'unknown', `pr`.`fullname`) as `fullname`";
            else
                $sFields .= ", IF (`org`.`org_name` IS NULL, 'unknown', `pr`.`fullname`) as `fullname`";
        }

        if (!empty($aWhereQuery))
            $sWhere = 'WHERE ' . implode(' AND ', $aWhereQuery);

        return array(
            'fields' => $sFields,
            'join' =>  $sJoin,
            'where' => $sWhere
        );
    }

    public function isPointsEnabled()
    {
        return $this->_oConfig->CNF['USE_POINTS'] && $this->isEnabled('points');
    }

    public function isCreditsEnabled()
    {
        return $this->_oConfig->CNF['USE_CREDITS'] && $this->isEnabled('credits');
    }

    public function isFiatEnabled()
    {
        return $this->_oConfig->CNF['USE_FIAT'] && $this->isEnabled('payment');
    }

    /**
     * Return referrals table info by invitee profile id
     * @param $iProfileId
     * @return array|mixed
     */
    public function getRefInfoByInviteeId($iProfileId){
        $CNF = &$this->_oConfig->CNF;
	    return $this -> getRow("SELECT * FROM `{$CNF['REFERRALS']}` 
                                           WHERE `{$CNF['FR_INVITEE']}`= :profile LIMIT 1", array('profile' => $iProfileId));
	}

    /**
     * Returns referrals table info by referral profile id
     * it may be count or епу list of the all invited profiles
     * @param $iReferralId
     * @param false $bCount
     * @return array|false|mixed
     */
    public function getRefInfoByRefId($iReferralId, $bCount = false){
        $CNF = &$this->_oConfig->CNF;

        if(!$bCount)
            return $this -> getAll("SELECT * FROM `{$CNF['REFERRALS']}` 
                                           WHERE `{$CNF['FR_REFERRAL']}`= :referral", array('referral' => $iReferralId));

        return $this -> getOne("SELECT COUNT(*) FROM `{$CNF['REFERRALS']}` 
                                           WHERE `{$CNF['FR_REFERRAL']}`= :referral", array('referral' => $iReferralId));
    }

    public function getProfileActivity($iProfileId, $bCount = false, $sField = ''){
        $CNF = &$this->_oConfig->CNF;

        if (!$sField)
            return $this->getAllWithKey("SELECT 
                                         SUM(`{$CNF['FH_COUNT']}`) as `{$CNF['FH_COUNT']}`,
                                         SUM(`{$CNF['FH_CM']}`) as `{$CNF['FH_CM']}`,
                                         SUM(`{$CNF['FH_POINTS']}`) as `{$CNF['FH_POINTS']}`,
                                         SUM(`{$CNF['FH_CREDITS']}`) as `{$CNF['FH_CREDITS']}`,
                                        `{$CNF['FH_ACTION']}` 
                                        FROM `{$CNF['HISTORY']}` 
                                        WHERE `{$CNF['FH_OWNER']}`=:profile  
                                        GROUP BY `{$CNF['FH_ACTION']}`", $CNF['FH_ACTION'], array('profile' => $iProfileId));


        if ($bCount)
            return $this->getOne("SELECT 
                                                SUM(`{$CNF['FH_COUNT']}`) as `{$CNF['FH_COUNT']}`
                                                FROM `{$CNF['HISTORY']}` 
                                                WHERE `{$CNF['FH_OWNER']}`=:profile AND `{$CNF['FH_ACTION']}`=:{$CNF['FH_ACTION']}  
                                                GROUP BY `{$CNF['FH_ACTION']}`", array(
                'profile' => $iProfileId,
                $CNF['FH_ACTION'] => $sField
            ));

        return $this->getAll("SELECT 
                                           *
                                           FROM `{$CNF['HISTORY']}` 
                                           WHERE `{$CNF['FH_OWNER']}`=:profile AND `{$CNF['FH_ACTION']}`=:'{$CNF['FH_ACTION']}'  
                                           GROUP BY `{$CNF['FH_ACTION']}`", [
            'profile' => $iProfileId,
            $CNF['FH_ACTION'] => $sField
        ]);
    }

    public function saveReferral($iProfileId, $iReferralId){
        $CNF = &$this->_oConfig->CNF;
        $aReferral = $this -> getRefInfoByInviteeId($iProfileId);

        if (empty($aReferral))
            $bResult = $this -> query("INSERT INTO `{$CNF['REFERRALS']}` 
                                   SET 
                                        `{$CNF['FR_REFERRAL']}`=:ref,
                                        `{$CNF['FR_INVITEE']}`=:prof,
                                        `{$CNF['FR_INVITEE_A']}`=:account,
                                        `{$CNF['FR_TYPE']}`=:type,
                                        `{$CNF['FR_ADDED']}`=UNIX_TIMESTAMP()",
                array('ref' => $iReferralId, 'prof' => $iProfileId, 'type' => AQB_AFF_TYPE_REF, 'account' => BxDolProfile::getInstance($iReferralId) -> getAccountId()));
        else
            $bResult = $this -> query("UPDATE `{$CNF['REFERRALS']}` 
                                   SET 
                                        `{$CNF['FR_REFERRAL']}`=:ref,
                                        `{$CNF['FR_INVITEE']}`=:prof,                                        
                                        `{$CNF['FR_ADDED']}`=UNIX_TIMESTAMP()
                                   WHERE `{$CNF['FR_ID']}`= :id",
            array('ref' => $iReferralId, 'prof' => $iProfileId, 'id' => $aReferral[$CNF['FR_ID']]));


        return $bResult ? $this -> updateUserInfo($iReferralId, $CNF['FI_REFERRED']) : false;
    }

    /**
     * Removes the all referred profiles by referral id
     * @param $iProfileId
     * @return false|int
     */
    public function removeReferral($iProfileId){
        $CNF = &$this->_oConfig->CNF;
        $bResult = $this -> query("DELETE FROM `{$CNF['REFERRALS']}` 
                                   WHERE
                                        `{$CNF['FR_REFERRAL']}`=:profile",
                                 array('profile' => $iProfileId));

        $oMatrix =  new AqbAffiliateCloserTable($this->_oConfig);
        $oMatrix->deleteProfile($iProfileId);

        return $bResult ? $this -> updateUserInfo($iProfileId, $CNF['FI_REFERRED']) : false;
    }

    public function removeReferred($mixedIds = array()){
        $CNF = &$this->_oConfig->CNF;
	    if (empty($mixedIds))
            return false;

	    if (!is_array($mixedIds))
            $mixedIds = [(int)$mixedIds];

	    $aReferrals = array();
        foreach($mixedIds as &$iInviteeId) {
            $aReferral = $this->getRefInfoByInviteeId($iInviteeId);
            if (!empty($aReferral))
                $aReferrals[$aReferral[$CNF['FR_REFERRAL']]][] = $iInviteeId;
        }

        $sWHERE = "WHERE `{$CNF['FR_INVITEE']}` IN (" . implode(",", $mixedIds) . ")";
        $bResult = $this -> query("DELETE FROM `{$CNF['REFERRALS']}` {$sWHERE}");
        if (!$bResult)
            return false;

        $oMatrix =  new AqbAffiliateCloserTable($this->_oConfig);
        foreach($aReferrals as $iSponsorId => $aInvitee) {
            $this->updateUserInfo($iSponsorId, $CNF['FI_REFERRED']);

            foreach($aInvitee as &$iInvitee)
                $oMatrix->deleteProfileFromTheSponsorTree($iInvitee, $iSponsorId);
        }

        return $bResult;
    }

    public function removeReferralsById($mixedIds = array()){
        $CNF = &$this->_oConfig->CNF;
        if (empty($mixedIds))
            return false;

        if (!is_array($mixedIds))
            $mixedIds = [(int)$mixedIds];

        $sWHERE = "WHERE `{$CNF['FR_ID']}` IN (" . implode(",", $mixedIds) . ")";
        $sQuery = "SELECT * FROM `{$CNF['REFERRALS']}` {$sWHERE}";

        $aReferrals = $this->getAll($sQuery);
        if (empty($aReferrals))
            return false;

        $bResult = $this -> query("DELETE FROM `{$CNF['REFERRALS']}` {$sWHERE}");
        $oMatrix =  new AqbAffiliateCloserTable($this->_oConfig);
        foreach($aReferrals as &$aReferral) {
            $this->updateUserInfo($aReferral[$CNF['FR_REFERRAL']], $CNF['FI_REFERRED']);
            $oMatrix->deleteProfileFromTheSponsorTree($aReferral[$CNF['FR_INVITEE']], $aReferral[$CNF['FR_REFERRAL']]);
        }

        return $bResult;
    }

    public function removeEmptyReferral($iAccountId){
        $CNF = &$this->_oConfig->CNF;

        return $this->query("DELETE FROM `{$CNF['REFERRALS']}` 
                              WHERE `{$CNF['FR_INVITEE_A']}`=:id AND `{$CNF['FR_INVITEE']}`=0", array('id' => $iAccountId));
    }

    function unregisterProfile($iProfileId){
        $this->removeReferred($iProfileId);
        $this->removeReferral($iProfileId);
        $this->removeCommissionItem($iProfileId);
        $this->removeUserInfo($iProfileId);
    }

    function getUserInfo($iProfileId){
        $CNF = &$this->_oConfig->CNF;
        return $this->getRow("SELECT *
                                   FROM `{$CNF['INFO']}` 
                                   WHERE `{$CNF['FI_PROFILE']}`=:profile", array('profile' => $iProfileId));
    }

    function updateUserInfo($iProfileId, $sField = ''){
        $CNF = &$this->_oConfig->CNF;

        if (!$iProfileId || !BxDolProfile::getInstance($iProfileId))
            return false;

        $aUserInfo = $this -> getUserInfo($iProfileId);
        if (empty($aUserInfo))
            $this -> query("INSERT INTO `{$CNF['INFO']}`
                                      SET `{$CNF['FI_PROFILE']}`=:{$CNF['FI_PROFILE']}",
                array($CNF['FI_PROFILE'] => $iProfileId));

        $aProfileActivity = $this->getProfileActivity($iProfileId);
        $iReferred = (int)$this -> getRefInfoByRefId($iProfileId, true);
        if ($iReferred)
            $aProfileActivity[$CNF['FI_REFERRED']] = [
                $CNF['FH_COUNT'] => $iReferred,
                $CNF['FH_POINTS'] => 0,
                $CNF['FH_CREDITS'] => 0,
                $CNF['FH_CM'] => 0,
                $CNF['FH_ACTION'] => $CNF['FI_REFERRED']
            ];


        if ($sField === $CNF['FI_REFERRED'] && $iReferred) {
            return $this->query("UPDATE `{$CNF['INFO']}` 
                                          SET 
                                                `{$sField}`=:{$sField}                                                
                                          WHERE `{$CNF['FI_PROFILE']}`=:{$CNF['FI_PROFILE']}",
                array(
                    $sField => isset($aProfileActivity[$sField]) ? $aProfileActivity[$sField][$CNF['FH_COUNT']] : $this->getProfileActivity($iProfileId, true, $sField),
                    $CNF['FI_PROFILE'] => $iProfileId
                ));
        }

        if (empty($aProfileActivity))
            return $this -> query("REPLACE INTO `{$CNF['INFO']}` SET `{$CNF['FI_PROFILE']}`=:{$CNF['FI_PROFILE']}",
                array(
                    $CNF['FI_PROFILE'] => $iProfileId
                ));

        $aSet = [];
        $fCredits = $fPoints = $fCM = 0;
        foreach($aProfileActivity as $sKey => $aActivity){
            $aSet[$sKey] = $aActivity[$CNF['FH_COUNT']];

            $fPoints += $aActivity[$CNF['FH_POINTS']];
            $fCM += $aActivity[$CNF['FH_CM']];
            $fCredits += $aActivity[$CNF['FH_CREDITS']];
        }

        if (empty($aSet))
            return false;

        if ($this->isPointsEnabled())
            $aSet[$CNF['FI_POINTS']] = bx_srv('aqb_points', 'get_format_points', [$fPoints]);

        if ($this->isCreditsEnabled())
            $aSet[$CNF['FI_CREDITS']] = $this->_oConfig->number_format($fCredits);

        if ($this->isFiatEnabled())
            $aSet[$CNF['FI_COMMISSIONS']] = $fCM;

        $aFields = $this->getFields($CNF['INFO']);
        foreach($aFields['original'] as &$sField){
            if (!isset($aSet[$sField]) && !in_array($sField, [$CNF['FI_ID'], $CNF['FI_PROFILE'], $CNF['FI_UPDATE']]))
                $aSet[$sField] = 0;
        }

        return $this -> query("UPDATE `{$CNF['INFO']}` 
                                            SET " . $this->arrayToSQL($aSet) . ",
                                                `{$CNF['FI_UPDATE']}` = UNIX_TIMESTAMP() 
                                            WHERE `{$CNF['FI_PROFILE']}`=:{$CNF['FI_PROFILE']}",
            array(
                $CNF['FI_PROFILE'] => $iProfileId
            ));
    }

    function removeUserInfo($iProfileId){
        $CNF = &$this->_oConfig->CNF;
        return $this -> query("DELETE FROM `{$CNF['INFO']}` WHERE `{$CNF['FI_PROFILE']}` = :profile", array('profile' => $iProfileId));
    }

    function getBannerInfo($iId, $bHash = false){
        $CNF = &$this->_oConfig->CNF;
        if ($bHash)
            return $this->getRow("SELECT * FROM `{$CNF['BANNERS']}` WHERE MD5(`{$CNF['FB_ID']}`) = :id LIMIT 1", array('id' => $iId));

        return $this -> getRow("SELECT * FROM `{$CNF['BANNERS']}` WHERE `{$CNF['FB_ID']}` = :id LIMIT 1", array('id' => $iId));
    }

    function getBannersList($mixedActive = ''){
        $CNF = &$this->_oConfig->CNF;

        $sWhere = '';
        $aWhere = array();
        if ($mixedActive === TRUE) {
            $sWhere = "WHERE `{$CNF['FB_ACTIVE']}`=:{$CNF['FB_ACTIVE']}";
            $aWhere[$CNF['FB_ACTIVE']] = 1;
        }
        else
        if ($mixedActive === FALSE)
        {
            $sWhere = "WHERE `{$CNF['FB_ACTIVE']}`=:{$CNF['FB_ACTIVE']}";
            $aWhere[$CNF['FB_ACTIVE']] = 0;
        }

        return $this -> getAll("SELECT * FROM `{$this->_oConfig->CNF['BANNERS']}` {$sWhere}", $aWhere);
    }

    function getBannerCommissionInfo($iId){
        $CNF = &$this->_oConfig->CNF;
        $aBannerInfo = $this -> getBannerInfo($iId);
        if (empty($aBannerInfo) || !$aBannerInfo[$CNF['FB_COMMISSION']])
            return false;

        return @unserialize($aBannerInfo[$CNF['FB_COMMISSION']]);
    }

    function updateBanner($iId, $sField, $sValue){
        $CNF = &$this->_oConfig->CNF;
        return $this -> query("UPDATE `{$CNF['BANNERS']}` SET `{$sField}` =:value WHERE `{$CNF['FB_ID']}` = :id LIMIT 1", array('id' => $iId, 'value' => $sValue));
    }

    function deleteBanner($iId){
        $CNF = &$this->_oConfig->CNF;
        return $this -> query("DELETE FROM `{$CNF['BANNERS']}` WHERE `{$CNF['FB_ID']}` = :id", array('id' => $iId));
    }

    function getProgramsList($bActiveOnly = false){
        $CNF = &$this->_oConfig->CNF;

        $sWhere = '';
        if ($bActiveOnly)
            $sWhere = "WHERE `{$CNF['FMP_ACTIVE']}` = 1";

        return $this -> getAll("SELECT * 
                                           FROM `{$CNF['PROGRAMS']}`
                                           {$sWhere}
                                           ORDER BY `{$CNF['FMP_ID']}`");
    }

    function updateProgram($iId, $sField, $mixedValue){
        $CNF = &$this->_oConfig->CNF;
        return $this -> query("UPDATE `{$CNF['PROGRAMS']}` 
                                     SET `{$sField}` =:value 
                                     WHERE `{$CNF['FMP_ID']}` = :id", array('id' => $iId, 'value' => $mixedValue));
    }

    function getProgramById($iId){
        $CNF = &$this->_oConfig->CNF;
        return $this -> getRow("SELECT * 
                                           FROM `{$CNF['PROGRAMS']}`
                                           WHERE `{$CNF['FMP_ID']}` =:id", array('id' => $iId));
    }

    function getProgramByMembership($iId){
        $CNF = &$this->_oConfig->CNF;
        return $this -> getRow("SELECT * 
                                           FROM `{$CNF['PROGRAMS']}`
                                           WHERE `{$CNF['FMP_MEMLEVEL']}` =:id", array('id' => $iId)
        );
    }

    function deleteProgram($iId){
        $CNF = &$this->_oConfig->CNF;
        return $this -> query("DELETE FROM `{$CNF['PROGRAMS']}` 
                                          WHERE `{$CNF['FMP_ID']}` =:id",
                                          array('id' => $iId));
    }

    function removeMatrixLevelCommissions($iProgramId){
        $CNF = &$this->_oConfig->CNF;
	    return $this->query("DELETE FROM `{$CNF['MATRIX_COMMISSIONS']}` 
                                   WHERE `{$CNF['FMC_PROGRAM_ID']}`=:program",
                            array('program' => $iProgramId));
    }
    function saveMatrixCommission($iProgram, &$aData){
        $CNF = &$this->_oConfig->CNF;
	    if (empty($aData) || !($aProgram = $this -> getProgramById($iProgram)))
	        return false;

	    $this->removeMatrixLevelCommissions($iProgram);
	    $bExists = false;
	    foreach($aData['levels'] as $iKey => $iLevel){
            $aCommission = array();
	        if (!(int)$iLevel)
	            continue;

	        $aModules = $this->getAvailableCommissionsModule();
	        foreach($aModules as &$sAction)
	          if (isset($aData[$sAction]) && ($aData[$sAction][$iKey] || $aData["{$sAction}_points"][$iKey] || $aData["{$sAction}_credits"][$iKey])) {
                  $aCommission[$sAction] = [
                      'currency' => $aData[$sAction][$iKey],
                      'points' => $aData["{$sAction}_points"][$iKey],
                      'credits' => $aData["{$sAction}_credits"][$iKey],
                  ];

                  if (isset($aData["{$sAction}_percentage"][$iLevel]))
                      $aCommission[$sAction]['percentage'] = 1;
            }
	        else
	            continue;

	        if (!empty($aCommission)) {
                $this->query("REPLACE INTO `{$CNF['MATRIX_COMMISSIONS']}`
                                          SET 
                                            `{$CNF['FMC_PROGRAM_ID']}`=:program,
                                            `{$CNF['FMC_LEVEL']}`=:level,
                                            `{$CNF['FMC_COMMISSION']}`=:commission", [
                    'program' => $iProgram,
                    'level' => $iLevel,
                    'commission' => @serialize($aCommission)
                ]);

                $bExists = true;
            }
        }

	    return $bExists;
    }

    function getMatrixLevelsCommission($iProgramId, $iLevel = 0){
        $CNF = &$this->_oConfig->CNF;

        if ($iLevel) {
            $sCommission = $this->getOne("SELECT `{$CNF['FMC_COMMISSION']}`
                                          FROM `{$CNF['MATRIX_COMMISSIONS']}` 
                                          WHERE `{$CNF['FMC_PROGRAM_ID']}`=:id AND `{$CNF['FMC_LEVEL']}`=:level",
                                         ['id' => $iProgramId, 'level' => $iLevel]);

            return $sCommission ? unserialize($sCommission) : false;
        }


        $aResult = $this -> getPairs("SELECT * FROM `{$CNF['MATRIX_COMMISSIONS']}` 
                                         WHERE `{$CNF['FMC_PROGRAM_ID']}`=:id",
                                                 $CNF['FMC_LEVEL'],
                                                 $CNF['FMC_COMMISSION'], ['id' => $iProgramId]);

	    $aLevels = array();
	    foreach($aResult as $iKey => $sValue)
            $aLevels[$iKey] = @unserialize($sValue);

	    return $aLevels;
    }

    function getAlreadySentEmailsNumber($iProfileId){
        $CNF = &$this->_oConfig->CNF;
	    return (int)$this -> getOne("SELECT SUM(`count`) 
                                           FROM `{$CNF['INVITATIONS_TRACK']}`
                                           WHERE `{$CNF['FIN_SENDER']}`=:profile AND `{$CNF['FIN_DATE']}` > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 24 HOUR))",
                                    array('profile' => $iProfileId));
    }

    function registerInvitation($iProfileId, $sEmail){
        $CNF = &$this->_oConfig->CNF;
        if (!$sEmail)
            return false;

        $sWhere = "WHERE `{$CNF['FIN_RES_EMAIL']}`=:email AND `{$CNF['FIN_SENDER']}`=:sender";
        $aFields = array('email' => $sEmail, 'sender' => $iProfileId);
        $bIsAlreadySent = $this -> getOne("SELECT COUNT(*) 
                                                      FROM `{$CNF['INVITATIONS_TRACK']}` 
                                                      {$sWhere}",
                                                      $aFields);
        if ($bIsAlreadySent)
            return $this -> query("UPDATE `{$CNF['INVITATIONS_TRACK']}` 
                                                      SET `{$CNF['FIN_COUNT']}` = `{$CNF['FIN_COUNT']}` + 1, `date` = UNIX_TIMESTAMP() {$sWhere}", $aFields);

        return $this -> query("INSERT INTO `{$CNF['INVITATIONS_TRACK']}` 
                                                      SET 
                                                         `date` = UNIX_TIMESTAMP(),
                                                         `{$CNF['FIN_SENDER']}`=:sender,
                                                         `{$CNF['FIN_RES_EMAIL']}`=:email", $aFields);
    }

    function registerReferredAccount($iReferral, $iAccountId, $iBanner = 0){
        $CNF = &$this->_oConfig->CNF;
        return $this -> query("REPLACE INTO `{$CNF['REFERRALS']}`  
                                        SET 
                                          `{$CNF['FR_REFERRAL']}`=:referral,
                                          `{$CNF['FR_INVITEE_A']}`=:account,
                                          `{$CNF['FR_ADDED']}`=UNIX_TIMESTAMP(),
                                          `{$CNF['FR_TYPE']}`=:type,
                                          `{$CNF['FR_BANNER']}`=:banner",
                                        array(
                                               'referral' => $iReferral,
                                               'account' => $iAccountId,
                                               'type' => $iBanner ? AQB_AFF_TYPE_BANNER : AQB_AFF_TYPE_REF,
                                               'banner' => $iBanner
                                        ));
    }

    function registerReferredProfile($iProfileId){
        $CNF = &$this->_oConfig->CNF;

        $iAccountId = (int)BxDolProfile::getInstance($iProfileId)->getAccountId();
        if (!$iAccountId)
            return false;

        $aReferralInfo = $this->getRow("SELECT *
                                         FROM `{$CNF['REFERRALS']}` 
                                         WHERE `{$CNF['FR_INVITEE_A']}`=:account
                                         ORDER BY `{$CNF['FR_INVITEE']}`
                                         LIMIT 1",
                                         array('account' => $iAccountId));
        if (empty($aReferralInfo))
            return false;

        if (!(int)$aReferralInfo[$CNF['FR_INVITEE']])
            $bResult = $this -> query("UPDATE `{$CNF['REFERRALS']}`  
                                          SET 
                                             `{$CNF['FR_INVITEE']}`=:profile,
                                             `{$CNF['FR_ADDED']}`=UNIX_TIMESTAMP()
                                          WHERE `{$CNF['FR_ID']}`=:id",
                                          array(
                                                'profile' => $iProfileId,
                                                'id' => $aReferralInfo[$CNF['FR_ID']]
                                          ));
        else
            $bResult = $CNF['ACCOUNT_ALL_PROFILES'] ? $this -> query("INSERT INTO `{$CNF['REFERRALS']}`  
                                          SET 
                                             `{$CNF['FR_REFERRAL']}`=:referral,
                                             `{$CNF['FR_INVITEE']}`=:profile,
                                             `{$CNF['FR_INVITEE_A']}`=:account,
                                             `{$CNF['FR_ADDED']}`=UNIX_TIMESTAMP(),
                                             `{$CNF['FR_BANNER']}`=:banner",
                                            array(
                                                'referral' => $aReferralInfo[$CNF['FR_REFERRAL']],
                                                'account' => $iAccountId,
                                                'profile' => $iProfileId,
                                                'banner' => $aReferralInfo[$CNF['FR_BANNER']]
                                            )) : false;

        return $bResult;
    }

    public function addProfileToTheMatrix($iInviteeId){
        $CNF = &$this->_oConfig->CNF;

        $iSponsorId = $this->getSponsorProfileId($iInviteeId);
        if (!$iSponsorId)
            return false;

        $aMatrixSettings = $this->getProfileProgram($iSponsorId);
        if (empty($aMatrixSettings) || !(int)$aMatrixSettings[$CNF['FMP_ACTIVE']])
            return false;

        // don't add to the matrix if invitee has no selected membership level
        $aInviteeMembershipInfo = BxDolAcl::getInstance()->getMemberMembershipInfo($iInviteeId);
        if ((int)$aMatrixSettings[$CNF['FMP_MEMTOADD']] && (int)$aInviteeMembershipInfo['id'] !== (int)$aMatrixSettings[$CNF['FMP_MEMTOADD']])
            return false;

        $oMatrix =  new AqbAffiliateCloserTable($this->_oConfig);
        $iDeep = (int)$aMatrixSettings[$CNF['FMP_DEEP']];
        $iWidth = (int)$CNF['MATRIX_WIDTH'];

        if (!$iWidth) {
            $this->_oConfig->logData(" ---- Sponsor's {$iInviteeId} matrix has unlimited width:", false);
            $oMatrix->add($iSponsorId, $iInviteeId);
            $this->_oConfig->logData(" ---- Profile {$iInviteeId} was added to the matrix of {$iSponsorId} profile ---", false);
        }

        $aLevels = $oMatrix->getLevelProfilesCount($iSponsorId, $iDeep);
        if (empty($aLevels)) {
            $this->_oConfig->logData(" ---- Sponsor {$iSponsorId} has no levels in matrix yet (available width-{$iWidth}/deep-{$iDeep}):", false);
            $oMatrix->add($iSponsorId, $iInviteeId);
            $this->_oConfig->logData(" ---- Profile {$iInviteeId} was added to the matrix of {$iSponsorId} profile ---", false);
            return true;
        }

        $this->_oConfig->logData(" ---- Sponsor {$iSponsorId} has these levels in matrix: ", false);
        $this->_oConfig->logData($aLevels, false);

        if((int)$aMatrixSettings[$CNF['FMP_SPILLOVER']]) {
            $this->_oConfig->logData(" ---- Sponsor's {$iSponsorId} spillover is enabled, add invitee using it:", false);
            $iProfileIdToAdd = 0;
            $iLastLevel = 0;

            // if Sponsor is already has some number of levels with some number of users in them,
            // then trying to find profile with fewest profiles number to add new one invitee

            $this->_oConfig->logData(" ---- Trying to find profile to add new invitee in matrix of {$iSponsorId} to one of the down-line existed levels:", false);
            foreach ($aLevels as $iLevel => $iCount) {
                $iLastLevel = (int)$iLevel;
                $this->_oConfig->logData(" ---- Sponsor's {$iSponsorId} level {$iLevel} has {$iCount} profiles:", false);

                if (pow($iWidth, $iLevel) > $iCount) { // check profile number on level
                    $this->_oConfig->logData(" ---- level {$iLevel} is compatible to add invitee {$iInviteeId}: ", false);
                    if ($iLevel > 1) {
                        $iProfileIdToAdd = $oMatrix->getNodeWithFewestEntries($iSponsorId, $iLevel - 1);
                        $this->_oConfig->logData(" ---- selected {$iProfileIdToAdd} profile to add invitee {$iInviteeId} --- ", false);
                    }
                    else { // if root profile(owner) then add to root
                        $iProfileIdToAdd = $iSponsorId;
                        $this->_oConfig->logData(" ---- selected sponsor {$iSponsorId} as profile to add invitee {$iInviteeId} --- ", false);
                    }

                    break;
                }
            }

            if ($iProfileIdToAdd) // add invitee to found above profile
            {
                $oMatrix->add($iProfileIdToAdd, $iInviteeId);
                $this->_oConfig->logData(" ---- Profile {$iInviteeId} was added to the matrix of {$iProfileIdToAdd} profile.", false);
                if ($iLastLevel && $iLastLevel === $iDeep){
                    //in case if matrix is filled, then create new matrix
                    //echo " IT IS THE LAST MATRIX ELEMENT, MARK MATRIX OF MEMBER $iReferralId and params width and deep $iWidth, $iDeep as filled" . '<br/>';
                    $aACL =  BxDolAcl::getInstance()->getMemberMembershipInfo($iSponsorId);
                    $oMatrix->markAsFilled($iSponsorId, $aACL['id'], $iWidth, $iDeep);
                    $this->_oConfig->logData(" ---- Mark Sponsor's profile {$iSponsorId} matrix as filled.", false);
                }
            }
            elseif($iLastLevel && $iLastLevel < $iDeep) {
                //in case if there are less level then it can have
                //we add new level right under the next one to first user's profile
                $oMatrix->add($oMatrix->getNodeWithFewestEntries($iSponsorId, $iLastLevel), $iInviteeId);
                $this->_oConfig->logData(" ---- all existed levels of matrix {$iSponsorId} are filled and we add new empty level next to {$iLastLevel} level and add invitee {$iInviteeId} into it.", false);
            }
        }
        else
        {
            $this->_oConfig->logData(" ---- Sponsor's {$iSponsorId} spillover is disabled and trying to add to under parent;s level:", false);
            $iParentLevel = key($aLevels);
            if (/*isset($aLevels[$iParentLevel + 1])*/ ($iWidth && $aLevels[$iParentLevel + 1] < pow($iWidth, $iParentLevel + 1)) || !$iWidth) {
                $oMatrix->add($iSponsorId, $iInviteeId);
                $this->_oConfig->logData(" ---- Profile {} as added to {$iParentLevel} level of Sponsor's {$iSponsorId} matrix", false);
            }
        }
    }

    function getProfileProgram($iProfileId){
	    if (!$iProfileId)
            return false;

        $aMembershipInfo = BxDolAcl::getInstance() -> getMemberMembershipInfo($iProfileId);
        if (empty($aMembershipInfo))
            return false;

        return $this->getProgramByMembership($aMembershipInfo['id']);
    }

    function isProfileMatrixEnabled($iProfileId){
        $CNF = &$this->_oConfig->CNF;
        $aInfo = $this -> getProfileProgram($iProfileId);
        return !empty($aInfo) && (int)$aInfo[$CNF['FMP_MATRIX']];
    }

    function isMatrixSpilloverEnabled($iProfileId){
        $CNF = &$this->_oConfig->CNF;
        $aInfo = $this -> getProfileProgram($iProfileId);
        return !empty($aInfo) && (int)$aInfo[$CNF['FMP_SPILLOVER']];
    }

    function getProfileMatrixDeep($iProfileId){
        $CNF = &$this->_oConfig->CNF;
        $aInfo = $this -> getProfileProgram($iProfileId);
        return !empty($aInfo) ? /*(int)$aInfo[$CNF['FMP_DEEP']]*/ $CNF['MATRIX_WIDTH'] : false;
    }

    function getProfileMatrixWidth($iProfileId){
        $CNF = &$this->_oConfig->CNF;
        $aInfo = $this -> getProfileProgram($iProfileId);
        return !empty($aInfo) ? (int)$aInfo[$CNF['FMP_WIDTH']] : false;
    }

    function getDirectlyInvited($iProfileId){
        $CNF = &$this->_oConfig->CNF;
	    return $this->getRow("SELECT 
                                        COUNT(*) as `total`,
                                        SUM(IF(`{$CNF['FR_TYPE']}` = :referral , 1, 0 )) as `referrals`,
                                        SUM(IF(`{$CNF['FR_TYPE']}` = :banner , 1, 0 )) as `banners`
                                    FROM `{$CNF['REFERRALS']}` 
                                    WHERE `{$CNF['FR_REFERRAL']}`=:profile",
                                    array(
                                        'profile' => $iProfileId,
                                        'referral' => AQB_AFF_TYPE_REF,
                                        'banner' => AQB_AFF_TYPE_BANNER
                                    ));
    }

    function isDirectlyInvited($iProfileId, $iInvitee){
        $CNF = &$this->_oConfig->CNF;
        return (int)$this->getOne("SELECT COUNT(*) 
                                    FROM `{$CNF['REFERRALS']}` 
                                    WHERE `{$CNF['FR_REFERRAL']}`=:profile AND `{$CNF['FR_INVITEE']}`=:invitee",
            array(
                'profile' => $iProfileId,
                'invitee' => $iInvitee
            )) !== 0;
    }

    function getSponsorProfileId($iProfileId){
        $CNF = &$this->_oConfig->CNF;
	    return (int)$this->getOne("SELECT `{$CNF['FR_REFERRAL']}` 
                                    FROM `{$CNF['REFERRALS']}` 
                                    WHERE `{$CNF['FR_INVITEE']}`=:invitee",
            array(
                'invitee' => $iProfileId
            ));
    }

    function isBAPerformed($iProfileID, $iBannerId, $sAction, $sIp){
        $CNF = &$this->_oConfig->CNF;
	    if (!$iProfileID || !$sAction || !$sIp)
	        return false;

	    return  $this -> getOne("SELECT COUNT(*) 
                                                      FROM `{$CNF['BANNERS_TRACKER']}` 
                                                      WHERE `{$CNF['FBT_AUTHOR']}`=:author 
                                                      AND `{$CNF['FBT_TYPE']}`=:action 
                                                      AND `{$CNF['FBT_BANNER_ID']}`=:banner 
                                                      AND `{$CNF['FBT_EXECUTOR_IP']}`=:ip 
                                                      AND `{$CNF['FBT_ADDED']}` > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 24 HOUR))",
                                            array(
                                                'author' => $iProfileID,
                                                'action' => $sAction,
                                                'ip' => $sIp,
                                                'banner' => $iBannerId
                                            ));
    }

    function performBannerAction($iProfileID, $iBannerId, $sAction = AQB_AFF_IMPRESSION){
        $CNF = &$this->_oConfig->CNF;
        $sIP = getVisitorIP();

        if (!$iProfileID || $this -> isBAPerformed($iProfileID, $iBannerId, $sAction, $sIP))
            return false;

        return $this -> query("INSERT INTO `{$CNF['BANNERS_TRACKER']}` 
                                                      SET 
                                                          `{$CNF['FBT_AUTHOR']}` = :profile,
                                                          `{$CNF['FBT_BANNER_ID']}` = :banner,
                                                          `{$CNF['FBT_TYPE']}` = :action,
                                                          `{$CNF['FBT_EXECUTOR_IP']}` = :ip,
                                                          `date` = UNIX_TIMESTAMP()
                                                      ", array(
                                                          'profile' => $iProfileID,
                                                          'banner' => $iBannerId,
                                                          'action'=> $sAction,
                                                          'ip' => $sIP
                             ));
    }

    function getHistoryRecordByB($iProfileId, $iBannerId, $sAction = AQB_AFF_CLICK){
        $CNF = &$this->_oConfig->CNF;
	    return $this->getRow("SELECT * 
                                         FROM `{$CNF['HISTORY']}`
                                         WHERE 
                                             `{$CNF['FH_OWNER']}`=:profile AND 
                                             `{$CNF['FH_PERFORMER']}`=:performer AND
                                             `{$CNF['FH_TYPE']}`=:type AND
                                             `{$CNF['FH_ACTION']}`=:action",
            array(
                'profile' => $iProfileId,
                'performer' => $iBannerId,
                'type' => AQB_AFF_TYPE_BANNER,
                'action' => $sAction
            ));
    }

    function getProfileProgramCommission($iProfileId, $sAction = ''){
        $CNF = &$this->_oConfig->CNF;
	    $aProgram = $this->getProfileProgram($iProfileId);

        if (!isset($aProgram[$CNF['FMP_COMMISSION']]))
	        return false;

       $aParams = @unserialize($aProgram[$CNF['FMP_COMMISSION']]);
       return $this->getProgramCommission($aParams, $sAction);
    }

    function getProgramCommission(&$mixedProgram, $sAction = ''){
        $CNF = &$this->_oConfig->CNF;

        $aCommission = $mixedProgram;
        if(!is_array($mixedProgram) && (int)$mixedProgram) {
            $aProgram = $this->getProgramById($mixedProgram);

            if (empty($aProgram) || !(int)$aProgram[$CNF['FMP_ACTIVE']])
                return array();

            $aCommission = isset($aProgram[$CNF['FMP_COMMISSION']]) ? @unserialize($aProgram[$CNF['FMP_COMMISSION']]) : [];
            if (empty($aCommission))
                return array();
        }

        return $sAction && isset($aCommission[$sAction]) && is_array($aCommission[$sAction]) ? $aCommission[$sAction] : $aCommission;
    }

    /**
     * Occurs when an profile executes the action
     * @param $iExecutorId action's performer profile id
     * @param string $sAction - sort of the available actions
     * @param int $fPrice - price for actions with precentage commission, like membership updates or market purchases
     * @return bool
     */
    function registerProfileAction($iExecutorId, $sAction = AQB_AFF_MARKET, $fPrice = 0){
        $CNF = &$this->_oConfig->CNF;

        $iSponsorId = $this->getSponsorProfileId($iExecutorId);
        if (!$iSponsorId)
            return false;

        $bResult = true;
        $oMatrix = new AqbAffiliateCloserTable($this->_oConfig);
        $aAncestors = $oMatrix->getProfileAncestors($iExecutorId);
        $bPointsEnabled = $this->isPointsEnabled();
        $bFiatEnabled = $this->isFiatEnabled();
        $bCreditsEnabled = $this->isCreditsEnabled();

        if (!empty($aAncestors)) {
            $this->_oConfig->logData(" ---- Using matrix commissions settings ---- :", false);
            $this->_oConfig->logData(" ---- Ancestors list ---- :", false);
            $this->_oConfig->logData($aAncestors, false);

            foreach ($aAncestors as &$aAncestor) {
                $iSponsorId = $aAncestor[$CNF['FPTREE_PROFILE_ID']];
                $aProgram = $this->getProfileProgram($iSponsorId);
                if (empty($aProgram))
                    continue;

                $fCredits = $fPoints = $fCommission = 0;
                // init default commission for case if the matrix is not defined for the member or no matrix level's commission for this level
                if (isset($aProgram[$CNF['FMP_COMMISSION']]) && ($aParams = @unserialize($aProgram[$CNF['FMP_COMMISSION']]))){
                    $aCommission = $this->getProgramCommission($aParams, $sAction);
                    $fCredits = isset($aCommission[AQB_AFF_CUR_TYPE_CREDITS]) ? $aCommission[AQB_AFF_CUR_TYPE_CREDITS] : 0;
                    $fPoints = isset($aCommission[AQB_AFF_CUR_TYPE_POINTS]) ? $aCommission[AQB_AFF_CUR_TYPE_POINTS] : 0;
                    $fCommission = isset($aCommission[AQB_AFF_CUR_TYPE_CURRENCY]) ? $aCommission[AQB_AFF_CUR_TYPE_CURRENCY] : 0;
                    if (isset($aCommission[$CNF['COMMISSION_PERCENTAGE_OPTION']]) && $aCommission[$CNF['COMMISSION_PERCENTAGE_OPTION']] && $fPrice)
                        $fCommission = $this->_oConfig->number_format($fCommission * $fPrice / 100);
                }

                $aLevelCommission = [];
                if ((int)$aProgram[$CNF['FMP_MATRIX']]) {
                    $aLevelCommission = $this->getMatrixLevelsCommission((int)$aProgram[$CNF['FMP_ID']], $aAncestor[$CNF['FTREE_LEVEL']]);
                    if (!empty($aLevelCommission) && !empty($aLevelCommission[$sAction])) {
                        $fCommission = ($bFiatEnabled && isset($aLevelCommission[$sAction][AQB_AFF_CUR_TYPE_CURRENCY]) ? $aLevelCommission[$sAction][AQB_AFF_CUR_TYPE_CURRENCY] : '');
                        $fPoints = ($bPointsEnabled && isset($aLevelCommission[$sAction][AQB_AFF_CUR_TYPE_POINTS]) ? $aLevelCommission[$sAction][AQB_AFF_CUR_TYPE_POINTS] : '');
                        $fCredits = ($bCreditsEnabled && isset($aLevelCommission[$sAction][AQB_AFF_CUR_TYPE_CREDITS]) ? $aLevelCommission[$sAction][AQB_AFF_CUR_TYPE_CREDITS] : '');

                        if (isset($aLevelCommission[$sAction][$CNF['COMMISSION_PERCENTAGE_OPTION']]) && $fPrice) {
                            $fCommission = $this->_oConfig->number_format($fCommission * $fPrice / 100);
                        }
                    }
                }

                $this->_oConfig->logData(" ---- level's commissions ---- :", false);
                $this->_oConfig->logData($aLevelCommission, false);

                if (!$fCommission && !$fPoints && !$fCredits)
                    continue;

                $aParams = [
                    'profile' => $iSponsorId,
                    'performer' => $iExecutorId,
                    'type' => AQB_AFF_TYPE_REF,
                    'action' => $sAction,
                    'count' => 1,
                    'commission' => $fCommission,
                    'points' => $fPoints,
                    'credits' => $fCredits
                ];

                $this->_oConfig->logData($aParams, false);
                $bResult &= $this->registerActionInHistory($aParams);
            }
        }
        else
        {
            $fCredits = $fPoints = $fCommission = 0;
            $aCommission = $this->getProfileProgramCommission($iSponsorId, $sAction);
            $this->_oConfig->logData(" ---- Linear commission ---- :", false);
            $this->_oConfig->logData($aCommission, false);

            $aSpecialCommission = $this->getCurrentAclPrice($iExecutorId, $iSponsorId);
            if (!empty($aSpecialCommission) && isset($aSpecialCommission[$sAction])) {
                $aCommission = $aSpecialCommission[$sAction];
                $this->_oConfig->logData(" ---- Special commission ---- :", false);
                $this->_oConfig->logData($aCommission, false);
            }

            if (!empty($aCommission)) {
                $fPoints = isset($aCommission[AQB_AFF_CUR_TYPE_POINTS]) ? $aCommission[AQB_AFF_CUR_TYPE_POINTS] : 0;
                $fCommission = isset($aCommission[AQB_AFF_CUR_TYPE_CURRENCY]) ? $aCommission[AQB_AFF_CUR_TYPE_CURRENCY] : 0;
                $fCredits = isset($aCommission[AQB_AFF_CUR_TYPE_CREDITS]) ? $aCommission[AQB_AFF_CUR_TYPE_CREDITS] : 0;
                if (isset($aCommission[$CNF['COMMISSION_PERCENTAGE_OPTION']]) && $aCommission[$CNF['COMMISSION_PERCENTAGE_OPTION']] && $fPrice)
                    $fCommission = $this->_oConfig->number_format($fCommission * $fPrice / 100);
            }

            if (!$fCommission && !$fPoints && !$fCredits)
                return false;

            return $this->registerActionInHistory(
                                                    [
                                                     'profile' => $iSponsorId,
                                                     'performer' => $iExecutorId,
                                                     'type' => AQB_AFF_TYPE_REF,
                                                     'action' => $sAction,
                                                     'count' => 1,
                                                     'commission' => $fCommission,
                                                     'points' => $fPoints,
                                                     'credits' => $fCredits,
                                                    ]
                                                );
        }

         return $bResult;
    }

    /**
     * Register action performed by banner
     * @param $iProfileId - banner's owner id
     * @param $iBannerId - banner id
     * @param string $sAction - kind of action on of the available constants
     * @return bool|int|string
     */
    function registerBannersAction($iProfileId, $iBannerId, $sAction = AQB_AFF_JOIN){
        $CNF = &$this->_oConfig->CNF;

        if (!$this->performBannerAction($iProfileId, $iBannerId, $sAction))
               return false;

        $aCommission = $this->getBannerCommissionInfo($iBannerId);
        $fCommission = $fCredits = $fPoints = 0;
        $bFiatEnabled = $this->isFiatEnabled();
        if ($bFiatEnabled)
            $fCommission = (float)$aCommission[$sAction];

        $bPointsEnabled = $this->isPointsEnabled();
        if ($bPointsEnabled)
            $fPoints = (float)$aCommission["points_{$sAction}"];

        $bCreditsEnabled = $this->isCreditsEnabled();
        if ($bCreditsEnabled)
            $fCredits = (float)$aCommission["credits_{$sAction}"];

        if (!$fCommission && !$fPoints && !$fCredits)
            return false;

        $iCount = 1;
        $aHistory = $this->getHistoryRecordByB($iProfileId, $iBannerId, $sAction);
        if (!empty($aHistory)) {
            if ($bFiatEnabled)
                $fCommission = $aHistory[$CNF['FH_CM']] + $fCommission;

            if ($bPointsEnabled)
                $fPoints = $aHistory[$CNF['FH_POINTS']] + $fPoints;

            if ($bCreditsEnabled)
                $fCredits = $aHistory[$CNF['FH_CREDITS']] + $fCredits;

            $iCount = $aHistory[$CNF['FH_COUNT']] + 1;
        }

        return $this->registerActionInHistory(
                                                [
                                                  'profile' => $iProfileId,
                                                  'performer' => $iBannerId,
                                                  'type' => AQB_AFF_TYPE_BANNER,
                                                  'action' => $sAction,
                                                  'count' => $iCount,
                                                  'commission' => $fCommission,
                                                  'points' => $fPoints,
                                                  'credits' => $fCredits,
                                                ]
                                              );
    }

    /**
     * @param array $aParams should contain the next fields:
                'profile' => person who should get this commission
                'performer' => profile id or banner id
                'type' => referral or banner
                'action' => kind of the action
                'count' => number of executions, show how many times this action has been executed during short period of time,
                           it is used for banners' actions like impressions
                'commission' => commissions' amount
                'points' => points amount
     * @return bool|int
     */
    function registerActionInHistory($aParams){
        $CNF = &$this->_oConfig->CNF;

        if ($CNF['SEND_POINTS_IMMEDIATELY_ON_BALANCE'] && isset($aParams['points']) && (float)$aParams['points']){
            $fPoints = $aParams['points'];
            BxDolService::call('aqb_points', 'assign_points', [$fPoints, _t('_aqb_affiliate_history_title_' . $aParams['action']), $aParams['profile']]);

            $aParams['points'] = 0;
        }

        if ($CNF['SEND_CREDITS_IMMEDIATELY_ON_BALANCE'] && isset($aParams['credits']) && (float)$aParams['credits']){
            bx_srv('bx_credits', 'update_profile_balance', [$aParams['profile'], $aParams['credits'], 0, '', _t('_aqb_affiliate_history_title_' . $aParams['action'])]);
            $aParams['credits'] = 0;
        }

        if (!isset($aParams['info']))
            $aParams['info'] = '';

        $bResult = $this -> query("REPLACE INTO `{$CNF['HISTORY']}`  
                                          SET 
                                             `{$CNF['FH_OWNER']}`=:profile,
                                             `{$CNF['FH_PERFORMER']}`=:performer,
                                             `{$CNF['FH_TYPE']}`=:type,
                                             `{$CNF['FH_ACTION']}`=:action,
                                             `{$CNF['FH_ADDED']}`=UNIX_TIMESTAMP(),
                                             `{$CNF['FH_COUNT']}`=:count,
                                             `{$CNF['FH_CM']}`=:commission,
                                             `{$CNF['FH_POINTS']}`=:points,
                                             `{$CNF['FH_CREDITS']}`=:credits,
                                             `{$CNF['FH_INFO']}`=:info",
            $aParams);

        if ($bResult)
            return $this->updateUserInfo($aParams['profile']);

        return false;
    }

    function getAvailableCommissionsModule(){
        $aModules = array(AQB_AFF_JOIN);

        if ($this->isEnabledByName('bx_market')) {
            $aModules[] = AQB_AFF_MARKET;
        }

        if ($this->isEnabledByName('bx_acl') && $this->isEnabledByName('bx_payment')) {
            $aModules[] = AQB_AFF_UPGRADE;
        }

        if ($this->isEnabledByName('bx_payment')) {
            $aModules[] = AQB_AFF_PURCHASE;
        }

        return $aModules;
    }

    function getProfileHistoryTotalCommissions($iProfileId, $sAction = '', $bBanners = false){
        $CNF = &$this->_oConfig->CNF;

        $sWhere = "WHERE `{$CNF['FH_OWNER']}`=:profile";
        $aWhere = array( 'profile' => $iProfileId );
        if ($sAction) {
            $aWhere[$CNF['FH_ACTION']] = $sAction;
            $sWhere .= " AND `{$CNF['FH_ACTION']}`=:{$CNF['FH_ACTION']}";
        }

        if ($bBanners)
            $sWhere .= " AND `{$CNF['FH_TYPE']}`='" . AQB_AFF_TYPE_BANNER . "'";

        return $this->getRow("SELECT 
                                         SUM(`{$CNF['FH_POINTS']}`) as `{$CNF['FH_POINTS']}`, 
                                         SUM(`{$CNF['FH_CREDITS']}`) as `{$CNF['FH_CREDITS']}`, 
                                         SUM(`{$CNF['FH_CM']}`) as `{$CNF['FH_CM']}`                                                                                                 
                                         FROM `{$CNF['HISTORY']}`
                                         {$sWhere}
                                         GROUP BY `{$CNF['FH_OWNER']}`", $aWhere);
    }

    function removeCommissionItem($iProfileId, $aItems = array()){
        $CNF = &$this->_oConfig->CNF;

        $sItems = '';
        $aWhere = array('profile' => $iProfileId);
        if (!empty($aItems)){
            $sItems = implode(",", $aItems);
            $sItems = "AND `{$CNF['FH_ID']}` IN ($sItems)";
        }

        $this->query("DELETE FROM `{$CNF['HISTORY']}`
                                    WHERE `{$CNF['FH_OWNER']}`=:profile {$sItems}", $aWhere);

        return $this->updateUserInfo($iProfileId);
    }

    function getTxnInfo($iItemId){
        $CNF = &$this->_oConfig->CNF;
        return $this -> getRow("SELECT * FROM `{$CNF['TRANSACTIONS']}` WHERE `{$CNF['FC_ID']}`=:item", array('item' => $iItemId));
    }

    function updateTnxInfo($iItemId, $aData){
        $CNF = &$this->_oConfig->CNF;

        $aFields = array();
        $aWhere = array( 'item' => $iItemId );
        foreach($aData as $sKey => $sValue) {
            $aFields[] = "`{$sKey}` =:{$sKey}";
            $aWhere[$sKey] = $sValue;
        }

        if (empty($aFields))
            return false;

        $sFields = implode(',', $aFields);
        return $this -> query("UPDATE `{$CNF['TRANSACTIONS']}` 
                              SET  
                                 {$sFields}                             
                              WHERE `{$CNF['FC_ID']}`=:item", $aWhere);
    }

    function getProfileTxns($iProfileId, $sType = AQB_AFF_STATUS_PAID){
        $CNF = &$this->_oConfig->CNF;

        return $this -> getRow("SELECT * FROM `{$CNF['TRANSACTIONS']}` 
                                          WHERE `{$CNF['FC_PROFILE_ID']}`=:profile AND `{$CNF['FC_STATUS']}`=:status",
            array('profile' => $iProfileId, 'status' => $sType));
    }

    function addCommissionsRequest($iProfileId, $fCommission = 0, $fPoints = 0, $fCredits = 0){
        $CNF = &$this->_oConfig->CNF;
        if (!$fCommission && !$fPoints && !$fCredits)
            return false;

        return $this->query("INSERT INTO `{$CNF['TRANSACTIONS']}` 
                                         SET     
                                            `{$CNF['FC_PROFILE_ID']}`=:profile,
                                            `{$CNF['FC_AMOUNT']}`=:amount,
                                            `{$CNF['FC_PPOINTS']}`=:bpoints,                                            
                                            `{$CNF['FC_POINTS']}`=:points,
                                            `{$CNF['FC_CREDITS']}`=:credits,
                                            `{$CNF['FC_ADDED']}`=UNIX_TIMESTAMP()",
            [
              'profile' => $iProfileId,
              'points' => $fPoints,
              'credits' => $fCredits,
              'amount' => $fCommission,
              'bpoints' => +($CNF['SEND_POINTS_IMMEDIATELY'] && $fPoints)
            ]) ? $this->lastId() : false;
    }

    function getTransactionCommissions($iProfileId, $iStatus = ''){
        $CNF = &$this->_oConfig->CNF;
        $aWhere = array($CNF['FC_PROFILE_ID'] => $iProfileId);
        $sWhere = "";
        if ($iStatus) {
            $aWhere[$CNF['FC_STATUS']] = (int)$iStatus;
            $sWhere .= " AND `{$CNF['FC_STATUS']}`=:{$CNF['FC_STATUS']}";
        }

        return $this->getRow("SELECT 
                                          SUM(`{$CNF['FC_AMOUNT']}`) as `{$CNF['FC_AMOUNT']}`, 
                                          SUM(`{$CNF['FC_POINTS']}`) as `{$CNF['FC_POINTS']}`, 
                                          SUM(`{$CNF['FC_CREDITS']}`) as `{$CNF['FC_CREDITS']}` 
                                        FROM `{$CNF['TRANSACTIONS']}` 
                                        WHERE `{$CNF['FC_PROFILE_ID']}`=:{$CNF['FC_PROFILE_ID']} {$sWhere}
                                        GROUP BY `{$CNF['FC_PROFILE_ID']}`",
            $aWhere);
    }

    function getAvailableForCashOutCommissions($iProfileId){
        $CNF = &$this->_oConfig->CNF;
        $aHCommissions = $this->getProfileHistoryTotalCommissions($iProfileId);
        if (empty($aHCommissions))
            return false;

        $fCM = $aHCommissions[$CNF['FH_CM']];
        $fPoints = $aHCommissions[$CNF['FH_POINTS']];
        $fCredits = $aHCommissions[$CNF['FH_CREDITS']];

        $aTCommissions = $this->getTransactionCommissions($iProfileId);
        if (!empty($aTCommissions)) {
            $fCM -= $aTCommissions[$CNF['FC_AMOUNT']];
            $fPoints -= $aTCommissions[$CNF['FC_POINTS']];
            $fCredits -= $aTCommissions[$CNF['FC_CREDITS']];
        }

        return [
                 $CNF['FC_AMOUNT'] => $fCM > 0 ? $this->_oConfig->number_format($fCM) : 0,
                 $CNF['FC_POINTS'] =>  $fPoints > 0 ? bx_srv('aqb_points', 'get_format_points', array($fPoints)) : 0,
                 $CNF['FC_CREDITS'] =>  $fCredits > 0 ? $this->_oConfig->number_format($fCredits) : 0
               ];
    }

    function getInvitedByBanner($iProfileId = 0, $bCount = false){
        $CNF = &$this->_oConfig->CNF;

        $sWhere = "WHERE `{$CNF['FR_TYPE']}`= '" . AQB_AFF_TYPE_BANNER . "'";
        $aWhere = array();
        if ($iProfileId){
            $sWhere .= " AND `{$CNF['FR_REFERRAL']}`= :referral";
            $aWhere['referral'] = $iProfileId;
        }

        if(!$bCount)
            return $this -> getAll("SELECT * FROM `{$CNF['REFERRALS']}` 
                                          {$sWhere}", $aWhere);

        return $this -> getOne("SELECT COUNT(*) 
                                            FROM `{$CNF['REFERRALS']}` 
                                            {$sWhere}", $aWhere);
    }

    function rebuildMatrix(){
        $CNF = &$this->_oConfig->CNF;
        $aReferrals = $this->getAll("SELECT * FROM `{$CNF['REFERRALS']}` ORDER BY `id`");

        $this->query("TRUNCATE TABLE `{$CNF['TREE']}`");
        $this->query("TRUNCATE TABLE `{$CNF['TREE_PROFILES']}`");

        $bResult = false;
        foreach($aReferrals as &$aReferral) {
            if ($this->isProfileMatrixEnabled($aReferral[$CNF['FR_REFERRAL']]) && (int)$aReferral[$CNF['FR_INVITEE']]) {
                $this->addProfileToTheMatrix($aReferral[$CNF['FR_INVITEE']]);
                $bResult = true;
            }
        }

        return $bResult;
    }

    function getProfileIdByInvKey($sKey){
        $CNF = $this->_oConfig->CNF['INVITATIONS']['table'];
        if (!$sKey)
            return false;

        $aResult = $this->getRow("SELECT * FROM `{$CNF['name']}` WHERE `{$CNF['key']}` LIMIT 1");
        return isset($aResult[$CNF['profile_id']]) ? $aResult[$CNF['profile_id']] : 0;
    }

    function addSponsorTrack($iAccountId, $sCode){
        $CNF = &$this->_oConfig->CNF;
        return $this->query("REPLACE INTO `{$CNF['REFERRALS_SPONSOR']}` SET `{$CNF['FRT_ADDED']}`= UNIX_TIMESTAMP(), `{$CNF['FRT_SPONSOR']}`=:code, `{$CNF['FRT_ACCOUNT']}`=:id",
            array(
                'code' => $sCode,
                'id' => $iAccountId
            ));
    }

    function getSponsorCode($iAccountId){
        $CNF = &$this->_oConfig->CNF;
        return $this->getOne("SELECT `{$CNF['FRT_SPONSOR']}` FROM `{$CNF['REFERRALS_SPONSOR']}` WHERE `{$CNF['FRT_ACCOUNT']}`=:id",
            array(
                'id' => $iAccountId
            ));
    }

    function removeSponsorTrack($iAccountId){
        $CNF = &$this->_oConfig->CNF;
        return $this->query("DELETE FROM `{$CNF['REFERRALS_SPONSOR']}` WHERE `{$CNF['FRT_ACCOUNT']}`=:id",
            array(
                'id' => $iAccountId
            ));
    }

    function isProfileActive($iProfileId){
        return $this->getOne("SELECT `status` FROM `sys_profiles` WHERE `id`=:id LIMIT 1", array('id' => $iProfileId)) == BX_PROFILE_STATUS_ACTIVE;
    }

    public function createAccountAndProfile($aProfileInfo)
    {
        if (empty($aProfileInfo))
            return false;

        $sSalt = genRndSalt();
        $sPasswordHash = encryptUserPwd($aProfileInfo['password'], $sSalt);

        $sQuery = $this->prepare(
            "INSERT INTO
                     		`sys_accounts`
                     	SET
                     		`name`   			= ?,
                     		`email`      		= ?,
                     		`password`   		= ?,
                     		`salt`		   		= ?,							
							`added`				= UNIX_TIMESTAMP(),
                            `email_confirmed`   = 1
                     ",
            $aProfileInfo['username'],
            $aProfileInfo['email'],
            $sPasswordHash,
            $sSalt
        );

        $this->query($sQuery);
        $iAccountId = $this->lastId();
        if (!$iAccountId)
            return false;

        $this->query("INSERT INTO `sys_profiles` SET `account_id` = {$iAccountId}, `content_id` = {$iAccountId}, `type` = 'system', `status` = 'active'");
        $iSysProfileId = $this->lastId();

        $sQuery = $this->prepare(
            "INSERT INTO
	                     		`bx_persons_data`
	                     	SET
	                     		`author`   			= ?,
	                     		`added`      		= UNIX_TIMESTAMP(),	                     		
								`fullname`			= ?,
								`allow_view_to`		= 2
	                     ",
            $iSysProfileId,
            $aProfileInfo['username']
        );

        $this->query($sQuery);
        $iContentId = $this->lastId();
        $this->query("INSERT INTO `sys_profiles` SET `account_id` = {$iAccountId}, `type` = 'bx_persons', `content_id` = {$iContentId}, `status` = 'active'");
        $iProfileId = $this->lastId();
        if ($iProfileId) {
            BxDolAccountQuery::getInstance()->updateCurrentProfile($iAccountId, $iProfileId);
            BxDolAcl::getInstance()->setMembership($iProfileId, MEMBERSHIP_ID_STANDARD);
        }

        $sQuery = $this->prepare("UPDATE `bx_persons_data` SET `author` = ? WHERE `id` = ?", $iProfileId, $iContentId);
        return $this->query($sQuery) ? $iAccountId : false;
    }

    function getPaidLevels($bOriginal = false){
        $aPrices = $this -> getAllWithKey("SELECT * FROM `bx_acl_level_prices` ORDER BY `id`", 'id');
        if ($bOriginal)
            return $aPrices;

        $aResult = [];
        $aCurrency = bx_srv('bx_payment', 'get_currency_info');
        foreach($aPrices as $iKey => $aPrice) {
            $aMembershipLevel = BxDolAcl::getInstance()->getMembershipInfo($aPrice['level_id']);
            $aResult[$iKey] = _t($aMembershipLevel['name']) . " ( {$aPrice['name']} - {$aPrice['period']} {$aPrice['period_unit']}, {$aPrice['price']} {$aCurrency['code']} )";
        }

        return $aResult;
    }

    function removeAclCommission($iProgramId, $iAclId){
        $CNF = $this->_oConfig->CNF;
        return $this->query("DELETE FROM `{$CNF['PROGRAMS_ACL_CM']}`
                                       WHERE `{$CNF['FPMC_PROGRAM_ID']}` =:program_id AND `{$CNF['FPMC_ACTL_ID']}`=:acl_id", ['acl_id' => $iAclId, 'program_id' => $iProgramId]);
    }

    function getAclCommission($iProgramId = 0, $iAclId = 0){
        $CNF = $this->_oConfig->CNF;
        $aData = $this->getAclCommissions($iProgramId, $iAclId);
        if (empty($aData) || !$aData[$CNF['FPMC_COMMISSION']])
            return false;

        return @unserialize($aData[$CNF['FPMC_COMMISSION']]);
    }

    function getAclCommissions($iProgramId = 0, $iAclId = 0){
        $CNF = $this->_oConfig->CNF;
        if ($iProgramId && $iAclId)
            return $this->getRow("SELECT * FROM `{$CNF['PROGRAMS_ACL_CM']}`
                                            WHERE `{$CNF['FPMC_PROGRAM_ID']}` =:program_id AND `{$CNF['FPMC_ACTL_ID']}`=:acl_id LIMIT 1",
                                            ['acl_id' => $iAclId, 'program_id' => $iProgramId]);

        if ($iProgramId)
            return $this->getAll("SELECT * FROM `{$CNF['PROGRAMS_ACL_CM']}`
                                            WHERE `{$CNF['FPMC_PROGRAM_ID']}` =:program_id",
                ['program_id' => $iProgramId]);

        return $this->getAll("SELECT * FROM `{$CNF['PROGRAMS_ACL_CM']}`
                                        WHERE `{$CNF['FPMC_ACTL_ID']}`=:acl_id LIMIT 1",
                                        ['acl_id' => $iAclId]);

    }

    function isPaidLevelsAvailable(){
        $aData = $this->getPaidLevels();
        return !empty($aData);
    }

    function getCurrentAclPrice($iProfileId, $iSponsorId){
        $CNF = $this->_oConfig->CNF;
        $aMembershipInfo = BxDolAcl::getInstance()->getMemberMembershipInfo($iProfileId);
        if (empty($aMembershipInfo) || !$aMembershipInfo['transaction_id'])
            return false;

        $iAclId = $this->getOne("SELECT `price_id` FROM `bx_acl_licenses` WHERE `license`=:license ORDER BY `added` DESC LIMIT 1", ['license' => $aMembershipInfo['transaction_id']]);
        if ($iAclId){ //&& $this->getOne("SELECT COUNT(*) FROM `bx_acl_level_prices` WHERE `id`=:id LIMIT 1", ['id' => $iAclId])
            if (!($aProgram = $this->getProfileProgram($iSponsorId)))
                return false;

            return $this->getAclCommission($aProgram[$CNF['FMP_ID']], $iAclId);
        }

        return false;
    }
}

/** @} */
