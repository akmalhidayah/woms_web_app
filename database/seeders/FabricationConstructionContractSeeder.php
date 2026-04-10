<?php

namespace Database\Seeders;

use App\Models\FabricationConstructionContract;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FabricationConstructionContractSeeder extends Seeder
{
    /**
     * Seed the application's fabrication construction contract master data.
     */
    public function run(): void
    {
        $userId = User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_APPROVER, User::ROLE_PKM, User::ROLE_USER])
            ->value('id');

        $items = [
            $this->row(2026, 'A. JASA FABRIKASI', '1. JASA PEKERJAAN FABRIKASI DENGAN MATERIAL PLAT METAL (PLATE WORK)', null, 'Pekerjaan Fabrikasi menggunakan Plate Steel ASTM A-36/SS400', 'Kg', '7811.38'),
            $this->row(2026, 'A. JASA FABRIKASI', '1. JASA PEKERJAAN FABRIKASI DENGAN MATERIAL PLAT METAL (PLATE WORK)', null, 'Pekerjaan Fabrikasi menggunakan Wear Resistant Plate HRC 400', 'Kg', '9400.86'),
            $this->row(2026, 'A. JASA FABRIKASI', '1. JASA PEKERJAAN FABRIKASI DENGAN MATERIAL PLAT METAL (PLATE WORK)', null, 'Pekerjaan Fabrikasi menggunakan Wear Resistant Plate HRC 500', 'Kg', '9400.86'),
            $this->row(2026, 'A. JASA FABRIKASI', '1. JASA PEKERJAAN FABRIKASI DENGAN MATERIAL PLAT METAL (PLATE WORK)', null, 'Pekerjaan Fabrikasi menggunakan Wear Resistant Overlay Plate', 'Kg', '9400.86'),
            $this->row(2026, 'A. JASA FABRIKASI', '1. JASA PEKERJAAN FABRIKASI DENGAN MATERIAL PLAT METAL (PLATE WORK)', null, 'Pekerjaan Fabrikasi menggunakan Stainless Steel Plate ASTM 304', 'Kg', '28674.86'),
            $this->row(2026, 'A. JASA FABRIKASI', '1. JASA PEKERJAAN FABRIKASI DENGAN MATERIAL PLAT METAL (PLATE WORK)', null, 'Pekerjaan Fabrikasi menggunakan Stainless Steel Plate ASTM 310', 'Kg', '41788.22'),
            $this->row(2026, 'A. JASA FABRIKASI', '1. JASA PEKERJAAN FABRIKASI DENGAN MATERIAL PLAT METAL (PLATE WORK)', null, 'Pekerjaan Fabrikasi menggunakan Stainless Steel Plate ASTM 30815', 'Kg', '43706.90'),

            $this->row(2026, 'A. JASA FABRIKASI', '2. JASA PEKERJAAN PEMOTONGAN (CUTTING WORK)', null, 'Pekerjaan Pemotongan Plate, Profile Steel, Shaft dll', 'Kg', '3109.89'),

            $this->row(2026, 'A. JASA FABRIKASI', 'PEKERJAAN FABRIKASI DENGAN MATERIAL BAJA STRUKTUR (STEEL STRUCTURE WORK)', null, 'Pekerjaan Fabrikasi menggunakan Profile Steel ASTM-A36 (H-Beam, WF Beam, UNP, CNP, Bar angle, Pipe & Rebar)', 'Kg', '6459.85'),

            $this->row(2026, 'A. JASA FABRIKASI', '3. PEKERJAAN REPAIR PLATE & SHAFT & BAJA STRUKTUR (REPAIR WORK)', 'Repair Carbon Steel (Plate & baja struktur)', 'Repair Carbon Steel (Plate & baja struktur)', 'Kg', '5923.92'),
            $this->row(2026, 'A. JASA FABRIKASI', '3. PEKERJAAN REPAIR PLATE & SHAFT & BAJA STRUKTUR (REPAIR WORK)', 'Weld Build Up', 'Pekerjaan Weld Build Up - Carbon Steel', 'Cm3', '2206.48'),
            $this->row(2026, 'A. JASA FABRIKASI', '3. PEKERJAAN REPAIR PLATE & SHAFT & BAJA STRUKTUR (REPAIR WORK)', 'Weld Build Up', 'Pekerjaan Weld Build Up - Stainless Steel 304', 'Cm3', '3789.39'),
            $this->row(2026, 'A. JASA FABRIKASI', '3. PEKERJAAN REPAIR PLATE & SHAFT & BAJA STRUKTUR (REPAIR WORK)', 'Weld Build Up', 'Pekerjaan Weld Build Up - Stainless Steel 310 / 316', 'Cm3', '5736.85'),
            $this->row(2026, 'A. JASA FABRIKASI', '3. PEKERJAAN REPAIR PLATE & SHAFT & BAJA STRUKTUR (REPAIR WORK)', 'Weld Build Up', 'Pekerjaan Weld Build Up - Cast Iron', 'Cm3', '13094.99'),

            $this->row(2026, 'A. JASA FABRIKASI', '4. PEKERJAAN PENGEROLAN (BENDING/ROLLING WORK)', null, 'Pekerjaan Pengerolan Plate Steel ASTM A-36', 'Kg', '2258.59'),
            $this->row(2026, 'A. JASA FABRIKASI', '4. PEKERJAAN PENGEROLAN (BENDING/ROLLING WORK)', null, 'Pekerjaan Pengerolan Wear Resistant Plate', 'Kg', '2541.91'),
            $this->row(2026, 'A. JASA FABRIKASI', '4. PEKERJAAN PENGEROLAN (BENDING/ROLLING WORK)', null, 'Pengerolan Stainless Steel Plate', 'Kg', '2258.59'),
            $this->row(2026, 'A. JASA FABRIKASI', '4. PEKERJAAN PENGEROLAN (BENDING/ROLLING WORK)', null, 'Pengerolan Profile Steel structure (H-Beam, WF Beam, UNP, CNP, Bar angle, Pipe & Rebar)', 'Kg', '2258.59'),

            $this->row(2026, 'A. JASA FABRIKASI', '5. JASA PEKERJAAN MENGGUNAKAN MESIN (MACHINING BENCH WORK)', null, 'Bubut Kecil (Diameter <= 300 mm atau Panjang <= 1000 mm)', 'Jam', '36282.73'),
            $this->row(2026, 'A. JASA FABRIKASI', '5. JASA PEKERJAAN MENGGUNAKAN MESIN (MACHINING BENCH WORK)', null, 'Bubut Besar (Diameter > 300 mm atau Panjang > 1000 mm)', 'Jam', '53559.47'),
            $this->row(2026, 'A. JASA FABRIKASI', '5. JASA PEKERJAAN MENGGUNAKAN MESIN (MACHINING BENCH WORK)', null, 'Frais', 'Jam', '52963.23'),
            $this->row(2026, 'A. JASA FABRIKASI', '5. JASA PEKERJAAN MENGGUNAKAN MESIN (MACHINING BENCH WORK)', null, 'Milling gear', 'Jam', '49329.38'),
            $this->row(2026, 'A. JASA FABRIKASI', '5. JASA PEKERJAAN MENGGUNAKAN MESIN (MACHINING BENCH WORK)', null, 'Sekrap', 'Jam', '29490.10'),
            $this->row(2026, 'A. JASA FABRIKASI', '5. JASA PEKERJAAN MENGGUNAKAN MESIN (MACHINING BENCH WORK)', null, 'Bor', 'Jam', '23174.77'),
            $this->row(2026, 'A. JASA FABRIKASI', '5. JASA PEKERJAAN MENGGUNAKAN MESIN (MACHINING BENCH WORK)', null, 'Gerinda Shaft', 'Jam', '58324.98'),

            $this->row(2026, 'A. JASA FABRIKASI', '6. JASA PEKERJAAN PEMBUBUTAN DI LUAR WORKSHOP (MANUAL TURNING WORK)', null, 'Bubut Ketinggian < 12 M', 'Jam', '71186.79'),
            $this->row(2026, 'A. JASA FABRIKASI', '6. JASA PEKERJAAN PEMBUBUTAN DI LUAR WORKSHOP (MANUAL TURNING WORK)', null, 'Bubut Ketinggian > 12 M', 'Jam', '86136.14'),
            $this->row(2026, 'A. JASA FABRIKASI', '6. JASA PEKERJAAN PEMBUBUTAN DI LUAR WORKSHOP (MANUAL TURNING WORK)', null, 'Bubut Ketinggian < 12 M Area Panas > 35 C', 'Jam', '88814.11'),
            $this->row(2026, 'A. JASA FABRIKASI', '6. JASA PEKERJAAN PEMBUBUTAN DI LUAR WORKSHOP (MANUAL TURNING WORK)', null, 'Bubut Ketinggian > 12 M Area Panas > 35 C', 'Jam', '149225.28'),

            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '1. PEKERJAAN PEMBONGKARAN (DISMANTLING)', 'Pekerjaan Pembongkaran ketinggian < 12 Meter', 'Pekerjaan Pembongkaran Profile structure Carbon Steel (H-Beam, WF Beam, UNP, CNP, Bar angle, Pipe & Rebar)', 'Kg', '5860.94'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '1. PEKERJAAN PEMBONGKARAN (DISMANTLING)', 'Pekerjaan Pembongkaran ketinggian < 12 Meter', 'Pekerjaan Pembongkaran Profile structure Stainless Steel Steel (H-Beam, WF Beam, UNP, CNP, Bar angle Pipe)', 'Kg', '8791.40'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '1. PEKERJAAN PEMBONGKARAN (DISMANTLING)', 'Pekerjaan Pembongkaran ketinggian < 12 Meter', 'Pekerjaan Pembongkaran Plate Steel ASTM A-36/SS400', 'Kg', '8236.89'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '1. PEKERJAAN PEMBONGKARAN (DISMANTLING)', 'Pekerjaan Pembongkaran ketinggian < 12 Meter', 'Pekerjaan Pembongkaran Plate stainless steel', 'Kg', '10982.52'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '1. PEKERJAAN PEMBONGKARAN (DISMANTLING)', 'Pekerjaan Pembongkaran ketinggian < 12 Meter', 'Pekerjaan Pembongkaran Atap/dinding material', 'M2', '15917.84'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '1. PEKERJAAN PEMBONGKARAN (DISMANTLING)', 'Pekerjaan Pembongkaran ketinggian < 12 Meter', 'Pekerjaan Pembongkaran Mechanical', 'Kg', '1187.98'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '1. PEKERJAAN PEMBONGKARAN (DISMANTLING)', 'Pekerjaan Pembongkaran ketinggian < 12 Meter', 'Pekerjaan Pembongkaran Isolasi Aluminium Emboss Plate', 'M2', '32965.83'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '1. PEKERJAAN PEMBONGKARAN (DISMANTLING)', 'Pekerjaan Pembongkaran ketinggian < 12 Meter', 'Pembongkaran Castable', 'Kg', '1583.97'),

            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '1. PEKERJAAN PEMBONGKARAN (DISMANTLING)', 'Pekerjaan Pembongkaran ketinggian > 12 Meter', 'Pekerjaan Pembongkaran Profile structure Carbon Steel (H-Beam, WF Beam, UNP, CNP, Bar angle, Pipe & Rebar)', 'Kg', '8407.43'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '1. PEKERJAAN PEMBONGKARAN (DISMANTLING)', 'Pekerjaan Pembongkaran ketinggian > 12 Meter', 'Pekerjaan Pembongkaran Profile structure Stainless Steel Steel (H-Beam, WF Beam, UNP, CNP, Bar angle Pipe)', 'Kg', '10347.61'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '1. PEKERJAAN PEMBONGKARAN (DISMANTLING)', 'Pekerjaan Pembongkaran ketinggian > 12 Meter', 'Pekerjaan Pembongkaran Plate Steel ASTM A-36/SS400', 'Kg', '8580.09'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '1. PEKERJAAN PEMBONGKARAN (DISMANTLING)', 'Pekerjaan Pembongkaran ketinggian > 12 Meter', 'Pekerjaan Pembongkaran Cast Iron plate', 'Kg', '8786.02'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '1. PEKERJAAN PEMBONGKARAN (DISMANTLING)', 'Pekerjaan Pembongkaran ketinggian > 12 Meter', 'Pekerjaan Pembongkaran Plate stainless steel', 'Kg', '13727.74'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '1. PEKERJAAN PEMBONGKARAN (DISMANTLING)', 'Pekerjaan Pembongkaran ketinggian > 12 Meter', 'Pekerjaan Pembongkaran Atap/dinding material zincalume Ecospan', 'M2', '31835.69'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '1. PEKERJAAN PEMBONGKARAN (DISMANTLING)', 'Pekerjaan Pembongkaran ketinggian > 12 Meter', 'Pekerjaan Pembongkaran Mechanical', 'Kg', '1729.59'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '1. PEKERJAAN PEMBONGKARAN (DISMANTLING)', 'Pekerjaan Pembongkaran ketinggian > 12 Meter', 'Pekerjaan Pembongkaran Isolasi Aluminium Emboss Plate', 'M2', '63966.65'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '1. PEKERJAAN PEMBONGKARAN (DISMANTLING)', 'Pekerjaan Pembongkaran ketinggian > 12 Meter', 'Pembongkaran Castable', 'Kg', '1965.45'),

            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '2. PEKERJAAN PEMASANGAN (ERECTION)', 'Pekerjaan pemasangan ketinggian < 12 Meter', 'Pekerjaan Pemasangan Profile Steel ASTM-A36 (H-Beam, WF Beam, UNP, CNP, Bar angle, Pipe & Rebar)', 'Kg', '7437.34'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '2. PEKERJAAN PEMASANGAN (ERECTION)', 'Pekerjaan pemasangan ketinggian < 12 Meter', 'Pekerjaan Pemasangan Profile structure Stainless Steel Steel (H-Beam, WF Beam, UNP, CNP, Bar angle Pipe)', 'Kg', '8278.09'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '2. PEKERJAAN PEMASANGAN (ERECTION)', 'Pekerjaan pemasangan ketinggian < 12 Meter', 'Pekerjaan Pemasangan Plate Steel ASTM A-36', 'Kg', '6968.72'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '2. PEKERJAAN PEMASANGAN (ERECTION)', 'Pekerjaan pemasangan ketinggian < 12 Meter', 'Pekerjaan Pemasangan Wear Resistent Plate', 'Kg', '7259.08'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '2. PEKERJAAN PEMASANGAN (ERECTION)', 'Pekerjaan pemasangan ketinggian < 12 Meter', 'Pekerjaan Pemasangan Cast Iron plate', 'Kg', '8786.02'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '2. PEKERJAAN PEMASANGAN (ERECTION)', 'Pekerjaan pemasangan ketinggian < 12 Meter', 'Pekerjaan Pemasangan Stainless Steel Plate SUS 304', 'Kg', '8710.90'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '2. PEKERJAAN PEMASANGAN (ERECTION)', 'Pekerjaan pemasangan ketinggian < 12 Meter', 'Pekerjaan Pemasangan Stainless steel plate (ASTM 310 & ASTM 30815)', 'Kg', '10453.08'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '2. PEKERJAAN PEMASANGAN (ERECTION)', 'Pekerjaan pemasangan ketinggian < 12 Meter', 'Pekerjaan Pemasangan Atap/dinding material zincalume Ecospan', 'M2', '28233.78'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '2. PEKERJAAN PEMASANGAN (ERECTION)', 'Pekerjaan pemasangan ketinggian < 12 Meter', 'Pekerjaan Pemasangan Isolasi Aluminium Emboss Plate', 'M2', '77051.87'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '2. PEKERJAAN PEMASANGAN (ERECTION)', 'Pekerjaan pemasangan ketinggian < 12 Meter', 'Pekerjaan Pemasangan Mechanical', 'Kg', '2375.95'),

            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '2. PEKERJAAN PEMASANGAN (ERECTION)', 'Pekerjaan pemasangan ketinggian > 12 Meter', 'Pekerjaan Pemasangan Profile Carbon Steel (H-Beam, WF Beam, UNP, CNP, Bar angle, Pipe & Rebar)', 'Kg', '7760.71'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '2. PEKERJAAN PEMASANGAN (ERECTION)', 'Pekerjaan pemasangan ketinggian > 12 Meter', 'Pekerjaan Pemasangan Profile structure Stainless Steel Steel (H-Beam, WF Beam, UNP, CNP, Bar angle Pipe)', 'Kg', '10347.61'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '2. PEKERJAAN PEMASANGAN (ERECTION)', 'Pekerjaan pemasangan ketinggian > 12 Meter', 'Pekerjaan Pemasangan Plate Steel ASTM A-36/SS400', 'Kg', '9866.81'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '2. PEKERJAAN PEMASANGAN (ERECTION)', 'Pekerjaan pemasangan ketinggian > 12 Meter', 'Pekerjaan Pemasangan Wear Resistent Plate', 'Kg', '10724.79'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '2. PEKERJAAN PEMASANGAN (ERECTION)', 'Pekerjaan pemasangan ketinggian > 12 Meter', 'Pekerjaan Pemasangan Cast Iron plate', 'Kg', '9060.58'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '2. PEKERJAAN PEMASANGAN (ERECTION)', 'Pekerjaan pemasangan ketinggian > 12 Meter', 'Pekerjaan Pemasangan Stainless Steel Plate ASTM 304', 'Kg', '13727.74'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '2. PEKERJAAN PEMASANGAN (ERECTION)', 'Pekerjaan pemasangan ketinggian > 12 Meter', 'Pekerjaan Pemasangan Stainless steel plate (ASTM 310 & ASTM 30815)', 'Kg', '15443.70'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '2. PEKERJAAN PEMASANGAN (ERECTION)', 'Pekerjaan pemasangan ketinggian > 12 Meter', 'Pekerjaan Pemasangan Atap/dinding material zincalume Ecospan', 'M2', '45716.73'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '2. PEKERJAAN PEMASANGAN (ERECTION)', 'Pekerjaan pemasangan ketinggian > 12 Meter', 'Pekerjaan Pemasangan Isolasi Aluminium Emboss Plate', 'M2', '81319.87'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '2. PEKERJAAN PEMASANGAN (ERECTION)', 'Pekerjaan pemasangan ketinggian > 12 Meter', 'Pekerjaan Pemasangan Mechanical', 'Kg', '2573.95'),

            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '3. JASA PEKERJAAN PENGECATAN (PAINTING)', 'Pekerjaan Pengecatan Ketinggian < 12 Meter', 'Pekerjaan Pengecatan Ketinggian < 12 Meter (Based Primer + solvent-based Painting Thickness 150 - 250 um)', 'M2', '30219.20'),
            $this->row(2026, 'B. JASA PEKERJAAN KONSTRUKSI', '3. JASA PEKERJAAN PENGECATAN (PAINTING)', 'Pekerjaan Pengecatan Ketinggian > 12 Meter', 'Pekerjaan Pengecatan Ketinggian > 12 Meter (Based Primer + solvent-based Painting Thickness 150 - 250 um)', 'M2', '52763.68'),

            $this->row(2026, 'C. MATERIAL', null, null, 'Plate Steel Thickness <= 5 mm ASTM A-36/SS400', 'Kg', '22914.16'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Plate Steel Thickness > 5 mm - 15 mm ASTM A-36/SS400', 'Kg', '23324.75'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Plate Steel Thickness > 15 mm ASTM A-36/SS400', 'Kg', '25062.75'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Plate Checkered Thickness 4 mm - 6 mm ASTM A-36/SS400', 'Kg', '33822.88'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Plate Grating Galvanized; Weight Bar 30 mm; Thickness bar 3 mm ASTM A-36/SS400', 'Kg', '54362.58'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Plate Wear Resistance Plate Min.360 HB', 'Kg', '45328.80'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Plate Wear Resistance Plate Min.460 HB', 'Kg', '48566.57'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Plate Wear Resistance Overlay Plate 4 ON 6', 'Kg', '47737.61'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Plate Stainless Steel Plate SUS 304', 'Kg', '45328.80'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Plate Stainless Steel Plate SUS 310', 'Kg', '143361.24'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Plate Stainless Steel Plate ASTM 301815', 'Kg', '196892.08'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Plate zincalume Ecospan Thk 0,4 ASTM A792', 'M2', '110434.50'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Plate Aluminium Emboss Plate Thk 0,6 mm', 'M2', '151358.22'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Screw Roofing Length 70 mm', 'Ea', '1918.68'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Screw Roofing Length 50 mm', 'Ea', '1439.01'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Profile H-Beam ASTM-A36', 'Kg', '22544.48'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Profile WF Beam ASTM-A36', 'Kg', '22544.48'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Profile UNP ASTM A-36', 'Kg', '21585.14'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Profile Angle ASTM A-36/SS400', 'Kg', '21585.14'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Profile CNP ASTM A-36/SS400', 'Kg', '21585.14'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Deformed Bar ASTM A615 (BJTD)', 'Kg', '26381.84'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Plain Bar ASTM A615 (BJTP)', 'Kg', '21585.14'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Pipe Carbon Steel SCH 40 ASTM A-53; Seamless', 'Kg', '29883.43'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Pipe Carbon Steel SCH 80 ASTM A-53; Seamless', 'Kg', '30842.77'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Pipe Carbon Steel SCH 40 ASTM A-53; Welded', 'Kg', '28924.09'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Pipe Carbon Steel SCH 80 ASTM A-53; Welded', 'Kg', '29883.43'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Pipe Galvanized Steel SCH 20 ASTM A-53; Seamless', 'Kg', '30075.30'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Pipe Galvanized Steel SCH 40 ASTM A-53; Seamless', 'Kg', '30075.30'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Pipe Galvanized Steel SCH 20 ASTM A-53; Welded', 'Kg', '28924.09'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Pipe Galvanized Steel SCH 40 ASTM A-53; Welded', 'Kg', '30267.17'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Shaft Steel AISI 1045', 'Kg', '40407.39'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Shaft Steel AISI 4340', 'Kg', '188702.12'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Hollow Shaft Steel AISI 1045', 'Kg', '40407.39'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Hexagon Bar Steel AISI 1045', 'Kg', '54156.64'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Manganese Bronze SAE 430A', 'Kg', '235709.76'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Leaded Bronze SAE 660', 'Kg', '125217.81'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Shaft Stainless Steel AISI 304', 'Kg', '94260.88'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Shaft Stainless Steel AISI 316', 'Kg', '238129.70'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Gray Cast Iron ASTM A48', 'Kg', '26861.51'),
            $this->row(2026, 'C. MATERIAL', null, null, 'High Chromium Cast Iron ASTM A532', 'Kg', '46527.98'),
            $this->row(2026, 'C. MATERIAL', null, null, 'PTFE (polytetrafluoroethylene) ASTM D3294 (Teflon rod)', 'Kg', '315439.05'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Polycaprolactam (Nylon 6)', 'Kg', '268123.19'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Oil Based Primer (Zinc Chromate Primer/Epoxy Primer)', 'Kg', '100730.67'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Oil solvent-based (Nippon Paint)', 'Kg', '100730.67'),
            $this->row(2026, 'C. MATERIAL', null, null, 'Paint Thinner (Thinner ND super)', 'Liter', '35975.24'),
        ];

        DB::transaction(function () use ($items, $userId): void {
            FabricationConstructionContract::query()->delete();

            foreach ($items as $item) {
                FabricationConstructionContract::query()->create([
                    ...$item,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }
        });
    }

    /**
     * @return array<string, string|int>
     */
    private function row(
        int $tahun,
        string $jenisItem,
        ?string $subJenisItem,
        ?string $kategoriItem,
        string $namaItem,
        string $satuan,
        string $hargaSatuan,
    ): array {
        return [
            'tahun' => $tahun,
            'jenis_item' => $jenisItem,
            'sub_jenis_item' => $subJenisItem,
            'kategori_item' => $kategoriItem,
            'nama_item' => $namaItem,
            'satuan' => $satuan,
            'harga_satuan' => $hargaSatuan,
        ];
    }
}
