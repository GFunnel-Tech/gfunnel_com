<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaBaseView UNA Base Representation Classes
 * @{
 */

/**
 * Services for content objects
 */
class BxBaseServiceContent extends BxDol
{
    function serviceCheckLogged()
    {
        $oAccount = BxDolAccount::getInstance();
        $aRet = $oAccount ? $this->getUserIds($oAccount) : null;
        if ($aRet)
            $aRet['session'] = BxDolSession::getInstance()->getId();
        else
            $aRet = ['session' => BxDolSession::getInstance()->getId()];
        return $aRet;
    }

    /**
     * @page service Service Calls
     * @section bx_system_general System Services 
     * @subsection bx_system_general-content-objects Content Objects
     * @subsubsection bx_system_general_cnt-login Login user and get user session
     * 
     * Login user with account id = 12 and get session id back, session id can be used
     * as cookie header to perform other API calls under logged user:
     * @code curl -s --cookie "memberSession=SESSIONIDHERE" -H "Authorization: Bearer APIKEYHERE" "http://example.com/api.php?r=system/login/TemplServiceContent&params[]=12" @endcode
     * 
     * @param $iAccountId account id, or account email
     * @param $bRememberMe remeber session
     * @return array with session and user ids on success or false on error
     * 
     * @see BxBaseServiceContent::serviceLogin
     */
    /** 
     * @ref bx_system_general_cnt-login "Login user and get user's session"
     */
    function serviceLogin($iAccountId, $bRememberMe = false)
    {
        $oAccount = BxDolAccount::getInstance($iAccountId);
        if (!$oAccount)
            return false;

        bx_login($oAccount->id(), $bRememberMe);
        $aRet = $this->getUserIds($oAccount);
        if ($aRet)
            $aRet['session'] = BxDolSession::getInstance()->getId();
        else
            $aRet = ['session' => BxDolSession::getInstance()->getId()];
        return $aRet;
    }

    /**
     * @page service Service Calls
     * @section bx_system_general System Services 
     * @subsection bx_system_general-content-objects Content Objects
     * @subsubsection bx_system_general_cnt-logout Logout current user
     * 
     * @code curl -s --cookie "memberSession=SESSIONIDHERE" -H "Authorization: Bearer APIKEYHERE" "http://example.com/api.php?r=system/logout/TemplServiceContent" @endcode
     * 
     * @return boolean
     * 
     * @see BxBaseServiceContent::serviceLogout
     */
    /** 
     * @ref bx_system_general_cnt-logout "Logout current user"
     */
    function serviceLogout($bAllSessions = false)
    {
        if (!isLogged())
            return false;
        
        if ($bAllSessions) {
            $o = new BxDolSessionQuery();
            $o->deleteByAccountId(getLoggedId());
        }
        else {
            bx_logout();
        }

        return true;
    }

    /**
     * @page service Service Calls
     * @section bx_system_general System Services 
     * @subsection bx_system_general-content-objects Content Objects
     * @subsubsection bx_system_general_cnt-get_user_ids Get user's IDs
     * 
     * Get user's account id, profile id and profile module content id by account id.
     *
     * Get user's IDs by user's account id(12):
     * @code curl -s --cookie "memberSession=SESSIONIDHERE" -H "Authorization: Bearer APIKEYHERE" "http://example.com/api.php?r=system/get_user_ids/TemplServiceContent&params[]=12" @endcode
     * 
     * @param $iAccountId account id, or account email
     * @return array with user ids on success or false on error
     * 
     * @see BxBaseServiceContent::serviceGetUserIds
     */
    /** 
     * @ref bx_system_general_cnt-get_user_ids "Get user's IDs"
     */
    function serviceGetUserIds($iAccountId)
    {
        $oAccount = BxDolAccount::getInstance($iAccountId);
        if (!$oAccount)
            return false;

        return $this->getUserIds($oAccount);
    }

    /**
     * @page service Service Calls
     * @section bx_system_general System Services 
     * @subsection bx_system_general-content-objects Content Objects
     * @subsubsection bx_system_general_cnt-modules_list Get modules list
     * 
     * @code bx_srv('system', 'modules_list', [], 'TemplServiceContent'); @endcode
     * @code {{~system:modules_list:TemplServiceContent~}} @endcode
     *
     * Get modules list with content objects, content objects fields
     * @code curl -s --cookie "memberSession=SESSIONIDHERE" -H "Authorization: Bearer APIKEYHERE" "http://example.com/api.php?r=system/modules_list/TemplServiceContent" @endcode
     * 
     * @param $bContentModulesOnly if true - return only modules with content objects, if false - return all modules
     * @param $bNamesOnly if true - return only module names, if false - return full module info
     * @return content objects info array 
     * 
     * @see BxBaseServiceContent::serviceModulesList
     */
    /** 
     * @ref bx_system_general_cnt-modules_list "Get modules list"
     */
    public function serviceModulesList ($bContentModulesOnly = false, $bNamesOnly = false)
    {
        $aAllowedKeys = ['title', 'type', 'uri', 'db_prefix'];
        $a = BxDolModuleQuery::getInstance()->getModules();
        $aModules = [];
        foreach ($a as $r) {
            if ($r['enabled'] != 1)
                continue;

            $isContentModule = 'system' == $r['name'] ? true : (BxDolContentInfo::getObjectInstance($r['name']) ? true : false);
            if ($bContentModulesOnly && !$isContentModule)
                continue;

            $aModules[$r['name']]['content_module'] = $isContentModule;
    
            foreach ($aAllowedKeys as $sKey) {
                if (isset($r[$sKey])) {
                    $aModules[$r['name']][$sKey] = $r[$sKey];
                }
            }  
        }

        return $bNamesOnly ? array_keys($aModules) : $aModules;
    }

    /**
     * @page service Service Calls
     * @section bx_system_general System Services 
     * @subsection bx_system_general-content-objects Content Objects
     * @subsubsection bx_system_general_cnt-modules_fields Get modules fields
     * 
     * @code bx_srv('system', 'content_modules_fields', [], 'TemplServiceContent'); @endcode
     * @code {{~system:content_modules_fields:TemplServiceContent~}} @endcode
     *
     * Get mcontent odules fields
     * @code curl -s --cookie "memberSession=SESSIONIDHERE" -H "Authorization: Bearer APIKEYHERE" "http://example.com/api.php?r=system/content_modules_fields/TemplServiceContent" @endcode
     * 
     * @param $sModule module name or 'all' for all modules
     * @param $bVerbose if true - return all fields info, if false - return only short list of fields info
     * @return content objects info array 
     * 
     * @see BxBaseServiceContent::serviceContentModulesFields
     */
    /** 
     * @ref bx_system_general_cnt-modules_fields "Get content fields"
     */
    public function serviceContentModulesFields ($sModule = 'all', $bVerbose = false)
    {
        $a = BxDolContentInfo::getSystems();
        $a['system'] = [
            'db_table' => 'sys_accounts',
            'db_table_fields' => BxDolDb::getInstance()->getFields('sys_accounts')['original'],
        ];
        $a['system']['db_table_fields'][] = 'auto_send_confrmation_email';

        if ($sModule != 'all') {
            if (isset($a[$sModule])) {
                $a = [$sModule => $a[$sModule]];
            }
            else {
                return ['code' => 404, 'error' => _t('_sys_txt_not_found')];
            }
        }

        foreach ($a as $sSystem => $r) {
            if ($sSystem == 'system') {
                $aModules[$sSystem] = $r;
                continue;
            }
            if ($r['alert_action_add'] == 'commentPost') {
                continue;
            }
            $oModule = BxDolModule::getInstance($sSystem);
            if (!$oModule || !$oModule->isEnabled())
                continue;
            $oFormsHelper = $oModule->getFormsHelper();
            $sTableEntries = $oModule->_oConfig->CNF['TABLE_ENTRIES'];
            $aFields = BxDolDb::getInstance()->getFields($sTableEntries);
            $aModules[$sSystem] = [
                'db_table' => $sTableEntries,
                'db_table_fields' => $aFields['original'],
                'fields_add' => $this->_prepareShortFields($oFormsHelper->getObjectFormAdd()->aInputs, $bVerbose),
                'fields_edit' => $this->_prepareShortFields($oFormsHelper->getObjectFormEdit()->aInputs, $bVerbose),
            ];
        }
        return $aModules;
    }

    protected function _prepareShortFields($aFields, $bVerbose)
    {        
        if ($bVerbose)
            return $aFields;
        $aShortList = ['type', 'name', 'caption', 'info', 'required', 'value', 'values'];
        $aResult = [];
        foreach ($aFields as $sKey => $aField) {
            if ($aField['type'] == 'submit')
                continue;
            $aResult[$sKey] = [];
            foreach ($aShortList as $sShortKey) {
                if (isset($aField[$sShortKey]) && !empty($aField[$sShortKey])) {
                    $aResult[$sKey][$sShortKey] = $aField[$sShortKey];
                }
            }
        }
        return $aResult;
    }

    /**
     * @page service Service Calls
     * @section bx_system_general System Services 
     * @subsection bx_system_general-content-objects Content Objects
     * @subsubsection bx_system_general_cnt-get_info Get content info
     * 
     * @code bx_srv('system', 'get_info', ["bx_posts", 123], 'TemplServiceContent'); @endcode
     * @code {{~system:get_info:TemplServiceContent["bx_posts", 123]~}} @endcode
     *
     * Get post info by post id(123):
     * @code curl -s --cookie "memberSession=SESSIONIDHERE" -H "Authorization: Bearer APIKEYHERE" "http://example.com/api.php?r=system/get_info/TemplServiceContent&params[]=bx_posts&params[]=123" @endcode
     * Get account info by account id(12):
     * @code curl -s --cookie "memberSession=SESSIONIDHERE" -H "Authorization: Bearer APIKEYHERE" "http://example.com/api.php?r=system/get_info/TemplServiceContent&params[]=sys_account&params[]=12" @endcode
     * 
     * @param $sContentObject content object name
     * @param $iContentId content id
     * @param $bRawInfo if true - raw info return data directly from DB, if false - returns strutured array
     * @return content info array on success or empty array on error
     * 
     * @see BxBaseServiceContent::serviceGetInfo
     */
    /** 
     * @ref bx_system_general_cnt-get_info "Get content info"
     */
    public function serviceGetInfo ($sContentObject, $iContentId, $bRawInfo = false)
    {
        if ('sys_account' == $sContentObject || 'system' == $sContentObject) {
            $o = BxDolAccount::getInstance($iContentId);
            if (!$o)
                return false;
            return $o->getInfo();
        }

        $o = BxDolContentInfo::getObjectInstance($sContentObject);
        if (!$o)
            return false;

        if ($bRawInfo)
            return $o->getContentInfo($iContentId, false);
        else
            return $o->getContentInfoAPI($iContentId, false);
    }

    /**
     * @page service Service Calls
     * @section bx_system_general System Services 
     * @subsection bx_system_general-content-objects Content Objects
     * @subsubsection bx_system_general_cnt-search Search content
     * 
     * @code bx_srv('system', 'search', ["test"], 'TemplServiceContent'); @endcode
     * @code {{~system:search:TemplServiceContent["test"]~}} @endcode
     *
     * Search for posts containing the keyword "test":
     * @code curl -s --cookie "memberSession=SESSIONIDHERE" -H "Authorization: Bearer APIKEYHERE" "http://example.com/api.php?r=system/search/TemplServiceContent&params[]=test" @endcode
     * 
     * @param $sKeyword keyword to search for
     * @param $mixedSections content sections to search in, for example ['bx_posts', 'bx_persons'], if empty - search in all sections
     * @param $per_page number of results per page
     * @param $start start showing from result number
     * @return content info array on success or empty array on error or not found
     * 
     * @see BxBaseServiceContent::serviceSearch
     */
    /** 
     * @ref bx_system_general_cnt-search "Search content"
     */
    public function serviceSearch (string $sKeyword, array|string $mixedSections = '', ?int $per_page = 5, ?int $start = 0): array
    {
        $sClass = bx_get_search_class_name();
        $o = new $sClass($mixedSections);

        $o->setApiOutput(true);
        $o->setLiveSearch(true);
        $o->setDataProcessing(true);
        $o->setCustomSearchCondition(['keyword' => $sKeyword]);
        $o->setCustomCurrentCondition([
            'paginate' => [
                'forceStart' => $start,
                'perPage' => $per_page,
            ]
        ]);

        return $o->response();
    }

    /**
     * @page service Service Calls
     * @section bx_system_general System Services 
     * @subsection bx_system_general-content-objects Content Objects
     * @subsubsection bx_system_general_cnt-get_link Get content link
     * 
     * @code bx_srv('system', 'get_link', ["bx_posts", 123], 'TemplServiceContent'); @endcode
     * @code {{~system:get_link:TemplServiceContent["bx_posts", 123]~}} @endcode
     *
     * Get post link by post id(123):
     * @code curl -s --cookie "memberSession=SESSIONIDHERE" -H "Authorization: Bearer APIKEYHERE" "http://example.com/api.php?r=system/get_link/TemplServiceContent&params[]=bx_posts&params[]=123" @endcode
     * 
     * @param $sContentObject content object name
     * @param $iContentId content id
     * @return string with link on success or empty string on error
     * 
     * @see BxBaseServiceContent::serviceGetLink
     */
    /** 
     * @ref bx_system_general_cnt-get_link "Get content link"
     */
    public function serviceGetLink ($sContentObject, $iContentId)
    {
        if ('sys_account' == $sContentObject) {
            return false;
        }

        $o = BxDolContentInfo::getObjectInstance($sContentObject);
        if (!$o)
            return false;

        return $o->getContentLink($iContentId);
    }

    /**
     * @page service Service Calls
     * @section bx_system_general System Services 
     * @subsection bx_system_general-content-objects Content Objects
     * @subsubsection bx_system_general_cnt-delete Delete content
     * 
     * @code bx_srv('system', 'delete', ["bx_posts", 123], 'TemplServiceContent'); @endcode
     *
     * Delete post by post id(123):
     * @code curl -s --cookie "memberSession=SESSIONIDHERE" -H "Authorization: Bearer APIKEYHERE" "http://example.com/api.php?r=system/delete/TemplServiceContent&params[]=bx_posts&params[]=123" @endcode
     * Delete account with all its content by account id(12):
     * @code curl -s --cookie "memberSession=SESSIONIDHERE" -H "Authorization: Bearer APIKEYHERE" "http://example.com/api.php?r=system/delete/TemplServiceContent&params[]=sys_account&params[]=12" @endcode
     * 
     * @param $sContentObject content object name
     * @param $iContentId content id
     * @param $aParams array of params:   
     *          - `with_content` - true(default) | false: for `sys_account`, `bx_persons`, `bx_organizations`;
     *          - `force` - true | false(default): for `bx_persons`, `bx_organizations`;
     *          - `scheduled` - true | false(default): for `sys_account`;
     * @return array with code = 0 on success, or array with code != 0 and error message
     * 
     * @see BxBaseServiceContent::serviceDelete
     */
    /** 
     * @ref bx_system_general_cnt-delete "Delete content"
     */
    public function serviceDelete ($sContentObject, $iContentId, $aParams = [])
    {
        if ('sys_account' == $sContentObject) {
            $o = BxDolAccount::getInstance($iContentId);
            if (!$o)
                return ['code' => 404, 'error' => _t('_sys_txt_not_found')];
            return $o->delete(isset($aParams['with_content']) ? $aParams['with_content'] : true, isset($aParams['scheduled']) ? $aParams['scheduled'] : false);
        }
        if (in_array($sContentObject, ['bx_persons', 'bx_organizations'])) {
            $o = BxDolProfile::getInstanceByContentAndType($iContentId, $sContentObject);
            if (!$o)
                return ['code' => 404, 'error' => _t('_sys_txt_not_found')];
            return $o->delete(false, isset($aParams['with_content']) ? $aParams['with_content'] : true, isset($aParams['force']) ? $aParams['force'] : false);
        }

        $o = BxDolContentInfo::getObjectInstance($sContentObject);
        if (!$o)
            return ['code' => 404, 'error' => _t('_sys_txt_not_found')];
        if ($sErrorMsg = $o->deleteContent($iContentId))
            return ['code' => 500, 'error' => $sErrorMsg];
        else
            return ['code' => 0];
    }

    /**
     * @page service Service Calls
     * @section bx_system_general System Services 
     * @subsection bx_system_general-content-objects Content Objects
     * @subsubsection bx_system_general_cnt-update Update content
     * 
     * @code bx_srv('system', 'update', ["bx_posts", 123, ["title" => "new title"]], 'TemplServiceContent'); @endcode
     *
     * Update post text by post id(123):
     * @code curl -s --cookie "memberSession=SESSIONIDHERE" -H "Authorization: Bearer APIKEYHERE" "http://example.com/api.php?r=system/update/TemplServiceContent&params=%5B%22bx_posts%22%2C123%2C%7B%22text%22%3A%22new%20text%22%7D%5D" @endcode
     * Update account email by account id(4):
     * @code curl -s --cookie "memberSession=SESSIONIDHERE" -H "Authorization: Bearer APIKEYHERE" "http://example.com/api.php?r=system/update/TemplServiceContent&params=%5B%22sys_account%22%2C4%2C%7B%22email%22%3A%22new%40email.com%22%7D%5D" @endcode
     * To replace existing picture (for example profile avatar) delete previous picture first, 
     * then set new one.
     * 
     * @param $sContentObject content object name
     * @param $iContentId content id
     * @param $aValues key value pairs to update
     * @param $sDisplay form display name to use
     * @param $iProfileId profile ID to perform action on behalf of
     * @return array with code = 0 on success, or array with code != 0 and error message
     * 
     * @see BxBaseServiceContent::serviceUpdate
     */
    /** 
     * @ref bx_system_general_cnt-update "Update content"
     */
    public function serviceUpdate ($sContentObject, $iContentId, $aValues, $sDisplay = false, $iProfileId = 0)
    {
        if ($iProfileId) {
            $GLOBALS['glForceCurrentProfileId'] = $iProfileId;
        }
        $a = null;
        if ('sys_account' == $sContentObject) {
            $o = BxDolAccount::getInstance($iContentId);
            if (!$o) {
                $a = ['code' => 404, 'error' => _t('_sys_txt_not_found')];
            }
            else {
                $oQuery = BxDolAccountQuery::getInstance();
                foreach ($aValues as $k => $v) {
                    if (!$oQuery->isFieldExists('sys_accounts', $k)) {
                        $a = ['code' => 500, 'error' => _t('_sys_txt_forms_unknown_field_err', $k)];
                        break;
                    }
                }
                if (!$a) {
                    foreach ($aValues as $k => $v) {
                        if (!$oQuery->_updateField($o->id(), $k, $v)) {
                            $a = ['code' => 500, 'error' => _t('_error occured')];
                            break;
                        }
                    }
                }
                if (!$a) {
                    $a = ['code' => 0]; // success
                }
            }
        }
        else {
            $o = BxDolContentInfo::getObjectInstance($sContentObject);
            if (!$o) {
                $a = ['code' => 404, 'error' => _t('_sys_txt_not_found')];
            }
            else {
                if ($sErrorMsg = $o->updateContent($iContentId, $aValues, $sDisplay))
                    $a = ['code' => 500, 'error' => $sErrorMsg];
                else
                    $a = ['code' => 0]; // success
            }
        }

        if ($iProfileId) {
            $GLOBALS['glForceCurrentProfileId'] = 0;
        }

        return $a;
    }

    /**
     * @page service Service Calls
     * @section bx_system_general System Services 
     * @subsection bx_system_general-content-objects Content Objects
     * @subsubsection bx_system_general_cnt-add Add content
     * 
     * @code bx_srv('system', 'add', [$sContentObject, $aValues], 'TemplServiceContent'); @endcode
     *
     * Add new post with specified title, text and privacy, post authour is identified by memberSession cookie:
     * @code curl -s --cookie "memberSession=SESSIONIDHERE" -H "Authorization: Bearer APIKEYHERE" "http://example.com/api.php?r=system/add/TemplServiceContent&params=%5B%22bx_posts%22%2C%7B%22title%22%3A%22Post%20title%22%2C%22text%22%3A%22Some%20text%22%2C%22cat%22%3A2%2C%22allow_view_to%22%3A3%7D%5D" @endcode
     * Add new account with specified name, email and password, also mark email as confirmed:
     * @code curl -s --cookie "memberSession=SESSIONIDHERE" -H "Authorization: Bearer APIKEYHERE" "http://example.com/api.php?r=system/add/TemplServiceContent&params=%5B%22sys_account%22%2C%20%7B%22name%22%3A%22Vasya%22%2C%22email%22%3A%22vasya%40vasya.com%22%2C%22email_confirmed%22%3A%221%22%2C%22password%22%3A%221234%22%7D%5D" @endcode
     * 
     * @param $sContentObject content object name
     * @param $aValues key value pairs to add
     * @param $iProfileId profile ID to perform action on behalf of
     * @return array with code = 0 on success, or array with code != 0 and error message
     * 
     * @see BxBaseServiceContent::serviceAdd
     */
    /** 
     * @ref bx_system_general_cnt-add "Add content"
     */
    public function serviceAdd ($sContentObject, $aValues, $iProfileId = 0)
    {
        if ($iProfileId) {
            $GLOBALS['glForceCurrentProfileId'] = $iProfileId;
        }
                
        $a = [];
        if (bx_get_logged_profile_id() == 0) {
            $a = ['code' => 403, 'error' => 'Permission denied. Please login first.'];
        }
        elseif ('sys_account' == $sContentObject || 'system' == $sContentObject) {
            $o = new BxTemplAccountForms();
            $a = $o->createAccount ($aValues, BX_PROFILE_ACTION_EXTERNAL, false);
            if (isset($a['account_id']) && isset($aValues['email_confirmed'])) {
                $o = BxDolAccount::getInstance($a['account_id']);
                if ($o) {
                    $o->updateEmailConfirmed($aValues['email_confirmed'], isset($aValues['auto_send_confrmation_email']) ? $aValues['auto_send_confrmation_email'] : false);
                }
            }
            if (isset($a['account_id']) && isset($aValues['phone_confirmed'])) {
                $o = BxDolAccount::getInstance($a['account_id']);
                if ($o) {
                    $o->updatePhoneConfirmed($aValues['phone_confirmed']);
                }
            }
        }
        else {
            $o = BxDolContentInfo::getObjectInstance($sContentObject);
            if (!$o) {
                $a = ['code' => 404, 'error' => 'Content object(module) not found'];
            }
            else {
                $a = $o->addContent($aValues);
                if (!(isset($a['code']) && !$a['code'] && isset($a['content']))) {
                    $a['code'] = 500 + $a['code'];
                    $a['error'] = $a['message'];
                    $a['data'] = $a['errors'];
                }
            }
        }

        if ($iProfileId) {
            $GLOBALS['glForceCurrentProfileId'] = 0;
        }

        return $a;
    }

    /**
     * @page service Service Calls
     * @section bx_system_general System Services 
     * @subsection bx_system_general-content-objects Content Objects
     * @subsubsection bx_system_general_cnt-upload_from_url Upload file from URL
     * 
     * Upload file and associate with content:
     * @code bx_srv('system', 'upload_from_url', [$sContentObject, $sFileUrl, ['content_id' => 123]], 'TemplServiceContent'); @endcode
     *
     * Upload photo from `http://example.com/a.jpg` URL to `bx_persons_pictures` storage and associate
     * uploaded file to a person with content id (123), returned value is newly uploaded file id, 
     * it can be used in content update API/Service call to set profile picture.
     * @code curl -s --cookie "memberSession=SESSIONIDHERE" -H "Authorization: Bearer APIKEYHERE" "http://example.com/api.php?r=system/upload_from_url/TemplServiceContent&params=%5B%22bx_persons_pictures%22%2C%22http%3A%2F%2Fexample.com%2Fa.jpg%22%2C%20%7B%22content_id%22%3A123%7D%5D" @endcode
     * 
     * @param $sStorageObject storage object name
     * @param $sFileUrl URL to file to store in the storage
     * @param $aParams array of params, possible array keys:
     *          - `private` - true | false: set file as private or not, if omitted file is uploaded as public
     *          - `profile_id` - int: set owner of file to this user, of omitted, then currently logged in user is becoming file owner
     *          - `content_id` - int: associate file with this content
     * @param $iProfileId profile id to perform action on behalf of
     * @return uploaded file Id on success, or array with code != 0 and error message
     * 
     * @see BxBaseServiceContent::serviceUploadFromUrl
     */
    /** 
     * @ref bx_system_general_cnt-upload_from_url "Upload file from URL"
     */
    public function serviceUploadFromUrl ($sStorageObject, $sFileUrl, $aParams = [], $iProfileId = 0)
    {
        if ($iProfileId) {
            $GLOBALS['glForceCurrentProfileId'] = $iProfileId;
        }

        $oStorage = BxDolStorage::getObjectInstance($sStorageObject);
        if (!$oStorage) {
            $mixedRet = ['code' => 404, 'error' => _t('_sys_txt_not_found')];
        }
        else {
            $iFileId = $oStorage->storeFileFromUrl($sFileUrl, isset($aParams['private']) && $aParams['private'] ? true : false, isset($aParams['profile_id']) ? $aParams['profile_id'] : 0, isset($aParams['content_id']) ? $aParams['content_id'] : 0);

            $mixedRet = $iFileId ? $iFileId : ['code' => $oStorage->getErrorCode(), 'error' => $oStorage->getErrorString()];
        }

        if ($iProfileId) {
            $GLOBALS['glForceCurrentProfileId'] = 0;
        }

        return $mixedRet;
    }

    /**
     * @page service Service Calls
     * @section bx_system_general System Services 
     * @subsection bx_system_general-content-objects Content Objects
     * @subsubsection bx_system_general_cnt-delete_file Delete file
     * 
     * @code bx_srv('system', 'delete_file', ["bx_persons_pictures", 123], 'TemplServiceContent'); @endcode
     *
     * Delete file with id = 123 from `bx_persons_pictures` storage engine. 
     * <b>Please note</b> that associated with this file id content need to be updated by setting 
     * respective field to new file id or 0.
     * @code curl -s --cookie "memberSession=SESSIONIDHERE" -H "Authorization: Bearer APIKEYHERE" "http://example.com/api.php?r=system/delete_file/TemplServiceContent&params[]=bx_persons_pictures&params[]=123" @endcode
     * 
     * @param $sStorageObject storage object name
     * @param $iFileId file id
     * @return true on success, or array with code != 0 and error message
     * 
     * @see BxBaseServiceContent::serviceDeleteFile
     */
    /** 
     * @ref bx_system_general_cnt-delete_file "Delete file"
     */
    public function serviceDeleteFile ($sStorageObject, $iFileId)
    {
        $oStorage = BxDolStorage::getObjectInstance($sStorageObject);
        if (!$oStorage)
            return ['code' => 404, 'error' => _t('_sys_txt_not_found')];

        $b = $oStorage->deleteFile($iFileId);

        return $b ? $b : ['code' => $oStorage->getErrorCode(), 'error' => $oStorage->getErrorString()];
    }

    /**
     * @page service Service Calls
     * @section bx_system_general System Services 
     * @subsection bx_system_general-content-objects Content Objects
     * @subsubsection bx_system_general_cnt-replace_file Replace file
     * 
     * @code bx_srv('system', 'replace_file', ["bx_persons", 123, 'picture', 'http://a.me/i.png'], 'TemplServiceContent'); @endcode
     *
     * @code curl -s --cookie "memberSession=SESSIONIDHERE" -H "Authorization: Bearer APIKEYHERE" "http://example.com/api.php?r=system/replace_file/TemplServiceContent&params[]=bx_persons&params[]=123&params[]=picture&params[]=http://a.me/i.png" @endcode
     * 
     * @param $sContentObject content object name
     * @param $iContentId content id
     * @param $sField field name
     * @param $sFileUrl file URL
     * @return true on success, or array with code != 0 and error message
     * 
     * @see BxBaseServiceContent::serviceReplaceFile
     */
    /** 
     * @ref bx_system_general_cnt-replace_file "Replace file"
     */
    public function serviceReplaceFile ($sContentObject, $iContentId, $sField, $sFileUrl, $sDisplay = false)
    {
        // some checks
        if (!isLogged())
            return ['code' => 500, 'error' => _t('_sys_txt_not_found')];

        $a = $this->serviceGetInfo ($sContentObject, $iContentId, true);
        if (!$a)
            return ['code' => 404, 'error' => _t('_sys_txt_not_found')];

        $aParams = [];
        if ($sDisplay !== false)
            $aParams['display'] = $sDisplay;
        $oForm = bx_srv($sContentObject, 'get_object_form', ['edit', $aParams]);
        if (!$oForm)
            return ['code' => 404, 'error' => _t('_sys_request_module_not_found_cpt')];

        if (!isset($oForm->aInputs[$sField]))
            return ['code' => 404, 'error' => 'Unknown field'];

        if (!isset($oForm->aInputs[$sField]['storage_object']))
            return ['code' => 404, 'error' =>  "'$sField' field isn't file field (storage object isn't found in the specified field)"];

        // delete previous file
        $sStorageObject = $oForm->aInputs[$sField]['storage_object'];
        if ($a[$sField]) {
            $a = $this->serviceDeleteFile($sStorageObject, $a[$sField]);
            if (is_array($a) && isset($a['code']) && $a['code'])
                return $a;
        }

        // upload new file
        $iFileId = $a = $this->serviceUploadFromUrl($sStorageObject, $sFileUrl, ['content_id' => $iContentId, 'profile_id' => bx_get_logged_profile_id()]);
        if (is_array($a) && isset($a['code']) && $a['code'])
            return $a;

        // assign uploaded file id to the field
        return $this->serviceUpdate($sContentObject, $iContentId, [$sField => $iFileId], $sDisplay);
    }

    protected function getUserIds($oAccount)
    {
        $oProfile = BxDolProfile::getInstanceByAccount($oAccount->id());
        if (!$oProfile)
            return false;
        return [
            'account_id' => $oProfile->getAccountId(),
            'profile_id' => $oProfile->id(),
            'content_id' => $oProfile->getContentId(),
            'content_module' => $oProfile->getModule(),
        ];
    }
}

/** @} */
