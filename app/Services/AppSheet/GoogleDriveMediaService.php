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

    private const REFERENCE_TTL_SECONDS = 86400;

    private const LOOKUP_TTL_SECONDS = 21600;

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
    ];

    private ?bool $hasDriveScope = null;

    public function __construct(private readonly GoogleOAuthService $google) {}

    public function mediaUrl(string $collection, mixed $relativePath): ?string
    {
        $reference = $this->reference($collection, $relativePath);
        if ($reference === null || $this->folderId($collection) === null || ! $this->hasDriveScope()) {
            return null;
        }

        $key = hash_hmac('sha256', $collection."\0".$reference['filename'], (string) config('app.key'));
        if (! Cache::store('file')->put($this->referenceCacheKey($key), $reference, self::REFERENCE_TTL_SECONDS)) {
            return null;
        }

        return route('admin.appsheet.media.show', ['key' => $key]);
    }

    public function media(string $key): ?GoogleDriveMedia
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

        $validated = $this->reference(
            $reference['collection'],
            self::COLLECTIONS[$reference['collection']]['directory'].'/'.$reference['filename'],
        );
        if ($validated === null || ! hash_equals(
            $key,
            hash_hmac('sha256', $validated['collection']."\0".$validated['filename'], (string) config('app.key')),
        )) {
            return null;
        }

        $folderId = $this->folderId($validated['collection']);
        if ($folderId === null) {
            return null;
        }

        try {
            if (! $this->hasDriveScope()) {
                return null;
            }

            $token = $this->google->accessToken();
            $file = $this->resolveFile($folderId, $validated['filename'], $token);
            if ($file === null || ($file['found'] ?? false) !== true) {
                return null;
            }

            return $this->download($file, $token);
        } catch (GoogleOAuthException) {
            return null;
        } catch (Throwable $exception) {
            Log::warning('AppSheet Google Drive media proxy failed.', [
                'exception_type' => $exception::class,
            ]);

            return null;
        }
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
                    $cache->put($cacheKey, $file, self::LOOKUP_TTL_SECONDS);
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
    private function download(array $file, #[SensitiveParameter] string $token): ?GoogleDriveMedia
    {
        $response = $this->thumbnailResponse($file, $token);
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
    private function thumbnailResponse(array $file, #[SensitiveParameter] string $token): ?Response
    {
        if (! is_string($file['thumbnail_url'] ?? null)) {
            return null;
        }

        return Http::withToken($token)
            ->connectTimeout(5)
            ->timeout(15)
            ->withoutRedirecting()
            ->get($file['thumbnail_url']);
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
