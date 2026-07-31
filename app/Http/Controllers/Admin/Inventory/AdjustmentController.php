<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Inventory\StoreInventoryAdjustmentRequest;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryTransaction;
use App\Models\User;
use App\Services\Inventory\InventoryStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdjustmentController extends Controller
{
    public function index(): View
    {
        $inventoryReady = Schema::hasTable('inventory_transactions');
        $transactions = $inventoryReady
            ? InventoryTransaction::query()->with(['item', 'womsUser'])
                ->whereIn('transaction_type', ['adjustment_in', 'adjustment_out'])
                ->latest('transaction_at')->limit(10)->get()
            : collect();

        $items = $inventoryReady ? InventoryItem::query()->where('is_active', true)->orderBy('name')->get() : collect();

        return view('admin.inventory.adjustments.index', compact('inventoryReady', 'transactions', 'items'));
    }

    public function store(StoreInventoryAdjustmentRequest $request, InventoryStockService $service): RedirectResponse
    {
        $item = InventoryItem::query()->findOrFail($request->integer('inventory_item_id'));
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        try {
            $context = ['reference_number' => $request->string('reference_number')->toString() ?: null];
            $transaction = $request->string('adjustment_type')->toString() === InventoryStockService::TYPE_ADJUSTMENT_IN
                ? $service->adjustmentIn($item, $request->integer('quantity'), $actor, $request->string('reason')->toString(), $context)
                : $service->adjustmentOut($item, $request->integer('quantity'), $actor, $request->string('reason')->toString(), $context);
        } catch (\DomainException|\InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return back()->with('success', 'Koreksi stok berhasil. Nomor transaksi: '.$transaction->transaction_number);
    }
}
