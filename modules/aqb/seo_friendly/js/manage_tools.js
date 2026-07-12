/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Affiliate System Affiliate System
 * @ingroup     UnaModules
 *
 * @{
 */

function AqbSEOFManageTools (oOptions) {
	this._iSearchTimeoutId = false;
	this._sActionsUrl = oOptions.sActionUrl;
	this._sObjNameGrid = oOptions.sObjNameGrid;
    this._sObjName = oOptions.sObjName === undefined ? 'oAqbSEOFManageTools' : oOptions.sObjName;

    this._sAnimationEffect = oOptions.sAnimationEffect === undefined ? 'fade' : oOptions.sAnimationEffect;
    this._iAnimationSpeed = oOptions.iAnimationSpeed === undefined ? 'slow' : oOptions.iAnimationSpeed;

    this._aHtmlIds = oOptions.aHtmlIds === undefined ? {} : oOptions.aHtmlIds;
    this._oRequestParams = oOptions.oRequestParams === undefined ? {} : oOptions.oRequestParams;
}

AqbSEOFManageTools.prototype.updateReferral = function(oEl, iProfileId) {
	glGrids[this._sObjNameGrid].action('update_referral', {'referral': iProfileId, 'profile' : $("[name='profile']").val()});
};

AqbSEOFManageTools.prototype.removeReferral = function(oEl, iProfileId) {
	bx_loading_btn(oEl, true);
	glGrids[this._sObjNameGrid].action('remove_referral', { 'profile': iProfileId });
};

AqbSEOFManageTools.prototype.onChangeFilter = function(oFilter) {
	const $this = this,
		  sValueSearch = $(`#bx-grid-search-${this._sObjNameGrid}`).val();

	clearTimeout($this._iSearchTimeoutId);
	$this._iSearchTimeoutId = setTimeout(function () {
		glGrids[$this._sObjNameGrid].setFilter(sValueSearch, true);
	}, 500);
};


AqbSEOFManageTools.prototype.manuallyUpdateTnx = function(iTnx){
	glGrids[this._sObjNameGrid].action('process_tnx',{id:iTnx, value: $('[name="tnx"]').val(), status:$('[name="status"]:checked').val()});
};

/** @} */
