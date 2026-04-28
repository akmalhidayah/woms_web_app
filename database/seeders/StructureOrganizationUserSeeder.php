<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class StructureOrganizationUserSeeder extends Seeder
{
    /**
     * Seed approval users.
     */
    public function run(): void
    {
        $password = 'bengkelmesin123';

        $users = [
            ['id' => 6, 'name' => 'Ari N.K. Tri Mahesthi', 'email' => 'ari.mahesthi@sig.id'],
            ['id' => 7, 'name' => 'Manat L. Silitonga', 'email' => 'manat.silitonga@sig.id'],
            ['id' => 8, 'name' => 'Syafardino', 'email' => 'syafardino@sig.id'],
            ['id' => 9, 'name' => 'Yosi Pradana', 'email' => 'yosi.reapradana@sig.id'],
            ['id' => 10, 'name' => 'Andi Rachman', 'email' => 'andi.rachman@sig.id'],
            ['id' => 11, 'name' => 'Adi Fatkhurrohman', 'email' => 'adi.fatkhurrohman@sig.id'],

            ['id' => 12, 'name' => 'Muh. Musafir', 'email' => 'muh.musafir@sig.id'],
            ['id' => 13, 'name' => 'Albar Budiman', 'email' => 'albar.budiman@sig.id'],
            ['id' => 14, 'name' => 'Dwi Kurniawan', 'email' => 'dwi.kurniawan@sig.id'],
            ['id' => 15, 'name' => 'Maryono', 'email' => 'maryono@sig.id'],
            ['id' => 16, 'name' => 'Andi Hilman', 'email' => 'andi.hilman@sig.id'],
            ['id' => 17, 'name' => 'Ardiansyah', 'email' => 'ardiansyah.5384@sig.id'],
            ['id' => 18, 'name' => 'Ihrar Nuzul Azis', 'email' => 'ihrar.azis@sig.id'],
            ['id' => 19, 'name' => 'Suryadani', 'email' => 'suryadani@sig.id'],
            ['id' => 20, 'name' => 'Alamsyah', 'email' => 'alamsyah.5247@sig.id'],
            ['id' => 21, 'name' => 'Nur Asmal Mustafa', 'email' => 'nur.mustafa@sig.id'],
            ['id' => 22, 'name' => 'Yatman Setiawan', 'email' => 'yatman.setiawan@sig.id'],
            ['id' => 23, 'name' => 'Muh. Asis Asri', 'email' => 'muh.asis@sig.id'],
            ['id' => 24, 'name' => 'Ifnul Mubarak', 'email' => 'ifnul.mubarak@sig.id'],
            ['id' => 25, 'name' => 'Suryadi Pasambangi', 'email' => 'suryadi.pasambangi@sig.id'],
            ['id' => 26, 'name' => 'Jasmiati', 'email' => 'jasmiati@sig.id'],
            ['id' => 27, 'name' => 'Irsan ST', 'email' => 'irsan@sig.id'],
            ['id' => 28, 'name' => 'Stevanus Bodro Wibowo', 'email' => 'stevanus.bodro@sig.id'],
            ['id' => 29, 'name' => 'M. Alianto M', 'email' => 'm.alianto@sig.id'],
            ['id' => 30, 'name' => 'Wellem Ariance', 'email' => 'wellem.ariance@sig.id'],
            ['id' => 31, 'name' => 'Ambo Masse', 'email' => 'ambo.masse@sig.id'],
            ['id' => 32, 'name' => 'Capt. Guntur Eko Prasetyo', 'email' => 'guntur.eko.prasetyo@sig.id'],
            ['id' => 33, 'name' => 'Simon Salea', 'email' => 'simon.salea@sig.id'],
            ['id' => 34, 'name' => 'Hakmal Candra', 'email' => 'hakmal.candra@sig.id'],
            ['id' => 35, 'name' => 'Imran', 'email' => 'imran@sig.id'],
            ['id' => 36, 'name' => 'Parlindungan Pardosi', 'email' => 'parlindungan.pardosi@sig.id'],
            ['id' => 37, 'name' => 'Budi Wiyono', 'email' => 'budi.wiyono@sig.id'],
            ['id' => 38, 'name' => 'Mudassir Syam', 'email' => 'mudassir.syam@sig.id'],
            ['id' => 39, 'name' => 'Muhammad Rusdianto HN', 'email' => 'muhammad.rusdianto@sig.id'],
            ['id' => 40, 'name' => 'Abd. Wahid', 'email' => 'abd.wahid5082@sig.id'],

            ['id' => 41, 'name' => 'Nasaruddin', 'email' => 'nasaruddin.5133@sig.id'],
            ['id' => 42, 'name' => 'Wibowo', 'email' => 'wibowo@sig.id'],
            ['id' => 43, 'name' => 'Wahyu A.R.', 'email' => 'wahyu.a@sig.id'],
            ['id' => 44, 'name' => 'Andika Sariy Tandirura', 'email' => 'andika.tandirura@sig.id'],
            ['id' => 45, 'name' => 'Muhammad Fausi', 'email' => 'muhammad.fausi@sig.id'],
            ['id' => 46, 'name' => 'Antonius F.H. Sukma', 'email' => 'antonius.sukma@sig.id'],
            ['id' => 47, 'name' => 'Safruddin Haeruddin', 'email' => 'safruddin.haeruddin@sig.id'],
            ['id' => 48, 'name' => 'Ilyasusanto', 'email' => 'ilyasusanto@sig.id'],

            ['id' => 49, 'name' => 'Al Azhar', 'email' => 'al.azhar@sig.id'],
            ['id' => 50, 'name' => 'Muh. Basri', 'email' => 'muh.basri4911@sig.id'],
            ['id' => 51, 'name' => 'Irwan Saparuddin', 'email' => 'irwan.saparuddin@sig.id'],
            ['id' => 52, 'name' => 'Imam Suyuti', 'email' => 'imam.suyuti@sig.id'],
            ['id' => 53, 'name' => 'Mohammad Andi Yustian', 'email' => 'andi.yustian@sig.id'],
            ['id' => 54, 'name' => 'Arif Budiman', 'email' => 'arif.budiman@sig.id'],
            ['id' => 55, 'name' => 'Muhammad Ageng Anom', 'email' => 'muhammad.ageng@sig.id'],
            ['id' => 56, 'name' => 'H. Alimuddin', 'email' => 'alimuddin.5027@sig.id'],
            ['id' => 57, 'name' => 'Putra Adhi Sumaryanto', 'email' => 'putra.sumaryanto@sig.id'],
            ['id' => 58, 'name' => 'Ezra', 'email' => 'ezra@sig.id'],
            ['id' => 59, 'name' => 'H. Syahruddin', 'email' => 'syahruddin.5064@sig.id'],
            ['id' => 60, 'name' => 'Kaharuddin', 'email' => 'kaharuddin.5292@sig.id'],
            ['id' => 61, 'name' => 'Abd. Salam', 'email' => 'abd.salam5117@sig.id'],
            ['id' => 62, 'name' => 'MZ Sukma Hastika', 'email' => 'sukma.hastika@sig.id'],

            ['id' => 63, 'name' => 'Asriyanto Nasir', 'email' => 'asriyanto.nasir@sig.id'],
            ['id' => 64, 'name' => 'Nani Sri Lestari', 'email' => 'nani.lestari@sig.id'],
            ['id' => 65, 'name' => 'Cendhika Larassayom Esa', 'email' => 'cendhika.esa@sig.id'],
            ['id' => 66, 'name' => 'Ahmad', 'email' => 'ahmad.4924@sig.id'],
            ['id' => 67, 'name' => 'Syaharuddin Ngewa', 'email' => 'syahruddin.ngewa@sig.id'],
            ['id' => 68, 'name' => 'Surahman', 'email' => 'surahman@sig.id'],

            ['id' => 69, 'name' => 'M. Rizal M.', 'email' => 'm.rizal@sig.id'],
            ['id' => 70, 'name' => 'Resti Setianingrum', 'email' => 'resti.setianingrum@sig.id'],
            ['id' => 71, 'name' => 'Agus Firmanto', 'email' => 'agus.firmanto@sig.id'],
            ['id' => 72, 'name' => 'Lukas Tandi', 'email' => 'lukas.tandi@sig.id'],
            ['id' => 73, 'name' => 'Ahmad Zaky Imani', 'email' => 'ahmad.imani@sig.id'],
            ['id' => 74, 'name' => 'Alfian Jais', 'email' => 'alfian.jais@sig.id'],
            ['id' => 75, 'name' => 'M. Yasin', 'email' => 'm.yasin@sig.id'],
            ['id' => 76, 'name' => 'Andi Mayundari', 'email' => 'andi.mayundari@sig.id'],
            ['id' => 77, 'name' => 'Ahmad Zakki Mubarok', 'email' => 'ahmad.zakki@sig.id'],
            ['id' => 78, 'name' => 'Faizal Amir Razak', 'email' => 'faizal.razak@sig.id'],
            ['id' => 79, 'name' => 'Angga Adhitya', 'email' => 'angga.adhitya@sig.id'],
            ['id' => 80, 'name' => 'M. Sahrir', 'email' => 'm.sahrir@sig.id'],
            ['id' => 81, 'name' => 'Syamsupriadi', 'email' => 'syamsupriadi@sig.id'],
            ['id' => 82, 'name' => 'Sjarifuddin Said', 'email' => 'sjarifuddin.said@sig.id'],
            ['id' => 83, 'name' => 'Azis', 'email' => 'azis4881@sig.id'],

            ['id' => 84, 'name' => 'Mathius Rota', 'email' => 'mathius.rota@sig.id'],
            ['id' => 85, 'name' => 'Isak', 'email' => 'isak@sig.id'],
            ['id' => 86, 'name' => 'Rahmat S', 'email' => 'rahmat.s@sig.id'],
            ['id' => 87, 'name' => 'Harianto Marzuki', 'email' => 'harianto.marzuki@sig.id'],
            ['id' => 88, 'name' => 'Helton Yhoni', 'email' => 'helton.yhoni@sig.id'],
            ['id' => 89, 'name' => 'Achmad Firmansjah', 'email' => 'achmad.firmansjah@sig.id'],
            ['id' => 90, 'name' => 'Bungin R', 'email' => 'bungin.r@sig.id'],
            ['id' => 91, 'name' => 'Kardianusti T. Bua', 'email' => 'kardianusti.bua@sig.id'],
            ['id' => 92, 'name' => 'Jamaluddin', 'email' => 'jamaluddin.5094@sig.id'],
            ['id' => 93, 'name' => 'Zulfadli', 'email' => 'zulfadli@sig.id'],
            ['id' => 94, 'name' => 'Wirawan Yusuf', 'email' => 'wirawan.yusuf@sig.id'],
            ['id' => 95, 'name' => 'Sumardi', 'email' => 'sumardi@sig.id'],
            ['id' => 96, 'name' => 'Margiantonius', 'email' => 'margiantonius@sig.id'],
            ['id' => 97, 'name' => 'Rabenka Palesa', 'email' => 'rabenka.palesa@sig.id'],
            ['id' => 98, 'name' => 'Mursalin Tawang', 'email' => 'mursalim.tawang@sig.id'],
            ['id' => 99, 'name' => 'Aszriadi', 'email' => 'aszriadi@sig.id'],

            ['id' => 100, 'name' => 'H. Ferry Wardana', 'email' => 'ferry.wardana@sig.id'],
            ['id' => 101, 'name' => 'Muhammad Zubair Baso', 'email' => 'muhammad.baso@sig.id'],
            ['id' => 102, 'name' => 'Syamsul Bahri', 'email' => 'syamsul.bahri@sig.id'],
            ['id' => 103, 'name' => 'Wijanarko', 'email' => 'wijanarko@sig.id'],
            ['id' => 104, 'name' => 'Syaharuddin', 'email' => 'syaharuddin@sig.id'],
            ['id' => 105, 'name' => 'Roniansyah Malinggi', 'email' => 'roniansyah.malinggi@sig.id'],
            ['id' => 106, 'name' => 'Andi Rahmat', 'email' => 'andi.rahmat@sig.id'],
            ['id' => 107, 'name' => 'Ruben Bondo', 'email' => 'ruben.bondo@sig.id'],
            ['id' => 108, 'name' => 'Dasa Agustriawan', 'email' => 'dasa.agustriawan@sig.id'],
            ['id' => 109, 'name' => 'Lamasi', 'email' => 'lamasi@sig.id'],
            ['id' => 110, 'name' => 'Irfan', 'email' => 'irfan@sig.id'],
            ['id' => 111, 'name' => 'Andi Kasman Saransi', 'email' => 'andi.saransi@sig.id'],
            ['id' => 112, 'name' => 'Muh. Daris Danial', 'email' => 'muh.danial@sig.id'],
        ];

        foreach ($users as $item) {
            User::query()->updateOrCreate(
                ['id' => $item['id']],
                [
                    'name' => $item['name'],
                    'email' => $item['email'],
                    'password' => $password,
                    'role' => User::ROLE_APPROVER,
                    'admin_role' => null,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}