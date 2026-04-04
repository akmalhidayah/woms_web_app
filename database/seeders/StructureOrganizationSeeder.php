<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\UnitWork;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StructureOrganizationSeeder extends Seeder
{
    /**
     * Seed the structure organization data.
     */
    public function run(): void
    {
        $data = [
            'Clinker & Cement Production' => [
                [
                    'name' => 'Unit of Clinker Production 2',
                    'seksi' => [
                        'Section of Line 5 RKC Operation',
                        'Section of Line 4 RKC Operation',
                    ],
                ],
                [
                    'name' => 'Unit of Clinker Production 1',
                    'seksi' => [
                        'Section of Line 2/3 RKC Operation',
                        'Staff of Performance Monitoring & Eval.',
                    ],
                ],
                [
                    'name' => 'Unit of Cement Production',
                    'seksi' => [
                        'Section of Line 2/3 FM Operation',
                        'Section of Line 4 Finish Mill Operation',
                        'Section of Line 5 Finish Mill Operation',
                        'Section of Packer Operation',
                        'Section of Bulk Cement Operation',
                    ],
                ],
                [
                    'name' => 'Unit of Quality Assurance',
                    'seksi' => [
                        'Section of Material Quality Assurance',
                        'Section of Product Quality Assurance',
                        'Section of Cement App & Tech Services',
                    ],
                ],
            ],
            'Maintenance' => [
                [
                    'name' => 'Unit of Reliability Maintenance',
                    'seksi' => [
                        'Staff of Overhaul Management',
                        'Staff of Maintenance Inspection',
                        'Staff of Troubleshooting',
                        'Staff of PGO',
                        'Staff of Planning & Scheduling',
                    ],
                ],
                [
                    'name' => 'Unit of Machine Maintenance 2',
                    'seksi' => [
                        'Section of Line 4/5 RM Machine Maint',
                        'Section of Line 4/5 Kiln & CM Mach Maint',
                        'Section of Line 4/5 FM Machine Maint',
                    ],
                ],
                [
                    'name' => 'Unit of Elins Maintenance 1',
                    'seksi' => [
                        'Section of Packer Elins Maint',
                        'Section of Crusher Elins Maintenance',
                        'Section of Line 2/3 FM Elins Main',
                        'Section of Line 2/3 RKC Elins Maint.',
                    ],
                ],
                [
                    'name' => 'Unit of Elins Maintenance 2',
                    'seksi' => [
                        'Section of Line 4/5 FM Elins Maint',
                        'Section of EP/DC Maintenance',
                        'Section of Line 4/5 RKC Elctr. Maint.',
                        'Section of Line 4/5 RKC Instr. Maint.',
                    ],
                ],
                [
                    'name' => 'Unit of Machine Maintenance 1',
                    'seksi' => [
                        'Section of Crusher Machine & Conp Maint.',
                        'Section of Line 2/3 FM Machine Maint.',
                        'Section of Packer Machine Maintenance',
                        'Section of Line 2/3 RKC Machine Maint.',
                    ],
                ],
            ],
            'Mining & Power Plant' => [
                [
                    'name' => 'Unit of Mining',
                    'seksi' => [
                        'Section of Mine Safety Reclamation',
                        'Section of Limestone Mining',
                        'Section of Clay Mining',
                        'Section of Mine Planning & Monitoring',
                    ],
                ],
                [
                    'name' => 'Unit of Raw Material Management',
                    'seksi' => [],
                ],
                [
                    'name' => 'Unit of Power Plant Operation',
                    'seksi' => [
                        'Section of Power Plant Operation I',
                        'Section of Power Plant Operation',
                        'Section of Water & Coal Quality Control',
                        'Section of CUS',
                    ],
                ],
                [
                    'name' => 'Bureau of Power Plant II',
                    'seksi' => [],
                ],
                [
                    'name' => 'Bureau of Power Distribution And Network',
                    'seksi' => [],
                ],
                [
                    'name' => 'Unit of Power Plant Machine Maintenance',
                    'seksi' => [],
                ],
                [
                    'name' => 'Unit of Power Distribution',
                    'seksi' => [
                        'Section of Electricity Load Control',
                        'Section of Electrical Network Maint',
                        'Section of Net & Elec Plant Maint',
                    ],
                ],
                [
                    'name' => 'Staff of Power Plant Operation Planning & Control',
                    'seksi' => [
                        'Staff of Pwr Plant Opr Planning & Ctrlg',
                    ],
                ],
                [
                    'name' => 'Unit of Power Plant Elins Maintenance',
                    'seksi' => [
                        'Section of Power Plant Elctrical Maint.',
                        'Section of Power Plant Instrument Maint.',
                    ],
                ],
            ],
            'Production Planning & Control' => [
                [
                    'name' => 'Unit of Production Support',
                    'seksi' => [],
                ],
                [
                    'name' => 'Unit of Quality Control',
                    'seksi' => [],
                ],
                [
                    'name' => 'Unit of Production Plant Evaluation & Environmental',
                    'seksi' => [],
                ],
                [
                    'name' => 'Unit of OHS',
                    'seksi' => [],
                ],
                [
                    'name' => 'Unit of AFR & Energy',
                    'seksi' => [],
                ],
            ],
            'Project Management & Maintenance Support' => [
                [
                    'name' => 'Unit of Engineering',
                    'seksi' => [],
                ],
                [
                    'name' => 'Unit of Project Management',
                    'seksi' => [],
                ],
                [
                    'name' => 'Staff of TPM',
                    'seksi' => [],
                ],
                [
                    'name' => 'Unit of CAPEX Management',
                    'seksi' => [],
                ],
                [
                    'name' => 'Unit of Workshop & Design',
                    'seksi' => [],
                ],
                [
                    'name' => 'Unit of Maintenance Planning & Evaluation',
                    'seksi' => [],
                ],
            ],
            'Infrastructure' => [
                [
                    'name' => 'Unit of Packing Plant 2',
                    'seksi' => [],
                ],
                [
                    'name' => 'Unit of Plant & Port Product Discharge Opr',
                    'seksi' => [],
                ],
                [
                    'name' => 'Unit of SCM Infra Port Management',
                    'seksi' => [],
                ],
                [
                    'name' => 'Unit of Interplant Logistic',
                    'seksi' => [],
                ],
            ],
        ];

        DB::transaction(function () use ($data) {
            DB::table('unit_work_sections')->delete();
            DB::table('unit_works')->delete();
            DB::table('departments')->delete();

            foreach ($data as $departmentName => $units) {
                $department = Department::create([
                    'name' => $departmentName,
                    'general_manager_id' => null,
                ]);

                foreach ($units as $unitData) {
                    $unit = UnitWork::create([
                        'department_id' => $department->id,
                        'name' => $unitData['name'],
                        'senior_manager_id' => null,
                    ]);

                    foreach ($unitData['seksi'] as $sectionName) {
                        $unit->sections()->create([
                            'name' => $sectionName,
                            'manager_id' => null,
                        ]);
                    }
                }
            }
        });
    }
}
