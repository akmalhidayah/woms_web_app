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
        $structure = [
            'Clinker & Cement Production' => [
                'Derivative Product & Supporting' => [
                    'Derivative Prod. & Operation 2/3',
                    'Product Contract Compliance & SLA',
                ],
                'Clinker Production' => [
                    'Line 4 RKC Operation',
                    'Kiln Production Coach',
                ],
                'Cement Production' => [
                    'Line 4 Finish Mill Operation',
                    'Line 2/3 FM Operation',
                    'Cement Production Coach',
                    'Line 5 FM Operation',
                ],
            ],
            'Maintenance' => [
                'Reliability Maintenance' => [
                    'PGO',
                ],
                'Elins Maintenance 1' => [
                    'Line 2/3 RKC Elins Maint',
                    'Packer Plant Elins Maint',
                    'Crusher Elins Maintenance',
                    'Line 2/3 FM Elins Maintenance',
                ],
                'Elins Maintenance 2' => [
                    'EP/DC Maintenance',
                    'Line 4/5 RKC Instrument Maint',
                    'Line 4/5 RKC Electrical Maint',
                    'Line 4/5 FM Elins Maint',
                ],
                'Machine Maintenance 1' => [
                    'Line 2/3 FM Machine Maint',
                    'Line 2/3 RKC Machine Maint',
                    'Crusher Machine & Conveyor Maint',
                    'Packer Machine Maintenance',
                ],
                'Port Product Discharge Maintenance' => [
                    'Port Facility Elins Maintenance',
                ],
            ],
            'Project Management & Maintenance Support' => [
                'Engineering' => [
                    'Elins Design Engineering',
                    'Civil Design Engineering',
                    'Process Design Engineering',
                ],
                'Workshop' => [
                    'Elins Workshop',
                    'Machine Workshop',
                ],
                'Project Management' => [
                    'Project Execution (Construction)',
                ],
                'CAPEX Management' => [],
                'Maintenance Planning & Evaluation' => [],
            ],
            'Production Planning & Control' => [
                'Quality Control' => [
                    'QC 4/5',
                    'QC 2/3',
                    'Quality Development & Evaluation',
                ],
                'Production Plan Eval & Environmental' => [
                    'Production Planning',
                    'Raw Material & Cement Mill Eval',
                    'RKC Evaluation',
                    'Environmental Monitoring',
                    'PROPER & CDM',
                ],
                'Production Support' => [
                    'Heavy Equipment & Coal Transport',
                    'Plant Hygiene',
                    'Utility',
                ],
                'AFR & Energy' => [
                    'Coal Mixing',
                    'AFR & 3rd Material',
                ],
                'OHS' => [
                    'Plant OHS',
                    'BKS OHS',
                ],
            ],
            'Infrastructure' => [
                'Packing Plant 1' => [
                    'Makassar Packing Plant',
                    'Samarinda Packing Plant',
                    'Balikpapan Packing Plant',
                ],
                'Packing Plant 2' => [
                    'North Maluku Packing Plant',
                    'Ambon Packing Plant',
                    'Bitung Packing Plant',
                    'Kendari Packing Plant',
                    'Sorong Packing Plant',
                    'Mamuju Packing Plant',
                ],
                'SCM Infra Port Management' => [
                    'SCM Infra Port Management',
                ],
                'Plant & Port Product Discharge Operation' => [
                    'Port Operation Packer & Curah',
                    'Port Opr Silo, Aux, T & Silo M',
                    'Plant Site Packer & Bulk Opr',
                ],
                'Interplant Logistic' => [
                    'Plan Eval & Product Distribution',
                    'Sea Interplant',
                    'Land Interplant & DEPO Mgmt',
                ],
            ],
            'Mining & Power Plant' => [
                'Mining Operation' => [
                    'Limestone Mining',
                ],
                'Raw Material Management' => [
                    'Clay Crusher Operation',
                    'Limestone Crusher Operation',
                ],
                'Power Plant Operation' => [
                    'Power Plant Operation',
                    'Continuous System Unloading',
                    'PP Performance & Evaluation',
                    'Water Treatment & CUS Operation',
                ],
                'Power Plant Machine Maintenance' => [
                    'Power Plant Machine Maintenance',
                    'CUS Maintenance',
                ],
                'Power Distribution' => [
                    'Electricity Load Control',
                    'Electrical Network Maintenance',
                ],
                'Power Plant Elins Maintenance' => [
                    'Power Plant Instrument Maintenance',
                    'Power Plant Electrical Maintenance',
                ],
            ],
        ];

        $approvalUsers = [
            ['id' => 6, 'name' => 'Ari N.K. Tri Mahesthi', 'jabatan' => 'General Manager', 'dept' => 'Clinker & Cement Production', 'unit_kerja' => null, 'seksi' => null],
            ['id' => 7, 'name' => 'Manat L. Silitonga', 'jabatan' => 'General Manager', 'dept' => 'Maintenance', 'unit_kerja' => null, 'seksi' => null],
            ['id' => 8, 'name' => 'Syafardino', 'jabatan' => 'General Manager', 'dept' => 'Project Management & Maintenance Support', 'unit_kerja' => null, 'seksi' => null],
            ['id' => 9, 'name' => 'Yosi Pradana', 'jabatan' => 'General Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => null, 'seksi' => null],
            ['id' => 10, 'name' => 'Andi Rachman', 'jabatan' => 'General Manager', 'dept' => 'Infrastructure', 'unit_kerja' => null, 'seksi' => null],
            ['id' => 11, 'name' => 'Adi Fatkhurrohman', 'jabatan' => 'General Manager', 'dept' => 'Mining & Power Plant', 'unit_kerja' => null, 'seksi' => null],

            ['id' => 12, 'name' => 'Muh. Musafir', 'jabatan' => 'Senior Manager', 'dept' => 'Clinker & Cement Production', 'unit_kerja' => 'Derivative Product & Supporting', 'seksi' => null],
            ['id' => 13, 'name' => 'Albar Budiman', 'jabatan' => 'Senior Manager', 'dept' => 'Clinker & Cement Production', 'unit_kerja' => 'Clinker Production', 'seksi' => null],
            ['id' => 14, 'name' => 'Dwi Kurniawan', 'jabatan' => 'Senior Manager', 'dept' => 'Clinker & Cement Production', 'unit_kerja' => 'Cement Production', 'seksi' => null],
            ['id' => 15, 'name' => 'Maryono', 'jabatan' => 'Senior Manager', 'dept' => 'Maintenance', 'unit_kerja' => 'Reliability Maintenance', 'seksi' => null],
            ['id' => 16, 'name' => 'Andi Hilman', 'jabatan' => 'Senior Manager', 'dept' => 'Maintenance', 'unit_kerja' => 'Elins Maintenance 1', 'seksi' => null],
            ['id' => 17, 'name' => 'Ardiansyah', 'jabatan' => 'Senior Manager', 'dept' => 'Maintenance', 'unit_kerja' => 'Elins Maintenance 2', 'seksi' => null],
            ['id' => 18, 'name' => 'Ihrar Nuzul Azis', 'jabatan' => 'Senior Manager', 'dept' => 'Maintenance', 'unit_kerja' => 'Machine Maintenance 1', 'seksi' => null],
            ['id' => 19, 'name' => 'Suryadani', 'jabatan' => 'Senior Manager', 'dept' => 'Maintenance', 'unit_kerja' => 'Port Product Discharge Maintenance', 'seksi' => null],
            ['id' => 20, 'name' => 'Alamsyah', 'jabatan' => 'Senior Manager', 'dept' => 'Project Management & Maintenance Support', 'unit_kerja' => 'Engineering', 'seksi' => null],
            ['id' => 21, 'name' => 'Nur Asmal Mustafa', 'jabatan' => 'Senior Manager', 'dept' => 'Project Management & Maintenance Support', 'unit_kerja' => 'Workshop', 'seksi' => null],
            ['id' => 22, 'name' => 'Yatman Setiawan', 'jabatan' => 'Senior Manager', 'dept' => 'Project Management & Maintenance Support', 'unit_kerja' => 'Project Management', 'seksi' => null],
            ['id' => 23, 'name' => 'Muh. Asis Asri', 'jabatan' => 'Senior Manager', 'dept' => 'Project Management & Maintenance Support', 'unit_kerja' => 'CAPEX Management', 'seksi' => null],
            ['id' => 24, 'name' => 'Ifnul Mubarak', 'jabatan' => 'Senior Manager', 'dept' => 'Project Management & Maintenance Support', 'unit_kerja' => 'Maintenance Planning & Evaluation', 'seksi' => null],
            ['id' => 25, 'name' => 'Suryadi Pasambangi', 'jabatan' => 'Senior Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => 'Quality Control', 'seksi' => null],
            ['id' => 26, 'name' => 'Jasmiati', 'jabatan' => 'Senior Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => 'Production Plan Eval & Environmental', 'seksi' => null],
            ['id' => 27, 'name' => 'Irsan ST', 'jabatan' => 'Senior Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => 'Production Support', 'seksi' => null],
            ['id' => 28, 'name' => 'Stevanus Bodro Wibowo', 'jabatan' => 'Senior Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => 'AFR & Energy', 'seksi' => null],
            ['id' => 29, 'name' => 'M. Alianto M', 'jabatan' => 'Senior Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => 'OHS', 'seksi' => null],
            ['id' => 30, 'name' => 'Wellem Ariance', 'jabatan' => 'Senior Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'Packing Plant 1', 'seksi' => null],
            ['id' => 31, 'name' => 'Ambo Masse', 'jabatan' => 'Senior Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'Packing Plant 2', 'seksi' => null],
            ['id' => 32, 'name' => 'Capt. Guntur Eko Prasetyo', 'jabatan' => 'Senior Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'SCM Infra Port Management', 'seksi' => null],
            ['id' => 33, 'name' => 'Simon Salea', 'jabatan' => 'Senior Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'Plant & Port Product Discharge Operation', 'seksi' => null],
            ['id' => 34, 'name' => 'Hakmal Candra', 'jabatan' => 'Senior Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'Interplant Logistic', 'seksi' => null],
            ['id' => 35, 'name' => 'Imran', 'jabatan' => 'Senior Manager', 'dept' => 'Mining & Power Plant', 'unit_kerja' => 'Mining Operation', 'seksi' => null],
            ['id' => 36, 'name' => 'Parlindungan Pardosi', 'jabatan' => 'Senior Manager', 'dept' => 'Mining & Power Plant', 'unit_kerja' => 'Raw Material Management', 'seksi' => null],
            ['id' => 37, 'name' => 'Budi Wiyono', 'jabatan' => 'Senior Manager', 'dept' => 'Mining & Power Plant', 'unit_kerja' => 'Power Plant Operation', 'seksi' => null],
            ['id' => 38, 'name' => 'Mudassir Syam', 'jabatan' => 'Senior Manager', 'dept' => 'Mining & Power Plant', 'unit_kerja' => 'Power Plant Machine Maintenance', 'seksi' => null],
            ['id' => 39, 'name' => 'Muhammad Rusdianto HN', 'jabatan' => 'Senior Manager', 'dept' => 'Mining & Power Plant', 'unit_kerja' => 'Power Distribution', 'seksi' => null],
            ['id' => 40, 'name' => 'Abd. Wahid', 'jabatan' => 'Senior Manager', 'dept' => 'Mining & Power Plant', 'unit_kerja' => 'Power Plant Elins Maintenance', 'seksi' => null],

            ['id' => 41, 'name' => 'Nasaruddin', 'jabatan' => 'Manager', 'dept' => 'Clinker & Cement Production', 'unit_kerja' => 'Derivative Product & Supporting', 'seksi' => 'Derivative Prod. & Operation 2/3'],
            ['id' => 42, 'name' => 'Wibowo', 'jabatan' => 'Manager', 'dept' => 'Clinker & Cement Production', 'unit_kerja' => 'Derivative Product & Supporting', 'seksi' => 'Product Contract Compliance & SLA'],
            ['id' => 43, 'name' => 'Wahyu A.R.', 'jabatan' => 'Manager', 'dept' => 'Clinker & Cement Production', 'unit_kerja' => 'Clinker Production', 'seksi' => 'Line 4 RKC Operation'],
            ['id' => 44, 'name' => 'Andika Sariy Tandirura', 'jabatan' => 'Manager', 'dept' => 'Clinker & Cement Production', 'unit_kerja' => 'Clinker Production', 'seksi' => 'Kiln Production Coach'],
            ['id' => 45, 'name' => 'Muhammad Fausi', 'jabatan' => 'Manager', 'dept' => 'Clinker & Cement Production', 'unit_kerja' => 'Cement Production', 'seksi' => 'Line 4 Finish Mill Operation'],
            ['id' => 46, 'name' => 'Antonius F.H. Sukma', 'jabatan' => 'Manager', 'dept' => 'Clinker & Cement Production', 'unit_kerja' => 'Cement Production', 'seksi' => 'Line 2/3 FM Operation'],
            ['id' => 47, 'name' => 'Safruddin Haeruddin', 'jabatan' => 'Manager', 'dept' => 'Clinker & Cement Production', 'unit_kerja' => 'Cement Production', 'seksi' => 'Cement Production Coach'],
            ['id' => 48, 'name' => 'Ilyasusanto', 'jabatan' => 'Manager', 'dept' => 'Clinker & Cement Production', 'unit_kerja' => 'Cement Production', 'seksi' => 'Line 5 FM Operation'],
            ['id' => 49, 'name' => 'Al Azhar', 'jabatan' => 'Manager', 'dept' => 'Maintenance', 'unit_kerja' => 'Reliability Maintenance', 'seksi' => 'PGO'],
            ['id' => 50, 'name' => 'Muh. Basri', 'jabatan' => 'Manager', 'dept' => 'Maintenance', 'unit_kerja' => 'Elins Maintenance 1', 'seksi' => 'Line 2/3 RKC Elins Maint'],
            ['id' => 51, 'name' => 'Irwan Saparuddin', 'jabatan' => 'Manager', 'dept' => 'Maintenance', 'unit_kerja' => 'Elins Maintenance 1', 'seksi' => 'Packer Plant Elins Maint'],
            ['id' => 52, 'name' => 'Imam Suyuti', 'jabatan' => 'Manager', 'dept' => 'Maintenance', 'unit_kerja' => 'Elins Maintenance 1', 'seksi' => 'Crusher Elins Maintenance'],
            ['id' => 53, 'name' => 'Mohammad Andi Yustian', 'jabatan' => 'Manager', 'dept' => 'Maintenance', 'unit_kerja' => 'Elins Maintenance 1', 'seksi' => 'Line 2/3 FM Elins Maintenance'],
            ['id' => 54, 'name' => 'Arif Budiman', 'jabatan' => 'Manager', 'dept' => 'Maintenance', 'unit_kerja' => 'Elins Maintenance 2', 'seksi' => 'EP/DC Maintenance'],
            ['id' => 55, 'name' => 'Muhammad Ageng Anom', 'jabatan' => 'Manager', 'dept' => 'Maintenance', 'unit_kerja' => 'Elins Maintenance 2', 'seksi' => 'Line 4/5 RKC Instrument Maint'],
            ['id' => 56, 'name' => 'H. Alimuddin', 'jabatan' => 'Manager', 'dept' => 'Maintenance', 'unit_kerja' => 'Elins Maintenance 2', 'seksi' => 'Line 4/5 RKC Electrical Maint'],
            ['id' => 57, 'name' => 'Putra Adhi Sumaryanto', 'jabatan' => 'Manager', 'dept' => 'Maintenance', 'unit_kerja' => 'Elins Maintenance 2', 'seksi' => 'Line 4/5 FM Elins Maint'],
            ['id' => 58, 'name' => 'Ezra', 'jabatan' => 'Manager', 'dept' => 'Maintenance', 'unit_kerja' => 'Machine Maintenance 1', 'seksi' => 'Line 2/3 FM Machine Maint'],
            ['id' => 59, 'name' => 'H. Syahruddin', 'jabatan' => 'Manager', 'dept' => 'Maintenance', 'unit_kerja' => 'Machine Maintenance 1', 'seksi' => 'Line 2/3 RKC Machine Maint'],
            ['id' => 60, 'name' => 'Kaharuddin', 'jabatan' => 'Manager', 'dept' => 'Maintenance', 'unit_kerja' => 'Machine Maintenance 1', 'seksi' => 'Crusher Machine & Conveyor Maint'],
            ['id' => 61, 'name' => 'Abd. Salam', 'jabatan' => 'Manager', 'dept' => 'Maintenance', 'unit_kerja' => 'Machine Maintenance 1', 'seksi' => 'Packer Machine Maintenance'],
            ['id' => 62, 'name' => 'MZ Sukma Hastika', 'jabatan' => 'Manager', 'dept' => 'Maintenance', 'unit_kerja' => 'Port Product Discharge Maintenance', 'seksi' => 'Port Facility Elins Maintenance'],
            ['id' => 63, 'name' => 'Asriyanto Nasir', 'jabatan' => 'Manager', 'dept' => 'Project Management & Maintenance Support', 'unit_kerja' => 'Engineering', 'seksi' => 'Elins Design Engineering'],
            ['id' => 64, 'name' => 'Nani Sri Lestari', 'jabatan' => 'Manager', 'dept' => 'Project Management & Maintenance Support', 'unit_kerja' => 'Engineering', 'seksi' => 'Civil Design Engineering'],
            ['id' => 65, 'name' => 'Cendhika Larassayom Esa', 'jabatan' => 'Manager', 'dept' => 'Project Management & Maintenance Support', 'unit_kerja' => 'Engineering', 'seksi' => 'Process Design Engineering'],
            ['id' => 66, 'name' => 'Ahmad', 'jabatan' => 'Manager', 'dept' => 'Project Management & Maintenance Support', 'unit_kerja' => 'Workshop', 'seksi' => 'Elins Workshop'],
            ['id' => 67, 'name' => 'Syaharuddin Ngewa', 'jabatan' => 'Manager', 'dept' => 'Project Management & Maintenance Support', 'unit_kerja' => 'Workshop', 'seksi' => 'Machine Workshop'],
            ['id' => 68, 'name' => 'Surahman', 'jabatan' => 'Manager', 'dept' => 'Project Management & Maintenance Support', 'unit_kerja' => 'Project Management', 'seksi' => 'Project Execution (Construction)'],
            ['id' => 69, 'name' => 'M. Rizal M.', 'jabatan' => 'Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => 'Quality Control', 'seksi' => 'QC 4/5'],
            ['id' => 70, 'name' => 'Resti Setianingrum', 'jabatan' => 'Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => 'Quality Control', 'seksi' => 'QC 2/3'],
            ['id' => 71, 'name' => 'Agus Firmanto', 'jabatan' => 'Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => 'Quality Control', 'seksi' => 'Quality Development & Evaluation'],
            ['id' => 72, 'name' => 'Lukas Tandi', 'jabatan' => 'Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => 'Production Plan Eval & Environmental', 'seksi' => 'Production Planning'],
            ['id' => 73, 'name' => 'Ahmad Zaky Imani', 'jabatan' => 'Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => 'Production Plan Eval & Environmental', 'seksi' => 'Raw Material & Cement Mill Eval'],
            ['id' => 74, 'name' => 'Alfian Jais', 'jabatan' => 'Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => 'Production Plan Eval & Environmental', 'seksi' => 'RKC Evaluation'],
            ['id' => 75, 'name' => 'M. Yasin', 'jabatan' => 'Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => 'Production Plan Eval & Environmental', 'seksi' => 'Environmental Monitoring'],
            ['id' => 76, 'name' => 'Andi Mayundari', 'jabatan' => 'Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => 'Production Plan Eval & Environmental', 'seksi' => 'PROPER & CDM'],
            ['id' => 77, 'name' => 'Ahmad Zakki Mubarok', 'jabatan' => 'Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => 'Production Support', 'seksi' => 'Heavy Equipment & Coal Transport'],
            ['id' => 78, 'name' => 'Faizal Amir Razak', 'jabatan' => 'Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => 'Production Support', 'seksi' => 'Plant Hygiene'],
            ['id' => 79, 'name' => 'Angga Adhitya', 'jabatan' => 'Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => 'Production Support', 'seksi' => 'Utility'],
            ['id' => 80, 'name' => 'M. Sahrir', 'jabatan' => 'Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => 'AFR & Energy', 'seksi' => 'Coal Mixing'],
            ['id' => 81, 'name' => 'Syamsupriadi', 'jabatan' => 'Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => 'AFR & Energy', 'seksi' => 'AFR & 3rd Material'],
            ['id' => 82, 'name' => 'Sjarifuddin Said', 'jabatan' => 'Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => 'OHS', 'seksi' => 'Plant OHS'],
            ['id' => 83, 'name' => 'Azis', 'jabatan' => 'Manager', 'dept' => 'Production Planning & Control', 'unit_kerja' => 'OHS', 'seksi' => 'BKS OHS'],
            ['id' => 84, 'name' => 'Mathius Rota', 'jabatan' => 'Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'Packing Plant 1', 'seksi' => 'Makassar Packing Plant'],
            ['id' => 85, 'name' => 'Isak', 'jabatan' => 'Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'Packing Plant 1', 'seksi' => 'Samarinda Packing Plant'],
            ['id' => 86, 'name' => 'Rahmat S', 'jabatan' => 'Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'Packing Plant 1', 'seksi' => 'Balikpapan Packing Plant'],
            ['id' => 87, 'name' => 'Harianto Marzuki', 'jabatan' => 'Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'Packing Plant 2', 'seksi' => 'North Maluku Packing Plant'],
            ['id' => 88, 'name' => 'Helton Yhoni', 'jabatan' => 'Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'Packing Plant 2', 'seksi' => 'Ambon Packing Plant'],
            ['id' => 89, 'name' => 'Achmad Firmansjah', 'jabatan' => 'Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'Packing Plant 2', 'seksi' => 'Bitung Packing Plant'],
            ['id' => 90, 'name' => 'Bungin R', 'jabatan' => 'Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'Packing Plant 2', 'seksi' => 'Kendari Packing Plant'],
            ['id' => 91, 'name' => 'Kardianusti T. Bua', 'jabatan' => 'Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'Packing Plant 2', 'seksi' => 'Sorong Packing Plant'],
            ['id' => 92, 'name' => 'Jamaluddin', 'jabatan' => 'Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'Packing Plant 2', 'seksi' => 'Mamuju Packing Plant'],
            ['id' => 93, 'name' => 'Zulfadli', 'jabatan' => 'Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'SCM Infra Port Management', 'seksi' => 'SCM Infra Port Management'],
            ['id' => 94, 'name' => 'Wirawan Yusuf', 'jabatan' => 'Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'Plant & Port Product Discharge Operation', 'seksi' => 'Port Operation Packer & Curah'],
            ['id' => 95, 'name' => 'Sumardi', 'jabatan' => 'Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'Plant & Port Product Discharge Operation', 'seksi' => 'Port Opr Silo, Aux, T & Silo M'],
            ['id' => 96, 'name' => 'Margiantonius', 'jabatan' => 'Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'Plant & Port Product Discharge Operation', 'seksi' => 'Plant Site Packer & Bulk Opr'],
            ['id' => 97, 'name' => 'Rabenka Palesa', 'jabatan' => 'Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'Interplant Logistic', 'seksi' => 'Plan Eval & Product Distribution'],
            ['id' => 98, 'name' => 'Mursalin Tawang', 'jabatan' => 'Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'Interplant Logistic', 'seksi' => 'Sea Interplant'],
            ['id' => 99, 'name' => 'Aszriadi', 'jabatan' => 'Manager', 'dept' => 'Infrastructure', 'unit_kerja' => 'Interplant Logistic', 'seksi' => 'Land Interplant & DEPO Mgmt'],
            ['id' => 100, 'name' => 'H. Ferry Wardana', 'jabatan' => 'Manager', 'dept' => 'Mining & Power Plant', 'unit_kerja' => 'Mining Operation', 'seksi' => 'Limestone Mining'],
            ['id' => 101, 'name' => 'Muhammad Zubair Baso', 'jabatan' => 'Manager', 'dept' => 'Mining & Power Plant', 'unit_kerja' => 'Raw Material Management', 'seksi' => 'Clay Crusher Operation'],
            ['id' => 102, 'name' => 'Syamsul Bahri', 'jabatan' => 'Manager', 'dept' => 'Mining & Power Plant', 'unit_kerja' => 'Raw Material Management', 'seksi' => 'Limestone Crusher Operation'],
            ['id' => 103, 'name' => 'Wijanarko', 'jabatan' => 'Manager', 'dept' => 'Mining & Power Plant', 'unit_kerja' => 'Power Plant Operation', 'seksi' => 'Power Plant Operation'],
            ['id' => 104, 'name' => 'Syaharuddin', 'jabatan' => 'Manager', 'dept' => 'Mining & Power Plant', 'unit_kerja' => 'Power Plant Operation', 'seksi' => 'Continuous System Unloading'],
            ['id' => 105, 'name' => 'Roniansyah Malinggi', 'jabatan' => 'Manager', 'dept' => 'Mining & Power Plant', 'unit_kerja' => 'Power Plant Operation', 'seksi' => 'PP Performance & Evaluation'],
            ['id' => 106, 'name' => 'Andi Rahmat', 'jabatan' => 'Manager', 'dept' => 'Mining & Power Plant', 'unit_kerja' => 'Power Plant Operation', 'seksi' => 'Water Treatment & CUS Operation'],
            ['id' => 107, 'name' => 'Ruben Bondo', 'jabatan' => 'Manager', 'dept' => 'Mining & Power Plant', 'unit_kerja' => 'Power Plant Machine Maintenance', 'seksi' => 'Power Plant Machine Maintenance'],
            ['id' => 108, 'name' => 'Dasa Agustriawan', 'jabatan' => 'Manager', 'dept' => 'Mining & Power Plant', 'unit_kerja' => 'Power Plant Machine Maintenance', 'seksi' => 'CUS Maintenance'],
            ['id' => 109, 'name' => 'Lamasi', 'jabatan' => 'Manager', 'dept' => 'Mining & Power Plant', 'unit_kerja' => 'Power Distribution', 'seksi' => 'Electricity Load Control'],
            ['id' => 110, 'name' => 'Irfan', 'jabatan' => 'Manager', 'dept' => 'Mining & Power Plant', 'unit_kerja' => 'Power Distribution', 'seksi' => 'Electrical Network Maintenance'],
            ['id' => 111, 'name' => 'Rahman', 'jabatan' => 'Manager', 'dept' => 'Mining & Power Plant', 'unit_kerja' => 'Power Plant Elins Maintenance', 'seksi' => 'Power Plant Instrument Maintenance'],
            ['id' => 112, 'name' => 'Suriadi', 'jabatan' => 'Manager', 'dept' => 'Mining & Power Plant', 'unit_kerja' => 'Power Plant Elins Maintenance', 'seksi' => 'Power Plant Electrical Maintenance'],
        ];

        $generalManagers = [];
        $seniorManagers = [];
        $managers = [];

        foreach ($approvalUsers as $approvalUser) {
            $jabatan = $approvalUser['jabatan'];

            if ($jabatan === 'General Manager') {
                $generalManagers[$approvalUser['dept']] = $approvalUser['id'];
                continue;
            }

            if ($jabatan === 'Senior Manager') {
                $seniorManagers[$approvalUser['dept'].'|'.$approvalUser['unit_kerja']] = $approvalUser['id'];
                continue;
            }

            if ($jabatan === 'Manager') {
                $managers[$approvalUser['dept'].'|'.$approvalUser['unit_kerja'].'|'.$approvalUser['seksi']] = $approvalUser['id'];
            }
        }

        DB::transaction(function () use ($structure, $generalManagers, $seniorManagers, $managers) {
            foreach ($structure as $departmentName => $units) {
                $department = Department::query()->updateOrCreate(
                    ['name' => $departmentName],
                    ['general_manager_id' => $generalManagers[$departmentName] ?? null]
                );

                foreach ($units as $unitName => $sections) {
                    $unit = UnitWork::query()->updateOrCreate(
                        [
                            'department_id' => $department->id,
                            'name' => $unitName,
                        ],
                        [
                            'senior_manager_id' => $seniorManagers[$departmentName.'|'.$unitName] ?? null,
                        ]
                    );

                    foreach ($sections as $sectionName) {
                        $unit->sections()->updateOrCreate(
                            ['name' => $sectionName],
                            ['manager_id' => $managers[$departmentName.'|'.$unitName.'|'.$sectionName] ?? null]
                        );
                    }
                }
            }
        });
    }
}
