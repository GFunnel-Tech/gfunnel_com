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

class MzListingCmtsSearchResult extends BxBaseModGeneralCmtsSearchResult
{
    function __construct($sMode = '', $aParams = array())
    {
    	$this->sModule = 'mz_listing';

        parent::__construct($sMode, $aParams);

        $this->aCurrent['title'] = _t('_mz_listing_page_block_title_browse_cmts');
    }
}

/** @} */
