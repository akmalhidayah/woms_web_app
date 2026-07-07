<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SignatureImageStorage
{
    public static function storeFromRequest(Request $request, string $directory, string $prefix, string $fileKey = 'signature_file', string $dataKey = 'signature_data'): string
    {
        if ($request->hasFile($fileKey)) {
            return self::storeUploadedFile($request->file($fileKey), $directory, $prefix);
        }

        $data = trim((string) $request->input($dataKey, ''));

        if ($data !== '') {
            return self::storeDataUri($data, $directory, $prefix);
        }

        throw ValidationException::withMessages([
            $fileKey => 'Tanda tangan wajib diisi.',
        ]);
    }

    public static function storeUploadedFile(UploadedFile $file, string $directory, string $prefix): string
    {
        if (! str_starts_with((string) $file->getMimeType(), 'image/')) {
            throw ValidationException::withMessages([
                $file->getClientOriginalName() => 'Format tanda tangan tidak valid.',
            ]);
        }

        if ($file->getSize() > 1024 * 1024) {
            throw ValidationException::withMessages([
                $file->getClientOriginalName() => 'Ukuran tanda tangan terlalu besar.',
            ]);
        }

        $binary = file_get_contents($file->getRealPath());

        if ($binary === false) {
            throw ValidationException::withMessages([
                'signature_file' => 'Tanda tangan belum terbaca. Silakan tanda tangani ulang.',
            ]);
        }

        self::ensureImageHasVisibleSignature($binary, 'signature_file');

        $binary = self::trimSignatureWhitespace($binary);
        $path = $directory.'/'.$prefix.'-'.now()->format('YmdHis').'-'.Str::uuid().'.png';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    public static function storeDataUri(string $signatureData, string $directory, string $prefix): string
    {
        if (! str_starts_with($signatureData, 'data:image/png;base64,')) {
            throw ValidationException::withMessages([
                'signature_data' => 'Format tanda tangan tidak valid.',
            ]);
        }

        $base64 = substr($signatureData, strlen('data:image/png;base64,'));
        $binary = base64_decode($base64, true);

        if ($binary === false || strlen($binary) < 100) {
            throw ValidationException::withMessages([
                'signature_data' => 'Tanda tangan belum terbaca. Silakan tanda tangani ulang.',
            ]);
        }

        if (strlen($binary) > 1024 * 1024) {
            throw ValidationException::withMessages([
                'signature_data' => 'Ukuran tanda tangan terlalu besar.',
            ]);
        }

        self::ensureImageHasVisibleSignature($binary, 'signature_data');
        $binary = self::trimSignatureWhitespace($binary);

        $path = $directory.'/'.$prefix.'-'.now()->format('YmdHis').'-'.Str::uuid().'.png';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    public static function imageSource(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'data:image')) {
            return $value;
        }

        if (Storage::disk('public')->exists($value)) {
            return Storage::disk('public')->path($value);
        }

        return File::exists($value) ? $value : null;
    }

    private static function ensureImageHasVisibleSignature(string $binary, string $errorKey): void
    {
        if (! function_exists('imagecreatefromstring')) {
            return;
        }

        $image = @imagecreatefromstring($binary);

        if (! $image) {
            throw ValidationException::withMessages([
                $errorKey => 'Format tanda tangan tidak valid.',
            ]);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $inkPixels = 0;

        for ($y = 0; $y < $height; $y += 2) {
            for ($x = 0; $x < $width; $x += 2) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;

                if ($alpha >= 120) {
                    continue;
                }

                $red = ($rgba >> 16) & 0xFF;
                $green = ($rgba >> 8) & 0xFF;
                $blue = $rgba & 0xFF;

                if ($red < 245 || $green < 245 || $blue < 245) {
                    $inkPixels++;
                }

                if ($inkPixels >= 30) {
                    imagedestroy($image);

                    return;
                }
            }
        }

        imagedestroy($image);

        throw ValidationException::withMessages([
            $errorKey => 'Tanda tangan terlalu sedikit. Silakan tanda tangani dengan coretan yang jelas.',
        ]);
    }

    private static function trimSignatureWhitespace(string $binary): string
    {
        if (! function_exists('imagecreatefromstring')) {
            return $binary;
        }

        $image = @imagecreatefromstring($binary);

        if (! $image) {
            return $binary;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $minX = $width;
        $minY = $height;
        $maxX = -1;
        $maxY = -1;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if (! self::isInkPixel(imagecolorat($image, $x, $y))) {
                    continue;
                }

                $minX = min($minX, $x);
                $minY = min($minY, $y);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
            }
        }

        if ($maxX < 0 || $maxY < 0) {
            imagedestroy($image);

            return $binary;
        }

        $padding = 12;
        $cropX = max(0, $minX - $padding);
        $cropY = max(0, $minY - $padding);
        $cropRight = min($width - 1, $maxX + $padding);
        $cropBottom = min($height - 1, $maxY + $padding);
        $cropWidth = $cropRight - $cropX + 1;
        $cropHeight = $cropBottom - $cropY + 1;

        if ($cropWidth >= $width && $cropHeight >= $height) {
            imagedestroy($image);

            return $binary;
        }

        $cropped = imagecreatetruecolor($cropWidth, $cropHeight);
        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);

        $transparent = imagecolorallocatealpha($cropped, 255, 255, 255, 127);
        imagefilledrectangle($cropped, 0, 0, $cropWidth, $cropHeight, $transparent);
        imagecopy($cropped, $image, 0, 0, $cropX, $cropY, $cropWidth, $cropHeight);

        ob_start();
        $written = imagepng($cropped);
        $croppedBinary = ob_get_clean();

        imagedestroy($cropped);
        imagedestroy($image);

        return $written && is_string($croppedBinary) && $croppedBinary !== ''
            ? $croppedBinary
            : $binary;
    }

    private static function isInkPixel(int $rgba): bool
    {
        $alpha = ($rgba >> 24) & 0x7F;

        if ($alpha >= 120) {
            return false;
        }

        $red = ($rgba >> 16) & 0xFF;
        $green = ($rgba >> 8) & 0xFF;
        $blue = $rgba & 0xFF;

        return $red < 245 || $green < 245 || $blue < 245;
    }
}
