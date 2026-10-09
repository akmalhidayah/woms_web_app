<?php

namespace App\Services\Inspector;

use App\Models\EquipmentInspection;
use App\Models\EquipmentInspectionApproval;
use App\Models\User;
use App\Policies\EquipmentInspectionPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EquipmentInspectionDeletionService
{
    public function delete(User $actor, EquipmentInspection $existing, int $lockVersion): void
    {
        DB::transaction(function () use ($actor, $existing, $lockVersion): void {
            $inspection = EquipmentInspection::query()->whereKey($existing->id)->lockForUpdate()->firstOrFail();
            abort_unless((new EquipmentInspectionPolicy)->delete($actor, $inspection), 403);
            if ($inspection->lock_version !== $lockVersion) {
                throw ValidationException::withMessages(['inspection' => 'Laporan telah berubah. Muat ulang halaman sebelum menghapus.']);
            }
            $inspection->approvals()->whereIn('status', [EquipmentInspectionApproval::LOCKED, EquipmentInspectionApproval::PENDING])
                ->update(['status' => EquipmentInspectionApproval::CANCELLED]);
            $inspection->approvals()->update(['token_hash' => null, 'token_encrypted' => null, 'token_expires_at' => null]);
            $inspection->increment('lock_version');
            EquipmentInspectionWorkflow::audit($inspection, $actor, 'inspection_deleted', ['previous_status' => $inspection->status]);
            // Keep signatures, snapshots, document numbers and attachments recoverable for audit.
            $inspection->delete();
        });
    }
}
