<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Exceptions\Inventory\InactiveInventoryItemException;
use App\Exceptions\Inventory\InvalidInventoryActorException;
use App\Exceptions\Inventory\InvalidStockQuantityException;
use App\Exceptions\Inventory\InventoryStockOverflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Inventory\StoreInventoryStockInRequest;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryTransaction;
use App\Models\User;
use App\Services\Inventory\InventoryStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StockInController extends Controller
{
    public function index(Request $request): View
    {
        $inventoryReady = Schema::hasTable('inventory_transactions');
        $items = collect();
        $transactions = $inventoryReady
            ? InventoryTransaction::query()->with(['item', 'womsUser'])
                ->where('transaction_type', InventoryStockService::TYPE_STOCK_IN)
                ->when($request->filled('search'), function ($query) use ($request): void {
                    $search = trim((string) $request->string('search'));
                    $query->where(function ($query) use ($search): void {
                        $query->where('transaction_number', 'like', "%{$search}%")
                            ->orWhere('item_uid_snapshot', 'like', "%{$search}%")
                            ->orWhere('item_name_snapshot', 'like', "%{$search}%")
                            ->orWhere('reference_number', 'like', "%{$search}%");
                    });
                })
                ->when($request->integer('item_id'), fn ($query, int $itemId) => $query->where('inventory_item_id', $itemId))
                ->when($request->date_from, fn ($query, string $date) => $query->whereDate('transaction_at', '>=', $date))
                ->when($request->date_to, fn ($query, string $date) => $query->whereDate('transaction_at', '<=', $date))
                ->latest('transaction_at')
                ->paginate(20)
                ->withQueryString()
            : collect();

        if ($inventoryReady) {
            $items = InventoryItem::query()->where('is_active', true)->orderBy('name')->get(['id', 'uid', 'name']);
        }

        return view('admin.inventory.stock-in.index', compact('inventoryReady', 'transactions', 'items'));
    }

    public function create(Request $request): View
    {
        $inventoryReady = true;
        $items = InventoryItem::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'uid', 'name', 'current_stock', 'unit']);
        $selectedItemId = $request->integer('item_id') ?: null;

        return view('admin.inventory.stock-in.create', compact('inventoryReady', 'items', 'selectedItemId'));
    }

    public function store(
        StoreInventoryStockInRequest $request,
        InventoryStockService $stockService,
    ): RedirectResponse {
        $validated = $request->validated();
        $item = InventoryItem::query()
            ->where('is_active', true)
            ->findOrFail($validated['inventory_item_id']);
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        try {
            $transaction = $stockService->stockIn($item, $validated['quantity'], $actor, [
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
        } catch (InactiveInventoryItemException|InvalidInventoryActorException|InvalidStockQuantityException|InventoryStockOverflowException $exception) {
            return back()->withInput()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.inventory.stock-in.index')
            ->with('success', 'Stok barang berhasil ditambahkan. Nomor transaksi: '.$transaction->transaction_number);
    }
}
