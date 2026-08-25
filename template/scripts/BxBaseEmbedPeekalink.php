<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaBaseView UNA Base Representation Classes
 * @{
 */

/**
 * Peekalink integration.
 * 
 * @see BxDolEmbed
 */
class BxBaseEmbedPeekalink extends BxTemplEmbedSystem
{
    protected $_sEndpoint;

    public function __construct ($aObject, $oTemplate)
    {
        parent::__construct ($aObject, $oTemplate);

        $this->_bAsync = true;

        $this->_sEndpoint = 'https://api.peekalink.io/';
    }

    public function getDataFromApi ($sUrl, $sTheme)
    {
        $sResponse = '';
        if(($sApiKey = getParam('sys_embed_peekalink_api_key')) != '')
            $sResponse = bx_file_get_contents($this->_sEndpoint, ['link' => $sUrl], 'post-json', [
                'Authorization: Bearer ' . $sApiKey
            ]);

        $aResponse = [];
        if(!$sResponse || !($aResponse = json_decode($sResponse, true)) || !is_array($aResponse))
            return '';

        $aResult = [
            'title' => $aResponse['title'],
            'description' => $aResponse['description'],
            'image' => '',
            'logo' => '',
            'url' => $aResponse['url'],
            'domain' => $aResponse['domain']
        ];

        $aIs = array_flip(['thumbnail', 'medium', 'large', 'original']);

        if(($aP = $aResponse['page'] ?? false) && ($aPs = $aP['screenshot'] ?? false) && ($aPs = array_intersect_key($aPs, $aIs)) && (($aImg = $aPs['medium'] ?? false) || ($aImg = reset($aPs))))
            $aResult['image'] = $aImg['url'];

        if(($aI = $aResponse['image'] ?? false) && ($aI = array_intersect_key($aI, $aIs)) && (($aImg = $aI['thumbnail'] ?? false) || ($aImg = reset($aI))))
            $aResult['logo'] = $aImg['url'];

        $this->_storeImages($aResult);

        return json_encode($aResult);
    }
}

/** @} */
