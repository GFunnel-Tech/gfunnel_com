/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Listing Listing
 * @ingroup     ModzzzModules
 *
 * @{
 */

/**
 * Listing hours sub-form
 */
function BxListingHours (options) {

    this.init(options);
    this._eForm = null;
};

BxListingHours.prototype.init = function (options) {

    this._sUniqId = options.uniq_id;

    this._sJsInstanceName = options.js_instance_name;

    this._sResultContainerId = 'bx-form-input-hours-forms-' + this._sUniqId;

    this._sTemplateGhost = options.template_ghost ? options.template_ghost : '<div id="' + this._getHourContainerId('{hour_id}') + '"><input type="hidden" name="f[]" value="{hour_id}" />{hour_name} (<a href="javascript:void(0);" onclick="{js_instance_name}.deleteGhost(this, \'{hour_id}\')">delete</a>)</div>';

    this._sTemplateErrorGhosts = options.template_error ? options.template_error : '<div class="bx-informer-msg-error bx-def-padding-sec bx-def-round-corners bx-def-margin-sec-top">{error}</div>';

    this._iContentId = undefined == options.content_id || '' == options.content_id ? '' : parseInt(options.content_id);
};

BxListingHours.prototype._getUrlWithStandardParams = function () {
    return sUrlRoot + 'modules/?r=listing/hours&uid=' + this._sUniqId;
};

BxListingHours.prototype._getHourContainerId = function (iHourId) {    
	return 'mz-listing-hour-' + iHourId;
};

BxListingHours.prototype.getCurrentHoursCount = function () {
    return $('#' + $this._sResultContainerId + ' .mz-listing-hour-ghost').length;
};

BxListingHours.prototype.addEmptyGhost = function () {
    var oVars = {
        js_instance_name: this._sJsInstanceName,
        file_id: 0,
        hour_id: 0,
        repeat_from_day_of_week: '',
        repeat_from_hour: '',
        repeat_from_minute: '', 
        repeat_from_period: '',
        repeat_to_day_of_week: '',
        repeat_to_hour: '',
        repeat_to_minute: '', 
        repeat_to_period: '',
    }
    var sHTML = this._sTemplateGhost;
    for (var i in oVars)
        sHTML = sHTML.replace (new RegExp('{'+i+'}', 'g'), oVars[i]);

    $('#' + this._sResultContainerId).prepend(sHTML).addWebForms();
};

BxListingHours.prototype.restoreGhosts = function () {
    var sUrl = this._getUrlWithStandardParams() + '&f=json' + '&a=restore&c=' + this._iContentId + '&_t=' + (new Date());
    var $this = this;

    bx_loading(this._sResultContainerId, true);

    $.getJSON(sUrl, function (aData) {

        bx_loading($this._sResultContainerId, false);
        
        if (typeof $this._sTemplateGhost == 'object') {
            $.each($this._sTemplateGhost, function(iHourId, sHTML) {
            	var oHoursContainer = $('#' + $this._getHourContainerId(iHourId));
                if (oHoursContainer.length > 0)
                    return;
                
                if ('object' === typeof(aData) && 'undefined' !== aData[iHourId])
                    sHTML = $this._replaceVars(sHTML, aData[iHourId]);

                $('#' + $this._sResultContainerId).prepend(sHTML).addWebForms();
            });
        }

        if ('object' === typeof(aData)) {

            $.each(aData, function(iHourId, oVars) {
            	var oHoursContainer = $('#' + $this._getHourContainerId(iHourId));
                if (oHoursContainer.length > 0)
                    return;

                var sHTML;
                if (typeof $this._sTemplateGhost == 'object')
                    sHTML = $this._sTemplateGhost[iHourId];
                else
                    sHTML = $this._sTemplateGhost;

                if ("undefined" !== typeof(sHTML)) {
                    sHTML = $this._replaceVars(sHTML, oVars);
                    $('#' + $this._sResultContainerId).prepend(sHTML).addWebForms();
                }
            });
        }
    });
};

BxListingHours.prototype.deleteGhost = function (e, iHourId) {

    if (0 == parseInt(iHourId)) {
        $(e).parents().filter('.mz-listing-hour-ghost').slideUp(function () {
            $(this).remove();
        });
    }
    else {

        var sUrl = this._getUrlWithStandardParams() + '&a=delete&id=' + iHourId;
        var $this = this;

        var sHourContainerId = $this._getHourContainerId(iHourId);
        bx_loading(sHourContainerId, true);

        $.post(sUrl, function (sMsg) {
            bx_loading(sHourContainerId, false);
            if ('ok' == sMsg) {
                $('#' + sHourContainerId).slideUp('slow', function () {
                    $(this).remove();
                });
            } else {
                $('#' + $this._sResultContainerId).prepend($this._sTemplateErrorGhosts.replace('{error}', sMsg));
            }
        });
    }
};

BxListingHours.prototype._replaceVars = function (sHTML, oVars) {

    oVars = $.extend({}, oVars, {js_instance_name: this._sJsInstanceName});

    // replace vars
    for (var i in oVars) 
        sHTML = sHTML.replace (new RegExp('{'+i+'}', 'g'), oVars[i]);

    // set selected values
    var oHTML = $(sHTML);
    for (var i in oVars)
        oHTML.find('select[name="' + i + '[]"] option[value="' + oVars[i] + '"]').attr('selected', 'selected');
    sHTML = $('<div></div>').append(oHTML).html();

    return sHTML;
};

/** @} */
