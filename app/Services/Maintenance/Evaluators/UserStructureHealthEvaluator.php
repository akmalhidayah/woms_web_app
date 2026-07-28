<?php

namespace App\Services\Maintenance\Evaluators;

use App\Models\UnitWorkSection;
use App\Models\User;

class UserStructureHealthEvaluator implements MaintenanceEvaluator
{
    public function category(): string
    {
        return 'users_structure';
    }

    public function evaluate(string $mode): array
    {
        $findings = [];
        $limit = (int) config('maintenance.max_findings_per_category');

        User::query()
            ->select(['id', 'name', 'role'])
            ->whereNotIn('role', User::roles())
            ->limit($limit)
            ->each(function (User $user) use (&$findings): void {
                $findings[] = $this->finding('user_invalid_role', 'critical', $user->id, $user->name, 'User mempunyai role yang tidak valid.');
            });

        User::query()
            ->select(['id', 'name', 'admin_role'])
            ->where('role', User::ROLE_ADMIN)
            ->whereNotNull('admin_role')
            ->whereNotIn('admin_role', array_keys(User::adminRoleOptions()))
            ->limit($limit)
            ->each(function (User $user) use (&$findings): void {
                $findings[] = $this->finding('admin_invalid_subrole', 'critical', $user->id, $user->name, 'Admin mempunyai admin role yang tidak valid.');
            });

        UnitWorkSection::query()
            ->select(['id', 'name'])
            ->whereDoesntHave('unitWork')
            ->limit($limit)
            ->each(function (UnitWorkSection $section) use (&$findings): void {
                $findings[] = $this->finding('section_without_unit', 'warning', $section->id, $section->name, 'Seksi tidak mempunyai Unit Kerja yang valid.');
            });

        return $findings;
    }

    private function finding(string $code, string $severity, mixed $id, string $reference, string $description): array
    {
        return [
            'code' => $code,
            'severity' => $severity,
            'module' => 'User & Struktur',
            'title' => $description,
            'description' => $description,
            'subject_type' => 'user_structure',
            'subject_id' => $id,
            'reference' => $reference,
            'url' => route('admin.user-panel.index'),
            'secondary_url' => route('admin.structure.index'),
            'meta' => [],
            'detected_at' => now()->toIso8601String(),
        ];
    }
}
