==============================================================
  PYTHON AUTH MICROSERVICE  (AES-256-GCM + 3DES)
==============================================================
This is the cryptographic heart of the booking system's auth.
Laravel calls it over HTTP to register, log in, and verify users.

FILES
  crypto_core.py   - the essential crypto (AES + 3DES + PBKDF2). Run it
                     directly to see a self-test:  python crypto_core.py
  app.py           - Flask HTTP API (register / login / verify / health)
  requirements.txt - Python dependencies
  auth.sqlite      - auto-created user store (created on first run)

HOW THE CRYPTO WORKS (the assignment's "essential part")
  Passwords:  PBKDF2-HMAC-SHA256 with a per-user salt (one-way; never
              stored or sent back in plain text).
  Session token (LAYERED, uses BOTH ciphers):
       JSON payload
         -> AES-256-GCM encrypt      (inner layer: confidentiality+integrity)
         -> 3DES (DES-EDE3) CBC       (outer layer)
         -> base64url  =>  the token Laravel stores in the session
       Verifying reverses it: 3DES decrypt -> AES-GCM decrypt -> check expiry.
       Any tampering makes AES-GCM's tag check fail -> token rejected.

--------------------------------------------------------------
  SETUP (one time)
--------------------------------------------------------------
  1. Open PowerShell here:  C:\laragon\www\vr_showcase\auth_service
  2. Install dependencies:
        python -m pip install -r requirements.txt
  3. (Recommended) set a real master key for the crypto. In PowerShell:
        $env:AUTH_MASTER_KEY = "some-long-random-secret"
     If you skip this, a built-in dev fallback key is used.

--------------------------------------------------------------
  RUN
--------------------------------------------------------------
        python app.py
     -> serves on http://127.0.0.1:5001
     Leave this window open while using the booking system.

  Quick self-test of the crypto only (no server):
        python crypto_core.py

--------------------------------------------------------------
  HOW LARAVEL FINDS IT
--------------------------------------------------------------
  The Laravel .env has:   AUTH_SERVICE_URL=http://127.0.0.1:5001
  Laravel's AuthController + RequireAuthToken middleware POST to
  /register, /login and /verify on that URL.

--------------------------------------------------------------
  API (for reference)
--------------------------------------------------------------
  GET  /health   -> {ok:true, ciphers:[...]}
  POST /register {name,email,password}  -> {ok, user}
  POST /login    {email,password}       -> {ok, token, user}
  POST /verify   {token}                -> {ok, user}

NOTE: This service stores its OWN users in auth.sqlite. The Laravel
app only stores BOOKINGS (in database/database.sqlite) and keeps the
sealed token in the browser session.
==============================================================
