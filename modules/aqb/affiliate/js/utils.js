/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Affiliate System Affiliate System
 * @ingroup     UnaModules
 *
 * @{
 */

function AqbAffiliateUtils(oOptions) {
    this._sActionsUrl = oOptions.sActionUrl;
    this._sSearchId = oOptions.sSearchId ? oOptions.sSearchId : '#admin-search-result';
    this._iTimeOut = null;
    this._sNoActions = oOptions.sNoActions || 'no actions';
    this._sObjName = oOptions.sObjName === undefined ? 'oAqbAffiliateUtils' : oOptions.sObjName;
    this._bWasUpdated = false;
    this._iUnreadHistoryItems = typeof oOptions.iUnreadHistoryItems !== 'undefined' ? parseInt(oOptions.iUnreadHistoryItems) : 0;
}

AqbAffiliateUtils.prototype.onCloseLegend = function(oEl) {
    $(oEl)
        .closest('#banners_commission')
        .find('div.bx-form-section.bx-form-js-processed')
        .not(oEl)
        .each(function(){
            $(this)
                .addClass('bx-form-collapsed bx-form-section-hidden')
                .find('.bx-form-section-content')
                .slideUp();
        });
};

AqbAffiliateUtils.prototype.onSearch = function(oEl, iExcludeId) {
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

AqbAffiliateUtils.prototype.getProgramForm = function(iId, sType, element) {
    const fLoading = (bExecute = true) => {
        if (element)
            bx_loading_content($(element).closest('.aqb-affiliate-admin-program-item'), bExecute);
    };

    fLoading();
    this._performAction('add_program', {id: iId, type: sType}, () => {
        fLoading(false);
    });
};

AqbAffiliateUtils.prototype.setCommissionsRates = function(iId, oElement) {
    bx_loading_btn(oElement, true);
    this._performAction('set_commissions_rates', {id: iId }, () => {
       bx_loading_btn(oElement, false);
    });
};

AqbAffiliateUtils.prototype.loadAclCommissionFields = function(oElement, bClear = false) {
    const fFunc = (oObject) => {
        if (!oObject.next().hasClass('bx-form-section-wrapper'))
            $('.commissions-fields', oObject.parent()).closest('.bx-form-element-wrapper').remove();
        else
            $('.commissions-fields', oObject.parent()).closest('.bx-form-section.bx-form-section-divider').parent().remove();
    }

    const oObject = $('#bx-form-element-acl_level_id').next();
    if (!$(oElement).val()) {
        return fFunc(oObject);
    }

    bx_loading($('#bx-form-element-acl_level_id').parent(), true);
    this._performAction('get_acl_commissions_fields', { acl_id: $(oElement).val(), program_id: $('[name="program_id"]').val(), clear: +bClear }, ({ code, html, msg}) => {
        if (code && msg)
            bx_alert(msg);
        else if (html) {
            fFunc(oObject);
            $(oObject).after(html);
        }

        bx_loading($('#bx-form-element-acl_level_id').parent(), false);
    });
};

AqbAffiliateUtils.prototype.removeAclCommissionData = function() {
    const oObject = $('[name="acl_level_id"]');
    if (oObject.val())
        bx_confirm(_t('_aqb_affiliate_admin_commission_remove_data_confirm'), () => this.loadAclCommissionFields(oObject, true));
}

AqbAffiliateUtils.prototype.onDataImport = function(iId) {
    const oObject = $(`#aqb_affiliate_members_row_${iId}`),
          _this = this,
          importFunc = (oSendData, fCallback, sSuccess) => {
              $.ajax({
                  url: `${_this._sActionsUrl}import_accounts`,
                  dataType: 'json',
                  cache: false,
                  contentType: false,
                  processData: false,
                  data: oSendData,
                  type: 'post',
                  success: function(oData){
                      const { msg, code } = oData;
                      if (typeof fCallback === 'function')
                          fCallback();

                      if (!code && msg !== undefined && typeof sSuccess === 'function')
                          bx_confirm(msg, sSuccess);

                      if (code && msg !== undefined)
                          bx_alert(msg);
                  },
                  error: function() {
                      _t('_aqb_affiliate_import_file_not_found');
                  }
              });
          };

    if (oObject.length) {
        $(oObject)
            .append(
                $('<input type="file" id="aqb-aff-uploader-data" style="display:none"/>').bind('change', function() {
                    const {files} = this,
                        oFormData = new FormData();

                    if (files.length) {
                        bx_loading(oObject, true);
                        oFormData.append('file', files[0]);
                        oFormData.append('profile_id', iId);
                        importFunc(oFormData, () => {
                            bx_loading(oObject, false);
                        }, () => {
                            oFormData.append('agree', 1);
                            importFunc(oFormData,() => {
                                bx_loading(oObject, false);
                                glGrids['aqb_affiliate_members'].blink(`aqb_affiliate_members_row_${iId}`);
                            });
                        });
                    }
                }));

        $(`#aqb-aff-uploader-data`).click();
    }
};

AqbAffiliateUtils.prototype.changeStatus = function(iId, element) {
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

AqbAffiliateUtils.prototype.getPopupByAction = function(oElement, sAction, oParams = {}) {
    bx_loading_btn(oElement, true);
    this._performAction(sAction, oParams, () => {
        bx_loading_btn(oElement, false);
    });
};

AqbAffiliateUtils.prototype.setMatrixCommission = function(iProgramId, oElement) {
    this._performAction('set_matrix_commission_form', {id: iProgramId});
};

AqbAffiliateUtils.prototype.removeProgram = function(iProgramId) {
    bx_confirm(_t('_Are_you_sure'), () => {
        bx_loading('aqb_affiliate_matrix', true);
            this._performAction('remove_program', {id: iProgramId}, (oData) => {
                bx_loading('aqb_affiliate_matrix', false);
                this.getProgramsPreview();
            })
        }
    );
};

AqbAffiliateUtils.prototype.getProgramsPreview = function() {
    this._performAction('get_programs_preview', {}, ({ html }) => {
        if (html) {
            $(".aqb-affiliate-admin-programs")
                .fadeTo(100, 0.1)
                .fadeTo(200, 1.0)
                .replaceWith(html);
        }
    });
};

AqbAffiliateUtils.prototype.formSubmit = function(sForm, fCallback) {
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

AqbAffiliateUtils.prototype.createProgram = function(sForm) {
    this.formSubmit(sForm, () => {
        if (!$(".aqb-affiliate-admin-programs").length) {
            $('.bx-msg-box-container', $('#aqb_affiliate_matrix')).replaceWith('<div class="aqb-affiliate-admin-programs"></div>');
        }

        this.getProgramsPreview();
    });
};

AqbAffiliateUtils.prototype.copyToClipboard = function(oEl, sId) {
    if ($(sId)) {
        bx_loading_btn(oEl, true);
        $(sId).select();
        document.execCommand('copy');
    }

    setTimeout(() => bx_loading_btn(oEl, false), 1000);
};

AqbAffiliateUtils.prototype.saveMatrixLevels = function(sForm) {
    this.formSubmit(sForm, (oData) => {
        this.getProgramsPreview();
        if (!+oData.code)
        $(sForm)
           .closest('.bx-popup-applied:visible')
           .dolPopupHide({});
    });
};

AqbAffiliateUtils.prototype.viewPrograms = function(sForm) {
    this._performAction('get_programs_info', {});
};

AqbAffiliateUtils.prototype.viewInfo = function(sInfo = 'matrix_rebuild') {
    this._performAction('get_info', { info: sInfo});
};

AqbAffiliateUtils.prototype.rebuildMatrix = function(oEl){
    return bx_confirm(_t('_aqb_affiliate_rebuild_confirm'), () => {
        bx_loading_btn(oEl, true);
        this._performAction('rebuild_matrix', {}, (oData) => {
            bx_loading_btn(oEl, false);
            bx_alert(oData.msg);
        });
    });
};

AqbAffiliateUtils.prototype.viewProfileTree = function(iUserId, el) {
    const oParent = $(el).parent();
    bx_loading(oParent, true);
    this._performAction('get_profile_tree', { user_id: iUserId }, () => bx_loading(oParent, false));
};

AqbAffiliateUtils.prototype.sendInvitations = function(sForm) {
    this.formSubmit(sForm, (oData) => {
        if (!+oData.code)
           $(sForm)
               .closest('.bx-popup-applied:visible')
               .dolPopupHide({removeOnClose: true});
    });
};

AqbAffiliateUtils.prototype.loading = function(oEl, bActive = true) {
    bx_loading_content($(oEl).closest('.aqb-admin-actions-title'), bActive);
};

AqbAffiliateUtils.prototype.toFixed = function(sVal, iDecimal) {
    const aValues = ("" + sValal).split(".");

    if(aValues.length === 1)
        return sVal;

    const iInt = arr[0],
        dec = arr[1],
        max = dec.length - 1;

    return iDecimal === 0 ? iInt : [iInt,".",dec.substr(0, decimals > max ? max : decimals)].join("")
};

AqbAffiliateUtils.prototype.cashOutPerform = function(oEl) {
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

AqbAffiliateUtils.prototype._pointsAction = function(oEl, oParams, sAction) {
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

AqbAffiliateUtils.prototype.showPopupForm = function(sUrl) {
    const oPopupOptions = {closeOnOuterClick: false};

    $('#login_div').remove();
    $('<div id="login_div" style="display:none;"></div>').prependTo('body').load(
        sUrl,
        function() {
            $(this).dolPopup(oPopupOptions);
        }
    );
};

AqbAffiliateUtils.prototype._performAction = function(sAction, oValue, onSuccess, iDelayed = 0) {
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


AqbAffiliateUtils.prototype.hideDesc = function(oEl){
    const oSelect = $(oEl).closest('form').find("[name='desc']");

    if ($(oEl).val() === 'custom' && !$('#bx-form-element-desc').is(':visible'))
        $('#bx-form-element-desc').fadeIn();
    else
        if ($(oEl).val() !== 'custom' && $('#bx-form-element-desc').is(':visible'))
            $('#bx-form-element-desc').fadeOut();
}
