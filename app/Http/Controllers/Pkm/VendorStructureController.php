<?php

namespace App\Http\Controllers\Pkm;

use App\Http\Controllers\Controller;
use App\Models\VendorWorkType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VendorStructureController extends Controller
{
    public function update(Request $request, VendorWorkType $vendorWorkType): RedirectResponse
    {
        abort_unless($vendorWorkType->name === VendorWorkType::FIXED_VENDOR_NAME, 404);

        $validator = Validator::make($request->all(), [
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.name' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
            'sections.*.manager_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'pkmVendorStructure')
                ->withInput()
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
}
