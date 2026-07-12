/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Goal Goal
 * @ingroup     ModzzzModules
 *
 * @{
 */

function MzGoalManageTools(oOptions)
{
    BxBaseModTextManageTools.call(this, oOptions);

    this._sObjName = oOptions.sObjName == undefined ? 'oMzGoalManageTools' : oOptions.sObjName;
}

MzGoalManageTools.prototype = Object.create(BxBaseModTextManageTools.prototype);
MzGoalManageTools.prototype.constructor = MzGoalManageTools;

/** @} */
