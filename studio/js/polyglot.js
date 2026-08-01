/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaStudio UNA Studio
 * @{
 */
function BxDolStudioPolyglot(oOptions) {
    this.sActionsUrl = oOptions.sActionUrl;
    this.sObjName = oOptions.sObjName == undefined ? 'oBxDolStudioPolyglot' : oOptions.sObjName;
    this.sAnimationEffect = oOptions.sAnimationEffect == undefined ? 'fade' : oOptions.sAnimationEffect;
    this.iAnimationSpeed = oOptions.iAnimationSpeed == undefined ? 'slow' : oOptions.iAnimationSpeed;
    this.sCodeMirror = oOptions.sCodeMirror == undefined ? '' : oOptions.sCodeMirror;
    this.sPage = oOptions.sPage == undefined ? 'general' : oOptions.sPage;
}

BxDolStudioPolyglot.prototype.enableEditor = function(oSource) {
    var oDate = new Date();

    $.post(
        this.sActionsUrl, {
            pgt_action: 'enable-editor',
            pgt_value: $(oSource).prop('checked') ? 1 : 0,
            _t:oDate.getTime()
        },
        function(oData) {
            processJsonData(oData);
        },
        'json'
    );

    return true;
};

/**
 * Is needed if AJAX is used to change (reload) pages. 
 */
BxDolStudioPolyglot.prototype.changePage = function(sType, iStart, iLength) {
    var oDate = new Date();
    var $this = this;
    var oParams = {
        pgt_action: 'get-page-by-type',
        pgt_value: sType,
        _t:oDate.getTime()
    };

    if(sType == 'keys') {
        oParams.pgt_category = $('#pgt-keys-category').val();
        oParams.pgt_language = $('#pgt-keys-language').val();
        oParams.pgt_keyword = $('#pgt-keys-keyword').val();
        if(iStart)
            oParams.pgt_start = iStart;
        if(iLength)
            oParams.pgt_length = iLength;
    }

    $.get(
        this.sActionsUrl,
        oParams,
        function(oData) {
            if(oData.code != 0) {
                bx_alert(oData.message);
                return true;
            }

            $('#bx-std-pc-menu > .bx-std-pmi-active').removeClass('bx-std-pmi-active');
            $('#bx-std-pmi-' + sType).addClass('bx-std-pmi-active');

            $('#bx-std-pc-content').bx_anim('hide', $this.sAnimationEffect, $this.iAnimationSpeed, function() {
                $(this).html(oData.content).bx_anim('show', $this.sAnimationEffect, $this.iAnimationSpeed);
            });
        },
        'json'
    );

    return true;
};
/** @} */
