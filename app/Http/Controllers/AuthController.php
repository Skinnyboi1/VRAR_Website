<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    /**
     * Base URL of the Python auth microservice (AES + 3DES crypto).
     */
    protected function authService(): string
    {
        return rtrim(env('AUTH_SERVICE_URL', 'http://127.0.0.1:5001'), '/');
    }

    /* ---------------- views ---------------- */

    public function showLogin()
    {
        if (session('auth_user')) {
            return redirect()->route('bookings.index');
        }
        return view('auth.login');
    }

    public function showRegister()
    {
        if (session('auth_user')) {
            return redirect()->route('bookings.index');
        }
        return view('auth.register');
    }

    /* ---------------- actions ---------------- */

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:80',
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        try {
            $resp = Http::timeout(8)->post($this->authService() . '/register', $data);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors([
                'service' => 'The authentication service is not running. Start it with: python auth_service/app.py',
            ]);
        }

        $body = $resp->json();
        if (! $resp->successful() || ! ($body['ok'] ?? false)) {
            return back()->withInput()->withErrors([
                'email' => $body['error'] ?? 'Registration failed.',
            ]);
        }

        // Registered — now log them straight in.
        return $this->login($request);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $resp = Http::timeout(8)->post($this->authService() . '/login', $data);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors([
                'service' => 'The authentication service is not running. Start it with: python auth_service/app.py',
            ]);
        }

        $body = $resp->json();
        if (! $resp->successful() || ! ($body['ok'] ?? false)) {
            return back()->withInput()->withErrors([
                'email' => $body['error'] ?? 'Invalid credentials.',
            ]);
        }

        // Store the sealed (AES+3DES) token + user in the Laravel session.
        session([
            'auth_token' => $body['token'],
            'auth_user'  => $body['user'],
        ]);

        return redirect()->route('bookings.index')
            ->with('flash', 'Welcome back, ' . ($body['user']['name'] ?? 'there') . '!');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['auth_token', 'auth_user']);
        return redirect()->route('rooms.index')->with('flash', 'Signed out.');
    }
}
