<?php

namespace Database\Seeders;

use App\Models\BengkelPic;
use App\Models\BengkelTask;
use Illuminate\Database\Seeder;

class BengkelTaskSeeder extends Seeder
{
    public function run(): void
    {
        $pics = BengkelPic::query()
            ->orderBy('name')
            ->get(['id', 'name', 'avatar_path'])
            ->keyBy(static fn (BengkelPic $pic): string => mb_strtolower(trim($pic->name)));

        $profilesFor = static function (array $names) use ($pics): array {
            $profiles = [];

            foreach ($names as $name) {
                $cleanName = trim((string) $name);

                if ($cleanName === '') {
                    continue;
                }

                /** @var \App\Models\BengkelPic|null $pic */
                $pic = $pics->get(mb_strtolower($cleanName));

                $profiles[] = [
                    'id' => $pic?->id,
                    'name' => $pic?->name ?? $cleanName,
                    'avatar_path' => $pic?->avatar_path,
                ];
            }

            return $profiles;
        };

        $tasks = [
            [
                'job_name' => 'Fabrikasi Hopper Batu Kapur',
                'notification_number' => '174010101',
                'unit_work' => 'Unit of Machine Workshop',
                'seksi' => 'Section of Machine Workshop',
                'usage_plan_date' => '2026-04-24',
                'catatan' => 'Regu Fabrikasi',
                'pics' => ['Herman R', 'Herman S'],
            ],
            [
                'job_name' => 'Fabrikasi Cover Conveyor',
                'notification_number' => '174010102',
                'unit_work' => 'Unit of Machine Maintenance 1',
                'seksi' => 'Section of Crusher Machine & Conveyor Maint',
                'usage_plan_date' => '2026-04-25',
                'catatan' => 'Regu Fabrikasi',
                'pics' => ['Sudirman. MJ', 'Aswar'],
            ],
            [
                'job_name' => 'Fabrikasi Chute Raw Mill',
                'notification_number' => '174010103',
                'unit_work' => 'Unit of Cement Production',
                'seksi' => 'Section of Line 4 Finish Mill Operation',
                'usage_plan_date' => '2026-04-26',
                'catatan' => 'Regu Fabrikasi',
                'pics' => ['Ikhlas', 'Adil Makmur'],
            ],
            [
                'job_name' => 'Fabrikasi Dudukan Motor',
                'notification_number' => '174010104',
                'unit_work' => 'Unit of Reliability Maintenance',
                'seksi' => 'Section of PGO',
                'usage_plan_date' => '2026-04-27',
                'catatan' => 'Regu Fabrikasi',
                'pics' => ['Arsyad'],
            ],
            [
                'job_name' => 'Fabrikasi Spiral Screw',
                'notification_number' => '174010105',
                'unit_work' => 'Unit of Elins Maintenance 2',
                'seksi' => 'Section of EP/DC Maintenance',
                'usage_plan_date' => '2026-04-28',
                'catatan' => 'Regu Fabrikasi',
                'pics' => ['Tahriruddin', 'Firman Ferdinan'],
            ],
            [
                'job_name' => 'Fabrikasi Bracket Sensor',
                'notification_number' => '174010106',
                'unit_work' => 'Unit of Elins Workshop',
                'seksi' => 'Section of Elins Workshop',
                'usage_plan_date' => '2026-04-29',
                'catatan' => 'Regu Fabrikasi',
                'pics' => ['Herman R'],
            ],
            [
                'job_name' => 'Fabrikasi Shaft Support',
                'notification_number' => '174010107',
                'unit_work' => 'Unit of Machine Maintenance 1',
                'seksi' => 'Section of Line 2/3 FM Machine Maint',
                'usage_plan_date' => '2026-04-30',
                'catatan' => 'Regu Fabrikasi',
                'pics' => ['Dahlan', 'Faisal'],
            ],
            [
                'job_name' => 'Fabrikasi Dudukan Bearing Fan',
                'notification_number' => '174010108',
                'unit_work' => 'Unit of Power Plant Machine Maintenance',
                'seksi' => 'Section of Power Plant Machine Maintenance',
                'usage_plan_date' => '2026-05-01',
                'catatan' => 'Regu Fabrikasi',
                'pics' => ['Suardi'],
            ],
            [
                'job_name' => 'Refurbish Impeller Fan',
                'notification_number' => '174010109',
                'unit_work' => 'Unit of Machine Workshop',
                'seksi' => 'Section of Machine Workshop',
                'usage_plan_date' => '2026-05-02',
                'catatan' => 'Regu Bengkel (Refurbish)',
                'pics' => ['Rusman Majid', 'Mustari Mustafa'],
            ],
            [
                'job_name' => 'Refurbish Roller Table',
                'notification_number' => '174010110',
                'unit_work' => 'Unit of Machine Maintenance 1',
                'seksi' => 'Section of Line 2/3 RKC Machine Maint',
                'usage_plan_date' => '2026-05-03',
                'catatan' => 'Regu Bengkel (Refurbish)',
                'pics' => ['Ali asdar'],
            ],
            [
                'job_name' => 'Refurbish Guide Vane',
                'notification_number' => '174010111',
                'unit_work' => 'Unit of Clinker Production',
                'seksi' => 'Section of Line 4 RKC Operation',
                'usage_plan_date' => '2026-05-04',
                'catatan' => 'Regu Bengkel (Refurbish)',
                'pics' => ['Haerullah', 'Rusmanto. K'],
            ],
            [
                'job_name' => 'Refurbish Bucket Elevator',
                'notification_number' => '174010112',
                'unit_work' => 'Unit of Machine Maintenance 1',
                'seksi' => 'Section of Crusher Machine & Conveyor Maint',
                'usage_plan_date' => '2026-05-05',
                'catatan' => 'Regu Bengkel (Refurbish)',
                'pics' => ['Jumardi'],
            ],
            [
                'job_name' => 'Fabrikasi Nozzle Pipe',
                'notification_number' => '174010113',
                'unit_work' => 'Unit of Power Plant Elins Maintenance',
                'seksi' => 'Section of Power Plant Electrical Maintenance',
                'usage_plan_date' => '2026-05-06',
                'catatan' => 'Regu Fabrikasi',
                'pics' => ['Yakobus. P', 'Sudirman'],
            ],
            [
                'job_name' => 'Fabrikasi Housing Bearing',
                'notification_number' => '174010114',
                'unit_work' => 'Unit of Plant & Port Product Discharge Operation',
                'seksi' => 'Section of Plant Site Packer & Bulk Opr',
                'usage_plan_date' => '2026-05-07',
                'catatan' => 'Regu Fabrikasi',
                'pics' => ['Juniardi', 'Makmur'],
            ],
            [
                'job_name' => 'Refurbish Screw Feeder',
                'notification_number' => '174010115',
                'unit_work' => 'Unit of Raw Material Management',
                'seksi' => 'Section of Limestone Crusher Operation',
                'usage_plan_date' => '2026-05-08',
                'catatan' => 'Regu Bengkel (Refurbish)',
                'pics' => ['Akbar', 'Muh. Yunus. T'],
            ],
        ];

        foreach ($tasks as $task) {
            $names = array_values(array_filter(array_map('trim', $task['pics'])));

            BengkelTask::query()->updateOrCreate(
                ['notification_number' => $task['notification_number']],
                [
                    'job_name' => $task['job_name'],
                    'unit_work' => $task['unit_work'],
                    'seksi' => $task['seksi'],
                    'usage_plan_date' => $task['usage_plan_date'],
                    'catatan' => $task['catatan'],
                    'person_in_charge' => $names,
                    'person_in_charge_profiles' => $profilesFor($names),
                ]
            );
        }
    }
}
