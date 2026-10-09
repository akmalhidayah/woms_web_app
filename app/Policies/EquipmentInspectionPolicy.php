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
        return ($this->monitor($user) || $this->view($user, $inspection))
            && ! $inspection->trashed()
            && in_array($inspection->status, [EquipmentInspection::STATUS_DRAFT, EquipmentInspection::STATUS_READY,
                EquipmentInspection::STATUS_MANAGER, EquipmentInspection::STATUS_LEADER, EquipmentInspection::STATUS_REVISION], true)
            && $inspection->final_pdf_path === null;
    }
}
