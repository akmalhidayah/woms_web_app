<?php

namespace App\Http\Controllers\Admin\Orders;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\StoreOrderScopeOfWorkRequest;
use App\Http\Requests\Admin\Orders\UpdateOrderScopeOfWorkRequest;
use App\Models\Order;
use App\Models\OrderScopeOfWork;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class OrderScopeOfWorkController extends Controller
{
    /**
     * Store a newly created scope of work.
     */
    public function store(StoreOrderScopeOfWorkRequest $request, Order $order): RedirectResponse
    {
        $attributes = [
            'nama_penginput' => $request->validated('nama_penginput'),
            'tanggal_dokumen' => $request->validated('tanggal_dokumen'),
            'tanggal_pemakaian' => $request->validated('tanggal_pemakaian'),
            'scope_items' => $this->scopeItemsFromRequest($request->validated()),
            'catatan' => $request->validated('catatan'),
            'tanda_tangan' => $request->validated('tanda_tangan'),
        ];

        $scopeOfWork = $order->scopeOfWork()->first();

        if ($scopeOfWork) {
            $scopeOfWork->update($attributes);
            $message = 'Scope of Work berhasil diperbarui.';
        } else {
            $order->scopeOfWork()->create([
                ...$attributes,
                'created_by' => $request->user()?->id,
            ]);
            $message = 'Scope of Work berhasil disimpan.';
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', $message);
    }

    /**
     * Update the specified scope of work.
     */
    public function update(UpdateOrderScopeOfWorkRequest $request, Order $order, OrderScopeOfWork $scopeOfWork): RedirectResponse
    {
        abort_unless($scopeOfWork->order_id === $order->id, 404);

        $scopeOfWork->update([
            'nama_penginput' => $request->validated('nama_penginput'),
            'tanggal_dokumen' => $request->validated('tanggal_dokumen'),
            'tanggal_pemakaian' => $request->validated('tanggal_pemakaian'),
            'scope_items' => $this->scopeItemsFromRequest($request->validated()),
            'catatan' => $request->validated('catatan'),
            'tanda_tangan' => $request->validated('tanda_tangan'),
        ]);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'Scope of Work berhasil diperbarui.');
    }

    /**
     * Stream the scope of work as PDF in the browser.
     */
    public function pdf(Order $order, OrderScopeOfWork $scopeOfWork): Response
    {
        abort_unless($scopeOfWork->order_id === $order->id, 404);

        $scopeOfWork->loadMissing('creator');
        $order->loadMissing('creator');
        $signaturePath = null;

        if ($scopeOfWork->tanda_tangan && str_starts_with($scopeOfWork->tanda_tangan, 'data:image')) {
            $signatureDirectory = storage_path('app/public/signatures');

            if (! File::exists($signatureDirectory)) {
                File::makeDirectory($signatureDirectory, 0755, true);
            }

            $imageData = explode(',', $scopeOfWork->tanda_tangan)[1] ?? null;

            if ($imageData) {
                $signaturePath = $signatureDirectory.DIRECTORY_SEPARATOR.'scope-of-work-'.$scopeOfWork->id.'.png';
                file_put_contents($signaturePath, base64_decode($imageData));
            }
        }

        $pdf = Pdf::loadView('admin.orders.scope-of-work-pdf', [
            'order' => $order,
            'scopeOfWork' => $scopeOfWork,
            'scopeItems' => $scopeOfWork->scope_items ?? [],
            'signaturePath' => $signaturePath,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('scope-of-work-'.$order->nomor_order.'.pdf');
    }

    /**
     * Build the scope items payload from request data.
     *
     * @param  array<string, mixed>  $validated
     * @return array<int, array<string, string|null>>
     */
    private function scopeItemsFromRequest(array $validated): array
    {
        $items = [];
        $keterangan = $validated['keterangan'] ?? [];

        foreach ($validated['scope_pekerjaan'] as $index => $scopePekerjaan) {
            $items[] = [
                'scope_pekerjaan' => $scopePekerjaan,
                'qty' => $validated['qty'][$index] ?? '',
                'satuan' => $validated['satuan'][$index] ?? '',
                'keterangan' => $keterangan[$index] ?? null,
            ];
        }

        return $items;
    }
}
