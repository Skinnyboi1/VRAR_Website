@extends('layouts.app')
@section('title', 'New Booking')

@section('body')
<nav class="nav scrolled">
    <div class="container nav-inner">
        <a href="{{ route('rooms.index') }}" class="brand"><span class="logo">◈</span>
            <span>Immersa<span style="color:var(--muted)">Rooms</span></span></a>
        <div class="nav-links">
            <a href="{{ route('rooms.index') }}">Showcase</a>
            <a href="{{ route('bookings.index') }}">My Bookings</a>
        </div>
    </div>
</nav>

<main class="book-wrap">
    <div class="book-head">
        <div>
            <span class="auth-eyebrow">RESERVE A ROOM</span>
            <h1>New Booking</h1>
        </div>
        <a href="{{ route('bookings.index') }}" class="x-btn x-btn-ghost ghost-dark">← Back</a>
    </div>

    @include('partials.errors')

    {{-- Room + date selector (GET reloads taken slots) --}}
    <form method="GET" action="{{ route('bookings.create') }}" class="book-filter" id="filter-form">
        <label>Room
            <select name="room" onchange="document.getElementById('filter-form').submit()">
                @foreach ($rooms as $room)
                    <option value="{{ $room->slug }}" @selected($selected === $room->slug)>{{ $room->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Date
            <input type="date" name="date" value="{{ $date }}" min="{{ now()->toDateString() }}"
                   onchange="document.getElementById('filter-form').submit()">
        </label>
    </form>

    {{-- Booking form --}}
    <form method="POST" action="{{ route('bookings.store') }}" class="book-create">
        @csrf
        <input type="hidden" name="room_slug" value="{{ $selected }}">
        <input type="hidden" name="date" value="{{ $date }}">

        <h3>Pick a time slot</h3>
        <div class="slot-grid">
            @foreach ($slots as $slot)
                @php $isTaken = in_array($slot, $taken, true); @endphp
                <label class="slot {{ $isTaken ? 'taken' : '' }}">
                    <input type="radio" name="time_slot" value="{{ $slot }}" {{ $isTaken ? 'disabled' : '' }} required>
                    <span class="slot-time">{{ $slot }}</span>
                    <span class="slot-state">{{ $isTaken ? 'Booked' : 'Available' }}</span>
                </label>
            @endforeach
        </div>

        <label class="book-purpose-field">Purpose <small>(optional)</small>
            <input type="text" name="purpose" value="{{ old('purpose') }}" maxlength="120"
                   placeholder="e.g. Design review, client call">
        </label>

        <button type="submit" class="x-btn x-btn-primary book-confirm">Confirm booking →</button>
    </form>
</main>
@endsection
