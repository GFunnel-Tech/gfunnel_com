/*
 * GFunnel — public home page interactions (template/page_home.html).
 * Vanilla JS, no dependencies.
 */
(function() {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    ready(function() {
        var root = document.getElementById('gfh');
        if (!root) return;

        /* --- Sticky header shadow --- */
        var header = document.getElementById('gfh-header');
        var topBtn = document.getElementById('gfh-top-btn');
        function onScroll() {
            var y = window.scrollY || window.pageYOffset;
            if (header) header.classList.toggle('gfh-header-scrolled', y > 8);
            if (topBtn) topBtn.classList.toggle('gfh-show', y > 700);
        }
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        if (topBtn) topBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        /* --- Mobile menu --- */
        var burger = document.getElementById('gfh-burger');
        var mobile = document.getElementById('gfh-mobile');
        if (burger && mobile) {
            burger.addEventListener('click', function() {
                var open = mobile.classList.toggle('gfh-open');
                burger.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            mobile.addEventListener('click', function(e) {
                if (e.target.closest('a')) {
                    mobile.classList.remove('gfh-open');
                    burger.setAttribute('aria-expanded', 'false');
                }
            });
        }

        /* --- Smooth scroll for in-page anchors --- */
        root.addEventListener('click', function(e) {
            var a = e.target.closest('a[href^="#"]');
            if (!a) return;
            var target = document.getElementById(a.getAttribute('href').slice(1));
            if (!target) return;
            e.preventDefault();
            var offset = header ? header.offsetHeight + 12 : 0;
            var top = target.getBoundingClientRect().top + (window.scrollY || window.pageYOffset) - offset;
            window.scrollTo({ top: top, behavior: 'smooth' });
        });

        /* --- Reveal on scroll --- */
        var reveals = root.querySelectorAll('.gfh-reveal');
        if ('IntersectionObserver' in window) {
            var revealObs = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('gfh-in');
                        revealObs.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
            reveals.forEach(function(el) { revealObs.observe(el); });
        } else {
            reveals.forEach(function(el) { el.classList.add('gfh-in'); });
        }

        /* --- Stat counters --- */
        function animateCount(el) {
            var target = parseInt(el.getAttribute('data-count'), 10);
            if (isNaN(target)) return;
            var prefix = el.getAttribute('data-prefix') || '';
            var suffix = el.getAttribute('data-suffix') || '';
            var dur = 1600;
            var start = null;
            function fmt(n) { return n.toLocaleString('en-US'); }
            function step(ts) {
                if (!start) start = ts;
                var p = Math.min((ts - start) / dur, 1);
                var eased = 1 - Math.pow(1 - p, 3);
                el.textContent = prefix + fmt(Math.round(target * eased)) + suffix;
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        }
        var counters = root.querySelectorAll('[data-count]');
        if ('IntersectionObserver' in window && counters.length) {
            var countObs = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        animateCount(entry.target);
                        countObs.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });
            counters.forEach(function(el) { countObs.observe(el); });
        } else {
            counters.forEach(function(el) {
                el.textContent = (el.getAttribute('data-prefix') || '')
                    + parseInt(el.getAttribute('data-count'), 10).toLocaleString('en-US')
                    + (el.getAttribute('data-suffix') || '');
            });
        }

        /* --- App directory mockup: search + category pills + add toggles --- */
        var appSearch = document.getElementById('gfh-app-search');
        var appGrid = document.getElementById('gfh-app-grid');
        var appPills = document.getElementById('gfh-app-pills');
        var appCat = '';
        function applyAppFilter() {
            if (!appGrid) return;
            var q = appSearch ? appSearch.value.trim().toLowerCase() : '';
            var appEmpty = document.getElementById('gfh-app-empty');
            var visible = 0;
            appGrid.querySelectorAll('.gfh-app-card').forEach(function(card) {
                var hitQ = (card.getAttribute('data-app') || '').toLowerCase().indexOf(q) !== -1;
                var hitCat = !appCat || card.getAttribute('data-cat') === appCat;
                var hit = hitQ && hitCat;
                card.style.display = hit ? '' : 'none';
                if (hit) visible++;
            });
            if (appEmpty) appEmpty.style.display = visible ? 'none' : 'block';
        }
        if (appSearch) appSearch.addEventListener('input', applyAppFilter);
        if (appPills) {
            appPills.addEventListener('click', function(e) {
                var pill = e.target.closest('.gfh-appdir-pill');
                if (!pill) return;
                appCat = pill.getAttribute('data-cat') || '';
                appPills.querySelectorAll('.gfh-appdir-pill').forEach(function(p) {
                    p.classList.toggle('gfh-active', p === pill);
                });
                applyAppFilter();
            });
        }
        root.querySelectorAll('.gfh-app-toggle').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var on = btn.classList.toggle('gfh-on');
                btn.textContent = on ? '✓' : '+';
                btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
        });

        /* --- Workspace chips drive the "Welcome back" picker mockup --- */
        var wsTitle = document.getElementById('gfh-ws-title');
        var wsAvatar = document.getElementById('gfh-ws-avatar');
        var chips = root.querySelectorAll('.gfh-chip');

        function setWorkspace(chip) {
            chips.forEach(function(c) { c.classList.toggle('gfh-chip-active', c === chip); });
            var name = chip.getAttribute('data-ws') || '';
            if (wsTitle) wsTitle.textContent = name.charAt(0).toUpperCase() + name.slice(1);
            if (wsAvatar) {
                var words = name.split(' ');
                var noun = words[words.length - 1] || name;
                wsAvatar.textContent = noun.charAt(0).toUpperCase();
            }
        }
        chips.forEach(function(chip) {
            chip.addEventListener('click', function() { setWorkspace(chip); });
        });
        if (chips.length) setWorkspace(chips[0]);

        /* --- Module list drives the Sales Hub mockup banner --- */
        var hubTag = document.getElementById('gfh-hub-tag');
        var hubTitle = document.getElementById('gfh-hub-title');
        var hubSub = document.getElementById('gfh-hub-sub');
        root.querySelectorAll('.gfh-module-item[data-hub-title]').forEach(function(item) {
            function apply() {
                root.querySelectorAll('.gfh-module-item').forEach(function(m) {
                    m.classList.toggle('gfh-active', m === item);
                });
                if (hubTag) hubTag.textContent = item.getAttribute('data-hub-tag') || 'Module';
                if (hubTitle) hubTitle.textContent = item.getAttribute('data-hub-title');
                if (hubSub && item.getAttribute('data-hub-sub')) hubSub.textContent = item.getAttribute('data-hub-sub');
            }
            item.addEventListener('mouseenter', apply);
            item.addEventListener('focus', apply);
            item.addEventListener('click', apply);
        });

        /* --- Footer newsletter: validate, then hand off to account creation --- */
        var newsForm = document.getElementById('gfh-news-form');
        if (newsForm) {
            newsForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var input = document.getElementById('gfh-news-email');
                var msg = document.getElementById('gfh-news-msg');
                var email = input ? input.value.trim() : '';
                var valid = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email);
                if (!valid) {
                    if (msg) {
                        msg.textContent = 'Please enter a valid email address.';
                        msg.className = 'gfh-news-msg gfh-err';
                    }
                    if (input) input.focus();
                    return;
                }
                if (msg) {
                    msg.textContent = 'You’re in - taking you to create your free account…';
                    msg.className = 'gfh-news-msg gfh-ok';
                }
                var joinUrl = newsForm.getAttribute('data-join-url') || '/';
                joinUrl += (joinUrl.indexOf('?') === -1 ? '?' : '&') + 'email=' + encodeURIComponent(email);
                setTimeout(function() { window.location.href = joinUrl; }, 900);
            });
        }
    });
})();
