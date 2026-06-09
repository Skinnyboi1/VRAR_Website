<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RoomController extends Controller
{
    /**
     * Show the gallery / landing page with all rooms.
     */
    public function index()
    {
        // Only show rooms that actually have a model file on disk.
        return view('rooms.index', [
            'rooms' => $this->rooms()->filter->model_exists->values(),
        ]);
    }

    /**
     * Show a single room in the immersive VR/AR viewer.
     */
    public function show(string $slug)
    {
        $room = $this->rooms()->firstWhere('slug', $slug);

        if (! $room) {
            throw new NotFoundHttpException("Room [{$slug}] not found.");
        }

        return view('rooms.show', [
            'room'  => $room,
            'rooms' => $this->rooms(),
        ]);
    }

    /**
     * Public HTTPS base URL exposed by the locally running ngrok agent
     * (its inspector API lives at 127.0.0.1:4040), or null if no tunnel.
     */
    protected function tunnelBaseUrl(): ?string
    {
        try {
            $resp = Http::timeout(1.5)->get('http://127.0.0.1:4040/api/tunnels');

            if ($resp->ok()) {
                $tunnels = $resp->json('tunnels', []);

                // Prefer an https tunnel.
                foreach ($tunnels as $t) {
                    if (($t['proto'] ?? null) === 'https' && ! empty($t['public_url'])) {
                        return rtrim($t['public_url'], '/');
                    }
                }
                // Fallback: any tunnel, upgraded to https.
                if (! empty($tunnels[0]['public_url'])) {
                    return rtrim(preg_replace('#^http://#', 'https://', $tunnels[0]['public_url']), '/');
                }
            }
        } catch (\Throwable $e) {
            // ngrok not running / not reachable — fine.
        }

        return null;
    }

    /**
     * Returns the public tunnel URL for a given room (used for the AR QR).
     * { url: "https://xxxx.ngrok-free.app/rooms/{slug}" } or { url: null }.
     */
    public function tunnel(string $slug)
    {
        $base = $this->tunnelBaseUrl();

        return response()->json([
            'url' => $base ? $base . '/rooms/' . $slug : null,
        ]);
    }

    /**
     * Returns the public tunnel URL for the site root (used for the
     * "scan to open on your phone" QR on the gallery page).
     */
    public function tunnelHome()
    {
        $base = $this->tunnelBaseUrl();

        return response()->json([
            'url' => $base ?: null,
        ]);
    }

    /**
     * Load the room catalog and normalise each entry into an object,
     * resolving asset paths and whether the model file actually exists.
     */
    protected function rooms(): Collection
    {
        return collect(config('rooms.rooms', []))->map(function (array $room) {
            $modelPath     = 'assets/models/' . $room['model'];
            $thumbnailPath = 'assets/thumbnails/' . $room['thumbnail'];

            $room['model_exists']     = file_exists(public_path($modelPath));
            $room['thumbnail_exists'] = file_exists(public_path($thumbnailPath));

            // public asset() URL the front-end will request.
            $room['model_url']     = asset($modelPath);
            $room['thumbnail_url'] = $room['thumbnail_exists']
                ? asset($thumbnailPath)
                : null;

            return (object) $room;
        });
    }
}
