/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Goal Goal
 * @ingroup     ModzzzModules
 *
 * @{
 */

function MzGoalEntry(oOptions) {
    this._sActionsUri = oOptions.sActionUri;
    this._sActionsUrl = oOptions.sActionUrl;
    this._sObjName = oOptions.sObjName == undefined ? 'oMzGoalEntry' : oOptions.sObjName;
    this._sAnimationEffect = oOptions.sAnimationEffect == undefined ? 'slide' : oOptions.sAnimationEffect;
    this._iAnimationSpeed = oOptions.iAnimationSpeed == undefined ? 'slow' : oOptions.iAnimationSpeed;
    this._aHtmlIds = oOptions.aHtmlIds == undefined ? {} : oOptions.aHtmlIds;
 
    this._oRequestParams = oOptions.oRequestParams == undefined ? {} : oOptions.oRequestParams;
}

MzGoalEntry.prototype.interested = function(oElement, iContentId) {
    this._performAction(oElement, 'interested', iContentId);
};
 
MzGoalEntry.prototype.notinterested = function(oElement, iContentId) {
    this._performAction(oElement, 'notinterested', iContentId);
};

MzGoalEntry.prototype.achieved = function(oElement, iContentId) {
    this._performAction(oElement, 'achieved', iContentId);
};

MzGoalEntry.prototype.complete = function(oElement, iContentId) {
    this._performAction(oElement, 'complete', iContentId);
};

MzGoalEntry.prototype.fail = function(oElement, iContentId) {
    this._performAction(oElement, 'fail', iContentId);
};
 
MzGoalEntry.prototype.onChangeCategory = function(oElement) {
    var $this = this;
    var oParams = this._getDefaultData();
    oParams['category'] = $(oElement).val();

    this.loadingInBlock(oElement, true);

    jQuery.get (
        this._sActionsUrl + 'get_category_form',
        oParams,
        function(oData) {
            if(oElement)
                $this.loadingInBlock(oElement, false);

            if(!oData || !oData.content && oData.content.length == 0) 
                return;

            var oContent = $(oData.content);
            var sFormId = oContent.filter('form').attr('id');
            if(!sFormId)
                return;

            $('form#' + sFormId).replaceWith(oContent);
        },
        'json'
    );
};

MzGoalEntry.prototype.onChangeGetSubCategories = function(oElement) {
	 
    var $this = this;
    var oParams = this._getDefaultData();
    oParams['category'] = $(oElement).val();
 
    this.loadingInBlock(oElement, true);
 
    jQuery.get (
        this._sActionsUrl + 'get_subcategory_options',
        oParams,
        function(oData) {
            if(oElement)
                $this.loadingInBlock(oElement, false);

            if(!oData || !oData.content && oData.content.length == 0) 
                return;

            var oContent = $(oData.content);
            //var sFormId = oContent.filter('form').attr('id');
            //if(!sFormId)
            //    return;

			$("#mz_goal_subcategory").html(oContent);

            //$('form#' + sFormId).replaceWith(oContent);
        },
        'json'
    );
};
 
MzGoalEntry.prototype.loadingInButton = function(e, bShow) {
    if($(e).length)
        bx_loading_btn($(e), bShow);
    else
        bx_loading($('body'), bShow);	
};

MzGoalEntry.prototype.loadingInBox = function(e, bShow) {
    var oParent = $(e).length ? $(e).parents('.bx-base-text-poll:first') : $('body'); 
    bx_loading(oParent, bShow);
};

MzGoalEntry.prototype.loadingInBlock = function(e, bShow) {
    var oParent = $(e).length ? $(e).parents('.bx-db-container:first') : $('body'); 
    bx_loading(oParent, bShow);
};

MzGoalEntry.prototype._getDefaultData = function() {
    var oDate = new Date();
    return jQuery.extend({}, this._oRequestParams, {_t:oDate.getTime()});
};
 
MzGoalEntry.prototype._performAction = function(oElement, sAction, iContentId, sContentIdKey) {
    var $this = this;
    var oParams = this._getDefaultData();
    oParams[sContentIdKey ? sContentIdKey : 'id'] = iContentId;

    if(oElement)
        this.loadingInButton(oElement, true);

    jQuery.get (
        this._sActionsUrl + sAction,
        oParams,
        function(oData) {
            if(oElement)
                $this.loadingInButton(oElement, false);

            processJsonData(oData);
        },
        'json'
    );
};
  
/** @} */
