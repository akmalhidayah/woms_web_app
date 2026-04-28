<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;
use App\Models\VendorWorkType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StructureOrganizationController extends Controller
{
    /**
     * Display the structure organization page.
     */
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->string('q')),
            'department' => (string) $request->string('department'),
        ];

        $departments = Department::query()
            ->with([
                'generalManager',
                'units' => fn ($query) => $query
                    ->with(['seniorManager', 'sections.manager'])
                    ->orderBy('name'),
            ])
            ->when($filters['department'] !== '', fn ($query) => $query->where('id', (int) $filters['department']))
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $query->where(function ($departmentQuery) use ($filters) {
                    $departmentQuery
                        ->where('name', 'like', '%'.$filters['q'].'%')
                        ->orWhereHas('units', function ($unitQuery) use ($filters) {
                            $unitQuery
                                ->where('name', 'like', '%'.$filters['q'].'%')
                                ->orWhereHas('sections', fn ($sectionQuery) => $sectionQuery->where('name', 'like', '%'.$filters['q'].'%'));
                        });
                });
            })
            ->orderBy('name')
            ->get();

        $hppApprovalSetting = \App\Models\HppApprovalSetting::query()
            ->with([
                'plannerControl:id,name',
                'counterPartUnit:id,name,senior_manager_id',
                'counterPartUnit.seniorManager:id,name',
                'counterPartSection:id,unit_work_id,name,manager_id',
                'counterPartSection.manager:id,name',
                'dirops:id,name',
            ])
            ->firstOrCreate([]);

        $unitWorks = UnitWork::query()
            ->with(['sections.manager'])
            ->orderBy('name')
            ->get(['id', 'name', 'senior_manager_id']);

        return view('admin.structure.index', [
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'structureDepartments' => $departments,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'hppApprovalSetting' => $hppApprovalSetting,
            'unitWorks' => $unitWorks,
            'sectionOptions' => UnitWorkSection::query()
                ->with('unitWork:id,name')
                ->orderBy('name')
                ->get(['id', 'unit_work_id', 'name', 'manager_id']),
            'vendorWorkTypes' => VendorWorkType::query()
                ->with(['vendorSections.manager:id,name'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storeVendorStructure(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'unique:vendor_work_types,name'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.name' => ['required', 'string', 'max:255'],
            'sections.*.manager_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('admin.structure.index')
                ->withErrors($validator, 'vendorStructure')
                ->withInput();
        }

        $validated = $validator->validated();

        $vendorWorkType = VendorWorkType::create([
            'name' => trim($validated['name']),
        ]);

        $this->syncVendorSections($vendorWorkType, $validated['sections']);

        return redirect()
            ->route('admin.structure.index')
            ->with('success', 'Struktur vendor berhasil ditambahkan.');
    }

    public function updateVendorStructure(Request $request, VendorWorkType $vendorWorkType): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', Rule::unique('vendor_work_types', 'name')->ignore($vendorWorkType->id)],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.name' => ['required', 'string', 'max:255'],
            'sections.*.manager_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('admin.structure.index')
                ->withErrors($validator, 'vendorStructure')
                ->withInput();
        }

        $validated = $validator->validated();

        $vendorWorkType->update([
            'name' => trim($validated['name']),
        ]);

        $this->syncVendorSections($vendorWorkType, $validated['sections']);

        return redirect()
            ->route('admin.structure.index')
            ->with('success', 'Struktur vendor berhasil diperbarui.');
    }

    public function destroyVendorStructure(VendorWorkType $vendorWorkType): RedirectResponse
    {
        $vendorWorkType->delete();

        return redirect()
            ->route('admin.structure.index')
            ->with('success', 'Struktur vendor berhasil dihapus.');
    }

    /**
     * Store a newly created structure.
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'department_name_new' => ['nullable', 'string', 'max:255', 'unique:departments,name'],
            'general_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'unit_name' => ['required', 'string', 'max:255', 'unique:unit_works,name'],
            'senior_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'sections' => ['nullable', 'array'],
            'sections.*.name' => ['required_with:sections', 'string', 'max:255'],
            'sections.*.manager_id' => ['nullable', 'integer', 'exists:users,id'],
        ])->after(function ($validator) use ($request) {
            if (! $request->filled('department_id') && ! $request->filled('department_name_new')) {
                $validator->errors()->add('department_id', 'Pilih departemen atau buat departemen baru.');
            }
        });

        if ($validator->fails()) {
            return redirect()
                ->route('admin.structure.index')
                ->withErrors($validator)
                ->withInput()
                ->with('structure_modal', [
                    'mode' => 'create',
                    'action' => route('admin.structure.store'),
                ]);
        }

        $validated = $validator->validated();

        DB::transaction(function () use ($validated) {
            $department = $this->resolveDepartmentForStructure($validated);

            $department->update([
                'general_manager_id' => $validated['general_manager_id'] ?? null,
            ]);

            $unit = UnitWork::create([
                'department_id' => $department->id,
                'name' => trim($validated['unit_name']),
                'senior_manager_id' => $validated['senior_manager_id'] ?? null,
            ]);

            foreach ($validated['sections'] ?? [] as $section) {
                $unit->sections()->create([
                    'name' => trim((string) $section['name']),
                    'manager_id' => $section['manager_id'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('admin.structure.index')
            ->with('success', 'Struktur organisasi berhasil ditambahkan.');
    }

    /**
     * Update the specified structure.
     */
    public function update(Request $request, UnitWork $unitWork): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'department_name_new' => ['nullable', 'string', 'max:255', Rule::unique('departments', 'name')],
            'general_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'unit_name' => ['required', 'string', 'max:255', Rule::unique('unit_works', 'name')->ignore($unitWork->id)],
            'senior_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'sections' => ['nullable', 'array'],
            'sections.*.name' => ['required_with:sections', 'string', 'max:255'],
            'sections.*.manager_id' => ['nullable', 'integer', 'exists:users,id'],
        ])->after(function ($validator) use ($request) {
            if (! $request->filled('department_id') && ! $request->filled('department_name_new')) {
                $validator->errors()->add('department_id', 'Pilih departemen atau buat departemen baru.');
            }
        });

        if ($validator->fails()) {
            return redirect()
                ->route('admin.structure.index')
                ->withErrors($validator)
                ->withInput()
                ->with('structure_modal', [
                    'mode' => 'edit',
                    'action' => route('admin.structure.update', $unitWork),
                ]);
        }

        $validated = $validator->validated();

        DB::transaction(function () use ($validated, $unitWork) {
            $department = $this->resolveDepartmentForStructure($validated);

            $department->update([
                'general_manager_id' => $validated['general_manager_id'] ?? null,
            ]);

            $unitWork->update([
                'department_id' => $department->id,
                'name' => trim($validated['unit_name']),
                'senior_manager_id' => $validated['senior_manager_id'] ?? null,
            ]);

            $unitWork->sections()->delete();

            foreach ($validated['sections'] ?? [] as $section) {
                $unitWork->sections()->create([
                    'name' => trim((string) $section['name']),
                    'manager_id' => $section['manager_id'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('admin.structure.index')
            ->with('success', 'Struktur organisasi berhasil diperbarui.');
    }

    /**
     * Remove the specified structure.
     */
    public function destroy(UnitWork $unitWork): RedirectResponse
    {
        $unitWork->delete();

        return redirect()
            ->route('admin.structure.index')
            ->with('success', 'Struktur organisasi berhasil dihapus.');
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($department->id)],
            'general_manager_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('admin.structure.index')
                ->withErrors($validator)
                ->withInput()
                ->with('department_modal', [
                    'action' => route('admin.structure.departments.update', $department),
                ]);
        }

        $validated = $validator->validated();

        $department->update([
            'name' => trim($validated['name']),
            'general_manager_id' => $validated['general_manager_id'] ?? null,
        ]);

        return redirect()
            ->route('admin.structure.index')
            ->with('success', 'Departemen berhasil diperbarui.');
    }

    private function resolveDepartmentForStructure(array $validated): Department
    {
        if (! empty($validated['department_id'])) {
            return Department::findOrFail((int) $validated['department_id']);
        }

        return Department::create([
            'name' => trim((string) $validated['department_name_new']),
            'general_manager_id' => $validated['general_manager_id'] ?? null,
        ]);
    }

    /**
     * @param list<array{name: string, manager_id: int|string}> $sections
     */
    private function syncVendorSections(VendorWorkType $vendorWorkType, array $sections): void
    {
        DB::transaction(function () use ($vendorWorkType, $sections): void {
            $vendorWorkType->vendorSections()->delete();

            foreach ($sections as $section) {
                $vendorWorkType->vendorSections()->create([
                    'name' => trim((string) $section['name']),
                    'manager_id' => $section['manager_id'],
                ]);
            }
        });
    }
}
