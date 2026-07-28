<?php

namespace App\Services\Maintenance\Evaluators;

use App\Models\Hpp;
use App\Models\LhppBast;

class DocumentHealthEvaluator implements MaintenanceEvaluator
{
    public function category(): string
    {
        return 'documents';
    }

    public function evaluate(string $mode): array
    {
        $findings = [];
        $limit = (int) config('maintenance.max_findings_per_category');

        Hpp::query()
            ->selectRaw('order_id, COUNT(*) as aggregate')
            ->whereNotNull('order_id')
            ->groupBy('order_id')
            ->havingRaw('COUNT(*) > 1')
            ->limit($limit)
            ->get()
            ->each(function ($row) use (&$findings): void {
                $findings[] = $this->finding('multiple_hpp_per_order', 'warning', 'HPP', $row->order_id, 'Satu order mempunyai lebih dari satu HPP legacy.');
            });

        LhppBast::query()
            ->select(['lhpp_basts.id', 'lhpp_basts.hpp_id'])
            ->join('hpps', 'hpps.id', '=', 'lhpp_basts.hpp_id')
            ->where('hpps.status', '!=', Hpp::STATUS_APPROVED)
            ->limit($limit)
            ->each(function (LhppBast $bast) use (&$findings): void {
                $findings[] = $this->finding('bast_uses_non_approved_hpp', 'critical', 'BAST', $bast->id, 'BAST menunjuk HPP yang belum approved.');
            });

        LhppBast::query()
            ->select(['id'])
            ->where('termin_type', 'termin_2')
            ->whereNull('parent_lhpp_bast_id')
            ->limit($limit)
            ->each(function (LhppBast $bast) use (&$findings): void {
                $findings[] = $this->finding('termin_two_without_parent', 'critical', 'BAST', $bast->id, 'BAST Termin 2 tidak mempunyai Termin 1.');
            });

        return $findings;
    }

    private function finding(string $code, string $severity, string $module, mixed $id, string $description): array
    {
        return [
            'code' => $code,
            'severity' => $severity,
            'module' => $module,
            'title' => $description,
            'description' => $description,
            'subject_type' => strtolower($module),
            'subject_id' => $id,
            'reference' => (string) $id,
            'url' => $module === 'HPP' ? route('admin.hpp.index') : route('admin.lhpp.index'),
            'secondary_url' => null,
            'meta' => [],
            'detected_at' => now()->toIso8601String(),
        ];
    }
}
