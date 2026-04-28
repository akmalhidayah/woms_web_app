<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class StructureOrganizationUserInitialSeeder extends Seeder
{
    /**
     * Update initials for structure organization approval users.
     */
    public function run(): void
    {
        $initials = [
            // GENERAL MANAGER
            ['email' => 'ari.mahesthi@sig.id', 'name' => 'Ari N.K. Tri Mahesthi', 'inisial' => 'ATM'],
            ['email' => 'manat.silitonga@sig.id', 'name' => 'Manat L. Silitonga', 'inisial' => 'MSG'],
            ['email' => 'syafardino@sig.id', 'name' => 'Syafardino', 'inisial' => 'SFO'],
            ['email' => 'yosi.reapradana@sig.id', 'name' => 'Yosi Pradana', 'inisial' => 'YRP'],
            ['email' => 'adi.fatkhurrohman@sig.id', 'name' => 'Adi Fatkhurrohman', 'inisial' => 'AFR'],

            // SENIOR MANAGER / UNIT KERJA
            ['email' => 'muh.musafir@sig.id', 'name' => 'Muh. Musafir', 'inisial' => 'MMU'],
            ['email' => 'albar.budiman@sig.id', 'name' => 'Albar Budiman', 'inisial' => 'ABN'],
            ['email' => 'dwi.kurniawan@sig.id', 'name' => 'Dwi Kurniawan', 'inisial' => 'DWK'],
            ['email' => 'maryono@sig.id', 'name' => 'Maryono', 'inisial' => 'MYO'],
            ['email' => 'andi.hilman@sig.id', 'name' => 'Andi Hilman', 'inisial' => 'AHL'],
            ['email' => 'ardiansyah.5384@sig.id', 'name' => 'Ardiansyah', 'inisial' => 'ADH'],
            ['email' => 'ihrar.azis@sig.id', 'name' => 'Ihrar Nuzul Azis', 'inisial' => 'INA'],
            ['email' => 'alamsyah.5247@sig.id', 'name' => 'Alamsyah', 'inisial' => 'ALS'],
            ['email' => 'nur.mustafa@sig.id', 'name' => 'Nur Asmal Mustafa', 'inisial' => 'NAA'],
            ['email' => 'yatman.setiawan@sig.id', 'name' => 'Yatman Setiawan', 'inisial' => 'YSW'],
            ['email' => 'muh.asis@sig.id', 'name' => 'Muh. Asis Asri', 'inisial' => 'MAS'],
            ['email' => 'ifnul.mubarak@sig.id', 'name' => 'Ifnul Mubarak', 'inisial' => 'IMB'],
            ['email' => 'suryadi.pasambangi@sig.id', 'name' => 'Suryadi Pasambangi', 'inisial' => 'SYP'],
            ['email' => 'jasmiati@sig.id', 'name' => 'Jasmiati', 'inisial' => 'JSM'],
            ['email' => 'irsan@sig.id', 'name' => 'Irsan ST', 'inisial' => 'ISN'],
            ['email' => 'stevanus.bodro@sig.id', 'name' => 'Stevanus Bodro Wibowo', 'inisial' => 'SBW'],
            ['email' => 'm.alianto@sig.id', 'name' => 'M. Alianto M', 'inisial' => 'MAT'],
            ['email' => 'hakmal.candra@sig.id', 'name' => 'Hakmal Candra', 'inisial' => 'HCD'],
            ['email' => 'imran@sig.id', 'name' => 'Imran', 'inisial' => 'IMR'],
            ['email' => 'parlindungan.pardosi@sig.id', 'name' => 'Parlindungan Pardosi', 'inisial' => 'PPS'],
            ['email' => 'budi.wiyono@sig.id', 'name' => 'Budi Wiyono', 'inisial' => 'BYO'],
            ['email' => 'mudassir.syam@sig.id', 'name' => 'Mudassir Syam', 'inisial' => 'MDS'],
            ['email' => 'muhammad.rusdianto@sig.id', 'name' => 'Muhammad Rusdianto HN', 'inisial' => 'MRO'],
            ['email' => 'abd.wahid5082@sig.id', 'name' => 'Abd. Wahid', 'inisial' => 'AWD'],

            // MANAGER - CLINKER & CEMENT PRODUCTION
            ['email' => 'nasaruddin.5133@sig.id', 'name' => 'Nasaruddin', 'inisial' => 'NSD'],
            ['email' => 'wibowo@sig.id', 'name' => 'Wibowo', 'inisial' => 'WBW'],
            ['email' => 'wahyu.a@sig.id', 'name' => 'Wahyu A.R.', 'inisial' => 'WHY'],
            ['email' => 'safruddin.haeruddin@sig.id', 'name' => 'Safruddin Haeruddin', 'inisial' => 'SFH'],
            ['email' => 'ilyasusanto@sig.id', 'name' => 'Ilyasusanto', 'inisial' => 'IYS'],

            // MANAGER - MAINTENANCE
            ['email' => 'al.azhar@sig.id', 'name' => 'Al Azhar', 'inisial' => 'AZH'],
            ['email' => 'muh.basri4911@sig.id', 'name' => 'Muh. Basri', 'inisial' => 'MBS'],
            ['email' => 'irwan.saparuddin@sig.id', 'name' => 'Irwan Saparuddin', 'inisial' => 'ISR'],
            ['email' => 'arif.budiman@sig.id', 'name' => 'Arif Budiman', 'inisial' => 'ABD'],
            ['email' => 'muhammad.ageng@sig.id', 'name' => 'Muhammad Ageng Anom', 'inisial' => 'MHA'],
            ['email' => 'alimuddin.5027@sig.id', 'name' => 'H. Alimuddin', 'inisial' => 'ALN'],
            ['email' => 'putra.sumaryanto@sig.id', 'name' => 'Putra Adhi Sumaryanto', 'inisial' => 'PAS'],
            ['email' => 'kaharuddin.5292@sig.id', 'name' => 'Kaharuddin', 'inisial' => 'KHR'],
            ['email' => 'abd.salam5117@sig.id', 'name' => 'Abd. Salam', 'inisial' => 'ABS'],

            // MANAGER - PROJECT MANAGEMENT & MAINTENANCE SUPPORT
            ['email' => 'asriyanto.nasir@sig.id', 'name' => 'Asriyanto Nasir', 'inisial' => 'AON'],
            ['email' => 'nani.lestari@sig.id', 'name' => 'Nani Sri Lestari', 'inisial' => 'NSL'],
            ['email' => 'cendhika.esa@sig.id', 'name' => 'Cendhika Larassayom Esa', 'inisial' => 'CLE'],
            ['email' => 'ahmad.4924@sig.id', 'name' => 'Ahmad', 'inisial' => 'AHA'],
            ['email' => 'syahruddin.ngewa@sig.id', 'name' => 'Syaharuddin Ngewa', 'inisial' => 'SNA'],
            ['email' => 'surahman@sig.id', 'name' => 'Surahman', 'inisial' => 'SRH'],

            // MANAGER - PRODUCTION PLANNING & CONTROL
            ['email' => 'm.rizal@sig.id', 'name' => 'M. Rizal M.', 'inisial' => 'MRM'],
            ['email' => 'resti.setianingrum@sig.id', 'name' => 'Resti Setianingrum', 'inisial' => 'RSN'],
            ['email' => 'lukas.tandi@sig.id', 'name' => 'Lukas Tandi', 'inisial' => 'LKT'],
            ['email' => 'ahmad.imani@sig.id', 'name' => 'Ahmad Zaky Imani', 'inisial' => 'AZK'],
            ['email' => 'alfian.jais@sig.id', 'name' => 'Alfian Jais', 'inisial' => 'AJS'],
            ['email' => 'm.yasin@sig.id', 'name' => 'M. Yasin', 'inisial' => 'MYN'],
            ['email' => 'andi.mayundari@sig.id', 'name' => 'Andi Mayundari', 'inisial' => 'AMY'],
            ['email' => 'ahmad.zakki@sig.id', 'name' => 'Ahmad Zakki Mubarok', 'inisial' => 'AZM'],
            ['email' => 'faizal.razak@sig.id', 'name' => 'Faizal Amir Razak', 'inisial' => 'FAR'],
            ['email' => 'angga.adhitya@sig.id', 'name' => 'Angga Adhitya', 'inisial' => 'AGA'],
            ['email' => 'm.sahrir@sig.id', 'name' => 'M. Sahrir', 'inisial' => 'MSI'],
            ['email' => 'syamsupriadi@sig.id', 'name' => 'Syamsupriadi', 'inisial' => 'SSP'],
            ['email' => 'sjarifuddin.said@sig.id', 'name' => 'Sjarifuddin Said', 'inisial' => 'SJP'],

            // MANAGER - INFRASTRUCTURE
            ['email' => 'sumardi@sig.id', 'name' => 'Sumardi', 'inisial' => 'SMR'],
            ['email' => 'margiantonius@sig.id', 'name' => 'Margiantonius', 'inisial' => 'MRG'],
            ['email' => 'rabenka.palesa@sig.id', 'name' => 'Rabenka Palesa', 'inisial' => 'RPS'],

            // MANAGER - MINING & POWER PLANT
            ['email' => 'ferry.wardana@sig.id', 'name' => 'H. Ferry Wardana', 'inisial' => 'FWD'],
            ['email' => 'muhammad.baso@sig.id', 'name' => 'Muhammad Zubair Baso', 'inisial' => 'MZU'],
            ['email' => 'wijanarko@sig.id', 'name' => 'Wijanarko', 'inisial' => 'WJO'],
            ['email' => 'andi.rahmat@sig.id', 'name' => 'Andi Rahmat', 'inisial' => 'ARM'],
            ['email' => 'ruben.bondo@sig.id', 'name' => 'Ruben Bondo', 'inisial' => 'MSM'],
            ['email' => 'lamasi@sig.id', 'name' => 'Lamasi', 'inisial' => 'LMS'],
            ['email' => 'andi.saransi@sig.id', 'name' => 'Andi Kasman Saransi', 'inisial' => 'AKS'],
            ['email' => 'muh.danial@sig.id', 'name' => 'Muh. Daris Danial', 'inisial' => 'MDD'],
        ];

        foreach ($initials as $item) {
            User::query()
                ->where('email', $item['email'])
                ->update([
                    'inisial' => $item['inisial'],
                ]);
        }
    }
}