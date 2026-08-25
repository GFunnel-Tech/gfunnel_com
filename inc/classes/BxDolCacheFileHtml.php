<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

class BxDolCacheFileHtml extends BxDolCacheFile
{
    /**
     * Get all data from the cache file.
     *
     * @param  string $sKey - file name
     * @param  int    $iTTL - time to live
     * @return the    data is got from cache.
     */
    function getData($sKey, $iTTL = false)
    {
        if(!file_exists($this->sPath . $sKey))
            return null;

        if ($iTTL > 0 && $this->_removeFileIfTtlExpired ($this->sPath . $sKey, $iTTL))
            return null;

        return file_get_contents($this->sPath . $sKey);
    }

    /**
     * Get full path to cache file
     */
    function getDataFilePath($sKey, $iTTL = false)
    {
        if (!file_exists($this->sPath . $sKey))
            return null;

        if ($iTTL > 0 && $this->_removeFileIfTtlExpired ($this->sPath . $sKey, $iTTL))
            return null;

        return $this->sPath . $sKey;
    }

    /**
     * Save all data in cache file.
     *
     * @param  string  $sKey      - file name
     * @param  mixed   $mixedData - the data to be cached in the file
     * @param  int     $iTTL      - time to live
     * @return boolean result of operation.
     */
    function setData($sKey, $mixedData, $iTTL = false)
    {
        $sFileName = $this->sPath . $sKey;
        $sFileNameTmp = $this->sPath . '.' . uniqid('', true) . '.tmp';

        if(file_exists($sFileName) && !is_writable($sFileName))
           return false;

        if (false === file_put_contents($sFileNameTmp, $mixedData))
            return false;
        rename($sFileNameTmp, $sFileName);
        @chmod($sFileName, 0666);

        if (function_exists('opcache_invalidate')) opcache_invalidate($sFileName, true);

        return true;
    }
}

/** @} */
