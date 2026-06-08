@extends('layouts.app')

@section('title', $room->name . ' — VR/AR Viewer')

@push('head')
    {{-- A-Frame: pinned version for predictable WebXR behaviour --}}
    <script src="https://aframe.io/releases/1.5.0/aframe.min.js"></script>
    {{-- Environment component: gives instant lighting/sky/ground so models are never black --}}
    <script src="https://unpkg.com/aframe-environment-component@1.3.2/dist/aframe-environment-component.min.js"></script>
    {{-- qrcode generator (client-side, no external calls) for desktop→mobile AR handoff --}}
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <style>
        /* keep A-Frame's own VR button out of the way; we use our dock */
        .a-enter-vr, .a-enter-ar { display: none !important; }
        a-scene { width: 100vw; height: 100vh; }
    </style>
@endpush

@section('body')
<div class="viewer-shell">

    {{-- ===== Loading overlay ===== --}}
    <div class="loader" id="loader">
        <div class="loader-card">
            <div class="spinner"></div>
            <h3>{{ $room->name }}</h3>
            <p id="loader-text">Preparing the space…</p>
            <div class="progress-track"><div class="progress-fill" id="progress-fill"></div></div>
        </div>
    </div>

    {{-- ===== Error / info notice (hidden by default) ===== --}}
    <div class="notice hidden" id="notice">
        <div id="notice-body"></div>
    </div>

    {{-- ===== AR QR handoff modal (hidden by default) ===== --}}
    <div class="qr-backdrop hidden" id="qr-backdrop">
        <div class="qr-modal">
            <button class="qr-close" id="qr-close" aria-label="Close">×</button>
            <h3>📱 View in AR on your phone</h3>
            <p>AR works on a phone or tablet. Scan this code with your camera to open
               <b>{{ $room->name }}</b> and place it in your space.</p>
            <div class="qr-canvas" id="qr-canvas"></div>
            <div class="qr-url" id="qr-url"></div>
            <p class="qr-foot">Make sure your phone is on the same network. On Android use
               Chrome (ARCore); on iOS, tap <b>View in AR</b> once the page loads.</p>
        </div>
    </div>

    {{-- ===== Top bar ===== --}}
    <div class="viewer-topbar">
        <a href="{{ route('rooms.index') }}" class="viewer-back">← Back to gallery</a>
        <div class="viewer-title">
            <div class="name">{{ $room->name }}</div>
            <div class="sub">{{ $room->tagline }}</div>
        </div>
    </div>

    {{-- ===== Control dock ===== --}}
    <div class="viewer-dock">
        <button class="dock-btn is-primary" id="btn-cardboard">🏠 Room View</button>
        <button class="dock-btn" id="btn-vr">🥽 Enter VR</button>
        <button class="dock-btn" id="btn-ar">📱 View in AR</button>
        <button class="dock-btn" id="btn-reset">⟳ Reset view</button>
    </div>

    {{-- Hint shown while in Cardboard/stereo mode --}}
    <div class="cardboard-hint hidden" id="cardboard-hint">
        Look at the floor and <b>tap to walk there</b>. Move your phone to look around.
        <button id="cardboard-exit">Exit</button>
    </div>

    {{-- ===== A-Frame scene ===== --}}
    <a-scene
        id="scene"
        loading-screen="enabled: false"
        gltf-model="dracoDecoderPath: https://www.gstatic.com/draco/versioned/decoders/1.5.6/;"
        renderer="antialias: true; colorManagement: true; physicallyCorrectLights: true; exposure: 1.0; toneMapping: ACESFilmic"
        webxr="optionalFeatures: hit-test, local-floor, bounded-floor, hand-tracking"
        device-orientation-permission-ui="enabled: true"
        vr-mode-ui="enabled: false">

        {{-- Asset preloading so we can track progress --}}
        <a-assets timeout="180000">
            @if ($room->model_exists)
                <a-asset-item id="room-asset" src="{{ $room->model_url }}"></a-asset-item>
            @endif
        </a-assets>

        {{-- Lighting: ambient fill + key directional so GLB materials are visible --}}
        <a-entity light="type: ambient; intensity: 0.7; color: #ffffff"></a-entity>
        <a-entity light="type: directional; intensity: 0.9; castShadow: true"
                  position="3 8 5"></a-entity>
        <a-entity light="type: hemisphere; intensity: 0.6; color: #ffffff; groundColor: #d8d0ec"></a-entity>

        {{-- Environment (sky + soft ground). Hidden automatically in AR by passthrough.
             Pastel palette to match the flat/light website theme. --}}
        <a-entity id="env"
                  environment="preset: default; lighting: none; shadow: false; ground: flat;
                               groundColor: #e6d8c4; groundColor2: #ece1d1;
                               skyType: gradient; skyColor: #f1e8db; horizonColor: #fae6d8;
                               fog: 0.15; dressing: none; grid: none">
        </a-entity>

        {{-- ===== The Blender room model ===== --}}
        @if ($room->model_exists)
            <a-entity
                id="room-model"
                gltf-model="#room-asset"
                position="{{ $room->transform['position'] }}"
                rotation="{{ $room->transform['rotation'] }}"
                scale="{{ $room->transform['scale'] }}"
                shadow="receive: true; cast: true">
            </a-entity>
        @endif

        {{-- ===== Procedural fallback room (shown if no/failed model) ===== --}}
        <a-entity id="fallback-room" visible="{{ $room->model_exists ? 'false' : 'true' }}">
            {{-- floor --}}
            <a-plane position="0 0 0" rotation="-90 0 0" width="12" height="12"
                     material="color: #e3d4bf; roughness: 0.95; metalness: 0"></a-plane>
            {{-- back wall --}}
            <a-plane position="0 2 -6" width="12" height="4"
                     material="color: #f3ebdd; side: double"></a-plane>
            {{-- left/right walls --}}
            <a-plane position="-6 2 0" rotation="0 90 0" width="12" height="4"
                     material="color: #ede2d0; side: double"></a-plane>
            <a-plane position="6 2 0" rotation="0 -90 0" width="12" height="4"
                     material="color: #ede2d0; side: double"></a-plane>
            {{-- meeting table --}}
            <a-box position="0 0.75 -1" width="3.2" height="0.12" depth="1.4"
                   material="color: {{ $room->accent }}; roughness: 0.5; metalness: 0.1"></a-box>
            <a-cylinder position="0 0.37 -1" radius="0.18" height="0.75"
                        material="color: #d8c3a6"></a-cylinder>
            {{-- chairs --}}
            <a-box position="-1 0.45 0" width="0.5" height="0.5" depth="0.5" material="color: #e0d0b8"></a-box>
            <a-box position="1 0.45 0"  width="0.5" height="0.5" depth="0.5" material="color: #e0d0b8"></a-box>
            <a-box position="-1 0.45 -2" width="0.5" height="0.5" depth="0.5" material="color: #e0d0b8"></a-box>
            <a-box position="1 0.45 -2"  width="0.5" height="0.5" depth="0.5" material="color: #e0d0b8"></a-box>
            {{-- display wall --}}
            <a-plane position="0 2 -5.9" width="4" height="2.2"
                     material="color: #fbf6ee; emissive: {{ $room->accent }}; emissiveIntensity: 0.1"></a-plane>
            <a-text value="PLACEHOLDER ROOM\nDrop your .glb to replace"
                    align="center" position="0 2 -5.85" width="6" color="#978a7e"></a-text>
        </a-entity>

        {{-- ===== Compact AR placeholder (shown ONLY in AR when no real model) =====
             A small tabletop diorama placed ~1m in front of the user so you can
             confirm AR works even before the .glb assets exist. The viewer JS
             shows/hides + positions this on AR enter/exit. --}}
        <a-entity id="ar-placeholder" visible="false" position="0 -0.4 -1" scale="0.18 0.18 0.18">
            {{-- base slab --}}
            <a-box position="0 0 0" width="5" height="0.2" depth="5"
                   material="color: #20203a; roughness: 0.8"></a-box>
            {{-- table --}}
            <a-box position="0 0.95 0" width="3.2" height="0.12" depth="1.4"
                   material="color: {{ $room->accent }}; metalness: 0.3; roughness: 0.4"></a-box>
            <a-cylinder position="0 0.55 0" radius="0.18" height="0.75" material="color: #15151f"></a-cylinder>
            {{-- chairs --}}
            <a-box position="-1 0.55 1" width="0.5" height="0.5" depth="0.5" material="color: #3a3a5c"></a-box>
            <a-box position="1 0.55 1"  width="0.5" height="0.5" depth="0.5" material="color: #3a3a5c"></a-box>
            <a-box position="-1 0.55 -1" width="0.5" height="0.5" depth="0.5" material="color: #3a3a5c"></a-box>
            <a-box position="1 0.55 -1"  width="0.5" height="0.5" depth="0.5" material="color: #3a3a5c"></a-box>
            {{-- spinning marker so it's obviously "live" --}}
            <a-octahedron position="0 2.2 0" radius="0.45"
                          material="color: {{ $room->accent }}; emissive: {{ $room->accent }}; emissiveIntensity: 0.4"
                          animation="property: rotation; to: 0 360 0; loop: true; dur: 4000; easing: linear"></a-octahedron>
            <a-text value="AR PREVIEW\n(placeholder — add .glb)" align="center"
                    position="0 3 0" width="10" color="#ffffff"></a-text>
        </a-entity>

        {{-- ===== Camera rig (walk + look). Works on desktop, mobile gyro, and VR. =====
             Movement model (kept simple to avoid controls fighting each other):
               • rig  -> holds position; wasd-controls walks it (desktop W/A/S/D + arrows)
               • head -> camera; look-controls handles mouse-drag + phone gyro look
               • cursor -> gaze ray for tap / look-to-walk teleport
        --}}
        <a-entity id="rig"
                  position="{{ $room->spawn['position'] }}"
                  rotation="{{ $room->spawn['rotation'] }}"
                  wasd-controls="acceleration: 18; fly: false">
            <a-entity id="head" camera
                      look-controls="reverseMouseDrag: false; touchEnabled: true; magicWindowTrackingEnabled: true"
                      position="0 0 0">
                {{-- Gaze cursor. Raycasts the teleport floor (and any .clickable).
                     fuse (gaze-dwell) is turned on by the JS only in Room View. --}}
                <a-cursor id="cursor"
                          color="{{ $room->accent }}"
                          raycaster="objects: .teleport-floor, .clickable; far: 40"
                          fuse="false" fuse-timeout="1100"
                          material="opacity: 0.9"
                          geometry="primitive: ring; radiusInner: 0.012; radiusOuter: 0.02"
                          position="0 0 -1"></a-cursor>
            </a-entity>
            {{-- VR hand controllers (auto-detected on headsets) --}}
            <a-entity laser-controls="hand: left"  raycaster="objects: .teleport-floor, .clickable"></a-entity>
            <a-entity laser-controls="hand: right" raycaster="objects: .teleport-floor, .clickable"></a-entity>
        </a-entity>

        {{-- Invisible teleport floor: a large plane the gaze cursor can hit so
             "look + tap" moves you anywhere on the ground. --}}
        <a-plane class="teleport-floor" rotation="-90 0 0" width="60" height="60"
                 position="0 0 0" material="opacity: 0; transparent: true; side: double"
                 visible="true"></a-plane>

        {{-- Visual marker showing where a teleport will land. --}}
        <a-entity id="teleport-marker" visible="false" position="0 0.02 0">
            <a-ring rotation="-90 0 0" radius-inner="0.25" radius-outer="0.35"
                    material="color: {{ $room->accent }}; shader: flat; opacity: 0.9"></a-ring>
            <a-ring rotation="-90 0 0" radius-inner="0.02" radius-outer="0.06"
                    material="color: {{ $room->accent }}; shader: flat; opacity: 0.9"></a-ring>
        </a-entity>

    </a-scene>
</div>
@endsection

@push('scripts')
<script>
    window.ROOM_CONFIG = {
        slug:        @json($room->slug),
        modelUrl:    @json($room->model_url),
        modelFile:   @json($room->model),
        modelExists: @json((bool) $room->model_exists),
        spawn:       @json($room->spawn),
    };
</script>
<script src="{{ asset('assets/js/room-viewer.js') }}"></script>
@endpush
