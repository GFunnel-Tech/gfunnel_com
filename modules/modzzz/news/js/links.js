/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    News News
 * @ingroup    ModzzzModules
 *
 * @{
 */

function MzNewsLinks(oOptions)
{
    BxBaseModTextLinks.call(this, oOptions);

    this._sObjName = oOptions.sObjName == undefined ? 'oMzNewsLinks' : oOptions.sObjName;
}

MzNewsLinks.prototype = Object.create(BxBaseModTextLinks.prototype);
MzNewsLinks.prototype.constructor = MzNewsLinks;

/** @} */
