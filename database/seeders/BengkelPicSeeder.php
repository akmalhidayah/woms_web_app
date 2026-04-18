<?php

namespace Database\Seeders;

use App\Models\BengkelPic;
use Illuminate\Database\Seeder;

class BengkelPicSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            'Sudirman. MJ',
            'Aswar',
            'Ikhlas',
            'Adil Makmur',
            'Arsyad',
            'Tahriruddin',
            'Firman Ferdinan',
            'Herman. S',
            'Herman. R',
            'Dahlan',
            'Faisal',
            'Suardi',
            'Rusman Majid',
            'Mustari Mustafa',
            'Ali asdar',
            'Haerullah',
            'Rusmanto. K',
            'Jumardi',
            'Yakobus. P',
            'Sudirman',
            'Juniardi',
            'Makmur',
            'Akbar',
            'Muh. Yunus. T',
            'Satria. P',
            'Fadhil Pratama',
            'Wahyu Pratama',
        ])->each(static function (string $name): void {
            BengkelPic::query()->firstOrCreate(
                ['name' => $name],
                ['avatar_path' => null]
            );
        });
    }
}
