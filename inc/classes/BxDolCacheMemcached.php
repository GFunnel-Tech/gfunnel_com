<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup UnaCore UNA Core
 * @{
 */

class BxDolCacheMemcached extends BxDolCache
{
    protected $iTTL = 3600;
    protected $oMemcached = null;

    /**
     * Constructor
     */
    function __construct()
    {
        parent::__construct();

        if (class_exists('Memcached')) {

            // Persistent connection
            $this->oMemcached = new Memcached('una_cache_pool');

            // Avoid adding servers repeatedly
            if (!count($this->oMemcached->getServerList())) {

                $sHost = getParam('sys_cache_memcache_host');
                $iPort = (int)getParam('sys_cache_memcache_port');

                if (false === strpos($sHost, ',')) {

                    $this->oMemcached->addServer(
                        trim($sHost),
                        $iPort
                    );

                } else {

                    $aHosts = explode(',', $sHost);

                    if ($aHosts) {

                        $this->oMemcached->setOption(
                            Memcached::OPT_DISTRIBUTION,
                            Memcached::DISTRIBUTION_CONSISTENT
                        );

                        $this->oMemcached->setOption(
                            Memcached::OPT_LIBKETAMA_COMPATIBLE,
                            true
                        );

                        foreach ($aHosts as $s) {
                            $this->oMemcached->addServer(
                                trim($s),
                                $iPort
                            );
                        }
                    } else {
                        $this->oMemcached = null;
                    }
                }
            }

            // Verify connection
            if ($this->oMemcached) {
                $stats = $this->oMemcached->getStats();

                if (empty($stats)) {
                    $this->oMemcached = null;
                }
            }
        }
    }

    /**
     * Get data from cache
     */
    function getData($sKey, $iTTL = false)
    {
        $mixedData = $this->oMemcached->get($sKey);

        if ($mixedData === false && $this->oMemcached->getResultCode() === Memcached::RES_NOTFOUND) {
            return null;
        }

        return $mixedData;
    }

    /**
     * Save data to cache
     */
    function setData($sKey, $mixedData, $iTTL = false)
    {
        return $this->oMemcached->set(
            $sKey,
            $mixedData,
            false === $iTTL ? $this->iTTL : $iTTL
        );
    }

    /**
     * Delete cache item
     */
    function delData($sKey)
    {
        $this->oMemcached->delete($sKey);
        return true;
    }

    /**
     * Check if Memcached is available
     */
    function isAvailable()
    {
        return $this->oMemcached !== null;
    }

    /**
     * Check if extension is installed
     */
    function isInstalled()
    {
        return extension_loaded('memcached');
    }

    /**
     * Flush all cache
     * Note: ignores prefix parameter
     */
    function removeAllByPrefix($s)
    {
        return $this->oMemcached->flush();
    }
}

/** @} */