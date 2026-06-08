/* ============================================================
   Room Viewer — A-Frame / WebXR controller
   Handles: model load progress, error fallbacks, AR session,
   mode switching, and feature detection.
   Config is injected via window.ROOM_CONFIG (see show.blade.php).
   ============================================================ */
(function () {
    'use strict';

    var cfg = window.ROOM_CONFIG || {};
    var els = {
        scene:        document.getElementById('scene'),
        modelEntity:  document.getElementById('room-model'),
        fallback:     document.getElementById('fallback-room'),
        loader:       document.getElementById('loader'),
        progressFill: document.getElementById('progress-fill'),
        loaderText:   document.getElementById('loader-text'),
        notice:       document.getElementById('notice'),
        noticeBody:   document.getElementById('notice-body'),
        btnVR:        document.getElementById('btn-vr'),
        btnAR:        document.getElementById('btn-ar'),
        btnReset:     document.getElementById('btn-reset'),
        qrBackdrop:   document.getElementById('qr-backdrop'),
        qrCanvas:     document.getElementById('qr-canvas'),
        qrUrl:        document.getElementById('qr-url'),
        qrClose:      document.getElementById('qr-close'),
        btnCardboard: document.getElementById('btn-cardboard'),
        cardboardHint:document.getElementById('cardboard-hint'),
        cardboardExit:document.getElementById('cardboard-exit'),
        cursor:       document.getElementById('cursor'),
        teleMarker:   document.getElementById('teleport-marker'),
        rig:          document.getElementById('rig'),
    };

    var isMobile = /Android|iPhone|iPad|iPod|Mobile|Silk/i.test(navigator.userAgent);

    // On desktop the AR button hands off to a phone, so label it accordingly.
    if (!isMobile && els.btnAR) {
        els.btnAR.textContent = '📱 AR via phone (QR)';
    }

    function hideLoader() {
        if (els.loader) els.loader.classList.add('hidden');
    }
    function setProgress(pct, label) {
        if (els.progressFill) els.progressFill.style.width = pct + '%';
        if (label && els.loaderText) els.loaderText.textContent = label;
    }
    function showNotice(html) {
        if (!els.notice) return;
        els.noticeBody.innerHTML = html;
        els.notice.classList.remove('hidden');
    }

    /* ---------- show procedural fallback room ---------- */
    function showFallbackRoom(reason) {
        if (els.modelEntity) els.modelEntity.setAttribute('visible', 'false');
        if (els.fallback)    els.fallback.setAttribute('visible', 'true');
        hideLoader();
        console.warn('[room-viewer] Using fallback room. Reason:', reason);
    }

    /* ============================================================
       1. Decide: real model or fallback?
       ============================================================ */
    if (!cfg.modelExists) {
        // No GLB on disk yet — render the procedural placeholder room
        // so VR/AR still works and nothing looks broken.
        showFallbackRoom('model file not present on server');
    } else if (els.modelEntity) {
        // Drive a loading bar off the asset item, then swap to gltf-model.
        var assetItem = document.getElementById('room-asset');

        var loaded = false;
        // Generous timeout — large models over a tunnel can take a while.
        var safety = setTimeout(function () {
            if (!loaded) {
                showFallbackRoom('load timeout (model took too long / failed silently)');
            }
        }, 180000); // 3 minutes

        if (assetItem) {
            assetItem.addEventListener('progress', function (e) {
                if (e.detail && e.detail.loadedBytes && e.detail.totalBytes) {
                    var pct = Math.round((e.detail.loadedBytes / e.detail.totalBytes) * 100);
                    setProgress(Math.min(pct, 99), 'Loading room… ' + pct + '%');
                }
            });
        }

        els.modelEntity.addEventListener('model-loaded', function () {
            loaded = true;
            clearTimeout(safety);
            setProgress(100, 'Ready');
            // Small delay so the 100% reads before fade-out.
            setTimeout(hideLoader, 350);
            console.log('[room-viewer] Model loaded:', cfg.modelUrl);
        });

        els.modelEntity.addEventListener('model-error', function (e) {
            loaded = true; // stop the safety timer path
            clearTimeout(safety);
            console.error('[room-viewer] model-error', e.detail);
            showFallbackRoom('GLTF parse/load error');
            showNotice(
                '<h3>Couldn\'t load this model</h3>' +
                '<p>The file <code>' + (cfg.modelFile || 'model.glb') + '</code> exists but failed to load. ' +
                'This usually means it isn\'t a valid <b>.glb</b> (glTF binary) export.</p>' +
                '<div class="hint"><b>Fix in Blender:</b> File ▸ Export ▸ glTF 2.0, set ' +
                '<b>Format = glTF Binary (.glb)</b>, enable <b>+Y Up</b>, and include ' +
                'Materials &amp; Textures. Re-drop it at <code>public/assets/models/' +
                (cfg.modelFile || 'model.glb') + '</code>.</div>'
            );
        });
    } else {
        showFallbackRoom('no model entity in DOM');
    }

    /* Scene fully attached — if model already cached/loaded, ensure loader gone. */
    if (els.scene) {
        els.scene.addEventListener('loaded', function () {
            if (!cfg.modelExists) hideLoader();
        });
    }

    /* ============================================================
       2. VR / AR buttons
       ============================================================ */
    function whenSceneReady(fn) {
        if (els.scene && els.scene.hasLoaded) fn();
        else if (els.scene) els.scene.addEventListener('loaded', fn);
    }

    // Whether this very device can run a native AR session.
    var nativeARSupported = false;

    // Feature detection for VR/AR availability.
    whenSceneReady(function () {
        if (!navigator.xr) {
            disable(els.btnVR, 'VR not supported on this device/browser');
            // Note: we do NOT disable the AR button — on desktop it shows a QR
            // so the user can continue on their phone.
            return;
        }
        navigator.xr.isSessionSupported('immersive-vr').then(function (ok) {
            if (!ok) disable(els.btnVR, 'No VR headset detected');
        }).catch(function () { disable(els.btnVR, 'VR check failed'); });

        navigator.xr.isSessionSupported('immersive-ar').then(function (ok) {
            nativeARSupported = !!ok;
        }).catch(function () { nativeARSupported = false; });
    });

    function disable(btn, title) {
        if (!btn) return;
        btn.setAttribute('disabled', 'disabled');
        btn.title = title;
    }

    if (els.btnVR) {
        els.btnVR.addEventListener('click', function () {
            try { els.scene.enterVR(); }
            catch (err) {
                showNotice('<h3>VR unavailable</h3><p>This browser/device can\'t start a VR session. ' +
                    'Try a Meta Quest browser or a WebXR-capable desktop browser with a headset connected.</p>');
            }
        });
    }

    function arUnsupportedNotice(extra) {
        var iOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);
        var html = '<h3>AR can\'t start on this device</h3>';
        if (iOS) {
            html += '<p><b>iPhone/iPad Safari does not support WebXR AR</b> — this is an Apple ' +
                'limitation, not a bug in the site. The room still works in the 360° view ' +
                '(just move your phone to look around). For true AR on iOS you\'d need a ' +
                'USDZ/Quick Look export instead.</p>';
        } else {
            html += '<p>Your browser reported no WebXR AR support. On Android: use <b>Google Chrome</b> ' +
                '(not a webview/in-app browser), and install/update <b>"Google Play Services for AR"</b> ' +
                '(ARCore) from the Play Store. Then reopen this page.</p>';
        }
        if (extra) html += '<div class="hint">' + extra + '</div>';
        showNotice(html);
    }

    function launchAR() {
        if (!els.scene.enterAR) {
            // Very old A-Frame — try the legacy path, else explain.
            try { els.scene.enterVR(true); } catch (e) { arUnsupportedNotice(); }
            return;
        }
        // enterAR() returns a promise in A-Frame 1.5; catch async rejections so
        // the button never "silently does nothing".
        var p;
        try { p = els.scene.enterAR(); }
        catch (err) { arUnsupportedNotice(String(err && err.message || err)); return; }

        if (p && typeof p.catch === 'function') {
            p.catch(function (err) {
                console.error('[room-viewer] enterAR rejected', err);
                arUnsupportedNotice(String(err && err.message || err));
            });
        }
    }

    if (els.btnAR) {
        els.btnAR.addEventListener('click', function () {
            if (isMobile) {
                // On a phone: check support first, then launch (or explain why not).
                if (navigator.xr) {
                    navigator.xr.isSessionSupported('immersive-ar').then(function (ok) {
                        if (ok) launchAR();
                        else arUnsupportedNotice();
                    }).catch(function () { arUnsupportedNotice(); });
                } else {
                    arUnsupportedNotice('navigator.xr is missing — the browser has no WebXR at all.');
                }
            } else if (nativeARSupported) {
                launchAR();
            } else {
                // Desktop without AR: hand off to the user's phone via QR.
                openQRModal();
            }
        });
    }

    /* ============================================================
       QR handoff: render a QR pointing at a phone-reachable URL so the
       phone can open the room and run AR. Prefers the live ngrok tunnel
       URL (asked from Laravel), falling back to this page's URL.
       ============================================================ */
    function renderQR(url) {
        if (!els.qrCanvas) return;
        els.qrCanvas.innerHTML = '';
        if (typeof qrcode === 'function') {
            try {
                var qr = qrcode(0, 'M');       // type 0 = auto-size, error level M
                qr.addData(url);
                qr.make();
                els.qrCanvas.innerHTML = qr.createImgTag(6, 8); // (cellSize, margin)
            } catch (e) {
                els.qrCanvas.textContent = 'QR error';
                console.error('[room-viewer] QR generation failed', e);
            }
        } else {
            els.qrCanvas.textContent = 'QR library not loaded';
        }
    }

    function setQRWarning(url, viaTunnel) {
        var existing = document.getElementById('qr-warn');
        if (existing) existing.remove();
        if (!els.qrUrl) return;

        var isLocalOnly = /^https?:\/\/(localhost|127\.0\.0\.1|\[?::1\]?)/i.test(url);
        var isHttps = /^https:/i.test(url);
        var msgs = [];

        if (viaTunnel) {
            // Tunnel URL is public + https — good to go.
            return;
        }
        if (isLocalOnly) {
            msgs.push('This URL points to <b>localhost</b>, which your phone can\'t reach. ' +
                'Start the ngrok tunnel (see <code>start-showcase</code>) and reopen this — ' +
                'the QR will switch to the public address automatically.');
        } else if (!isHttps) {
            msgs.push('AR requires <b>HTTPS</b>. Serve over https (or use the ngrok tunnel) for AR to start on the phone.');
        }
        if (msgs.length) {
            var note = document.createElement('div');
            note.id = 'qr-warn';
            note.className = 'qr-warn';
            note.innerHTML = msgs.join('<br><br>');
            els.qrUrl.insertAdjacentElement('afterend', note);
        }
    }

    function showQR(url, viaTunnel) {
        if (els.qrUrl) els.qrUrl.textContent = url;
        setQRWarning(url, viaTunnel);
        renderQR(url);
    }

    function openQRModal() {
        if (!els.qrBackdrop) return;

        // Show immediately with the current page URL, then upgrade to the
        // tunnel URL if ngrok is running.
        showQR(window.location.href, false);
        els.qrBackdrop.classList.remove('hidden');

        if (cfg.slug) {
            fetch('/api/tunnel/' + encodeURIComponent(cfg.slug), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (data && data.url) {
                        showQR(data.url, true);   // public https tunnel URL
                        console.log('[room-viewer] QR using tunnel URL:', data.url);
                    }
                })
                .catch(function () { /* keep the fallback URL */ });
        }
    }

    function closeQRModal() {
        if (els.qrBackdrop) els.qrBackdrop.classList.add('hidden');
    }

    if (els.qrClose) els.qrClose.addEventListener('click', closeQRModal);
    if (els.qrBackdrop) {
        els.qrBackdrop.addEventListener('click', function (e) {
            if (e.target === els.qrBackdrop) closeQRModal(); // click outside modal
        });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeQRModal();
    });

    /* ============================================================
       4. AR passthrough: hide sky/ground/fallback walls in AR so the
          real-world camera feed shows, then restore them on exit.
       ============================================================ */
    var env = document.getElementById('env');
    var arPlaceholder = document.getElementById('ar-placeholder');
    var arHidden = [];

    function enterARMode() {
        // Hide the virtual sky/ground + the big walled placeholder room so the
        // phone's camera passthrough shows through.
        arHidden = [];
        [env, els.fallback].forEach(function (el) {
            if (el && el.getAttribute('visible') !== false &&
                el.getAttribute('visible') !== 'false') {
                arHidden.push(el);
                el.setAttribute('visible', 'false');
            }
        });

        if (cfg.modelExists && els.modelEntity) {
            // Real room: make sure it's visible and sitting in front of the user.
            els.modelEntity.setAttribute('visible', 'true');
        } else if (arPlaceholder) {
            // No model yet: show the compact AR diorama so AR is never empty.
            arPlaceholder.setAttribute('visible', 'true');
        }
    }

    function exitARMode() {
        arHidden.forEach(function (el) { el.setAttribute('visible', 'true'); });
        arHidden = [];
        if (arPlaceholder) arPlaceholder.setAttribute('visible', 'false');
    }

    if (els.scene) {
        els.scene.addEventListener('enter-vr', function () {
            if (els.scene.is('ar-mode')) enterARMode();
        });
        els.scene.addEventListener('exit-vr', function () {
            exitARMode();
        });
    }

    /* ============================================================
       3. Reset view (recenters camera rig to spawn)
       ============================================================ */
    if (els.btnReset) {
        els.btnReset.addEventListener('click', function () {
            var rig = document.getElementById('rig');
            if (rig && cfg.spawn) {
                rig.setAttribute('position', cfg.spawn.position || '0 1.6 4');
                rig.setAttribute('rotation', cfg.spawn.rotation || '0 0 0');
            }
            var cam = document.querySelector('#rig [camera]');
            if (cam) cam.setAttribute('rotation', '0 0 0');
        });
    }

    /* Close notice on click anywhere on it */
    if (els.notice) {
        els.notice.addEventListener('click', function () {
            els.notice.classList.add('hidden');
        });
    }

    /* ============================================================
       5. Gaze-to-move teleport (works on desktop, phone, Cardboard, VR).
          The cursor raycasts the invisible .teleport-floor; we show a
          marker at the hit point, and a click/tap/fuse moves the rig there.
       ============================================================ */
    var lastHit = null; // {x, y, z} world point on the floor under the reticle

    function showMarker(pt) {
        lastHit = { x: pt.x, y: pt.y, z: pt.z };
        if (els.teleMarker) {
            els.teleMarker.object3D.position.set(pt.x, pt.y + 0.02, pt.z);
            els.teleMarker.setAttribute('visible', 'true');
        }
    }
    function hideMarker() {
        lastHit = null;
        if (els.teleMarker) els.teleMarker.setAttribute('visible', 'false');
    }

    function teleportToHit() {
        if (!lastHit || !els.rig) return;
        var cur = els.rig.getAttribute('position');
        // Only move across the floor (x/z); keep the rig's standing height.
        els.rig.setAttribute('position', { x: lastHit.x, y: cur.y, z: lastHit.z });
    }

    // Continuously read the cursor's raycaster each frame to follow the floor
    // hit point under the reticle. Polling the raycaster directly is far more
    // reliable across desktop/phone/VR than the enter/leave events alone.
    function startTeleportTracking() {
        if (!els.cursor) return;
        setInterval(function () {
            var ray = els.cursor.components && els.cursor.components.raycaster;
            if (!ray) return;
            var hits = ray.intersections || [];
            var floorHit = null;
            for (var i = 0; i < hits.length; i++) {
                var el = hits[i].object && hits[i].object.el;
                if (el && el.classList && el.classList.contains('teleport-floor')) {
                    floorHit = hits[i]; break;
                }
            }
            if (floorHit && floorHit.point) showMarker(floorHit.point);
            else hideMarker();
        }, 60); // ~16fps marker update — smooth enough, cheap
    }

    // Teleport on any cursor click (mouse click, screen tap, or gaze-fuse).
    if (els.cursor) {
        els.cursor.addEventListener('click', teleportToHit);
    }
    // Also accept a direct screen tap anywhere (mobile), mapping to the reticle.
    if (els.scene) {
        els.scene.addEventListener('loaded', startTeleportTracking);
    }

    /* ============================================================
       6. Cardboard VR — stereoscopic split-screen + gyro for a phone in
          a Google Cardboard viewer. Uses A-Frame's magic-window/stereo
          VR fallback when WebXR immersive-vr isn't available.
       ============================================================ */
    function requestOrientationPermission() {
        // iOS 13+ needs an explicit gesture-triggered permission for gyro.
        var DOE = window.DeviceOrientationEvent;
        if (DOE && typeof DOE.requestPermission === 'function') {
            return DOE.requestPermission().catch(function () { /* ignore */ });
        }
        return Promise.resolve();
    }

    function setFuse(enabled) {
        if (!els.cursor) return;
        // In Cardboard there's no reliable tap, so enable gaze-fuse selection.
        els.cursor.setAttribute('cursor', 'fuse', enabled);
        if (enabled) els.cursor.setAttribute('cursor', 'fuseTimeout', 1200);
    }

    function enterCardboard() {
        requestOrientationPermission().then(function () {
            setFuse(true); // hands-free gaze selection
            try {
                els.scene.enterVR();   // no WebXR headset -> stereo + magic window
            } catch (e) {
                showNotice('<h3>Couldn\'t start Cardboard mode</h3>' +
                    '<p>Your browser blocked fullscreen/VR. Make sure you opened this in ' +
                    '<b>Chrome</b> (Android) over HTTPS, then try again.</p>');
                setFuse(false);
            }
        });
    }

    if (els.btnCardboard) {
        els.btnCardboard.addEventListener('click', enterCardboard);
    }
    if (els.cardboardExit) {
        els.cardboardExit.addEventListener('click', function () {
            try { els.scene.exitVR(); } catch (e) {}
        });
    }

    // Show/hide the Cardboard hint with stereo VR (but not AR or headset VR).
    if (els.scene) {
        els.scene.addEventListener('enter-vr', function () {
            // Only show the gaze hint when we're in the non-AR (VR/stereo) mode.
            if (!els.scene.is('ar-mode') && els.cardboardHint) {
                els.cardboardHint.classList.remove('hidden');
            }
        });
        els.scene.addEventListener('exit-vr', function () {
            if (els.cardboardHint) els.cardboardHint.classList.add('hidden');
            setFuse(false);
            if (els.teleMarker) els.teleMarker.setAttribute('visible', 'false');
        });
    }
})();
