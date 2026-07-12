/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Auto Friends Auto Friends
 * @ingroup     UNAModules
 *
 * @{
 */

function AqbAfMain(oOptions) {
    this._sActionsUrl = oOptions.sActionUrl;
    this._sObjName = oOptions.sObjName == undefined ? 'oAqbAfMain' : oOptions.sObjName;
    this._sAnimationEffect = oOptions.sAnimationEffect == undefined ? 'slide' : oOptions.sAnimationEffect;
    this._iAnimationSpeed = oOptions.iAnimationSpeed == undefined ? 'slow' : oOptions.iAnimationSpeed;
    this._aHtmlIds = oOptions.aHtmlIds == undefined ? {} : oOptions.aHtmlIds;

    this._sType = oOptions.sType == undefined ? 'short' : oOptions.sType;
    this._iStart = oOptions.iStart == undefined ? 0 : oOptions.iStart;
    this._iPerPage = oOptions.iPerPage == undefined ? 10 : oOptions.iPerPage;

    var $this = this;
    $(document).ready(function() {
    	$this.initForm();
    });
}

AqbAfMain.prototype.initForm = function() {
    var $this = this;

    if($('#' + this._aHtmlIds['ff_form']).length == 0)
        return;

    $('#' + this._aHtmlIds['ff_profile']).autocomplete({
        source: this._sActionsUrl + 'search_profiles/',
        select: function(oEvent, oUi) {
            $('#' + $this._aHtmlIds['ff_profile_id']).val(oUi.item.value);
            $(this).val(oUi.item.label);

            oEvent.preventDefault();
        }
    });
};

AqbAfMain.prototype.changePage = function(oLink, sType, iStart, iPerPage) {
    this._sType = sType;
    this._iStart = iStart;
    this._iPerPage = iPerPage;
    this.getProfiles(oLink);
};

AqbAfMain.prototype.getProfiles = function(oElement) {
    var $this = this;
    var oDate = new Date();

    this.loadingInBlock(oElement, true);

    oParams = {
        type: this._sType,
        start: this._iStart,
        per_page: this._iPerPage,
        _t: oDate.getTime()
    };

    $.get(
        this._sActionsUrl + 'get_profiles/',
        oParams,
        function(oData){
            $this.loadingInBlock(oElement, false);

            $this.processResult(oData);
        },
        'json'
    );
};

AqbAfMain.prototype.onGetProfiles = function(oData) {
    var $this = this;

    $('#' + this._aHtmlIds['profiles']).bx_anim('hide', this._sAnimationEffect, this._iAnimationSpeed, function() {
        $(this).html(oData.content).bx_anim('show', $this._sAnimationEffect, $this._iAnimationSpeed);
    });
};

AqbAfMain.prototype.processResult = function(oData) {
    var $this = this;

    if(oData && oData.message != undefined && oData.message.length != 0)
        alert(oData.message);

    if(oData && oData.reload != undefined && parseInt(oData.reload) == 1)
    	document.location = document.location;

    if(oData && oData.popup != undefined) {
    	var oPopup = $(oData.popup).hide(); 

    	$('#' + oPopup.attr('id')).remove();
        oPopup.prependTo('body').dolPopup({
            fog: {
                color: '#fff',
                opacity: .7
            },
            closeOnOuterClick: false
        });
    }

    if (oData && oData.eval != undefined)
        eval(oData.eval);
};

AqbAfMain.prototype.loadingInBlock = function(e, bShow) {
    var oParent = $(e).length ? $(e).parents('.bx-db-container:first') : $('body'); 
    bx_loading(oParent, bShow);
};