<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

/**
 * Embed provider.
 *
 * Create an embed from a link.
 *
 * @section embed_provider_create Creating the Editor object:
 *
 *
 * Add record to 'sys_objects_embeds' table:
 *
 * - object: name of the embed object, in the format: vendor prefix, underscore, module prefix, underscore, internal identifier or nothing
 * - title: title of the embed provider, dmay be isplayed in the Studio.
 * - override_class_name: user defined class name which is derived from one of base embed provider classes.
 * - override_class_file: the location of the user defined class, leave it empty if class is located in system folders.
 *
 *
 * @section example Example of usage
 *
 * Generate HTML for a link which will be converted to an embed later:
 *
 * @code
 *  $oEmbed = BxDolEmbed::getObjectInstance(); // get default embed object instance
 *  if ($oEmbed) // check if embed provider is available for using
 *      echo $oEmbed->getLinkHTML ('https://una.io', 'UNA.IO'); // output HTML which will be automatically converted into embed upon page load
 *  else
 *      echo '<a href="https://una.io">UNA.IO</a>';
 * @endcode
 *
 */
class BxDolEmbed extends BxDolFactoryObject
{
    protected $_bAsync = false;
    protected $_sSocketName = 'sys_embed';

    protected $_sTableData = '';

    protected $_iLifetime;

    protected $_bCssJsAdded = false;
        
    public function __construct ($aObject, $oTemplate = null)
    {
        parent::__construct($aObject, $oTemplate, 'BxDolEmbedQuery');

        $this->_oDb->setParams([
            'table_data' => $this->_sTableData
        ]);

        $this->_iLifetime = 86400 * (int)getParam('sys_embed_lifetime');
    }

    /**
     * Get embed provider object instance by object name
     * @param $sObject object name
     * @return object instance or false on error
     */
    static public function getObjectInstance($sObject = false, $oTemplate = false)
    {
        if (!$sObject)
            $sObject = getParam('sys_embed_default');

        return parent::getObjectInstanceByClassNames($sObject, $oTemplate, 'BxDolEmbed', 'BxDolEmbedQuery');
    }

    /**
     * Print HTML which will be automatically converted into embed upon page load
     * @param $sLink - link
     * @param $sTitle - title or use link for the title if omitted
     * @param $sMaxWidth - try to restrict max width of embed (works in supported embed providers only)
     */
    public function getLinkHTML ($sLink, $sTitle = '', $sMaxWidth = '')
    {
        // override this function in particular embed provider class
    }

    /**
     * Execute an initialization JS code
     */
    public function addProcessLinkMethod ()
    {
        // override this function in particular embed provider class
        return '';
    }
    
    /**
     * Add css/js files which are needed for embed display and functionality.
     */
    public function addJsCss ()
    {
        // override this function in particular embed provider class
    }

    public function getData ($sUrl, $sTheme, $bForcedly = false)
    {
        $sUrl = $this->cleanYoutubeUrl($sUrl);

        if($this->_sTableData) {
            $aLocal = $this->_oDb->getLocalInfo($sUrl, $sTheme);

            $bRevalidate = false;
            if($this->_iLifetime && ($iAdded = (int)($aLocal['added'] ?? 0)) && $iAdded + $this->_iLifetime < time()) {
                $this->_oDb->deleteLocal([
                    'url' => $sUrl,
                    'theme' => $sTheme
                ]);

                $bRevalidate = true;
            }

            $sData = false;
            if($bRevalidate || (($sKey = 'data') && !($sData = $aLocal[$sKey] ?? false)))
                $sData = $this->{'_getData' . ($sData !== false ? 'Empty' : ($this->_bAsync && !$bForcedly ? 'Async' : ''))}($sUrl, $sTheme);
        }
        else
            $sData = $this->_getData($sUrl, $sTheme);

        return json_decode($sData, true);
    }

    public function getDataFromApi ($sUrl, $sTheme)
    {
        // override this function in particular embed provider class
    }

    public function onGetDataFromApi ($iId)
    {
        // override this function in particular embed provider class
    }

    public function process ()
    {
        $aEmbeds = $this->_oDb->getLocalUnprocessed();
        if(empty($aEmbeds) || !is_array($aEmbeds))
            return;

        $oSockets = BxDolSockets::getInstance();
        $bSockets = $oSockets->isEnabled();

        foreach($aEmbeds as $aEmbed)
            if(($sData = $this->getDataFromApi($aEmbed['url'], $aEmbed['theme']))) {
                $this->_oDb->updateLocal(['data' => $sData], ['id' => $aEmbed['id']]);

                $this->onGetDataFromApi($aEmbed['id']);

                if($bSockets)
                    $oSockets->sendEvent($this->_sSocketName, $aEmbed['id'], 'processed', $sData);
            }
    }

    public function cleanYoutubeUrl($url)
    {
        $parsedUrl = parse_url($url);
        if (isset($parsedUrl['host']) && $parsedUrl['host'] === 'youtu.be') {
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                if (isset($queryParams['si'])) {
                    unset($queryParams['si']);
                }
                $queryString = http_build_query($queryParams);
                $cleanUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
                if (!empty($queryString)) {
                    $cleanUrl .= '?' . $queryString;
                }
                return $cleanUrl;
            }
            return $url;
        }
        return $url;
    }
    
    protected function _getData($sUrl, $sTheme)
    {
        $sData = $this->getDataFromApi($sUrl, $sTheme);
        if($sData) {
            $iId = $this->_oDb->insertLocal($sUrl, $sTheme, $sData);

            $this->onGetDataFromApi($iId);
        }

        return $sData;
    }

    protected function _getDataAsync($sUrl, $sTheme)
    {
        $iId = $this->_oDb->insertLocal($sUrl, $sTheme);

        if(($sName = 'bx_embed_processing') && ($oCronQuery = BxDolCronQuery::getInstance()) && !$oCronQuery->isTransientJobService($sName))
            $oCronQuery->addTransientJobService($sName, ['system', 'process_embed', [], 'TemplServices']);

        return $this->_getDataEmpty($sUrl, $sTheme, [
            'id' => $iId,
            'processing' => true
        ]);
    }

    protected function _getDataEmpty($sUrl, $sTheme, $aAddons = [])
    {
        return json_encode(array_merge([
            'url' => $sUrl,
            'domain' => parse_url($sUrl, PHP_URL_HOST),
        ], $aAddons));
    }
}

/** @} */
