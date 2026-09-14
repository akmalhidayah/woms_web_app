<?php

namespace App\Http\Controllers\Admin\AppSheet;

use App\Http\Controllers\Controller;
use App\Services\AppSheet\GoogleDriveMediaService;
use Symfony\Component\HttpFoundation\Response;

class AppSheetMediaController extends Controller
{
    public function show(string $key, GoogleDriveMediaService $mediaService): Response
    {
        $media = $mediaService->media($key);
        abort_if($media === null, 404);

        return response($media->contents, 200, [
            'Cache-Control' => 'private, max-age=3600',
            'Content-Length' => (string) strlen($media->contents),
            'Content-Type' => $media->mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
