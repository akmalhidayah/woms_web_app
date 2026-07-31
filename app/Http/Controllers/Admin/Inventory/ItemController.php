<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Inventory\StoreInventoryItemRequest;
use App\Http\Requests\Admin\Inventory\UpdateInventoryItemRequest;
use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryLocation;
use App\Models\Inventory\InventorySubcategory;
use App\Models\User;
use App\Services\Inventory\InventoryStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class ItemController extends Controller
{
    public function index(Request $request): View
    {
        $query = InventoryItem::query()->with(['category', 'subcategory', 'location']);
        if ($request->string('status')->toString() === 'archived') {
            $query->onlyTrashed();
        } else {
            $query
                ->when($request->string('status')->toString() === 'active', fn ($query) => $query->where('is_active', true))
                ->when($request->string('status')->toString() === 'inactive', fn ($query) => $query->where('is_active', false))
                ->when($request->string('status')->toString() === 'low', fn ($query) => $query->where('current_stock', '>', 0)->whereColumn('current_stock', '<=', 'minimum_stock'))
                ->when($request->string('status')->toString() === 'out', fn ($query) => $query->where('current_stock', 0));
        }

        $items = $query
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->string('search'));
                $query->where(fn ($query) => $query->where('uid', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
            })
            ->when(in_array($request->string('item_type')->toString(), ['consumable', 'equipment'], true), fn ($query) => $query->where('item_type', $request->string('item_type')->toString()))
            ->when($request->integer('category'), fn ($query, int $id) => $query->where('inventory_category_id', $id))
            ->when($request->integer('location'), fn ($query, int $id) => $query->where('inventory_location_id', $id))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.inventory.items.index', [
            'inventoryReady' => true,
            'items' => $items,
            'categories' => InventoryCategory::query()->orderBy('name')->get(['id', 'name']),
            'locations' => InventoryLocation::query()->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function create(): View
    {
        return $this->formView(new InventoryItem(['unit' => 'EA', 'minimum_stock' => 0, 'is_active' => true]), 'create');
    }

    public function store(StoreInventoryItemRequest $request, InventoryStockService $stockService): RedirectResponse
    {
        $data = $request->safe()->except(['opening_stock', 'image']);
        $newPath = $this->storeImage($request);

        try {
            $item = DB::transaction(function () use ($data, $newPath, $request, $stockService): InventoryItem {
                $item = InventoryItem::query()->create([
                    ...$data,
                    'image_disk' => 'local',
                    'image_path' => $newPath,
                ]);

                if ($request->integer('opening_stock') > 0) {
                    $actor = $request->user();
                    abort_unless($actor instanceof User, 403);
                    $stockService->createOpeningBalance($item, $request->integer('opening_stock'), $actor);
                }

                return $item;
            });
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }
            throw $exception;
        }

        return redirect()->route('admin.inventory.items.edit', $item)->with('success', 'Barang berhasil dibuat.');
    }

    public function edit(InventoryItem $inventoryItem): View
    {
        return $this->formView($inventoryItem, 'edit');
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $oldDisk = $inventoryItem->image_disk;
        $oldPath = $inventoryItem->image_path;
        $newPath = $this->storeImage($request);

        try {
            DB::transaction(function () use ($inventoryItem, $data, $newPath, $request): void {
                $inventoryItem->update([
                    ...$data,
                    'image_disk' => $newPath ? 'local' : $inventoryItem->image_disk,
                    'image_path' => $newPath ?: ($request->boolean('remove_image') ? null : $inventoryItem->image_path),
                ]);
            });
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }
            throw $exception;
        }

        if (($newPath || $request->boolean('remove_image')) && $oldPath && $this->safePath($oldPath)) {
            Storage::disk($oldDisk)->delete($oldPath);
        }

        return back()->with('success', 'Barang berhasil diperbarui.');
    }

    public function destroy(InventoryItem $inventoryItem): RedirectResponse
    {
        if ((int) $inventoryItem->current_stock > 0) {
            return back()->withErrors(['archive' => 'Barang masih memiliki stok. Lakukan koreksi stok keluar terlebih dahulu.']);
        }
        DB::transaction(function () use ($inventoryItem): void {
            $inventoryItem->forceFill(['is_active' => false])->save();
            $inventoryItem->delete();
        });

        return back()->with('success', 'Barang berhasil diarsipkan.');
    }

    public function restore(int $inventoryItem): RedirectResponse
    {
        $item = InventoryItem::onlyTrashed()->findOrFail($inventoryItem);
        DB::transaction(function () use ($item): void {
            $item->restore();
            $item->forceFill(['is_active' => false])->save();
        });

        return redirect()->route('admin.inventory.items.edit', $item)->with('success', 'Barang dipulihkan dalam status nonaktif.');
    }

    public function image(InventoryItem $inventoryItem): Response
    {
        abort_unless($inventoryItem->image_path && $this->safePath($inventoryItem->image_path), 404);
        $disk = Storage::disk($inventoryItem->image_disk);
        abort_unless($disk->exists($inventoryItem->image_path), 404);

        return response($disk->get($inventoryItem->image_path), 200, [
            'Content-Type' => $disk->mimeType($inventoryItem->image_path) ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function formView(InventoryItem $item, string $mode): View
    {
        return view('admin.inventory.items.form', [
            'item' => $item,
            'mode' => $mode,
            'inventoryReady' => true,
            'categories' => InventoryCategory::query()->where(fn ($query) => $query->where('is_active', true)->when($item->inventory_category_id, fn ($query, $id) => $query->orWhere('id', $id)))->orderBy('name')->get(),
            'subcategories' => InventorySubcategory::query()->with('category')->where(fn ($query) => $query->where('is_active', true)->when($item->inventory_subcategory_id, fn ($query, $id) => $query->orWhere('id', $id)))->orderBy('name')->get(),
            'locations' => InventoryLocation::query()->where(fn ($query) => $query->where('is_active', true)->when($item->inventory_location_id, fn ($query, $id) => $query->orWhere('id', $id)))->orderBy('name')->get(),
        ]);
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }
        $file = $request->file('image');
        $extension = strtolower($file->guessExtension() ?: $file->extension());

        return $file->storeAs('inventory/items/'.now()->format('Y/m'), Str::ulid().'.'.$extension, 'local');
    }

    private function safePath(string $path): bool
    {
        return ! str_starts_with($path, '/') && ! str_contains($path, '..') && ! str_contains($path, "\0");
    }
}
