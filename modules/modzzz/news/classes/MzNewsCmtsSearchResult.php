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

class MzNewsCmtsSearchResult extends BxBaseModGeneralCmtsSearchResult
{
    function __construct($sMode = '', $aParams = array())
    {
    	$this->sModule = 'mz_news';

        parent::__construct($sMode, $aParams);

        $this->aCurrent['title'] = _t('_mz_news_page_block_title_browse_cmts');
    }
}

/** @} */
