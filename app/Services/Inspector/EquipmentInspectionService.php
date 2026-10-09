<?php

namespace App\Services\Inspector;

use App\Models\EquipmentInspection;
use App\Models\EquipmentInspectionSignature;
use App\Models\User;
use App\Policies\EquipmentInspectionPolicy;
use App\Support\Inspector\EquipmentFormCatalog;
use App\Support\Inspector\EquipmentInspectionNumberGenerator;
use App\Support\Inspector\EquipmentInspectionSnapshot;
use App\Support\Inspector\InspectionImageStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class EquipmentInspectionService
{
    public function __construct(
        private readonly InspectionImageStorage $images,
        private readonly EquipmentInspectionNumberGenerator $numbers,
    ) {}

    public function save(User $actor, ?EquipmentInspection $existing, ?string $formSlug, array $data, array $uploads): EquipmentInspection
    {
        $createdPaths = [];
        $deletedPaths = [];
        try {
            $inspection = DB::transaction(function () use ($actor, $existing, $formSlug, $data, $uploads, &$createdPaths, &$deletedPaths): EquipmentInspection {
                abort_unless($actor->hasRole(User::ROLE_INSPECTOR), 403);
                $isNew = $existing === null;
                if ($existing) {
                    $inspection = EquipmentInspection::query()->whereKey($existing->id)->lockForUpdate()->firstOrFail();
                    abort_unless((new EquipmentInspectionPolicy)->view($actor, $inspection), 403);
                    $this->assertEditable($inspection, (int) $data['lock_version']);
                    $inspection->lock_version++;
                } else {
                    $template = EquipmentFormCatalog::find((string) $formSlug);
                    abort_if($template === null, 404);
                    $inspection = EquipmentInspection::create([
                        'public_id' => (string) Str::uuid(),
                        'form_slug' => $template['id'],
                        'form_name' => $template['name'],
                        'template_snapshot' => $template,
                        'template_hash' => EquipmentInspectionSnapshot::hash($template),
                        'inspector_user_id' => $actor->id,
                        'inspector_name' => $actor->name,
                        'inspection_date' => $data['inspection_date'],
                        'status' => EquipmentInspection::STATUS_DRAFT,
                        'document_version' => 1,
                        'lock_version' => 1,
                    ]);
                    $position = 0;
                    foreach ($template['groups'] as $group) {
                        foreach ($group['items'] as $item) {
                            $inspection->answers()->create([
                                'item_key' => $item['id'], 'position' => ++$position, 'item_label' => $item['label'],
                                'group_key' => $group['id'], 'group_label' => $group['name'],
                            ]);
                        }
                    }
                }

                $this->validateDate($data['inspection_date']);
                $inspection->inspection_date = $data['inspection_date'];
                $inspection->save();
                $answers = $inspection->answers()->withCount('attachments')->get()->keyBy('item_key');
                $keys = $answers->keys()->all();
                $submittedKeys = array_keys($data['answers']);
                sort($keys);
                sort($submittedKeys);
                if ($keys !== $submittedKeys || array_diff(array_keys($uploads), $keys)) {
                    throw ValidationException::withMessages(['answers' => 'Daftar item berbeda dari snapshot laporan. Muat ulang halaman.']);
                }

                $deletedIds = array_map('intval', $data['delete_attachments'] ?? []);
                $deleting = $inspection->attachments()->whereIn('id', $deletedIds)->get();
                if ($deleting->count() !== count($deletedIds)) {
                    throw ValidationException::withMessages(['delete_attachments' => 'Lampiran yang dipilih tidak tersedia pada laporan ini.']);
                }
                foreach ($deleting as $file) {
                    $deletedPaths[] = $file->path;
                    $file->delete();
                    $this->log($inspection, $actor, 'attachment_removed', ['attachment_id' => $file->id, 'answer_id' => $file->equipment_inspection_answer_id]);
                }

                $changes = [];
                foreach ($answers as $key => $answer) {
                    $value = $data['answers'][$key];
                    Validator::make($value, [
                        'rating' => ['nullable', Rule::in(['A', 'B', 'C'])],
                        'remark' => ['nullable', 'string', 'max:2000'],
                    ])->validate();
                    $rating = $value['rating'] ?: null;
                    $remark = filled($value['remark'] ?? null) ? trim($value['remark']) : null;
                    if ($answer->rating !== $rating || $answer->remark !== $remark) {
                        $changes[] = ['item_key' => $key, 'before' => ['rating' => $answer->rating, 'remark' => $answer->remark], 'after' => ['rating' => $rating, 'remark' => $remark]];
                    }
                    $answer->update(['rating' => $rating, 'remark' => $remark]);
                    $files = $uploads[$key] ?? [];
                    if ($files !== [] && ! in_array($rating, ['B', 'C'], true)) {
                        throw ValidationException::withMessages(['photos.'.$key => 'Foto baru hanya dapat ditambahkan pada item B atau C.']);
                    }
                    $remainingCount = $answer->attachments_count - $deleting->where('equipment_inspection_answer_id', $answer->id)->count();
                    if ($remainingCount + count($files) > 3) {
                        throw ValidationException::withMessages(['photos.'.$key => 'Maksimal 3 foto tersimpan per item.']);
                    }
                    foreach ($files as $upload) {
                        $stored = $this->images->photo($upload, $inspection->public_id);
                        $createdPaths[] = $stored['path'];
                        $file = $answer->attachments()->create([
                            ...$stored, 'equipment_inspection_id' => $inspection->id, 'uploaded_by' => $actor->id,
                        ]);
                        $this->log($inspection, $actor, 'attachment_added', ['attachment_id' => $file->id, 'item_key' => $key]);
                    }
                }

                $this->log($inspection, $actor, $isNew ? 'draft_created' : 'draft_updated', [
                    'inspection_date' => $inspection->inspection_date->format('Y-m-d'), 'changes' => $changes,
                ]);

                return $inspection;
            });
        } catch (Throwable $exception) {
            $this->images->cleanup($createdPaths);
            throw $exception;
        }

        // The deleted metadata remains soft-deleted for audit and any storage cleanup follow-up.
        $this->images->cleanup($deletedPaths);

        return $inspection;
    }

    public function sign(User $actor, EquipmentInspection $existing, array $data): EquipmentInspection
    {
        $createdPaths = [];
        try {
            return DB::transaction(function () use ($actor, $existing, $data, &$createdPaths): EquipmentInspection {
                $inspection = EquipmentInspection::query()->whereKey($existing->id)->lockForUpdate()->firstOrFail();
                abort_unless((new EquipmentInspectionPolicy)->view($actor, $inspection), 403);
                if ($inspection->document_version !== (int) $data['document_version']) {
                    throw ValidationException::withMessages(['document_version' => 'Versi dokumen berubah. Buka ulang laporan sebelum tanda tangan.']);
                }
                $signature = $inspection->signatures()->where('document_version', $inspection->document_version)
                    ->where('role_key', EquipmentInspectionSignature::ROLE_INSPECTOR)->first();
                if ($signature && $inspection->status === EquipmentInspection::STATUS_READY) {
                    return $inspection;
                }

                $this->assertEditable($inspection, (int) $data['lock_version']);
                $this->validateDate($inspection->inspection_date->format('Y-m-d'));
                $inspection->load('answers.attachments');
                $expected = collect($inspection->template_snapshot['groups'])->flatMap(fn (array $group): array => $group['items'])->pluck('id')->sort()->values()->all();
                if ($expected === [] || $inspection->answers->pluck('item_key')->sort()->values()->all() !== $expected) {
                    throw ValidationException::withMessages(['answers' => 'Snapshot item laporan tidak lengkap.']);
                }
                $errors = [];
                foreach ($inspection->answers as $answer) {
                    if (! in_array($answer->rating, ['A', 'B', 'C'], true)) {
                        $errors['answers.'.$answer->item_key.'.rating'] = 'Item '.$answer->position.': pilih A, B, atau C sebelum tanda tangan.';
                    }
                    if (in_array($answer->rating, ['B', 'C'], true) && ! preg_match('/[^\s\p{Z}]/u', (string) $answer->remark)) {
                        $errors['answers.'.$answer->item_key.'.remark'] = 'Item '.$answer->position.': keterangan B/C wajib diisi sebelum tanda tangan.';
                    }
                    if ($answer->attachments->count() > 3) {
                        $errors['photos.'.$answer->item_key] = 'Jumlah lampiran melebihi batas.';
                    }
                    foreach ($answer->attachments as $file) {
                        if ((int) $file->equipment_inspection_id !== (int) $inspection->id) {
                            throw ValidationException::withMessages(['photos' => 'Relasi lampiran tidak sesuai laporan.']);
                        }
                        try {
                            $binary = $this->images->read($file->path, $file->sha256);
                        } catch (RuntimeException $exception) {
                            report($exception);
                            throw ValidationException::withMessages(['photos.'.$answer->item_key => 'Foto item '.$answer->position.' tidak tersedia atau berubah. Periksa lampiran sebelum tanda tangan.']);
                        }
                        $info = @getimagesizefromstring($binary);
                        if (! $info || $info['mime'] !== $file->mime_type || strlen($binary) !== (int) $file->size) {
                            $errors['photos.'.$answer->item_key] = 'Isi lampiran tidak valid.';
                        }
                    }
                }
                if ($errors !== []) {
                    throw ValidationException::withMessages($errors);
                }

                $stored = $this->images->signature($data['signature_data'], $inspection->public_id);
                $createdPaths[] = $stored['path'];
                $signedAt = now(config('app.timezone'));
                $this->numbers->assign($inspection, $signedAt);
                $inspection->forceFill([
                    'inspector_name' => $actor->name, 'signed_at' => $signedAt,
                    'status' => EquipmentInspection::STATUS_READY, 'lock_version' => $inspection->lock_version + 1,
                ])->save();
                $payload = EquipmentInspectionSnapshot::payload($inspection);
                $inspection->signatures()->create([
                    'role_key' => EquipmentInspectionSignature::ROLE_INSPECTOR,
                    'step' => EquipmentInspectionSignature::STEPS[EquipmentInspectionSignature::ROLE_INSPECTOR],
                    'signer_user_id' => $actor->id, 'signer_name' => $actor->name, 'signer_position' => 'Inspektor',
                    'signature_path' => $stored['path'], 'signature_sha256' => $stored['sha256'], 'signed_at' => $signedAt,
                    'document_version' => $inspection->document_version,
                    'content_hash' => EquipmentInspectionSnapshot::hash($payload), 'signed_payload' => $payload,
                ]);
                $this->log($inspection, $actor, 'inspector_signed');
                $this->log($inspection, $actor, 'number_issued', ['document_no' => $inspection->document_no]);
                $this->log($inspection, $actor, 'status_changed', ['from' => EquipmentInspection::STATUS_DRAFT, 'to' => EquipmentInspection::STATUS_READY]);

                return $inspection;
            });
        } catch (Throwable $exception) {
            $this->images->cleanup($createdPaths);
            throw $exception;
        }
    }

    private function assertEditable(EquipmentInspection $inspection, int $lockVersion): void
    {
        if (! $inspection->isDraft()) {
            throw ValidationException::withMessages(['inspection' => 'Laporan sudah ditandatangani dan terkunci.']);
        }
        if ($inspection->lock_version !== $lockVersion) {
            throw ValidationException::withMessages(['lock_version' => 'Laporan berubah di tab lain. Salin isian Anda bila perlu, lalu muat ulang sebelum menyimpan atau menandatangani.']);
        }
    }

    private function validateDate(string $date): void
    {
        Validator::make(['inspection_date' => $date], ['inspection_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now(config('app.timezone'))->toDateString()]])->validate();
    }

    private function log(EquipmentInspection $inspection, User $actor, string $event, array $metadata = []): void
    {
        $inspection->logs()->create([
            'actor_user_id' => $actor->id, 'event' => $event, 'document_version' => $inspection->document_version,
            'lock_version' => $inspection->lock_version, 'metadata' => $metadata, 'created_at' => now(),
        ]);
    }
}
