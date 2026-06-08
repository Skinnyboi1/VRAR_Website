<?php

use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| VR/AR Meeting Room Showcase.
|   /                 -> gallery of all rooms
|   /rooms/{slug}     -> immersive VR/AR viewer for one room
|
*/

Route::get('/', [RoomController::class, 'index'])->name('rooms.index');
Route::get('/rooms/{slug}', [RoomController::class, 'show'])->name('rooms.show');

// Returns the public ngrok URL (used to build QR codes).
Route::get('/api/tunnel-home', [RoomController::class, 'tunnelHome'])->name('rooms.tunnel.home');
Route::get('/api/tunnel/{slug}', [RoomController::class, 'tunnel'])->name('rooms.tunnel');
