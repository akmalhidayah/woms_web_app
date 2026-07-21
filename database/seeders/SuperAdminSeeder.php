<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the application's super administrator account.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'akmalhidayat826@gmail.com'],
            [
                'name' => 'Akmal Hidayah',
                'password' => 'bengkelmesin123',
                'role' => User::ROLE_ADMIN,
                'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
                'email_verified_at' => now(),
            ]
        );
    }
}
