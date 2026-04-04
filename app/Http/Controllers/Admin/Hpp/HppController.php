<?php

namespace App\Http\Controllers\Admin\Hpp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HppController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));

        $dummyRows = collect([
            [
                'id' => 1,
                'order_no' => 'ORD-2026-0012',
                'job_name' => 'Fabrikasi support kiln',
                'kategori' => 'Fabrikasi',
                'lokasi' => 'Workshop',
                'area' => 'Bengkel Mesin',
                'nilai' => 185_000_000,
                'status' => 'in_review',
                'case_code' => 'FAB-WORKSHOP-UNDER250',
                'current_step' => 'SM Pengendali',
                'created_at' => '2026-04-03',
            ],
            [
                'id' => 2,
                'order_no' => 'ORD-2026-0015',
                'job_name' => 'Konstruksi area packing T4',
                'kategori' => 'Konstruksi',
                'lokasi' => 'Dalam',
                'area' => 'T4',
                'nilai' => 325_000_000,
                'status' => 'in_review',
                'case_code' => 'KON-DALAM-ABOVE250',
                'current_step' => 'GM Pengendali',
                'created_at' => '2026-04-03',
            ],
            [
                'id' => 3,
                'order_no' => 'ORD-2026-0020',
                'job_name' => 'Fabrikasi luar area pabrik',
                'kategori' => 'Fabrikasi',
                'lokasi' => 'Luar',
                'area' => 'Luar Area Pabrik',
                'nilai' => 90_000_000,
                'status' => 'approved',
                'case_code' => 'FAB-LUAR-UNDER250',
                'current_step' => 'Selesai',
                'created_at' => '2026-04-02',
            ],
            [
                'id' => 4,
                'order_no' => 'ORD-2026-0024',
                'job_name' => 'Konstruksi luar area pabrik',
                'kategori' => 'Konstruksi',
                'lokasi' => 'Luar',
                'area' => 'Luar Area Pabrik',
                'nilai' => 520_000_000,
                'status' => 'rejected',
                'case_code' => 'KON-LUAR-ABOVE250',
                'current_step' => 'DIROPS',
                'created_at' => '2026-04-01',
            ],
        ]);

        $filtered = $dummyRows
            ->when($search !== '', fn ($collection) => $collection->filter(function ($row) use ($search) {
                $needle = mb_strtolower($search);
                return str_contains(mb_strtolower($row['order_no']), $needle)
                    || str_contains(mb_strtolower($row['job_name']), $needle)
                    || str_contains(mb_strtolower($row['kategori']), $needle)
                    || str_contains(mb_strtolower($row['area']), $needle);
            }))
            ->when($status !== '', fn ($collection) => $collection->where('status', $status))
            ->values();

        return view('admin.hpp.index', [
            'rows' => $filtered,
            'search' => $search,
            'status' => $status,
            'statusOptions' => [
                'draft' => 'Draft',
                'in_review' => 'In Review',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.hpp.create');
    }
}
