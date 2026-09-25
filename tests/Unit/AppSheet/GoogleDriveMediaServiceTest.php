<?php

namespace Tests\Unit\AppSheet;

use App\Services\AppSheet\GoogleDriveMediaService;
use App\Services\AppSheet\GoogleOAuthService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class GoogleDriveMediaServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key' => 'base64:test-app-key',
            'services.google.drive_data_images_folder_id' => 'data-folder-id',
            'services.google.drive_stock_consumable_images_folder_id' => 'stock-folder-id',
        ]);
        $cache = Cache::store('array');
        Cache::shouldReceive('store')->with('file')->andReturn($cache);
        Http::preventStrayRequests();
    }

    public function test_exact_img_filename_is_resolved_inside_the_allowlisted_stock_folder(): void
    {
        Http::fake([
            'www.googleapis.com/drive/v3/files*' => Http::response(['files' => [[
                'id' => 'drive-file-id-123',
                'name' => 'BMS-C01.IMG.234719.jpg',
                'mimeType' => 'image/jpeg',
                'size' => '5000000',
                'thumbnailLink' => 'https://lh3.googleusercontent.com/drive-thumbnail=s220',
            ]]]),
            'lh3.googleusercontent.com/*' => Http::response('thumbnail-bytes', 200, ['Content-Type' => 'image/jpeg']),
        ]);
        $service = $this->serviceWithDriveScope();

        $url = $service->mediaUrl(
            GoogleDriveMediaService::STOCK_COLLECTION,
            'STOCK CONS BMS_Images/BMS-C01.IMG.234719.jpg',
        );

        self::assertNotNull($url);
        self::assertStringNotContainsString('drive-file-id-123', $url);
        self::assertStringNotContainsString('BMS-C01.IMG.234719.jpg', $url);
        $media = $service->media(basename(parse_url($url, PHP_URL_PATH)));
        $cachedMedia = $service->media(basename(parse_url($url, PHP_URL_PATH)));
        self::assertSame('thumbnail-bytes', $media?->contents);
        self::assertSame('image/jpeg', $media?->mimeType);
        self::assertSame('thumbnail-bytes', $cachedMedia?->contents);
        Http::assertSent(function (Request $request): bool {
            $url = rawurldecode($request->url());

            return str_contains($url, '/drive/v3/files?')
                && str_contains($url, "'stock-folder-id' in parents")
                && str_contains($url, "name = 'BMS-C01.IMG.234719.jpg'")
                && $request->hasHeader('Authorization', 'Bearer drive-access-token');
        });
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://lh3.googleusercontent.com/drive-thumbnail=s220');
        Http::assertSentCount(2);
    }

    public function test_only_expected_relative_directories_can_create_media_references(): void
    {
        $service = $this->serviceWithoutDriveCalls();

        self::assertNotNull($service->mediaUrl(
            GoogleDriveMediaService::REQUESTER_COLLECTION,
            'Data_Images/Hadi.png',
        ));
        self::assertNotNull($service->mediaUrl(
            GoogleDriveMediaService::STOCK_COLLECTION,
            'STOCK CONS BMS_Images/BMS-C01.jpg',
        ));
        self::assertNull($service->mediaUrl(
            GoogleDriveMediaService::REQUESTER_COLLECTION,
            'Other/Hadi.png',
        ));
        self::assertNull($service->mediaUrl(
            GoogleDriveMediaService::STOCK_COLLECTION,
            'STOCK CONS BMS_Images/../secret.jpg',
        ));
        self::assertNull($service->mediaUrl('unknown', 'Data_Images/Hadi.png'));
        self::assertNull($service->mediaUrl(GoogleDriveMediaService::REQUESTER_COLLECTION, ''));
    }

    public function test_missing_exact_file_lookup_is_retried_after_sixty_seconds(): void
    {
        Http::fake([
            'www.googleapis.com/drive/v3/files*' => Http::response(['files' => []]),
        ]);
        $service = $this->serviceWithDriveScope();
        $url = $service->mediaUrl(
            GoogleDriveMediaService::REQUESTER_COLLECTION,
            'Data_Images/Missing.png',
        );
        $key = basename(parse_url($url, PHP_URL_PATH));

        self::assertNull($service->media($key));
        self::assertNull($service->media($key));
        Http::assertSentCount(1);

        $this->travel(59)->seconds();
        self::assertNull($service->media($key));
        Http::assertSentCount(1);

        $this->travel(2)->seconds();
        self::assertNull($service->media($key));
        Http::assertSentCount(2);
    }

    public function test_found_file_lookup_remains_cached_for_six_hours(): void
    {
        Http::fake([
            'www.googleapis.com/drive/v3/files*' => Http::response(['files' => [[
                'id' => 'drive-file-id-456',
                'name' => 'Available.jpg',
                'mimeType' => 'image/jpeg',
                'size' => '1000',
                'thumbnailLink' => 'https://lh3.googleusercontent.com/available=s220',
            ]]]),
            'lh3.googleusercontent.com/*' => Http::response('available-thumbnail', 200, ['Content-Type' => 'image/jpeg']),
        ]);
        $service = $this->serviceWithDriveScope();
        $url = $service->mediaUrl(
            GoogleDriveMediaService::REQUESTER_COLLECTION,
            'Data_Images/Available.jpg',
        );
        $key = basename(parse_url($url, PHP_URL_PATH));

        self::assertSame('available-thumbnail', $service->media($key)?->contents);
        Http::assertSentCount(2);

        $this->travel(5)->hours();
        Cache::store('file')->forget('appsheet:drive:media:v1:'.$key);
        self::assertSame('available-thumbnail', $service->media($key)?->contents);
        Http::assertSentCount(3);

        $this->travel(61)->minutes();
        Cache::store('file')->forget('appsheet:drive:media:v1:'.$key);
        self::assertSame('available-thumbnail', $service->media($key)?->contents);
        Http::assertSentCount(5);
    }

    public function test_legacy_token_without_drive_scope_fails_gracefully_without_drive_request(): void
    {
        $google = Mockery::mock(GoogleOAuthService::class);
        $google->shouldReceive('hasDriveReadScope')->once()->andReturnFalse();
        $google->shouldNotReceive('accessToken');
        $service = new GoogleDriveMediaService($google);

        self::assertNull($service->mediaUrl(
            GoogleDriveMediaService::REQUESTER_COLLECTION,
            'Data_Images/Hadi.png',
        ));

        Http::assertNothingSent();
    }

    public function test_drive_permission_failure_returns_fallback_without_exposing_response_body(): void
    {
        Http::fake([
            'www.googleapis.com/drive/v3/files*' => Http::response([
                'error' => ['message' => 'sensitive-drive-response-body'],
            ], 403),
        ]);
        $service = $this->serviceWithDriveScope();
        $url = $service->mediaUrl(
            GoogleDriveMediaService::STOCK_COLLECTION,
            'STOCK CONS BMS_Images/Unavailable.jpg',
        );

        self::assertNull($service->media(basename(parse_url($url, PHP_URL_PATH))));
        Http::assertSentCount(1);
    }

    private function serviceWithDriveScope(): GoogleDriveMediaService
    {
        $google = Mockery::mock(GoogleOAuthService::class);
        $google->shouldReceive('hasDriveReadScope')->andReturnTrue();
        $google->shouldReceive('accessToken')->andReturn('drive-access-token');

        return new GoogleDriveMediaService($google);
    }

    private function serviceWithoutDriveCalls(): GoogleDriveMediaService
    {
        $google = Mockery::mock(GoogleOAuthService::class);
        $google->shouldReceive('hasDriveReadScope')->once()->andReturnTrue();
        $google->shouldNotReceive('accessToken');

        return new GoogleDriveMediaService($google);
    }
}
