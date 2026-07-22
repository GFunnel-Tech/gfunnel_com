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
        function onScroll() {
            var y = window.scrollY || window.pageYOffset;
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

        /* --- Cmd/Ctrl+K focuses the hero search --- */
        var heroInput = document.getElementById('gfh-hero-input');
        if (heroInput) {
            document.addEventListener('keydown', function(e) {
                if ((e.metaKey || e.ctrlKey) && (e.key === 'k' || e.key === 'K')) {
                    e.preventDefault();
                    heroInput.focus();
                    heroInput.select();
                }
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
