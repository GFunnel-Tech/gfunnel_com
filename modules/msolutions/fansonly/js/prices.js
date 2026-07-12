/**
 * Copyright (c) MSolutions
 * 
 * @defgroup    Fansonly Fansonly module
 * @ingroup     MSolutionsModules
 *
 * @{
 */ 


function MsFansonlyPrices(oOptions) {
    this._sActionsUri = oOptions.sActionUri;
    this._sActionsUrl = oOptions.sActionUrl;
    this._sObjNameGrid = oOptions.sObjNameGrid;
    this._sObjName = oOptions.sObjName == undefined ? 'oMsFansonlyPrices' : oOptions.sObjName;
    this._aHtmlIds = oOptions.aHtmlIds == undefined ? {} : oOptions.aHtmlIds;
    this._oRequestParams = oOptions.oRequestParams == undefined ? {} : oOptions.oRequestParams;
}

MsFansonlyPrices.prototype.onChangeSubscription = function() {
	this.reloadGrid($('#bx-grid-level-' + this._sObjNameGrid).val());
};

MsFansonlyPrices.prototype.reloadGrid = function(iSubscriptionId) {
    glGrids[this._sObjNameGrid].reload(0);
    
    /*
    //    return;
    if(glGrids[this._sObjNameGrid]._oQueryAppend['subscription_id'] == iSubscriptionId)
        return;

    glGrids[this._sObjNameGrid]._oQueryAppend['subscription_id'] = iSubscriptionId;
    glGrids[this._sObjNameGrid].reload(0);
    */
};

/** @} */
