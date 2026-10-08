<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\LhppBast;
use App\Models\LpjPpl;
use App\Support\BastDisplayLabel;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class LpjPplUpdateService
{
    /**
     * @param array<string, mixed> $validated
     */
    public function update(int $lhppId, array $validated, ?int $actorId = null): LpjPpl
    {
        $selectedTermin = (int) ($validated['selected_termin'] ?? 0);

        if (! in_array($selectedTermin, [1, 2], true)) {
            throw ValidationException::withMessages([
                'selected_termin' => 'Termin yang dipilih tidak valid.',
            ]);
        }

        $parent = LhppBast::query()
            ->with(['garansi:id,lhpp_bast_id,garansi_months', 'terminTwo:id,parent_lhpp_bast_id,approval_status'])
            ->where('termin_type', 'termin_1')
            ->findOrFail($lhppId);

        $this->validateEligibility($parent, $selectedTermin, $parent->terminTwo);

        $disk = Storage::disk('public');
        $newStoredPaths = [];
        $oldPathsToDelete = [];

        try {
            foreach (['lpj', 'ppl'] as $documentType) {
                $file = $validated["{$documentType}_document"] ?? null;

                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $filename = sprintf(
                    '%s-Termin-%d-%s-%s.pdf',
                    strtoupper($documentType),
                    $selectedTermin,
                    $this->safeFilenamePart((string) $parent->nomor_order),
                    Str::uuid(),
                );
                $path = $file->storeAs('lpj-ppl/'.$this->safeFilenamePart((string) $parent->nomor_order), $filename, 'public');

                if (! is_string($path) || $path === '') {
                    throw ValidationException::withMessages([
                        "{$documentType}_document" => 'Dokumen gagal disimpan. Silakan coba kembali.',
                    ]);
                }

                $newStoredPaths[$documentType] = $path;
            }

            $lpjPpl = DB::transaction(function () use (
                $lhppId,
                $validated,
                $selectedTermin,
                $actorId,
                $disk,
                $newStoredPaths,
                &$oldPathsToDelete,
            ): LpjPpl {
                $lockedParent = LhppBast::query()
                    ->where('termin_type', 'termin_1')
                    ->lockForUpdate()
                    ->findOrFail($lhppId);
                $lockedParent->load('garansi:id,lhpp_bast_id,garansi_months');

                $lockedTerminTwo = $selectedTermin === 2
                    ? LhppBast::query()
                        ->where('parent_lhpp_bast_id', $lockedParent->id)
                        ->where('termin_type', 'termin_2')
                        ->lockForUpdate()
                        ->first()
                    : null;

                $this->validateEligibility($lockedParent, $selectedTermin, $lockedTerminTwo);

                $lpjPpl = LpjPpl::query()
                    ->where('lhpp_bast_id', $lockedParent->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lpjPpl) {
                    $lpjPpl = new LpjPpl([
                        'lhpp_bast_id' => $lockedParent->id,
                        'created_by' => $actorId,
                    ]);
                }

                $suffix = "termin{$selectedTermin}";
                $numberAttributes = [
                    'lpj' => "lpj_number_{$suffix}",
                    'ppl' => "ppl_number_{$suffix}",
                ];
                $pathAttributes = [
                    'lpj' => "lpj_document_path_{$suffix}",
                    'ppl' => "ppl_document_path_{$suffix}",
                ];

                foreach ($numberAttributes as $documentType => $attribute) {
                    if (array_key_exists("{$documentType}_number", $validated)) {
                        $value = trim((string) ($validated["{$documentType}_number"] ?? ''));
                        $lpjPpl->{$attribute} = $value !== '' ? $value : null;
                    }
                }

                foreach ($pathAttributes as $documentType => $attribute) {
                    $oldPath = $lpjPpl->{$attribute};
                    $remove = (bool) ($validated["remove_{$documentType}_document"] ?? false);

                    if (isset($newStoredPaths[$documentType])) {
                        $lpjPpl->{$attribute} = $newStoredPaths[$documentType];
                        if (filled($oldPath) && $oldPath !== $newStoredPaths[$documentType]) {
                            $oldPathsToDelete[] = $oldPath;
                        }
                    } elseif ($remove) {
                        $lpjPpl->{$attribute} = null;
                        if (filled($oldPath)) {
                            $oldPathsToDelete[] = $oldPath;
                        }
                    }
                }

                $activeStatusField = $selectedTermin === 1 ? 'termin1_status' : 'termin2_status';
                $requestedStatus = (string) ($validated[$activeStatusField] ?? 'belum');

                if (! in_array($requestedStatus, ['belum', 'sudah'], true)) {
                    throw ValidationException::withMessages([
                        $activeStatusField => 'Status pembayaran tidak valid.',
                    ]);
                }

                if ($requestedStatus === 'sudah' && ! $this->isCompletePackage($lpjPpl, $selectedTermin, $disk)) {
                    throw ValidationException::withMessages([
                        $activeStatusField => $this->incompletePaymentMessage($selectedTermin, $lockedParent),
                    ]);
                }

                $lpjPpl->updated_by = $actorId;
                $lpjPpl->save();

                $lockedParent->{$activeStatusField} = $requestedStatus;
                $lockedParent->updated_by = $actorId;
                $lockedParent->save();

                return $lpjPpl->refresh();
            });
        } catch (Throwable $exception) {
            $this->cleanupPaths(array_values($newStoredPaths), 'new file after failed LPJ/PPL update');

            throw $exception;
        }

        $this->cleanupPaths(array_values(array_unique($oldPathsToDelete)), 'old file after successful LPJ/PPL update');

        return $lpjPpl;
    }

    private function validateEligibility(LhppBast $parent, int $selectedTermin, ?LhppBast $terminTwo): void
    {
        $garansiMonths = $parent->garansi?->garansi_months;

        if ($selectedTermin === 1) {
            if ($parent->approval_status !== LhppBast::APPROVAL_APPROVED) {
                $bastLabel = BastDisplayLabel::bastLabel('termin_1', $garansiMonths, BastDisplayLabel::isWithoutWarranty($garansiMonths));
                $documentLabel = BastDisplayLabel::isWithoutWarranty($garansiMonths) ? 'LPJ/PPL' : 'LPJ/PPL Termin 1';

                throw ValidationException::withMessages([
                    'lpj_ppl' => "{$documentLabel} hanya dapat diproses setelah {$bastLabel} disetujui.",
                ]);
            }

            return;
        }

        if (BastDisplayLabel::isWithoutWarranty($garansiMonths)) {
            throw ValidationException::withMessages([
                'lpj_ppl' => 'LPJ/PPL Termin 2 tidak tersedia untuk order tanpa garansi.',
            ]);
        }

        if ($garansiMonths === null) {
            throw ValidationException::withMessages([
                'lpj_ppl' => 'Data garansi belum tersedia sehingga LPJ/PPL Termin 2 belum dapat diproses.',
            ]);
        }

        if (($parent->termin1_status ?? 'belum') !== 'sudah') {
            throw ValidationException::withMessages([
                'lpj_ppl' => 'LPJ/PPL Termin 2 hanya dapat diproses setelah pembayaran Termin 1 selesai.',
            ]);
        }

        if (! $terminTwo) {
            throw ValidationException::withMessages([
                'lpj_ppl' => 'LPJ/PPL Termin 2 belum dapat diproses karena BAST Termin 2 belum dibuat.',
            ]);
        }

        if ($terminTwo->approval_status === LhppBast::APPROVAL_REJECTED) {
            throw ValidationException::withMessages([
                'lpj_ppl' => 'BAST Termin 2 ditolak dan harus dibuat ulang sebelum LPJ/PPL Termin 2 diproses.',
            ]);
        }

        if ($terminTwo->approval_status !== LhppBast::APPROVAL_APPROVED) {
            throw ValidationException::withMessages([
                'lpj_ppl' => 'LPJ/PPL Termin 2 hanya dapat diproses setelah BAST Termin 2 disetujui.',
            ]);
        }
    }

    private function isCompletePackage(LpjPpl $lpjPpl, int $selectedTermin, FilesystemAdapter $disk): bool
    {
        $suffix = "termin{$selectedTermin}";
        $lpjPath = $lpjPpl->{"lpj_document_path_{$suffix}"};
        $pplPath = $lpjPpl->{"ppl_document_path_{$suffix}"};

        return filled(trim((string) $lpjPpl->{"lpj_number_{$suffix}"}))
            && filled(trim((string) $lpjPpl->{"ppl_number_{$suffix}"}))
            && filled($lpjPath)
            && filled($pplPath)
            && $disk->exists($lpjPath)
            && $disk->exists($pplPath);
    }

    private function incompletePaymentMessage(int $selectedTermin, LhppBast $parent): string
    {
        $garansiMonths = $parent->garansi?->garansi_months;

        if ($selectedTermin === 1 && BastDisplayLabel::isWithoutWarranty($garansiMonths)) {
            return 'Pembayaran hanya dapat ditandai selesai setelah nomor dan dokumen LPJ/PPL lengkap.';
        }

        $stage = BastDisplayLabel::stageLabel($selectedTermin === 2 ? 'termin_2' : 'termin_1', $garansiMonths);

        return "Pembayaran {$stage} hanya dapat ditandai selesai setelah nomor dan dokumen LPJ/PPL {$stage} lengkap.";
    }

    /** @param list<string> $paths */
    private function cleanupPaths(array $paths, string $context): void
    {
        foreach ($paths as $path) {
            try {
                if (Storage::disk('public')->exists($path) && ! Storage::disk('public')->delete($path)) {
                    Log::warning('Failed to clean up LPJ/PPL file.', compact('path', 'context'));
                }
            } catch (Throwable $exception) {
                Log::warning('Failed to clean up LPJ/PPL file.', [
                    'path' => $path,
                    'context' => $context,
                    'exception' => $exception,
                ]);
            }
        }
    }

    private function safeFilenamePart(string $value): string
    {
        $safe = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $value), '-');

        return $safe !== '' ? $safe : 'order';
    }
}
