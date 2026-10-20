<?php

namespace App\Policies;

use App\Models\EquipmentInspection;
use App\Models\User;

class EquipmentInspectionPolicy
{
    public function monitor(User $user): bool
    {
        return $user->isAdmin() && \App\Support\AdminMenuRegistry::canAccess($user, \App\Support\AdminMenuRegistry::MENU_INSPEKSI);
    }

    public function view(User $user, EquipmentInspection $inspection): bool
    {
        return $user->hasRole(User::ROLE_INSPECTOR)
            && (int) $inspection->inspector_user_id === (int) $user->id;
    }

    public function update(User $user, EquipmentInspection $inspection): bool
    {
        return $this->view($user, $inspection) && $inspection->isDraft();
    }

    public function delete(User $user, EquipmentInspection $inspection): bool
    {
        if ($inspection->trashed()) {
            return false;
        }
        if ($this->monitor($user)) {
            return true;
        }

        $signatureCount = array_key_exists('signatures_count', $inspection->getAttributes())
            ? (int) $inspection->signatures_count : $inspection->signatures()->count();
        $approvalCount = array_key_exists('approvals_count', $inspection->getAttributes())
            ? (int) $inspection->approvals_count : $inspection->approvals()->count();

        return $this->view($user, $inspection)
            && $inspection->status === EquipmentInspection::STATUS_DRAFT
            && $inspection->document_no === null
            && $inspection->signed_at === null
            && $signatureCount === 0
            && $approvalCount === 0;
    }
}
