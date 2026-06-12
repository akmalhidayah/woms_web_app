<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDashboardPasswordRequest;
use App\Http\Requests\UpdateDashboardProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function editAdmin(): View
    {
        return view('admin.profile.edit');
    }

    public function updateAdmin(UpdateDashboardProfileRequest $request): RedirectResponse
    {
        return $this->update($request, 'admin.profile.edit');
    }

    public function updateAdminPassword(UpdateDashboardPasswordRequest $request): RedirectResponse
    {
        return $this->updatePassword($request, 'admin.profile.edit');
    }

    public function editPkm(): View
    {
        return view('pkm.profile.edit');
    }

    public function updatePkm(UpdateDashboardProfileRequest $request): RedirectResponse
    {
        return $this->update($request, 'pkm.profile.edit');
    }

    public function updatePkmPassword(UpdateDashboardPasswordRequest $request): RedirectResponse
    {
        return $this->updatePassword($request, 'pkm.profile.edit');
    }

    private function update(UpdateDashboardProfileRequest $request, string $routeName): RedirectResponse
    {
        $user = $request->user();
        $emailWasChanged = $user->email !== $request->validated('email');

        $user->fill($request->validated());

        if ($emailWasChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()
            ->route($routeName)
            ->with('success', 'Perubahan profil berhasil disimpan.');
    }

    private function updatePassword(UpdateDashboardPasswordRequest $request, string $routeName): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => Hash::make($request->validated('password')),
        ])->save();

        return redirect()
            ->route($routeName)
            ->with('success', 'Password berhasil diperbarui.');
    }
}
