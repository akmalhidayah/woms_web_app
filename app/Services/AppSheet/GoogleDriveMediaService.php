<?php

namespace App\Services\AppSheet;

use App\Exceptions\AppSheet\GoogleOAuthException;
use App\Support\AppSheet\GoogleDriveMedia;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SensitiveParameter;
use Throwable;

class GoogleDriveMediaService
{
    public const REQUESTER_COLLECTION = 'requester';

    public const STOCK_COLLECTION = 'stock';

    public const DAILY_REPORT_COLLECTION = 'daily-report';

    public const VARIANT_THUMB = 'thumb';

    public const VARIANT_PREVIEW = 'preview';

    public const VARIANT_DISPLAY = 'display';

    private const REFERENCE_TTL_SECONDS = 86400;

    private const LOOKUP_TTL_SECONDS = 21600;

    private const MISSING_LOOKUP_TTL_SECONDS = 60;

    private const MEDIA_TTL_SECONDS = 86400;

    private const MAX_IMAGE_BYTES = 10_485_760;

    private const IMAGE_MIME_TYPES = [
        'image/avif',
        'image/bmp',
        'image/gif',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private const COLLECTIONS = [
        self::REQUESTER_COLLECTION => [
            'directory' => 'Data_Images',
            'folder_config' => 'services.google.drive_data_images_folder_id',
        ],
        self::STOCK_COLLECTION => [
            'directory' => 'STOCK CONS BMS_Images',
            'folder_config' => 'services.google.drive_stock_consumable_images_folder_id',
        ],
        self::DAILY_REPORT_COLLECTION => [
            'directory' => 'Input LapHarian_Images',
            'folder_config' => 'services.google.drive_daily_report_images_folder_id',
        ],
    ];

    private ?bool $hasDriveScope = null;

    public function __construct(private readonly GoogleOAuthService $google) {}

    public function mediaUrl(string $collection, mixed $relativePath, string $variant = self::VARIANT_THUMB): ?string
    {
        return $this->mediaUrlForRoute(
            $collection,
            $relativePath,
            $variant,
            'admin.appsheet.media.show',
        );
    }

    public function dailyReportDisplayMediaUrl(mixed $relativePath): ?string
    {
        return $this->mediaUrlForRoute(
            self::DAILY_REPORT_COLLECTION,
            $relativePath,
            self::VARIANT_DISPLAY,
            'display.bengkel.daily-report-media',
        );
    }

    public function dailyReportDisplayAvatarUrl(mixed $relativePath): ?string
    {
        return $this->mediaUrlForRoute(
            self::REQUESTER_COLLECTION,
            $relativePath,
            self::VARIANT_THUMB,
            'display.bengkel.daily-report-avatar',
        );
    }

    private function mediaUrlForRoute(
        string $collection,
        mixed $relativePath,
        string $variant,
        string $routeName,
    ): ?string
    {
        $reference = $this->reference($collection, $relativePath);
        if ($reference === null || ! in_array($variant, [self::VARIANT_THUMB, self::VARIANT_PREVIEW, self::VARIANT_DISPLAY], true)
            || $this->folderId($collection) === null || ! $this->hasDriveScope()) {
            return null;
        }

        $reference['variant'] = $variant;
        $key = hash_hmac('sha256', $collection."\0".$reference['filename']."\0".$variant, (string) config('app.key'));
        if (! Cache::store('file')->put($this->referenceCacheKey($key), $reference, self::REFERENCE_TTL_SECONDS)) {
            return null;
        }

        return route($routeName, ['key' => $key]);
    }

    public function media(
        string $key,
        ?string $requiredCollection = null,
        ?string $requiredVariant = null,
    ): ?GoogleDriveMedia
    {
        if (! preg_match('/\A[a-f0-9]{64}\z/', $key)) {
            return null;
        }

        $reference = Cache::store('file')->get($this->referenceCacheKey($key));
        if (! is_array($reference)
            || ! is_string($reference['collection'] ?? null)
            || ! is_string($reference['filename'] ?? null)) {
            return null;
        }

        if ($requiredCollection !== null && $reference['collection'] !== $requiredCollection) {
            return null;
        }

        $legacyReference = ! array_key_exists('variant', $reference);
        $variant = $legacyReference ? self::VARIANT_THUMB : $reference['variant'];
        if (! is_string($variant) || ! in_array($variant, [self::VARIANT_THUMB, self::VARIANT_PREVIEW, self::VARIANT_DISPLAY], true)) {
            return null;
        }

        if ($requiredVariant !== null && $variant !== $requiredVariant) {
            return null;
        }

        $settings = self::COLLECTIONS[$reference['collection']] ?? null;
        if (! is_array($settings) || ! is_string($settings['directory'] ?? null)) {
            return null;
        }

        $validated = $this->reference(
            $reference['collection'],
            $settings['directory'].'/'.$reference['filename'],
        );
        $signaturePayload = $validated === null
            ? ''
            : $validated['collection']."\0".$validated['filename'].($legacyReference ? '' : "\0".$variant);
        if ($validated === null || ! hash_equals($key, hash_hmac('sha256', $signaturePayload, (string) config('app.key')))) {
            return null;
        }

        $folderId = $this->folderId($validated['collection']);
        if ($folderId === null) {
            return null;
        }

        try {
            return $this->cachedMedia($key, function () use ($folderId, $validated, $variant): ?GoogleDriveMedia {
                return $this->retrieveMedia($folderId, $validated['filename'], $variant);
            });
        } catch (GoogleOAuthException) {
            return null;
        } catch (Throwable $exception) {
            Log::warning('AppSheet Google Drive media proxy failed.', [
                'exception_type' => $exception::class,
            ]);

            return null;
        }
    }

    private function cachedMedia(string $key, callable $resolver): ?GoogleDriveMedia
    {
        $cache = Cache::store('file');
        $cacheKey = 'appsheet:drive:media:v1:'.$key;
        $cached = $this->mediaFromCache($cache->get($cacheKey));
        if ($cached !== null) {
            return $cached;
        }

        try {
            return $cache->lock($cacheKey.':lock', 60)->block(5, function () use ($cache, $cacheKey, $resolver): ?GoogleDriveMedia {
                $cached = $this->mediaFromCache($cache->get($cacheKey));
                if ($cached !== null) {
                    return $cached;
                }

                $media = $resolver();
                if ($media !== null) {
                    $cache->put($cacheKey, [
                        'contents' => $media->contents,
                        'mime_type' => $media->mimeType,
                    ], self::MEDIA_TTL_SECONDS);
                }

                return $media;
            });
        } catch (LockTimeoutException) {
            return $this->mediaFromCache($cache->get($cacheKey)) ?? $resolver();
        }
    }

    private function mediaFromCache(mixed $cached): ?GoogleDriveMedia
    {
        if (! is_array($cached)
            || ! is_string($cached['contents'] ?? null)
            || $cached['contents'] === ''
            || strlen($cached['contents']) > self::MAX_IMAGE_BYTES
            || ! is_string($cached['mime_type'] ?? null)
            || ! in_array($cached['mime_type'], self::IMAGE_MIME_TYPES, true)) {
            return null;
        }

        return new GoogleDriveMedia($cached['contents'], $cached['mime_type']);
    }

    private function retrieveMedia(string $folderId, string $filename, string $variant): ?GoogleDriveMedia
    {
        if (! $this->hasDriveScope()) {
            return null;
        }

        $token = $this->google->accessToken();
        $file = $this->resolveFile($folderId, $filename, $token);
        if ($file === null || ($file['found'] ?? false) !== true) {
            return null;
        }

        return $this->download($file, $token, $variant);
    }

    /**
     * @return array{collection: string, filename: string}|null
     */
    private function reference(string $collection, mixed $relativePath): ?array
    {
        $settings = self::COLLECTIONS[$collection] ?? null;
        if (! is_array($settings) || ! is_string($relativePath) || str_contains($relativePath, "\0")) {
            return null;
        }

        $path = str_replace('\\', '/', trim($relativePath));
        $parts = explode('/', $path);
        if (count($parts) !== 2 || $parts[0] !== $settings['directory']) {
            return null;
        }

        $filename = trim($parts[1]);
        if ($filename === '' || $filename === '.' || $filename === '..' || basename($filename) !== $filename) {
            return null;
        }

        return ['collection' => $collection, 'filename' => $filename];
    }

    private function folderId(string $collection): ?string
    {
        $configKey = self::COLLECTIONS[$collection]['folder_config'] ?? null;
        $folderId = is_string($configKey) ? config($configKey) : null;

        return is_string($folderId) && preg_match('/\A[A-Za-z0-9_-]+\z/', $folderId)
            ? $folderId
            : null;
    }

    /**
     * @return array{found: bool, id?: string, mime_type?: string, size?: int, thumbnail_url?: string}|null
     */
    private function resolveFile(string $folderId, string $filename, #[SensitiveParameter] string $token): ?array
    {
        $cache = Cache::store('file');
        $cacheKey = 'appsheet:drive:file:v2:'.hash('sha256', $folderId."\0".$filename);
        $cached = $cache->get($cacheKey);
        if (is_array($cached) && array_key_exists('found', $cached)) {
            return $cached;
        }

        try {
            return $cache->lock($cacheKey.':lock', 15)->block(3, function () use ($cache, $cacheKey, $folderId, $filename, $token): ?array {
                $cached = $cache->get($cacheKey);
                if (is_array($cached) && array_key_exists('found', $cached)) {
                    return $cached;
                }

                $file = $this->lookupFile($folderId, $filename, $token);
                if ($file !== null) {
                    $ttl = ($file['found'] ?? false) === true
                        ? self::LOOKUP_TTL_SECONDS
                        : self::MISSING_LOOKUP_TTL_SECONDS;

                    $cache->put($cacheKey, $file, $ttl);
                }

                return $file;
            });
        } catch (LockTimeoutException) {
            return null;
        }
    }

    /**
     * @return array{found: bool, id?: string, mime_type?: string, size?: int, thumbnail_url?: string}|null
     */
    private function lookupFile(string $folderId, string $filename, #[SensitiveParameter] string $token): ?array
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(15)
            ->withoutRedirecting()
            ->get('https://www.googleapis.com/drive/v3/files', [
                'q' => "'".$this->escapeQueryValue($folderId)."' in parents and name = '".$this->escapeQueryValue($filename)."' and trashed = false",
                'fields' => 'files(id,name,mimeType,size,thumbnailLink)',
                'pageSize' => 2,
                'spaces' => 'drive',
                'supportsAllDrives' => 'true',
                'includeItemsFromAllDrives' => 'true',
            ]);

        if (! $response->successful()) {
            return null;
        }

        $files = $response->json('files');
        if (! is_array($files)) {
            return null;
        }

        foreach ($files as $file) {
            if (! is_array($file) || ($file['name'] ?? null) !== $filename) {
                continue;
            }

            $id = $file['id'] ?? null;
            $mimeType = $file['mimeType'] ?? null;
            $size = filter_var($file['size'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            $thumbnailUrl = $this->safeThumbnailUrl($file['thumbnailLink'] ?? null);
            if (! is_string($id) || ! preg_match('/\A[A-Za-z0-9_-]{10,200}\z/', $id)
                || ! is_string($mimeType) || ! in_array($mimeType, self::IMAGE_MIME_TYPES, true)
                || $size === false || ($thumbnailUrl === null && $size > self::MAX_IMAGE_BYTES)) {
                return ['found' => false];
            }

            $resolved = [
                'found' => true,
                'id' => $id,
                'mime_type' => $mimeType,
                'size' => $size,
            ];
            if ($thumbnailUrl !== null) {
                $resolved['thumbnail_url'] = $thumbnailUrl;
            }

            return $resolved;
        }

        return ['found' => false];
    }

    /**
     * @param  array{found: bool, id?: string, mime_type?: string, size?: int, thumbnail_url?: string}  $file
     */
    private function download(array $file, #[SensitiveParameter] string $token, string $variant): ?GoogleDriveMedia
    {
        $response = $this->thumbnailResponse($file, $token, $variant);
        if ($response === null || ! $response->successful()) {
            if (($file['size'] ?? self::MAX_IMAGE_BYTES + 1) > self::MAX_IMAGE_BYTES) {
                return null;
            }

            $response = Http::withToken($token)
                ->connectTimeout(5)
                ->timeout(20)
                ->withoutRedirecting()
                ->get('https://www.googleapis.com/drive/v3/files/'.rawurlencode($file['id']), [
                    'alt' => 'media',
                    'supportsAllDrives' => 'true',
                ]);
        }

        if (! $response->successful()) {
            return null;
        }

        $contents = $response->body();
        $mimeType = strtolower(trim(explode(';', $response->header('Content-Type') ?: $file['mime_type'])[0]));
        if (! in_array($mimeType, self::IMAGE_MIME_TYPES, true)
            || strlen($contents) > self::MAX_IMAGE_BYTES) {
            return null;
        }

        return new GoogleDriveMedia($contents, $mimeType);
    }

    /**
     * @param  array{thumbnail_url?: string}  $file
     */
    private function thumbnailResponse(array $file, #[SensitiveParameter] string $token, string $variant): ?Response
    {
        if (! is_string($file['thumbnail_url'] ?? null)) {
            return null;
        }

        $url = $file['thumbnail_url'];
        if (in_array($variant, [self::VARIANT_PREVIEW, self::VARIANT_DISPLAY], true)) {
            $size = $variant === self::VARIANT_PREVIEW ? 1600 : 960;
            $resizedUrl = preg_replace('/=s\d+(?:-[a-z0-9-]+)?\z/i', '=s'.$size, $url);
            if (is_string($resizedUrl)) {
                $url = $resizedUrl;
            }
        }

        return Http::withToken($token)
            ->connectTimeout(5)
            ->timeout(15)
            ->withoutRedirecting()
            ->get($url);
    }

    private function safeThumbnailUrl(mixed $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $url = trim($url);
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);
        $user = parse_url($url, PHP_URL_USER);
        $password = parse_url($url, PHP_URL_PASS);
        $allowedHost = is_string($host)
            && ($host === 'drive.google.com' || str_ends_with($host, '.googleusercontent.com'));

        if ($scheme !== 'https' || ! $allowedHost || ($port !== null && $port !== 443)
            || $user !== null || $password !== null) {
            return null;
        }

        return $url;
    }

    private function escapeQueryValue(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }

    private function referenceCacheKey(string $key): string
    {
        return 'appsheet:drive:reference:'.$key;
    }

    private function hasDriveScope(): bool
    {
        if ($this->hasDriveScope !== null) {
            return $this->hasDriveScope;
        }

        try {
            return $this->hasDriveScope = $this->google->hasDriveReadScope();
        } catch (GoogleOAuthException) {
            return $this->hasDriveScope = false;
        }
    }
}
