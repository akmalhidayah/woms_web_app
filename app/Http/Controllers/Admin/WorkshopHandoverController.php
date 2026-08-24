<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class WorkshopHandoverController extends Controller
{
    public function __invoke(): View
    {
        // Data sementara untuk mematangkan tampilan sebelum alur serah terima ditentukan.
        $handovers = collect([
            [
                'order_number' => '17049907',
                'notification_number' => '300001960812',
                'work_name' => '408RE01M2.1 FABRIKASI PORTAL KABEL',
                'unit' => 'Elins Maintenance 2',
                'section' => 'Line 4/5 RKC Electrical Maint',
                'recipient' => 'Muh. Akbar',
                'date' => '25-08-2026',
                'status' => 'Menunggu Serah Terima',
                'tone' => 'amber',
            ],
            [
                'order_number' => 'MANUAL-BENGKEL-000206',
                'notification_number' => '-',
                'work_name' => 'FABRIKASI CEKHOLE BIN PASIR BESI RAWMILL 5',
                'unit' => 'Clinker Production',
                'section' => 'Line 4 RKC Operation',
                'recipient' => 'Haerullah',
                'date' => '24-08-2026',
                'status' => 'Dalam Proses',
                'tone' => 'blue',
            ],
            [
                'order_number' => '17049873',
                'notification_number' => '300001958640',
                'work_name' => '417-CC01-PERBAIKAN PEMBUANGAN DUST TRAP OVH 2027',
                'unit' => 'Clinker Production',
                'section' => 'Line 4 RKC Operation',
                'recipient' => 'Herman R.',
                'date' => '22-08-2026',
                'status' => 'Selesai',
                'tone' => 'emerald',
            ],
        ]);

        return view('admin.workshop-handover.index', compact('handovers'));
    }
}
