<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaBaseView UNA Base Representation Classes
 * @{
 */

/**
 * System services related to Comments.
 */
class BxBaseAuditServices extends BxDol
{
    public function __construct()
    {
        parent::__construct();
    }

    public function serviceManageTools()
    {
        $oGrid = BxDolGrid::getObjectInstance('sys_audit_administration');
        if(!$oGrid)
            return '';

        return [
            'content' =>  $oGrid->getCode()
        ];
    }

    public function serviceGetMenuAddonManageTools()
    {
        return [];
    }

    public function serviceGetMemberships()
    {
        $aLevels = BxDolAcl::getInstance()->getMemberships(false, false, true, false);
        unset($aLevels[MEMBERSHIP_ID_NON_MEMBER]);
        return $aLevels;
    }
}

/** @} */
