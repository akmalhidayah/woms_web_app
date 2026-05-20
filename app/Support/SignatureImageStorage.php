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

        $extension = $file->extension() ?: 'png';

        return $file->storeAs($directory, $prefix.'-'.now()->format('YmdHis').'-'.Str::uuid().'.'.$extension, 'public');
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
}
