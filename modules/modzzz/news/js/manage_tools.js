/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    News News
 * @ingroup    ModzzzModules
 *
 * @{
 */
 
function MzNewsManageTools(oOptions)
{
    BxBaseModTextManageTools.call(this, oOptions);

    this._sObjName = oOptions.sObjName == undefined ? 'oMzNewsManageTools' : oOptions.sObjName;
}

MzNewsManageTools.prototype = Object.create(BxBaseModTextManageTools.prototype);
MzNewsManageTools.prototype.constructor = MzNewsManageTools;






/** @} */
