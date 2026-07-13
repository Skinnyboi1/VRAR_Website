<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RequireAuthToken
{
    /**
     * Gate booking routes: require a session token that the Python auth
     * service can still verify (it unseals the 3DES+AES layers and checks
     * expiry). If invalid/expired, bounce to login.
     */
    public function handle(Request $request, Closure $next)
    {
        $token = session('auth_token');
        if (! $token) {
            return redirect()->route('auth.login')->withErrors(['email' => 'Please sign in first.']);
        }

        $base = rtrim(env('AUTH_SERVICE_URL', 'http://127.0.0.1:5001'), '/');
        try {
            $resp = Http::timeout(6)->post($base . '/verify', ['token' => $token]);
            $body = $resp->json();
            if (! $resp->successful() || ! ($body['ok'] ?? false)) {
                $request->session()->forget(['auth_token', 'auth_user']);
                return redirect()->route('auth.login')
                    ->withErrors(['email' => 'Your session expired. Please sign in again.']);
            }
            // refresh the cached user from the verified token
            session(['auth_user' => $body['user']]);
        } catch (\Throwable $e) {
            return redirect()->route('auth.login')->withErrors([
                'email' => 'Auth service unavailable. Start it with: python auth_service/app.py',
            ]);
        }

        return $next($request);
    }
}
