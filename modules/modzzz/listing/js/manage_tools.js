/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Listing Listing
 * @ingroup     ModzzzModules
 *
 * @{
 */

function MzListingManageTools(oOptions)
{
    BxBaseModTextManageTools.call(this, oOptions);

    this._sObjName = oOptions.sObjName == undefined ? 'oMzListingManageTools' : oOptions.sObjName;
}

MzListingManageTools.prototype = Object.create(BxBaseModTextManageTools.prototype);
MzListingManageTools.prototype.constructor = MzListingManageTools;

/** @} */
