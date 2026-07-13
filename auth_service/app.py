"""
app.py — Python authentication microservice (Flask)
====================================================
Exposes a small HTTP API the Laravel app calls for the ESSENTIAL auth crypto:

    POST /register   {name, email, password}      -> {ok, user} or {ok:false, error}
    POST /login      {email, password}            -> {ok, token, user} or error
    POST /verify     {token}                      -> {ok, user} or {ok:false}
    GET  /health                                  -> {ok:true}

Crypto (see crypto_core.py):
  * Passwords  -> PBKDF2-HMAC-SHA256 (one-way)
  * Tokens     -> AES-256-GCM (inner) wrapped in 3DES-CBC (outer), layered.

Users are stored in a local SQLite file owned by THIS service (auth.sqlite),
keeping the cryptographic auth concern isolated from the Laravel app.

Run:
    pip install -r requirements.txt
    python app.py            # serves on http://127.0.0.1:5001
"""

import os
import time
import sqlite3

from flask import Flask, request, jsonify

import crypto_core as cc

DB_PATH = os.path.join(os.path.dirname(__file__), "auth.sqlite")
TOKEN_TTL_SECONDS = 60 * 60 * 8  # 8 hours

app = Flask(__name__)


# ----------------------------------------------------------------------
# Tiny SQLite helper
# ----------------------------------------------------------------------
def db():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    return conn


def init_db():
    with db() as conn:
        conn.execute(
            """
            CREATE TABLE IF NOT EXISTS users (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                name          TEXT NOT NULL,
                email         TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                created_at    INTEGER NOT NULL
            )
            """
        )
        conn.commit()


def user_public(row) -> dict:
    return {"id": row["id"], "name": row["name"], "email": row["email"]}


# ----------------------------------------------------------------------
# Routes
# ----------------------------------------------------------------------
@app.get("/health")
def health():
    return jsonify(ok=True, service="auth", ciphers=["AES-256-GCM", "3DES-CBC"])


@app.post("/register")
def register():
    data = request.get_json(silent=True) or {}
    name = (data.get("name") or "").strip()
    email = (data.get("email") or "").strip().lower()
    password = data.get("password") or ""

    if not name or not email or len(password) < 6:
        return jsonify(ok=False, error="Name, email and a 6+ char password are required."), 400

    pw_hash = cc.hash_password(password)  # PBKDF2 (one-way)
    try:
        with db() as conn:
            cur = conn.execute(
                "INSERT INTO users (name, email, password_hash, created_at) VALUES (?,?,?,?)",
                (name, email, pw_hash, int(time.time())),
            )
            conn.commit()
            row = conn.execute("SELECT * FROM users WHERE id=?", (cur.lastrowid,)).fetchone()
    except sqlite3.IntegrityError:
        return jsonify(ok=False, error="That email is already registered."), 409

    return jsonify(ok=True, user=user_public(row))


@app.post("/login")
def login():
    data = request.get_json(silent=True) or {}
    email = (data.get("email") or "").strip().lower()
    password = data.get("password") or ""

    with db() as conn:
        row = conn.execute("SELECT * FROM users WHERE email=?", (email,)).fetchone()

    # verify_password is constant-time; we still run it on a dummy hash when the
    # user doesn't exist to avoid leaking which emails are registered (timing).
    stored = row["password_hash"] if row else cc.hash_password("dummy")
    if not cc.verify_password(password, stored) or row is None:
        return jsonify(ok=False, error="Invalid email or password."), 401

    # Issue a sealed session token: AES-256-GCM inner + 3DES outer layer.
    token = cc.seal_token({
        "uid": row["id"],
        "email": row["email"],
        "name": row["name"],
        "iat": int(time.time()),
        "exp": int(time.time()) + TOKEN_TTL_SECONDS,
    })
    return jsonify(ok=True, token=token, user=user_public(row))


@app.post("/verify")
def verify():
    data = request.get_json(silent=True) or {}
    token = data.get("token") or ""
    payload = cc.unseal_token(token)  # peels 3DES then AES-GCM; None if tampered

    if not payload:
        return jsonify(ok=False, error="Invalid or tampered token."), 401
    if payload.get("exp", 0) < int(time.time()):
        return jsonify(ok=False, error="Token expired."), 401

    return jsonify(ok=True, user={
        "id": payload["uid"],
        "name": payload.get("name"),
        "email": payload.get("email"),
    })


if __name__ == "__main__":
    init_db()
    print("Auth service (AES-256-GCM + 3DES) on http://127.0.0.1:5001")
    app.run(host="127.0.0.1", port=5001, debug=False)
