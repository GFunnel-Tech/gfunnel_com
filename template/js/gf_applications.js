/*
 * GFunnel — Application Hub interactions (gf_applications.php).
 * Vanilla JS, no dependencies. Progressive enhancement: the page is fully
 * usable (server-rendered) without it.
 *
 *  - Welcome hero: cross-fading banner rotation + clickable dots, live date.
 *  - App Directory: instant client-side search filter.
 *  - "Add to My Apps": localStorage-backed toggle with added state.
 *  - Video Chat: lightweight popover (Google Meet + saved custom link).
 */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    var LS_APPS = 'gfa_my_apps';
    var LS_MEET = 'gfa_meet_url';

    function readSet(key) {
        try { var a = JSON.parse(localStorage.getItem(key) || '[]'); return new Set(Array.isArray(a) ? a : []); }
        catch (e) { return new Set(); }
    }
    function writeSet(key, set) {
        try { localStorage.setItem(key, JSON.stringify(Array.prototype.slice.call(set))); } catch (e) {}
    }

    ready(function () {
        var root = document.getElementById('gfa');
        if (!root) return;

        // Config: window.GFA (standalone) or data-attrs on #gfa (module block).
        var GFA = window.GFA;
        if (!GFA) GFA = { logged: root.getAttribute('data-gfa-logged') === '1', endpoint: root.getAttribute('data-gfa-endpoint') || '' };

        /* ---- Block mode: client-side Apps/Marketplace tab toggle --- */
        var tabBtns = [].slice.call(root.querySelectorAll('.gfa-tab[data-gfa-tab]'));
        var panels = [].slice.call(root.querySelectorAll('[data-gfa-panel]'));
        if (tabBtns.length && panels.length) {
            tabBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var which = btn.getAttribute('data-gfa-tab');
                    tabBtns.forEach(function (b) { b.classList.toggle('gfa-on', b === btn); });
                    panels.forEach(function (p) { p.classList.toggle('gfa-on', p.getAttribute('data-gfa-panel') === which); });
                });
            });
        }

        /* ---- Welcome hero: rotating banners + dots ------------------ */
        var hero = document.getElementById('gfa-hero');
        if (hero) {
            var slides = [].slice.call(hero.querySelectorAll('.gfa-hero-slide'));
            var dots = [].slice.call(hero.querySelectorAll('.gfa-hero-dot'));
            var idx = 0, timer = null;
            function show(n) {
                idx = (n + slides.length) % slides.length;
                slides.forEach(function (s, i) { s.classList.toggle('gfa-on', i === idx); });
                dots.forEach(function (d, i) { d.classList.toggle('gfa-on', i === idx); });
            }
            function start() { if (slides.length > 1) timer = setInterval(function () { show(idx + 1); }, 5000); }
            function stop() { if (timer) { clearInterval(timer); timer = null; } }
            dots.forEach(function (d, i) { d.addEventListener('click', function () { stop(); show(i); start(); }); });
            hero.addEventListener('mouseenter', stop);
            hero.addEventListener('mouseleave', start);
            show(0); start();
        }

        /* ---- Live date under "Welcome!" ---------------------------- */
        var dateEl = document.getElementById('gfa-hero-date');
        if (dateEl) {
            try {
                var now = new Date();
                var d = now.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                var t = now.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
                dateEl.textContent = d + ' • ' + t;
            } catch (e) {}
        }

        /* ---- App Directory: instant search filter ------------------ */
        var search = document.getElementById('gfa-dir-search');
        var grid = document.getElementById('gfa-dir-grid');
        if (search && grid) {
            var cards = [].slice.call(grid.querySelectorAll('[data-search]'));
            var emptyMsg = document.getElementById('gfa-dir-empty');
            function applyFilter() {
                var q = search.value.trim().toLowerCase(), shown = 0;
                cards.forEach(function (c) {
                    var hit = q === '' || (c.getAttribute('data-search') || '').indexOf(q) > -1;
                    c.classList.toggle('gfa-hidden', !hit);
                    if (hit) shown++;
                });
                if (emptyMsg) emptyMsg.style.display = (shown === 0) ? '' : 'none';
            }
            search.addEventListener('input', applyFilter);
        }

        /* ---- Add to My Apps ---------------------------------------- */
        /* Signed-in members persist to the server (survives across devices);   */
        /* guests fall back to localStorage.                                    */
        var mine = readSet(LS_APPS);
        function paint(btn, on) {
            var card = btn.closest('.gfa-appd-card');
            if (card) card.classList.toggle('gfa-added', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            btn.title = on ? 'Remove from My Apps' : 'Add to My Apps';
            btn.innerHTML = on
                ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>'
                : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>';
        }
        [].slice.call(root.querySelectorAll('.gfa-appd-add-btn')).forEach(function (btn) {
            var id = btn.getAttribute('data-app');
            if (!GFA.logged && id && mine.has(id)) paint(btn, true); // guest: hydrate from LS
            btn.addEventListener('click', function (e) {
                e.preventDefault(); e.stopPropagation();
                if (!id || btn.disabled) return;
                var next = btn.getAttribute('aria-pressed') !== 'true';
                if (GFA.logged) {
                    paint(btn, next); btn.disabled = true; // optimistic
                    var action = next ? 'add' : 'remove';
                    fetch(GFA.endpoint + '?gfa_action=' + action + '&app=' + encodeURIComponent(id),
                          { method: 'POST', credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
                        .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
                        .then(function (j) { if (!j || !j.ok) paint(btn, !next); })
                        .catch(function () { paint(btn, !next); }) // revert on failure
                        .then(function () { btn.disabled = false; });
                } else {
                    if (next) mine.add(id); else mine.delete(id);
                    paint(btn, next);
                    writeSet(LS_APPS, mine);
                }
            });
        });

        /* ---- Admin: pull directory from Supabase ------------------- */
        var syncBtn = document.getElementById('gfa-sync');
        var syncStatus = document.getElementById('gfa-sync-status');
        if (syncBtn) {
            syncBtn.addEventListener('click', function () {
                if (syncBtn.disabled) return;
                syncBtn.disabled = true;
                if (syncStatus) syncStatus.textContent = 'Syncing… this can take ~30s.';
                fetch((GFA.endpoint || '') + '?gfa_action=import',
                      { method: 'POST', credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (j) {
                        if (j && j.ok) {
                            var n = j.synced || {};
                            var apps = (n.directory_apps || 0);
                            if (syncStatus) syncStatus.textContent = 'Synced ' + apps + ' apps. Reloading…';
                            setTimeout(function () { location.reload(); }, 900);
                        } else {
                            var err = j && j.errors ? JSON.stringify(j.errors) : 'failed';
                            if (syncStatus) syncStatus.textContent = 'Error: ' + err;
                            syncBtn.disabled = false;
                        }
                    })
                    .catch(function (e) {
                        if (syncStatus) syncStatus.textContent = 'Error: ' + e;
                        syncBtn.disabled = false;
                    });
            });
        }

        /* ---- Video Chat popover ------------------------------------ */
        var vc = document.getElementById('gfa-videochat');
        var pop = document.getElementById('gfa-vc-pop');
        if (vc && pop) {
            vc.addEventListener('click', function (e) {
                e.stopPropagation();
                pop.classList.toggle('gfa-hidden');
                var saved = localStorage.getItem(LS_MEET) || '';
                var input = pop.querySelector('#gfa-vc-input');
                if (input) input.value = saved;
            });
            document.addEventListener('click', function (e) {
                if (!pop.classList.contains('gfa-hidden') && !pop.contains(e.target) && e.target !== vc)
                    pop.classList.add('gfa-hidden');
            });
            var saveBtn = pop.querySelector('#gfa-vc-save');
            if (saveBtn) saveBtn.addEventListener('click', function () {
                var input = pop.querySelector('#gfa-vc-input');
                var v = (input && input.value || '').trim();
                if (v) { localStorage.setItem(LS_MEET, v); window.open(v, '_blank', 'noopener'); }
                pop.classList.add('gfa-hidden');
            });
        }
    });
})();
