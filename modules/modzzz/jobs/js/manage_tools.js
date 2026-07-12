/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Jobs Jobs
 * @ingroup     ModzzzModules
 *
 * @{
 */

function MzJobsManageTools(oOptions)
{
    BxBaseModTextManageTools.call(this, oOptions);

    this._sObjName = oOptions.sObjName == undefined ? 'oMzJobsManageTools' : oOptions.sObjName;
}

MzJobsManageTools.prototype = Object.create(BxBaseModTextManageTools.prototype);
MzJobsManageTools.prototype.constructor = MzJobsManageTools;

/** @} */
