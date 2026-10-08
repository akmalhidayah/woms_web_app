<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\HppApprovalSetting;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;
use App\Models\VendorWorkType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

        $hppApprovalSetting = HppApprovalSetting::query()
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
            'vendorWorkType' => VendorWorkType::query()
                ->with(['vendorSections.manager:id,name'])
                ->where('name', VendorWorkType::FIXED_VENDOR_NAME)
                ->firstOrFail(),
        ]);
    }

    /**
     * Render the read-only organization chart used by the admin header preview.
     */
    public function preview(): View
    {
        $departments = Department::query()
            ->with([
                'generalManager:id,name,inisial',
                'units' => fn ($query) => $query
                    ->with([
                        'seniorManager:id,name,inisial',
                        'sections' => fn ($sectionQuery) => $sectionQuery
                            ->with('manager:id,name,inisial')
                            ->orderBy('name'),
                    ])
                    ->orderBy('name'),
            ])
            ->orderBy('name')
            ->get();

        $dirops = HppApprovalSetting::query()
            ->with('dirops:id,name,inisial')
            ->first()
            ?->dirops;

        return view('admin.structure.partials.preview', [
            'departments' => $departments,
            'dirops' => $dirops,
        ]);
    }

    public function storeVendorStructure(Request $request): RedirectResponse
    {
        abort(405, 'Vendor sudah ditetapkan sebagai '.VendorWorkType::FIXED_VENDOR_NAME.'. Kelola seksi melalui vendor tersebut.');
    }

    public function updateVendorStructure(Request $request, VendorWorkType $vendorWorkType): RedirectResponse
    {
        abort_unless(
            $vendorWorkType->name === VendorWorkType::FIXED_VENDOR_NAME,
            404
        );

        $validator = Validator::make($request->all(), [
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.id' => [
                'nullable',
                'integer',
                'distinct',
                Rule::exists('vendor_work_type_sections', 'id')
                    ->where(fn ($query) => $query->where('vendor_work_type_id', $vendorWorkType->id)),
            ],
            'sections.*.name' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
            'sections.*.manager_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('admin.structure.index')
                ->withErrors($validator, 'vendorStructure')
                ->withInput();
        }

        $validated = $validator->validated();

        try {
            $this->syncVendorSections($vendorWorkType, $validated['sections']);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.structure.index')
                ->withErrors($exception->errors(), 'vendorStructure')
                ->withInput();
        }

        return redirect()
            ->route('admin.structure.index')
            ->with('success', 'Seksi vendor '.VendorWorkType::FIXED_VENDOR_NAME.' berhasil diperbarui.');
    }

    public function destroyVendorStructure(VendorWorkType $vendorWorkType): RedirectResponse
    {
        abort(405, 'Vendor '.VendorWorkType::FIXED_VENDOR_NAME.' bersifat tetap dan tidak dapat dihapus.');
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
            'sections.*.name' => ['required_with:sections', 'string', 'max:255', 'distinct:ignore_case'],
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
            'sections.*.id' => [
                'nullable',
                'integer',
                'distinct',
                Rule::exists('unit_work_sections', 'id')
                    ->where(fn ($query) => $query->where('unit_work_id', $unitWork->id)),
            ],
            'sections.*.name' => ['required_with:sections', 'string', 'max:255', 'distinct:ignore_case'],
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

        try {
            DB::transaction(function () use ($validated, $unitWork): void {
                $lockedUnit = UnitWork::query()->whereKey($unitWork->id)->lockForUpdate()->firstOrFail();
                $department = $this->resolveDepartmentForStructure($validated);

                $department->update([
                    'general_manager_id' => $validated['general_manager_id'] ?? null,
                ]);

                $lockedUnit->update([
                    'department_id' => $department->id,
                    'name' => trim($validated['unit_name']),
                    'senior_manager_id' => $validated['senior_manager_id'] ?? null,
                ]);

                $this->syncUnitSections($lockedUnit, $validated['sections'] ?? []);
            });
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.structure.index')
                ->withErrors($exception->errors())
                ->withInput()
                ->with('structure_modal', [
                    'mode' => 'edit',
                    'action' => route('admin.structure.update', $unitWork),
                ]);
        }

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
     * @param  list<array{id?: int|string|null, name: string, manager_id: int|string}>  $sections
     */
    private function syncVendorSections(VendorWorkType $vendorWorkType, array $sections): void
    {
        DB::transaction(function () use ($vendorWorkType, $sections): void {
            $vendor = VendorWorkType::query()->whereKey($vendorWorkType->id)->lockForUpdate()->firstOrFail();
            $existingSections = $vendor->vendorSections()->lockForUpdate()->get()->keyBy('id');
            $submittedIds = [];

            foreach ($sections as $section) {
                $attributes = [
                    'name' => trim((string) $section['name']),
                    'normalized_name' => Str::lower(trim((string) $section['name'])),
                    'manager_id' => $section['manager_id'],
                ];
                $sectionId = isset($section['id']) && $section['id'] !== '' ? (int) $section['id'] : null;

                if ($sectionId !== null) {
                    $existingSection = $existingSections->get($sectionId);

                    if (! $existingSection) {
                        throw ValidationException::withMessages([
                            'sections' => 'Seksi vendor yang dipilih sudah berubah. Muat ulang halaman lalu coba kembali.',
                        ]);
                    }

                    $existingSection->update($attributes);
                    $submittedIds[] = $existingSection->id;

                    continue;
                }

                $submittedIds[] = $vendor->vendorSections()->create($attributes)->id;
            }

            $removedIds = $existingSections->keys()->diff($submittedIds)->values();
            $this->ensureVendorSectionsCanBeDeleted($removedIds->all());

            $vendor->vendorSections()->whereIn('id', $removedIds->all())->delete();
        });
    }

    /**
     * @param  list<array{id?: int|string|null, name: string, manager_id?: int|string|null}>  $sections
     */
    private function syncUnitSections(UnitWork $unitWork, array $sections): void
    {
        $existingSections = $unitWork->sections()->lockForUpdate()->get()->keyBy('id');
        $submittedIds = [];

        foreach ($sections as $section) {
            $attributes = [
                'name' => trim((string) $section['name']),
                'manager_id' => $section['manager_id'] ?? null,
            ];
            $sectionId = isset($section['id']) && $section['id'] !== '' ? (int) $section['id'] : null;

            if ($sectionId !== null) {
                $existingSection = $existingSections->get($sectionId);

                if (! $existingSection) {
                    throw ValidationException::withMessages([
                        'sections' => 'Seksi yang dipilih sudah berubah. Muat ulang halaman lalu coba kembali.',
                    ]);
                }

                $existingSection->update($attributes);
                $submittedIds[] = $existingSection->id;

                continue;
            }

            $submittedIds[] = $unitWork->sections()->create($attributes)->id;
        }

        $removedIds = $existingSections->keys()->diff($submittedIds)->values();
        $this->ensureUnitSectionsCanBeDeleted($removedIds->all());

        $unitWork->sections()->whereIn('id', $removedIds->all())->delete();
    }

    /**
     * @param  list<int>  $sectionIds
     */
    private function ensureUnitSectionsCanBeDeleted(array $sectionIds): void
    {
        if ($sectionIds === []) {
            return;
        }

        $isUsed = HppApprovalSetting::query()->whereIn('counter_part_section_id', $sectionIds)->exists()
            || DB::table('initial_works')->whereIn('unit_work_section_id', $sectionIds)->exists()
            || VendorWorkType::query()->whereIn('unit_work_section_id', $sectionIds)->exists();

        if ($isUsed) {
            throw ValidationException::withMessages([
                'sections' => 'Seksi tidak dapat dihapus karena masih digunakan oleh konfigurasi approval atau dokumen pekerjaan.',
            ]);
        }
    }

    /**
     * @param  list<int>  $sectionIds
     */
    private function ensureVendorSectionsCanBeDeleted(array $sectionIds): void
    {
        if ($sectionIds !== [] && DB::table('lhpp_basts')->whereIn('vendor_work_type_section_id', $sectionIds)->exists()) {
            throw ValidationException::withMessages([
                'sections' => 'Seksi vendor tidak dapat dihapus karena masih digunakan oleh dokumen BAST.',
            ]);
        }
    }
}
