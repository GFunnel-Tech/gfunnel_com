<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Listing Listing
 * @ingroup     ModzzzModules
 *
 * @{
 */

class MzListingSearchResult extends BxBaseModTextSearchResult
{
    protected $sModule;

   	protected $bItemType ='';
	
	function __construct($sMode = '', $aParams = array())
    {
        parent::__construct($sMode, $aParams);
 
        $this->sModule = 'mz_listing';
        $this->oModule = BxDolModule::getInstance($this->sModule);

        $CNF = &$this->oModule->_oConfig->CNF;
  
        $this->aCurrent = array(
            'name' => $this->sModule,
            'module_name' => $this->sModule,
            'object_metatags' => $CNF['OBJECT_METATAGS'],
            'title' => _t('_mz_listing_page_title_browse'),
            'table' => $CNF['TABLE_ENTRIES'],
            'ownFields' => array('id', 'title', 'text', 'thumb', 'thumb_data', 'author', 'added'),
            'searchFields' => array(),
            'restriction' => array(  
                'location' => array('value' => '', 'field' => 'location', 'operator' => '='),
                'client_listing_id' => array('value' => '', 'field' => 'entry_id', 'operator' => '=', 'table' => 'mz_listing_interested_track'), 
                'schedule_listing_id' => array('value' => '', 'field' => 'entry_id', 'operator' => '=', 'table' => 'mz_listing_schedules'), 
                'announcement_listing_id' => array('value' => '', 'field' => 'entry_id', 'operator' => '=', 'table' => 'mz_listing_announcements'), 
                'service_listing_id' => array('value' => '', 'field' => 'entry_id', 'operator' => '=', 'table' => 'mz_listing_services'), 
                'gift_listing_id' => array('value' => '', 'field' => 'entry_id', 'operator' => '=', 'table' => 'mz_listing_gifts'), 
                'reward_listing_id' => array('value' => '', 'field' => 'entry_id', 'operator' => '=', 'table' => 'mz_listing_rewards'), 
                'branch_listing_id' => array('value' => '', 'field' => 'entry_id', 'operator' => '=', 'table' => 'mz_listing_branches'), 
                'branch_private' => array('value' => '', 'field' => 'private', 'operator' => '!=', 'table' => 'mz_listing_branches'), 
				'review_parent' => array('value' => '', 'field' => 'cmt_parent_id', 'operator' => '<', 'table' => 'mz_listing_reviews'), 
				'listing_private' => array('value' => '', 'field' => 'branch_private', 'operator' => '!='), 
                'author' => array('value' => '', 'field' => 'author', 'operator' => '='),
                'dummy' => array('value' => '', 'field' => 'id', 'operator' => '='),
                'featured' => array('value' => '', 'field' => 'featured', 'operator' => '<>'),
                'category' => array('value' => '', 'field' => 'category', 'operator' => '='),
                'subcategory' => array('value' => '', 'field' => 'subcategory', 'operator' => '='),
                'status' => array('value' => 'active', 'field' => 'status', 'operator' => '='),
                'statusAdmin' => array('value' => 'active', 'field' => 'status_admin', 'operator' => '='),
            ),
            'paginate' => array('perPage' => getParam('mz_listing_per_page_browse'), 'start' => 0),
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

        $this->sFilterName = 'mz_listing_filter';

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
                $this->aCurrent['title'] = _t('_mz_listing_page_title_browse_recent');
                $this->aCurrent['rss']['link'] = 'modules/?r=listing/rss/' . $sMode;
                break;

            case 'featured':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_FEATURED']);
                $this->aCurrent['title'] = _t('_mz_listing_page_title_browse_featured');
                $this->aCurrent['restriction']['featured']['value'] = '0';
                $this->aCurrent['rss']['link'] = 'modules/?r=listing/rss/' . $sMode;
                $this->aCurrent['sorting'] = 'featured';
                break; 

            case 'popular':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_POPULAR']);
                $this->aCurrent['title'] = _t('_mz_listing_page_title_browse_popular');
                $this->aCurrent['rss']['link'] = 'modules/?r=listing/rss/' . $sMode;
                $this->aCurrent['sorting'] = 'popular';
                break;
    
            case 'updated':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_UPDATED']);
                $this->aCurrent['title'] = _t('_mz_listing_page_title_browse_updated');
                $this->aCurrent['rss']['link'] = 'modules/?r=listing/rss/' . $sMode;
                $this->aCurrent['sorting'] = 'updated';
                break;

            case 'top_reviewed':
 
                $this->aCurrent['sorting'] = 'top_reviewed';
                $this->aCurrent['restriction']['top_reviewed'] = array('value' => getParam('mz_listing_min_review_cnt'), 'field' => 'reviews', 'operator' => '>=');

                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_HOME']);
                $this->aCurrent['title'] = _t('_mz_listing_page_title_browse_top_reviewed');
                $this->aCurrent['rss']['link'] = 'modules/?r=listing/rss/' . $sMode;
                break;

            case 'last_reviewed':

				$this->aCurrent['join']['last_reviewed'] = array(
					'type' => 'INNER',
					'mainTable' => 'mz_listing_entries',
					'table' => 'mz_listing_reviews',
					'mainField' => 'id',
					'onField' => 'cmt_object_id',
					'joinFields' => array('cmt_time'),
				);

                $this->aCurrent['sorting'] = 'review';

                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_HOME']);
                $this->aCurrent['title'] = _t('_mz_listing_page_title_browse_last_reviewed');
                $this->aCurrent['rss']['link'] = 'modules/?r=listing/rss/' . $sMode;
                break; 
 /* 
            case 'city':
    
				$this->bItemType = 'city';

				$bLocationSet = false;
   				if(isset($aParams[$CNF['GET_PARAM_LISTING']]))
				{
					$iContentId = $aParams[$CNF['GET_PARAM_LISTING']];
					$oMeta = BxDolMetatags::getObjectInstance('mz_listing');
					$aLocation = $oMeta->locationGet($iContentId);
	  
					if(isset($aLocation['country']) && isset($aLocation['city']) && $aLocation['country'] && $aLocation['city'])
					{  
						$this->aCurrent['restriction']['city_profile_country'] = array('value' => $aLocation['country'], 'field' => 'country', 'operator' => '=', 'table' => 'bx_persons_meta_locations'); 
						$this->aCurrent['restriction']['city_profile_city'] = array('value' => $aLocation['city'], 'field' => 'city', 'operator' => '=', 'table' => 'bx_persons_meta_locations'); 
						$bLocationSet = true;
					}

					$this->sBrowseUrl = BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php', array(
						'i' => $CNF['URI_VIEW_ENTRY'], 
						$CNF['FIELD_ID'] => $aParams[$CNF['GET_PARAM_LISTING']]
					)); 
				} 
 
				if($bLocationSet)
				{
					$this->aCurrent['table'] = 'bx_persons_data';

					$this->aCurrent['join']['city_persons'] = array(
						'type' => 'INNER',
						'mainTable' => 'bx_persons_data',
						'table' => 'sys_profiles',
						'mainField' => 'id',
						'onField' => 'content_id',
						'joinFields' => array('id'),
					);
	 
					$this->aCurrent['join']['city_location'] = array(
						'type' => 'INNER',
						'mainTable' => 'bx_persons_data',
						'table' => 'bx_persons_meta_locations',
						'mainField' => 'id',
						'onField' => 'object_id',
						'joinFields' => array('object_id'),
					);
	 
					$this->aCurrent['ownFields'] = array('author','added');

					unset($this->aCurrent['restriction']['status']);
					unset($this->aCurrent['restriction']['statusAdmin']);
	 
					$this->aCurrent['restriction']['city_profile_active'] = array('value' => 'active', 'field' => 'status', 'operator' => '=', 'table' => 'sys_profiles'); 
					$this->aCurrent['restriction']['city_profile_type'] = array('value' => array('bx_persons'), 'field' => 'type', 'operator' => 'in', 'table' => 'sys_profiles'); 

					$this->aCurrent['restriction']['city_profile_self'] = array('value' => bx_get_logged_profile_id(), 'field' => 'author', 'operator' => '<>', 'table' => 'bx_persons_data'); 
 
				}else{ 
					$this->aCurrent['restriction']['dummy']['value'] = -99; 
				}
 
				$this->aCurrent['title'] = _t('_mz_listing_page_title_browse_listing_city');

                $this->aCurrent['sorting'] = 'city';
 
				break;
*/
              case 'client':
   
				$this->bItemType = 'client';
  	    
				$this->aCurrent['join']['client'] = array(
					'type' => 'INNER',
					'mainTable' => 'mz_listing_entries',
					'table' => 'mz_listing_interested_track',
					'mainField' => 'id',
					'onField' => 'entry_id',
					'joinFields' => array('id','profile_id','date'),
				);
 
				$this->aCurrent['ownFields'] = array();
 
   				if(isset($aParams[$CNF['GET_PARAM_LISTING']]))
				{
				    $this->aCurrent['paginate']['perPage'] = getParam('mz_listing_per_page_view');
					$this->aCurrent['restriction']['client_listing_id']['value'] = $aParams[$CNF['GET_PARAM_LISTING']];
					
					$this->sBrowseUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i='.$CNF['URI_VIEW_ENTRY'], array(
						$CNF['FIELD_ID'] => $aParams[$CNF['GET_PARAM_LISTING']]
					)));	
					
				}else{
					$this->aCurrent['restriction']['client_listing_id']['value'] = -99; 
				}
  
				$this->aCurrent['title'] = _t('_mz_listing_page_title_browse_listing_client');

                $this->aCurrent['sorting'] = 'client';
 
				break;

            case 'category':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_CATEGORIES']);
                if(!empty($aParams['category'])){
                    $this->aCurrent['restriction']['category']['value'] = $aParams[$CNF['GET_PARAM_CATEGORY']];
					$this->aCurrent['rss']['link'] = 'modules/?r=listing/rss/' . $sMode .'/'. $aParams[$CNF['GET_PARAM_CATEGORY']];
 				}else{
					$this->aCurrent['restriction']['category']['value'] = -99;
					$this->aCurrent['rss']['link'] = 'modules/?r=listing/rss/' . $sMode;
				}				
				$this->aCurrent['title'] = _t('_mz_listing_page_title_browse_category');
                $this->aCurrent['sorting'] = 'last';
                break; 
            case 'subcategory':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_SUBCATEGORIES']);
                if(!empty($aParams['subcategory'])){
                    $this->aCurrent['restriction']['subcategory']['value'] = $aParams[$CNF['GET_PARAM_SUBCATEGORY']];
					$this->aCurrent['rss']['link'] = 'modules/?r=listing/rss/' . $sMode .'/'. $aParams[$CNF['GET_PARAM_SUBCATEGORY']];
 				}else{
					$this->aCurrent['restriction']['subcategory']['value'] = -99;
					$this->aCurrent['rss']['link'] = 'modules/?r=listing/rss/' . $sMode;
				}	 
				$this->aCurrent['title'] = _t('_mz_listing_page_title_browse_category');
                $this->aCurrent['sorting'] = 'last';
                break;

            case 'local':  
 
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
					$this->aCurrent['title'] = _t('_mz_listing_page_title_browse_local'); 
					$this->aCurrent['rss']['link'] = 'modules/?r=listing/rss/' . $sMode;
				}else{ 
					$this->aCurrent['restriction']['location']['value'] = '-';
				}

                break;
            case 'announcements':
 				
				$this->bItemType = 'announcement';
  	    

				$this->aCurrent['join']['announcements'] = array(
					'type' => 'INNER',
					'mainTable' => 'mz_listing_entries',
					'table' => 'mz_listing_announcements',
					'mainField' => 'id',
					'onField' => 'entry_id',
					'joinFields' => array('id','title', 'text','author','added','changed'),
				);
  
				$this->aCurrent['ownFields'] = array();
   
				if($aParams[$CNF['GET_PARAM_LISTING']])
				{
				    $this->aCurrent['paginate']['perPage'] = getParam('mz_listing_per_page_announce'); 
					$this->aCurrent['restriction']['announcement_listing_id']['value'] = $aParams[$CNF['GET_PARAM_LISTING']]; 
				}else{ 
					$this->aCurrent['restriction']['announcement_listing_id']['value'] = -99;
				}

				$this->sBrowseUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i='.$CNF['URI_VIEW_ENTRY'], array(
					$CNF['FIELD_ID'] => $aParams[$CNF['GET_PARAM_LISTING']]
				)));

				$this->aCurrent['title'] = _t('_mz_listing_page_title_browse_announcements');

                $this->aCurrent['sorting'] = 'announcement';
 
				break;
            case 'schedules':
 				
				$this->bItemType = 'schedule';
	 
				$this->aCurrent['join']['schedules'] = array(
					'type' => 'INNER',
					'mainTable' => 'mz_listing_entries',
					'table' => 'mz_listing_schedules',
					'mainField' => 'id',
					'onField' => 'entry_id',
					'joinFields' => array('id','title', 'text','start_time','end_time','author','added','changed'),
				);
  
				$this->aCurrent['ownFields'] = array();
   
				if($aParams[$CNF['GET_PARAM_LISTING']])
				{
				    $this->aCurrent['paginate']['perPage'] = getParam('mz_listing_per_page_view');
					$this->aCurrent['restriction']['schedule_listing_id']['value'] = $aParams[$CNF['GET_PARAM_LISTING']]; 
				}else{ 
					$this->aCurrent['restriction']['schedule_listing_id']['value'] = -99;
				}

				$this->sBrowseUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i='.$CNF['URI_VIEW_ENTRY'], array(
					$CNF['FIELD_ID'] => $aParams[$CNF['GET_PARAM_LISTING']]
				)));

				$this->aCurrent['title'] = _t('_mz_listing_page_title_browse_schedules');

                $this->aCurrent['sorting'] = 'schedule';
 
				break;
            case 'services':
 				
				$this->bItemType = 'service';
 		 
				$this->aCurrent['join']['services'] = array(
					'type' => 'INNER',
					'mainTable' => 'mz_listing_entries',
					'table' => 'mz_listing_services',
					'mainField' => 'id',
					'onField' => 'entry_id',
					'joinFields' => array('id','cat','thumb','text','title','author','added','changed'),
				);
  
				$this->aCurrent['ownFields'] = array();
    
   				if($aParams[$CNF['GET_PARAM_LISTING']])
				{ 
				    $this->aCurrent['paginate']['perPage'] = getParam('mz_listing_per_page_view');
					$this->aCurrent['restriction']['service_listing_id']['value'] = $aParams[$CNF['GET_PARAM_LISTING']];   
				}else{ 
					$this->aCurrent['restriction']['service_listing_id']['value'] = -99;
				}

				$this->sBrowseUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i='.$CNF['URI_VIEW_ENTRY'], array(
					$CNF['FIELD_ID'] => $aParams[$CNF['GET_PARAM_LISTING']]
				)));

				$this->aCurrent['title'] = _t('_mz_listing_page_title_browse_services');

                $this->aCurrent['sorting'] = 'service';
 
				break;
            case 'gifts':
 				
				$this->bItemType = 'gift';
 		 
				$this->aCurrent['join']['gifts'] = array(
					'type' => 'INNER',
					'mainTable' => 'mz_listing_entries',
					'table' => 'mz_listing_gifts',
					'mainField' => 'id',
					'onField' => 'entry_id',
					'joinFields' => array('id','cat','thumb','text','title','author','added','changed'),
				);
  
				$this->aCurrent['ownFields'] = array();
    
   				if($aParams[$CNF['GET_PARAM_LISTING']])
				{ 
				    $this->aCurrent['paginate']['perPage'] = getParam('mz_listing_per_page_view');
					$this->aCurrent['restriction']['gift_listing_id']['value'] = $aParams[$CNF['GET_PARAM_LISTING']];   
				}else{ 
					$this->aCurrent['restriction']['gift_listing_id']['value'] = -99;
				}

				$this->sBrowseUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i='.$CNF['URI_VIEW_ENTRY'], array(
					$CNF['FIELD_ID'] => $aParams[$CNF['GET_PARAM_LISTING']]
				)));

				$this->aCurrent['title'] = _t('_mz_listing_page_title_browse_gifts');

                $this->aCurrent['sorting'] = 'gift';
 
				break;

            case 'rewards':
 				
				$this->bItemType = 'reward';
 		 
				$this->aCurrent['join']['rewards'] = array(
					'type' => 'INNER',
					'mainTable' => 'mz_listing_entries',
					'table' => 'mz_listing_rewards',
					'mainField' => 'id',
					'onField' => 'entry_id',
					'joinFields' => array('id', 'thumb','text','title','author','added','changed'),
				);
  
				$this->aCurrent['ownFields'] = array();
    
   				if($aParams[$CNF['GET_PARAM_LISTING']])
				{ 
				    $this->aCurrent['paginate']['perPage'] = getParam('mz_listing_per_page_view');
					$this->aCurrent['restriction']['reward_listing_id']['value'] = $aParams[$CNF['GET_PARAM_LISTING']];   
				}else{ 
					$this->aCurrent['restriction']['reward_listing_id']['value'] = -99;
				}

				$this->sBrowseUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i='.$CNF['URI_VIEW_ENTRY'], array(
					$CNF['FIELD_ID'] => $aParams[$CNF['GET_PARAM_LISTING']]
				)));

				$this->aCurrent['title'] = _t('_mz_listing_page_title_browse_rewards');

                $this->aCurrent['sorting'] = 'reward';
 
				break;

            case 'branches':
 				
				$this->bItemType = 'branch';
 
				$this->aCurrent['join']['branches'] = array(
					'type' => 'INNER',
					'mainTable' => 'mz_listing_entries',
					'table' => 'mz_listing_branches',
					'mainField' => 'id',
					'onField' => 'entry_id',
					'joinFields' => array('id','text','thumb','title','author','added','changed'),
				);
 
				$this->aCurrent['ownFields'] = array();
   
				$iProfileId = bx_get_logged_profile_id (); 
 
   				if($aParams[$CNF['GET_PARAM_LISTING']])
				{
					$this->aCurrent['paginate']['perPage'] = getParam('mz_listing_per_page_view');
					$this->aCurrent['restriction']['branch_listing_id']['value'] = $aParams[$CNF['GET_PARAM_LISTING']]; 
				}else{ 
					$this->aCurrent['restriction']['branch_listing_id']['value'] = -99;
				} 
   
				$this->sBrowseUrl = bx_absolute_url(BxDolPermalinks::getInstance()->permalink('page.php?i='.$CNF['URI_VIEW_ENTRY'], array(
					$CNF['FIELD_ID'] => $aParams[$CNF['GET_PARAM_LISTING']]
				)));
	  
				$this->aCurrent['title'] = _t('_mz_listing_page_title_browse_branches');

                $this->aCurrent['sorting'] = 'branch';
 
				break;
 
            case 'review':
 				
				$this->bItemType = 'review';
  	   
				$this->aCurrent['paginate']['perPage'] = getParam('mz_listing_per_page_review');

				$this->aCurrent['join']['review'] = array(
					'type' => 'INNER',
					'mainTable' => 'mz_listing_entries',
					'table' => 'mz_listing_reviews',
					'mainField' => 'id',
					'onField' => 'cmt_object_id',
					'joinFields' => array('cmt_id','cmt_parent_id','cmt_vparent_id','cmt_object_id','cmt_author_id','cmt_text','cmt_mood','cmt_rate','cmt_rate_count','cmt_time','cmt_replies','cmt_pinned'),
				);
   
				$this->aCurrent['ownFields'] = array('title');
  
				$this->aCurrent['restriction']['review_parent']['value'] = '1';

                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_HOME']);
 
				$this->aCurrent['title'] = _t('_mz_listing_page_title_browse_reviews');

                $this->aCurrent['sorting'] = 'review';
 
				break;

            case '': // search results
                $this->sBrowseUrl = BX_DOL_SEARCH_KEYWORD_PAGE;
                $this->aCurrent['title'] = _t('_mz_listing');
                unset($this->aCurrent['paginate']['perPage'], $this->aCurrent['rss']);
                break;

            default:
                $sMode = '';
                $this->isError = true;
        }

        $this->processReplaceableMarkers($oProfileAuthor);
 
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
			case 'service': 
				return array('order' => ' ORDER BY `' . $CNF['TABLE_SERVICES'] . '`.`added` DESC '); 
                break;
			case 'gift': 
				return array('order' => ' ORDER BY `' . $CNF['TABLE_GIFTS'] . '`.`added` DESC '); 
                break;
			case 'reward': 
				return array('order' => ' ORDER BY `' . $CNF['TABLE_REWARDS'] . '`.`added` DESC '); 
                break;
			case 'branch': 
				return array('order' => ' ORDER BY `' . $CNF['TABLE_BRANCHES'] . '`.`added` DESC '); 
                break;
			case 'schedule': 
				return array('order' => ' ORDER BY `' . $CNF['TABLE_SCHEDULE'] . '`.`added` ASC '); 
                break;
			case 'announcement': 
				return array('order' => ' ORDER BY `' . $CNF['TABLE_ANNOUNCEMENT'] . '`.`sticky` DESC, `' . $CNF['TABLE_ANNOUNCEMENT'] . '`.`added` ASC '); 
                break;			
			case 'top_reviewed':
				return array('order' => ' ORDER BY `' . $CNF['TABLE_ENTRIES'] . '`.`reviews_avg` DESC '); 
                break; 
			case 'review':
				return array('order' => ' ORDER BY `mz_listing_reviews`.`cmt_time` DESC '); 
                break;
			case 'client': 
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
		}elseif($this->bItemType == 'service')
		{  
			return $oMain->_oTemplate->service_main_unit($aData);  
		}elseif($this->bItemType == 'reward')
		{  
			return $oMain->_oTemplate->reward_main_unit($aData);  
		}elseif($this->bItemType == 'gift')
		{  
			return $oMain->_oTemplate->gift_main_unit($aData);  
		}elseif($this->bItemType == 'branch')
		{  
			return $oMain->_oTemplate->branch_main_unit($aData); 		
		}elseif($this->bItemType == 'schedule')
		{  
			return $oMain->_oTemplate->schedule_main_unit($aData); 
		}elseif($this->bItemType == 'announcement')
		{  
			return $oMain->_oTemplate->announcement_main_unit($aData); 
		}elseif($this->bItemType == 'client')
		{  
			return $oMain->_oTemplate->listing_profile_client($aData);   
		}else{
			return $oMain->_oTemplate->unit($aData, $this->bProcessPrivateContent, $this->_bLiveSearch ? $this->sUnitTemplateLiveSearch : $this->sUnitTemplate, $this->aUnitParams);
		}
    }
    
    function displayResultBlock ()
    {
        $oMain = $this->getMain();

		if(!empty($this->bItemType) && !in_array($this->bItemType, array('branch','service','gift','reward'))) 
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
 /*
	 		if($this->bItemType=='branch') 
				$sCode = $oMain->_oTemplate->parseHtmlByName('comment_block.html', array(
					  'id' => time(),
					  'comment' => $sCode, 
					  'view_image_popup' => '',
					  'script' => '',
				));
 */
			return $sCode;
		}else{
			return parent::displayResultBlock ();
		}
    }




}

/** @} */
