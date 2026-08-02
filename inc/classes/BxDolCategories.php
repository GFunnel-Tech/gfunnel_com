<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

class BxDolCategories extends BxDolFactory implements iBxDolSingleton
{
    protected $_bIsApi;

    protected $_oDb;

    protected $_sModule;
    protected $_oModule;

    protected $_sBrowseUrl;

    protected function __construct()
    {
        parent::__construct();

        $this->_bIsApi = bx_is_api();

        $this->_oDb = new BxDolCategoriesQuery();

        $this->_sBrowseUrl = bx_append_url_params('searchKeyword.php', [
            'keyword' => '{keyword}',
            'cat' => 'multi',
            'section' => '{section}'
        ], true, ['{keyword}', '{section}']) . '{add}';
    }

    public function __clone()
    {
        if (isset($GLOBALS['bxDolClasses'][get_class($this)]))
            trigger_error('Clone is not allowed for the class: ' . get_class($this), E_USER_ERROR);
    }

    public static function getInstance()
    {
        return self::getInstanceWithTemplate(null);
    }

    public static function getInstanceWithTemplate($oTemplate)
    {
        $sClassName = __CLASS__;
        if($oTemplate)
            $sClassName .= get_class($oTemplate);

        if(!isset($GLOBALS['bxDolClasses'][$sClassName]))
            $GLOBALS['bxDolClasses'][$sClassName] = new BxTemplCategories($oTemplate);

        return $GLOBALS['bxDolClasses'][$sClassName];
    }

    public function setModule($sModule)
    {
        if(!($this->_sModule = $sModule) || !($this->_oModule = BxDolModule::getInstance($sModule))) 
            return;

        $CNF = &$this->_oModule->_oConfig->CNF;

        if(($sKey = 'URL_MULTI_CATEGORY') && !empty($CNF[$sKey]))
            $this->_sBrowseUrl = bx_append_url_params(BxDolPermalinks::getInstance()->permalink($CNF[$sKey]), [
                'category' => '{keyword}'
            ], true, ['{keyword}']);
    }

    public function getData($aParams = array())
    {
        return $this->_oDb->getData($aParams);
    }
    
    public function delete($sModule, $iObject)
    {
        return $this->_oDb->delete($sModule, $iObject);
    }
    
    public function add($sModule, $iProfileId, $sValue, $iObject, $bAutoActivation)
    {
        return $this->_oDb->add($sModule, $iProfileId, $sValue, $iObject, $bAutoActivation);
    }

    public function getUrl($sModule, $sValue, $sAddParams = '')
    {
        $s = BX_DOL_URL_ROOT . bx_replace_markers($this->_sBrowseUrl, [
            'keyword' => rawurlencode($sValue),
            'section' => $sModule,
            'add' => $sAddParams
        ]);

        if($this->_bIsApi)
            return bx_api_get_relative_url($s);

        return $s;
    }
}

/** @} */
