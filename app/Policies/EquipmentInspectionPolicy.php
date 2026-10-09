<?php

namespace App\Policies;

use App\Models\EquipmentInspection;
use App\Models\User;

class EquipmentInspectionPolicy
{
    public function view(User $user, EquipmentInspection $inspection): bool
    {
        return $user->hasRole(User::ROLE_INSPECTOR)
            && (int) $inspection->inspector_user_id === (int) $user->id;
    }

    public function update(User $user, EquipmentInspection $inspection): bool
    {
        return $this->view($user, $inspection) && $inspection->isDraft();
    }
}
