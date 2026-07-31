<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Inventory\StoreInventoryMasterDataRequest;
use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryLocation;
use App\Models\Inventory\InventoryRequestType;
use App\Models\Inventory\InventorySubcategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function index(): View
    {
        return view('admin.inventory.master-data.index', [
            'inventoryReady' => true,
            'categories' => InventoryCategory::query()->withCount(['subcategories', 'items'])->orderBy('name')->get(),
            'subcategories' => InventorySubcategory::query()->with(['category'])->withCount('items')->orderBy('name')->get(),
            'locations' => InventoryLocation::query()->withCount('items')->orderBy('name')->get(),
            'requestTypes' => InventoryRequestType::query()->withCount('transactions')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreInventoryMasterDataRequest $request, string $type): RedirectResponse
    {
        $this->modelClass($type)::query()->create($request->validated());

        return back()->with('success', 'Master data berhasil ditambahkan.');
    }

    public function update(StoreInventoryMasterDataRequest $request, string $type, int $id): RedirectResponse
    {
        $this->find($type, $id)->update($request->validated());

        return back()->with('success', 'Master data berhasil diperbarui.');
    }

    public function status(string $type, int $id): RedirectResponse
    {
        $model = $this->find($type, $id);
        $model->update(['is_active' => ! $model->is_active]);

        return back()->with('success', 'Status master data berhasil diperbarui.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $model = $this->find($type, $id);
        $used = match ($type) {
            'categories' => $model->subcategories()->exists() || $model->items()->exists(),
            'subcategories', 'locations' => $model->items()->exists(),
            'request-types' => $model->transactions()->exists(),
        };

        if ($used) {
            return back()->withErrors(['master_data' => 'Data masih digunakan. Nonaktifkan data ini sebagai pengganti penghapusan.']);
        }
        $model->delete();

        return back()->with('success', 'Master data berhasil dihapus.');
    }

    private function find(string $type, int $id): Model
    {
        return $this->modelClass($type)::query()->findOrFail($id);
    }

    /** @return class-string<Model> */
    private function modelClass(string $type): string
    {
        return match ($type) {
            'categories' => InventoryCategory::class,
            'subcategories' => InventorySubcategory::class,
            'locations' => InventoryLocation::class,
            'request-types' => InventoryRequestType::class,
            default => abort(404),
        };
    }
}
