<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

if (!defined('BX_DOL_SESSION_IDLE_TIMEOUT'))
    define('BX_DOL_SESSION_IDLE_TIMEOUT', 2*24*60*60);
if (!defined('BX_DOL_SESSION_IDLE_TIMEOUT_GUESTS'))
    define('BX_DOL_SESSION_IDLE_TIMEOUT_GUESTS', 4*60*60);
if (!defined('BX_DOL_SESSION_SKIP_UPDATE'))
    define('BX_DOL_SESSION_SKIP_UPDATE', 30);
define('BX_DOL_SESSION_COOKIE', 'memberSession');

class BxDolSession extends BxDolFactory implements iBxDolSingleton
{
    protected $oDb;
    protected $sId;
    protected $iUserId;
    protected $aData;
    protected $iTTL;

    protected $bAutoLogout;	//--- Auto logout with broken session

    protected function __construct()
    {
        if (isset($GLOBALS['bxDolClasses'][get_class($this)]))
            trigger_error ('Multiple instances are not allowed for the class: ' . get_class($this), E_USER_ERROR);

        parent::__construct();

        $this->oDb = new BxDolSessionQuery();
        $this->sId = '';
        $this->iUserId = 0;
        $this->aData = array();
        $this->iTTL = 0;

        $this->bAutoLogout = false; // when changing to 'true', it maybe you will need to increase BX_DOL_SESSION_IDLE_TIMEOUT
        if (defined('BX_SESSION_AUTO_LOGOUT'))
            $this->bAutoLogout = BX_SESSION_AUTO_LOGOUT;
    }

    /**
     * Prevent cloning the instance
     */
    public function __clone()
    {
        if (isset($GLOBALS['bxDolClasses'][get_class($this)]))
            trigger_error('Clone is not allowed for the class: ' . get_class($this), E_USER_ERROR);
    }

    /**
     * Get singleton instance of the class
     */
    public static function getInstance()
    {
        if(!isset($GLOBALS['bxDolClasses'][__CLASS__]))
            $GLOBALS['bxDolClasses'][__CLASS__] = new BxDolSession();

        return $GLOBALS['bxDolClasses'][__CLASS__];
    }

    public static function getSessionCookie()
    {
        if (isset($_COOKIE[BX_DOL_SESSION_COOKIE]))
            return bx_process_input($_COOKIE[BX_DOL_SESSION_COOKIE]);
        return null;
    }
    
    function start($bForceStart = false)
    {
        if (defined('BX_DOL_CRON_EXECUTE') || defined('BX_MANIFEST') || defined('BX_SERVICE_WORKER'))
            return true;

        if (!$bForceStart && $this->getSessionCookie() === null)
            return true;
        
        if (getParam('sys_session_auth')) {
            $this->exists($this->sId);
        }
        else {
            if ($this->exists($this->sId)) {
                if ($this->iUserId == getLoggedId())
                    return true;
                $this->destroy(false);
            }
        }

		/**
		 * Force logout a logged in user if his session wasn't found and required to be automatically recreated.
		 * It's needed to avoid problems when different users are logged in at the same time under one account. 
		 */
		if($this->bAutoLogout && isLogged())
			bx_logout();

		// try to restore user's old session
		if (!getParam('sys_session_auth') && isLogged() && defined('BX_DOL_SESSION_RESTORATION') && constant('BX_DOL_SESSION_RESTORATION')) {
		    $this->sId = $this->oDb->getOldSession(getLoggedId());
		    if ($this->sId)
		        $this->exists($this->sId); // it exists for sure but required for initializing some data there
        }

		// if an old session has not been found then generate a new one
		if (!$this->sId)
            $this->sId = genRndPwd(32, true);

        if (!isset($_COOKIE[BX_DOL_SESSION_COOKIE]) || $_COOKIE[BX_DOL_SESSION_COOKIE] != $this->sId || $this->isValue('remember_me')) {
            $iTime = 0;
            if (getParam('sys_session_auth') && $this->isValue('remember_me') && $this->getValue('remember_me'))
                $iTime = time() + getParam('sys_session_lifetime_in_min') * 60;

            bx_setcookie(BX_DOL_SESSION_COOKIE, $this->sId, $iTime, 'auto', '', 'auto', true);
            $this->iTTL = $iTime;
            unset($this->aData['remember_me']);
        }

        $this->save();


        return true;
    }

    function destroy($bDeleteCookies = true)
    {
        if(empty($this->sId))
            $this->start();

        if(empty($this->sId))
            return;

        if ($bDeleteCookies) {
            bx_setcookie(BX_DOL_SESSION_COOKIE, '', time() - 86400, 'auto', '', 'auto', true);
            unset($_COOKIE[BX_DOL_SESSION_COOKIE]);
        }

        $this->oDb->delete($this->sId);

        $this->sId = '';
        $this->iUserId = 0;
        $this->iTTL = 0;
        $this->aData = array();
    }

    function exists($sId = '')
    {        
        if(empty($sId))
            $sId = $this->getSessionCookie();

        $mixedSession = $this->oDb->exists($sId);
        if($mixedSession === false) 
        	return false;

		$this->sId = $mixedSession['id'];
		$this->iUserId = (int)$mixedSession['user_id'];
        $this->iTTL = isset($mixedSession['ttl']) ? (int)$mixedSession['ttl'] : 0;
		$this->aData = unserialize($mixedSession['data']);

		$this->oDb->update($this->sId);		//--- update session's time
		return true;
    }

    function getId()
    {
        return $this->sId;
    }

    function setUserId($iUserId)
    {
        if(empty($this->sId))
            $this->start(true);

        if(empty($this->sId))
            return;

        if (0 == $iUserId && $this->iUserId) { // update last active field when user is logged out
            $this->oDb->updateLastActivityAccount($this->iUserId, time());
        }
        $this->iUserId = $iUserId;
        $this->save();
    }

    function getUserId()
    {
        if(empty($this->sId))
            $this->start();

        return $this->iUserId;
    }

    function setTTL($iTTL)
    {
        if(empty($this->sId))
            $this->start();

        if(empty($this->sId))
            return;

        $this->iTTL = $iTTL;
        $this->save();
    }
    
    function getTTL()
    {
        if(empty($this->sId))
            $this->start();

        return $this->iTTL;
    }

    function setValue($sKey, $mixedValue)
    {
        if(empty($this->sId))
            $this->start(true);

        $this->aData[$sKey] = $mixedValue;
        $this->save();
    }

    function unsetValue($sKey)
    {
        if(empty($this->sId))
            $this->start(true);

        unset($this->aData[$sKey]);

        $this->save();
    }

	function isValue($sKey)
    {
        if(empty($this->sId))
            $this->start();

        return isset($this->aData[$sKey]);
    }

    function getValue($sKey)
    {
        if(empty($this->sId))
            $this->start();

        return isset($this->aData[$sKey]) ? $this->aData[$sKey] : false;
    }

    function getUnsetValue($sKey)
    {
        $mixedValue = $this->getValue($sKey);
        if($mixedValue !== false)
            $this->unsetValue($sKey);

        return $mixedValue;
    }

    public function maintenance()
    {
        return $this->oDb->deleteExpired();
    }

    protected function save()
    {
        if($this->iUserId == 0)
            $this->iUserId = getLoggedId();

        $this->oDb->save($this->sId, array(
            'user_id' => $this->iUserId,
            'ttl' => $this->iTTL,
            'data' => serialize($this->aData)
        ));
    }
}

/** @} */
