/**
 * Copyright (c) MSolutions
 * 
 * @defgroup    Fansonly Fansonly module
 * @ingroup     MSolutionsModules
 *
 * @{
 */ 

function MsFansonlyMain(oOptions) {
    this._sActionsUrl = oOptions.sActionUrl;
    this._sObjName = oOptions.sObjName == undefined ? 'oMsFansonlyMain' : oOptions.sObjName;
    this._sObjNameGrid = oOptions.sObjNameGrid == undefined ? '' : oOptions.sObjNameGrid;
    
    this._sAnimationEffect = oOptions.sAnimationEffect == undefined ? 'fade' : oOptions.sAnimationEffect;
    this._iAnimationSpeed = oOptions.iAnimationSpeed == undefined ? 'slow' : oOptions.iAnimationSpeed;

    this._aHtmlIds = oOptions.aHtmlIds == undefined ? {} : oOptions.aHtmlIds;
}

MsFansonlyMain.prototype.onClickSetSubscription = function(iFanProfileId, iSubscription) {
    $('.bx-popup-applied:visible').dolPopupHide();

    if(glGrids[this._sObjNameGrid] != undefined)
        glGrids[this._sObjNameGrid].actionWithId(iFanProfileId, 'set_subscription_submit', {subscription: iSubscription}, '', false, false);
};

MsFansonlyMain.prototype.onClickSetSubscriptionMulti = function(oElement, iFanProfileId, iSubscription) {
    if(!glGrids[this._sObjNameGrid])
        return;

    var oSiblings = $(oElement).parents('.bx-base-group-sr-subscription:first').siblings();
    if(iSubscription == 0)
        oSiblings.find('input:checked').removeAttr('checked');
    else
        oSiblings.find("input[value = '0']:checked").removeAttr('checked');

    var sSubscriptions = $(oElement).parents('.bx-base-group-sr-subscriptions:first').find('input:checked').serialize();
    glGrids[this._sObjNameGrid].actionWithId(iFanProfileId, 'set_subscription_submit', {}, sSubscriptions, false, false);
};

/** @} */
