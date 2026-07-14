/**
 * GFunnel — Time Tracking module (header pill + popup).
 *
 * Boots from window.gfTimerBoot = {url, now, timer, ws, ws_name} rendered by
 * the toolbar (see BxBaseFunctions::getGfToolbar). Everything else is fetched
 * from gf_timer.php as JSON and rendered client-side.
 *
 * Three responsibilities:
 *  1. header pill: live elapsed time, start/stop, opens the popup;
 *  2. activity tracker: while a timer runs, every page records its URL,
 *     title, workspace, clicks and visible seconds - flushed to the server
 *     once a minute and when the page is left;
 *  3. popup: active timer, today summary, recent entries (edit / delete /
 *     manual add), next tasks, and billing settings (default rate/increment
 *     plus per-workspace overrides).
 */

(function($) {
    'use strict';

    var oBoot = window.gfTimerBoot;
    if(!oBoot || !oBoot.url)
        return;

    var sUrl = oBoot.url,
        fSkew = oBoot.now ? Date.now() / 1000 - oBoot.now : 0, // client clock - server clock
        oTimer = oBoot.timer || null,                          // running timer {id, title, date_start, ws, ws_name}
        oState = null,                                         // last full state payload
        sTab = 'entries',
        sView = 'main',
        iDetailEntry = 0,
        iEditEntry = 0,
        bManualOpen = false;

    var SYNC_KEY = 'gf_tt_sync';

    /*=== Helpers ============================================================*/

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function serverNow() {
        return Math.floor(Date.now() / 1000 - fSkew);
    }

    function elapsed() {
        return oTimer ? Math.max(0, serverNow() - oTimer.date_start) : 0;
    }

    function pad(i) {
        return ('0' + i).slice(-2);
    }

    function fmtClock(iSec) {
        return pad(Math.floor(iSec / 3600)) + ':' + pad(Math.floor(iSec / 60) % 60) + ':' + pad(iSec % 60);
    }

    function fmtDur(iSec) {
        if(iSec < 60)
            return iSec + 's';

        var iH = Math.floor(iSec / 3600),
            iM = Math.floor(iSec / 60) % 60,
            iS = iSec % 60,
            a = [];

        if(iH) a.push(iH + 'h');
        if(iM) a.push(iM + 'm');
        if(iS && !iH) a.push(iS + 's');
        return a.join(' ') || '0s';
    }

    function fmtMoney(f) {
        return '$' + (Math.round(f * 100) / 100).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function fmtTime(iUnix) {
        var o = new Date(iUnix * 1000),
            iH = o.getHours(),
            s = iH >= 12 ? 'PM' : 'AM';
        return (iH % 12 || 12) + ':' + pad(o.getMinutes()) + ' ' + s;
    }

    function fmtDay(iUnix) {
        var o = new Date(iUnix * 1000),
            oNow = new Date(),
            oYd = new Date(oNow.getTime() - 86400000),
            aM = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        if(o.toDateString() == oNow.toDateString()) return 'Today';
        if(o.toDateString() == oYd.toDateString()) return 'Yesterday';
        return aM[o.getMonth()] + ' ' + o.getDate();
    }

    function fmtDateInput(iUnix) {
        var o = new Date(iUnix * 1000);
        return o.getFullYear() + '-' + pad(o.getMonth() + 1) + '-' + pad(o.getDate());
    }

    function fmtTimeInput(iUnix) {
        var o = new Date(iUnix * 1000);
        return pad(o.getHours()) + ':' + pad(o.getMinutes());
    }

    /* "1:30" (h:mm), "1:02:30" (h:mm:ss) or "90" (minutes) -> seconds */
    function parseDuration(s) {
        s = $.trim(String(s || ''));
        if(!s)
            return 0;

        var a = s.split(':');
        if(a.length == 3)
            return (parseInt(a[0], 10) || 0) * 3600 + (parseInt(a[1], 10) || 0) * 60 + (parseInt(a[2], 10) || 0);
        if(a.length == 2)
            return (parseInt(a[0], 10) || 0) * 3600 + (parseInt(a[1], 10) || 0) * 60;

        var f = parseFloat(s);
        return isNaN(f) ? 0 : Math.round(f * 60);
    }

    function durInput(iSec) {
        return Math.floor(iSec / 3600) + ':' + pad(Math.floor(iSec / 60) % 60) + ':' + pad(iSec % 60);
    }

    var INCREMENT_LABELS = {1: '1 second', 60: '1 minute', 300: '5 minutes', 360: '6 minutes', 600: '10 minutes', 900: '15 minutes'};

    function post(oData, fnDone) {
        $.post(sUrl, oData, function(oResp) {
            if(!oResp || typeof oResp.code == 'undefined')
                return;

            applyState(oResp);
            if(fnDone)
                fnDone(oResp);
        }, 'json');
    }

    function applyState(oResp) {
        oState = oResp;
        fSkew = Date.now() / 1000 - oResp.now;

        var oOld = oTimer;
        oTimer = oResp.timer || null;

        if((oOld && !oTimer) || (!oOld && oTimer) || (oOld && oTimer && oOld.id != oTimer.id))
            tracker.reset();

        renderPill();
        if(isPopupOpen())
            renderPopup();

        try { localStorage.setItem(SYNC_KEY, String(Date.now())); } catch(e) {}
    }

    /*=== Header pill ========================================================*/

    function renderPill() {
        var e = $('#gf-timer');
        if(!e.length)
            return;

        e.toggleClass('gf-timer-running', !!oTimer).removeClass('gf-timer-paused');
        e.find('.gf-timer-time').text(fmtClock(elapsed()));
        e.find('.gf-timer-label').text(oTimer ? (oTimer.title || 'Tracking') : 'No active timer');
        e.attr('title', oTimer ? 'Tracking — click for details' : 'Time tracking');
    }

    $(document).on('click.gf-tt', '#gf-timer .gf-timer-toggle', function(oEvent) {
        oEvent.preventDefault();
        oEvent.stopPropagation();

        if(oTimer)
            stopTimer();
        else {
            openPopup();
            setTimeout(function() { $('#gf-tt-title').trigger('focus'); }, 60);
        }
    });

    $(document).on('click.gf-tt', '#gf-timer', function(oEvent) {
        if($(oEvent.target).closest('.gf-timer-toggle').length)
            return;

        openPopup();
    });

    setInterval(function() {
        if(oTimer) {
            renderPill();
            if(isPopupOpen() && sView == 'main')
                $('#gf-tt-elapsed').text(fmtClock(elapsed()));
        }
    }, 1000);

    /*=== Activity tracker ===================================================*/

    var tracker = (function() {
        var oPage = null,     // current page record {u,t,w,wn,c,s}
            iLastTick = 0,
            iFlushed = 0;     // seconds already reported for this page

        function startPage() {
            oPage = {
                u: location.pathname + location.search,
                t: document.title || location.pathname,
                w: parseInt(oBoot.ws, 10) || 0,
                wn: oBoot.ws_name || '',
                c: 0,
                s: 0
            };
            iLastTick = Date.now();
            iFlushed = 0;
        }

        function tick() {
            if(!oPage || !oTimer)
                return;

            var iNow = Date.now();
            if(document.visibilityState === 'visible')
                oPage.s += Math.min(15, Math.round((iNow - iLastTick) / 1000));
            iLastTick = iNow;
        }

        function pending() {
            if(!oPage)
                return null;

            tick();
            var iSec = oPage.s - iFlushed;
            if(iSec <= 0 && !oPage.c)
                return null;

            return [{u: oPage.u, t: oPage.t, w: oPage.w, wn: oPage.wn, c: oPage.c, s: Math.max(0, iSec)}];
        }

        function flush() {
            if(!oTimer)
                return;

            var aBuf = pending();
            if(!aBuf)
                return;

            var iClicks = oPage.c, iSec = oPage.s;
            $.post(sUrl, {a: 'activity', v: JSON.stringify(aBuf)}, function() {
                iFlushed = iSec;
                oPage.c = Math.max(0, oPage.c - iClicks);
            }, 'json');
        }

        /* last-chance flush when the page is being left */
        function beacon() {
            if(!oTimer || !navigator.sendBeacon)
                return;

            var aBuf = pending();
            if(!aBuf)
                return;

            var oData = new FormData();
            oData.append('a', 'activity');
            oData.append('v', JSON.stringify(aBuf));
            if(navigator.sendBeacon(sUrl, oData)) {
                iFlushed = oPage.s;
                oPage.c = 0;
            }
        }

        $(document).on('click.gf-tt-tracker', function() {
            if(oPage && oTimer)
                oPage.c++;
        });
        document.addEventListener('visibilitychange', function() {
            if(document.visibilityState === 'hidden') {
                tick();
                beacon();
            }
            else
                iLastTick = Date.now();
        });
        window.addEventListener('pagehide', beacon);

        setInterval(tick, 5000);
        setInterval(flush, 60000);

        if(oTimer)
            startPage();

        return {
            reset: function() {
                oPage = null;
                if(oTimer)
                    startPage();
            },
            drain: function() {
                var aBuf = pending();
                if(oPage) {
                    iFlushed = oPage.s;
                    oPage.c = 0;
                }
                return aBuf;
            }
        };
    })();

    /* another tab started/stopped a timer: refresh */
    $(window).on('storage.gf-tt', function(oEvent) {
        var o = oEvent.originalEvent;
        if(o && o.key == SYNC_KEY)
            $.post(sUrl, {a: 'state'}, function(oResp) {
                if(!oResp)
                    return;

                oState = oResp;
                fSkew = Date.now() / 1000 - oResp.now;
                var oOld = oTimer;
                oTimer = oResp.timer || null;
                if((oOld && !oTimer) || (!oOld && oTimer) || (oOld && oTimer && oOld.id != oTimer.id))
                    tracker.reset();

                renderPill();
                if(isPopupOpen())
                    renderPopup();
            }, 'json');
    });

    /*=== Timer actions ======================================================*/

    function startTimer(sTitle, sDescription, iTask) {
        post({a: 'start', title: sTitle || '', description: sDescription || '', task: iTask || 0});
    }

    function stopTimer() {
        var oData = {a: 'stop'};
        var aBuf = tracker.drain();
        if(aBuf)
            oData.v = JSON.stringify(aBuf);

        // carry any in-popup edits of the running title/notes into the entry
        var eTitle = $('#gf-tt-run-title'), eDesc = $('#gf-tt-run-desc');
        if(eTitle.length)
            oData.title = $.trim(eTitle.val());
        if(eDesc.length)
            oData.description = $.trim(eDesc.val());

        post(oData, function() {
            sTab = 'entries';
            if(isPopupOpen())
                renderPopup();
        });
    }

    /*=== Popup ==============================================================*/

    function isPopupOpen() {
        return $('#gf-tt-overlay').is(':visible');
    }

    function ensurePopup() {
        if($('#gf-tt-overlay').length)
            return;

        $('body').append(
            '<div id="gf-tt-overlay" class="gf-tt-overlay" style="display:none">' +
                '<div class="gf-tt-modal" role="dialog" aria-label="Time Tracking">' +
                    '<div class="gf-tt-head">' +
                        '<div class="gf-tt-head-title">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' +
                            '<span>Time Tracking</span>' +
                        '</div>' +
                        '<div class="gf-tt-head-actions">' +
                            '<button type="button" class="gf-tt-iconbtn gf-tt-settings-btn" title="Billing settings" aria-label="Billing settings">' +
                                '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>' +
                            '</button>' +
                            '<button type="button" class="gf-tt-iconbtn gf-tt-close-btn" title="Close" aria-label="Close">' +
                                '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>' +
                            '</button>' +
                        '</div>' +
                    '</div>' +
                    '<div class="gf-tt-body" id="gf-tt-body"></div>' +
                '</div>' +
            '</div>'
        );

        $('#gf-tt-overlay').on('click', function(oEvent) {
            if(oEvent.target === this)
                closePopup();
        });
        $(document).on('keydown.gf-tt', function(oEvent) {
            if(oEvent.key === 'Escape' && isPopupOpen())
                closePopup();
        });
        $('#gf-tt-overlay').on('click', '.gf-tt-close-btn', closePopup);
        $('#gf-tt-overlay').on('click', '.gf-tt-settings-btn', function() {
            sView = sView == 'settings' ? 'main' : 'settings';
            renderPopup();
        });

        bindPopupHandlers();
    }

    function openPopup() {
        ensurePopup();
        sView = 'main';
        $('#gf-tt-overlay').css('display', 'flex');
        if(oState)
            renderPopup();
        else
            $('#gf-tt-body').html('<div class="gf-tt-loading">Loading&hellip;</div>');

        post({a: 'state'});
    }

    function closePopup() {
        $('#gf-tt-overlay').hide();
        iDetailEntry = 0;
        iEditEntry = 0;
        bManualOpen = false;
        sView = 'main';
    }

    function renderPopup() {
        if(!oState)
            return;

        $('#gf-tt-body').html(sView == 'settings' ? htmlSettings() : htmlMain());
    }

    /*--- Main view ---*/

    function htmlMain() {
        var s = '<aside class="gf-tt-left">' + htmlTimerCard() + htmlSummary() + '</aside>';

        s += '<section class="gf-tt-right">';
        s += '<div class="gf-tt-tabs">' +
            '<button type="button" class="gf-tt-tab' + (sTab == 'entries' ? ' gf-tt-tab-on' : '') + '" data-tab="entries">Recent Entries <span>(' + oState.entries.length + ')</span></button>' +
            '<button type="button" class="gf-tt-tab' + (sTab == 'tasks' ? ' gf-tt-tab-on' : '') + '" data-tab="tasks">My Next Tasks <span>(' + oState.tasks.filter(function(t) { return !t.done; }).length + ')</span></button>' +
        '</div>';
        s += sTab == 'entries' ? htmlEntries() : htmlTasks();
        s += '</section>';

        return s;
    }

    function htmlTimerCard() {
        if(oTimer) {
            return '<div class="gf-tt-timer-card gf-tt-timer-active">' +
                '<div class="gf-tt-live"><span class="gf-tt-live-dot"></span>Tracking</div>' +
                '<div class="gf-tt-elapsed" id="gf-tt-elapsed">' + fmtClock(elapsed()) + '</div>' +
                '<div class="gf-tt-ws-chip" title="Workspace this timer was started in">' + esc(oTimer.ws_name) + '</div>' +
                '<input type="text" id="gf-tt-run-title" class="gf-tt-input" value="' + esc(oTimer.title) + '" placeholder="What are you working on?" />' +
                '<textarea id="gf-tt-run-desc" class="gf-tt-textarea" placeholder="Add a description (optional)&hellip;">' + esc(oTimer.description || '') + '</textarea>' +
                '<div class="gf-tt-timer-actions">' +
                    '<button type="button" class="gf-tt-btn gf-tt-btn-stop gf-tt-stop-btn">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><rect x="5" y="5" width="14" height="14" rx="2"/></svg>' +
                        'Stop timer</button>' +
                    '<button type="button" class="gf-tt-btn-ghost gf-tt-discard-btn" title="Discard without saving">Discard</button>' +
                '</div>' +
            '</div>';
        }

        return '<div class="gf-tt-timer-card">' +
            '<div class="gf-tt-idle-icon">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' +
            '</div>' +
            '<div class="gf-tt-idle-title">No Active Timer</div>' +
            '<div class="gf-tt-idle-hint">Name what you&#39;re working on, or start from a task.</div>' +
            '<input type="text" id="gf-tt-title" class="gf-tt-input" placeholder="What are you working on?" />' +
            '<textarea id="gf-tt-desc" class="gf-tt-textarea" placeholder="Add a description (optional)&hellip;"></textarea>' +
            '<button type="button" class="gf-tt-btn gf-tt-btn-start gf-tt-start-btn">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><polygon points="6 3 20 12 6 21 6 3"/></svg>' +
                'Start timer</button>' +
        '</div>';
    }

    function htmlSummary() {
        var o = oState.today,
            sVs = (o.vs > 0 ? '+' : '') + o.vs + '%';

        return '<div class="gf-tt-summary">' +
            '<div class="gf-tt-summary-head">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>' +
                'Today&#39;s Summary' +
            '</div>' +
            '<div class="gf-tt-total-card">' +
                '<div class="gf-tt-total-label">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' +
                    'Total Today' +
                '</div>' +
                '<div class="gf-tt-total-value">' + fmtDur(o.total) + '</div>' +
                (o.amount > 0 ? '<div class="gf-tt-total-amount">' + fmtMoney(o.amount) + ' billable</div>' : '') +
            '</div>' +
            '<div class="gf-tt-tiles">' +
                '<div class="gf-tt-tile">' +
                    '<div class="gf-tt-tile-label">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>' +
                        'Tasks</div>' +
                    '<div class="gf-tt-tile-value">' + o.tasks + '</div>' +
                    '<div class="gf-tt-tile-sub">completed</div>' +
                '</div>' +
                '<div class="gf-tt-tile">' +
                    '<div class="gf-tt-tile-label">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M7 16h8"/><path d="M7 11h12"/><path d="M7 6h3"/></svg>' +
                        'vs Last Week</div>' +
                    '<div class="gf-tt-tile-value">' + sVs + '</div>' +
                    '<div class="gf-tt-tile-sub">same period</div>' +
                '</div>' +
            '</div>' +
        '</div>';
    }

    /*--- Entries tab ---*/

    function htmlEntries() {
        var s = '<div class="gf-tt-panel">';

        s += '<div class="gf-tt-panel-bar">' +
            '<button type="button" class="gf-tt-add-manual-btn gf-tt-linkbtn">' + (bManualOpen ? '&minus; Close' : '+ Add time manually') + '</button>' +
        '</div>';

        if(bManualOpen)
            s += htmlEntryForm(null);

        if(!oState.entries.length && !bManualOpen)
            s += '<div class="gf-tt-empty">' +
                '<div class="gf-tt-empty-icon">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' +
                '</div>' +
                '<div class="gf-tt-empty-title">No entries yet</div>' +
                '<div class="gf-tt-empty-hint">Start the timer or add time manually and your<br/>work will be logged here.</div>' +
            '</div>';

        var sDay = '';
        $.each(oState.entries, function(i, o) {
            var sThisDay = fmtDay(o.date_start);
            if(sThisDay != sDay) {
                sDay = sThisDay;
                s += '<div class="gf-tt-day">' + esc(sDay) + '</div>';
            }
            s += iEditEntry == o.id ? htmlEntryForm(o) : htmlEntryRow(o);
        });

        return s + '</div>';
    }

    function htmlEntryRow(o) {
        var iEnd = o.date_start + o.duration,
            iPages = o.activity.length,
            bDetail = iDetailEntry == o.id;

        var s = '<div class="gf-tt-entry' + (bDetail ? ' gf-tt-entry-open' : '') + '" data-id="' + o.id + '">';
        s += '<div class="gf-tt-entry-row gf-tt-entry-main">' +
            '<div class="gf-tt-entry-info">' +
                '<div class="gf-tt-entry-title">' + esc(o.title) + (o.manual ? '<span class="gf-tt-badge">manual</span>' : '') + '</div>' +
                '<div class="gf-tt-entry-meta">' + esc(o.ws_name) + ' &middot; ' + fmtTime(o.date_start) + ' &ndash; ' + fmtTime(iEnd) +
                    (iPages ? ' &middot; ' + iPages + ' page' + (iPages == 1 ? '' : 's') + ' visited' : '') +
                '</div>' +
            '</div>' +
            '<div class="gf-tt-entry-side">' +
                '<span class="gf-tt-entry-dur">' + fmtDur(o.duration) + '</span>' +
                (o.amount > 0 ? '<span class="gf-tt-entry-amount">' + fmtMoney(o.amount) + '</span>' : '') +
                '<span class="gf-tt-entry-actions">' +
                    '<button type="button" class="gf-tt-iconbtn gf-tt-entry-edit" title="Edit entry"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg></button>' +
                    '<button type="button" class="gf-tt-iconbtn gf-tt-entry-del" title="Delete entry"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg></button>' +
                '</span>' +
            '</div>' +
        '</div>';

        if(bDetail) {
            s += '<div class="gf-tt-entry-detail">';

            if(o.description)
                s += '<div class="gf-tt-detail-desc">' + esc(o.description) + '</div>';

            if(o.segments.length) {
                s += '<div class="gf-tt-detail-sec">Billing by workspace</div>';
                $.each(o.segments, function(i, oSeg) {
                    s += '<div class="gf-tt-detail-row">' +
                        '<span>' + esc(oSeg.ws_name) + '</span>' +
                        '<span class="gf-tt-detail-dim">' + fmtDur(oSeg.seconds) +
                            (oSeg.billable != oSeg.seconds ? ' &rarr; ' + fmtDur(oSeg.billable) + ' billed' : '') +
                            (oSeg.rate > 0 ? ' @ ' + fmtMoney(oSeg.rate) + '/h' : '') + '</span>' +
                        '<span class="gf-tt-detail-amount">' + (oSeg.amount > 0 ? fmtMoney(oSeg.amount) : '&mdash;') + '</span>' +
                    '</div>';
                });
            }

            if(o.billable < o.duration && o.billable > 0)
                s += '<div class="gf-tt-detail-note">Overlapping time with other entries is excluded from billing.</div>';

            if(o.activity.length) {
                s += '<div class="gf-tt-detail-sec">Activity</div>';
                $.each(o.activity.slice(0, 12), function(i, oAct) {
                    s += '<div class="gf-tt-detail-row">' +
                        '<span class="gf-tt-detail-page" title="' + esc(oAct.u) + '">' + esc(oAct.t || oAct.u) + '</span>' +
                        '<span class="gf-tt-detail-dim">' + fmtDur(oAct.s) + ' &middot; ' + oAct.c + ' click' + (oAct.c == 1 ? '' : 's') + '</span>' +
                    '</div>';
                });
                if(o.activity.length > 12)
                    s += '<div class="gf-tt-detail-note">+ ' + (o.activity.length - 12) + ' more pages</div>';
            }

            s += '</div>';
        }

        return s + '</div>';
    }

    /* shared create/edit form; o = null for the manual-add form */
    function htmlEntryForm(o) {
        var iStart = o ? o.date_start : Math.floor(Date.now() / 1000) - 3600,
            sDur = o ? durInput(o.duration) : '1:00:00';

        var sWsOptions = '';
        $.each(oState.workspaces, function(i, oWs) {
            var iSel = o ? o.ws : oState.ws;
            sWsOptions += '<option value="' + oWs.id + '"' + (oWs.id == iSel ? ' selected' : '') + '>' + esc(oWs.name) + '</option>';
        });

        return '<div class="gf-tt-entry-form" data-id="' + (o ? o.id : 0) + '">' +
            '<input type="text" class="gf-tt-input gf-tt-f-title" placeholder="What was this time for?" value="' + esc(o ? o.title : '') + '" />' +
            '<textarea class="gf-tt-textarea gf-tt-f-desc" placeholder="Description (optional)&hellip;">' + esc(o ? o.description || '' : '') + '</textarea>' +
            '<div class="gf-tt-form-grid">' +
                '<label>Date<input type="date" class="gf-tt-input gf-tt-f-date" value="' + fmtDateInput(iStart) + '" /></label>' +
                '<label>Start<input type="time" class="gf-tt-input gf-tt-f-time" value="' + fmtTimeInput(iStart) + '" /></label>' +
                '<label>Duration<input type="text" class="gf-tt-input gf-tt-f-dur" value="' + sDur + '" placeholder="h:mm or minutes" /></label>' +
                '<label>Workspace<select class="gf-tt-input gf-tt-f-ws">' + sWsOptions + '</select></label>' +
            '</div>' +
            '<div class="gf-tt-form-actions">' +
                '<button type="button" class="gf-tt-btn gf-tt-btn-start gf-tt-entry-save">' + (o ? 'Save changes' : 'Add entry') + '</button>' +
                '<button type="button" class="gf-tt-btn-ghost gf-tt-entry-cancel">Cancel</button>' +
            '</div>' +
        '</div>';
    }

    /*--- Tasks tab ---*/

    function htmlTasks() {
        var s = '<div class="gf-tt-panel">';

        s += '<div class="gf-tt-task-add">' +
            '<input type="text" id="gf-tt-task-title" class="gf-tt-input" placeholder="Add a task you&#39;re going to work on&hellip;" />' +
            '<button type="button" class="gf-tt-btn gf-tt-btn-start gf-tt-task-add-btn">+</button>' +
        '</div>';

        if(!oState.tasks.length)
            s += '<div class="gf-tt-empty">' +
                '<div class="gf-tt-empty-icon">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="6" height="6" rx="1"/><path d="m3.5 17.5 2 2 3.5-3.5"/><path d="M13 6h8"/><path d="M13 12h8"/><path d="M13 18h8"/></svg>' +
                '</div>' +
                '<div class="gf-tt-empty-title">No tasks yet</div>' +
                '<div class="gf-tt-empty-hint">File a task with the + button and it&#39;ll show up<br/>here, ready to track.</div>' +
            '</div>';

        $.each(oState.tasks, function(i, o) {
            s += '<div class="gf-tt-task' + (o.done ? ' gf-tt-task-done' : '') + '" data-id="' + o.id + '">' +
                '<button type="button" class="gf-tt-task-check" title="' + (o.done ? 'Mark as not done' : 'Mark as done') + '">' +
                    (o.done
                        ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>'
                        : '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>') +
                '</button>' +
                '<span class="gf-tt-task-title">' + esc(o.title) + '</span>' +
                '<span class="gf-tt-task-actions">' +
                    (!o.done ? '<button type="button" class="gf-tt-iconbtn gf-tt-task-start" title="Start timer from this task"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="6 3 20 12 6 21 6 3"/></svg></button>' : '') +
                    '<button type="button" class="gf-tt-iconbtn gf-tt-task-del" title="Delete task"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg></button>' +
                '</span>' +
            '</div>';
        });

        return s + '</div>';
    }

    /*--- Settings view ---*/

    function htmlIncrementSelect(sClass, iValue) {
        var s = '<select class="gf-tt-input ' + sClass + '">';
        $.each(oState.increments, function(i, iInc) {
            s += '<option value="' + iInc + '"' + (iInc == iValue ? ' selected' : '') + '>' + INCREMENT_LABELS[iInc] + '</option>';
        });
        return s + '</select>';
    }

    function htmlSettings() {
        var o = oState.settings;

        var s = '<div class="gf-tt-settings">';
        s += '<div class="gf-tt-settings-title">Billing settings</div>';
        s += '<div class="gf-tt-settings-hint">Set your hourly rate and how tracked time is rounded when it&#39;s billed. ' +
            'The default applies everywhere; add a workspace override when a workspace bills differently.</div>';

        s += '<div class="gf-tt-set-card">' +
            '<div class="gf-tt-set-row-head">Default (all workspaces)</div>' +
            '<div class="gf-tt-set-grid">' +
                '<label>Hourly rate ($/h)<input type="number" min="0" step="0.01" class="gf-tt-input gf-tt-set-def-rate" value="' + o['default'].rate + '" /></label>' +
                '<label>Billing increment' + htmlIncrementSelect('gf-tt-set-def-inc', o['default'].increment) + '</label>' +
            '</div>' +
        '</div>';

        s += '<div class="gf-tt-set-row-head gf-tt-set-ws-head">Workspace overrides</div>';
        $.each(oState.workspaces, function(i, oWs) {
            if(!oWs.id)
                return;

            var oOverride = o.overrides[oWs.id],
                bCustom = !!oOverride,
                oValue = oOverride || o['default'];

            s += '<div class="gf-tt-set-card gf-tt-set-ws" data-ws="' + oWs.id + '">' +
                '<div class="gf-tt-set-ws-row">' +
                    '<span class="gf-tt-set-ws-name">' + esc(oWs.name) + '</span>' +
                    '<label class="gf-tt-set-custom"><input type="checkbox" class="gf-tt-set-ws-on"' + (bCustom ? ' checked' : '') + ' /> Custom billing</label>' +
                '</div>' +
                '<div class="gf-tt-set-grid gf-tt-set-ws-fields' + (bCustom ? '' : ' gf-tt-set-off') + '">' +
                    '<label>Hourly rate ($/h)<input type="number" min="0" step="0.01" class="gf-tt-input gf-tt-set-ws-rate" value="' + oValue.rate + '"' + (bCustom ? '' : ' disabled') + ' /></label>' +
                    '<label>Billing increment' + htmlIncrementSelect('gf-tt-set-ws-inc' + (bCustom ? '' : ' gf-tt-disabled'), oValue.increment) + '</label>' +
                '</div>' +
            '</div>';
        });

        s += '<div class="gf-tt-form-actions">' +
            '<button type="button" class="gf-tt-btn gf-tt-btn-start gf-tt-set-save">Save settings</button>' +
            '<button type="button" class="gf-tt-btn-ghost gf-tt-set-back">Back</button>' +
        '</div>';

        return s + '</div>';
    }

    /*=== Popup event handlers (delegated once) ==============================*/

    function bindPopupHandlers() {
        var eOverlay = $('#gf-tt-overlay');

        eOverlay.on('click', '.gf-tt-tab', function() {
            sTab = $(this).data('tab');
            iDetailEntry = 0;
            iEditEntry = 0;
            renderPopup();
        });

        //--- timer
        eOverlay.on('click', '.gf-tt-start-btn', function() {
            startTimer($.trim($('#gf-tt-title').val()), $.trim($('#gf-tt-desc').val()));
        });
        eOverlay.on('keydown', '#gf-tt-title', function(oEvent) {
            if(oEvent.key === 'Enter')
                startTimer($.trim($('#gf-tt-title').val()), $.trim($('#gf-tt-desc').val()));
        });
        eOverlay.on('click', '.gf-tt-stop-btn', stopTimer);
        eOverlay.on('click', '.gf-tt-discard-btn', function() {
            if(confirm('Discard this timer without saving an entry?'))
                post({a: 'discard'});
        });
        eOverlay.on('change blur', '#gf-tt-run-title, #gf-tt-run-desc', function() {
            if(!oTimer)
                return;

            oTimer.title = $.trim($('#gf-tt-run-title').val()) || oTimer.title;
            oTimer.description = $.trim($('#gf-tt-run-desc').val());
            renderPill();
            $.post(sUrl, {a: 'update', title: oTimer.title, description: oTimer.description}, null, 'json');
        });

        //--- entries
        eOverlay.on('click', '.gf-tt-add-manual-btn', function() {
            bManualOpen = !bManualOpen;
            iEditEntry = 0;
            renderPopup();
        });
        eOverlay.on('click', '.gf-tt-entry-main', function(oEvent) {
            if($(oEvent.target).closest('.gf-tt-entry-actions').length)
                return;

            var iId = parseInt($(this).closest('.gf-tt-entry').data('id'), 10);
            iDetailEntry = iDetailEntry == iId ? 0 : iId;
            renderPopup();
        });
        eOverlay.on('click', '.gf-tt-entry-edit', function() {
            iEditEntry = parseInt($(this).closest('.gf-tt-entry').data('id'), 10);
            iDetailEntry = 0;
            bManualOpen = false;
            renderPopup();
        });
        eOverlay.on('click', '.gf-tt-entry-del', function() {
            var iId = parseInt($(this).closest('.gf-tt-entry').data('id'), 10);
            if(confirm('Delete this time entry?'))
                post({a: 'entry_del', id: iId});
        });
        eOverlay.on('click', '.gf-tt-entry-cancel', function() {
            iEditEntry = 0;
            bManualOpen = false;
            renderPopup();
        });
        eOverlay.on('click', '.gf-tt-entry-save', function() {
            var eForm = $(this).closest('.gf-tt-entry-form'),
                iId = parseInt(eForm.data('id'), 10),
                iDuration = parseDuration(eForm.find('.gf-tt-f-dur').val());

            if(!$.trim(eForm.find('.gf-tt-f-title').val())) {
                eForm.find('.gf-tt-f-title').trigger('focus');
                return;
            }
            if(iDuration <= 0) {
                eForm.find('.gf-tt-f-dur').trigger('focus');
                return;
            }

            post({
                a: iId ? 'entry_save' : 'entry_add',
                id: iId || 0,
                title: $.trim(eForm.find('.gf-tt-f-title').val()),
                description: $.trim(eForm.find('.gf-tt-f-desc').val()),
                date: eForm.find('.gf-tt-f-date').val(),
                time: eForm.find('.gf-tt-f-time').val(),
                duration: iDuration,
                ws: eForm.find('.gf-tt-f-ws').val()
            }, function() {
                iEditEntry = 0;
                bManualOpen = false;
                renderPopup();
            });
        });

        //--- tasks
        var fnTaskAdd = function() {
            var sTitle = $.trim($('#gf-tt-task-title').val());
            if(!sTitle)
                return;

            post({a: 'task_add', title: sTitle});
        };
        eOverlay.on('click', '.gf-tt-task-add-btn', fnTaskAdd);
        eOverlay.on('keydown', '#gf-tt-task-title', function(oEvent) {
            if(oEvent.key === 'Enter')
                fnTaskAdd();
        });
        eOverlay.on('click', '.gf-tt-task-check', function() {
            post({a: 'task_toggle', id: $(this).closest('.gf-tt-task').data('id')});
        });
        eOverlay.on('click', '.gf-tt-task-del', function() {
            post({a: 'task_del', id: $(this).closest('.gf-tt-task').data('id')});
        });
        eOverlay.on('click', '.gf-tt-task-start', function() {
            post({a: 'start', task: $(this).closest('.gf-tt-task').data('id'), title: '', description: ''});
        });

        //--- settings
        eOverlay.on('change', '.gf-tt-set-ws-on', function() {
            var eCard = $(this).closest('.gf-tt-set-ws'),
                bOn = $(this).is(':checked');

            eCard.find('.gf-tt-set-ws-fields').toggleClass('gf-tt-set-off', !bOn);
            eCard.find('.gf-tt-set-ws-rate').prop('disabled', !bOn);
            eCard.find('.gf-tt-set-ws-inc').toggleClass('gf-tt-disabled', !bOn);
        });
        eOverlay.on('click', '.gf-tt-set-back', function() {
            sView = 'main';
            renderPopup();
        });
        eOverlay.on('click', '.gf-tt-set-save', function() {
            var oData = {
                'default': {
                    rate: parseFloat($('.gf-tt-set-def-rate').val()) || 0,
                    increment: parseInt($('.gf-tt-set-def-inc').val(), 10) || 1
                },
                overrides: {}
            };

            $('.gf-tt-set-ws').each(function() {
                var eCard = $(this),
                    iWs = parseInt(eCard.data('ws'), 10);

                if(eCard.find('.gf-tt-set-ws-on').is(':checked'))
                    oData.overrides[iWs] = {
                        rate: parseFloat(eCard.find('.gf-tt-set-ws-rate').val()) || 0,
                        increment: parseInt(eCard.find('.gf-tt-set-ws-inc').val(), 10) || 1
                    };
                else
                    oData.overrides[iWs] = null;
            });

            post({a: 'settings_save', v: JSON.stringify(oData)}, function() {
                sView = 'main';
                renderPopup();
            });
        });
    }

    /*=== Boot ===============================================================*/

    $(function() {
        renderPill();
    });

})(jQuery);
