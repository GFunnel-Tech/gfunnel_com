/**
 *     copyright            : (C) 2018 AQB Soft
 *     website              : http://www.aqbsoft.com
 *
 * IMPORTANT: This is a commercial product made by AQB Soft. It cannot be modified for other than personal usage.
 * The "personal usage" means the product can be installed and set up for ONE domain name ONLY.
 * To be able to use this product for another domain names you have to order another copy of this product (license).
 *
 * This product cannot be redistributed for free or a fee without written permission from AQB Soft.
 *
 * This notice may not be removed from the source code.
 *
 * @defgroup    Personal Bookmarks Personal Bookmarks
 * @ingroup     TridentModules
 *
 * @{
 */


function aqb_personal_bookmarks_add() {
	var sName = prompt(_t('_aqb_personal_bookmarks_name'), document.title);
	if (!sName) return;

	var oDate = new Date();

    bx_loading('aqb_extruder_viewport', true);

    $.post(sUrlRoot + 'modules/?r=aqb_personal_bookmarks/add_bookmark/', {url: window.location.href, name: sName, t:oDate.getTime()}, function(sResponse) {
        bx_loading('aqb_extruder_viewport', false);
    	$('#aqb_extruder_links').html(sResponse);
    }, 'html');
}

function aqb_personal_bookmarks_add_link() {
	var sUrl = prompt(_t('_aqb_personal_bookmarks_add_link_url'), 'https://');
	if (sUrl === null) return;
	sUrl = sUrl.replace(/^\s+|\s+$/g, '');
	if (!sUrl || sUrl == 'https://' || sUrl == 'http://') return;

	var sName = prompt(_t('_aqb_personal_bookmarks_name'), sUrl);
	if (!sName) return;

	var oDate = new Date();

    bx_loading('aqb_extruder_viewport', true);

    $.post(sUrlRoot + 'modules/?r=aqb_personal_bookmarks/add_bookmark/', {url: sUrl, name: sName, t:oDate.getTime()}, function(sResponse) {
        bx_loading('aqb_extruder_viewport', false);
    	$('#aqb_extruder_links').html(sResponse);
    }, 'html');
}

function aqb_personal_bookmarks_delete(id) {
	if (!confirm(_t('_Are_you_sure'))) return;

	var oDate = new Date();

    bx_loading('aqb_extruder_viewport', true);

    $.post(sUrlRoot + 'modules/?r=aqb_personal_bookmarks/delete_bookmark/', {id: id, t:oDate.getTime()}, function(sResponse) {
    	$('#aqb_extruder_links').html(sResponse);
    	bx_loading('aqb_extruder_viewport', false);
    }, 'html');
}

function aqb_personal_bookmarks_open_link(sLink, bExternal) {
    $('#aqb_extruder').closeMbExtruder();
    if (bExternal)
        window.open(sLink, '_blank');
    else
        window.location.href = sLink;
}


function aqb_sortable_init() {
    $("#aqb_extruder_links").sortable({
        distance: 10,
        axis: 'y',
        placeholder: "aqb_sortable_placeholder",
        start: function(event, ui) {
            $('div#aqb_extruder_viewport').css('overflow-y', 'hidden');
        },
        stop: function(event, ui) {
            $('div#aqb_extruder_viewport').css('overflow-y', 'auto');
        },
        update: function(event, ui) {
            var aOrder = new Array();
            $('#aqb_extruder_links > div.voice').each(function(i, oItem){
                aOrder.push($(oItem).attr('bookmark_id'));
            })
            $.post(sUrlRoot + 'modules/?r=aqb_personal_bookmarks/update_order/', {order: aOrder});
        }
    });
    $("#aqb_extruder_links").disableSelection();
}

function aqb_personal_bookmarks_set_cookie(name,value,days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "")  + expires + "; path=/";
}

function aqb_personal_bookmarks_get_cookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

/** @} */
