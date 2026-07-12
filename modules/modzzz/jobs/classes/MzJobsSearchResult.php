<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Jobs Jobs
 * @ingroup     ModzzzModules
 *
 * @{
 */

class MzJobsSearchResult extends BxBaseModTextSearchResult
{
    protected $sModule;

    function __construct($sMode = '', $aParams = array())
    {
        parent::__construct($sMode, $aParams);

        $this->sModule = 'mz_jobs';
        $this->oModule = BxDolModule::getInstance($this->sModule);

        $CNF = &$this->oModule->_oConfig->CNF;
 
        $this->aCurrent = array(
            'name' => $this->sModule,
            'module_name' => $this->sModule,
            'object_metatags' => $CNF['OBJECT_METATAGS'],
            'title' => _t('_mz_jobs_page_title_browse'),
            'table' => $CNF['TABLE_ENTRIES'], 
            'ownFields' => array('id', 'title', 'text',  'max_salary',  'min_salary', 'salary_type', 'salary_negotiable', 'location',  'company', 'company_uri', 'thumb', 'thumb_data', 'author', 'added'),
            'searchFields' => array(),
            'restriction' => array( 
                'location' => array('value' => '', 'field' => 'location', 'operator' => '='),
                'jobType' => array('value' => 'seeking', 'field' => 'type', 'operator' => '='), 
				'job_type' => array('value' => '', 'field'=>'job_type', 'operator'=>'='), 
				'company' => array('value' => '', 'field'=>'company_uri', 'operator'=>'='),  
				'remote' => array('value' => '', 'field'=>'remote', 'operator'=>'='), 
                'author' => array('value' => '', 'field' => 'author', 'operator' => '='),
                'featured' => array('value' => '', 'field' => 'featured', 'operator' => '<>'),
                'category' => array('value' => '', 'field' => 'category', 'operator' => '='),
                'status' => array('value' => 'active', 'field' => 'status', 'operator' => '='),
                'statusAdmin' => array('value' => 'active', 'field' => 'status_admin', 'operator' => '='),
            ),
            'paginate' => array('perPage' => getParam('mz_jobs_per_page_browse'), 'start' => 0),
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

        $this->sFilterName = 'mz_jobs_filter';

        $sSearchFields = getParam($CNF['PARAM_SEARCHABLE_FIELDS']);
        $this->aCurrent['searchFields'] = !empty($sSearchFields) ? explode(',', $sSearchFields) : '';

        $oProfileAuthor = null;

        switch ($sMode) {

            case 'local':  
 
				$sMetaType = '';

				$iProfileId = bx_get_logged_profile_id (); 
				if($iProfileId){
					$oProfile = BxDolProfile::getInstance($iProfileId);
					$aInfo = $oProfile->getInfo($iProfileId);
			 
					$oMeta = BxDolMetatags::getObjectInstance($aInfo['type']);
					$aProfileLocation = $oMeta->locationGet($aInfo['content_id']);
	 
					if(isset($aProfileLocation['city']) && $aProfileLocation['city'])
						 $sMetaType = 'location_country_city';
					elseif(isset($aProfileLocation['state']) && $aProfileLocation['state'])
						 $sMetaType = 'location_country_state';
					elseif(isset($aProfileLocation['country']) && $aProfileLocation['country'])
						 $sMetaType = 'location_country'; 
				}

				if($sMetaType){  
					$this->setMetaType('location_country_city');
	 
					$this->setCustomSearchCondition( $arr = array('keyword'=>$aProfileLocation['country'], 'city'=>$aProfileLocation['city']) );
	  
					$this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_LOCAL']);
					$this->aCurrent['title'] = _t('_mz_jobs_page_title_browse_local'); 
					$this->aCurrent['rss']['link'] = 'modules/?r=jobs/rss/' . $sMode;
				}else{ 
					$this->aCurrent['restriction']['location']['value'] = '-';
				} 
                break;
 
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
                $this->aCurrent['title'] = _t('_mz_jobs_page_title_browse_recent');
                $this->aCurrent['rss']['link'] = 'modules/?r=jobs/rss/' . $sMode;
                break;

            case 'featured':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_HOME']);
                $this->aCurrent['title'] = _t('_mz_jobs_page_title_browse_featured');
                $this->aCurrent['restriction']['featured']['value'] = '0';
                $this->aCurrent['rss']['link'] = 'modules/?r=jobs/rss/' . $sMode;
                $this->aCurrent['sorting'] = 'featured';
                break; 
 
			case 'job_types':

				$aJobTypes = BxDolForm::getDataItems('mz_jobs_type');
 
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_JOB_TYPE'],  array($CNF['GET_PARAM_JOB_TYPE'] => $aParams[$CNF['GET_PARAM_JOB_TYPE']]));
                if(!empty($aParams[$CNF['GET_PARAM_JOB_TYPE']]))
                    $this->aCurrent['restriction']['job_type']['value'] = $aParams[$CNF['GET_PARAM_JOB_TYPE']];

                $this->aCurrent['title'] = _t('_mz_jobs_page_title_browse_type_jobs', $aJobTypes[$aParams[$CNF['GET_PARAM_JOB_TYPE']]]); 
                $this->aCurrent['rss']['link'] = 'modules/?r=jobs/rss/' . $sMode .'/'. $aParams[$CNF['GET_PARAM_JOB_TYPE']];
                $this->aCurrent['sorting'] = 'last';
                break; 

			case 'company':
 
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_COMPANY'],  array($CNF['GET_PARAM_COMPANY'] => $aParams[$CNF['GET_PARAM_COMPANY']]));
                if(!empty($aParams[$CNF['GET_PARAM_COMPANY']]))
                    $this->aCurrent['restriction']['company']['value'] = $aParams[$CNF['GET_PARAM_COMPANY']];

				$sCompany = $this->oModule->_oDb->getCompanyByUri($aParams[$CNF['GET_PARAM_COMPANY']]);

                $this->aCurrent['title'] = _t('_mz_jobs_page_title_browse_type_jobs', $sCompany); 
                $this->aCurrent['rss']['link'] = 'modules/?r=jobs/rss/' . $sMode .'/'. $aParams[$CNF['GET_PARAM_COMPANY']];
                $this->aCurrent['sorting'] = 'last';
                break; 

			case 'remote':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_REMOTE']);
                $this->aCurrent['title'] = _t('_mz_jobs_page_title_browse_remote');
				$this->aCurrent['restriction']['remote']['value'] = 1;
                $this->aCurrent['rss']['link'] = 'modules/?r=jobs/rss/' . $sMode;
                $this->aCurrent['sorting'] = 'last';
                break;

			case 'wanted':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_WANTED']);
                $this->aCurrent['title'] = _t('_mz_jobs_page_title_browse_wanted');
				$this->aCurrent['restriction']['jobType']['value'] = 'wanted';
                $this->aCurrent['rss']['link'] = 'modules/?r=jobs/rss/' . $sMode;
                $this->aCurrent['sorting'] = 'last';
                break;
  
            case 'popular':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_POPULAR']);
                $this->aCurrent['title'] = _t('_mz_jobs_page_title_browse_popular');
                $this->aCurrent['rss']['link'] = 'modules/?r=jobs/rss/' . $sMode;
                $this->aCurrent['sorting'] = 'popular';
                break;

            case 'updated':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_UPDATED']);
                $this->aCurrent['title'] = _t('_mz_jobs_page_title_browse_updated');
                $this->aCurrent['rss']['link'] = 'modules/?r=jobs/rss/' . $sMode;
                $this->aCurrent['sorting'] = 'updated';
                break;

            case 'category':
                $this->sBrowseUrl = BxDolPermalinks::getInstance()->permalink($CNF['URL_CATEGORIES']);
                if(!empty($aParams['category']))
                    $this->aCurrent['restriction']['category']['value'] = $aParams['category'];
                $this->aCurrent['title'] = _t('_mz_jobs_page_title_browse_category');
                $this->aCurrent['rss']['link'] = 'modules/?r=jobs/rss/' . $sMode;
                $this->aCurrent['sorting'] = 'last';
                break;

            case '': // search results
                $this->sBrowseUrl = BX_DOL_SEARCH_KEYWORD_PAGE;
                $this->aCurrent['title'] = _t('_mz_jobs');
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
        }
        return $aSql;
    }
}

/** @} */
