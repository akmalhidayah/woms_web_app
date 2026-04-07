<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LhppBast;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LhppController extends Controller
{
    public function updateQualityControl(Request $request, LhppBast $lhpp)
    {
        try {
            $validated = $request->validate([
                'quality_control_status' => ['required', 'in:pending,approved,rejected'],
            ]);

            $lhpp->quality_control_status = $validated['quality_control_status'];
            $lhpp->updated_by = $request->user()?->id;
            $lhpp->save();

            return redirect()
                ->route('admin.lhpp.index', $request->only('search', 'page'))
                ->with('status', sprintf('Quality control untuk order %s berhasil diperbarui.', $lhpp->nomor_order));
        } catch (Throwable $exception) {
            Log::error('Failed to update admin BAST quality control.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'lhpp_id' => $lhpp->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return back()->withErrors([
                'quality_control_status' => 'Terjadi kesalahan saat memperbarui quality control BAST.',
            ]);
        }
    }

    public function index(Request $request): View
    {
        try {
            $search = trim((string) $request->string('search'));

            $lhpps = LhppBast::query()
                ->with([
                    'order:id,nomor_order,unit_kerja,seksi',
                    'purchaseOrder:id,order_id,purchase_order_number',
                ])
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($builder) use ($search): void {
                        $builder
                            ->where('nomor_order', 'like', "%{$search}%")
                            ->orWhere('purchase_order_number', 'like', "%{$search}%")
                            ->orWhere('unit_kerja', 'like', "%{$search}%")
                            ->orWhere('seksi', 'like', "%{$search}%")
                            ->orWhere('deskripsi_pekerjaan', 'like', "%{$search}%");
                    });
                })
                ->latest('id')
                ->paginate(10)
                ->withQueryString();

            return view('admin.lhpp.index', [
                'search' => $search,
                'lhpps' => $lhpps,
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to load admin BAST index page.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat memuat halaman BAST admin.');
        }
    }

    public function pdf(Request $request, LhppBast $lhpp)
    {
        try {
            $pdf = Pdf::loadView('pkm.lhpp.pdf', [
                'lhpp' => $lhpp,
                'materialItems' => collect($lhpp->material_items ?? []),
                'serviceItems' => collect($lhpp->service_items ?? []),
            ])->setPaper('a4', 'portrait');

            return $pdf->stream('bast-termin-1-'.$lhpp->nomor_order.'.pdf');
        } catch (Throwable $exception) {
            Log::error('Failed to generate admin BAST PDF.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'lhpp_id' => $lhpp->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat membuat PDF BAST admin.');
        }
    }
}
