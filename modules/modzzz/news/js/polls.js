/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    News News
 * @ingroup    ModzzzModules
 *
 * @{
 */

function MzNewsPolls(oOptions)
{
    BxBaseModTextPolls.call(this, oOptions);

    this._sObjName = oOptions.sObjName == undefined ? 'oMzNewsPolls' : oOptions.sObjName;
}

MzNewsPolls.prototype = Object.create(BxBaseModTextPolls.prototype);
MzNewsPolls.prototype.constructor = MzNewsPolls;

/** @} */
