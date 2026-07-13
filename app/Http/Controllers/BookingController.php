<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BookingController extends Controller
{
    /** Fixed hourly slots offered for every room. */
    public const SLOTS = [
        '08:00-09:00', '09:00-10:00', '10:00-11:00', '11:00-12:00',
        '13:00-14:00', '14:00-15:00', '15:00-16:00', '16:00-17:00',
    ];

    /** Rooms available to book (only those with a real model). */
    protected function rooms(): Collection
    {
        return collect(config('rooms.rooms', []))->filter(function ($r) {
            return file_exists(public_path('assets/models/' . $r['model']));
        })->map(fn ($r) => (object) $r)->values();
    }

    /** My bookings list. */
    public function index()
    {
        $user = session('auth_user');

        $bookings = Booking::where('user_id', $user['id'])
            ->orderBy('date')
            ->orderBy('time_slot')
            ->get();

        return view('bookings.index', [
            'user'     => $user,
            'bookings' => $bookings,
        ]);
    }

    /** Booking form. */
    public function create(Request $request)
    {
        $rooms    = $this->rooms();
        $selected = $request->query('room', $rooms->first()->slug ?? null);

        // Slots already taken for the chosen room+date (to disable them).
        $date = $request->query('date', now()->addDay()->toDateString());
        $taken = Booking::where('room_slug', $selected)
            ->whereDate('date', $date)
            ->pluck('time_slot')
            ->all();

        return view('bookings.create', [
            'user'     => session('auth_user'),
            'rooms'    => $rooms,
            'slots'    => self::SLOTS,
            'selected' => $selected,
            'date'     => $date,
            'taken'    => $taken,
        ]);
    }

    /** Store a booking (with double-booking prevention). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'room_slug' => 'required|string',
            'date'      => 'required|date|after_or_equal:today',
            'time_slot' => 'required|string',
            'purpose'   => 'nullable|string|max:120',
        ]);

        $room = $this->rooms()->firstWhere('slug', $data['room_slug']);
        if (! $room) {
            return back()->withInput()->withErrors(['room_slug' => 'Unknown room.']);
        }
        if (! in_array($data['time_slot'], self::SLOTS, true)) {
            return back()->withInput()->withErrors(['time_slot' => 'Invalid time slot.']);
        }

        // Conflict check (also enforced by a unique DB index as a backstop).
        $clash = Booking::where('room_slug', $data['room_slug'])
            ->whereDate('date', $data['date'])
            ->where('time_slot', $data['time_slot'])
            ->exists();
        if ($clash) {
            return back()->withInput()->withErrors([
                'time_slot' => 'That slot is already booked for this room. Pick another.',
            ]);
        }

        $user = session('auth_user');
        Booking::create([
            'user_id'    => $user['id'],
            'user_email' => $user['email'],
            'user_name'  => $user['name'] ?? null,
            'room_slug'  => $room->slug,
            'room_name'  => $room->name,
            'date'       => $data['date'],
            'time_slot'  => $data['time_slot'],
            'purpose'    => $data['purpose'] ?? null,
        ]);

        return redirect()->route('bookings.index')
            ->with('flash', "Booked {$room->name} on {$data['date']} ({$data['time_slot']}).");
    }

    /** Cancel one of my bookings. */
    public function destroy(Request $request, Booking $booking)
    {
        $user = session('auth_user');
        if ($booking->user_id !== ($user['id'] ?? null)) {
            abort(403);
        }
        $booking->delete();

        return redirect()->route('bookings.index')->with('flash', 'Booking cancelled.');
    }
}
