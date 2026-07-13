<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id', 'user_email', 'user_name',
        'room_slug', 'room_name', 'date', 'time_slot', 'purpose',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}
