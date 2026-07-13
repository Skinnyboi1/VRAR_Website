<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
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

/*
| Auth (crypto handled by the Python AES+3DES microservice)
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('auth.register');
Route::post('/register', [AuthController::class, 'register'])->name('auth.register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

/*
| Bookings (require a valid sealed token, verified by the Python service)
*/
Route::middleware('auth.token')->group(function () {
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/new', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');
});
