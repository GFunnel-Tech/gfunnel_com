<?php defined('BX_DOL') or die('hack attempt');
/**
 *     copyright            : (C) 2018 AQB Soft
 *     website              : http://www.aqbsoft.com
 *
 * IMPORTANT: This is a commercial product made by AQB Soft. It cannot be modified for other than personal usage.
 * The "personal usage" means the product can be installed and set up for ONE domain name ONLY.
 * To be able to use this product for another domain names you have to order another copy of this product (license).
 *
 * This product cannot be redistributed for free or a fee without written permission from AQB Soft.
 *
 * This notice may not be removed from the source code.
 *
 * @defgroup    Locations Map Locations Map
 * @ingroup     UnaModules
 *
 * @{
 */


class AqbLocationsMapConfig extends BxBaseModGeneralConfig {
	function __construct($aModule) {
	    parent::__construct($aModule);
    }

    function getOSMMapProvider($sProviderName, $sAPIKey = '') {
    	$aProviders = array(
    		'OSM.Mapnik' => array(
    			'url' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    			'options' => array(
    				'maxZoom' => 19,
					'attribution' => '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    			),
    		),
    		'OSM.BackAndWhite' => array(
    			'url' => 'http://{s}.tiles.wmflabs.org/bw-mapnik/{z}/{x}/{y}.png',
    			'options' => array(
    				'maxZoom' => 18,
					'attribution' => '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    			),
    		),
    		'Stamen.Toner' => array(
    			'url' => 'https://stamen-tiles-{s}.a.ssl.fastly.net/toner/{z}/{x}/{y}{r}.{ext}',
    			'options' => array(
    				'attribution' => 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
					'subdomains' => 'abcd',
					'minZoom' => 0,
					'maxZoom' => 20,
					'ext' => 'png',
    			),
    		),
    		'Stamen.TonerLite' => array(
    			'url' => 'https://stamen-tiles-{s}.a.ssl.fastly.net/toner-lite/{z}/{x}/{y}{r}.{ext}',
    			'options' => array(
    				'attribution' => 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
					'subdomains' => 'abcd',
					'minZoom' => 0,
					'maxZoom' => 20,
					'ext' => 'png',
    			),
    		),
    		'Stamen.Terrain' => array(
    			'url' => 'https://stamen-tiles-{s}.a.ssl.fastly.net/terrain/{z}/{x}/{y}{r}.{ext}',
    			'options' => array(
    				'attribution' => 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
					'subdomains' => 'abcd',
					'minZoom' => 0,
					'maxZoom' => 22,
					'ext' => 'png',
    			),
    		),
    		'Esri.WorldStreetMap' => array(
    			'url' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}',
    			'options' => array(
    				'attribution' => 'Tiles &copy; Esri &mdash; Source: Esri, DeLorme, NAVTEQ, USGS, Intermap, iPC, NRCAN, Esri Japan, METI, Esri China (Hong Kong), Esri (Thailand), TomTom, 2012',
                    'maxZoom' => 22,
    			),
    		),
    		'Esri.WorldTopoMap' => array(
    			'url' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}',
    			'options' => array(
    				'attribution' => 'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ, TomTom, Intermap, iPC, USGS, FAO, NPS, NRCAN, GeoBase, Kadaster NL, Ordnance Survey, Esri Japan, METI, Esri China (Hong Kong), and the GIS User Community',
                    'maxZoom' => 22,
    			),
    		),
    		'CartoDB.Positron' => array(
    			'url' => 'https://cartodb-basemaps-{s}.global.ssl.fastly.net/light_all/{z}/{x}/{y}{r}.png',
    			'options' => array(
    				'attribution' => '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="http://cartodb.com/attributions">CartoDB</a>',
    				'subdomains' => 'abcd',
					'maxZoom' => 19,
    			),
    		),
    		'CartoDB.PositronNoLabels' => array(
    			'url' => 'https://cartodb-basemaps-{s}.global.ssl.fastly.net/light_nolabels/{z}/{x}/{y}{r}.png',
    			'options' => array(
    				'attribution' => '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="http://cartodb.com/attributions">CartoDB</a>',
    				'subdomains' => 'abcd',
					'maxZoom' => 19,
    			),
    		),
    		'CartoDB.DarkMatter' => array(
    			'url' => 'https://cartodb-basemaps-{s}.global.ssl.fastly.net/dark_all/{z}/{x}/{y}{r}.png',
    			'options' => array(
    				'attribution' => '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="http://cartodb.com/attributions">CartoDB</a>',
    				'subdomains' => 'abcd',
					'maxZoom' => 19,
    			),
    		),
    		'CartoDB.Voyager' => array(
    			'url' => 'https://cartodb-basemaps-{s}.global.ssl.fastly.net/rastertiles/voyager/{z}/{x}/{y}{r}.png',
    			'options' => array(
    				'attribution' => '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="http://cartodb.com/attributions">CartoDB</a>',
    				'subdomains' => 'abcd',
					'maxZoom' => 19,
    			),
    		),
    		'CartoDB.VoyagerNoLabels' => array(
    			'url' => 'https://cartodb-basemaps-{s}.global.ssl.fastly.net/rastertiles/voyager_nolabels/{z}/{x}/{y}{r}.png',
    			'options' => array(
    				'attribution' => '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="http://cartodb.com/attributions">CartoDB</a>',
    				'subdomains' => 'abcd',
					'maxZoom' => 19,
    			),
    		),
    		'Wikimedia' => array(
    			'url' => 'https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}{r}.png',
    			'options' => array(
    				'attribution' => '<a href="https://wikimediafoundation.org/wiki/Maps_Terms_of_Use">Wikimedia</a>',
    				'minZoom' => 1,
					'maxZoom' => 19,
    			),
    		),
    		'Mapbox.Streets' => array(
    			'url' => 'https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}',
    			'options' => array(
    				'attribution' => 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
    				'maxZoom' => 18,
					'id' => 'mapbox.streets',
					'accessToken' => $sAPIKey,
    			),
    		),
    		'Mapbox.Light' => array(
    			'url' => 'https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}',
    			'options' => array(
    				'attribution' => 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
    				'maxZoom' => 18,
					'id' => 'mapbox.light',
					'accessToken' => $sAPIKey,
    			),
    		),
    		'Mapbox.Dark' => array(
    			'url' => 'https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}',
    			'options' => array(
    				'attribution' => 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
    				'maxZoom' => 18,
					'id' => 'mapbox.dark',
					'accessToken' => $sAPIKey,
    			),
    		),
    		'Thunderforest.OpenCycleMap' => array(
    			'url' => 'https://{s}.tile.thunderforest.com/cycle/{z}/{x}/{y}.png?apikey={apikey}',
    			'options' => array(
    				'attribution' => '&copy; <a href="http://www.thunderforest.com/">Thunderforest</a>, &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    				'maxZoom' => 22,
					'apikey' => $sAPIKey,
    			),
    		),
    		'Thunderforest.SpinalMap' => array(
    			'url' => 'https://{s}.tile.thunderforest.com/spinal-map/{z}/{x}/{y}.png?apikey={apikey}',
    			'options' => array(
    				'attribution' => '&copy; <a href="http://www.thunderforest.com/">Thunderforest</a>, &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    				'maxZoom' => 22,
					'apikey' => $sAPIKey,
    			),
    		),
    		'Thunderforest.Pioneer' => array(
    			'url' => 'https://{s}.tile.thunderforest.com/pioneer/{z}/{x}/{y}.png?apikey={apikey}',
    			'options' => array(
    				'attribution' => '&copy; <a href="http://www.thunderforest.com/">Thunderforest</a>, &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    				'maxZoom' => 22,
					'apikey' => $sAPIKey,
    			),
    		),
    	);

    	if (!isset($aProviders[$sProviderName])) return false;

        $aProviders[$sProviderName]['options']['url'] = $aProviders[$sProviderName]['url'];
        return $aProviders[$sProviderName]['options'];
    }
}

/** @} */
