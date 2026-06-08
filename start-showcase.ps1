# ============================================================
#  VR/AR Meeting Room Showcase — launcher
#  Starts the Laravel dev server and an ngrok HTTPS tunnel so
#  the AR QR code resolves to a phone-reachable address.
#
#  Usage:   powershell -ExecutionPolicy Bypass -File .\start-showcase.ps1
#  Stop:    press Ctrl+C (closes the tunnel; the server window stays —
#           close it manually or re-run this script).
# ============================================================

$ErrorActionPreference = 'Stop'
$port = 8000
$root = $PSScriptRoot

# --- locate ngrok (PATH first, then the winget install location) ---
$ngrok = (Get-Command ngrok -ErrorAction SilentlyContinue).Source
if (-not $ngrok) {
    $candidate = Get-ChildItem "$env:LOCALAPPDATA\Microsoft\WinGet\Packages" -Recurse -Filter ngrok.exe -ErrorAction SilentlyContinue |
                 Select-Object -First 1 -ExpandProperty FullName
    if ($candidate) { $ngrok = $candidate }
}
if (-not $ngrok) {
    Write-Host "ngrok not found. Install with:  winget install ngrok.ngrok" -ForegroundColor Red
    exit 1
}

# --- ensure an authtoken is configured ---
$cfgPath = "$env:LOCALAPPDATA\ngrok\ngrok.yml"
if (-not (Test-Path $cfgPath)) { $cfgPath = "$env:USERPROFILE\.ngrok2\ngrok.yml" }
if (-not (Test-Path $cfgPath)) {
    Write-Host ""
    Write-Host "  ngrok needs a (free) authtoken before it can open a tunnel." -ForegroundColor Yellow
    Write-Host "  1) Sign up / log in:  https://dashboard.ngrok.com/signup" -ForegroundColor Yellow
    Write-Host "  2) Copy your token:   https://dashboard.ngrok.com/get-started/your-authtoken" -ForegroundColor Yellow
    Write-Host "  3) Run:  ngrok config add-authtoken <YOUR_TOKEN>" -ForegroundColor Yellow
    Write-Host "  Then re-run this script." -ForegroundColor Yellow
    Write-Host ""
    exit 1
}

# --- find an already-running Laravel server, or start one ---
# This avoids the ERR_NGROK_8012 "failed to connect to upstream" error that
# happens when ngrok points at a port where nothing is listening.
function Test-PortListening($p) {
    [bool](Get-NetTCPConnection -LocalPort $p -State Listen -ErrorAction SilentlyContinue)
}

if (Test-PortListening $port) {
    Write-Host "Laravel server already listening on http://127.0.0.1:$port — reusing it." -ForegroundColor Green
} else {
    # Fall back: scan a few common ports a server might already be on.
    $found = $null
    foreach ($p in 8000, 8080, 8001) { if (Test-PortListening $p) { $found = $p; break } }

    if ($found) {
        $port = $found
        Write-Host "Found a running server on http://127.0.0.1:$port — tunnelling to it." -ForegroundColor Green
    } else {
        Write-Host "Starting Laravel dev server on http://127.0.0.1:$port ..." -ForegroundColor Cyan
        Start-Process -FilePath "php" -ArgumentList "artisan", "serve", "--port=$port" -WorkingDirectory $root
        # Wait until it actually accepts connections (up to ~10s).
        for ($i = 0; $i -lt 20 -and -not (Test-PortListening $port); $i++) { Start-Sleep -Milliseconds 500 }
        if (-not (Test-PortListening $port)) {
            Write-Host "Server didn't come up on port $port. Check 'php artisan serve' manually." -ForegroundColor Red
            exit 1
        }
        Write-Host "Server is up on http://127.0.0.1:$port." -ForegroundColor Green
    }
}

Write-Host "Opening ngrok HTTPS tunnel -> 127.0.0.1:$port ..." -ForegroundColor Cyan
Write-Host "When ngrok shows a 'Forwarding https://...' line, open that URL (or the room page)" -ForegroundColor Green
Write-Host "on your computer; the AR button's QR will point your phone at the public address." -ForegroundColor Green
Write-Host ""

# Run ngrok in the foreground so its dashboard / forwarding URL is visible.
& $ngrok http $port --host-header="127.0.0.1:$port"
