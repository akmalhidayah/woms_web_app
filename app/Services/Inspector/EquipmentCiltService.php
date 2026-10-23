<?php

namespace App\Services\Inspector;

use App\Models\EquipmentInspectionUnit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class EquipmentCiltService
{
    public const DISK = 'local';

    public function replace(User $actor, EquipmentInspectionUnit $existing, UploadedFile $file): void
    {
        $path = 'equipment-cilt/'.$existing->id.'/'.Str::uuid().'.pdf';
        try {
            $stored = Storage::disk(self::DISK)->putFileAs(dirname($path), $file, basename($path), ['visibility' => 'private']);
        } catch (Throwable) {
            $stored = false;
        }
        if ($stored !== $path) {
            $this->cleanup($existing->id, $path);
            Log::warning('Equipment CILT upload failed.', ['unit_id' => $existing->id]);
            throw ValidationException::withMessages(['cilt_pdf' => 'PDF CILT gagal disimpan. Dokumen sebelumnya tetap tersedia.']);
        }

        try {
            DB::transaction(function () use ($actor, $existing, $file, $path): void {
                $unit = EquipmentInspectionUnit::query()->whereKey($existing->id)->lockForUpdate()->firstOrFail();
                $oldPath = $unit->cilt_path;
                $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
                $name = Str::limit(preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?: 'Standar CILT.pdf', 240, '');
                $unit->forceFill([
                    'cilt_path' => $path,
                    'cilt_original_name' => $name,
                    'cilt_size' => $file->getSize(),
                    'cilt_updated_at' => now(),
                    'cilt_uploaded_by' => $actor->id,
                ])->save();
                $this->audit($unit, $actor, $oldPath ? 'cilt_replaced' : 'cilt_uploaded');
                // Never remove the previous PDF before the outermost transaction commits.
                DB::afterCommit(fn () => $this->cleanup($unit->id, $oldPath));
            });
        } catch (Throwable $exception) {
            $this->cleanup($existing->id, $path);
            throw $exception;
        }
    }

    public function delete(User $actor, EquipmentInspectionUnit $existing): void
    {
        DB::transaction(function () use ($actor, $existing): void {
            $unit = EquipmentInspectionUnit::query()->whereKey($existing->id)->lockForUpdate()->firstOrFail();
            $oldPath = $unit->cilt_path;
            if ($oldPath === null) {
                return;
            }
            $unit->forceFill([
                'cilt_path' => null, 'cilt_original_name' => null, 'cilt_size' => null,
                'cilt_updated_at' => null, 'cilt_uploaded_by' => null,
            ])->save();
            $this->audit($unit, $actor, 'cilt_deleted');
            DB::afterCommit(fn () => $this->cleanup($unit->id, $oldPath));
        });
    }

    /** Only safe display metadata; no filesystem path or uploader identity. */
    public function describe(EquipmentInspectionUnit $unit): ?array
    {
        if (blank($unit->cilt_path)) {
            return null;
        }

        return [
            'name' => $unit->cilt_original_name ?: 'Standar CILT.pdf',
            'updated_at' => $unit->cilt_updated_at?->toIso8601String(),
            'available' => $this->available($unit),
        ];
    }

    public function response(EquipmentInspectionUnit $unit): Response
    {
        abort_unless($this->available($unit), 404, 'Dokumen Standar CILT tidak tersedia.');

        try {
            $stream = Storage::disk(self::DISK)->readStream($unit->cilt_path);
        } catch (Throwable) {
            $stream = false;
        }
        if (! is_resource($stream)) {
            Log::warning('Equipment CILT document unreadable.', ['unit_id' => $unit->id]);
            abort(404, 'Dokumen Standar CILT tidak tersedia.');
        }

        // Open before sending: replacing/deleting the old path cannot switch the
        // document halfway through this response or leak a filesystem exception.
        return response()->stream(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Standar-CILT.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    private function available(EquipmentInspectionUnit $unit): bool
    {
        if (blank($unit->cilt_path)) {
            return false;
        }
        try {
            if ($this->validPath($unit->id, $unit->cilt_path) && Storage::disk(self::DISK)->exists($unit->cilt_path)) {
                return true;
            }
        } catch (Throwable) {
            // Record only the unit identifier, never the private path.
        }
        Log::warning('Equipment CILT document unavailable.', ['unit_id' => $unit->id]);

        return false;
    }

    private function validPath(int $unitId, ?string $path): bool
    {
        return is_string($path) && preg_match('~\Aequipment-cilt/'.$unitId.'/[a-f0-9-]{36}\.pdf\z~D', $path) === 1;
    }

    private function cleanup(int $unitId, ?string $path): void
    {
        if ($path === null) {
            return;
        }
        try {
            if ($this->validPath($unitId, $path) && Storage::disk(self::DISK)->delete($path)) {
                return;
            }
        } catch (Throwable) {
            // A cleanup failure must not roll back a committed replacement.
        }
        Log::warning('Equipment CILT cleanup needs attention.', ['unit_id' => $unitId]);
    }

    private function audit(EquipmentInspectionUnit $unit, User $actor, string $event): void
    {
        $unit->logs()->create(['actor_user_id' => $actor->id, 'event' => $event, 'created_at' => now()]);
    }
}
