<?php

namespace App\Support\Inspector;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class EquipmentInspectionFormImageStorage
{
    public const DISK = 'local';

    /**
     * @return array{path: string, mime_type: string, size: int, sha256: string}
     */
    public function store(UploadedFile $file): array
    {
        $binary = file_get_contents($file->getRealPath());
        if (! function_exists('imagecreatefromstring')) {
            throw ValidationException::withMessages(['image' => 'Server belum mendukung pemrosesan gambar (GD). Hubungi administrator.']);
        }

        $info = is_string($binary) && $binary !== '' && strlen($binary) <= 2 * 1024 * 1024
            ? @getimagesizefromstring($binary) : false;
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)
            || $info[0] > 4096 || $info[1] > 4096 || $info[0] * $info[1] > 12000000) {
            throw ValidationException::withMessages(['image' => 'Foto peralatan tidak valid, terlalu besar, atau formatnya tidak didukung.']);
        }

        $image = @imagecreatefromstring($binary);
        if (! $image) {
            throw ValidationException::withMessages(['image' => 'Isi foto peralatan tidak dapat dibaca.']);
        }

        try {
            $scale = min(1, 1600 / max(imagesx($image), imagesy($image)));
            $width = max(1, (int) round(imagesx($image) * $scale));
            $height = max(1, (int) round(imagesy($image) * $scale));
            $output = imagecreatetruecolor($width, $height);
            imagefill($output, 0, 0, imagecolorallocate($output, 255, 255, 255));
            imagecopyresampled($output, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
            ob_start();
            imagejpeg($output, null, 85);
            $normalized = (string) ob_get_clean();
            imagedestroy($output);
        } finally {
            imagedestroy($image);
        }

        $path = 'equipment-inspection-forms/'.Str::uuid().'.jpg';
        try {
            $written = $normalized !== '' && Storage::disk(self::DISK)->put($path, $normalized, ['visibility' => 'private']);
        } catch (Throwable) {
            $written = false;
        }
        if (! $written) {
            $this->cleanup($path);
            throw ValidationException::withMessages(['image' => 'Foto peralatan gagal disimpan. Tidak ada perubahan master yang diterapkan.']);
        }

        return [
            'path' => $path,
            'mime_type' => 'image/jpeg',
            'size' => strlen($normalized),
            'sha256' => hash('sha256', $normalized),
        ];
    }

    public function read(string $path, string $expectedHash): string
    {
        if (! str_starts_with($path, 'equipment-inspection-forms/') || str_contains($path, '..')) {
            throw new RuntimeException('Lokasi foto master inspeksi tidak valid.');
        }
        $binary = Storage::disk(self::DISK)->get($path);
        if (! is_string($binary) || $binary === '' || ! hash_equals($expectedHash, hash('sha256', $binary))) {
            throw new RuntimeException('Foto master inspeksi tidak tersedia atau integritasnya berubah.');
        }

        return $binary;
    }

    public function cleanup(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'equipment-inspection-forms/') || str_contains($path, '..')) {
            return;
        }
        try {
            Storage::disk(self::DISK)->delete($path);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
