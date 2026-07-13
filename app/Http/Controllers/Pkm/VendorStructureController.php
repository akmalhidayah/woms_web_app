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
use Illuminate\Validation\Rule;

class VendorStructureController extends Controller
{
    private const DEFAULT_MANAGER_PASSWORD = 'bengkelmesin123';

    public function update(Request $request, VendorWorkType $vendorWorkType): RedirectResponse
    {
        abort_unless($vendorWorkType->name === VendorWorkType::FIXED_VENDOR_NAME, 404);

        $validator = Validator::make($request->all(), [
            'sections' => ['required', 'array', 'min:1'],
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

        DB::transaction(function () use ($vendorWorkType, $validator): void {
            $vendorWorkType->vendorSections()->delete();
            foreach ($validator->validated()['sections'] as $section) {
                $vendorWorkType->vendorSections()->create([
                    'name' => trim((string) $section['name']),
                    'manager_id' => $section['manager_id'],
                ]);
            }
        });

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
