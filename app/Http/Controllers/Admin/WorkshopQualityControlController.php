<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class WorkshopQualityControlController extends Controller
{
    public function __invoke(): View
    {
        // Data sementara untuk mematangkan tampilan sebelum sumber data QC disepakati.
        $qualityControls = collect([
            [
                'order_number' => '17049907',
                'notification_number' => '300001960812',
                'work_name' => '408RE01M2.1 FABRIKASI PORTAL KABEL',
                'unit' => 'Elins Maintenance 2',
                'section' => 'Line 4/5 RKC Electrical Maint',
                'type' => 'Fabrikasi',
                'inspector' => 'Herman R.',
                'date' => '24-08-2026',
                'status' => 'Perlu Pemeriksaan',
                'tone' => 'amber',
            ],
            [
                'order_number' => 'MANUAL-BENGKEL-000206',
                'notification_number' => '-',
                'work_name' => 'FABRIKASI CEKHOLE BIN PASIR BESI RAWMILL 5',
                'unit' => 'Clinker Production',
                'section' => 'Line 4 RKC Operation',
                'type' => 'Fabrikasi',
                'inspector' => 'Haerullah',
                'date' => '23-08-2026',
                'status' => 'Dalam Pemeriksaan',
                'tone' => 'blue',
            ],
            [
                'order_number' => 'MANUAL-BENGKEL-000205',
                'notification_number' => '-',
                'work_name' => 'MODIFIKASI MESIN LIPAT HYDROLIK BMS',
                'unit' => 'Workshop',
                'section' => 'Machine Workshop',
                'type' => 'Refurbish',
                'inspector' => 'Sudirman. MJ',
                'date' => '22-08-2026',
                'status' => 'Menunggu Approval',
                'tone' => 'violet',
            ],
            [
                'order_number' => '17049873',
                'notification_number' => '300001958640',
                'work_name' => '417-CC01-PERBAIKAN PEMBUANGAN DUST TRAP OVH 2027',
                'unit' => 'Clinker Production',
                'section' => 'Line 4 RKC Operation',
                'type' => 'Refurbish',
                'inspector' => 'Akbar',
                'date' => '21-08-2026',
                'status' => 'Selesai',
                'tone' => 'emerald',
            ],
        ]);

        return view('admin.workshop-quality-control.index', [
            'qualityControls' => $qualityControls,
        ]);
    }
}
