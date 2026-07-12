<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Goal Goal
 * @ingroup     ModzzzModules
 *
 * @{
 */

class MzGoalMenuSnippetMeta extends BxBaseModTextMenuSnippetMeta
{
    protected $_sCategoryUrl;
    
    public function __construct($aObject, $oTemplate = false)
    {
        $this->_sModule = 'mz_goal';

        parent::__construct($aObject, $oTemplate);

        $CNF = &$this->_oModule->_oConfig->CNF; 

        $this->_sCategoryUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink($CNF['URL_CATEGORIES'], array($CNF['GET_PARAM_CATEGORY'] => ''))); //UNA 13 RC
    }

    protected function _getMenuItemCategory($aItem)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;

        if(empty($CNF['FIELD_CATEGORY']) || empty($this->_aContentInfo[$CNF['FIELD_CATEGORY']]))
            return false;
 
        $iCategory = (int)$this->_aContentInfo[$CNF['FIELD_SUBCATEGORY']];
		if($iCategory){
			$this->_sCategoryUrl = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink($CNF['URL_SUBCATEGORIES'], array($CNF['GET_PARAM_SUBCATEGORY'] => '')); 
        }else{
			$iCategory = (int)$this->_aContentInfo[$CNF['FIELD_CATEGORY']];
 
			$this->_sCategoryUrl = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink($CNF['URL_CATEGORIES'], array($CNF['GET_PARAM_CATEGORY'] => ''));
		}
			
		$aCategory = $this->_oModule->_oDb->getCategories(array('type' => 'id', 'id' => $iCategory));  
		if(empty($aCategory) || !is_array($aCategory))
            return false;

        return $this->getUnitMetaItemLink(_t($aCategory['title']), array(
            'href' => $this->_sCategoryUrl . $iCategory
        ));
    } 
  
}

/** @} */
