<?php

namespace App\Services;

use App\Models\OutlineAgreement;
use App\Models\OutlineAgreementHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutlineAgreementService
{
    public function createAgreement(array $data, User $actor): OutlineAgreement
    {
        return DB::transaction(function () use ($data, $actor): OutlineAgreement {
            $agreement = OutlineAgreement::create([
                'nomor_oa' => $data['nomor_oa'],
                'unit_work_id' => (int) $data['unit_work_id'],
                'jenis_kontrak' => $data['jenis_kontrak'],
                'nama_kontrak' => $data['nama_kontrak'],
                'nilai_kontrak_awal' => $data['nilai_kontrak_awal'],
                'periode_awal_start' => $data['periode_awal_start'],
                'periode_awal_end' => $data['periode_awal_end'],
                'current_total_nilai' => $data['nilai_kontrak_awal'],
                'current_period_start' => $data['periode_awal_start'],
                'current_period_end' => $data['periode_awal_end'],
                'status' => $this->resolveStatus($data['periode_awal_end']),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $history = $agreement->histories()->create([
                'revision_no' => 1,
                'tipe_perubahan' => OutlineAgreement::CHANGE_INITIAL,
                'nilai_tambahan' => 0,
                'periode_start' => $data['periode_awal_start'],
                'periode_end' => $data['periode_awal_end'],
                'snapshot_total_nilai' => $data['nilai_kontrak_awal'],
                'snapshot_period_start' => $data['periode_awal_start'],
                'snapshot_period_end' => $data['periode_awal_end'],
                'keterangan' => 'Initial contract created.',
                'payload_json' => null,
                'created_by' => $actor->id,
            ]);

            $agreement->update([
                'latest_history_id' => $history->id,
            ]);

            $this->syncTargets(
                $agreement,
                $data['target_years'] ?? [],
                $data['target_values'] ?? [],
            );

            return $agreement->fresh(['unitWork.department', 'histories.creator', 'yearlyTargets', 'latestHistory']);
        });
    }

    public function addAmendment(OutlineAgreement $agreement, array $data, User $actor): OutlineAgreementHistory
    {
        return DB::transaction(function () use ($agreement, $data, $actor): OutlineAgreementHistory {
            $agreement->refresh();

            $newTotal = (float) $agreement->current_total_nilai;
            $newPeriodEnd = $agreement->current_period_end?->format('Y-m-d');

            if (in_array($data['tipe_perubahan'], [
                OutlineAgreement::CHANGE_ADD_VALUE,
                OutlineAgreement::CHANGE_EXTEND_AND_ADD_VALUE,
            ], true)) {
                $newTotal += (float) ($data['nilai_tambahan'] ?? 0);
            }

            if (in_array($data['tipe_perubahan'], [
                OutlineAgreement::CHANGE_EXTEND,
                OutlineAgreement::CHANGE_EXTEND_AND_ADD_VALUE,
            ], true)) {
                if (! empty($data['periode_end']) && $newPeriodEnd && $data['periode_end'] < $newPeriodEnd) {
                    throw ValidationException::withMessages([
                        'periode_end' => 'Periode akhir baru tidak boleh lebih kecil dari periode aktif saat ini.',
                    ]);
                }

                $newPeriodEnd = $data['periode_end'];
            }

            $revisionNo = ((int) $agreement->histories()->max('revision_no')) + 1;

            $history = $agreement->histories()->create([
                'revision_no' => $revisionNo,
                'tipe_perubahan' => $data['tipe_perubahan'],
                'nilai_tambahan' => (float) ($data['nilai_tambahan'] ?? 0),
                'periode_start' => $agreement->current_period_start,
                'periode_end' => $newPeriodEnd,
                'snapshot_total_nilai' => $newTotal,
                'snapshot_period_start' => $agreement->current_period_start,
                'snapshot_period_end' => $newPeriodEnd,
                'keterangan' => $data['keterangan'] ?? null,
                'payload_json' => [
                    'before_total' => (float) $agreement->current_total_nilai,
                    'before_period_end' => optional($agreement->current_period_end)->format('Y-m-d'),
                ],
                'created_by' => $actor->id,
            ]);

            $agreement->update([
                'current_total_nilai' => $newTotal,
                'current_period_end' => $newPeriodEnd,
                'latest_history_id' => $history->id,
                'status' => $this->resolveStatus((string) $newPeriodEnd),
                'updated_by' => $actor->id,
            ]);

            return $history->fresh('creator');
        });
    }

    public function updateAgreement(OutlineAgreement $agreement, array $data, User $actor): OutlineAgreement
    {
        return DB::transaction(function () use ($agreement, $data, $actor): OutlineAgreement {
            $agreement->refresh();

            $oldTotal = (float) $agreement->current_total_nilai;
            $newTotal = (float) $data['current_total_nilai'];
            $oldPeriodEnd = $agreement->current_period_end?->format('Y-m-d');
            $newPeriodEnd = (string) $data['current_period_end'];
            $delta = round($newTotal - $oldTotal, 2);
            $periodChanged = $oldPeriodEnd !== $newPeriodEnd;

            if ($agreement->current_period_start && $newPeriodEnd < $agreement->current_period_start->format('Y-m-d')) {
                throw ValidationException::withMessages([
                    'current_period_end' => 'Periode akhir aktif tidak boleh lebih kecil dari periode mulai aktif.',
                ]);
            }

            $agreement->update([
                'nomor_oa' => $data['nomor_oa'],
                'unit_work_id' => (int) $data['unit_work_id'],
                'jenis_kontrak' => $data['jenis_kontrak'],
                'nama_kontrak' => $data['nama_kontrak'],
                'current_total_nilai' => $newTotal,
                'current_period_end' => $newPeriodEnd,
                'status' => $this->resolveStatus($newPeriodEnd),
                'updated_by' => $actor->id,
            ]);

            if ($delta !== 0.0 || $periodChanged || ! empty($data['keterangan_perubahan'])) {
                $type = match (true) {
                    $delta > 0 && $periodChanged => OutlineAgreement::CHANGE_EXTEND_AND_ADD_VALUE,
                    $delta > 0 => OutlineAgreement::CHANGE_ADD_VALUE,
                    $delta === 0.0 && $periodChanged => OutlineAgreement::CHANGE_EXTEND,
                    default => OutlineAgreement::CHANGE_REVISION,
                };

                $history = $agreement->histories()->create([
                    'revision_no' => ((int) $agreement->histories()->max('revision_no')) + 1,
                    'tipe_perubahan' => $type,
                    'nilai_tambahan' => max($delta, 0),
                    'periode_start' => $agreement->current_period_start,
                    'periode_end' => $newPeriodEnd,
                    'snapshot_total_nilai' => $newTotal,
                    'snapshot_period_start' => $agreement->current_period_start,
                    'snapshot_period_end' => $newPeriodEnd,
                    'keterangan' => $data['keterangan_perubahan'] ?? 'Master OA diperbarui melalui menu edit.',
                    'payload_json' => [
                        'before_total' => $oldTotal,
                        'after_total' => $newTotal,
                        'before_period_end' => $oldPeriodEnd,
                        'after_period_end' => $newPeriodEnd,
                    ],
                    'created_by' => $actor->id,
                ]);

                $agreement->update([
                    'latest_history_id' => $history->id,
                ]);
            }

            $this->syncTargets(
                $agreement,
                $data['target_years'] ?? [],
                $data['target_values'] ?? [],
            );

            return $agreement->fresh(['unitWork.department', 'histories.creator', 'yearlyTargets', 'latestHistory']);
        });
    }

    /**
     * @param  array<int, mixed>  $years
     * @param  array<int, mixed>  $values
     */
    private function syncTargets(OutlineAgreement $agreement, array $years, array $values): void
    {
        $rows = [];

        foreach ($years as $index => $year) {
            $value = $values[$index] ?? null;

            if (! $year || $value === null || $value === '') {
                continue;
            }

            $rows[(int) $year] = [
                'tahun' => (int) $year,
                'nilai_target' => (float) $value,
            ];
        }

        if ($rows === []) {
            return;
        }

        $agreement->yearlyTargets()->delete();
        $agreement->yearlyTargets()->createMany(array_values($rows));
    }

    private function resolveStatus(string $periodEnd): string
    {
        return now()->toDateString() > $periodEnd
            ? OutlineAgreement::STATUS_EXPIRED
            : OutlineAgreement::STATUS_ACTIVE;
    }
}
