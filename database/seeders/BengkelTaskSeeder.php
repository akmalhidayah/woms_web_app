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
            ->get(['id', 'name', 'avatar_path', 'avatar_position_x', 'avatar_position_y'])
            ->keyBy(static fn (BengkelPic $pic): string => mb_strtolower(trim($pic->name)));

        $profilesFor = static function (array $assignments) use ($pics): array {
            $profiles = [];

            foreach ($assignments as $assignment) {
                $cleanName = trim((string) ($assignment['name'] ?? ''));

                if ($cleanName === '') {
                    continue;
                }

                /** @var \App\Models\BengkelPic|null $pic */
                $pic = $pics->get(mb_strtolower($cleanName));

                $profiles[] = [
                    'id' => $pic?->id,
                    'name' => $pic?->name ?? $cleanName,
                    'avatar_path' => $pic?->avatar_path,
                    'avatar_position_x' => $pic?->avatar_position_x ?? 50,
                    'avatar_position_y' => $pic?->avatar_position_y ?? 50,
                    'work_descriptions' => collect($assignment['descriptions'] ?? [])
                        ->map(fn ($description): string => trim((string) $description))
                        ->filter()
                        ->values()
                        ->all(),
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
                'assignments' => [
                    ['name' => 'Herman. R', 'descriptions' => ['Marking plat hopper', 'Fit up rangka penguat']],
                    ['name' => 'Herman. S', 'descriptions' => ['Pengelasan sambungan hopper', 'Grinding area las']],
                ],
            ],
            [
                'job_name' => 'Fabrikasi Cover Conveyor',
                'notification_number' => '174010102',
                'unit_work' => 'Unit of Machine Maintenance 1',
                'seksi' => 'Section of Crusher Machine & Conveyor Maint',
                'usage_plan_date' => '2026-04-25',
                'catatan' => 'Regu Fabrikasi',
                'assignments' => [
                    ['name' => 'Sudirman. MJ', 'descriptions' => ['Potong plat cover', 'Drilling lubang baut']],
                    ['name' => 'Aswar', 'descriptions' => ['Bending cover conveyor', 'Finishing permukaan']],
                ],
            ],
            [
                'job_name' => 'Fabrikasi Chute Raw Mill',
                'notification_number' => '174010103',
                'unit_work' => 'Unit of Cement Production',
                'seksi' => 'Section of Line 4 Finish Mill Operation',
                'usage_plan_date' => '2026-04-26',
                'catatan' => 'Regu Fabrikasi',
                'assignments' => [
                    ['name' => 'Ikhlas', 'descriptions' => ['Setting pola chute', 'Fit up wear plate']],
                    ['name' => 'Adil Makmur', 'descriptions' => ['Pengelasan wear plate', 'Pemeriksaan dimensi akhir']],
                ],
            ],
            [
                'job_name' => 'Fabrikasi Dudukan Motor',
                'notification_number' => '174010104',
                'unit_work' => 'Unit of Reliability Maintenance',
                'seksi' => 'Section of PGO',
                'usage_plan_date' => '2026-04-27',
                'catatan' => 'Regu Fabrikasi',
                'assignments' => [
                    ['name' => 'Arsyad', 'descriptions' => ['Pembuatan base plate', 'Las support motor']],
                ],
            ],
            [
                'job_name' => 'Fabrikasi Spiral Screw',
                'notification_number' => '174010105',
                'unit_work' => 'Unit of Elins Maintenance 2',
                'seksi' => 'Section of EP/DC Maintenance',
                'usage_plan_date' => '2026-04-28',
                'catatan' => 'Regu Fabrikasi',
                'assignments' => [
                    ['name' => 'Tahriruddin', 'descriptions' => ['Roll spiral blade', 'Fit up blade ke shaft']],
                    ['name' => 'Firman Ferdinan', 'descriptions' => ['Pengelasan spiral', 'Balancing visual']],
                ],
            ],
            [
                'job_name' => 'Fabrikasi Bracket Sensor',
                'notification_number' => '174010106',
                'unit_work' => 'Unit of Elins Workshop',
                'seksi' => 'Section of Elins Workshop',
                'usage_plan_date' => '2026-04-29',
                'catatan' => 'Regu Fabrikasi',
                'assignments' => [
                    ['name' => 'Herman. R', 'descriptions' => ['Potong siku bracket', 'Drilling slot sensor']],
                ],
            ],
            [
                'job_name' => 'Fabrikasi Shaft Support',
                'notification_number' => '174010107',
                'unit_work' => 'Unit of Machine Maintenance 1',
                'seksi' => 'Section of Line 2/3 FM Machine Maint',
                'usage_plan_date' => '2026-04-30',
                'catatan' => 'Regu Fabrikasi',
                'assignments' => [
                    ['name' => 'Dahlan', 'descriptions' => ['Bubut spacer support', 'Setting center shaft']],
                    ['name' => 'Faisal', 'descriptions' => ['Las rangka support', 'Finishing dan pengecatan']],
                ],
            ],
            [
                'job_name' => 'Fabrikasi Dudukan Bearing Fan',
                'notification_number' => '174010108',
                'unit_work' => 'Unit of Power Plant Machine Maintenance',
                'seksi' => 'Section of Power Plant Machine Maintenance',
                'usage_plan_date' => '2026-05-01',
                'catatan' => 'Regu Fabrikasi',
                'assignments' => [
                    ['name' => 'Suardi', 'descriptions' => ['Pembuatan dudukan bearing', 'Cek kerataan base']],
                ],
            ],
            [
                'job_name' => 'Refurbish Impeller Fan',
                'notification_number' => '174010109',
                'unit_work' => 'Unit of Machine Workshop',
                'seksi' => 'Section of Machine Workshop',
                'usage_plan_date' => '2026-05-02',
                'catatan' => 'Regu Bengkel (Refurbish)',
                'assignments' => [
                    ['name' => 'Rusman Majid', 'descriptions' => ['Bongkar blade aus', 'Pembersihan area hub']],
                    ['name' => 'Mustari Mustafa', 'descriptions' => ['Build up blade impeller', 'Grinding profile blade']],
                ],
            ],
            [
                'job_name' => 'Refurbish Roller Table',
                'notification_number' => '174010110',
                'unit_work' => 'Unit of Machine Maintenance 1',
                'seksi' => 'Section of Line 2/3 RKC Machine Maint',
                'usage_plan_date' => '2026-05-03',
                'catatan' => 'Regu Bengkel (Refurbish)',
                'assignments' => [
                    ['name' => 'Ali asdar', 'descriptions' => ['Repair permukaan roller', 'Cek putaran bearing']],
                ],
            ],
            [
                'job_name' => 'Refurbish Guide Vane',
                'notification_number' => '174010111',
                'unit_work' => 'Unit of Clinker Production',
                'seksi' => 'Section of Line 4 RKC Operation',
                'usage_plan_date' => '2026-05-04',
                'catatan' => 'Regu Bengkel (Refurbish)',
                'assignments' => [
                    ['name' => 'Haerullah', 'descriptions' => ['Cutting bagian vane rusak', 'Fit up plate pengganti']],
                    ['name' => 'Rusmanto. K', 'descriptions' => ['Pengelasan guide vane', 'Finishing tepi vane']],
                ],
            ],
            [
                'job_name' => 'Refurbish Bucket Elevator',
                'notification_number' => '174010112',
                'unit_work' => 'Unit of Machine Maintenance 1',
                'seksi' => 'Section of Crusher Machine & Conveyor Maint',
                'usage_plan_date' => '2026-05-05',
                'catatan' => 'Regu Bengkel (Refurbish)',
                'assignments' => [
                    ['name' => 'Jumardi', 'descriptions' => ['Repair bucket retak', 'Pasang penguat bibir bucket']],
                ],
            ],
            [
                'job_name' => 'Fabrikasi Nozzle Pipe',
                'notification_number' => '174010113',
                'unit_work' => 'Unit of Power Plant Elins Maintenance',
                'seksi' => 'Section of Power Plant Electrical Maintenance',
                'usage_plan_date' => '2026-05-06',
                'catatan' => 'Regu Fabrikasi',
                'assignments' => [
                    ['name' => 'Yakobus. P', 'descriptions' => ['Potong pipa nozzle', 'Setting flange']],
                    ['name' => 'Sudirman', 'descriptions' => ['Pengelasan nozzle', 'Leak test visual']],
                ],
            ],
            [
                'job_name' => 'Fabrikasi Housing Bearing',
                'notification_number' => '174010114',
                'unit_work' => 'Unit of Plant & Port Product Discharge Operation',
                'seksi' => 'Section of Plant Site Packer & Bulk Opr',
                'usage_plan_date' => '2026-05-07',
                'catatan' => 'Regu Fabrikasi',
                'assignments' => [
                    ['name' => 'Juniardi', 'descriptions' => ['Machining housing bearing', 'Cek ukuran bore']],
                    ['name' => 'Makmur', 'descriptions' => ['Fabrikasi cover housing', 'Finishing sisi luar']],
                ],
            ],
            [
                'job_name' => 'Refurbish Screw Feeder',
                'notification_number' => '174010115',
                'unit_work' => 'Unit of Raw Material Management',
                'seksi' => 'Section of Limestone Crusher Operation',
                'usage_plan_date' => '2026-05-08',
                'catatan' => 'Regu Bengkel (Refurbish)',
                'assignments' => [
                    ['name' => 'Akbar', 'descriptions' => ['Ganti flight screw aus', 'Setting pitch screw']],
                    ['name' => 'Muh. Yunus. T', 'descriptions' => ['Las flight screw', 'Finishing ujung shaft']],
                ],
            ],
            [
                'job_name' => 'Fabrikasi Guard Pulley Conveyor',
                'notification_number' => '174010116',
                'unit_work' => 'Unit of Machine Maintenance 2',
                'seksi' => 'Section of Belt Conveyor Maintenance',
                'usage_plan_date' => '2026-05-09',
                'catatan' => 'Regu Fabrikasi',
                'assignments' => [
                    ['name' => 'Satria. P', 'descriptions' => ['Potong wiremesh guard', 'Buat rangka pengaman']],
                    ['name' => 'Wahyu Pratama', 'descriptions' => ['Las rangka guard', 'Pemasangan engsel inspeksi']],
                ],
            ],
            [
                'job_name' => 'Fabrikasi Platform Inspection',
                'notification_number' => '174010117',
                'unit_work' => 'Unit of Clinker Production',
                'seksi' => 'Section of Line 2/3 RKC Operation',
                'usage_plan_date' => '2026-05-10',
                'catatan' => 'Regu Fabrikasi',
                'assignments' => [
                    ['name' => 'Fadhil Pratama', 'descriptions' => ['Fabrikasi frame platform', 'Pasang checker plate']],
                    ['name' => 'Adil Makmur', 'descriptions' => ['Las handrail platform', 'Finishing joint las']],
                ],
            ],
            [
                'job_name' => 'Refurbish Gear Coupling',
                'notification_number' => '174010118',
                'unit_work' => 'Unit of Machine Workshop',
                'seksi' => 'Section of Machine Workshop',
                'usage_plan_date' => '2026-05-11',
                'catatan' => 'Regu Bengkel (Refurbish)',
                'assignments' => [
                    ['name' => 'Dahlan', 'descriptions' => ['Bongkar coupling', 'Cek keausan gear']],
                    ['name' => 'Faisal', 'descriptions' => ['Build up area aus', 'Machining ulang diameter']],
                ],
            ],
            [
                'job_name' => 'Fabrikasi Ducting Dust Collector',
                'notification_number' => '174010119',
                'unit_work' => 'Unit of EP/DC Maintenance',
                'seksi' => 'Section of EP/DC Maintenance',
                'usage_plan_date' => '2026-05-12',
                'catatan' => 'Regu Fabrikasi',
                'assignments' => [
                    ['name' => 'Aswar', 'descriptions' => ['Rolling plat ducting', 'Fit up elbow duct']],
                    ['name' => 'Ikhlas', 'descriptions' => ['Pengelasan sambungan duct', 'Pemasangan flange']],
                ],
            ],
            [
                'job_name' => 'Refurbish Jaw Crusher Liner',
                'notification_number' => '174010120',
                'unit_work' => 'Unit of Raw Material Management',
                'seksi' => 'Section of Limestone Crusher Operation',
                'usage_plan_date' => '2026-05-13',
                'catatan' => 'Regu Bengkel (Refurbish)',
                'assignments' => [
                    ['name' => 'Rusman Majid', 'descriptions' => ['Bersihkan liner lama', 'Cutting area retak']],
                    ['name' => 'Jumardi', 'descriptions' => ['Build up liner', 'Cek profil permukaan']],
                ],
            ],
            [
                'job_name' => 'Fabrikasi Support Cable Tray',
                'notification_number' => '174010121',
                'unit_work' => 'Unit of Elins Workshop',
                'seksi' => 'Section of Elins Workshop',
                'usage_plan_date' => '2026-05-14',
                'catatan' => 'Regu Fabrikasi',
                'assignments' => [
                    ['name' => 'Firman Ferdinan', 'descriptions' => ['Potong support tray', 'Drilling lubang mounting']],
                    ['name' => 'Tahriruddin', 'descriptions' => ['Las bracket support', 'Finishing galvanis dingin']],
                ],
            ],
            [
                'job_name' => 'Refurbish Rotary Feeder',
                'notification_number' => '174010122',
                'unit_work' => 'Unit of Cement Production',
                'seksi' => 'Section of Line 4 Finish Mill Operation',
                'usage_plan_date' => '2026-05-15',
                'catatan' => 'Regu Bengkel (Refurbish)',
                'assignments' => [
                    ['name' => 'Mustari Mustafa', 'descriptions' => ['Repair blade rotor', 'Cek clearance housing']],
                    ['name' => 'Haerullah', 'descriptions' => ['Pengelasan casing aus', 'Finishing permukaan casing']],
                ],
            ],
            [
                'job_name' => 'Fabrikasi Manhole Cover',
                'notification_number' => '174010123',
                'unit_work' => 'Unit of Plant & Port Product Discharge Operation',
                'seksi' => 'Section of Jetty Operation',
                'usage_plan_date' => '2026-05-16',
                'catatan' => 'Regu Fabrikasi',
                'assignments' => [
                    ['name' => 'Sudirman. MJ', 'descriptions' => ['Potong cover manhole', 'Buat frame pengunci']],
                    ['name' => 'Yakobus. P', 'descriptions' => ['Pasang handle cover', 'Finishing pengecatan']],
                ],
            ],
            [
                'job_name' => 'Refurbish Chain Conveyor',
                'notification_number' => '174010124',
                'unit_work' => 'Unit of Machine Maintenance 1',
                'seksi' => 'Section of Crusher Machine & Conveyor Maint',
                'usage_plan_date' => '2026-05-17',
                'catatan' => 'Regu Bengkel (Refurbish)',
                'assignments' => [
                    ['name' => 'Akbar', 'descriptions' => ['Repair flight chain', 'Cek pitch rantai']],
                    ['name' => 'Rusmanto. K', 'descriptions' => ['Las flight pengganti', 'Setting alignment chain']],
                ],
            ],
            [
                'job_name' => 'Fabrikasi Ladder Access',
                'notification_number' => '174010125',
                'unit_work' => 'Unit of Power Plant Machine Maintenance',
                'seksi' => 'Section of Power Plant Machine Maintenance',
                'usage_plan_date' => '2026-05-18',
                'catatan' => 'Regu Fabrikasi',
                'assignments' => [
                    ['name' => 'Herman. S', 'descriptions' => ['Fabrikasi anak tangga', 'Las rangka ladder']],
                    ['name' => 'Suardi', 'descriptions' => ['Pasang safety cage', 'Finishing cat safety']],
                ],
            ],
            [
                'job_name' => 'Refurbish Damper Plate',
                'notification_number' => '174010126',
                'unit_work' => 'Unit of EP/DC Maintenance',
                'seksi' => 'Section of EP/DC Maintenance',
                'usage_plan_date' => '2026-05-19',
                'catatan' => 'Regu Bengkel (Refurbish)',
                'assignments' => [
                    ['name' => 'Ali asdar', 'descriptions' => ['Repair shaft damper', 'Cek gerak buka tutup']],
                    ['name' => 'Makmur', 'descriptions' => ['Ganti plate damper', 'Pengelasan stopper']],
                ],
            ],
            [
                'job_name' => 'Fabrikasi Skirt Board Conveyor',
                'notification_number' => '174010127',
                'unit_work' => 'Unit of Machine Maintenance 2',
                'seksi' => 'Section of Belt Conveyor Maintenance',
                'usage_plan_date' => '2026-05-20',
                'catatan' => 'Regu Fabrikasi',
                'assignments' => [
                    ['name' => 'Wahyu Pratama', 'descriptions' => ['Potong skirt board', 'Drilling mounting rubber']],
                    ['name' => 'Satria. P', 'descriptions' => ['Fit up bracket skirt', 'Finishing sisi tajam']],
                ],
            ],
            [
                'job_name' => 'Refurbish Pump Base Frame',
                'notification_number' => '174010128',
                'unit_work' => 'Unit of Power Plant Machine Maintenance',
                'seksi' => 'Section of Power Plant Machine Maintenance',
                'usage_plan_date' => '2026-05-21',
                'catatan' => 'Regu Bengkel (Refurbish)',
                'assignments' => [
                    ['name' => 'Juniardi', 'descriptions' => ['Repair base frame korosi', 'Cek kerataan dudukan']],
                    ['name' => 'Dahlan', 'descriptions' => ['Machining slot anchor', 'Finishing permukaan base']],
                ],
            ],
            [
                'job_name' => 'Fabrikasi Bin Vent Support',
                'notification_number' => '174010129',
                'unit_work' => 'Unit of Cement Production',
                'seksi' => 'Section of Line 4 Finish Mill Operation',
                'usage_plan_date' => '2026-05-22',
                'catatan' => 'Regu Fabrikasi',
                'assignments' => [
                    ['name' => 'Arsyad', 'descriptions' => ['Fabrikasi support bin vent', 'Pasang stiffener']],
                    ['name' => 'Fadhil Pratama', 'descriptions' => ['Las frame support', 'Finishing pengecatan']],
                ],
            ],
            [
                'job_name' => 'Refurbish Apron Feeder Pan',
                'notification_number' => '174010130',
                'unit_work' => 'Unit of Raw Material Management',
                'seksi' => 'Section of Limestone Crusher Operation',
                'usage_plan_date' => '2026-05-23',
                'catatan' => 'Regu Bengkel (Refurbish)',
                'assignments' => [
                    ['name' => 'Muh. Yunus. T', 'descriptions' => ['Repair pan feeder retak', 'Build up sisi aus']],
                    ['name' => 'Rusman Majid', 'descriptions' => ['Grinding hasil repair', 'Cek dimensi pan']],
                ],
            ],
        ];

        foreach ($tasks as $task) {
            $profiles = $profilesFor($task['assignments']);
            $names = collect($profiles)->pluck('name')->filter()->values()->all();

            BengkelTask::query()->updateOrCreate(
                ['notification_number' => $task['notification_number']],
                [
                    'job_name' => $task['job_name'],
                    'unit_work' => $task['unit_work'],
                    'seksi' => $task['seksi'],
                    'usage_plan_date' => $task['usage_plan_date'],
                    'catatan' => $task['catatan'],
                    'person_in_charge' => $names,
                    'person_in_charge_profiles' => $profiles,
                ]
            );
        }
    }
}
