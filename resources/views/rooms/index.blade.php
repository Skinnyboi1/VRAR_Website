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

<header class="hero">
    <div class="container">
        <span class="eyebrow"><span class="dot"></span> WebXR · No app install required</span>
        <h1>Step inside your <span class="grad">meeting rooms</span><br>in VR &amp; AR.</h1>
        <p class="lead">
            A showcase of architectural meeting spaces modeled in Blender — explore them in
            immersive virtual reality, place them in your own room with AR, or look around
            right from your phone.
        </p>
        <div class="hero-badges">
            <span class="badge">🥽 <b>VR</b> headset ready</span>
            <span class="badge">📱 <b>AR</b> on mobile</span>
            <span class="badge">🌀 <b>360°</b> magic window</span>
            <span class="badge">⚡ Built on <b>A-Frame</b> + WebXR</span>
        </div>
    </div>
</header>

<main class="container">
    <section id="gallery">
        <div class="section-head">
            <div>
                <h2>The Collection</h2>
                <p>{{ $rooms->count() }} curated spaces — click any room to enter.</p>
            </div>
        </div>

        <div class="grid">
            @foreach ($rooms as $room)
                <article class="card">
                    <div class="card-media">
                        @if ($room->thumbnail_url)
                            <img src="{{ $room->thumbnail_url }}" alt="{{ $room->name }}" loading="lazy">
                        @else
                            <div class="preview-fallback">
                                <div class="ico">🏛️</div>
                            </div>
                        @endif

                        <div class="card-tags">
                            <span class="tag vr">VR</span>
                            <span class="tag ar">AR</span>
                        </div>

                        @if ($room->model_exists)
                            <span class="status-pill ready">● Ready</span>
                        @else
                            <span class="status-pill pending">● Awaiting model</span>
                        @endif

                        <div class="accent-bar" style="background: linear-gradient(90deg, {{ $room->accent }}, transparent)"></div>
                    </div>

                    <div class="card-body">
                        <h3>{{ $room->name }}</h3>
                        <p class="tagline">{{ $room->tagline }}</p>

                        <div class="meta-row">
                            <span class="meta">👥 Seats {{ $room->capacity }}</span>
                            <span class="meta">📦 GLB model</span>
                        </div>

                        <div class="feature-chips">
                            @foreach ($room->features as $feature)
                                <span class="chip">{{ $feature }}</span>
                            @endforeach
                        </div>

                        <a href="{{ route('rooms.show', $room->slug) }}" class="btn btn-primary">
                            Enter room →
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section id="how">
        <div class="section-head">
            <div>
                <h2>How it works</h2>
                <p>One scan — four ways to explore.</p>
            </div>
        </div>
        <div class="grid">
            <article class="card"><div class="card-body">
                <h3>📦 Google Cardboard VR</h3>
                <p class="tagline">Got a cheap Cardboard viewer? Tap <b>Cardboard VR</b> — the screen splits into two eyes and you look around by moving your head. <b>Look at a spot and tap to walk there.</b></p>
            </div></article>
            <article class="card"><div class="card-body">
                <h3>🥽 Headset VR</h3>
                <p class="tagline">On a Meta Quest or any WebXR headset, tap <b>Enter VR</b> and walk through the space at true scale.</p>
            </div></article>
            <article class="card"><div class="card-body">
                <h3>📱 Augmented Reality</h3>
                <p class="tagline">On a supported phone, tap <b>View in AR</b> to drop the room into your real environment and walk around it.</p>
            </div></article>
            <article class="card"><div class="card-body">
                <h3>🌀 360° Magic Window</h3>
                <p class="tagline">No viewer? Just move your phone — or drag with a mouse — to look around the room from inside.</p>
            </div></article>
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
