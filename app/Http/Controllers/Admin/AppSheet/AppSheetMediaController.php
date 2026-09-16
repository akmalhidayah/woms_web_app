<?php

namespace App\Http\Controllers\Admin\AppSheet;

use App\Http\Controllers\Controller;
use App\Services\AppSheet\GoogleDriveMediaService;
use App\Support\AppSheet\GoogleDriveMedia;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppSheetMediaController extends Controller
{
    public function show(Request $request, string $key, GoogleDriveMediaService $mediaService): Response
    {
        return $this->mediaResponse($request, $mediaService->media($key));
    }

    public function showDailyReportDisplay(
        Request $request,
        string $key,
        GoogleDriveMediaService $mediaService,
    ): Response
    {
        return $this->mediaResponse($request, $mediaService->media(
            $key,
            GoogleDriveMediaService::DAILY_REPORT_COLLECTION,
            GoogleDriveMediaService::VARIANT_DISPLAY,
        ));
    }

    public function showDailyReportDisplayAvatar(
        Request $request,
        string $key,
        GoogleDriveMediaService $mediaService,
    ): Response
    {
        return $this->mediaResponse($request, $mediaService->media(
            $key,
            GoogleDriveMediaService::REQUESTER_COLLECTION,
            GoogleDriveMediaService::VARIANT_THUMB,
        ));
    }

    private function mediaResponse(Request $request, ?GoogleDriveMedia $media): Response
    {
        abort_if($media === null, 404);

        $response = response($media->contents, 200, [
            'Cache-Control' => 'private, max-age=86400, immutable',
            'Content-Length' => (string) strlen($media->contents),
            'Content-Type' => $media->mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setEtag(hash('sha256', $media->contents));
        $response->isNotModified($request);

        return $response;
    }
}
