/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Goal Goal
 * @ingroup     ModzzzModules
 *
 * @{
 */

function MzGoalPolls(oOptions)
{
    BxBaseModTextPolls.call(this, oOptions);

    this._sObjName = oOptions.sObjName == undefined ? 'oMzGoalPolls' : oOptions.sObjName;
}

MzGoalPolls.prototype = Object.create(BxBaseModTextPolls.prototype);
MzGoalPolls.prototype.constructor = MzGoalPolls;

/** @} */
