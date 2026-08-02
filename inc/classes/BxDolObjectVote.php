<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

class BxDolObjectVote extends BxDolObject
{
    protected $_aVote;

    protected $_aElementDefaults;
    protected $_aElementDefaultsApi;
    protected $_aElementParamsApi; //--- Params from DefaultsApi array to be passed to Api

    protected function __construct($sSystem, $iId, $iInit = true, $oTemplate = false)
    {
        parent::__construct($sSystem, $iId, $iInit, $oTemplate);
        if(empty($this->_sSystem))
            return;

        $this->_aVote = [];
    }

    public static function onAuthorDelete ($iAuthorId)
    {
        $aSystems = self::getSystems();
        foreach($aSystems as $sSystem => $aSystem)
            self::getObjectInstance($sSystem, 0)->getQueryObject()->deleteAuthorEntries($iAuthorId);

        return true;
    }

    public function getObjectAuthorId($iObjectId = 0)
    {
    	if(empty($this->_aSystem['trigger_field_author']))
            return 0;

        return $this->_oQuery->getObjectAuthorId($iObjectId ? $iObjectId : $this->getId());
    }


    /**
     * Interface functions for outer usage
     */
    public function isUndo()
    {
        return (int)$this->_aSystem['is_undo'] == 1;
    }


    /**
     * Permissions functions
     */
    public function isAllowedVote($isPerformAction = false)
    {
        if(isAdmin())
            return true;
        
        if(!$this->checkAction('vote', $isPerformAction))
            return false;

        $aObject = $this->_oQuery->getObjectInfo($this->_iId);
        if(empty($aObject) || !is_array($aObject))
            return false;

        return $this->_isAllowedVoteByObject($aObject);
    }

    public function msgErrAllowedVote()
    {
        $sMsg = $this->checkActionErrorMsg('vote');
        if(empty($sMsg))
            $sMsg = _t('_sys_txt_access_denied');

        return $sMsg;
    }

    public function isAllowedVoteView($isPerformAction = false)
    {
        if(isAdmin())
            return true;

        return $this->checkAction('vote_view', $isPerformAction);
    }
    
    public function msgErrAllowedVoteView()
    {
        return $this->checkActionErrorMsg('vote_view');
    }
    
    public function isAllowedVoteViewVoters($isPerformAction = false)
    {
        if(isAdmin())
            return true;

        return $this->checkAction('vote_view_voters', $isPerformAction);
    }

    public function msgErrAllowedVoteViewVoters()
    {
        return $this->checkActionErrorMsg('vote_view_voters');
    }


    /**
     * Internal functions
     */
    protected function _getVote($iObjectId = 0, $bForceGet = false)
    {
        if(!empty($this->_aVote) && !$bForceGet)
            return $this->_aVote;

        if(empty($iObjectId))
            $iObjectId = $this->getId();

        $this->_aVote = $this->_oQuery->getVote($iObjectId);
        return $this->_aVote;
    }

    protected function _getTrack($iObjectId, $iAuthorId)
    {
        return $this->_oQuery->getTrack($iObjectId, $iAuthorId);
    }
}

/** @} */
