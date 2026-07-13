@extends('layouts.app')
@section('title', 'My Bookings')

@section('body')
<nav class="nav scrolled">
    <div class="container nav-inner">
        <a href="{{ route('rooms.index') }}" class="brand"><span class="logo">◈</span>
            <span>Immersa<span style="color:var(--muted)">Rooms</span></span></a>
        <div class="nav-links">
            <a href="{{ route('rooms.index') }}">Showcase</a>
            <a href="{{ route('bookings.index') }}">My Bookings</a>
            <form method="POST" action="{{ route('auth.logout') }}" style="display:inline">
                @csrf <button type="submit" class="linklike">Sign out ({{ $user['name'] ?? $user['email'] }})</button>
            </form>
        </div>
    </div>
</nav>

<main class="book-wrap">
    <div class="book-head">
        <div>
            <span class="auth-eyebrow">YOUR RESERVATIONS</span>
            <h1>My Bookings</h1>
        </div>
        <a href="{{ route('bookings.create') }}" class="x-btn x-btn-primary">+ New booking</a>
    </div>

    @include('partials.errors')

    @if ($bookings->isEmpty())
        <div class="book-empty">
            <span class="ico">📅</span>
            <h3>No bookings yet</h3>
            <p>Reserve one of the showcase rooms for a date and time slot.</p>
            <a href="{{ route('bookings.create') }}" class="x-btn x-btn-primary">Book a room →</a>
        </div>
    @else
        <div class="book-list">
            @foreach ($bookings as $b)
                <div class="book-row">
                    <div class="book-room">
                        <span class="book-room-name">{{ $b->room_name }}</span>
                        @if ($b->purpose)<span class="book-purpose">{{ $b->purpose }}</span>@endif
                    </div>
                    <div class="book-when">
                        <span class="book-date">{{ \Illuminate\Support\Carbon::parse($b->date)->format('D, d M Y') }}</span>
                        <span class="book-slot">{{ $b->time_slot }}</span>
                    </div>
                    <div class="book-actions">
                        <a href="{{ route('rooms.show', $b->room_slug) }}" class="x-btn x-btn-ghost ghost-dark">View room</a>
                        <form method="POST" action="{{ route('bookings.destroy', $b) }}"
                              onsubmit="return confirm('Cancel this booking?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="x-btn cancel-btn">Cancel</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</main>
@endsection
