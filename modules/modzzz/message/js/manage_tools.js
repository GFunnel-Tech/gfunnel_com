/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Message Message
 * @ingroup    ModzzzModules
 *
 * @{
 */
 
function MzMessageManageTools(oOptions)
{
    BxBaseModTextManageTools.call(this, oOptions);

    this._sObjName = oOptions.sObjName == undefined ? 'oMzMessageManageTools' : oOptions.sObjName;
}

MzMessageManageTools.prototype = Object.create(BxBaseModTextManageTools.prototype);
MzMessageManageTools.prototype.constructor = MzMessageManageTools;
 

/** @} */
