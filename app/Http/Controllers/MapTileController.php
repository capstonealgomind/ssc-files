<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MapTileController extends Controller
{
    public function show(int $z, int $x, int $y): Response
    {
        if ($z < 0 || $z > 19 || $x < 0 || $y < 0) {
            abort(404);
        }

        $cacheKey = "map-tile:{$z}:{$x}:{$y}";

        $image = Cache::get($cacheKey);

        if (! is_string($image) || ! str_starts_with($image, "\x89PNG")) {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'SSCEVS/1.0 (location settings map)',
                    'Accept'     => 'image/png,image/*,*/*',
                ])
                ->get("https://tile.openstreetmap.org/{$z}/{$x}/{$y}.png");

            if (! $response->successful()) {
                abort(404);
            }

            $image = $response->body();

            if (! str_starts_with($image, "\x89PNG")) {
                abort(502);
            }

            Cache::put($cacheKey, $image, now()->addDays(7));
        }

        return response($image, 200)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=604800');
    }
}
