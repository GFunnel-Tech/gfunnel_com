/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Jobs Jobs
 * @ingroup     ModzzzModules
 *
 * @{
 */

function MzJobsPolls(oOptions)
{
    BxBaseModTextPolls.call(this, oOptions);

    this._sObjName = oOptions.sObjName == undefined ? 'oMzJobsPolls' : oOptions.sObjName;
}

MzJobsPolls.prototype = Object.create(BxBaseModTextPolls.prototype);
MzJobsPolls.prototype.constructor = MzJobsPolls;

/** @} */
