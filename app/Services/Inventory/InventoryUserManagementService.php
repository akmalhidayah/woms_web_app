<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryUserManagementService
{
    public function createUser(User $admin, array $data): InventoryUser
    {
        $this->assertAdmin($admin);
        $password = $this->temporaryPassword();

        $user = DB::transaction(fn (): InventoryUser => InventoryUser::query()->create([
            'name' => trim($data['name']),
            'email' => Str::lower(trim($data['email'])),
            'password' => $password,
            'employee_number' => $this->nullable($data['employee_number'] ?? null),
            'phone' => $this->nullable($data['phone'] ?? null),
            'position' => $this->nullable($data['position'] ?? null),
            'department' => trim($data['department']),
            'role' => 'user',
            'is_active' => (bool) ($data['is_active'] ?? true),
            'must_change_password' => true,
            'last_login_at' => null,
        ]));
        $user->setAttribute('temporary_password', $password);

        return $user;
    }

    public function updateStatus(User $admin, InventoryUser $inventoryUser, bool $isActive): InventoryUser
    {
        $this->assertAdmin($admin);

        return DB::transaction(function () use ($inventoryUser, $isActive): InventoryUser {
            $lockedUser = InventoryUser::query()->lockForUpdate()->findOrFail($inventoryUser->getKey());
            $lockedUser->forceFill(['is_active' => $isActive])->save();

            if (! $isActive) {
                $lockedUser->tokens()->delete();
            }

            return $lockedUser->refresh();
        });
    }

    public function resetPassword(User $admin, InventoryUser $inventoryUser): InventoryUser
    {
        $this->assertAdmin($admin);
        $password = $this->temporaryPassword();

        $user = DB::transaction(function () use ($inventoryUser, $password): InventoryUser {
            $lockedUser = InventoryUser::query()->lockForUpdate()->findOrFail($inventoryUser->getKey());
            $lockedUser->forceFill([
                'password' => $password,
                'must_change_password' => true,
            ])->save();
            $lockedUser->tokens()->delete();

            return $lockedUser->refresh();
        });

        $user->setAttribute('temporary_password', $password);

        return $user;
    }

    private function assertAdmin(User $admin): void
    {
        abort_unless($admin->exists && $admin->isAdmin(), 403);
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function temporaryPassword(): string
    {
        return Str::password(16, symbols: true);
    }
}
