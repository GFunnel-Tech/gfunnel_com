/**
 * GFunnel - customizable left sidebar (application/workspace layout).
 * Talks to gf_sidebar.php and swaps the rail in place after each edit, mirroring
 * the hub-tabs personalization in _page_toolbar_auth_js.html. Also hosts the
 * in-shell iframe loader for custom links whose open mode is "iframe".
 *
 * Functions are global (inline onclick handlers) and idempotent - the script is
 * re-served with each re-render but only ever (re)defines these globals.
 */
(function($) {
    'use strict';

    function endpoint() {
        return window.gfSidebarEndpoint || 'gf_sidebar.php';
    }

    // POST an action, then replace the rail with the returned markup. `reopen`
    // keeps the customize panel open across the swap.
    window.gfSidebarPost = function(sAction, oData, bReopen) {
        var oPost = $.extend({ a: sAction }, oData || {});
        $.post(endpoint(), oPost, function(oResp) {
            if(!oResp || oResp.code !== 0 || !oResp.html)
                return;

            // Replace only the <nav class="gf-sidebar"> node (the returned markup
            // also carries the one-time <link>/<script>, which we intentionally
            // drop so they are not duplicated on every edit).
            var $new = $('<div></div>').html(oResp.html).find('.gf-sidebar').first();
            if(!$new.length)
                return;

            $('.gf-sidebar').first().replaceWith($new);

            if(bReopen)
                gfSidebarPanelOpen();
        }, 'json');
    };

    window.gfSidebarPanelToggle = function() {
        var el = document.getElementById('gf-sb-panel');
        if(el)
            el.style.display = (el.style.display === 'none' || !el.style.display) ? 'block' : 'none';
    };

    window.gfSidebarPanelOpen = function() {
        var el = document.getElementById('gf-sb-panel');
        if(el)
            el.style.display = 'block';
    };

    // Collect the current key order from the customize panel rows.
    function panelKeys() {
        var aKeys = [];
        $('#gf-sb-panel .gf-mrow').each(function() {
            var k = $(this).attr('data-key');
            if(k)
                aKeys.push(k);
        });
        return aKeys;
    }

    window.gfSidebarMove = function(oBtn, iDir) {
        var $row = $(oBtn).closest('.gf-mrow');
        if(iDir < 0) {
            var $prev = $row.prev('.gf-mrow');
            if($prev.length) $row.insertBefore($prev);
        } else {
            var $next = $row.next('.gf-mrow');
            if($next.length) $row.insertAfter($next);
        }
        gfSidebarPost('reorder', { v: JSON.stringify(panelKeys()) }, true);
    };

    window.gfSidebarToggle = function(oBtn) {
        var sKey = $(oBtn).closest('.gf-mrow').attr('data-key');
        if(sKey) gfSidebarPost('toggle', { i: sKey }, true);
    };

    window.gfSidebarDelete = function(oBtn) {
        var sKey = $(oBtn).closest('.gf-mrow').attr('data-key');
        if(sKey) gfSidebarPost('del', { i: sKey }, true);
    };

    window.gfSidebarReset = function() {
        if(window.confirm('Reset this workspace’s navigation to the default?'))
            gfSidebarPost('reset', {}, true);
    };

    /* --- Add-custom-link modal --- */

    window.gfSidebarModalOpen = function() {
        var el = document.getElementById('gf-sb-modal');
        if(el) el.style.display = 'block';
    };

    window.gfSidebarModalClose = function() {
        var el = document.getElementById('gf-sb-modal');
        if(el) el.style.display = 'none';
    };

    window.gfSidebarIconPick = function(oBtn) {
        $('#gf-sb-icons .gf-sb-iconopt').removeClass('gf-sb-iconopt-on');
        $(oBtn).addClass('gf-sb-iconopt-on');
    };

    window.gfSidebarAdd = function() {
        var sTitle = $.trim($('#gf-sb-f-title').val() || '');
        var sUrl = $.trim($('#gf-sb-f-url').val() || '');
        var sMode = $('input[name="gf-sb-mode"]:checked').val() || 'self';
        var sIcon = $('#gf-sb-icons .gf-sb-iconopt-on').attr('data-icon') || 'link';
        var $err = $('#gf-sb-err');

        if(!sTitle || !sUrl) {
            $err.text('Give the link a title and a URL.').show();
            return;
        }
        // http(s) or site-relative only
        if(!/^https?:\/\//i.test(sUrl) && /^[a-z][a-z0-9+.\-]*:/i.test(sUrl)) {
            $err.text('Use an https:// address or a /site path.').show();
            return;
        }
        $err.hide();

        gfSidebarPost('add', { title: sTitle, url: sUrl, icon: sIcon, open_mode: sMode }, true);
        // the rail (incl. this modal) is replaced on success, which closes it
    };

    /* --- In-shell iframe host (open mode: iframe) --- */

    window.gfSidebarFrame = function(sUrl, sTitle) {
        var host = document.getElementById('gf-sb-frame-host');
        if(!host) {
            host = document.createElement('div');
            host.id = 'gf-sb-frame-host';
            host.className = 'gf-sb-frame-host';
            host.innerHTML =
                '<div class="gf-sb-frame-bar">' +
                    '<span class="gf-sb-frame-title"></span>' +
                    '<button type="button" class="gf-sb-frame-close" title="Close" onclick="gfSidebarFrameClose()">&times;</button>' +
                '</div>' +
                '<iframe class="gf-sb-frame" frameborder="0" allow="fullscreen"></iframe>';
            document.body.appendChild(host);
        }
        host.querySelector('.gf-sb-frame-title').textContent = sTitle || '';
        host.querySelector('.gf-sb-frame').setAttribute('src', sUrl);
        host.style.display = 'flex';
        return false; // cancel default navigation
    };

    window.gfSidebarFrameClose = function() {
        var host = document.getElementById('gf-sb-frame-host');
        if(host) {
            host.style.display = 'none';
            var f = host.querySelector('.gf-sb-frame');
            if(f) f.setAttribute('src', 'about:blank');
        }
    };
})(jQuery);
