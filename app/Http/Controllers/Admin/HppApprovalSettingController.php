<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hpp\UpdateHppApprovalSettingRequest;
use App\Models\HppApprovalSetting;
use Illuminate\Http\RedirectResponse;

class HppApprovalSettingController extends Controller
{
    public function update(UpdateHppApprovalSettingRequest $request): RedirectResponse
    {
        $setting = HppApprovalSetting::current();

        $setting->update($request->validated());

        return redirect()
            ->route('admin.structure.index')
            ->with('success', 'Master approval khusus HPP berhasil diperbarui.');
    }
}