<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SchoolIdImage
{
    public static function response(?string $path): Response
    {
        abort_unless($path && preg_match('~^id-cards/[\w.-]+$~D', $path), 404);

        $headers = [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ];
        $disk = Storage::disk('public');
        if ($disk->exists($path)) {
            return $disk->response($path, null, $headers);
        }

        $legacyFile = public_path('storage/'.$path);
        abort_unless(is_file($legacyFile), 404);

        return response()->file($legacyFile, $headers);
    }
}
