/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Affiliate System Affiliate System
 * @ingroup     UnaModules
 *
 * @{
 */

function AqbSEOFUtils(oOptions) {
    this._sActionsUrl = oOptions.sActionUrl;
    this._sSearchId = oOptions.sSearchId ? oOptions.sSearchId : '#admin-search-result';
    this._iTimeOut = null;
    this._sNoActions = oOptions.sNoActions || 'no actions';
    this._sObjName = oOptions.sObjName === undefined ? 'oAqbSEOFUtils' : oOptions.sObjName;
    this._iMode = 0;
    this._sModule = '';
}

AqbSEOFUtils.prototype.onUpdateUriItem = function(iId) {
    const oObject = $(`#aqb_seof_links_row_${iId}`),
        _this = this;

    $.post(
        `${_this._sActionsUrl}update_item`,
        { uri: $(`[name="rec_${iId}"]`).val(), id: iId },
        function(oData){
            glGrids['aqb_seof_urls'].action('display', { mode: this._iMode });
            glGrids['aqb_seof_links'].action('get', { id: iId });
            processJsonData(oData);
        },
        'json'
    );
};

AqbSEOFUtils.prototype.onUpdateUri = function(iId) {
    const _this = this;
    //glGrids[$this._sObjNameGrid].setFilter(sValueSearch, true);
    $.post(
        `${_this._sActionsUrl}update_uri`,
        { uri: $(`[name="uri"]`).val(), id: iId },
        function(oData){
            glGrids['aqb_seof_urls'].action('display', { mode: this._iMode });
            if (+oData.code)
                $('.bx-popup-applied:visible').dolPopupHide({});

            processJsonData(oData);
        },
        'json'
    );
};

AqbSEOFUtils.prototype.switchMode = function(oElement) {
    this._iMode = +$(oElement).is(':checked');
    glGrids['aqb_seof_urls']._oQueryAppend['mode'] = this._iMode;
    glGrids['aqb_seof_urls'].action('display');
};

AqbSEOFUtils.prototype.switchModule = function(oElement) {
    this._sModule = $(oElement).val();
    glGrids['aqb_seof_urls']._oQueryAppend['module'] = this._sModule;
    glGrids['aqb_seof_urls'].action('display');
};


AqbSEOFUtils.prototype.onSearch = function(oEl, iExcludeId) {
    const _this = this;

    bx_loading_content(_this._sSearchId, true);
    clearTimeout(this._iTimeOut);

    this._performAction('search_profile', {term:$(oEl).val(), exclude:iExcludeId},
        (oDate) => {
        bx_loading_content(_this._sSearchId, false);

        if (~oDate.code)
            $(_this._sSearchId).html(oDate.html);

    }, 1500);
};

AqbSEOFUtils.prototype.getProgramForm = function(iId, sType, element) {
    const fLoading = (bExecute = true) => {
        if (element)
            bx_loading_content($(element).closest('.aqb-affiliate-admin-program-item'), bExecute);
    };

    fLoading();
    this._performAction('add_program', {id: iId, type: sType}, () => {
        fLoading(false);
    });
};

AqbSEOFUtils.prototype.changeStatus = function(iId, element) {
    const oProgram = $(element).closest('.aqb-affiliate-admin-program-item');
    if (!oProgram)
        return;

    bx_loading(oProgram, true);
    this._performAction('update_program_status', { id: iId }, (oData) => {
        bx_loading(oProgram, false);
        if (oData.html)
            $(oProgram).closest('.aqb-affiliate-admin-programs').replaceWith(oData.html);
    });
};

AqbSEOFUtils.prototype.getPopupByAction = function(oElement, sAction, oParams = {}) {
    bx_loading_btn(oElement, true);
    this._performAction(sAction, oParams, () => {
        bx_loading_btn(oElement, false);
    });
};

AqbSEOFUtils.prototype.removeProgram = function(iProgramId) {
    bx_confirm(_t('_Are_you_sure'), () => {
        bx_loading('aqb_affiliate_matrix', true);
            this._performAction('remove_program', {id: iProgramId}, (oData) => {
                bx_loading('aqb_affiliate_matrix', false);
                this.getProgramsPreview();
            })
        }
    );
};

AqbSEOFUtils.prototype.getProgramsPreview = function() {
    this._performAction('get_programs_preview', {}, ({ html }) => {
        if (html) {
            $(".aqb-affiliate-admin-programs")
                .fadeTo(100, 0.1)
                .fadeTo(200, 1.0)
                .replaceWith(html);
        }
    });
};

AqbSEOFUtils.prototype.formSubmit = function(sForm, fCallback) {
    const $this = this;
    $(sForm).ajaxForm({
        dataType: "json",
        beforeSubmit: function (formData, jqForm, options) {
            bx_loading($(sForm), true);
        },
        success: function (oData) {
            //$(sForm).closest(".bx-popup-applied:visible").dolPopupHide({removeOnClose: true});
            if (typeof fCallback === 'function')
                fCallback(oData);

            processJsonData(oData);
            bx_loading($(sForm), false);
        }
    });

    $(sForm).submit();
};

AqbSEOFUtils.prototype.createProgram = function(sForm) {
    this.formSubmit(sForm, () => {
        if (!$(".aqb-affiliate-admin-programs").length) {
            $('.bx-msg-box-container', $('#aqb_affiliate_matrix')).replaceWith('<div class="aqb-affiliate-admin-programs"></div>');
        }

        this.getProgramsPreview();
    });
};

AqbSEOFUtils.prototype.copyToClipboard = function(oEl, sId) {
    if ($(sId)) {
        bx_loading_btn(oEl, true);
        $(sId).select();
        document.execCommand('copy');
    }

    setTimeout(() => bx_loading_btn(oEl, false), 1000);
};

AqbSEOFUtils.prototype.saveMatrixLevels = function(sForm) {
    this.formSubmit(sForm, (oData) => {
        this.getProgramsPreview();
        if (!+oData.code)
        $(sForm)
           .closest('.bx-popup-applied:visible')
           .dolPopupHide({});
    });
};

AqbSEOFUtils.prototype.viewPrograms = function(sForm) {
    this._performAction('get_programs_info', {});
};

AqbSEOFUtils.prototype.viewInfo = function(sInfo = 'matrix_rebuild') {
    this._performAction('get_info', { info: sInfo});
};

AqbSEOFUtils.prototype.rebuildMatrix = function(oEl){
    return bx_confirm(_t('_aqb_affiliate_rebuild_confirm'), () => {
        bx_loading_btn(oEl, true);
        this._performAction('rebuild_matrix', {}, (oData) => {
            bx_loading_btn(oEl, false);
            bx_alert(oData.msg);
        });
    });
};

AqbSEOFUtils.prototype.viewProfileTree = function(iUserId, el) {
    const oParent = $(el).parent();
    bx_loading(oParent, true);
    this._performAction('get_profile_tree', { user_id: iUserId }, () => bx_loading(oParent, false));
};

AqbSEOFUtils.prototype.sendInvitations = function(sForm) {
    this.formSubmit(sForm, (oData) => {
        if (!+oData.code)
           $(sForm)
               .closest('.bx-popup-applied:visible')
               .dolPopupHide({removeOnClose: true});
    });
};

AqbSEOFUtils.prototype.loading = function(oEl, bActive = true) {
    bx_loading_content($(oEl).closest('.aqb-admin-actions-title'), bActive);
};

AqbSEOFUtils.prototype.toFixed = function(sVal, iDecimal) {
    const aValues = ("" + sValal).split(".");

    if(aValues.length === 1)
        return sVal;

    const iInt = arr[0],
        dec = arr[1],
        max = dec.length - 1;

    return iDecimal === 0 ? iInt : [iInt,".",dec.substr(0, decimals > max ? max : decimals)].join("")
};

AqbSEOFUtils.prototype.cashOutPerform = function(oEl) {
    const oParams = {};
    let bExist = false;
    $(oEl)
        .closest('#cash-out')
        .find('input[type="text"]')
        .each(function(){
            if (+$(this).val()) {
                oParams[$(this).prop('name')] = +$(this).val();
                bExist = true;
            }
         });

    if (bExist)
        return bx_confirm(_t('_aqb_affiliate_cash_out_confirm'),
        () => this._pointsAction(oEl, oParams, 'perform_cash_out'));
};

AqbSEOFUtils.prototype._pointsAction = function(oEl, oParams, sAction) {
    const oParent = $(oEl).closest('.bx-form-section-content');
    bx_loading_content(oParent, true);
    this._performAction(sAction, oParams, (oData) => {
        bx_loading_content(oParent, true);
        if (!parseInt(oData.code))
            $(oEl)
                .closest('.bx-popup-applied:visible')
                .dolPopupHide()
    });
};

AqbSEOFUtils.prototype.showPopupForm = function(sUrl) {
    const oPopupOptions = {closeOnOuterClick: false};

    $('#login_div').remove();
    $('<div id="login_div" style="display:none;"></div>').prependTo('body').load(
        sUrl,
        function() {
            $(this).dolPopup(oPopupOptions);
        }
    );
};

AqbSEOFUtils.prototype._performAction = function(sAction, oValue, onSuccess, iDelayed = 0) {
    const oDate = new Date(),
          oParams = {_t: oDate.getTime()},
          fExecute = () =>  $.post(
                                    `${this._sActionsUrl}${sAction}`,
                                    Object.assign(oParams, oValue),
                                    function(oData){
                                           if (typeof onSuccess === 'function')
                                                onSuccess(oData);

                                            processJsonData(oData);
                                    },
                                    'json'
                                );

    if (iDelayed)
        this._iTimeOut = setTimeout(fExecute, iDelayed);
    else
        fExecute();
};