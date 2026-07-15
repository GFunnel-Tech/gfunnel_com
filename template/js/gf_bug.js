/**
 * GFunnel — Bug report widget.
 *
 * Loaded on every authorized page next to the header bug icon
 * (template/_page_toolbar_auth.html). Flow:
 *
 *  1. Bug icon → intro modal: "Click to report" (element picker) or
 *     "Upload screenshot" (skip the picker). "Don't show this again"
 *     remembers the choice and jumps straight into the picker.
 *  2. Picker mode: orange banner, hover highlight with a selector tooltip;
 *     clicking an element captures a viewport screenshot (html2canvas,
 *     lazy-loaded from plugins_public) plus selector/XPath metadata.
 *  3. Side panel: screenshot (Annotate / Replace), up to 3 additional
 *     screenshots, a native 30s screen recording (or a Komodo link),
 *     description, priority and the auto-captured metadata.
 *  4. Submit → multipart POST to gf_bug.php.
 */

(function() {
    'use strict';

    var oCfg = {
        endpoint: 'gf_bug.php',
        html2canvas: '',
        komodo: 'https://kommodo.gfunnel.com'
    };

    var oState = null;
    var oH2cPromise = null;
    var RECORD_LIMIT = 30; // seconds
    var EXTRA_LIMIT = 3;

    function freshState() {
        return {
            shot: null,          // main screenshot Blob
            shotUrl: '',         // object URL for the preview
            extras: [],          // [{blob, url}]
            recBlob: null,
            recUrl: '',
            recError: false,
            recorder: null,
            recStream: null,
            recTimer: null,
            meta: {}
        };
    }

    /*--- Tiny DOM helpers -------------------------------------------------*/

    function el(sTag, sClass, sHtml) {
        var e = document.createElement(sTag);
        if(sClass)
            e.className = sClass;
        if(typeof sHtml == 'string')
            e.innerHTML = sHtml;
        return e;
    }

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    }

    function svg(sPaths, iSize) {
        iSize = iSize || 16;
        return '<svg xmlns="http://www.w3.org/2000/svg" width="' + iSize + '" height="' + iSize + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + sPaths + '</svg>';
    }

    var ICO_CAMERA = svg('<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/>');
    var ICO_VIDEO = svg('<path d="m16 13 5.223 3.482a.5.5 0 0 0 .777-.416V7.87a.5.5 0 0 0-.752-.432L16 10.5"/><rect x="2" y="6" width="14" height="12" rx="2"/>');
    var ICO_TARGET = svg('<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>');
    var ICO_CLOSE = svg('<path d="M18 6 6 18"/><path d="m6 6 12 12"/>');
    var ICO_ADD_IMG = svg('<path d="M16 5h6"/><path d="M19 2v6"/><path d="M21 11.5V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7.5"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/><circle cx="9" cy="9" r="2"/>', 22);

    /*--- Selector / XPath builders (mirror the v0 prototype's format) -----*/

    function cssSegment(eNode) {
        var s = eNode.tagName.toLowerCase();

        var aClasses = [];
        if(eNode.classList)
            for(var i = 0; i < eNode.classList.length && aClasses.length < 2; i++) {
                var c = eNode.classList[i];
                if(/^gf-bug/.test(c))
                    continue;
                aClasses.push(c.replace(/([^A-Za-z0-9_-])/g, '\\$1'));
            }
        if(aClasses.length)
            s += '.' + aClasses.join('.');

        var eParent = eNode.parentElement;
        if(eParent) {
            var iIndex = Array.prototype.indexOf.call(eParent.children, eNode) + 1;
            if(eParent.children.length > 1)
                s += ':nth-child(' + iIndex + ')';
        }

        return s;
    }

    function cssSelector(eNode) {
        var aParts = [];
        var e = eNode;
        while(e && e.tagName && e.tagName.toLowerCase() != 'body' && e.tagName.toLowerCase() != 'html' && aParts.length < 8) {
            if(e.id) {
                aParts.unshift('#' + e.id.replace(/([^A-Za-z0-9_-])/g, '\\$1'));
                break;
            }
            aParts.unshift(cssSegment(e));
            e = e.parentElement;
        }
        return aParts.join(' > ');
    }

    function xPath(eNode) {
        var aParts = [];
        var e = eNode;
        while(e && e.nodeType === 1) {
            var iIndex = 1;
            var eSib = e.previousElementSibling;
            while(eSib) {
                if(eSib.tagName === e.tagName)
                    iIndex++;
                eSib = eSib.previousElementSibling;
            }
            aParts.unshift(e.tagName.toLowerCase() + '[' + iIndex + ']');
            e = e.parentElement;
        }
        return '/' + aParts.join('/');
    }

    function captureMeta(eNode) {
        return {
            page_url: window.location.href,
            viewport: window.innerWidth + 'x' + window.innerHeight,
            dpr: String(window.devicePixelRatio || 1),
            ua: navigator.userAgent,
            selector: eNode ? cssSelector(eNode) : '',
            xpath: eNode ? xPath(eNode) : ''
        };
    }

    /*--- Screenshot capture (html2canvas, lazy) ---------------------------*/

    function loadH2c() {
        if(window.html2canvas)
            return Promise.resolve();
        if(oH2cPromise)
            return oH2cPromise;

        oH2cPromise = new Promise(function(resolve, reject) {
            var eScript = document.createElement('script');
            eScript.src = oCfg.html2canvas;
            eScript.onload = function() { resolve(); };
            eScript.onerror = function() { oH2cPromise = null; reject(new Error('html2canvas failed to load')); };
            document.head.appendChild(eScript);
        });
        return oH2cPromise;
    }

    function captureViewport() {
        return loadH2c().then(function() {
            return window.html2canvas(document.body, {
                x: window.scrollX,
                y: window.scrollY,
                width: window.innerWidth,
                height: window.innerHeight,
                windowWidth: window.innerWidth,
                windowHeight: window.innerHeight,
                scale: Math.min(window.devicePixelRatio || 1, 2),
                useCORS: true,
                logging: false,
                ignoreElements: function(e) {
                    return e.classList && (e.classList.contains('gf-bug-layer') || e.classList.contains('gf-bug-banner'));
                }
            });
        }).then(function(eCanvas) {
            return new Promise(function(resolve) {
                eCanvas.toBlob(function(oBlob) { resolve(oBlob); }, 'image/png');
            });
        });
    }

    /*--- Intro modal -------------------------------------------------------*/

    function openIntro() {
        var eWrap = el('div', 'gf-bug-layer gf-bug-modal-wrap');
        eWrap.innerHTML =
            '<div class="gf-bug-backdrop"></div>' +
            '<div class="gf-bug-modal" role="dialog" aria-modal="true" aria-label="Report a bug">' +
                '<button type="button" class="gf-bug-modal-close" aria-label="Close">' + ICO_CLOSE + '</button>' +
                '<h3>Report a bug</h3>' +
                '<p>Click anywhere on the page where you\'re experiencing an issue. We\'ll automatically capture a screenshot and the element you clicked.</p>' +
                '<button type="button" class="gf-bug-choice gf-bug-choice-primary" data-action="pick">' +
                    '<span class="gf-bug-choice-ico">' + ICO_TARGET + '</span>' +
                    '<span><b>Click to report</b><i>Select the element with the issue</i></span>' +
                '</button>' +
                '<button type="button" class="gf-bug-choice" data-action="upload">' +
                    '<span class="gf-bug-choice-ico">' + ICO_CAMERA + '</span>' +
                    '<span><b>Upload screenshot</b><i>Skip element picker, attach your own</i></span>' +
                '</button>' +
                '<button type="button" class="gf-bug-cancel-link" data-action="cancel">' + svg('<path d="M18 6 6 18"/><path d="m6 6 12 12"/>', 14) + ' Cancel</button>' +
                '<div class="gf-bug-modal-foot">' +
                    '<label class="gf-bug-check"><input type="checkbox" class="gf-bug-skip-intro" /> Don\'t show this again</label>' +
                '</div>' +
            '</div>';
        document.body.appendChild(eWrap);

        function close() {
            eWrap.remove();
            document.removeEventListener('keydown', onKey);
        }
        function onKey(oEvent) {
            if(oEvent.key === 'Escape')
                close();
        }
        function rememberSkip() {
            if(eWrap.querySelector('.gf-bug-skip-intro').checked)
                try { localStorage.setItem('gf_bug_skip_intro', '1'); } catch(e) {}
        }

        document.addEventListener('keydown', onKey);
        eWrap.querySelector('.gf-bug-backdrop').addEventListener('click', close);
        eWrap.querySelector('.gf-bug-modal-close').addEventListener('click', close);
        eWrap.querySelector('[data-action="cancel"]').addEventListener('click', close);
        eWrap.querySelector('[data-action="pick"]').addEventListener('click', function() {
            rememberSkip();
            close();
            startPicker();
        });
        eWrap.querySelector('[data-action="upload"]').addEventListener('click', function() {
            rememberSkip();
            close();
            pickFile(function(oBlob) {
                oState = freshState();
                oState.meta = captureMeta(null);
                setMainShot(oBlob);
                openPanel();
            });
        });
    }

    /*--- Element picker -----------------------------------------------------*/

    function startPicker() {
        var eBanner = el('div', 'gf-bug-banner',
            '<span>Bug mode active &mdash; click on the issue</span>' +
            '<button type="button" class="gf-bug-banner-cancel">' + svg('<path d="M18 6 6 18"/><path d="m6 6 12 12"/>', 14) + ' Cancel</button>' +
            '<em>(or press Escape)</em>');
        var eBox = el('div', 'gf-bug-layer gf-bug-pick-box');
        var eTip = el('div', 'gf-bug-layer gf-bug-pick-tip');
        eBox.style.display = eTip.style.display = 'none';

        document.body.appendChild(eBanner);
        document.body.appendChild(eBox);
        document.body.appendChild(eTip);
        document.documentElement.classList.add('gf-bug-picking');

        var eCurrent = null;

        function targetAt(oEvent) {
            var e = document.elementFromPoint(oEvent.clientX, oEvent.clientY);
            if(!e || e === document.body || e === document.documentElement)
                return null;
            if(e.closest('.gf-bug-banner, .gf-bug-layer'))
                return null;
            return e;
        }

        function onMove(oEvent) {
            var e = targetAt(oEvent);
            eCurrent = e;
            if(!e) {
                eBox.style.display = eTip.style.display = 'none';
                return;
            }

            var oRect = e.getBoundingClientRect();
            eBox.style.display = 'block';
            eBox.style.left = oRect.left + 'px';
            eBox.style.top = oRect.top + 'px';
            eBox.style.width = oRect.width + 'px';
            eBox.style.height = oRect.height + 'px';

            eTip.style.display = 'block';
            eTip.textContent = cssSegment(e);
            var iTipTop = oRect.bottom + 6;
            if(iTipTop > window.innerHeight - 30)
                iTipTop = Math.max(oRect.top - 30, 4);
            eTip.style.top = iTipTop + 'px';
            eTip.style.left = Math.min(Math.max(oRect.left, 4), window.innerWidth - 220) + 'px';
        }

        function stop() {
            eBanner.remove();
            eBox.remove();
            eTip.remove();
            document.documentElement.classList.remove('gf-bug-picking');
            document.removeEventListener('mousemove', onMove, true);
            document.removeEventListener('click', onClick, true);
            document.removeEventListener('keydown', onKey, true);
        }

        function onKey(oEvent) {
            if(oEvent.key === 'Escape') {
                oEvent.preventDefault();
                stop();
            }
        }

        function onClick(oEvent) {
            if(oEvent.target.closest('.gf-bug-banner-cancel')) {
                oEvent.preventDefault();
                oEvent.stopPropagation();
                stop();
                return;
            }
            if(oEvent.target.closest('.gf-bug-banner'))
                return;

            oEvent.preventDefault();
            oEvent.stopPropagation();

            var e = eCurrent || targetAt(oEvent);
            oState = freshState();
            oState.meta = captureMeta(e);
            stop();

            // let the highlight/banner leave the DOM before the capture
            setTimeout(function() {
                captureViewport().then(function(oBlob) {
                    setMainShot(oBlob);
                    openPanel();
                }).catch(function() {
                    openPanel(); // no screenshot - the panel offers an upload
                });
            }, 60);
        }

        document.addEventListener('mousemove', onMove, true);
        document.addEventListener('click', onClick, true);
        document.addEventListener('keydown', onKey, true);
    }

    /*--- Shared file helpers -------------------------------------------------*/

    function pickFile(fnDone) {
        var eInput = document.createElement('input');
        eInput.type = 'file';
        eInput.accept = 'image/png,image/jpeg,image/webp';
        eInput.addEventListener('change', function() {
            if(eInput.files && eInput.files[0])
                fnDone(eInput.files[0]);
        });
        eInput.click();
    }

    function setMainShot(oBlob) {
        if(oState.shotUrl)
            URL.revokeObjectURL(oState.shotUrl);
        oState.shot = oBlob;
        oState.shotUrl = oBlob ? URL.createObjectURL(oBlob) : '';
    }

    /*--- Report panel ---------------------------------------------------------*/

    function openPanel() {
        closePanel(true);

        var oMeta = oState.meta;
        var ePanel = el('div', 'gf-bug-layer gf-bug-panel');
        ePanel.innerHTML =
            '<div class="gf-bug-panel-head">' +
                '<h3>Report a bug</h3>' +
                '<button type="button" class="gf-bug-panel-close" aria-label="Close">' + ICO_CLOSE + '</button>' +
            '</div>' +
            '<div class="gf-bug-panel-body">' +

                '<div class="gf-bug-section">' +
                    '<label class="gf-bug-label">Screenshot</label>' +
                    '<div class="gf-bug-shot">' +
                        '<img class="gf-bug-shot-img" alt="Bug screenshot" />' +
                        '<div class="gf-bug-shot-empty">' + ICO_CAMERA + '<span>No screenshot captured</span></div>' +
                    '</div>' +
                    '<div class="gf-bug-shot-actions">' +
                        '<button type="button" class="gf-bug-btn" data-action="annotate">Annotate</button>' +
                        '<button type="button" class="gf-bug-btn" data-action="replace">Replace</button>' +
                    '</div>' +
                '</div>' +

                '<div class="gf-bug-section">' +
                    '<label class="gf-bug-label">Additional screenshots (<span class="gf-bug-extra-count">0</span>/' + EXTRA_LIMIT + ')</label>' +
                    '<div class="gf-bug-extras">' +
                        '<button type="button" class="gf-bug-extra-add" aria-label="Add screenshot">' + ICO_ADD_IMG + '</button>' +
                    '</div>' +
                '</div>' +

                '<div class="gf-bug-section">' +
                    '<label class="gf-bug-label">Screen recording</label>' +
                    '<div class="gf-bug-rec-idle">' +
                        '<button type="button" class="gf-bug-btn" data-action="record">' + ICO_VIDEO + ' Record screen (max ' + RECORD_LIMIT + 's)</button>' +
                    '</div>' +
                    '<div class="gf-bug-rec-live" style="display:none">' +
                        '<span class="gf-bug-rec-dot"></span> Recording&hellip; <span class="gf-bug-rec-clock">0:00</span>' +
                        '<button type="button" class="gf-bug-btn gf-bug-btn-sm" data-action="rec-stop">Stop</button>' +
                    '</div>' +
                    '<div class="gf-bug-rec-error" style="display:none">' +
                        'Screen recording was cancelled or denied.' +
                        '<button type="button" class="gf-bug-link" data-action="rec-dismiss">Dismiss</button>' +
                    '</div>' +
                    '<div class="gf-bug-rec-done" style="display:none">' +
                        '<video class="gf-bug-rec-video" controls></video>' +
                        '<button type="button" class="gf-bug-link" data-action="rec-remove">Remove recording</button>' +
                    '</div>' +
                    '<div class="gf-bug-komodo">' +
                        'Need a longer recording? Record it with <a href="' + esc(oCfg.komodo) + '" target="_blank" rel="noopener">Komodo</a> and paste the link:' +
                        '<input type="url" class="gf-bug-input gf-bug-rec-url" placeholder="https://kommodo.gfunnel.com/..." />' +
                    '</div>' +
                '</div>' +

                '<div class="gf-bug-section">' +
                    '<label class="gf-bug-label">Description <em class="gf-bug-req">*</em></label>' +
                    '<textarea class="gf-bug-input gf-bug-desc" rows="4" placeholder="Describe what went wrong..."></textarea>' +
                '</div>' +

                '<div class="gf-bug-section">' +
                    '<label class="gf-bug-label">Priority</label>' +
                    '<div class="gf-bug-priority">' +
                        '<button type="button" class="gf-bug-prio" data-prio="low">Low</button>' +
                        '<button type="button" class="gf-bug-prio gf-bug-prio-on" data-prio="medium">Medium</button>' +
                        '<button type="button" class="gf-bug-prio" data-prio="high">High</button>' +
                    '</div>' +
                '</div>' +

                '<div class="gf-bug-section">' +
                    '<label class="gf-bug-label">Auto-captured metadata</label>' +
                    '<div class="gf-bug-meta">' +
                        '<div><b>URL:</b> ' + esc(oMeta.page_url) + '</div>' +
                        '<div><b>Viewport:</b> ' + esc(oMeta.viewport) + '</div>' +
                        '<div><b>DPR:</b> ' + esc(oMeta.dpr) + '</div>' +
                        '<div><b>UA:</b> ' + esc(oMeta.ua) + '</div>' +
                        (oMeta.selector ? '<div><b>Selector:</b> ' + esc(oMeta.selector) + '</div>' : '') +
                        (oMeta.xpath ? '<div><b>XPath:</b> ' + esc(oMeta.xpath) + '</div>' : '') +
                    '</div>' +
                '</div>' +

            '</div>' +
            '<div class="gf-bug-panel-foot">' +
                '<button type="button" class="gf-bug-submit" disabled>Submit bug report</button>' +
            '</div>';

        document.body.appendChild(ePanel);
        requestAnimationFrame(function() { ePanel.classList.add('gf-bug-panel-open'); });

        refreshShot(ePanel);

        /*--- wiring ---*/
        ePanel.querySelector('.gf-bug-panel-close').addEventListener('click', function() {
            closePanel();
        });

        ePanel.querySelector('[data-action="replace"]').addEventListener('click', function() {
            pickFile(function(oBlob) {
                setMainShot(oBlob);
                refreshShot(ePanel);
            });
        });

        ePanel.querySelector('[data-action="annotate"]').addEventListener('click', function() {
            if(!oState.shot)
                return;
            openAnnotator(function(oBlob) {
                setMainShot(oBlob);
                refreshShot(ePanel);
            });
        });

        ePanel.querySelector('.gf-bug-extra-add').addEventListener('click', function() {
            if(oState.extras.length >= EXTRA_LIMIT)
                return;
            pickFile(function(oBlob) {
                oState.extras.push({blob: oBlob, url: URL.createObjectURL(oBlob)});
                refreshExtras(ePanel);
            });
        });

        ePanel.querySelector('[data-action="record"]').addEventListener('click', function() {
            startRecording(ePanel);
        });
        ePanel.querySelector('[data-action="rec-stop"]').addEventListener('click', function() {
            stopRecording();
        });
        ePanel.querySelector('[data-action="rec-dismiss"]').addEventListener('click', function() {
            oState.recError = false;
            refreshRecording(ePanel);
        });
        ePanel.querySelector('[data-action="rec-remove"]').addEventListener('click', function() {
            if(oState.recUrl)
                URL.revokeObjectURL(oState.recUrl);
            oState.recBlob = null;
            oState.recUrl = '';
            refreshRecording(ePanel);
        });

        var eDesc = ePanel.querySelector('.gf-bug-desc');
        var eSubmit = ePanel.querySelector('.gf-bug-submit');
        eDesc.addEventListener('input', function() {
            eSubmit.disabled = eDesc.value.trim() === '';
        });

        ePanel.querySelectorAll('.gf-bug-prio').forEach(function(eBtn) {
            eBtn.addEventListener('click', function() {
                ePanel.querySelectorAll('.gf-bug-prio').forEach(function(e) { e.classList.remove('gf-bug-prio-on'); });
                eBtn.classList.add('gf-bug-prio-on');
            });
        });

        eSubmit.addEventListener('click', function() {
            submitReport(ePanel);
        });
    }

    function panelEl() {
        return document.querySelector('.gf-bug-panel');
    }

    function closePanel(bSilent) {
        var ePanel = panelEl();
        if(!ePanel)
            return;

        if(!bSilent) {
            var bDirty = oState && (oState.shot || oState.extras.length || oState.recBlob
                || (ePanel.querySelector('.gf-bug-desc') && ePanel.querySelector('.gf-bug-desc').value.trim() !== ''));
            if(bDirty && !window.confirm('Discard this bug report?'))
                return;
        }

        stopRecording(true);
        ePanel.remove();
    }

    function refreshShot(ePanel) {
        var eImg = ePanel.querySelector('.gf-bug-shot-img');
        var eEmpty = ePanel.querySelector('.gf-bug-shot-empty');
        var eAnnotate = ePanel.querySelector('[data-action="annotate"]');
        if(oState.shotUrl) {
            eImg.src = oState.shotUrl;
            eImg.style.display = 'block';
            eEmpty.style.display = 'none';
            eAnnotate.disabled = false;
        }
        else {
            eImg.style.display = 'none';
            eEmpty.style.display = 'flex';
            eAnnotate.disabled = true;
        }
    }

    function refreshExtras(ePanel) {
        var eWrap = ePanel.querySelector('.gf-bug-extras');
        eWrap.querySelectorAll('.gf-bug-extra').forEach(function(e) { e.remove(); });

        var eAdd = eWrap.querySelector('.gf-bug-extra-add');
        oState.extras.forEach(function(oExtra, iIndex) {
            var eThumb = el('div', 'gf-bug-extra',
                '<img src="' + oExtra.url + '" alt="Additional screenshot ' + (iIndex + 1) + '" />' +
                '<button type="button" class="gf-bug-extra-del" aria-label="Remove">' + svg('<path d="M18 6 6 18"/><path d="m6 6 12 12"/>', 12) + '</button>');
            eThumb.querySelector('.gf-bug-extra-del').addEventListener('click', function() {
                URL.revokeObjectURL(oExtra.url);
                oState.extras.splice(iIndex, 1);
                refreshExtras(ePanel);
            });
            eWrap.insertBefore(eThumb, eAdd);
        });

        eAdd.style.display = oState.extras.length >= EXTRA_LIMIT ? 'none' : 'flex';
        ePanel.querySelector('.gf-bug-extra-count').textContent = oState.extras.length;
    }

    /*--- Screen recording (native, capped at RECORD_LIMIT seconds) -----------*/

    function refreshRecording(ePanel) {
        ePanel = ePanel || panelEl();
        if(!ePanel)
            return;

        var bLive = !!oState.recorder;
        ePanel.querySelector('.gf-bug-rec-idle').style.display = (!bLive && !oState.recBlob && !oState.recError) ? 'block' : 'none';
        ePanel.querySelector('.gf-bug-rec-live').style.display = bLive ? 'flex' : 'none';
        ePanel.querySelector('.gf-bug-rec-error').style.display = (!bLive && oState.recError) ? 'flex' : 'none';

        var eDone = ePanel.querySelector('.gf-bug-rec-done');
        eDone.style.display = (!bLive && oState.recBlob) ? 'block' : 'none';
        if(oState.recBlob)
            ePanel.querySelector('.gf-bug-rec-video').src = oState.recUrl;
    }

    function startRecording(ePanel) {
        if(!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia || typeof MediaRecorder == 'undefined') {
            oState.recError = true;
            refreshRecording(ePanel);
            return;
        }

        navigator.mediaDevices.getDisplayMedia({video: true, audio: false}).then(function(oStream) {
            var sMime = MediaRecorder.isTypeSupported('video/webm;codecs=vp9') ? 'video/webm;codecs=vp9' : 'video/webm';
            var oRecorder = new MediaRecorder(oStream, {mimeType: sMime});
            var aChunks = [];
            var iStarted = Date.now();

            oState.recorder = oRecorder;
            oState.recStream = oStream;
            oState.recError = false;

            oRecorder.ondataavailable = function(oEvent) {
                if(oEvent.data && oEvent.data.size)
                    aChunks.push(oEvent.data);
            };
            oRecorder.onstop = function() {
                oStream.getTracks().forEach(function(oTrack) { oTrack.stop(); });
                clearInterval(oState.recTimer);
                oState.recorder = null;
                oState.recStream = null;

                if(aChunks.length) {
                    if(oState.recUrl)
                        URL.revokeObjectURL(oState.recUrl);
                    oState.recBlob = new Blob(aChunks, {type: 'video/webm'});
                    oState.recUrl = URL.createObjectURL(oState.recBlob);
                }
                refreshRecording();
            };

            // stop when the user ends the share from the browser UI
            oStream.getVideoTracks()[0].addEventListener('ended', function() {
                stopRecording();
            });

            oRecorder.start();

            oState.recTimer = setInterval(function() {
                var iSec = Math.floor((Date.now() - iStarted) / 1000);
                var eClock = document.querySelector('.gf-bug-rec-clock');
                if(eClock)
                    eClock.textContent = '0:' + (iSec < 10 ? '0' : '') + Math.min(iSec, RECORD_LIMIT);
                if(iSec >= RECORD_LIMIT)
                    stopRecording();
            }, 250);

            refreshRecording(ePanel);
        }).catch(function() {
            oState.recError = true;
            refreshRecording(ePanel);
        });
    }

    function stopRecording(bSilent) {
        if(oState && oState.recTimer)
            clearInterval(oState.recTimer);
        if(oState && oState.recorder && oState.recorder.state !== 'inactive') {
            if(bSilent)
                oState.recorder.onstop = function() {
                    oState.recStream && oState.recStream.getTracks().forEach(function(oTrack) { oTrack.stop(); });
                };
            oState.recorder.stop();
        }
    }

    /*--- Annotator: draw on the screenshot ------------------------------------*/

    function openAnnotator(fnDone) {
        var eWrap = el('div', 'gf-bug-layer gf-bug-annotate-wrap');
        eWrap.innerHTML =
            '<div class="gf-bug-backdrop"></div>' +
            '<div class="gf-bug-annotate">' +
                '<div class="gf-bug-annotate-bar">' +
                    '<b>Annotate</b>' +
                    '<button type="button" class="gf-bug-btn gf-bug-btn-sm gf-bug-tool-on" data-tool="pen">Pen</button>' +
                    '<button type="button" class="gf-bug-btn gf-bug-btn-sm" data-tool="rect">Box</button>' +
                    '<button type="button" class="gf-bug-btn gf-bug-btn-sm" data-action="undo">Undo</button>' +
                    '<span class="gf-bug-annotate-spacer"></span>' +
                    '<button type="button" class="gf-bug-btn gf-bug-btn-sm" data-action="cancel">Cancel</button>' +
                    '<button type="button" class="gf-bug-btn gf-bug-btn-sm gf-bug-btn-primary" data-action="save">Save</button>' +
                '</div>' +
                '<div class="gf-bug-annotate-stage"><canvas></canvas></div>' +
            '</div>';
        document.body.appendChild(eWrap);

        var eCanvas = eWrap.querySelector('canvas');
        var oCtx = eCanvas.getContext('2d');
        var eImage = new Image();
        var aSteps = []; // snapshots for undo
        var sTool = 'pen';
        var bDown = false;
        var oStart = null;
        var oSnapshot = null;

        eImage.onload = function() {
            eCanvas.width = eImage.naturalWidth;
            eCanvas.height = eImage.naturalHeight;
            oCtx.drawImage(eImage, 0, 0);
        };
        eImage.src = oState.shotUrl;

        function canvasPoint(oEvent) {
            var oRect = eCanvas.getBoundingClientRect();
            return {
                x: (oEvent.clientX - oRect.left) * (eCanvas.width / oRect.width),
                y: (oEvent.clientY - oRect.top) * (eCanvas.height / oRect.height)
            };
        }

        function stroke() {
            oCtx.strokeStyle = '#ef4444';
            oCtx.lineWidth = Math.max(3, eCanvas.width / 400);
            oCtx.lineCap = 'round';
            oCtx.lineJoin = 'round';
        }

        eCanvas.addEventListener('pointerdown', function(oEvent) {
            bDown = true;
            eCanvas.setPointerCapture(oEvent.pointerId);
            aSteps.push(oCtx.getImageData(0, 0, eCanvas.width, eCanvas.height));
            if(aSteps.length > 20)
                aSteps.shift();

            oStart = canvasPoint(oEvent);
            if(sTool === 'rect')
                oSnapshot = oCtx.getImageData(0, 0, eCanvas.width, eCanvas.height);
            else {
                stroke();
                oCtx.beginPath();
                oCtx.moveTo(oStart.x, oStart.y);
            }
        });

        eCanvas.addEventListener('pointermove', function(oEvent) {
            if(!bDown)
                return;
            var oPoint = canvasPoint(oEvent);
            if(sTool === 'pen') {
                oCtx.lineTo(oPoint.x, oPoint.y);
                oCtx.stroke();
            }
            else {
                oCtx.putImageData(oSnapshot, 0, 0);
                stroke();
                oCtx.strokeRect(oStart.x, oStart.y, oPoint.x - oStart.x, oPoint.y - oStart.y);
            }
        });

        eCanvas.addEventListener('pointerup', function() {
            bDown = false;
        });

        eWrap.querySelectorAll('[data-tool]').forEach(function(eBtn) {
            eBtn.addEventListener('click', function() {
                sTool = eBtn.getAttribute('data-tool');
                eWrap.querySelectorAll('[data-tool]').forEach(function(e) { e.classList.remove('gf-bug-tool-on'); });
                eBtn.classList.add('gf-bug-tool-on');
            });
        });

        eWrap.querySelector('[data-action="undo"]').addEventListener('click', function() {
            if(aSteps.length)
                oCtx.putImageData(aSteps.pop(), 0, 0);
        });
        eWrap.querySelector('[data-action="cancel"]').addEventListener('click', function() {
            eWrap.remove();
        });
        eWrap.querySelector('.gf-bug-backdrop').addEventListener('click', function() {
            eWrap.remove();
        });
        eWrap.querySelector('[data-action="save"]').addEventListener('click', function() {
            eCanvas.toBlob(function(oBlob) {
                eWrap.remove();
                if(oBlob)
                    fnDone(oBlob);
            }, 'image/png');
        });
    }

    /*--- Submit -----------------------------------------------------------------*/

    function submitReport(ePanel) {
        var eSubmit = ePanel.querySelector('.gf-bug-submit');
        var sDescription = ePanel.querySelector('.gf-bug-desc').value.trim();
        if(sDescription === '')
            return;

        eSubmit.disabled = true;
        eSubmit.textContent = 'Submitting…';

        var oData = new FormData();
        oData.append('a', 'submit');
        oData.append('description', sDescription);
        var ePrio = ePanel.querySelector('.gf-bug-prio-on');
        oData.append('priority', ePrio ? ePrio.getAttribute('data-prio') : 'medium');
        oData.append('recording_url', ePanel.querySelector('.gf-bug-rec-url').value.trim());

        Object.keys(oState.meta).forEach(function(sKey) {
            oData.append(sKey, oState.meta[sKey]);
        });

        if(oState.shot)
            oData.append('screenshot', oState.shot, 'screenshot.png');
        oState.extras.forEach(function(oExtra, iIndex) {
            oData.append('extra_' + iIndex, oExtra.blob, 'extra_' + iIndex + '.png');
        });
        if(oState.recBlob)
            oData.append('recording', oState.recBlob, 'recording.webm');

        fetch(oCfg.endpoint, {method: 'POST', body: oData, credentials: 'same-origin'}).then(function(oResp) {
            return oResp.json();
        }).then(function(oResp) {
            if(oResp && oResp.code === 0) {
                closePanel(true);
                toast('Bug report submitted — thank you!');
            }
            else {
                eSubmit.disabled = false;
                eSubmit.textContent = 'Submit bug report';
                toast((oResp && oResp.msg) || 'Something went wrong — please try again.', true);
            }
        }).catch(function() {
            eSubmit.disabled = false;
            eSubmit.textContent = 'Submit bug report';
            toast('Something went wrong — please try again.', true);
        });
    }

    function toast(sMessage, bError) {
        var eToast = el('div', 'gf-bug-layer gf-bug-toast' + (bError ? ' gf-bug-toast-error' : ''), esc(sMessage));
        document.body.appendChild(eToast);
        requestAnimationFrame(function() { eToast.classList.add('gf-bug-toast-in'); });
        setTimeout(function() {
            eToast.classList.remove('gf-bug-toast-in');
            setTimeout(function() { eToast.remove(); }, 400);
        }, 4000);
    }

    /*--- Boot ----------------------------------------------------------------------*/

    function init() {
        var eLauncher = document.getElementById('gf-bug-launcher');
        if(!eLauncher)
            return;

        oCfg.endpoint = eLauncher.getAttribute('data-endpoint') || oCfg.endpoint;
        oCfg.html2canvas = eLauncher.getAttribute('data-html2canvas') || oCfg.html2canvas;
        oCfg.komodo = eLauncher.getAttribute('data-komodo') || oCfg.komodo;

        eLauncher.addEventListener('click', function() {
            if(panelEl() || document.querySelector('.gf-bug-banner')) // a report is already in progress
                return;

            var bSkipIntro = false;
            try { bSkipIntro = localStorage.getItem('gf_bug_skip_intro') === '1'; } catch(e) {}

            if(bSkipIntro)
                startPicker();
            else
                openIntro();
        });
    }

    if(document.readyState === 'loading')
        document.addEventListener('DOMContentLoaded', init);
    else
        init();
})();
