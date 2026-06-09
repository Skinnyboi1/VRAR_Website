@extends('layouts.app')

@section('title', 'Immersive Meeting Rooms — VR & AR Showcase')

@section('body')
<nav class="nav">
    <div class="container nav-inner">
        <a href="{{ route('rooms.index') }}" class="brand">
            <span class="logo">◈</span>
            <span>Immersa<span style="color:var(--muted)">Rooms</span></span>
        </a>
        <div class="nav-links">
            <a href="#gallery">Gallery</a>
            <a href="#how">How it works</a>
        </div>
    </div>
</nav>

{{-- ===== Cinematic hero ===== --}}
<header class="x-hero">
    <div class="x-hero-bg"></div>
    <div class="x-hero-inner">
        <span class="x-eyebrow">IMMERSIVE MEETING ROOMS</span>
        <h1 class="x-hero-title">The Collection</h1>
        <p class="x-hero-sub">{{ $rooms->count() }} architectural spaces, modeled in Blender.<br>Step inside in VR, AR, or 360° &mdash; straight from your browser.</p>
        <div class="x-hero-cta">
            <a href="#room-{{ $rooms->first()->slug ?? '' }}" class="x-btn x-btn-primary">Explore rooms ↓</a>
            <a href="#how" class="x-btn x-btn-ghost">How it works</a>
        </div>
        <div class="x-hero-modes">
            <span>🥽 VR Headset</span><span>📦 Cardboard</span><span>📱 AR</span><span>🌀 360°</span>
        </div>
    </div>
</header>

<main>
    {{-- ===== One full-bleed showcase band per room (alternating) ===== --}}
    @foreach ($rooms as $i => $room)
        <section class="x-showcase {{ $i % 2 === 1 ? 'reverse' : '' }}"
                 id="room-{{ $room->slug }}"
                 style="--room-accent: {{ $room->accent }}">
            <div class="x-showcase-inner">

                {{-- Visual / preview --}}
                <div class="x-preview">
                    <div class="x-preview-frame">
                        @if ($room->thumbnail_url)
                            <img src="{{ $room->thumbnail_url }}" alt="{{ $room->name }}" loading="lazy">
                        @else
                            <div class="x-preview-art">
                                <span class="x-preview-ico">🏛️</span>
                                <span class="x-preview-tag">Live 3D · enter to view</span>
                            </div>
                        @endif
                    </div>
                    <div class="x-preview-badges">
                        <span class="x-mode-pill">VR</span>
                        <span class="x-mode-pill">AR</span>
                        <span class="x-mode-pill">360°</span>
                    </div>
                </div>

                {{-- Copy + specs --}}
                <div class="x-copy">
                    <span class="x-copy-eyebrow">{{ str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) }} / {{ str_pad((string)$rooms->count(), 2, '0', STR_PAD_LEFT) }}</span>
                    <h2 class="x-copy-title">{{ $room->name }}</h2>
                    <p class="x-copy-tagline">{{ $room->tagline }}</p>
                    <p class="x-copy-desc">{{ $room->description }}</p>

                    {{-- Big spec callouts (Xiaomi-style numbers) --}}
                    <div class="x-specs">
                        <div class="x-spec">
                            <span class="x-spec-num">{{ $room->capacity }}</span>
                            <span class="x-spec-label">Seats</span>
                        </div>
                        @if (!empty($room->area))
                        <div class="x-spec">
                            <span class="x-spec-num">{{ $room->area }}<small>m²</small></span>
                            <span class="x-spec-label">Floor area</span>
                        </div>
                        @endif
                        <div class="x-spec">
                            <span class="x-spec-num">{{ count($room->features) }}</span>
                            <span class="x-spec-label">Features</span>
                        </div>
                    </div>

                    {{-- Feature pills --}}
                    <div class="x-feature-pills">
                        @foreach ($room->features as $feature)
                            <span class="x-pill">{{ $feature }}</span>
                        @endforeach
                    </div>

                    <a href="{{ route('rooms.show', $room->slug) }}" class="x-btn x-btn-primary x-enter">
                        Enter {{ $room->name }} →
                    </a>
                </div>
            </div>
        </section>
    @endforeach

    {{-- ===== How it works ===== --}}
    <section id="how" class="x-how">
        <div class="x-how-inner">
            <span class="x-eyebrow dark">FOUR WAYS TO EXPLORE</span>
            <h2 class="x-how-title">One scan. Every device.</h2>
            <div class="x-how-grid">
                <div class="x-how-card">
                    <span class="x-how-ico">📦</span>
                    <h3>Cardboard VR</h3>
                    <p>Tap <b>Room View</b> — the screen splits into two eyes. Look at the floor and tap to walk. Drop the phone in a Cardboard viewer.</p>
                </div>
                <div class="x-how-card">
                    <span class="x-how-ico">🥽</span>
                    <h3>Headset VR</h3>
                    <p>On a Meta Quest or any WebXR headset, tap <b>Enter VR</b> and walk the space at true scale.</p>
                </div>
                <div class="x-how-card">
                    <span class="x-how-ico">📱</span>
                    <h3>Augmented Reality</h3>
                    <p>On a supported phone, tap <b>View in AR</b> to place the room in your real surroundings.</p>
                </div>
                <div class="x-how-card">
                    <span class="x-how-ico">🌀</span>
                    <h3>360° Magic Window</h3>
                    <p>No viewer? Move your phone — or drag with a mouse — to look around from inside.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="footer">
    <div class="container">
        Built with Laravel + A-Frame · WebXR immersive showcase ·
        Models authored in Blender.
    </div>
</footer>

{{-- ===== Always-visible "Open on your phone" QR sidebar ===== --}}
<aside class="qr-sidebar" id="qr-sidebar">
    <button class="qr-sidebar-toggle" id="qr-sidebar-toggle" aria-label="Toggle QR">📱</button>
    <div class="qr-sidebar-inner">
        <h4>Open on your phone</h4>
        <div class="qr-canvas" id="site-qr-canvas"></div>
        <p class="qr-sidebar-hint">Scan with your camera to explore in&nbsp;VR,&nbsp;AR&nbsp;&amp;&nbsp;360°.</p>
        <div class="qr-url" id="site-qr-url"></div>
    </div>
</aside>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<script src="{{ asset('assets/js/site-qr.js') }}"></script>
@endpush
