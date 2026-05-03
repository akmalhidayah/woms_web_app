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
            // =========================
            // GENERAL MANAGER / DEPARTEMEN
            // =========================
            ['email' => 'ari.mahesthi@sig.id', 'name' => 'Ari N.K. Tri Mahesthi', 'inisial' => 'ATM'],
            ['email' => 'manat.silitonga@sig.id', 'name' => 'Manat L. Silitonga', 'inisial' => 'MSG'],
            ['email' => 'syafardino@sig.id', 'name' => 'Syafardino', 'inisial' => 'SFO'],
            ['email' => 'yosi.reapradana@sig.id', 'name' => 'Yosi Pradana', 'inisial' => 'YRP'],
            ['email' => 'andi.rachman@sig.id', 'name' => 'Andi Rachman', 'inisial' => 'ANR'],
            ['email' => 'adi.fatkhurrohman@sig.id', 'name' => 'Adi Fatkhurrohman', 'inisial' => 'AFR'],

            // =========================
            // SENIOR MANAGER / UNIT KERJA
            // =========================

            // Clinker & Cement Production
            ['email' => 'muh.musafir@sig.id', 'name' => 'Muh. Musafir', 'inisial' => 'MMU'],
            ['email' => 'albar.budiman@sig.id', 'name' => 'Albar Budiman', 'inisial' => 'ABN'],
            ['email' => 'dwi.kurniawan@sig.id', 'name' => 'Dwi Kurniawan', 'inisial' => 'DWK'],

            // Maintenance
            ['email' => 'maryono@sig.id', 'name' => 'Maryono', 'inisial' => 'MYO'],
            ['email' => 'andi.hilman@sig.id', 'name' => 'Andi Hilman', 'inisial' => 'AHL'],
            ['email' => 'ardiansyah.5384@sig.id', 'name' => 'Ardiansyah', 'inisial' => 'ADH'],
            ['email' => 'ihrar.azis@sig.id', 'name' => 'Ihrar Nuzul Azis', 'inisial' => 'INA'],
            ['email' => 'suryadani@sig.id', 'name' => 'Suryadani', 'inisial' => 'SYD'],

            // Project Management & Maintenance Support
            ['email' => 'alamsyah.5247@sig.id', 'name' => 'Alamsyah', 'inisial' => 'ALS'],
            ['email' => 'nur.mustafa@sig.id', 'name' => 'Nur Asmal Mustafa', 'inisial' => 'NAA'],
            ['email' => 'yatman.setiawan@sig.id', 'name' => 'Yatman Setiawan', 'inisial' => 'YSW'],
            ['email' => 'muh.asis@sig.id', 'name' => 'Muh. Asis Asri', 'inisial' => 'MAS'],
            ['email' => 'ifnul.mubarak@sig.id', 'name' => 'Ifnul Mubarak', 'inisial' => 'IMB'],

            // Production Planning & Control
            ['email' => 'suryadi.pasambangi@sig.id', 'name' => 'Suryadi Pasambangi', 'inisial' => 'SYP'],
            ['email' => 'jasmiati@sig.id', 'name' => 'Jasmiati', 'inisial' => 'JSM'],
            ['email' => 'irsan@sig.id', 'name' => 'Irsan ST', 'inisial' => 'ISN'],
            ['email' => 'stevanus.bodro@sig.id', 'name' => 'Stevanus Bodro Wibowo', 'inisial' => 'SBW'],
            ['email' => 'm.alianto@sig.id', 'name' => 'M. Alianto M', 'inisial' => 'MAT'],

            // Infrastructure
            ['email' => 'wellem.ariance@sig.id', 'name' => 'Wellem Ariance', 'inisial' => null],
            ['email' => 'ambo.masse@sig.id', 'name' => 'Ambo Masse', 'inisial' => null],
            ['email' => 'guntur.eko.prasetyo@sig.id', 'name' => 'Capt. Guntur Eko Prasetyo', 'inisial' => null],
            ['email' => 'simon.salea@sig.id', 'name' => 'Simon Salea', 'inisial' => null],
            ['email' => 'hakmal.candra@sig.id', 'name' => 'Hakmal Candra', 'inisial' => 'HCD'],

            // Mining & Power Plant
            ['email' => 'imran@sig.id', 'name' => 'Imran', 'inisial' => 'IMR'],
            ['email' => 'parlindungan.pardosi@sig.id', 'name' => 'Parlindungan Pardosi', 'inisial' => 'PPS'],
            ['email' => 'budi.wiyono@sig.id', 'name' => 'Budi Wiyono', 'inisial' => 'BYO'],
            ['email' => 'mudassir.syam@sig.id', 'name' => 'Mudassir Syam', 'inisial' => 'MDS'],
            ['email' => 'muhammad.rusdianto@sig.id', 'name' => 'Muhammad Rusdianto HN', 'inisial' => 'MRO'],
            ['email' => 'abd.wahid5082@sig.id', 'name' => 'Abd. Wahid', 'inisial' => 'AWD'],

            // =========================
            // MANAGER / SEKSI
            // =========================

            // Clinker & Cement Production
            ['email' => 'nasaruddin.5133@sig.id', 'name' => 'Nasaruddin', 'inisial' => 'NSD'],
            ['email' => 'wibowo@sig.id', 'name' => 'Wibowo', 'inisial' => 'WBW'],
            ['email' => 'wahyu.a@sig.id', 'name' => 'Wahyu A.R.', 'inisial' => 'WHY'],
            ['email' => 'andika.tandirura@sig.id', 'name' => 'Andika Sariy Tandirura', 'inisial' => 'AST'],
            ['email' => 'muhammad.fausi@sig.id', 'name' => 'Muhammad Fausi', 'inisial' => 'MHF'],
            ['email' => 'antonius.sukma@sig.id', 'name' => 'Antonius F.H. Sukma', 'inisial' => 'AFH'],
            ['email' => 'safruddin.haeruddin@sig.id', 'name' => 'Safruddin Haeruddin', 'inisial' => 'SFH'],
            ['email' => 'ilyasusanto@sig.id', 'name' => 'Ilyasusanto', 'inisial' => 'IYS'],

            // Maintenance
            ['email' => 'al.azhar@sig.id', 'name' => 'Al Azhar', 'inisial' => 'AZH'],
            ['email' => 'muh.basri4911@sig.id', 'name' => 'Muh. Basri', 'inisial' => 'MBS'],
            ['email' => 'irwan.saparuddin@sig.id', 'name' => 'Irwan Saparuddin', 'inisial' => 'ISR'],
            ['email' => 'imam.suyuti@sig.id', 'name' => 'Imam Suyuti', 'inisial' => 'IMS'],
            ['email' => 'andi.yustian@sig.id', 'name' => 'Mohammad Andi Yustian', 'inisial' => 'MAY'],
            ['email' => 'arif.budiman@sig.id', 'name' => 'Arif Budiman', 'inisial' => 'ABD'],
            ['email' => 'muhammad.ageng@sig.id', 'name' => 'Muhammad Ageng Anom', 'inisial' => 'MHA'],
            ['email' => 'alimuddin.5027@sig.id', 'name' => 'H. Alimuddin', 'inisial' => 'ALN'],
            ['email' => 'putra.sumaryanto@sig.id', 'name' => 'Putra Adhi Sumaryanto', 'inisial' => 'PAS'],
            ['email' => 'ezra@sig.id', 'name' => 'Ezra', 'inisial' => 'EZR'],
            ['email' => 'syahruddin.5064@sig.id', 'name' => 'H. Syahruddin', 'inisial' => null],
            ['email' => 'kaharuddin.5292@sig.id', 'name' => 'Kaharuddin', 'inisial' => 'KHR'],
            ['email' => 'abd.salam5117@sig.id', 'name' => 'Abd. Salam', 'inisial' => 'ABS'],
            ['email' => 'sukma.hastika@sig.id', 'name' => 'MZ Sukma Hastika', 'inisial' => 'SMH'],

            // Project Management & Maintenance Support
            ['email' => 'asriyanto.nasir@sig.id', 'name' => 'Asriyanto Nasir', 'inisial' => 'AON'],
            ['email' => 'nani.lestari@sig.id', 'name' => 'Nani Sri Lestari', 'inisial' => 'NSL'],
            ['email' => 'cendhika.esa@sig.id', 'name' => 'Cendhika Larassayom Esa', 'inisial' => 'CLE'],
            ['email' => 'ahmad.4924@sig.id', 'name' => 'Ahmad', 'inisial' => 'AHA'],
            ['email' => 'syahruddin.ngewa@sig.id', 'name' => 'Syaharuddin Ngewa', 'inisial' => 'SNA'],
            ['email' => 'surahman@sig.id', 'name' => 'Surahman', 'inisial' => 'SRH'],

            // Production Planning & Control
            ['email' => 'm.rizal@sig.id', 'name' => 'M. Rizal M.', 'inisial' => 'MRM'],
            ['email' => 'resti.setianingrum@sig.id', 'name' => 'Resti Setianingrum', 'inisial' => 'RSN'],
            ['email' => 'agus.firmanto@sig.id', 'name' => 'Agus Firmanto', 'inisial' => 'AGF'],
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
            ['email' => 'azis4881@sig.id', 'name' => 'Azis', 'inisial' => 'AZS'],

            // Infrastructure
            ['email' => 'mathius.rota@sig.id', 'name' => 'Mathius Rota', 'inisial' => 'MAR'],
            ['email' => 'isak@sig.id', 'name' => 'Isak', 'inisial' => 'ISK'],
            ['email' => 'rahmat.s@sig.id', 'name' => 'Rahmat S', 'inisial' => 'RHT'],
            ['email' => 'harianto.marzuki@sig.id', 'name' => 'Harianto Marzuki', 'inisial' => 'HAM'],
            ['email' => 'helton.yhoni@sig.id', 'name' => 'Helton Yhoni', 'inisial' => 'HEY'],
            ['email' => 'achmad.firmansjah@sig.id', 'name' => 'Achmad Firmansjah', 'inisial' => 'AFJ'],
            ['email' => 'bungin.r@sig.id', 'name' => 'Bungin R', 'inisial' => 'BNR'],
            ['email' => 'kardianusti.bua@sig.id', 'name' => 'Kardianusti T. Bua', 'inisial' => 'KTB'],
            ['email' => 'jamaluddin.5094@sig.id', 'name' => 'Jamaluddin', 'inisial' => 'JAM'],
            ['email' => 'zulfadli@sig.id', 'name' => 'Zulfadli', 'inisial' => 'ZLF'],
            ['email' => 'wirawan.yusuf@sig.id', 'name' => 'Wirawan Yusuf', 'inisial' => 'WIY'],
            ['email' => 'sumardi@sig.id', 'name' => 'Sumardi', 'inisial' => 'SMR'],
            ['email' => 'margiantonius@sig.id', 'name' => 'Margiantonius', 'inisial' => 'MRG'],
            ['email' => 'rabenka.palesa@sig.id', 'name' => 'Rabenka Palesa', 'inisial' => 'RPS'],
            ['email' => 'mursalim.tawang@sig.id', 'name' => 'Mursalin Tawang', 'inisial' => 'MTW'],
            ['email' => 'aszriadi@sig.id', 'name' => 'Aszriadi', 'inisial' => 'AZD'],

            // Mining & Power Plant
            ['email' => 'ferry.wardana@sig.id', 'name' => 'H. Ferry Wardana', 'inisial' => 'FWD'],
            ['email' => 'muhammad.baso@sig.id', 'name' => 'Muhammad Zubair Baso', 'inisial' => 'MZU'],
            ['email' => 'syamsul.bahri@sig.id', 'name' => 'Syamsul Bahri', 'inisial' => 'SYU'],
            ['email' => 'wijanarko@sig.id', 'name' => 'Wijanarko', 'inisial' => 'WJO'],
            ['email' => 'syaharuddin@sig.id', 'name' => 'Syaharuddin', 'inisial' => null],
            ['email' => 'roniansyah.malinggi@sig.id', 'name' => 'Roniansyah Malinggi', 'inisial' => 'RNY'],
            ['email' => 'andi.rahmat@sig.id', 'name' => 'Andi Rahmat', 'inisial' => 'ARM'],
            ['email' => 'ruben.bondo@sig.id', 'name' => 'Ruben Bondo', 'inisial' => 'MSM'],
            ['email' => 'dasa.agustriawan@sig.id', 'name' => 'Dasa Agustriawan', 'inisial' => 'DSA'],
            ['email' => 'lamasi@sig.id', 'name' => 'Lamasi', 'inisial' => 'LMS'],
            ['email' => 'irfan@sig.id', 'name' => 'Irfan', 'inisial' => 'IRN'],
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