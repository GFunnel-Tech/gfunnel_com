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

        /* --- Sticky nav shadow + back-to-top visibility --- */
        var topBtn = document.getElementById('gfh-top-btn');
        var navBar = document.getElementById('gfh-header');
        function onScroll() {
            var y = window.scrollY || window.pageYOffset;
            if (topBtn) topBtn.classList.toggle('gfh-show', y > 700);
            if (navBar) navBar.classList.toggle('gfh-scrolled', y > 8);
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
        var header = document.getElementById('gfh-header');
        root.addEventListener('click', function(e) {
            var a = e.target.closest('a[href^="#"]');
            if (!a || a.getAttribute('href') === '#') return;
            var target = document.getElementById(a.getAttribute('href').slice(1));
            if (!target) return;
            e.preventDefault();
            var offset = header ? header.offsetHeight + 12 : 0;
            var top = target.getBoundingClientRect().top + (window.scrollY || window.pageYOffset) - offset;
            window.scrollTo({ top: top, behavior: 'smooth' });
        });

        /* --- Cmd/Ctrl+K focuses the nav search --- */
        var navInput = document.getElementById('gfh-nav-input');
        if (navInput) {
            document.addEventListener('keydown', function(e) {
                if ((e.metaKey || e.ctrlKey) && (e.key === 'k' || e.key === 'K')) {
                    e.preventDefault();
                    navInput.focus();
                    navInput.select();
                }
            });
        }

        /* --- Hero search scope tabs (focus + routing) --- */
        var heroTabs = root.querySelectorAll('.gfh-hero-tab');
        var heroScope = document.getElementById('gfh-hero-scope');
        if (heroTabs.length) {
            heroTabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    heroTabs.forEach(function(t) { t.classList.toggle('gfh-on', t === tab); });
                    if (heroScope) heroScope.value = tab.getAttribute('data-scope') || 'all';
                    var ph = tab.getAttribute('data-ph');
                    if (heroInput && ph) { heroInput.setAttribute('placeholder', ph); heroInput.focus(); }
                });
            });
        }

        /* --- Product tour tabs (hubs) --- */
        var hubTabs = root.querySelectorAll('.gfh-hub-tab');
        var hubPanels = root.querySelectorAll('.gfh-hub-panel');
        if (hubTabs.length) {
            hubTabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    var key = tab.getAttribute('data-hub');
                    hubTabs.forEach(function(t) { t.classList.toggle('gfh-on', t === tab); });
                    hubPanels.forEach(function(p) { p.classList.toggle('gfh-on', p.getAttribute('data-hub') === key); });
                });
            });
        }

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
    });
})();
