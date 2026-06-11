<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['id' => 5],
            [
                'name' => 'Akmal Hidayah',
                'email' => 'akmalhidayat826@gmail.com',
                'password' => 'bengkelmesin123',
                'role' => User::ROLE_ADMIN,
                'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
                'email_verified_at' => now(),
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'role' => User::ROLE_USER,
            ]
        );

        $this->call([
            StructureOrganizationUserSeeder::class,
            StructureOrganizationUserInitialSeeder::class,
            StructureOrganizationSeeder::class,
            FabricationConstructionContractSeeder::class,
            BengkelPicSeeder::class,
            BengkelTaskSeeder::class,
        ]);
    }
}
