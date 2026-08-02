<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

class BxDolCacheRedis extends BxDolCache
{
    protected $iTTL = 3600;
    protected $oRedis = null;

    /**
     * constructor
     */
    function __construct()
    {
        parent::__construct();
        
        $s = getParam('sys_cache_redis_connection_string');
        if (class_exists('Redis') && false !== ($a = parse_url($s))) {
            $this->oRedis = new Redis();
            $sHost = $a['host'] ?? 'localhost';
            $iPort = isset($a['port']) && (int)$a['port'] != 0 ? (int)$a['port'] : 6379;
            $iDatabase = $a['path'] ? (int)trim($a['path'], '/') : 0;
            $sPassword = $a['user'] ?? '';

            try {
                if (!$this->oRedis->connect($sHost, $iPort)) {
                    $this->oRedis = null;
                } else {
                    if ($sPassword && !$this->oRedis->auth($sPassword)) {
                        $this->oRedis = null;
                    } elseif (!$this->oRedis->select($iDatabase)) {
                        $this->oRedis = null;
                    }
                }
            } catch (Exception $e) {
                $this->oRedis = null;
            }

            if ($this->oRedis != null) {
                $serializer = (
                    defined('Redis::SERIALIZER_IGBINARY') &&
                    extension_loaded('igbinary')
                ) ? Redis::SERIALIZER_IGBINARY : Redis::SERIALIZER_PHP;                
                $this->oRedis->setOption(Redis::OPT_SERIALIZER, $serializer);
                //echoDbgLog("Serializer:" . $this->oRedis->getOption(Redis::OPT_SERIALIZER));
            }
        }
    }

    /**
     * Get data from cache server
     *
     * @param  string $sKey - cache key
     * @param  int    $iTTL - time to live
     * @return the    data is got from cache.
     */
    function getData($sKey, $iTTL = false)
    {
        if (!$this->oRedis) {
            return null;
        }
        
        $mixedData = $this->oRedis->get($sKey);
        return false === $mixedData ? null : $mixedData;
    }

    /**
     * Save data in cache server
     *
     * @param  string  $sKey      - cache key
     * @param  mixed   $mixedData - the data to be cached
     * @param  int     $iTTL      - time to live
     * @return boolean result of operation.
     */
    function setData($sKey, $mixedData, $iTTL = false)
    {
        if (!$this->oRedis) {
            return false;
        }
        
        $iExpire = false === $iTTL ? $this->iTTL : $iTTL;
        if ($iExpire) {
            return $this->oRedis->setex($sKey, $iExpire, $mixedData);
        } else {
            return $this->oRedis->set($sKey, $mixedData);
        }
    }

    /**
     * Delete cache from cache server
     *
     * @param  string $sKey - cache key
     * @return result of the operation
     */
    function delData($sKey)
    {
        if (!$this->oRedis) {
            return false;
        }
        
        $this->oRedis->del($sKey);
        return true;
    }

    /**
     * Remove all keys by prefix
     *
     * @param  string $sPrefix - key prefix
     * @return boolean result of operation.
     */
    function removeAllByPrefix($sPrefix)
    {
        if (!$this->oRedis) {
            return false;
        }
        
        $aKeys = $this->oRedis->keys($sPrefix . '*');
        if ($aKeys && count($aKeys) > 0) {
            return $this->oRedis->del($aKeys) > 0;
        }
        
        return true;
    }

    /**
     * Get size of all keys by prefix
     *
     * @param  string $sPrefix - key prefix
     * @return int size in bytes or 0 if no keys found.
     */
    function getSizeByPrefix($sPrefix)
    {
        if (!$this->oRedis) {
            return 0;
        }
        
        $aKeys = $this->oRedis->keys($sPrefix . '*');
        if (!$aKeys) {
            return 0;
        }

        $iSize = 0;
        foreach ($aKeys as $sKey) {
            $iSize += strlen($this->oRedis->rawCommand('MEMORY', 'USAGE', $sKey));
        }
        
        return $iSize;
    }

    /**
     * Check if Redis is available
     * @return boolean
     */
    function isAvailable()
    {
        return $this->oRedis == null ? false : true;
    }

    /**
     * Check if Redis extension is loaded
     * @return boolean
     */
    function isInstalled()
    {
        return extension_loaded('redis');
    }
}

/** @} */
