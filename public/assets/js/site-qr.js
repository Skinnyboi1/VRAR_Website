/* ============================================================
   Site QR sidebar — always-visible "scan to open on your phone".
   Renders on load; prefers the live ngrok tunnel URL (asked from
   Laravel) so the QR is phone-reachable, else the current origin.
   ============================================================ */
(function () {
    'use strict';

    var sidebar = document.getElementById('qr-sidebar');
    var toggle  = document.getElementById('qr-sidebar-toggle');
    var canvas  = document.getElementById('site-qr-canvas');
    var urlEl   = document.getElementById('site-qr-url');

    if (!sidebar || !canvas) return;

    function renderQR(url) {
        canvas.innerHTML = '';
        if (typeof qrcode === 'function') {
            try {
                var qr = qrcode(0, 'M');
                qr.addData(url);
                qr.make();
                canvas.innerHTML = qr.createImgTag(5, 6);
            } catch (e) {
                canvas.textContent = 'QR error';
                console.error('[site-qr] generation failed', e);
            }
        } else {
            canvas.textContent = 'QR library not loaded';
        }
    }

    function show(url) {
        if (urlEl) urlEl.textContent = url.replace(/^https?:\/\//, '');
        renderQR(url);
    }

    // Render immediately with the current origin, then upgrade to tunnel URL.
    show(window.location.origin + '/');

    fetch('/api/tunnel-home', { headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
            if (data && data.url) {
                show(data.url);
                console.log('[site-qr] using tunnel URL:', data.url);
            }
        })
        .catch(function () { /* keep fallback */ });

    // Solidify the nav bar once the user scrolls past the hero.
    var nav = document.querySelector('.nav');
    if (nav) {
        var onScroll = function () {
            if (window.scrollY > window.innerHeight * 0.7) nav.classList.add('scrolled');
            else nav.classList.remove('scrolled');
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    // Collapse / expand (remembered for the session).
    if (toggle) {
        var collapsed = sessionStorage.getItem('qrCollapsed') === '1';
        if (collapsed) sidebar.classList.add('collapsed');
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            sessionStorage.setItem('qrCollapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
        });
    }
})();
