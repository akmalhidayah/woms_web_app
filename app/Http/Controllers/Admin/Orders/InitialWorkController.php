<?php

namespace App\Http\Controllers\Admin\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\StoreInitialWorkRequest;
use App\Http\Requests\Admin\Orders\UpdateInitialWorkRequest;
use App\Models\InitialWork;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class InitialWorkController extends Controller
{
    /**
     * Store a newly created initial work.
     */
    public function store(StoreInitialWorkRequest $request, Order $order): RedirectResponse
    {
        abort_unless(Order::priorityPrimaryFor($order->prioritas) === 'emergency', 403);

        if ($order->initialWork) {
            return redirect()
                ->route('admin.orders.index')
                ->with('status', 'Initial Work untuk order ini sudah tersedia.');
        }

        $initialWork = $order->initialWork()->create([
            'nomor_initial_work' => $this->nextDocumentNumber(),
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'kepada_yth' => $request->validated('kepada_yth') ?: 'PT. PRIMA KARYA MANUNGGAL',
            'perihal' => $request->validated('perihal'),
            'tanggal_initial_work' => $request->validated('tanggal_initial_work'),
            'functional_location' => $this->normalizeRowValues($request->validated('functional_location')),
            'scope_pekerjaan' => $this->normalizeRowValues($request->validated('scope_pekerjaan')),
            'qty' => $this->normalizeRowValues($request->validated('qty')),
            'stn' => $this->normalizeRowValues($request->validated('stn')),
            'keterangan' => $this->normalizeOptionalRowValues($request->validated('keterangan', []), count($request->validated('functional_location'))),
            'keterangan_pekerjaan' => $request->validated('keterangan_pekerjaan') ?: null,
            'created_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('admin.orders.index')
            ->with('status', sprintf('Initial Work untuk order %s berhasil dibuat.', $initialWork->nomor_order));
    }

    /**
     * Update the specified initial work.
     */
    public function update(UpdateInitialWorkRequest $request, Order $order, InitialWork $initialWork): RedirectResponse
    {
        abort_unless($initialWork->order_id === $order->id, 404);

        $initialWork->update([
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'kepada_yth' => $request->validated('kepada_yth') ?: 'PT. PRIMA KARYA MANUNGGAL',
            'perihal' => $request->validated('perihal'),
            'tanggal_initial_work' => $request->validated('tanggal_initial_work'),
            'functional_location' => $this->normalizeRowValues($request->validated('functional_location')),
            'scope_pekerjaan' => $this->normalizeRowValues($request->validated('scope_pekerjaan')),
            'qty' => $this->normalizeRowValues($request->validated('qty')),
            'stn' => $this->normalizeRowValues($request->validated('stn')),
            'keterangan' => $this->normalizeOptionalRowValues($request->validated('keterangan', []), count($request->validated('functional_location'))),
            'keterangan_pekerjaan' => $request->validated('keterangan_pekerjaan') ?: null,
        ]);

        return redirect()
            ->route('admin.orders.index')
            ->with('status', sprintf('Initial Work untuk order %s berhasil diperbarui.', $initialWork->nomor_order));
    }

    /**
     * Stream the initial work as PDF.
     */
    public function pdf(Order $order, InitialWork $initialWork): Response
    {
        abort_unless($initialWork->order_id === $order->id, 404);

        $pdf = Pdf::loadView('admin.orders.initial-work-pdf', [
            'order' => $order,
            'initialWork' => $initialWork,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('initial-work-'.$order->nomor_order.'.pdf');
    }

    /**
     * Build the next document number preview.
     */
    public static function previewDocumentNumber(): string
    {
        return app(self::class)->nextDocumentNumber();
    }

    /**
     * @param  list<mixed>  $values
     * @return list<string>
     */
    private function normalizeRowValues(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => trim((string) $value))
            ->values()
            ->all();
    }

    /**
     * @param  list<mixed>  $values
     * @return list<string>
     */
    private function normalizeOptionalRowValues(array $values, int $targetCount): array
    {
        return collect(range(0, max(0, $targetCount - 1)))
            ->map(fn ($index) => trim((string) ($values[$index] ?? '')))
            ->values()
            ->all();
    }

    /**
     * Generate the next Initial Work document number.
     */
    private function nextDocumentNumber(): string
    {
        $now = now();
        $month = $now->format('m');
        $year = $now->format('Y');

        $lastInitialWork = InitialWork::query()
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->latest('created_at')
            ->first();

        $nextNumber = '001';

        if ($lastInitialWork && preg_match('/^(\d{3})\//', $lastInitialWork->nomor_initial_work, $matches)) {
            $nextNumber = str_pad(((int) $matches[1]) + 1, 3, '0', STR_PAD_LEFT);
        }

        return sprintf('%s/IW/25.10/%s-%s', $nextNumber, $month, $year);
    }
}
