<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    News News
 * @ingroup     ModzzzModules
 *
 * @{
 */

class MzNewsSearchResult extends BxBaseModTextSearchResult
{
	protected $sArchivePeriod;
 
    function __construct($sMode = '', $aParams = array())
    {
        parent::__construct($sMode, $aParams);

		$this->sArchivePeriod = '';

        $this->aCurrent = array(
            'name' => 'mz_news',
            'module_name' => 'mz_news',
            'object_metatags' => 'mz_news',
            'title' => _t('_mz_news_page_title_browse'),
            'table' => 'mz_news_news',
            'ownFields' => array('id', 'title', 'text', 'source', 'thumb', 'thumb_data', 'author', 'added'), //UNA 13 RC
            'searchFields' => array(),
            'restriction' => array(
                'language' => array('value' => '', 'field' => 'language', 'operator' => 'in'),
                'location' => array('value' => '', 'field' => 'location', 'operator' => '='),
                'category' => array('value' => '', 'field' => 'cat', 'operator' => '='),
                'breaking' => array('value' => '', 'field' => 'breaking', 'operator' => '='), 
                'author' => array('value' => '', 'field' => 'author', 'operator' => '='),
                'role' => array('value' => '', 'field' => 'role', 'operator' => '=', 'table' => 'sys_accounts'), 
        		'featured' => array('value' => '', 'field' => 'featured', 'operator' => '<>'),
        		'status' => array('value' => 'active', 'field' => 'status', 'operator' => '='),
        		'statusAdmin' => array('value' => 'active', 'field' => 'status_admin', 'operator' => '='),
            ),
/*
            'join' => array ( 
                'profile' => array(
                    'type' => 'INNER',
                    'table' => 'sys_profiles',
                    'mainField' => 'author',
                    'onField' => 'id',
                    'joinFields' => array(),
                ),
                'account' => array(
                    'type' => 'INNER',
                    'table' => 'sys_accounts',
                    'mainTable' => 'sys_profiles',
                    'mainField' => 'account_id',
                    'onField' => 'id',
                    'joinFields' => array('role'),
                ),
            ),
*/ 
            'paginate' => array('perPage' => getParam('mz_news_per_page_browse'), 'start' => 0),
            'sorting' => 'last',
            'rss' => array(
                'title' => '',
                'link' => '',
                'image' => '',
                'profile' => 0,
                'fields' => array (
                    'Guid' => 'link',
                    'Link' => 'link',
                    'Title' => 'title',
                    'DateTimeUTS' => 'added',
                    'Desc' => 'text',
                    'Image' => 'thumb'
                ),
            ),
            'ident' => 'id',
        );

        $this->sFilterName = 'mz_news_filter';
        $this->oModule = $this->getMain();

        $CNF = &$this->oModule->_oConfig->CNF;

        $sSearchFields = getParam($CNF['PARAM_SEARCHABLE_FIELDS']);
        $this->aCurrent['searchFields'] = !empty($sSearchFields) ? explode(',', $sSearchFields) : '';

        $oProfileAuthor = null;
  
		if (isset($CNF['FIELD_LANGUAGE']) && $this->oModule->_oConfig->isMultiLanguage()){
			$this->aCurrent['restriction']['language']['value'] = array('', 'all', bx_lang_name());
		}else{
			unset($this->aCurrent['restriction']['language']);
		}
 
        switch ($sMode) {
            case 'author':
                if(!$this->_updateCurrentForAuthor($sMode, $aParams, $oProfileAuthor))
                    $this->isError = true;
                break;

            case 'context':
                if(!$this->_updateCurrentForContext($sMode, $aParams, $oProfileAuthor))
                    $this->isError = true;
                break;
                
            case 'favorite':
                if(!$this->_updateCurrentForFavorite($sMode, $aParams, $oProfileAuthor))
                    $this->isError = true;
                break;

			case 'recent':
			case 'public':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_HOME']);
                $this->aCurrent['title'] = _t('_mz_news_page_title_browse_recent');
                $this->aCurrent['rss']['link'] = 'modules/?r=news/rss/' . $sMode;
                break;

            case 'featured':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_HOME']);
                $this->aCurrent['title'] = _t('_mz_news_page_title_browse_featured');
                $this->aCurrent['restriction']['featured']['value'] = '0';
                $this->aCurrent['rss']['link'] = 'modules/?r=news/rss/' . $sMode;
                $this->aCurrent['sorting'] = 'featured';
                break;
 /*
            case 'category':  
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_HOME']);
                $this->aCurrent['title'] = _t('_mz_news_page_title_browse_featured');
                $this->aCurrent['restriction']['featured']['value'] = '0';
                $this->aCurrent['rss']['link'] = 'modules/?r=news/rss/' . $sMode;
                $this->aCurrent['sorting'] = 'featured';
               break;
*/

            case 'category': 
				
			    $iCategoryId = bx_process_input(bx_get('category'), BX_DATA_INT);
 
				$oCategory = BxDolCategory::getObjectInstance($CNF['OBJECT_CATEGORY']);
				$sCategoryTitle = $oCategory->getCategoryTitle($iCategoryId);
				 
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_CATEGORY'] . $iCategoryId);
                $this->aCurrent['title'] = _t('_mz_news_page_title_browse_category', $sCategoryTitle);
                $this->aCurrent['restriction']['category']['value'] = $iCategoryId;
                $this->aCurrent['rss']['link'] = 'modules/?r=news/rss/' . $sMode;
                break;

            case 'archive': 
				
			    $sPeriod = bx_process_input(bx_get('period'), BX_DATA_TEXT);
  
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_ARCHIVE'] . $sPeriod);
                $this->aCurrent['title'] = _t('_mz_news_page_title_browse_archive', $sPeriod);
                $this->sArchivePeriod = $sPeriod;
                $this->aCurrent['rss']['link'] = 'modules/?r=news/rss/' . $sMode;
                break;

            case 'local':  

				//$aMetaTypes = array('location_country', 'location_country_city', 'location_country_state', 'mention', 'keyword');

				$sMetaType = '';

				$iProfileId = bx_get_logged_profile_id (); 
				if($iProfileId){
					$oProfile = BxDolProfile::getInstance($iProfileId);
					$aInfo = $oProfile->getInfo($iProfileId);
			 
					if(isset($aInfo['type']) && $aInfo['type'] != 'system')
					{
						$oMeta = BxDolMetatags::getObjectInstance($aInfo['type']);
				 
						$aProfileLocation = $oMeta->locationGet($aInfo['content_id']);
	 
						if(isset($aProfileLocation['city']) && $aProfileLocation['city'])
							 $sMetaType = 'location_country_city';
						elseif(isset($aProfileLocation['state']) && $aProfileLocation['state'])
							 $sMetaType = 'location_country_state';
						elseif(isset($aProfileLocation['country']) && $aProfileLocation['country'])
							 $sMetaType = 'location_country'; 
					}
				}

				if($sMetaType){  
					$this->setMetaType('location_country_city');
	 
					$this->setCustomSearchCondition( $arr = array('keyword'=>$aProfileLocation['country'], 'city'=>$aProfileLocation['city']) );
	  
					$this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_LOCAL']);
					$this->aCurrent['title'] = _t('_mz_news_page_title_browse_local'); 
					$this->aCurrent['rss']['link'] = 'modules/?r=news/rss/' . $sMode;
				}else{ 
					$this->aCurrent['restriction']['location']['value'] = '-';
				}

                break;

            case 'breaking': 
			  
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_HOME']);
                $this->aCurrent['title'] = _t('_mz_news_page_title_browse_breaking');
                $this->aCurrent['restriction']['breaking']['value'] = 1;
                $this->aCurrent['rss']['link'] = 'modules/?r=news/rss/' . $sMode;
                break;

            case 'admin':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_HOME']);
                $this->aCurrent['title'] = _t('_mz_news_page_title_browse_admin');
				$this->aCurrent['restriction']['role']['value'] = '3'; 
                $this->aCurrent['rss']['link'] = 'modules/?r=news/rss/' . $sMode;
                //$this->aCurrent['sorting'] = 'popular';
                break;
 
            case 'popular':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_POPULAR']);
                $this->aCurrent['title'] = _t('_mz_news_page_title_browse_popular');
                $this->aCurrent['rss']['link'] = 'modules/?r=news/rss/' . $sMode;
                $this->aCurrent['sorting'] = 'popular';
                break;

            case 'top':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_TOP']);
                $this->aCurrent['title'] = _t('_mz_news_page_title_browse_top');
                $this->aCurrent['rss']['link'] = 'modules/?r=news/rss/' . $sMode;
                $this->aCurrent['sorting'] = 'top';
                break;

            case 'featured':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_FEATURED']);
                $this->aCurrent['title'] = _t('_mz_news_page_title_browse_featured');
                $this->aCurrent['rss']['link'] = 'modules/?r=news/rss/' . $sMode;
                $this->aCurrent['sorting'] = 'featured';
                break;

            case 'updated':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_UPDATED']);
                $this->aCurrent['title'] = _t('_mz_news_page_title_browse_updated');
                $this->aCurrent['rss']['link'] = 'modules/?r=news/rss/' . $sMode;
                $this->aCurrent['sorting'] = 'updated';
                break;

            case '': // search results
			    $this->checkRestrictionsForContext($sMode, $aParams, $oProfileAuthor);//una 13 beta  
                $this->sBrowseUrl = BX_DOL_SEARCH_KEYWORD_PAGE;
                $this->aCurrent['title'] = _t('_mz_news');
                unset($this->aCurrent['paginate']['perPage'], $this->aCurrent['rss']);
                break;

            default:
                $sMode = '';
                $this->isError = true;
        }

        $this->processReplaceableMarkers($oProfileAuthor);

        $this->addConditionsForPrivateContent($CNF, $oProfileAuthor);
		$this->addCustomConditions($CNF, $oProfileAuthor, $sMode, $aParams); //una 13 beta
    }
 
    protected function addConditionsForAuthorStatus($CNF)
    {
        if (empty($CNF['FIELD_AUTHOR']))
            return;

        $this->aCurrent['restriction']['statusAuthor'] = [
            'value' => 'active',
            'field' => 'status',
            'operator' => '=',
            'table' => 'sys_profiles',
        ];

        $this->aCurrent['join']['statusAuthor'] = [
            'type' => 'INNER',
            'table' => 'sys_profiles',
            'mainField' => $CNF['FIELD_AUTHOR'],
            'onField' => 'id',
            'joinFields' => array(),
        ];

        $this->aCurrent['join']['profileAccount'] = [
            'type' => 'INNER',
			'table' => 'sys_accounts',
			'mainTable' => 'sys_profiles',
			'mainField' => 'account_id',
			'onField' => 'id',
			'joinFields' => array('role'),
        ]; 
    }
  
    /**
     * Check restriction params and make condition part of query
     * return $sqlWhere sql code of query for WHERE part
     */
    function getRestriction ()
    {
		$CNF = &$this->oModule->_oConfig->CNF;
   
        $sqlWhere = parent::getRestriction ();
  
		if($this->sArchivePeriod)
		{ 
  			$iPos = strpos($this->sArchivePeriod, '-'); 
			if ($iPos === false){  
				$iMonthId = 0;
				$iYearId = $this->sArchivePeriod;
			}else{
				list($iYearId, $iMonthId) = explode('-', $this->sArchivePeriod);
			}

			$sqlWhere .= " AND YEAR(FROM_UNIXTIME(`".$this->aCurrent['table']."`.`".$CNF['FIELD_PUBLISHED']."`)) = {$iYearId} ";

			if($iMonthId)
			{
				$sqlWhere .= " AND MONTH(FROM_UNIXTIME(`".$this->aCurrent['table']."`.`".$CNF['FIELD_PUBLISHED']."`)) = {$iMonthId} ";
			} 
		}

        return $sqlWhere;
    }
 
}

/** @} */
