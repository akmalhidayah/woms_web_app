<?php

namespace App\Support;

use App\Models\User;

class AdminSidebarBadgeCounter
{
    public function __construct(
        private readonly AdminActionCenter $actionCenter,
    ) {}

    /**
     * @return array<string, int>
     */
    public function counts(?User $user = null): array
    {
        return $this->actionCenter->sidebarCounts($user);
    }
}
