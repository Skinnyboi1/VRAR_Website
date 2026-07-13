@extends('layouts.app')
@section('title', 'Sign in — VR/AR Meeting Rooms')

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
        <span class="auth-eyebrow">SECURE SIGN IN</span>
        <h1>Welcome back</h1>
        <p class="auth-sub">Sign in to book a meeting room.</p>

        @include('partials.errors')

        <form method="POST" action="{{ route('auth.login.post') }}" class="auth-form">
            @csrf
            <label>Email
                <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com">
            </label>
            <label>Password
                <input type="password" name="password" required placeholder="••••••••">
            </label>
            <button type="submit" class="x-btn x-btn-primary auth-submit">Sign in →</button>
        </form>

        <p class="auth-alt">New here? <a href="{{ route('auth.register') }}">Create an account</a></p>
        <p class="auth-crypto">🔒 Credentials protected with PBKDF2; sessions sealed with <b>AES-256-GCM + 3DES</b> via a Python service.</p>
    </div>
</main>
@endsection
