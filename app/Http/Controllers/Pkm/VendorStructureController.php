<?php

namespace App\Http\Controllers\Pkm;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VendorWorkType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VendorStructureController extends Controller
{
    private const DEFAULT_MANAGER_PASSWORD = 'bengkelmesin123';

    public function update(Request $request, VendorWorkType $vendorWorkType): RedirectResponse
    {
        abort_unless($vendorWorkType->name === VendorWorkType::FIXED_VENDOR_NAME, 404);

        $validator = Validator::make($request->all(), [
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.id' => ['nullable', 'integer'],
            'sections.*.name' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
            'sections.*.manager_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_APPROVER)),
            ],
        ], [
            'sections.*.manager_id.exists' => 'Manager seksi harus menggunakan akun Approval yang valid.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'pkmVendorStructure')->withInput()
                ->with('pkm_vendor_structure_modal', true);
        }

        try {
            DB::transaction(function () use ($vendorWorkType, $validator): void {
                $vendor = VendorWorkType::query()->whereKey($vendorWorkType->id)->lockForUpdate()->firstOrFail();
                $existing = $vendor->vendorSections()->lockForUpdate()->get()->keyBy('id');
                $submittedIds = [];
                $seenNames = [];

                foreach ($validator->validated()['sections'] as $index => $section) {
                    $name = trim((string) $section['name']);
                    $normalizedName = Str::lower($name);
                    if (isset($seenNames[$normalizedName])) {
                        throw ValidationException::withMessages([
                            "sections.{$index}.name" => 'Nama seksi tidak boleh duplikat, termasuk perbedaan huruf besar/kecil.',
                        ]);
                    }
                    $seenNames[$normalizedName] = true;
                    $sectionId = isset($section['id']) ? (int) $section['id'] : null;

                    if ($sectionId) {
                        $model = $existing->get($sectionId);
                        if (! $model) {
                            throw ValidationException::withMessages(["sections.{$index}.id" => 'Seksi vendor yang dipilih tidak valid.']);
                        }
                        $model->update(['name' => $name, 'normalized_name' => $normalizedName, 'manager_id' => $section['manager_id']]);
                        $submittedIds[] = $sectionId;
                    } else {
                        $submittedIds[] = $vendor->vendorSections()->create([
                            'name' => $name,
                            'normalized_name' => $normalizedName,
                            'manager_id' => $section['manager_id'],
                        ])->id;
                    }
                }

                $vendor->vendorSections()->whereNotIn('id', $submittedIds)->delete();
            });
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors(), 'pkmVendorStructure')->withInput()
                ->with('pkm_vendor_structure_modal', true);
        }

        return back()->with('status', 'Seksi vendor berhasil diperbarui.');
    }

    public function storeManager(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'nomor_hp' => ['nullable', 'string', 'max:30'],
            'inisial' => ['nullable', 'string', 'max:20'],
        ]);

        $manager = User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'nomor_hp' => $this->nullableTrim($validated['nomor_hp'] ?? null),
            'inisial' => $this->nullableTrim($validated['inisial'] ?? null),
            'role' => User::ROLE_APPROVER,
            'admin_role' => null,
            'password' => self::DEFAULT_MANAGER_PASSWORD,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Manager seksi berhasil ditambahkan.',
            'manager' => $manager->only(['id', 'name', 'email', 'nomor_hp', 'inisial']),
        ], 201);
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
