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

class AqbAffiliateConfig extends BxBaseModGeneralConfig
{
    public function __construct($aModule)
	{
        parent::__construct($aModule);

        $this->CNF = array (

            // database tables
            'HISTORY' => $aModule['db_prefix'] . 'history',
            'REFERRALS' => $aModule['db_prefix'] . 'referrals',
            'INFO' => $aModule['db_prefix'] . 'user_info',
            'TRANSACTIONS' => $aModule['db_prefix'] . 'transactions',
            'BANNERS' => $aModule['db_prefix'] . 'banners',
            'FILES' => $aModule['db_prefix'] . 'files',
            'PROGRAMS' => $aModule['db_prefix'] . 'programs',
            'PROGRAMS_ACL_CM' => $aModule['db_prefix'] . 'programs_memlevels_pricing',
            'MATRIX_COMMISSIONS' => $aModule['db_prefix'] . 'matrix_commissions',
            'INVITATIONS_TRACK' => $aModule['db_prefix'] . 'invitations_track',
            'TREE' => $aModule['db_prefix'] . 'tree',
            'TREE_PROFILES' => $aModule['db_prefix'] . 'tree_profiles',
            'BANNERS_TRACKER' => $aModule['db_prefix'] . 'banners_tracker',
            'REFERRALS_SPONSOR' => $aModule['db_prefix'] . 'referrals_track',

            // TREE Fields
            'FTREE_DES_ID' => 'descendant_id',
            'FTREE_ANC_ID' => 'ancestor_id',
            'FTREE_N_ANC_ID' => 'nearest_ancestor_id',
            'FTREE_LEVEL' => 'level',
            'FTREE_SUBJECT_ID' => 'subject_id',
            'FTREE_DATE' => 'date',

            // Tree Profiles Table
            'FPTREE_ID' => 'id',
            'FPTREE_PROFILE_ID' => 'profile_id',
            'FPTREE_TOTAL' => 'total',
            'FPTREE_FILLED' => 'filled',
            'FPTREE_ADDED' => 'added',
            'FPTREE_UPDATE' => 'updated',
            'FPTREE_MEM_LEVEL' => 'membership',

            // Invitation tracker fields
            'FIN_SENDER' => 'profile_id',
            'FIN_RES_EMAIL' => 'email',
            'FIN_DATE' => 'date',
            'FIN_COUNT' => 'count',

            // Banners tracker fields
            'FBT_ID' => 'id',
            'FBT_BANNER_ID' => 'banner_id',
            'FBT_AUTHOR' => 'author_id',
            'FBT_EXECUTOR_IP' => 'executor_sip',
            'FBT_TYPE' => 'action',
            'FBT_ADDED' => 'date',

            // History fields
            'FH_ID' => 'id',
            'FH_TYPE' => 'type',
            'FH_ACTION' => 'action',
            'FH_OWNER' => 'owner_id',
            'FH_PERFORMER' => 'performer',
            'FH_ADDED' => 'added',
            'FH_CM' => 'commissions',
            'FH_PSTATUS' => 'pstatus',
            'FH_POINTS' => 'points',
            'FH_CREDITS' => 'credits',
            'FH_COUNT' => 'count',
            'FH_INFO' => 'info',

            // User Info
            'FI_ID' => 'id',
            'FI_PROFILE' => 'profile_id',
            'FI_UPDATE' => 'update',
            'FI_JOINS' => 'joins',
            'FI_MARKETS' => 'markets',
            'FI_REFERRED' => 'referred',
            'FI_IMPRESSIONS' => 'impressions',
            'FI_CLICKS' => 'clicks',
            'FI_UPGRADES' => 'upgrades',
            'FI_CUSTOM' => 'custom',
            'FI_PURCHASES' => 'purchases',
            'FI_COMMISSIONS' => 'commission',
            'FI_POINTS' => 'points',
            'FI_CREDITS' => 'credits',

            // Referrals fields
            'FR_ID' => 'id',
            'FR_REFERRAL' => 'referral',
            'FR_INVITEE_A' => 'invitee_a',
            'FR_INVITEE' => 'invitee',
            'FR_TYPE' => 'type',
            'FR_ADDED' => 'added',
            'FR_BANNER' => 'banner',

            // Programs fields
            'FMP_ID' => 'id',
            'FMP_MATRIX' => 'matrix',
            'FMP_SPILLOVER' => 'spillover',
            'FMP_ACTIVE' => 'active',
            'FMP_PERCENTAGE' => 'percentage',
            'FMP_DESC' => 'description',
            'FMP_WIDTH' => 'width',
            'FMP_MEMLEVEL' => 'membership_level',
            'FMP_MEMTOADD' => 'mem_to_add',
            'FMP_DEEP' => 'deep',
            'FMP_JOIN' => 'join',
            'FMP_UPGRADE' => 'upgrade',
            'FMP_LEVELS' => 'levels',
            'FMP_COMMISSION' => 'commission',

            // Matrix commissions fields
            'FMC_LEVEL' => 'level',
            'FMC_PROGRAM_ID' => 'program_id',
            'FMC_COMMISSION' => 'commission',

            // Programs paid levels prices commission
            'FPMC_PROGRAM_ID' => 'program_id',
            'FPMC_ACTL_ID' => 'acl_level_id',
            'FPMC_COMMISSION' => 'commission',

            // Referrals track
            'FRT_ACCOUNT' => 'account_id',
            'FRT_SPONSOR' => 'sponsor',
            'FRT_ADDED' => 'date',

            // Banners Info
            'FB_ID' => 'id',
            'FB_TITLE' => 'title',
            'FB_IMG' => 'img',
            'FB_COMMISSION' => 'commissions',
            'FB_SIZE' => 'size',
            'FB_LINK' => 'link',
            'FB_ADDED' => 'added',
            'FB_ACTIVE' => 'active',

            // Commissions/Transactions
            'FC_ID' => 'id',
            'FC_AMOUNT' => 'amount',
            'FC_POINTS' => 'points',
            'FC_PPOINTS' => 'paid_points',
            'FC_CREDITS' => 'credits',
            'FC_PCREDITS' => 'paid_credits',
            'FC_STATUS' => 'status',
            'FC_PROFILE_ID' => 'profile_id',
            'FC_ADDED' => 'added',
            'FC_PAID' => 'paid',
            'FC_TXN' => 'txn',
            'FC_PAYMENT_STATUS' => 'payment_status',

            'COMMISSION_ACTIVITIES' => array('join','upgrade','market','purchase'),
            'COMMISSION_PERCENTAGE_OPTION' => 'percentage',
            'ALLOWED_PROFILE_TYPES' => array('bx_persons','bx_organizations'),

            // objects
            'OBJECT_UPLOADERS' => array('sys_simple'),
            'OBJECT_STORAGE' =>  "{$this->_sName}_files",
            'OBJECT_IMAGES_TRANSCODER_PREVIEW' => "{$this->_sName}_bnr_preview",

            'OBJECT_GRID_MEMBERS' => "{$this->_sName}_members",
            'OBJECT_GRID_COMMON' => "{$this->_sName}_common",
            'OBJECT_GRID_REQUESTS' => "{$this->_sName}_requests",
            'OBJECT_GRID_BANNERS' => "{$this->_sName}_banners",
            'OBJECT_GRID_MATRIX' => "{$this->_sName}_matrix",
            'OBJECT_GRID_MEMBERSHIPS' => "{$this->_sName}_memberships",
            'OBJECT_GRID_HISTORY' => "{$this->_sName}_history",

            'COOKIE_BANNER' => "aqb_aff_banner",
            'COOKIE_REFERRAL' => "aqb_aff_referral",
            'COOKIE_ADS_CODE' => "aqb_aff_code",

            'BRANCH' => 'BRANCH',
            'PER_PAGE_REFERRALS' => getParam($aModule['name'] . '_per_page_browse'),

            'REFER_PREFIX' => getParam($aModule['name'] . '_referral_prefix'),
            'REFER_LINK' => getParam($aModule['name'] . '_referral_link'),
            'USE_POINTS' => getParam($aModule['name'] . '_use_points') == 'on',
            'USE_CREDITS' => getParam($aModule['name'] . '_use_credits') == 'on',
            'USE_FIAT' => getParam($aModule['name'] . '_use_fiat') == 'on',
            'USE_QUEUE' => getParam($aModule['name'] . '_use_queue') == 'on',
            'SEND_INVITATIONS' => getParam($aModule['name'] . '_allow_send_invitation') == 'on',
            'ALLOW_TO_ADD_MESSAGE' => getParam($aModule['name'] . '_allow_to_add_message') == 'on',
            'ACCOUNT_ALL_PROFILES' => getParam($aModule['name'] . '_add_all_account_profiles') == 'on',
            'MESSAGE_SYMBOLS_NUMBER' => (int)getParam($aModule['name'] . '_message_length'),
            'MATRIX_WIDTH' => (int)getParam($aModule['name'] . '_matrix_width'),
            'MAX_INVITATIONS_NUMBER' => (int)getParam($aModule['name'] . '_invitations_limit'),
            'KEEP_COOKIE' => (int)getParam($aModule['name'] . '_keep_cookie'),
            'ADD_TO_FRIENDS_LIST' => getParam($aModule['name'] . '_auto_friend') == 'on',
            'SEND_POINTS_IMMEDIATELY' => getParam($aModule['name'] . '_pay_points_immediately') == 'on',
            'SEND_POINTS_IMMEDIATELY_ON_BALANCE' => getParam($aModule['name'] . '_send_points_on_balance_immediately') == 'on',
            'SEND_CREDITS_IMMEDIATELY' => getParam($aModule['name'] . '_pay_credits_immediately') == 'on',
            'SEND_CREDITS_IMMEDIATELY_ON_BALANCE' => getParam($aModule['name'] . '_send_credits_on_balance_immediately') == 'on',
            'SHOW_USERS_DETAILS' => getParam($aModule['name'] . '_show_users_details') == 'on',
            'CASH_OUT_LIMIT' => (float)getParam($aModule['name'] . '_commissions_limit'),
            'ENABLE-LOG' => false,
            'INVITATIONS' => array(
                'prefix' => 'icode',
                'table' => array(
                    'name' => 'bx_inv_invites',
                    'key' => 'key',
                    'profile_id' => 'profile_id',
                    'account_id' => 'account_id',
                )
            ),
            'FIELD_STATUS' => '',
            'FIELD_STATUS_ADMIN' => '',
            'T' => array(
                'filter_item_active' => '',
                'filter_item_hidden' => '',
                'filter_item_pending' => '',
            ),
        );

        $this->_aJsClasses = array(
            'manage_tools' => "{$this->_sClassPrefix}ManageTools",
            'utils' => "{$this->_sClassPrefix}Utils"
        );

        $this->_aJsObjects = array(
            'manage_tools' => "o{$this->_sClassPrefix}ManageTools",
            'utils' => "o{$this->_sClassPrefix}Utils",
            'analytics' => 'analytics.js'
        );

        $this->_aGridObjects = array(
            'members' => $this->CNF['OBJECT_GRID_MEMBERS'],
            'common' => $this->CNF['OBJECT_GRID_COMMON'],
            'requests' => $this->CNF['OBJECT_GRID_REQUESTS'],
            'banners' => $this->CNF['OBJECT_GRID_BANNERS'],
            'matrix' => $this->CNF['OBJECT_GRID_MATRIX'],
            'memberships' => $this->CNF['OBJECT_GRID_MEMBERSHIPS'],
            'history' => $this->CNF['OBJECT_GRID_HISTORY']
        );
	}

	function getReferralLink($iProfileId, $iBannerId = 0, $bAsPageAddon = false){
        $CNF = &$this->CNF;

        $sRefUrl = "{$CNF['REFER_LINK']}?{$CNF['REFER_PREFIX']}";
        if ($bAsPageAddon) {
            $sRefUrl = $_SERVER['REQUEST_URI'] . (stripos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?');
            $sPath = parse_url(BX_DOL_URL_ROOT, PHP_URL_PATH);
            $sRefUrl = substr($sRefUrl, get_mb_len($sPath));

            $sRefUrl = BX_DOL_URL_ROOT . "{$sRefUrl}{$CNF['REFER_PREFIX']}";
        }

        return $sRefUrl . '=' . $this->getAdsHash($iProfileId, $iBannerId);
    }

    function getAnalyticsJs(){
        return $this->getHomeUrl() . "js/{$this->_aJsObjects['analytics']}";
    }

    function getCORSResponse(){
        header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
        if ('OPTIONS' == $_SERVER['REQUEST_METHOD']) {
            header('Access-Control-Allow-Methods: POST,GET');
            header('Access-Control-Allow-Headers: Authorization,Content-Type,X-Custom-Header,X-Requested-With');
            exit(0);
        }
    }

    function cryptData( $sData,$sAction = 'encrypt') {
        $sEncryptMethod = "AES-256-CBC";
        $iv = substr( hash( 'sha256',BX_DOL_SECRET ),0,16 );

        if( $sAction === 'encrypt' )
            return base64_encode(openssl_encrypt( $sData,$sEncryptMethod,BX_DOL_SECRET,0,$iv ));

        if( $sAction === 'decrypt' )
            return openssl_decrypt(base64_decode($sData),$sEncryptMethod,BX_DOL_SECRET,0,$iv);

        return false;
    }

    function getAdsHash($iProfileId, $iBannerId = 0){
	    $sParams = $iProfileId;
	    if ((int)$iBannerId)
            $sParams = "{$sParams}:{$iBannerId}"; // encrypt profile and banner id

	    return $this->cryptData($sParams);
    }

    public function logData($oObject, $bAlert = true){
       if (!$this->CNF['ENABLE-LOG'] || !$oObject)
            return false;

       $fp = fopen(BX_DIRECTORY_PATH_LOGS . $this->getName() . '.log','a+');
       if ($bAlert) {
           fwrite($fp, date('m-d H:i:s') . " ---- action = {$oObject -> sAction}; unit = {$oObject -> sUnit}; sender = {$oObject->iSender}; object = {$oObject->iObject}; aExtras = " . print_r($oObject->aExtras, true) . PHP_EOL);
       }
       else
       {
          if (empty($oObject))
             return false;

          if (is_array($oObject)) {
             fwrite($fp,date('m-d H:i:s') . ' ------- data ------ ' . PHP_EOL);
             fwrite($fp,  print_r($oObject,true));
          } else
             fwrite($fp, $oObject . PHP_EOL);
       }

       fclose($fp);
    }

    function floatToString($fFloat){
        $sFloat = (string)$fFloat;
        if (!strpos($sFloat,"E"))
            return $sFloat;

        list($bE,$aE)= explode("E", $sFloat);

        $fS = "%." . (strlen(explode(".", $bE)[1]) + (abs($aE) - 1)) . "f";
        return sprintf($fS, $fFloat);
    }

    function getAdsCode(){
        return isset($_COOKIE[$this->CNF['COOKIE_ADS_CODE']]) ? $_COOKIE[$this->CNF['COOKIE_ADS_CODE']] : false;
    }

    public function number_format($fValue){
        return number_format($fValue, 2, '.', '');
    }

    public function isAutoApprove()
    {
        return true;
    }

    function getCommissionValue($aCommission, $sValue, $sType = AQB_AFF_CUR_TYPE_CURRENCY){
        $sPrefix = $sType !== AQB_AFF_CUR_TYPE_CURRENCY ? "{$sType}_" : '';
        $fFloat = isset($aCommission["{$sPrefix}{$sValue}"]) ? $aCommission["{$sPrefix}{$sValue}"] : 0;

        if ($sType == AQB_AFF_CUR_TYPE_POINTS)
            return $this->floatToString($fFloat);

        return $fFloat;
    }
}

/** @} */
