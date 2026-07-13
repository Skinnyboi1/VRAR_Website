@extends('layouts.app')
@section('title', 'Create account — VR/AR Meeting Rooms')

@section('body')
<nav class="nav scrolled">
    <div class="container nav-inner">
        <a href="{{ route('rooms.index') }}" class="brand"><span class="logo">◈</span>
            <span>Immersa<span style="color:var(--muted)">Rooms</span></span></a>
        <div class="nav-links"><a href="{{ route('rooms.index') }}">← Showcase</a></div>
    </div>
</nav>

<main class="auth-wrap">
    <div class="auth-card">
        <span class="auth-eyebrow">CREATE ACCOUNT</span>
        <h1>Join the showcase</h1>
        <p class="auth-sub">Make an account to reserve rooms.</p>

        @include('partials.errors')

        <form method="POST" action="{{ route('auth.register.post') }}" class="auth-form">
            @csrf
            <label>Full name
                <input type="text" name="name" value="{{ old('name') }}" required autofocus placeholder="Jane Doe">
            </label>
            <label>Email
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="you@example.com">
            </label>
            <label>Password <small>(min 6 characters)</small>
                <input type="password" name="password" required placeholder="••••••••">
            </label>
            <button type="submit" class="x-btn x-btn-primary auth-submit">Create account →</button>
        </form>

        <p class="auth-alt">Already have an account? <a href="{{ route('auth.login') }}">Sign in</a></p>
        <p class="auth-crypto">🔒 Password hashed with PBKDF2-HMAC-SHA256 in a Python service. Never stored in plain text.</p>
    </div>
</main>
@endsection
