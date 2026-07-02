<?php

namespace App\Http\Controllers\Admin\Orders;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\StoreOrderScopeOfWorkRequest;
use App\Http\Requests\Admin\Orders\UpdateOrderScopeOfWorkRequest;
use App\Models\Order;
use App\Models\OrderScopeOfWork;
use App\Support\ScopeOfWorkPdfPresenter;
use App\Support\SignatureImageStorage;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class OrderScopeOfWorkController extends Controller
{
    /**
     * Store a newly created scope of work.
     */
    public function store(StoreOrderScopeOfWorkRequest $request, Order $order): RedirectResponse
    {
        $scopeOfWork = $order->scopeOfWork()->first();
        $attributes = [
            'nama_penginput' => $request->validated('nama_penginput'),
            'tanggal_dokumen' => $request->validated('tanggal_dokumen'),
            'tanggal_pemakaian' => $request->validated('tanggal_pemakaian'),
            'scope_items' => $this->scopeItemsFromRequest($request->validated()),
            'catatan' => $request->validated('catatan'),
            'tanda_tangan' => $this->storeSignature($request, $order, $scopeOfWork?->tanda_tangan),
        ];

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
        abort_unless((int) $scopeOfWork->order_id === (int) $order->getKey(), 404);

        $scopeOfWork->update([
            'nama_penginput' => $request->validated('nama_penginput'),
            'tanggal_dokumen' => $request->validated('tanggal_dokumen'),
            'tanggal_pemakaian' => $request->validated('tanggal_pemakaian'),
            'scope_items' => $this->scopeItemsFromRequest($request->validated()),
            'catatan' => $request->validated('catatan'),
            'tanda_tangan' => $this->storeSignature($request, $order, $scopeOfWork->tanda_tangan),
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
        abort_unless((int) $scopeOfWork->order_id === (int) $order->getKey(), 404);

        $scopeOfWork->loadMissing('creator');
        $order->loadMissing([
            'creator',
            'initialWork.outlineAgreement.unitWork',
            'latestHpp.outlineAgreement.unitWork',
        ]);
        $presenter = app(ScopeOfWorkPdfPresenter::class);

        $pdf = Pdf::loadView('admin.orders.scope-of-work-pdf', [
            'order' => $order,
            'scopeOfWork' => $scopeOfWork,
            'scopeItems' => $scopeOfWork->scope_items ?? [],
            'signaturePath' => SignatureImageStorage::imageSource($scopeOfWork->tanda_tangan),
            'creatorName' => $presenter->creatorName($scopeOfWork),
            'creatorUnitLabel' => $presenter->creatorUnitLabel($order),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('scope-of-work-'.$order->nomor_order.'.pdf');
    }

    private function storeSignature(StoreOrderScopeOfWorkRequest|UpdateOrderScopeOfWorkRequest $request, Order $order, ?string $existingSignature): ?string
    {
        if ($request->hasFile('tanda_tangan_file') || filled($request->input('tanda_tangan'))) {
            return SignatureImageStorage::storeFromRequest(
                $request,
                'signatures',
                'scope-of-work-'.$order->id,
                'tanda_tangan_file',
                'tanda_tangan',
            );
        }

        return $existingSignature;
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
