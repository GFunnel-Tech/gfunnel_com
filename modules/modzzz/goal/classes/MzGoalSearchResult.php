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

class MzGoalSearchResult extends BxBaseModTextSearchResult
{
    protected $sModule;

   	protected $bItemType ='';
	
	function __construct($sMode = '', $aParams = array())
    {
        parent::__construct($sMode, $aParams);

        $this->sModule = 'mz_goal';
        $this->oModule = BxDolModule::getInstance($this->sModule);

        $CNF = &$this->oModule->_oConfig->CNF;
    
        $this->aCurrent = array(
            'name' => $this->sModule,
            'module_name' => $this->sModule,
            'object_metatags' => $CNF['OBJECT_METATAGS'],
            'title' => _t('_mz_goal_page_title_browse'),
            'table' => $CNF['TABLE_ENTRIES'],
            'ownFields' => array('id', 'title', 'text', 'thumb', 'thumb_data', 'attained_at_start', 'complete_by', 'last_update', 'tasks', 'tasks_completed', 'author', 'added'),
            'searchFields' => array(),
            'restriction' => array(  
                'supporter_goal_id' => array('value' => '', 'field' => 'entry_id', 'operator' => '=', 'table' => 'mz_goal_interested_track'), 
                'task_goal_id' => array('value' => '', 'field' => 'entry_id', 'operator' => '=', 'table' => 'mz_goal_tasks'), 
                'sponsor_goal_id' => array('value' => '', 'field' => 'entry_id', 'operator' => '=', 'table' => 'mz_goal_sponsors'), 
                'news_goal_id' => array('value' => '', 'field' => 'entry_id', 'operator' => '=', 'table' => 'mz_goal_news'), 
				'review_parent' => array('value' => '', 'field' => 'cmt_parent_id', 'operator' => '<', 'table' => 'mz_goal_reviews'), 
                'today_start' => array('value' => '', 'field' => 'complete_by', 'operator' => '>='), 
                'today_end' => array('value' => '', 'field' => 'complete_by', 'operator' => '<='), 
                'author' => array('value' => '', 'field' => 'author', 'operator' => '='),
                'id' => array('value' => '', 'field' => 'id', 'operator' => '='),
                'featured' => array('value' => '', 'field' => 'featured', 'operator' => '<>'),
                'category' => array('value' => '', 'field' => 'category', 'operator' => '='),
                'subcategory' => array('value' => '', 'field' => 'subcategory', 'operator' => '='),
                'status' => array('value' => 'active', 'field' => 'status', 'operator' => '='),
                'statusAdmin' => array('value' => 'active', 'field' => 'status_admin', 'operator' => '='),
            ),
            'paginate' => array('perPage' => getParam('mz_goal_per_page_browse'), 'start' => 0),
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

        $this->sFilterName = 'mz_goal_filter';

        $sSearchFields = getParam($CNF['PARAM_SEARCHABLE_FIELDS']);
        $this->aCurrent['searchFields'] = !empty($sSearchFields) ? explode(',', $sSearchFields) : '';

        $oProfileAuthor = null;

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
 
				unset($this->aCurrent['restriction']['objects_'.$this->sModule.'_list']);//UNA 13 RC2
                break;
 
            case 'public':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_HOME']);
                $this->aCurrent['title'] = _t('_mz_goal_page_title_browse_recent');
                $this->aCurrent['rss']['link'] = 'modules/?r=goal/rss/' . $sMode;
                break;

            case 'featured':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_FEATURED']);
                $this->aCurrent['title'] = _t('_mz_goal_page_title_browse_featured');
                $this->aCurrent['restriction']['featured']['value'] = '0';
                $this->aCurrent['rss']['link'] = 'modules/?r=goal/rss/' . $sMode;
                $this->aCurrent['sorting'] = 'featured';
                break; 

            case 'completed':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_COMPLETED']);
                $this->aCurrent['title'] = _t('_mz_goal_page_title_browse_completed');
                $this->aCurrent['restriction']['status']['value'] = 'completed';
                $this->aCurrent['rss']['link'] = 'modules/?r=goal/rss/' . $sMode;
                $this->aCurrent['sorting'] = 'updated';
                break; 

             case 'achieved':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_ACHIEVED']);
                $this->aCurrent['title'] = _t('_mz_goal_page_title_browse_achieved');
                $this->aCurrent['restriction']['status']['value'] = 'achieved';
                $this->aCurrent['rss']['link'] = 'modules/?r=goal/rss/' . $sMode;
                $this->aCurrent['sorting'] = 'updated';
                break; 
            case 'popular':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_POPULAR']);
                $this->aCurrent['title'] = _t('_mz_goal_page_title_browse_popular');
                $this->aCurrent['rss']['link'] = 'modules/?r=goal/rss/' . $sMode;
                $this->aCurrent['sorting'] = 'popular';
                break;
    
            case 'updated':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_UPDATED']);
                $this->aCurrent['title'] = _t('_mz_goal_page_title_browse_updated');
                $this->aCurrent['rss']['link'] = 'modules/?r=goal/rss/' . $sMode;
                $this->aCurrent['sorting'] = 'updated';
                break;
  
            case 'today': 
 
				$iBeginOfDay = strtotime("today", time());
				$iEndOfDay   = strtotime("tomorrow", $iBeginOfDay) - 1;
 
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_TODAY']);
 
				$this->aCurrent['restriction']['today_start']['value'] = $iBeginOfDay;
				$this->aCurrent['restriction']['today_end']['value'] = $iEndOfDay;
  
				$this->aCurrent['rss']['link'] = 'modules/?r=goal/rss/' . $sMode;
		 
                $this->aCurrent['title'] = _t('_mz_goal_page_title_browse_today');
                $this->aCurrent['sorting'] = 'last';
                break;
 
              case 'supporter':
   
				$this->bItemType = 'supporter';
  	    
				$this->aCurrent['join']['supporter'] = array(
					'type' => 'INNER',
					'mainTable' => 'mz_goal_entries',
					'table' => 'mz_goal_interested_track',
					'mainField' => 'id',
					'onField' => 'entry_id',
					'joinFields' => array('id','profile_id','date'),
				);
 
				$this->aCurrent['ownFields'] = array();
 
   				if(isset($aParams[$CNF['GET_PARAM_GOAL']]))
				{
					$this->aCurrent['restriction']['supporter_goal_id']['value'] = $aParams[$CNF['GET_PARAM_GOAL']];
					
					$this->sBrowseUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i='.$CNF['URI_VIEW_ENTRY'], array(
						$CNF['FIELD_ID'] => $aParams[$CNF['GET_PARAM_GOAL']]
					)));	
					
				}else{
					$this->aCurrent['restriction']['supporter_goal_id']['value'] = -999; 
				}
  
				$this->aCurrent['title'] = _t('_mz_goal_page_title_browse_goal_supporter');

                $this->aCurrent['sorting'] = 'supporter';
 
				break;

            case 'category':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_CATEGORIES']);
                if(!empty($aParams['category'])){
                    $this->aCurrent['restriction']['category']['value'] = $aParams[$CNF['GET_PARAM_CATEGORY']];
					$this->aCurrent['rss']['link'] = 'modules/?r=goal/rss/' . $sMode .'/'. $aParams[$CNF['GET_PARAM_CATEGORY']];
 				}else{
					$this->aCurrent['restriction']['category']['value'] = -99;
					$this->aCurrent['rss']['link'] = 'modules/?r=goal/rss/' . $sMode;
				}				
				$this->aCurrent['title'] = _t('_mz_goal_page_title_browse_category');
                $this->aCurrent['sorting'] = 'last';
                break; 
            case 'subcategory':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_SUBCATEGORIES']);
                if(!empty($aParams['subcategory'])){
                    $this->aCurrent['restriction']['subcategory']['value'] = $aParams[$CNF['GET_PARAM_SUBCATEGORY']];
					$this->aCurrent['rss']['link'] = 'modules/?r=goal/rss/' . $sMode .'/'. $aParams[$CNF['GET_PARAM_SUBCATEGORY']];
 				}else{
					$this->aCurrent['restriction']['subcategory']['value'] = -99;
					$this->aCurrent['rss']['link'] = 'modules/?r=goal/rss/' . $sMode;
				}	 
				$this->aCurrent['title'] = _t('_mz_goal_page_title_browse_category');
                $this->aCurrent['sorting'] = 'last';
                break;
 
            case 'tasks':
 				
				$this->bItemType = 'task';
  	    
				$this->aCurrent['join']['tasks'] = array(
					'type' => 'INNER',
					'mainTable' => 'mz_goal_entries',
					'table' => 'mz_goal_tasks',
					'mainField' => 'id',
					'onField' => 'entry_id',
					'joinFields' => array('id','title', 'text','progress','start_time','end_time','author','added','changed'),
				);
  
				$this->aCurrent['ownFields'] = array();
   
				if($aParams[$CNF['GET_PARAM_GOAL']])
				{
					$this->aCurrent['restriction']['task_goal_id']['value'] = $aParams[$CNF['GET_PARAM_GOAL']]; 
				}else{ 
					$this->aCurrent['restriction']['task_goal_id']['value'] = -99;
				}

				$this->sBrowseUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i='.$CNF['URI_VIEW_ENTRY'], array(
					$CNF['FIELD_ID'] => $aParams[$CNF['GET_PARAM_GOAL']]
				)));

				$this->aCurrent['title'] = _t('_mz_goal_page_title_browse_tasks');

                $this->aCurrent['sorting'] = 'task';
 
				break;

            case 'sponsors':
 				
				$this->bItemType = 'sponsor';
  	    
				$this->aCurrent['join']['sponsors'] = array(
					'type' => 'INNER',
					'mainTable' => 'mz_goal_entries',
					'table' => 'mz_goal_sponsors',
					'mainField' => 'id',
					'onField' => 'entry_id',
					'joinFields' => array('id','cat', 'text','title','author','added','changed'),
				);
  
				$this->aCurrent['ownFields'] = array();
    
   				if($aParams[$CNF['GET_PARAM_GOAL']])
				{
					$this->aCurrent['restriction']['sponsor_goal_id']['value'] = $aParams[$CNF['GET_PARAM_GOAL']];   
				}else{ 
					$this->aCurrent['restriction']['sponsor_goal_id']['value'] = -99;
				}

				$this->sBrowseUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i='.$CNF['URI_VIEW_ENTRY'], array(
					$CNF['FIELD_ID'] => $aParams[$CNF['GET_PARAM_GOAL']]
				)));

				$this->aCurrent['title'] = _t('_mz_goal_page_title_browse_sponsors');

                $this->aCurrent['sorting'] = 'sponsor';
 
				break;

            case 'news':
 				
				$this->bItemType = 'news';
  	    
				$this->aCurrent['join']['news'] = array(
					'type' => 'INNER',
					'mainTable' => 'mz_goal_entries',
					'table' => 'mz_goal_news',
					'mainField' => 'id',
					'onField' => 'entry_id',
					'joinFields' => array('id','start_time','end_time','text','title', 'author','added','changed'),
				);
 
				$this->aCurrent['ownFields'] = array();
   
				$iProfileId = bx_get_logged_profile_id (); 

				$aDataEntry = $this->oModule->_oDb->getContentInfoById($aParams[$CNF['GET_PARAM_GOAL']]);
				$iGoalAuthor = $aDataEntry[$CNF['FIELD_AUTHOR']];
  
   				if($aParams[$CNF['GET_PARAM_GOAL']])
				{
					$this->aCurrent['restriction']['news_goal_id']['value'] = $aParams[$CNF['GET_PARAM_GOAL']]; 
				}else{ 
					$this->aCurrent['restriction']['news_goal_id']['value'] = -99;
				} 
   
				$this->sBrowseUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i='.$CNF['URI_VIEW_ENTRY'], array(
					$CNF['FIELD_ID'] => $aParams[$CNF['GET_PARAM_GOAL']]
				)));
	  
				$this->aCurrent['title'] = _t('_mz_goal_page_title_browse_news');

                $this->aCurrent['sorting'] = 'news';
 
				break;
 
            case 'review':
 				
				$this->bItemType = 'review';
  	   
				$this->aCurrent['join']['review'] = array(
					'type' => 'INNER',
					'mainTable' => 'mz_goal_entries',
					'table' => 'mz_goal_reviews',
					'mainField' => 'id',
					'onField' => 'cmt_object_id',
					'joinFields' => array('cmt_id','cmt_parent_id','cmt_vparent_id','cmt_object_id','cmt_author_id','cmt_level','cmt_text','cmt_mood','cmt_rate','cmt_rate_count','cmt_time','cmt_replies','cmt_pinned'),
				);
  
				$this->aCurrent['ownFields'] = array('title', 'rate', 'votes', 'rrate', 'rvotes', 'score', 'comments', 'reviews', 'reviews_avg');
  
				$this->aCurrent['restriction']['review_parent']['value'] = '1';

                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_HOME']);
 
				$this->aCurrent['title'] = _t('_mz_goal_page_title_browse_reviews');

                $this->aCurrent['sorting'] = 'review';
 
				break;

            case '': // search results
                $this->sBrowseUrl = BX_DOL_SEARCH_KEYWORD_PAGE;
                $this->aCurrent['title'] = _t('_mz_goal');
                unset($this->aCurrent['paginate']['perPage'], $this->aCurrent['rss']);
                break;

            default:
                $sMode = '';
                $this->isError = true;
        }

        $this->processReplaceableMarkers($oProfileAuthor);

		if($sMode != 'city')
			$this->addConditionsForPrivateContent($CNF, $oProfileAuthor);
 
	    $this->addCustomConditions($CNF, $oProfileAuthor, $sMode, $aParams); //UNA 13 beta  
    }

    function getAlterOrder()
    {
        $CNF = &$this->oModule->_oConfig->CNF;

        $aSql = array();
        switch ($this->aCurrent['sorting']) {
            case 'last':
                $aSql['order'] = ' ORDER BY `' . $CNF['TABLE_ENTRIES'] . '`.`added` DESC';
                break;
            case 'featured':
                $aSql['order'] = ' ORDER BY `' . $CNF['TABLE_ENTRIES'] . '`.`featured` DESC';
                break;
            case 'updated':
                $aSql['order'] = ' ORDER BY `' . $CNF['TABLE_ENTRIES'] . '`.`changed` DESC';
                break;
            case 'popular':
                $aSql['order'] = ' ORDER BY `' . $CNF['TABLE_ENTRIES'] . '`.`views` DESC';
                break;
			case 'sponsor': 
				return array('order' => ' ORDER BY `' . $CNF['TABLE_SPONSORS'] . '`.`added` DESC '); 
                break;
			case 'news': 
				return array('order' => ' ORDER BY `' . $CNF['TABLE_NEWS'] . '`.`added` DESC '); 
                break;
			case 'task': 
				return array('order' => ' ORDER BY `' . $CNF['TABLE_TASK'] . '`.`added` ASC '); 
                break;
			case 'review':
				return array('order' => ' ORDER BY `mz_goal_reviews`.`cmt_time` DESC '); 
                break;
			case 'supporter': 
				return array('order' => ' ORDER BY `' . $CNF['TABLE_INTERESTED_TRACK'] . '`.`date` DESC '); 
                break; 
        }
        return $aSql;
    }

    function _getPseud ()
    {
        return array(
            'id' => 'id',
            'category' => 'category',
            'subcategory' => 'subcategory',
            'title' => 'title',
            'text' => 'text',
            'added' => 'added',
            'author' => 'author',
            'photo' => 'photo',
        );
    }
 
    function displaySearchUnit ($aData)
    {
        $CNF = &$this->oModule->_oConfig->CNF;

        $oMain = $this->getMain();
 
		if($this->bItemType == 'review')
		{  
			return $oMain->_oTemplate->review_main_unit($aData);
		}elseif($this->bItemType == 'sponsor')
		{  
			return $oMain->_oTemplate->sponsor_main_unit($aData);  
		}elseif($this->bItemType == 'news')
		{  
			return $oMain->_oTemplate->news_main_unit($aData); 		
		}elseif($this->bItemType == 'task')
		{  
			return $oMain->_oTemplate->task_main_unit($aData); 
		}elseif($this->bItemType == 'supporter')
		{  
			return $oMain->_oTemplate->supporter_unit($aData);   
		}else{
			return $oMain->_oTemplate->unit($aData, $this->bProcessPrivateContent, $this->_bLiveSearch ? $this->sUnitTemplateLiveSearch : $this->sUnitTemplate, $this->aUnitParams);
		}
    }
   
    function displayResultBlock ()
    {
        $oMain = $this->getMain();

		if(!empty($this->bItemType))
		{
			$sCode = '';
			bx_alert('simple_search', 'before_get_data', 0, false, array('object' => &$this->aCurrent, 'mode' => $this->_sMode));
			$aData = $this->getSearchData();
			bx_alert('simple_search', 'get_data', 0, false, array('object' => &$this->aCurrent, 'mode' => $this->_sMode, 'search_results' => &$aData));
			if ($this->aCurrent['paginate']['num'] > 0) {

				$sCode .= $this->addCustomParts();

				foreach ($aData as $aValue)
					$sCode .= $this->displaySearchUnit($aValue);  
			}
	 
	 		if($this->bItemType=='news') 
				$sCode = $oMain->_oTemplate->parseHtmlByName('comment_block.html', array(
					  'id' => time(),
					  'comment' => $sCode, 
					  'view_image_popup' => '',
					  'script' => '',
				));
 
			return $sCode;
		}else{
			return parent::displayResultBlock ();
		}
    }




}

/** @} */
