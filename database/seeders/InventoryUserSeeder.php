<?php

namespace Database\Seeders;

use App\Models\Inventory\InventoryUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class InventoryUserSeeder extends Seeder
{
    public function run(): void
    {
        $name = $this->requiredEnvironmentValue('INVENTORY_SEED_NAME');
        $email = strtolower($this->requiredEnvironmentValue('INVENTORY_SEED_EMAIL'));
        $password = $this->requiredEnvironmentValue('INVENTORY_SEED_PASSWORD');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('INVENTORY_SEED_EMAIL harus berupa alamat email yang valid.');
        }

        if (mb_strlen($password) < 8) {
            throw new RuntimeException('INVENTORY_SEED_PASSWORD minimal harus terdiri dari 8 karakter.');
        }

        $employeeNumber = $this->nullableEnvironmentValue('INVENTORY_SEED_EMPLOYEE_NUMBER');
        $phone = $this->nullableEnvironmentValue('INVENTORY_SEED_PHONE');
        $position = $this->nullableEnvironmentValue('INVENTORY_SEED_POSITION');
        $department = $this->nullableEnvironmentValue('INVENTORY_SEED_DEPARTMENT') ?? 'Workshop';

        DB::transaction(function () use (
            $name,
            $email,
            $password,
            $employeeNumber,
            $phone,
            $position,
            $department,
        ): void {
            $user = InventoryUser::withTrashed()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            $passwordChanged = $user === null || ! Hash::check($password, $user->password);

            if ($user === null) {
                $user = new InventoryUser;
                $user->email = $email;
            }

            $user->fill([
                'name' => $name,
                'password' => $password,
                'employee_number' => $employeeNumber,
                'phone' => $phone,
                'position' => $position,
                'department' => $department,
                'role' => 'user',
                'is_active' => true,
                'must_change_password' => true,
            ]);

            if ($user->trashed()) {
                $user->deleted_at = null;
            }

            $user->save();

            // Bila seeder dipakai untuk mengganti password akun yang sudah ada,
            // cabut token lama agar perangkat wajib login kembali.
            if ($passwordChanged && $user->tokens()->exists()) {
                $user->tokens()->delete();
            }

            $this->command?->info(sprintf(
                'Akun Inventory siap: %s <%s> (ID: %s, wajib ganti password: ya)',
                $user->name,
                $user->email,
                $user->getKey(),
            ));
        });
    }

    private function requiredEnvironmentValue(string $key): string
    {
        $value = trim((string) getenv($key));

        if ($value === '') {
            throw new RuntimeException(
                sprintf(
                    '%s belum diisi. Jalankan seeder dengan environment variable tersebut.',
                    $key,
                )
            );
        }

        return $value;
    }

    private function nullableEnvironmentValue(string $key): ?string
    {
        $value = trim((string) getenv($key));

        return $value === '' ? null : $value;
    }
}
