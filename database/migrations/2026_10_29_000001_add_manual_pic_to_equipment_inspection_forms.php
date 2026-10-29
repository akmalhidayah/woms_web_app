<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $missing = collect([
            'pic_name',
            'pic_photo_path',
            'pic_photo_mime_type',
            'pic_photo_size',
            'pic_photo_sha256',
        ])->reject(fn (string $column): bool => Schema::hasColumn('equipment_inspection_forms', $column));

        if ($missing->isNotEmpty()) {
            Schema::table('equipment_inspection_forms', function (Blueprint $table) use ($missing): void {
                if ($missing->contains('pic_name')) {
                    $table->string('pic_name')->nullable()->after('description');
                }
                if ($missing->contains('pic_photo_path')) {
                    $table->string('pic_photo_path')->nullable()->after('pic_name');
                }
                if ($missing->contains('pic_photo_mime_type')) {
                    $table->string('pic_photo_mime_type', 64)->nullable()->after('pic_photo_path');
                }
                if ($missing->contains('pic_photo_size')) {
                    $table->unsignedInteger('pic_photo_size')->nullable()->after('pic_photo_mime_type');
                }
                if ($missing->contains('pic_photo_sha256')) {
                    $table->char('pic_photo_sha256', 64)->nullable()->after('pic_photo_size');
                }
            });
        }

        if (! Schema::hasTable('bengkel_pics') || ! Schema::hasColumn('equipment_inspection_forms', 'bengkel_pic_id')) {
            return;
        }

        DB::table('equipment_inspection_forms')
            ->join('bengkel_pics', 'bengkel_pics.id', '=', 'equipment_inspection_forms.bengkel_pic_id')
            ->select([
                'equipment_inspection_forms.id as form_id',
                'equipment_inspection_forms.pic_name as current_pic_name',
                'equipment_inspection_forms.pic_photo_path as current_pic_photo_path',
                'bengkel_pics.name as pic_name',
                'bengkel_pics.avatar_path as avatar_path',
            ])
            ->orderBy('equipment_inspection_forms.id')
            ->each(function (object $row): void {
                $values = filled($row->current_pic_name) ? [] : ['pic_name' => $row->pic_name];

                if (blank($row->current_pic_photo_path)) {
                    try {
                        $photo = $this->copyLegacyPhoto((int) $row->form_id, $row->avatar_path);
                        if ($photo !== null) {
                            $values = [...$values, ...$photo];
                        }
                    } catch (\Throwable $exception) {
                        report($exception);
                    }
                }

                if ($values !== []) {
                    DB::table('equipment_inspection_forms')
                        ->where('id', $row->form_id)
                        ->update($values);
                }
            });
    }

    public function down(): void
    {
        Schema::table('equipment_inspection_forms', function (Blueprint $table): void {
            $table->dropColumn([
                'pic_name',
                'pic_photo_path',
                'pic_photo_mime_type',
                'pic_photo_size',
                'pic_photo_sha256',
            ]);
        });
    }

    /**
     * @return array<string, int|string>|null
     */
    private function copyLegacyPhoto(int $formId, ?string $legacyPath): ?array
    {
        if (! $legacyPath || str_contains($legacyPath, '..') || ! Storage::disk('public')->exists($legacyPath)) {
            return null;
        }

        $binary = Storage::disk('public')->get($legacyPath);
        $imageInfo = is_string($binary) && $binary !== '' ? @getimagesizefromstring($binary) : false;
        if (! $imageInfo || ! in_array($imageInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return null;
        }

        $mime = match ($imageInfo[2]) {
            IMAGETYPE_PNG => 'image/png',
            IMAGETYPE_WEBP => 'image/webp',
            default => 'image/jpeg',
        };
        $extension = match ($imageInfo[2]) {
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            default => 'jpg',
        };
        $hash = hash('sha256', $binary);
        $path = 'equipment-inspection-pics/legacy-'.$formId.'-'.substr($hash, 0, 16).'.'.$extension;

        if (! Storage::disk('local')->exists($path)
            && ! Storage::disk('local')->put($path, $binary, ['visibility' => 'private'])) {
            return null;
        }

        return [
            'pic_photo_path' => $path,
            'pic_photo_mime_type' => $mime,
            'pic_photo_size' => strlen($binary),
            'pic_photo_sha256' => $hash,
        ];
    }
};
