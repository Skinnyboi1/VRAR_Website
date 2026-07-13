<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            // user identity comes from the Python auth service (uid + email)
            $table->unsignedBigInteger('user_id');
            $table->string('user_email');
            $table->string('user_name')->nullable();

            $table->string('room_slug');      // matches config/rooms.php slug
            $table->string('room_name');
            $table->date('date');
            $table->string('time_slot');      // e.g. "09:00-10:00"
            $table->string('purpose')->nullable();

            $table->timestamps();

            // Prevent the same room being booked twice for the same slot.
            $table->unique(['room_slug', 'date', 'time_slot']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
