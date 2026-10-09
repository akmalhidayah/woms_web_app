<?php

namespace App\Support\Inspector;

use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class EquipmentInspectionApproverResolver
{
    public function resolve(): array
    {
        $sections = UnitWorkSection::query()->with('manager')->where('name', 'Machine Workshop')
            ->whereHas('unitWork', fn ($query) => $query->where('name', 'Workshop'))->get();
        $units = UnitWork::query()->with('seniorManager')->where('name', 'TPM Officer')->get();
        $manager = $sections->count() === 1 ? $sections->first()->manager : null;
        $leader = $units->count() === 1 ? $units->first()->seniorManager : null;
        foreach (['Manager Machine Workshop' => $manager, 'Senior Manager TPM / Leader Gugus' => $leader] as $label => $user) {
            if (! $user || $user->role !== User::ROLE_APPROVER || ! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages(['approver' => $label.': struktur harus unik, akun harus ber-role approver dan memiliki email valid. Periksa Workshop / Machine Workshop dan TPM Officer.']);
            }
        }

        return [
            ['step_order' => 2, 'role_key' => 'manager_workshop', 'user' => $manager, 'position' => 'Manager Machine Workshop'],
            ['step_order' => 3, 'role_key' => 'leader_gugus', 'user' => $leader, 'position' => 'Senior Manager TPM / Leader Gugus'],
        ];
    }
}
