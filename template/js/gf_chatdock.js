/**
 * GFunnel — site-wide "Chats" dock (Facebook Messenger right-rail).
 * -----------------------------------------------------------------------------
 * Progressive-enhancement only: the dock server-renders its conversation list,
 * so it is fully readable without JS. This script adds open/close + persistence,
 * client-side search, and opening a conversation in the full Messenger. It never
 * throws into the page — every hook is guarded.
 *
 * Phase 1: rows open the full /messenger app. Phase 2 will load conversations
 * into floating chat windows in place (via the Messenger AJAX actions).
 */
(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState !== 'loading') fn();
		else document.addEventListener('DOMContentLoaded', fn);
	}

	ready(function () {
		try {
			var eDock = document.getElementById('gf-chatdock');
			if (!eDock) return;

			// Don't duplicate the experience on the Messenger page itself.
			if (document.body && document.body.classList.contains('bx-page-messenger')) {
				eDock.parentNode && eDock.parentNode.removeChild(eDock);
				return;
			}

			var sOpenUrl = eDock.getAttribute('data-open-url') || '';
			var eLauncher = eDock.querySelector('.gf-chatdock-launcher');
			var eMin = eDock.querySelector('.gf-chatdock-min');
			var eSearch = eDock.querySelector('.gf-chatdock-search-input');
			var eList = eDock.querySelector('.gf-chatdock-list');
			var eNoResults = eDock.querySelector('.gf-chatdock-noresults');
			var KEY = 'gfChatDockOpen';

			function setOpen(bOpen) {
				eDock.classList.toggle('is-open', !!bOpen);
				if (eLauncher) eLauncher.setAttribute('aria-expanded', bOpen ? 'true' : 'false');
				try { window.localStorage.setItem(KEY, bOpen ? '1' : '0'); } catch (e) {}
				if (bOpen && eSearch) { try { eSearch.focus(); } catch (e) {} }
			}

			if (eLauncher) eLauncher.addEventListener('click', function () { setOpen(true); });
			if (eMin) eMin.addEventListener('click', function () { setOpen(false); });

			// Restore last state (default collapsed).
			var sSaved = '0';
			try { sSaved = window.localStorage.getItem(KEY) || '0'; } catch (e) {}
			if (sSaved === '1') setOpen(true);

			// The reused briefs carry an inline onclick calling oMessenger, which
			// only exists on the Messenger page. Neutralise it and open the full
			// Messenger instead (Phase 2: floating windows in place).
			if (eList) {
				var aRows = eList.querySelectorAll('.bx-messenger-jots-snip');
				Array.prototype.forEach.call(aRows, function (eRow) {
					eRow.onclick = null;
					eRow.addEventListener('click', function (ev) {
						ev.preventDefault();
						if (sOpenUrl) window.location.href = sOpenUrl;
					});
					var aLinks = eRow.querySelectorAll('a[href="javascript:void(0);"]');
					Array.prototype.forEach.call(aLinks, function (eA) { eA.removeAttribute('href'); });
				});
			}

			// Client-side filter over the conversation rows.
			if (eSearch && eList) {
				eSearch.addEventListener('input', function () {
					var sTerm = (eSearch.value || '').trim().toLowerCase();
					var iShown = 0;
					var aRows = eList.querySelectorAll('.bx-messenger-jots-snip');
					Array.prototype.forEach.call(aRows, function (eRow) {
						var bMatch = !sTerm || (eRow.textContent || '').toLowerCase().indexOf(sTerm) !== -1;
						eRow.style.display = bMatch ? '' : 'none';
						if (bMatch) iShown++;
					});
					if (eNoResults) eNoResults.hidden = (iShown !== 0);
				});
			}

			// Reflect unread state on the launcher: any unread row -> show a dot count.
			try {
				var iUnread = eDock.querySelectorAll('.bx-messenger-jots-snip.unread-lot').length;
				if (iUnread > 0 && eLauncher) {
					eDock.classList.add('has-unread');
					var eBadge = document.createElement('span');
					eBadge.className = 'gf-chatdock-unread';
					eBadge.textContent = iUnread > 99 ? '99+' : String(iUnread);
					eLauncher.appendChild(eBadge);
				}
			} catch (e) {}
		} catch (e) {
			if (window.console && console.warn) console.warn('gf-chatdock init failed', e);
		}
	});
})();
