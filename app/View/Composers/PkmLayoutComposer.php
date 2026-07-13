<?php

namespace App\View\Composers;

use App\Models\User;
use App\Models\VendorWorkType;
use App\Support\PkmNotificationCenter;
use App\Support\PkmSidebarBadgeCounter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PkmLayoutComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();
        $snapshot = PkmNotificationCenter::snapshot(5, $user);
        $vendor = VendorWorkType::query()
            ->with(['vendorSections.manager:id,name,email,nomor_hp,inisial,role'])
            ->where('name', VendorWorkType::FIXED_VENDOR_NAME)->first();
        $hasErrors = session('errors')?->getBag('pkmVendorStructure')->any() ?? false;
        $sectionsSource = $hasErrors ? old('sections', []) : ($vendor?->vendorSections ?? []);
        $assignedIds = collect($vendor?->vendorSections ?? [])->pluck('manager_id');
        $managerIds = $assignedIds->merge(collect($hasErrors ? old('sections', []) : [])->pluck('manager_id'))->filter()->unique();

        $view->with([
            'pkmNotifications' => $snapshot['notifications'],
            'pkmNotificationCount' => $snapshot['count'],
            'pkmUnreadNotificationKeys' => $snapshot['unread_keys'],
            'pkmBadgeCounts' => app(PkmSidebarBadgeCounter::class)->counts(),
            'pkmVendorWorkType' => $vendor,
            'pkmVendorManagers' => User::query()->where('role', User::ROLE_APPROVER)->whereIn('id', $managerIds)
                ->orderBy('name')->get(['id', 'name', 'email', 'nomor_hp', 'inisial']),
            'pkmVendorSections' => collect($sectionsSource)->map(fn ($section, $index): array => [
                'id' => (string) (is_array($section) ? ($section['id'] ?? '') : $section->id),
                'uid' => $hasErrors ? 'old-'.$index : 'section-'.(is_array($section) ? Str::uuid() : $section->id),
                'name' => (string) (is_array($section) ? ($section['name'] ?? '') : $section->name),
                'manager_id' => (string) (is_array($section) ? ($section['manager_id'] ?? '') : $section->manager_id),
            ])->values()->all(),
        ]);
    }
}
