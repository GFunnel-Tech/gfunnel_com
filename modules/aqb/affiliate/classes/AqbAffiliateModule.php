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

define('AQB_PROGRAM_TYPE_DEFAULT', 'standard');
define('AQB_PROGRAM_TYPE_MATRIX', 'matrix');

define('AQB_REF_UPDATE_REF', 'referred');
define('AQB_REF_UPDATE_PROF', 'profile');
define('AQB_REF_UPDATE_JOIN', 'joined');

define('AQB_AFF_TYPE_REF', 'referral');
define('AQB_AFF_TYPE_BANNER', 'banner');

define('AQB_AFF_CLICK', 'clicks');
define('AQB_AFF_IMPRESSION', 'impressions');
define('AQB_AFF_JOIN', 'joins');
define('AQB_AFF_UPGRADE', 'upgrades');
define('AQB_AFF_MARKET', 'markets');
define('AQB_AFF_PURCHASE', 'purchases');
define('AQB_AFF_REFERRED', 'referred');
define('AQB_AFF_CUSTOM', 'custom');

define('AQB_AFF_STATUS_PENDING', 0);
define('AQB_AFF_STATUS_PAID', 1);

define('AQB_AFF_CUR_TYPE_CURRENCY', 'currency');
define('AQB_AFF_CUR_TYPE_POINTS', 'points');
define('AQB_AFF_CUR_TYPE_CREDITS', 'credits');

require_once ('AqbAffiliateCloserTable.php');

class AqbAffiliateModule extends BxBaseModTextModule
{
	public function __construct(&$aModule){
        parent::__construct($aModule);       
    }

    private function getProfilesByTerm($sTerm){
        $aResult = array();
        $aUsers = BxDolService::call('system', 'profiles_search', [$sTerm], 'TemplServiceProfiles');
        if (empty($aUsers))
            return $aResult;

        foreach($aUsers as $iKey => $aValue){
            /*if ($aValue['value'] == $this->_iProfileId)
                continue;*/

            $oProfile = BxDolProfile::getInstance($aValue['value']);
            $aProfileInfo = $oProfile -> getInfo();
            $aProfileInfoDetails = BxDolService::call($aProfileInfo['type'], 'get_content_info_by_id', array($aProfileInfo['content_id']));
            $oAccountInfo = BxDolAccount::getInstance($aProfileInfo['account_id']);
            if ($oProfile && !empty($aProfileInfoDetails) && !empty($oAccountInfo))
                $aResult[] = array(
                    'name' => $oProfile -> getDisplayName(),
                    'thumb' => $oProfile -> getThumb(),
                    'id' => $oProfile -> id()
                );
        }

        return $aResult;
    }

    public function actionSearchProfile(){
        $iExclude = bx_get('exclude');
	    $aProfiles = $this -> getProfilesByTerm(bx_get('term'));

        $aResult = array('code' => 1);
        if (empty($aProfiles))
            return echoJson($aResult);

        $aVars = array('bx_repeat:items' => array());
        foreach($aProfiles as &$aProfile) {
            if ($iExclude == $aProfile['id'])
                continue;

            $aVars['bx_repeat:items'][] = array(
                'thumb' => $aProfile['thumb'],
                'title' => $aProfile['name'],
                'referral' => $aProfile['id']
            );
        }
        $aResult = array('code' => 0, 'html' => $this->_oTemplate->parseHtmlByName('search-referral-item.html', $aVars));
        echoJson($aResult);
    }

    public function actionAddProgram(){
        if (!isAdmin())
            return;

        $iEntryId = bx_get('id');
        if (!$iEntryId)
            $iEntryId = bx_get('entry_id');

        $sType = bx_get('type');
        $mixedContent = !$sType || $sType === AQB_PROGRAM_TYPE_DEFAULT ?
                                                $this -> _oTemplate -> getAddProgramForm($iEntryId) :
                                                $this -> _oTemplate -> getMatrixSettingsForm($iEntryId);
        if (is_array($mixedContent))
            return echoJson($mixedContent);

        return echoJson(array(
                        'popup' => array(
                        'html' => BxTemplFunctions::getInstance()->popupBox('aqb-popup-program-form', _t('_aqb_affiliate_add_program_title'), $mixedContent),
                        'options' => array('closeOnOuterClick' => false))
                      ));
    }

    public function actionSetCommissionsRates(){
        if (!isAdmin())
            return;

        $iEntryId = bx_get('id');
        $iAclId = bx_get('acl_level_id');
        if (!$iEntryId)
            $iEntryId = bx_get('program_id');

        $mixedContent = $this->_oTemplate->getAddCommissionsForm($iEntryId, $iAclId);
        if (is_array($mixedContent))
            return echoJson($mixedContent);

        return echoJson([
            'popup' => [
                'html' => BxTemplFunctions::getInstance()->popupBox('aqb-popup-program-form', _t('_aqb_affiliate_add_program_title'), $mixedContent),
                'options' => ['closeOnOuterClick' => false]]
        ]);
    }

    function actionGetAclCommissionsFields(){
        if (!isAdmin())
            return;

        $iProgramId = bx_get('program_id');
        $iAclId = bx_get('acl_id');
        $bBeforeClear = (int)bx_get('clear');
        if (!$iProgramId || !$iAclId)
            return echoJson(['code' => 1, 'msg' => _t('_aqb_affiliate_not_data_error')]);

        if ($bBeforeClear)
            $this->_oDb->removeAclCommission($iProgramId, $iAclId);

        $oForm = new BxTemplFormView([]);
        $aInfo = $this->_oDb->getAclCommission($iProgramId, $iAclId);
        $oForm -> aInputs = $this->_oTemplate->getCommissionFormFields($aInfo, true);

        echoJson(['html' => $oForm->genRows()]);
    }

    public function actionGetProgramsPreview(){
        if (!isAdmin())
            return;

        echoJson(array('html' => $this->_oTemplate->getProgramsPreview()));
    }

    public function actionRemoveProgram(){
        if (!isAdmin())
            return echoJson(array(
                'code' => 1,
                'msg' => _t('_aqb_affiliate_only_admin')
            ));

        $iProgramId = bx_get('id');
        if (!$iProgramId || !($aProgramInfo = $this -> _oDb -> getProgramById($iProgramId)))
            return echoJson(array(
                'msg' => _t('_aqb_affiliate_admin_program_not_found')
            ));

        echoJson(array('code' => $this -> _oDb -> deleteProgram($iProgramId)));
    }

    public function actionSetMatrixCommissionForm(){
        $CNF = &$this->_oConfig->CNF;
	    if (!isAdmin())
            return echoJson(array(
                'code' => 1,
                'msg' => _t('_aqb_affiliate_only_admin')
            ));

        $iProgramId = bx_get('id');
        if (!$iProgramId || !($aProgramInfo = $this -> _oDb -> getProgramById($iProgramId)))
            return echoJson(array(
                                    'code' => 1,
                                    'msg' => _t('_aqb_affiliate_admin_program_not_found')
                                  ));

        $sContent = $this->_oTemplate->setMatrixCommissionForm($iProgramId);
        if (is_array($sContent))
            return echoJson($sContent);

        $aMembershipInfo = BxDolAcl::getInstance() -> getMembershipInfo($aProgramInfo[$CNF['FMP_MEMLEVEL']]);
        return echoJson(array(
                                'popup' => array(
                                'html' => BxTemplFunctions::getInstance()->popupBox('matrix-levels-commissions', _t('_aqb_affiliate_program_set_commission_to_program', _t($aMembershipInfo['name'])), $sContent),
                                'options' => array('closeOnOuterClick' => false))
                        ));
    }

    public function actionSaveMatrixCommission(){
	    $iProgramId = (int)bx_get('program');

	    if (!isAdmin() || !$iProgramId || !$this->_oDb->saveMatrixCommission($iProgramId, $_POST))
	        return echoJson(array(
                'msg' => _t('_aqb_affiliate_matrix_levels_settings_are_empty'),
                'code' => 1
            ));

        echoJson(array(
               'code' => 0
        ));
    }

    public function serviceGetProfileInfo(){
	    if (!$this->isLogged())
	        return '';

	    return $this->_oTemplate->getProfileInfo($this->_iProfileId);
    }

    public function serviceGetProfileStat(){
        if (!$this->isLogged())
            return '';

        return '';
    }

    public function serviceGetReferralsLinks(){
        if (!$this->isLogged())
            return '';
	    return $this -> _oTemplate -> getReferralLinksBlock($this->_iProfileId);
    }

    public function actionGetProgramsInfo(){
        $sContent = $this -> _oTemplate -> getProgramsInfoList();
	    return echoJson(array(
            'popup' => array(
                'html' => BxTemplFunctions::getInstance()->popupBox('programs-info', _t('_aqb_affiliate_profile_program_info_block'), $sContent),
                'options' => array('closeOnOuterClick' => true))
        ));
    }

    public function actionGetProfileTree(){
        $iUserId = bx_get('user_id');
        $oMatrix =  new AqbAffiliateCloserTable($this->_oConfig);
        $iProfileId = $oMatrix->getProfileIdByUserId($iUserId);
        if ($iProfileId !== $this->_iProfileId && !isAdmin($this->_iProfileId))
            return echoJson(array( 'msg' => _t('_aqb_affiliate_you_can_not_view_tree')));

        $sContent = $this->_oTemplate->getUserTree($iUserId, $iProfileId);
	    return echoJson(array(
                                'popup' => array(
                                                 'html' => BxTemplFunctions::getInstance()->transBox('profile-tree', $sContent), //_t('_aqb_affiliate_profile_tree', BxDolProfile::getInstance($iProfileId) -> getDisplayNAme())
                                                 'options' => array('closeOnOuterClick' => true)
                                                )
                            ));
    }

    function actionSendInvitation(){
        $CNF = &$this->_oConfig->CNF;

        if (!$CNF['SEND_INVITATIONS'] || !$this->isLogged())
            return '';

        $sMessage = bx_get('message');
        $aEmails = preg_split('/[\s,]+/',  bx_get('emails'), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($aEmails) || !($aEmails = array_filter($aEmails, function($sEmail){
                                                                        return filter_var($sEmail, FILTER_VALIDATE_EMAIL);
                                                                      })))
            return echoJson(
                array(
                    'code' => 1,
                    'msg' => _t('_aqb_affiliate_invitation_emails_list_empty')
                ));

        $iEmails = count($aEmails);
        $iAllowedNumber = (int)$CNF['MAX_INVITATIONS_NUMBER'];
        $iGetAlreadySent = $this -> _oDb -> getAlreadySentEmailsNumber($this->_iProfileId);
        if ($iAllowedNumber < ($iEmails + $iGetAlreadySent))
            return echoJson(
                            array(
                                    'code' => 1,
                                    'msg' => _t('_aqb_affiliate_max_email_error', $iGetAlreadySent, $iAllowedNumber)
                                 ));

        if (trim($sMessage))
            $sMessage = substr(html2txt($sMessage), 0, $CNF['MESSAGE_SYMBOLS_NUMBER']);

        $bIsUserMessage = $sMessage && $CNF['SEND_INVITATIONS'];
        $sRefLink = bx_get('page_url') ? bx_get('page_url') : $this->_oConfig->getReferralLink($this->_iProfileId);
        $aVars = array(
            'RealName' => BxDolProfile::getInstance() -> getDisplayName($this->_iProfileId),
            'message' => $bIsUserMessage ? $sMessage : _t('_aqb_affiliate_site_invitation_message'),
            'RefLink' => $sRefLink,
            'RefTitle' => $bIsUserMessage ? $sRefLink : getParam('site_title')
        );

        $oEmailTemplates = BxDolEmailTemplates::getInstance();
        if (!$oEmailTemplates)
            return echoJson(
                array(
                    'code' => 1,
                    'msg' => _t('_aqb_affiliate_email_template_not_found')
                ));

        $aTemplate = $oEmailTemplates->parseTemplate($this->_oConfig->getName() . '_invitation', $aVars);
        if (!$aTemplate)
            return false;

        $oChecker = new BxDolFormCheckerHelper();
        $iCounter = 0;

        foreach($aEmails as &$sEmail)
         if ($oChecker -> checkEmail($sEmail) && sendMail($sEmail, $aTemplate['Subject'], $aTemplate['Body'], 0, array(), BX_EMAIL_MASS, 'html', false, array(), $CNF['USE_QUEUE'])) {
             $iCounter++;
            if ($this -> _oDb -> registerInvitation((int)$this->_iProfileId, $sEmail)) {
                bx_alert($this->_oConfig->getName(), 'invitation_sent', (int)$this->_iProfileId, (int)$this->_iProfileId, array('recipient' => $sEmail));
            }
         }

        $aResult = array('code' => 1, 'msg' => _t('_aqb_affiliate_invitation_not_sent'));
        if ($iCounter)
            $aResult = array(
                              'code' => 0,
                              'msg' => $iCounter == 1 ? _t('_aqb_affiliate_invitation_successfully_sent_single') : _t('_aqb_affiliate_invitation_successfully_sent', $iCounter),
                            );

        echoJson($aResult);
    }

    function actionGetInvitationForm(){
        if (!$this->isLogged())
            return '';

        $sContent = $this -> _oTemplate -> getInvitationForm(bx_get('page_url'));
        echoJson(array(
            'popup' => array(
                'html' => BxTemplFunctions::getInstance()->popupBox('send-invitations-form', _t('_aqb_affiliate_send_invitation'), $sContent),
                'options' => array('closeOnOuterClick' => false))
        ));
    }

    public function serviceResponse($oAlert){
	    switch ($oAlert -> sUnit){
            case 'system':
                if ($oAlert->sAction === 'begin')
                    $this->processSystemBegin($oAlert);
                else
                {
                    $this->_oConfig->logData($oAlert);
                    if ($oAlert->sAction === 'register_payment' && !empty($oAlert->aExtras) && isset($oAlert->aExtras['pending'])) {
                        $aResult = &$oAlert->aExtras['pending'];

                        $aTransactionInfo = BxDolService::call('bx_payment', 'get_orders_info', array(
                            array('pending_id' => $aResult['id'])
                        ), 'Orders');

                        $this->_oConfig->logData($aTransactionInfo, false);
                        foreach ($aTransactionInfo as &$aTransaction) {
                            $aModuleInfo = $this->_oDb->getModuleById((int)$aTransaction['module_id']);
                            $this->_oConfig->logData('----- transaction info ------', false);
                            $this->_oConfig->logData($aModuleInfo, false);
                            if (empty($aModuleInfo) || !(float)$aTransaction['amount'] || !(int)$aModuleInfo['enabled'])
                                continue;

                            $sAction = AQB_AFF_PURCHASE;
                            switch ($aModuleInfo['name']) {
                                case 'bx_market':
                                    $sAction = AQB_AFF_MARKET;
                                    break;
                                case 'bx_acl':
                                    $sAction = AQB_AFF_UPGRADE;
                                    break;
                            }

                            $this->_oDb->registerProfileAction($aTransaction['client_id'], $sAction, (float)$aTransaction['amount']);
                        }
                    }
                }

                break;
            case 'account':
                $oAccount = BxDolAccount::getInstance($oAlert->iObject);
                $this->_oConfig->logData($oAlert);
                if ($oAccount) {
                    $bConfirm = $oAccount::isNeedConfirmEmail() || $oAccount::isNeedConfirmPhone();
                    if (($oAlert->sAction === 'added' && !$bConfirm) || ($oAlert->sAction === 'confirm' && $bConfirm)) {
                        $this->registerNewAccount($oAlert->iObject);
                        break;
                    }

                    if ($oAlert->sAction === 'added' && $bConfirm) {
                       $this->_oDb->addSponsorTrack($oAlert->iObject, $this->_oConfig->getAdsCode());
                       break;
                    }
                }

                if ($oAlert->sAction === 'delete' && (int)$oAlert->iObject) {
                    $this->_oDb->removeEmptyReferral((int)$oAlert->iObject);
                    $this->_oDb->removeSponsorTrack((int)$oAlert->iObject);
                }
                break;

            case 'profile':
                $this->_oConfig->logData($oAlert);
                if (in_array($oAlert->sAction, array('add', 'activate', 'approve'))) {
                        $this->registerNewProfile($oAlert->iObject);
                }

                if ($oAlert->sAction === 'delete')
                    $this->_oDb->unregisterProfile($oAlert->iObject);

                if ($oAlert->sAction === 'set_membership'){
                    $CNF = &$this->_oConfig->CNF;
                    $iSponsor = $this->_oDb->getSponsorProfileId($oAlert->iSender);
                    $this->_oConfig->logData("----- get referral of updated profile ------ $iSponsor {$oAlert->iSender}", false);
                    if ($iSponsor) {
                        $aProgram = $this->_oDb->getProfileProgram($iSponsor);
                        if (!empty($aProgram) && (int)$aProgram[$CNF['FMP_MATRIX']] && (int)$oAlert->aExtras['mlevel'] === (int)$aProgram[$CNF['FMP_MEMTOADD']]){
                            $oMatrix = new AqbAffiliateCloserTable($this->_oConfig);
                            if (!$oMatrix->hasEntry($oAlert->iSender)) {
                                $this->_oDb->addProfileToTheMatrix($oAlert->iSender);
                            }
                        }
                    }
                }
        };

	    return false;
    }
    
    private function registerNewProfile($iProfileId)
    {
        $CNF = &$this->_oConfig->CNF;
        $oProfile = BxDolProfile::getInstance($iProfileId, true);
        if (!$oProfile || !in_array($oProfile->getModule(), $CNF['ALLOWED_PROFILE_TYPES']))
            return false;

        if ($this->_oDb->getSponsorProfileId($iProfileId) || !$this->_oDb->isProfileActive($iProfileId))
            return false;

        if ($this->_oDb->registerReferredProfile($iProfileId)) {
            $iSponsor = $this->_oDb->getSponsorProfileId($iProfileId);

            if ($CNF['ADD_TO_FRIENDS_LIST'] && $iSponsor && ($oConnection = BxDolConnection::getObjectInstance('sys_profiles_friends'))) {
               $oConnection->addConnection($iSponsor, $iProfileId);
               $oConnection->addConnection($iProfileId, $iSponsor);
                bx_alert($this->_oConfig->getName(), 'friend_registered', $iProfileId, $iSponsor);
            }

            if ($this->_oDb->isProfileMatrixEnabled($iSponsor))
                $this->_oDb->addProfileToTheMatrix($iProfileId);

            $aReferralInfo = $this->_oDb->getRefInfoByInviteeId($iProfileId);
            if ((int)$aReferralInfo[$CNF['FR_BANNER']])
                $this->_oDb->registerBannersAction($aReferralInfo[$CNF['FR_REFERRAL']], $aReferralInfo[$CNF['FR_BANNER']], AQB_AFF_JOIN);
            else
                $this->_oDb->registerProfileAction($iProfileId, AQB_AFF_JOIN);

            $this->_oDb->updateUserInfo($iSponsor, $CNF['FI_REFERRED']);
        }
    }

    private function registerNewAccount($iAccountId)
    {
        $CNF = &$this->_oConfig->CNF;

        $sAdsCode = $this->_oConfig->getAdsCode();
        if (!$sAdsCode && !($sAdsCode = $this->_oDb->getSponsorCode($iAccountId)))
            return false;
        else
            $this->_oDb->removeSponsorTrack($iAccountId);

        $mixedProfile = $this->decryptAdsCode($sAdsCode);
        if (!$mixedProfile)
            return false;

        list($iReferral, $iBannerId) = $mixedProfile;
        $oProfile = BxDolProfile::getInstance($iReferral);
        if (!$oProfile)
            return false;

        if ($this->_oDb->registerReferredAccount($iReferral, $iAccountId, $iBannerId)){
            $oAccount = BxDolAccount::getInstance ($iAccountId);
            if ($oAccount && ($aProfiles = $oAccount->getProfiles())){
                $this->_oConfig->logData($aProfiles, false);
                foreach($aProfiles as $iKey => $aProfile)
                    $this->registerNewProfile($iKey);
            }
            $aUrl = parse_url(BX_DOL_URL_ROOT);
            $sPath = !empty($aUrl['path']) ? $aUrl['path'] : '/';
            setcookie($CNF['COOKIE_ADS_CODE'], '', time() - 24 * 60 * 60, $sPath, '', false, true /* http only */);
        }
    }

    private function processSystemBegin(&$oAlert){
        if (isLogged())
            return false;

	    $CNF = &$this->_oConfig->CNF;

	    // check if registration by invitation is enabled
        $bRegistrationByInvitation = getParam('bx_invites_enable_reg_by_inv') == 'on';
	    $sInvitationModKey = bx_get($CNF['INVITATIONS']['prefix']);
	    if ($sInvitationModKey && $bRegistrationByInvitation){
            $iProfileId = $this->_oDb->getProfileIdByInvKey($sInvitationModKey);
            if ($iProfileId)
               $sAdsCode = $this->_oConfig->getAdsHash($iProfileId);
            else
               return false;
        }
	    else
	    {
            $sAdsCode = bx_get($CNF['REFER_PREFIX']);
            if (!$sAdsCode)
               return false;

            $mixedProfile = $this->decryptAdsCode($sAdsCode);
            if (!$mixedProfile)
               return false;

            list($iProfileId,) = $mixedProfile;

            if ($bRegistrationByInvitation){
                $oSession = BxDolSession::getInstance();
                $oKeys = BxDolKey::getInstance();
                $iKeyLifetime = 86400 * (int)getParam('bx_invites_key_lifetime');
                $sKey = $oKeys->getNewKey(false, $iKeyLifetime);
                if($oKeys && $oKeys->isKeyExists($sKey))
                    $oSession->setValue($CNF['INVITATIONS']['prefix'], $sKey);
            }
        }

	    $oProfile = BxDolProfile::getInstance($iProfileId);
        if ($oProfile){
            $aUrl = parse_url(BX_DOL_URL_ROOT);
            $sPath = !empty($aUrl['path']) ? $aUrl['path'] : '/';

            $iCookieTime = $CNF['KEEP_COOKIE'] ? time() + 24 * 60 * 60 * $CNF['KEEP_COOKIE'] : 0;
            setcookie($CNF['COOKIE_ADS_CODE'], $sAdsCode, $iCookieTime, $sPath, '', false, true /* http only */);
        }
    }

    public function serviceGetReferralsInfo(){
	    return $this->_oTemplate->getReferralsStatBlock($this->_iProfileId);
    }

    public function serviceGetActivitiesInfo($iProfileId = 0){
        $this->_oTemplate->addJsTranslation(array('_aqb_affiliate_cash_out_confirm'), true);
        return $this->_oTemplate->getActivitiesQuickInfo( $iProfileId ? $iProfileId : $this->_iProfileId );
    }

    public function serviceGetToolsInfo(){
        return $this->_oTemplate->getBannersProfileInfo($this->_iProfileId);
    }

    public function serviceGetActivitiesTable( $iProfileId = 0 ){
        $oGrid = BxDolGrid::getObjectInstance($this -> _oConfig -> getGridObject('history'));
        $this->_oTemplate->addCss(array('history.css', 'referrals.css', 'admin.css'));
        return $oGrid->getCode();
    }

    public function serviceGetToolsTable(){
        if (!$this->_iProfileId)
            return false;

        return $this->_oTemplate->getUsersBannersArea($this->_iProfileId);
    }

    public function serviceGetReferralsTable(){
        $oGrid = BxDolGrid::getObjectInstance($this -> _oConfig -> getGridObject('common'));
        $this->_oTemplate->addCss('referrals.css');
	    return $oGrid->getCode();
    }

    public function serviceGetProfileMatrix(){
        $CNF = &$this->_oConfig->CNF;
        $aProgramSettings = $this->_oDb->getProfileProgram($this->_iProfileId);
        if (empty($aProgramSettings) || !(int)$aProgramSettings[$CNF['FMP_MATRIX']])
            return '';

        $oMatrix = new AqbAffiliateCloserTable($this->_oConfig);
        $aMatrices = $oMatrix->getProfileMatrices($this->_iProfileId);
        if (empty($aMatrices))
            return MsgBox(_t('_aqb_affiliate_referrals_page_not_matrix_yet'));

        $aVars = array();
        $sJsObject = $this->_oConfig->getJsObject('utils');
        foreach($aMatrices as $iUserId => $aMatrix){
            $iTotal = $iSpillover = $iDirectly = 0;
            foreach($aMatrix[$CNF['BRANCH']] as &$aProfile){
              if ($this->_oDb->isDirectlyInvited($this->_iProfileId, $aProfile[$CNF['FPTREE_PROFILE_ID']]))
                $iDirectly++;
              else
                $iSpillover++;

              $iTotal++;
            }

            $bIsActive = false;
            $aProgram = $aMembership = [];
            if ($aMatrix[$CNF['FPTREE_MEM_LEVEL']]) {
                $aMembership = BxDolAcl::getInstance()->getMembershipInfo($aMatrix[$CNF['FPTREE_MEM_LEVEL']]);
                if (!empty($aMembership) && !empty($aMembership['id'])) {
                    $aProgram = $this->_oDb->getProgramByMembership($aMembership['id']);
                    if (is_array($aProgram) && isset($aProgram[$CNF['FMP_ACTIVE']])) {
                        $bIsActive = (int)$aProgram[$CNF['FMP_ACTIVE']];
                    }
                }
            }

                $aVars['bx_repeat:items'][] = array(
                    'level' => empty($aProgram) ? _t('_aqb_affiliate_unknown') : _t($aMembership['name']),
                    'icon' => empty($aProgram) ? 'question-circle' : $aMembership['icon'],
                    'total' => $iTotal,
                    'directly' => $iDirectly,
                    'spillover' => $iSpillover,
                    'status' => _t('_aqb_affiliate_program_status_' . ( $bIsActive ? 'enabled' : 'disabled' )),
                    'user_id' => $iUserId,
                    'js_object' => $sJsObject,
                );
        }

        return $this->_oTemplate->parseHtmlByName('referral-profile-matrix-item.html', $aVars);
    }

    function actionAdsView(){
        $CNF = &$this->_oConfig->CNF;
        $iBannerId = bx_get('id');
	    if (!$iBannerId || !($aBannerInfo = $this->_oDb->getBannerInfo($iBannerId)))
	        return echoJson(array('code' => 1, 'msg' => _t('_aqb_affiliate_admin_banner_not_found')));

        $oStorage = BxDolStorage::getObjectInstance($CNF['OBJECT_STORAGE']);
        echoJson(array(
            'popup' => array(
                'html' => BxTemplFunctions::getInstance()->popupBox('aqb-popup-banner-view-form', _t($aBannerInfo[$CNF['FB_TITLE']]), '<img src="' . $oStorage->getFileUrlById($aBannerInfo[$CNF['FB_IMG']]) . '" />'),
                'options' => array(
                    'closeOnOuterClick' => true
                )
        )));
    }

    function actionAdsCode(){
        $iBannerId = bx_get('id');
        if (!$iBannerId || !($sBanner = $this->_oTemplate->getBannerCode($this->_iProfileId, $iBannerId)))
            return echoJson(array('code' => 1, 'msg' => _t('_aqb_affiliate_admin_banner_not_found')));

        echoJson(array(
            'popup' => array(
                'html' => BxTemplFunctions::getInstance()->popupBox('aqb-popup-banner-code-form', '', $sBanner),
                'options' => ['closeOnOuterClick' => true]
            )));
    }

    function validateAdsCode($sCode, $iProfileId, $iBannerId){
	   $sHash = $this->_oConfig->getAdsHash($iProfileId, $iBannerId);
	   return strcmp($sCode, $sHash) === 0;
    }

    function decryptAdsCode($sAds){
        $mixedData = $this->_oConfig->cryptData($sAds, 'decrypt');
        if (!$mixedData)
            return false;

        $iBannerId = 0;
        if (is_numeric($mixedData))
            $iProfileId = (int)$mixedData;
        else
            list($iProfileId, $iBannerId) = explode(':', $mixedData);

        return array($iProfileId, $iBannerId);
    }

    function executeActionByAdsCode($sAds, $iAction = AQB_AFF_IMPRESSION , &$iProfileId = 0, &$iBannerId = 0){
        if (!$sAds)
            return _t('_aqb_affiliate_hash_not_found');

        $mixedData = $this->decryptAdsCode($sAds);
        if ($mixedData === FALSE)
            return _t('_aqb_affiliate_hash_invalid');

        list($iProfileId, $iBannerId) = $mixedData;
        if (!$iProfileId || !$iBannerId || !$this->validateAdsCode($sAds, $iProfileId, $iBannerId))
            return _t('_aqb_affiliate_hash_invalid');

        $aBannerInfo = $this->_oDb->getBannerInfo($iBannerId);
        if (empty($aBannerInfo))
            return _t('_aqb_affiliate_hash_invalid');

        return $this->_oDb->registerBannersAction($iProfileId, $iBannerId, $iAction);
    }

    function actionGenerateAds(){
        $this->_oConfig->getCORSResponse();

        $iBannerId = $iProfileId = 0;
        $mixedResult = $this->executeActionByAdsCode(bx_get('ads'), AQB_AFF_IMPRESSION, $iProfileId, $iBannerId);
        if (is_string($mixedResult))
            return echoJson(array('code' => 1, 'message' => $mixedResult));

        return echoJson(array('code' => 0, 'html' => $this->_oTemplate->getAds($iProfileId, $iBannerId)));
    }

    function actionPerformAction(){
        $this->_oConfig->getCORSResponse();

        $sAds = bx_get('ads');
        $mixedResult = $this->executeActionByAdsCode(bx_get('ads'), AQB_AFF_CLICK);
        if (!is_bool($mixedResult))
            return echoJson(array('code' => 1, 'message' => $mixedResult));

        return echoJson(array('code' => 0, 'uid' => $sAds));
    }

    function actionUpdateProgramStatus(){
	    if (!isAdmin())
	        return '';

	    $iProgramId = (int)bx_get('id');
	    $aProgram = array();
	    if (!$iProgramId || !($aProgram = $this->_oDb->getProgramById($iProgramId)))
            echoJson(array('code' => 1, 'message' => _t('_aqb_affiliate_program_not_found')));

	    $CNF = &$this->_oConfig->CNF;
        $this->_oDb->updateProgram($iProgramId, $CNF['FMP_ACTIVE'], +!$aProgram[$CNF['FMP_ACTIVE']]);

	    return echoJson(array('html' => $this->_oTemplate->getProgramsPreview()));
    }

    function actionViewProfileCommission(){
        $oGrid = BxDolGrid::getObjectInstance($this -> _oConfig -> getGridObject('requests'));
        $oGrid->addMarkers(array(
            'profile_id' => $this->_iProfileId,
            'type' => 'unpaid'
        ));

        $sContent = $oGrid->getCode();
        return echoJson(array(
            'popup' => array(
                'html' => BxTemplFunctions::getInstance()->popupBox('commissions-info', _t('_aqb_affiliate_profile_commission_info_title_' . bx_get('type')), $sContent),
                'options' => array('closeOnOuterClick' => true)
        )));
    }

    public function actionCashOut(){
        if (!$this->isLogged())
            return echoJson(array(
                'msg' => _t('_aqb_affiliate_not_login')
            ));

        $CNF = &$this->_oConfig->CNF;
        $aCommission = $this->_oDb->getAvailableForCashOutCommissions($this->_iProfileId);
        if (empty($aCommission) || (!(float)$aCommission[$CNF['FC_AMOUNT']] && !(float)$aCommission[$CNF['FC_POINTS']] && !(float)$aCommission[$CNF['FC_CREDITS']]))
            return echoJson(array(
                'msg' => _t('_aqb_affiliate_profile_commission_not_enough')
            ));

        $sContent = $this->_oTemplate->getCashOutForm($this->_iProfileId);
        return echoJson(array(
                                'popup' => array(
                                    'html' => BxTemplFunctions::getInstance()->popupBox('commissions-cash-out', _t('_aqb_affiliate_cash_out_form_title'), $sContent),
                                    'options' => array('closeOnOuterClick' => false)
                              )));
    }

    public function actionPerformCashOut(){
        if (!$this->isLogged())
            return echoJson(array(
                'msg' => _t('_aqb_affiliate_not_login')
            ));

        $CNF = &$this->_oConfig->CNF;
        $fPoints = bx_get($CNF['FC_POINTS']);
        $fCredits = (float)bx_get($CNF['FC_CREDITS']);
        $fAmount = (float)bx_get($CNF['FC_AMOUNT']);
        $aSum = [];
        if (!$fCredits && !$fPoints && !$fAmount){
            return echoJson(array(
                'msg' => _t('_aqb_affiliate_cash_out_commission_error'),
                'code' => 1
            ));
        }

        $bIsPointEnabled = $this->_oDb->isPointsEnabled();
        $bIsCreditsEnabled = $this->_oDb->isCreditsEnabled();
        $bIsFiatEnabled = $this->_oDb->isFiatEnabled();
        $aCommission = $this->_oDb->getAvailableForCashOutCommissions($this->_iProfileId);

        $fCM = isset($aCommission[$CNF['FC_AMOUNT']]) && $aCommission[$CNF['FC_AMOUNT']] ? $aCommission[$CNF['FC_AMOUNT']] : 0;
        $fPS = isset($aCommission[$CNF['FC_POINTS']]) && $aCommission[$CNF['FC_POINTS']] ? $aCommission[$CNF['FC_POINTS']] : 0;
        $fCS = isset($aCommission[$CNF['FC_CREDITS']]) && $aCommission[$CNF['FC_CREDITS']] ? $aCommission[$CNF['FC_CREDITS']] : 0;
        if (empty($aCommission) || (!$fCM && !$fPS && !$fCS))
            return echoJson(array(
            'msg' => _t('_aqb_affiliate_profile_commission_not_enough'),
            'code' => 1
        ));

        if (($bIsFiatEnabled && $fCM && ($CNF['CASH_OUT_LIMIT'] > $fCM || $fAmount > $fCM)) ||
             ($bIsPointEnabled && $fPoints && $fPS < $fPoints) ||
             ($bIsCreditsEnabled && $fCredits && $fCS < $fCredits))
           return echoJson(array(
               'msg' => _t('_aqb_affiliate_profile_commission_not_valid'),
               'code' => 1
           ));

       if ($fPoints)
            $aSum[] = _t('_aqb_affiliate_cash_out_points', $fPoints);

        if ($fCredits)
            $aSum[] = _t('_aqb_affiliate_cash_out_credits', $fCredits);

       if ($fAmount) {
           $aCurr = bx_srv('bx_payment', 'get_currency_info');
           $aSum[] = _t('_aqb_affiliate_cash_out_currency', $fAmount, $aCurr['code']);
       }

        if ($iTnx = $this->_oDb->addCommissionsRequest($this->_iProfileId, $fAmount, $fPoints, $fCredits)) {
            $oProfile = BxDolProfile::getInstance();
            $aVars = array(
                'RealName' => '<a href="' . $oProfile->getUrl() . '">' . $oProfile->getDisplayName($this->_iProfileId) . '</a>',
                'Sum' => implode('<br/>', $aSum),
                'studio_requests' => BX_DOL_URL_STUDIO . 'module.php?name=aqb_affiliate&page=requests'
            );

            $oEmailTemplates = BxDolEmailTemplates::getInstance();
            if ($oEmailTemplates && ($aTemplate = $oEmailTemplates->parseTemplate('aqb_affiliate_cash_out_request', $aVars)))
                sendMail(getParam('site_email_notify'), $aTemplate['Subject'], $aTemplate['Body'], 0, array(), BX_EMAIL_MASS, 'html', false, array());

            if ($CNF['SEND_POINTS_IMMEDIATELY'] && $fPoints) {
                BxDolService::call('aqb_points', 'assign_points', array($fPoints, _t('_aqb_affiliate_profile_commission_cash_out_points'), $this->_iProfileId));
                if (!$fAmount && !$fCredits)
                    return echoJson(array(
                        'msg' => _t('_aqb_affiliate_cash_out_points_sent')
                    ));
            }

            if ($CNF['SEND_CREDITS_IMMEDIATELY'] && $fCredits) {
                $iCreditsTnxId = bx_srv('bx_credits', 'update_profile_balance', [$this->_iProfileId, $fCredits, 0, '', _t('_aqb_affiliate_profile_commission_cash_out_credits')]);

                if ($iCreditsTnxId)
                    $this->_oDb->updateTnxInfo($iTnx, [$CNF['FC_PCREDITS'] => $iCreditsTnxId]);

                if (!$fAmount && !$fPoints)
                    return echoJson(array(
                        'msg' => _t('_aqb_affiliate_cash_out_credits_sent')
                    ));
            }

            return echoJson(array(
                'msg' => _t('_aqb_affiliate_cash_out_request_sent')
            ));
        }

    }

    public function serviceGetCartItems($iSellerId)
    {
        $CNF = &$this->_oConfig->CNF;

        $iSellerId = (int)$iSellerId;
        if(empty($iSellerId))
            return array();

        $aItems = $this->_oDb->getProfileTxns($iSellerId, AQB_AFF_STATUS_PENDING);
        $aResult = array();
        foreach($aItems as $aItem) {
            $aResult[] = array(
                'id' => $aItem[$CNF['FC_ID']],
                'author_id' => $aItem[$CNF['FC_PROFILE_ID']],
                'name' => _t('_aqb_affiliate_exchange_cart_item'),
                'title' => _t('_aqb_affiliate_exchange_cart_item_title'),
                'url' => BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=affiliate-activities'),
                'price_single' => $aItem[$CNF['FC_AMOUNT']],
                'price_recurring' => 0,
                'period_recurring' => 0,
                'period_unit_recurring' => 0,
                'trial_recurring' => 0
            );
        }

        return $aResult;
    }

    public function serviceGetCartItem($iItemId)
    {
        $CNF = &$this->_oConfig->CNF;

        if(!$iItemId)
            return array();

        $aItem = $this->_oDb->getTxnInfo($iItemId);
        if(empty($aItem) || !is_array($aItem))
            return array();

        $oRecipientInfo = BxDolProfile::getInstance($aItem[$CNF['FC_PROFILE_ID']]);
        return array (
            'id' => $aItem[$CNF['FC_ID']],
            'author_id' => $aItem[$CNF['FC_PROFILE_ID']],
            'name' => _t('_aqb_affiliate_exchange_cart_item'),
            'title' => _t('_aqb_affiliate_exchange_cart_item_title'),
            'description' => _t('_aqb_affiliate_exchange_cart_item_desc', '<a href="' . $oRecipientInfo->getUrl() . '">' . $oRecipientInfo->getDisplayName() . '</a>'),
            'url' => BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=affiliate-activities'),
            'price_single' => $aItem[$CNF['FC_AMOUNT']],
            'price' => $aItem[$CNF['FC_AMOUNT']],
        );
    }

    function serviceRegisterCartItem($iClientId, $iSellerId, $iItemId, $iItemCount, $sOrderId) {
        $CNF = &$this->_oConfig->CNF;

        $aItem = $this->_oDb->getTxnInfo($iItemId);
        if(empty($aItem) || !is_array($aItem) || $aItem[$CNF['FC_PROFILE_ID']] != $iSellerId)
            return array();

        if ($this->_oDb->updateTnxInfo($iItemId, array(
            $CNF['FC_TXN'] => $sOrderId,
            $CNF['FC_STATUS'] => AQB_AFF_STATUS_PAID,
            $CNF['FC_PAID'] => time(),
        )))
       {
           $sURL = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=affiliate-activities');
           $aCurr = bx_srv('bx_payment', 'get_currency_info');
           $aPlus = array(
               'RealName' => BxDolProfile::getInstance() -> getDisplayName($iSellerId),
               'sum' => $aItem[$CNF['FC_AMOUNT']],
               'cur' => $aCurr['code'],
               'statistic_page' => '<a href="' . $sURL . '">' . _t('_aqb_affiliate_page_block_title_stat') . '</a>'
           );

           sendMailTemplate('aqb_affiliate_commission_paid', 0, $iSellerId, $aPlus, BX_EMAIL_SYSTEM);
       }

       return $aItem;
	}

    function serviceUnregisterCartItem($iClientId, $iSellerId, $iItemId, $iItemCount, $sOrderId) {
        if(!$this -> isLogged())
            return false;

        $CNF = &$this->_oConfig->CNF;
        $aTnxInfo = $this->_oDb->getTxnInfo($iItemId);
        if ($this->_oDb->updateTnxInfo($iItemId, array(
                $CNF['FC_STATUS'] => AQB_AFF_STATUS_PENDING
            ))) {
            if ($this->_oDb->isPointsEnabled() && $aTnxInfo[$CNF['FC_POINTS']]) {
                $this->_oDb->updateTnxInfo($iItemId, array(
                    $CNF['FC_PPOINTS'] => 0,
                    $CNF['FC_PAID'] => 0,
                    $CNF['FC_PCREDITS'] => 0,
                ));

                BxDolService::call('aqb_points', 'assign_points', array(-$aTnxInfo[$CNF['FC_POINTS']], _t('_aqb_affiliate_profile_commission_cash_out_cancelled_points'), $aTnxInfo[$CNF['FC_PROFILE_ID']]));
            }

            if ($this->_oDb->isCreditsEnabled() && $aTnxInfo[$CNF['FC_CREDITS']] && (int)$aTnxInfo[$CNF['FC_PCREDITS']]) {
               $oCreditsModule = BxDolModule::getInstance('bx_credits');
               if ($oCreditsModule && $oCreditsModule->_oDb->deleteHistory(['id' => (int)$aTnxInfo[$CNF['FC_PCREDITS']]])) {
                   $this->_oDb->updateTnxInfo($iItemId, [$CNF['FC_PCREDITS'] => 0]);
                   $oCreditsModule->_oDb->updateProfileBalance($aTnxInfo[$CNF['FC_PROFILE_ID']], -$aTnxInfo[$CNF['FC_CREDITS']]);
               }
            }
        }
    }

    function actionRebuildMatrix(){
        if(!isAdmin())
            return false;

        if (!$this->_oDb->rebuildMatrix())
            return echoJson(array('code' => 1, 'msg' => _t('_aqb_affiliate_matrix_update_failed')));

        echoJson(array('code' => 0, 'msg' => _t('_aqb_affiliate_matrix_update_success')));
    }

    function actionGetInfo(){
        if(!$this -> isLogged() || !bx_get('info'))
            return false;

        $sTitle = '';
        $sContent = _t('_Empty');
        switch(bx_get('info')){
            case 'matrix_rebuild':
              $sTitle = _t('_aqb_affiliate_matrix_rebuild');
              $sContent = _t('_aqb_affiliate_matrix_rebuild_info');
              break;
        }

        return echoJson(array(
            'popup' => array(
                'html' => BxTemplFunctions::getInstance()->popupBox('affiliate-info', $sTitle, $sContent),
                'options' => array('closeOnOuterClick' => true))
        ));
    }

    public function checkAllowedSetThumb ($iContentId = 0){
        return CHECK_ACTION_RESULT_ALLOWED;
    }

    public function serviceGetReferralLinkBlock(){
        if(!$this -> isLogged())
            return '';

	    return $this->_oTemplate->getReferralPageLinkBlock($this->_iProfileId);
    }

    public function serviceEntitySocialSharing($mixedContent = false, $aParams = array())
    {
        return $this->_oTemplate->parseHtmlByName('entry-share.html', array(
            'menu' => $this->_oTemplate->getSocialSharingCode($this->_iProfileId)
        ));
    }

    public function serviceGetThumb($iContentId, $sTranscoder = '')
    {
        return false;
    }
    public function checkAllowedEditAnyEntry ($isPerformAction = false)
    {
        return true;
    }
    public function checkAllowedEditAnyEntryForProfile ($isPerformAction = false, $iProfileId = false)
    {
        return true;
    }
    public function actionImportAccounts(){
        if(!isAdmin())
            return '';

        $iProfileId = bx_get('profile_id');
        $bAgree = (bool)bx_get('agree');
        $aFile = isset($_FILES['file']) ? $_FILES['file'] : false;
        if (empty($aFile) || !isset($aFile['name']))
            return echoJson(array(
                'code' => 1,
                'msg' => _t('_aqb_affiliate_import_file_not_found')
            ));

        $sExt = strtolower(pathinfo($aFile['name'], PATHINFO_EXTENSION));
        if ($sExt !== 'csv')
            return echoJson(array(
                'code' => 1,
                'msg' => _t('_aqb_affiliate_import_wrong_extension')
            ));

	    $sTargetFile = BX_DIRECTORY_PATH_TMP . basename($aFile['name']);
        $sTempFile = $aFile['tmp_name'];
        if (!move_uploaded_file($sTempFile, $sTargetFile)){
            return echoJson(array(
                'code' => 1,
                'msg' => _t('_aqb_affiliate_import_file_not_found')
            ));
        }

        $aProfiles = array_map('str_getcsv', file($sTargetFile));
        if (empty($aProfiles))
            return echoJson(array(
                'code' => 1,
                'msg' => _t('_aqb_affiliate_no_profiles_for_import')
            ));

        $iCounter = 0;
        foreach($aProfiles as &$aProfile){
            if (!isset($aProfile[0]))
                continue;

            list($sName, $sPassword, $sEmail) = preg_split("/[,;]+/", $aProfile[0]);

            if (!$sEmail || !filter_var($sEmail, FILTER_VALIDATE_EMAIL))
                continue;

            if ($this->_oDb->getOne("SELECT COUNT(*) FROM `sys_accounts` WHERE `email`=:email", array('email' => $sEmail)))
                continue;

            if ($bAgree && ($iAccountId = $this->_oDb->createAccountAndProfile(array('username' => trim($sName), 'email' => trim($sEmail), 'password' => $sPassword)))){
                if ($this->_oDb->registerReferredAccount($iProfileId, $iAccountId)){
                    $oAccount = BxDolAccount::getInstance ($iAccountId);
                    if ($oAccount && ($aItems = $oAccount->getProfiles()))
                        foreach($aItems as $iKey => $aItem) {
                            $this->registerNewProfile($iKey);
                        }
                }

                $iCounter++;
            } else
              $iCounter++;
        }

        if (!$iCounter)
            return echoJson(array(
                'code' => 1,
                'msg' => _t('_aqb_affiliate_no_profiles_for_import')
            ));

        echoJson(array(
            'code' => 0,
            'msg' => $bAgree ? _t('_aqb_affiliate_profiles_created', $iCounter) : _t('_aqb_affiliate_profiles_to_import', $iCounter)
        ));
    }

    function serviceGetReferralCode($iProfileId = 0, $bUsePageUrl = true){
	    if (!$iProfileId)
           $iProfileId = $this->_iProfileId;

       if (!$iProfileId)
           return false;

	   if ($bUsePageUrl)
	       return $this->_oConfig->getReferralLink($iProfileId, 0, $bUsePageUrl);

	   return [$this->_oConfig->CNF['REFER_PREFIX'] => $this->_oConfig->getAdsHash($iProfileId)];
    }

    /**
     * Programmatically connect an invitee to a referral (sponsor). Used by the
     * workspace-invite flow so an org invite also credits the inviter's affiliate
     * account, mirroring what a normal am_id referral link does at registration.
     *
     * @param  int  $iInviteeProfileId  the profile that was invited/joined
     * @param  int  $iReferralProfileId the inviting (sponsor) profile
     * @return bool true when the referral is now recorded (including a no-op when
     *              it already pointed at this sponsor), false when rejected.
     */
    function serviceSetReferral($iInviteeProfileId, $iReferralProfileId){
        $CNF = &$this->_oConfig->CNF;

        $iInviteeProfileId = (int)$iInviteeProfileId;
        $iReferralProfileId = (int)$iReferralProfileId;

        // must be two distinct, valid profiles
        if(!$iInviteeProfileId || !$iReferralProfileId || $iInviteeProfileId == $iReferralProfileId)
            return false;

        $oInvitee = BxDolProfile::getInstance($iInviteeProfileId);
        $oReferral = BxDolProfile::getInstance($iReferralProfileId);
        if(!$oInvitee || !$oReferral)
            return false;

        if(!in_array($oInvitee->getModule(), $CNF['ALLOWED_PROFILE_TYPES']) || !in_array($oReferral->getModule(), $CNF['ALLOWED_PROFILE_TYPES']))
            return false;

        // already sponsored by this referral - nothing to do (avoids a needless UPDATE)
        $iExisting = $this->_oDb->getSponsorProfileId($iInviteeProfileId);
        if($iExisting == $iReferralProfileId)
            return true;

        // refuse the trivial 2-cycle (referral is itself sponsored by the invitee)
        if($this->_oDb->getSponsorProfileId($iReferralProfileId) == $iInviteeProfileId)
            return false;

        return (bool)$this->_oDb->saveReferral($iInviteeProfileId, $iReferralProfileId);
    }
}

/** @} */
