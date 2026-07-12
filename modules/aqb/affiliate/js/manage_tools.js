/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Affiliate System Affiliate System
 * @ingroup     UnaModules
 *
 * @{
 */

function AqbAffiliateManageTools (oOptions) {
	this._iSearchTimeoutId = false;
	this._sActionsUrl = oOptions.sActionUrl;
	this._sObjNameGrid = oOptions.sObjNameGrid;
    this._sObjName = oOptions.sObjName === undefined ? 'oAqbAffiliateManageTools' : oOptions.sObjName;

    this._sAnimationEffect = oOptions.sAnimationEffect === undefined ? 'fade' : oOptions.sAnimationEffect;
    this._iAnimationSpeed = oOptions.iAnimationSpeed === undefined ? 'slow' : oOptions.iAnimationSpeed;
    this._sParamsDivider = oOptions.sParamsDivider === undefined ? '#-#' : oOptions.sParamsDivider;

    this._aHtmlIds = oOptions.aHtmlIds === undefined ? {} : oOptions.aHtmlIds;
    this._oRequestParams = oOptions.oRequestParams === undefined ? {} : oOptions.oRequestParams;
}


AqbAffiliateManageTools.prototype.updateReferral = function(oEl, iProfileId) {
	glGrids[this._sObjNameGrid].action('update_referral', {'referral': iProfileId, 'profile' : $("[name='profile']").val()});
};

AqbAffiliateManageTools.prototype.removeReferral = function(oEl, iProfileId) {
	bx_loading_btn(oEl, true);
	glGrids[this._sObjNameGrid].action('remove_referral', { 'profile': iProfileId });
};

AqbAffiliateManageTools.prototype.onChangeFilter = function(oFilter) {
	const $this = this,
			sStartDate = $(`#${this._sObjNameGrid}_start`).val() || '',
			sEndDate = $(`#${this._sObjNameGrid}_end`).val() || '',
			sValueSearch = $(`#${this._sObjNameGrid}-search`).val();

	clearTimeout($this._iSearchTimeoutId);
	$this._iSearchTimeoutId = setTimeout(function () {
		glGrids[$this._sObjNameGrid].setFilter(sValueSearch + $this._sParamsDivider + sStartDate + $this._sParamsDivider + sEndDate, true);
	}, 500);
};


AqbAffiliateManageTools.prototype.manuallyUpdateTnx = function(iTnx){
	glGrids[this._sObjNameGrid].action('process_tnx',{id:iTnx, value: $('[name="tnx"]').val(), status:$('[name="status"]:checked').val()});
};

/** @} */
