/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Listing Listing
 * @ingroup     ModzzzModules
 *
 * @{
 */

function MzListingPolls(oOptions)
{
    BxBaseModTextPolls.call(this, oOptions);

    this._sObjName = oOptions.sObjName == undefined ? 'oMzListingPolls' : oOptions.sObjName;
}

MzListingPolls.prototype = Object.create(BxBaseModTextPolls.prototype);
MzListingPolls.prototype.constructor = MzListingPolls;

/** @} */
