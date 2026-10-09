<?php

namespace App\Support\Inspector;

use App\Models\EquipmentInspectionSignature;
use App\Models\UnitWorkSection;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class EquipmentInspectionApproverResolver
{
    public function resolve(): array
    {
        $sections = UnitWorkSection::query()->with('manager')->where('name', 'Machine Workshop')
            ->whereHas('unitWork', fn ($query) => $query->where('name', 'Workshop'))->get();
        $manager = $sections->count() === 1 ? $sections->first()->manager : null;
        if (! $manager || $manager->role !== User::ROLE_APPROVER || ! filter_var($manager->email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['approver' => 'Manager Machine Workshop: struktur harus unik, akun harus ber-role approver dan memiliki email valid. Periksa Workshop / Machine Workshop.']);
        }

        return [
            ['step_order' => 2, 'role_key' => EquipmentInspectionSignature::ROLE_MANAGER, 'user' => $manager, 'position' => 'Manager Machine Workshop'],
        ];
    }
}
