"""
crypto_core.py
==============
The ESSENTIAL cryptographic part of the authentication system, in Python.

This module demonstrates a LAYERED cipher scheme using BOTH:
  * AES-256-GCM  -> modern authenticated encryption (confidentiality + integrity)
  * 3DES (DES-EDE3-CBC) -> a second, classical layer wrapped around the AES output

Layering order when SEALING a token:
    plaintext_token
        --> AES-256-GCM encrypt   (inner layer)
        --> 3DES-CBC encrypt      (outer layer)
        --> base64 -> stored / sent to client

UNSEALING reverses it: base64 -> 3DES decrypt -> AES-GCM decrypt -> plaintext.

Passwords are never encrypted reversibly; they are run through PBKDF2-HMAC-SHA256
(a slow key-derivation function) and only the derived hash + salt are stored.

Keys are derived once from a single master secret (AUTH_MASTER_KEY) so the same
service instance can always unseal what it sealed.
"""

import os
import json
import hmac
import base64
import hashlib
import secrets

from cryptography.hazmat.primitives.ciphers.aead import AESGCM
from cryptography.hazmat.primitives.ciphers import Cipher, algorithms, modes
from cryptography.hazmat.primitives import padding

# TripleDES (3DES) lives in the "decrepit" module in newer cryptography
# releases (it's legacy, but required here for the AES+3DES layering).
try:
    from cryptography.hazmat.decrepit.ciphers.algorithms import TripleDES
except ImportError:  # older cryptography versions
    from cryptography.hazmat.primitives.ciphers.algorithms import TripleDES


# ----------------------------------------------------------------------
# Key material — derived deterministically from one master secret.
# ----------------------------------------------------------------------
MASTER_SECRET = os.environ.get(
    "AUTH_MASTER_KEY",
    # Dev fallback. In production set AUTH_MASTER_KEY in the environment.
    "change-me-dev-master-secret-vr-showcase",
).encode("utf-8")


def _derive(label: bytes, length: int) -> bytes:
    """Derive a fixed-length key from the master secret + a label (HKDF-ish)."""
    return hashlib.pbkdf2_hmac("sha256", MASTER_SECRET, label, 100_000, dklen=length)


# AES-256 needs 32 bytes; 3DES (EDE3) needs 24 bytes.
AES_KEY = _derive(b"aes-256-gcm-token-key", 32)
DES3_KEY = _derive(b"3des-ede3-outer-key", 24)


# ----------------------------------------------------------------------
# Password hashing (PBKDF2-HMAC-SHA256) — one-way, with per-user salt.
# ----------------------------------------------------------------------
PBKDF2_ROUNDS = 200_000


def hash_password(password: str) -> str:
    """Return 'pbkdf2_sha256$rounds$salt$hash' (all base64) for storage."""
    salt = secrets.token_bytes(16)
    dk = hashlib.pbkdf2_hmac("sha256", password.encode("utf-8"), salt, PBKDF2_ROUNDS)
    return "pbkdf2_sha256${}${}${}".format(
        PBKDF2_ROUNDS,
        base64.b64encode(salt).decode(),
        base64.b64encode(dk).decode(),
    )


def verify_password(password: str, stored: str) -> bool:
    """Constant-time verify a password against a stored hash string."""
    try:
        algo, rounds, b64salt, b64hash = stored.split("$")
        if algo != "pbkdf2_sha256":
            return False
        rounds = int(rounds)
        salt = base64.b64decode(b64salt)
        expected = base64.b64decode(b64hash)
        dk = hashlib.pbkdf2_hmac("sha256", password.encode("utf-8"), salt, rounds)
        return hmac.compare_digest(dk, expected)
    except Exception:
        return False


# ----------------------------------------------------------------------
# Layered token sealing:  AES-256-GCM  (inner)  +  3DES-CBC  (outer)
# ----------------------------------------------------------------------
def _aes_encrypt(plaintext: bytes) -> bytes:
    """AES-256-GCM. Output = nonce(12) || ciphertext+tag."""
    nonce = secrets.token_bytes(12)
    ct = AESGCM(AES_KEY).encrypt(nonce, plaintext, None)
    return nonce + ct


def _aes_decrypt(blob: bytes) -> bytes:
    nonce, ct = blob[:12], blob[12:]
    return AESGCM(AES_KEY).decrypt(nonce, ct, None)


def _des3_encrypt(plaintext: bytes) -> bytes:
    """3DES (EDE3) in CBC mode with PKCS7 padding. Output = iv(8) || ciphertext."""
    iv = secrets.token_bytes(8)  # DES block size = 8 bytes
    padder = padding.PKCS7(TripleDES.block_size).padder()
    padded = padder.update(plaintext) + padder.finalize()
    cipher = Cipher(TripleDES(DES3_KEY), modes.CBC(iv))
    enc = cipher.encryptor()
    ct = enc.update(padded) + enc.finalize()
    return iv + ct


def _des3_decrypt(blob: bytes) -> bytes:
    iv, ct = blob[:8], blob[8:]
    cipher = Cipher(TripleDES(DES3_KEY), modes.CBC(iv))
    dec = cipher.decryptor()
    padded = dec.update(ct) + dec.finalize()
    unpadder = padding.PKCS7(TripleDES.block_size).unpadder()
    return unpadder.update(padded) + unpadder.finalize()


def seal_token(payload: dict) -> str:
    """
    Turn a payload dict into a sealed, tamper-proof token string.
    JSON -> AES-256-GCM -> 3DES-CBC -> base64url.
    """
    raw = json.dumps(payload, separators=(",", ":")).encode("utf-8")
    aes_layer = _aes_encrypt(raw)        # inner
    des_layer = _des3_encrypt(aes_layer)  # outer
    return base64.urlsafe_b64encode(des_layer).decode("utf-8")


def unseal_token(token: str) -> dict | None:
    """Reverse seal_token. Returns the payload dict, or None if invalid/tampered."""
    try:
        des_layer = base64.urlsafe_b64decode(token.encode("utf-8"))
        aes_layer = _des3_decrypt(des_layer)   # peel outer 3DES
        raw = _aes_decrypt(aes_layer)          # peel inner AES (also verifies GCM tag)
        return json.loads(raw.decode("utf-8"))
    except Exception:
        return None


# ----------------------------------------------------------------------
# Quick self-test:  python crypto_core.py
# ----------------------------------------------------------------------
if __name__ == "__main__":
    print("== Password hashing ==")
    h = hash_password("hunter2")
    print(" stored:", h)
    print(" verify correct :", verify_password("hunter2", h))
    print(" verify wrong   :", verify_password("nope", h))

    print("\n== Layered token (AES-256-GCM + 3DES) ==")
    tok = seal_token({"uid": 7, "email": "a@b.com"})
    print(" sealed token:", tok[:60], "...")
    print(" unsealed    :", unseal_token(tok))
    print(" tampered    :", unseal_token(tok[:-4] + "AAAA"))
