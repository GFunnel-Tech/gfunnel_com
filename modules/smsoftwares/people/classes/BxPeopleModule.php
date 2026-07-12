<?php defined('BX_DOL') or die('hack attempt');

bx_import('BxDolAcl');

class BxPeopleModule extends BxDolModule
{
    /**
     * Explicitly declare properties to avoid deprecation warnings
     */
    protected string $_sPrivacyObjectView;
    protected string $_sPrivacyGroupDefault;

    /**
     * Constructor
     */
    function __construct(&$aModule)
    {
        parent::__construct($aModule);
        // $this->_oConfig->init($this->_oDb);
        $this->_sPrivacyObjectView = 'sys_form_inputs_allow_view_to';
        $this->_sPrivacyGroupDefault = BX_DOL_PG_ALL;
    }

    /**
     * SERVICE METHODS
     */
    /*
     * COMMON METHODS
     */
    public function getUserId()
    {
        return isLogged() ? bx_get_logged_profile_id() : 0;
    }

    public function serviceAddApps()
    {
        bx_import('BxDolForm');
        $oForm = BxDolForm::getObjectInstance($this->_oConfig->getObject('form_app'), $this->_oConfig->getObject('form_display_app'), $this->_oTemplate);
        if (!$oForm)
            return MsgBox(_t('_sys_txt_error_occured'));
        
        $oForm->aFormAttrs['action'] = !empty($aParams['action']) ? $aParams['action'] : bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i=add-app'));
        $oForm->initChecker();
        
        if ($oForm->isSubmittedAndValid()) {
            $oDb = BxDolDb::getInstance();
            $oStorage = BxDolStorage::getObjectInstance(BX_DOL_STORAGE_OBJ_IMAGES);
            $sIcon = $oStorage->storeFileFromForm($_FILES['icon'], false, 0);
            $aMenuItem = array(
                'set_name' => 'sys_homepage',
                'module' => 'bx_people',
                'name' => $_POST['application_name'],
                'title_system' => $_POST['application_name'],
                'title' => $_POST['application_name'],
                'category' => $_POST['category'],
                'affiliate_url' => $_POST['affiliate_url'],
                'description' => $_POST['description'],
                'link' => $_POST['login_url'],
                'allow_view_to' => $_POST['allow_view_to'],
                'author' => $this->getUserId(),
                'icon' => $sIcon,
                
            );
            $sQuery = $oDb->prepare("SELECT `order` FROM `sys_menu_items` WHERE `set_name` = ? AND `active` = 1 AND `order` != ? ORDER BY `order` DESC LIMIT 1", 'set_homepage', 9999);
            $iProfileMenuOrder = (int)$oDb->getOne($sQuery);
            $aMenuItem['order'] = $iProfileMenuOrder + 1;
            $inserted = $oDb->query("INSERT INTO `sys_menu_items` SET " . $oDb->arrayToSQL($aMenuItem));
            
            if ($inserted) {
                header('Location: ' . BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=applications'));
                exit;
                $code = MsgBox(_t('_sys_txt_success'));
            } else {
                $code = MsgBox(_t('_sys_txt_error_occured'));
            }
        } else {
            $code = $oForm->getCode(); // display form
        }
        return $code;
    }

    function getPageJsObject()
    {
        return '';
    }

    public function serviceViewApps()
    {
        $oGrid = BxDolGrid::getObjectInstance('people_app_mod');
        if (!$oGrid)
            return MsgBox(_t('Empty'));
        return BxDolStudioTemplate::getInstance()->parseHtmlByName('storages.html', array(
            'js_object' => $this->getPageJsObject(),
            'content' => $oGrid->getCode()
        ));
    }

    public function serviceViewAppsComm()
    {
        $oGrid = BxDolGrid::getObjectInstance('people_app_mod_comm');
        if (!$oGrid)
            return MsgBox(_t('Empty'));
        return BxDolStudioTemplate::getInstance()->parseHtmlByName('storages.html', array(
            'js_object' => $this->getPageJsObject(),
            'content' => $oGrid->getCode()
        ));
    }

    public function serviceEditApps()
    {
        $getid = explode('/', $_GET['_q']);
        $id = $getid['1'];
        $apps = $this->_oDb->getAppById($id);
        bx_import('BxDolForm');
        $oForm = BxDolForm::getObjectInstance($this->_oConfig->getObject('form_app'), $this->_oConfig->getObject('form_display_app_edit'), $this->_oTemplate);
        if (!$oForm)
            return MsgBox(_t('_sys_txt_error_occured'));
        if (!$apps)
            return MsgBox(_t('_sys_txt_error_occured'));
        
        $oForm->aInputs['application_name']['value'] = $apps['name'];
        $oForm->aInputs['login_url']['value'] = $apps['link'];
        $oForm->aInputs['category']['value'] = $apps['category'];
        $oForm->aInputs['description']['value'] = $apps['description'];
        $oForm->aInputs['affiliate_url']['value'] = $apps['affiliate_url'];
        $oForm->aInputs['allow_view_to']['value'] = $apps['allow_view_to'];
        $oForm->initChecker();
        
        if ($oForm->isSubmittedAndValid()) {
            $oDb = BxDolDb::getInstance();
            $oStorage = BxDolStorage::getObjectInstance(BX_DOL_STORAGE_OBJ_IMAGES);
            $sIcon = $oStorage->storeFileFromForm($_FILES['icon'], false, 0);
            $aMenuItem = array(
                'name' => $_POST['application_name'],
                'title_system' => $_POST['application_name'],
                'title' => $_POST['application_name'],
                'category' => $_POST['category'],
                'affiliate_url' => $_POST['affiliate_url'],
                'description' => $_POST['description'],
                'link' => $_POST['login_url'],
                'allow_view_to' => $_POST['allow_view_to'],
                'icon' => $sIcon,
                'target' => '_blank',
            );
            $aBindingsWhere = array(
                'id' => $apps['id'],
            );
            $updated = $oDb->query("UPDATE `sys_menu_items` SET " . $oDb->arrayToSQL($aMenuItem) . " WHERE " . $oDb->arrayToSQL($aBindingsWhere));
            
            if ($updated) {
                header('Location: ' . BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=manage-app'));
                exit;
                $code = MsgBox(_t('_sys_txt_updated'));
            } else {
                $code = MsgBox(_t('_sys_txt_error_occured'));
            }
        } else {
            $code = $oForm->getCode(); // display form
        }
        return $code;
    }
}
/** @} */