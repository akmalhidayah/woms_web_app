<?php

namespace App\Support;

use App\Models\User;

class AdminSidebarBadgeCounter
{
    /**
     * @return array<string, int>
     */
    public function counts(?User $user = null): array
    {
        return $user?->isAdmin()
            ? app(AdminPresentationData::class)->summary($user)['badges']
            : app(AdminActionCenter::class)->sidebarCounts($user);
    }
}
