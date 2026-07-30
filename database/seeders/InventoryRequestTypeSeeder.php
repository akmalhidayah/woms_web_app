<?php

namespace Database\Seeders;

use App\Models\Inventory\InventoryRequestType;
use Illuminate\Database\Seeder;

class InventoryRequestTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->requestTypes() as $requestType) {
            InventoryRequestType::query()->updateOrCreate(
                ['code' => $requestType['code']],
                $requestType
            );
        }
    }

    /**
     * @return list<array{
     *     code: string,
     *     name: string,
     *     requires_damaged_photo: bool,
     *     requires_new_item_photo: bool,
     *     is_active: bool
     * }>
     */
    private function requestTypes(): array
    {
        return [
            [
                'code' => 'new_request',
                'name' => 'Permintaan Baru',
                'requires_damaged_photo' => false,
                'requires_new_item_photo' => false,
                'is_active' => true,
            ],
            [
                'code' => 'new_replacement',
                'name' => 'Penggantian Baru',
                'requires_damaged_photo' => false,
                'requires_new_item_photo' => false,
                'is_active' => true,
            ],
            [
                'code' => 'damaged_replacement',
                'name' => 'Penggantian Alat Rusak',
                'requires_damaged_photo' => true,
                'requires_new_item_photo' => false,
                'is_active' => true,
            ],
            [
                'code' => 'operational',
                'name' => 'Keperluan Operasional',
                'requires_damaged_photo' => false,
                'requires_new_item_photo' => false,
                'is_active' => true,
            ],
            [
                'code' => 'other',
                'name' => 'Lainnya',
                'requires_damaged_photo' => false,
                'requires_new_item_photo' => false,
                'is_active' => true,
            ],
        ];
    }
}
