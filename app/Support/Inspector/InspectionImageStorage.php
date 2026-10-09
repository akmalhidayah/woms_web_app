<?php

namespace App\Support\Inspector;

use App\Support\SignatureImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class InspectionImageStorage
{
    public const DISK = 'local';

    public function photo(UploadedFile $file, string $publicId): array
    {
        $binary = file_get_contents($file->getRealPath());
        $image = $this->decode($binary ?: '', 'photos', 4096, 4096, 2 * 1024 * 1024);
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

        return $this->write($normalized, 'equipment-inspections/'.$publicId.'/photos/'.Str::uuid().'.jpg', 'image/jpeg');
    }

    public function signature(string $data, string $publicId): array
    {
        $prefix = 'data:image/png;base64,';
        if (! str_starts_with($data, $prefix)) {
            throw ValidationException::withMessages(['signature_data' => 'Tanda tangan harus digambar pada kanvas.']);
        }
        $binary = base64_decode(substr($data, strlen($prefix)), true);
        $image = $this->decode($binary ?: '', 'signature_data', 2000, 1000, 1024 * 1024, true);
        try {
            $ink = 0;
            $minX = imagesx($image);
            $minY = imagesy($image);
            $maxX = $maxY = 0;
            for ($y = 0; $y < imagesy($image); $y += 2) {
                for ($x = 0; $x < imagesx($image); $x += 2) {
                    $color = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                    if ($color['alpha'] < 100 && min($color['red'], $color['green'], $color['blue']) < 220) {
                        $ink++;
                        $minX = min($minX, $x);
                        $maxX = max($maxX, $x);
                        $minY = min($minY, $y);
                        $maxY = max($maxY, $y);
                    }
                }
            }
            if ($ink < 30 || $maxX - $minX < 20 || $maxY - $minY < 10) {
                throw ValidationException::withMessages(['signature_data' => 'Tanda tangan kosong atau terlalu sedikit. Gambar coretan yang jelas.']);
            }
            imagepalettetotruecolor($image);
            imagesavealpha($image, true);
            ob_start();
            imagepng($image);
            $normalized = (string) ob_get_clean();
        } finally {
            imagedestroy($image);
        }

        return $this->write(SignatureImageStorage::trimSignatureWhitespace($normalized),
            'equipment-inspections/'.$publicId.'/signatures/'.Str::uuid().'.png', 'image/png');
    }

    public function read(string $path, ?string $expectedHash = null): string
    {
        if (! str_starts_with($path, 'equipment-inspections/') || str_contains($path, '..')) {
            throw new RuntimeException('Lokasi media inspeksi tidak valid.');
        }
        $data = Storage::disk(self::DISK)->get($path);
        if (! is_string($data) || $data === '' || ($expectedHash && ! hash_equals($expectedHash, hash('sha256', $data)))) {
            throw new RuntimeException('Media inspeksi tidak tersedia atau integritasnya berubah.');
        }

        return $data;
    }

    public function cleanup(array $paths): void
    {
        foreach ($paths as $path) {
            try {
                $deleted = Storage::disk(self::DISK)->delete($path);
            } catch (Throwable) {
                $deleted = false;
            }
            if (! $deleted) {
                report(new RuntimeException('Cleanup media inspeksi gagal; periksa penyimpanan privat.'));
            }
        }
    }

    private function write(string $binary, string $path, string $mime): array
    {
        try {
            $written = $binary !== '' && Storage::disk(self::DISK)->put($path, $binary, ['visibility' => 'private']);
        } catch (Throwable) {
            $written = false;
        }
        if (! $written) {
            $this->cleanup([$path]);
            report(new RuntimeException('Media inspeksi gagal disimpan pada disk privat.'));
            throw ValidationException::withMessages(['media' => 'Foto atau tanda tangan gagal disimpan. Tidak ada perubahan laporan yang diterapkan; silakan coba lagi.']);
        }

        return ['path' => $path, 'mime_type' => $mime, 'size' => strlen($binary), 'sha256' => hash('sha256', $binary)];
    }

    private function decode(string $binary, string $key, int $maxWidth, int $maxHeight, int $maxBytes, bool $pngOnly = false): \GdImage
    {
        if (! function_exists('imagecreatefromstring')) {
            throw ValidationException::withMessages([$key => 'Server belum mendukung pemrosesan gambar (GD). Hubungi administrator.']);
        }
        $info = $binary !== '' && strlen($binary) <= $maxBytes ? @getimagesizefromstring($binary) : false;
        $types = $pngOnly ? [IMAGETYPE_PNG] : [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
        if (! $info || ! in_array($info[2], $types, true) || $info[0] > $maxWidth || $info[1] > $maxHeight
            || $info[0] * $info[1] > 12000000) {
            throw ValidationException::withMessages([$key => 'Gambar tidak valid, terlalu besar, atau format tidak didukung.']);
        }
        $image = @imagecreatefromstring($binary);
        if (! $image) {
            throw ValidationException::withMessages([$key => 'Isi gambar tidak dapat dibaca.']);
        }

        return $image;
    }
}
