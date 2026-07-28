<?php

namespace App\Services\Maintenance\Evaluators;

use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\InitialWorkSignature;
use App\Models\LhppBastSignature;
use App\Models\QualityControlSignature;
use Illuminate\Database\Eloquent\Model;

class ApprovalHealthEvaluator implements MaintenanceEvaluator
{
    public function category(): string
    {
        return 'approval';
    }

    public function evaluate(string $mode): array
    {
        $findings = [];

        Hpp::query()
            ->select(['id', 'order_id', 'status', 'updated_at'])
            ->where('status', Hpp::STATUS_IN_REVIEW)
            ->whereDoesntHave('signatures', fn ($query) => $query->where('status', HppSignature::STATUS_PENDING))
            ->limit($this->limit())
            ->each(function (Hpp $hpp) use (&$findings): void {
                $findings[] = $this->finding(
                    'hpp_in_review_without_pending_signature',
                    'critical',
                    'HPP',
                    $hpp,
                    'Approval HPP tidak mempunyai tahap aktif.',
                    route('admin.hpp.index')
                );
            });

        foreach ([
            HppSignature::class => ['hpp_id', 'HPP'],
            LhppBastSignature::class => ['lhpp_bast_id', 'BAST'],
            InitialWorkSignature::class => ['initial_work_id', 'Initial Work'],
            QualityControlSignature::class => ['quality_control_report_id', 'Quality Control'],
        ] as $model => [$foreignKey, $module]) {
            $model::query()
                ->selectRaw($foreignKey.', COUNT(*) as pending_count')
                ->where('status', $model::STATUS_PENDING)
                ->groupBy($foreignKey)
                ->havingRaw('COUNT(*) > 1')
                ->limit($this->limit())
                ->get()
                ->each(function ($row) use (&$findings, $foreignKey, $module): void {
                    $findings[] = $this->plainFinding(
                        strtolower(str_replace(' ', '_', $module)).'_multiple_pending_signatures',
                        'critical',
                        $module,
                        'Terdapat lebih dari satu tahap approval aktif.',
                        $row->{$foreignKey},
                        (string) $row->{$foreignKey}
                    );
                });

            $model::query()
                ->select(['id', $foreignKey, 'created_at'])
                ->where('status', $model::STATUS_PENDING)
                ->whereNull('signer_user_id')
                ->limit($this->limit())
                ->each(function ($signature) use (&$findings, $module): void {
                    $findings[] = $this->plainFinding(
                        strtolower(str_replace(' ', '_', $module)).'_pending_without_signer',
                        'critical',
                        $module,
                        'Tahap approval aktif tidak mempunyai signer.',
                        $signature->id,
                        (string) $signature->id
                    );
                });
        }

        return $findings;
    }

    private function finding(string $code, string $severity, string $module, Model $subject, string $description, ?string $url): array
    {
        return $this->plainFinding($code, $severity, $module, $description, $subject->getKey(), (string) $subject->getKey(), $url);
    }

    private function plainFinding(string $code, string $severity, string $module, string $description, mixed $id, string $reference, ?string $url = null): array
    {
        return [
            'code' => $code,
            'severity' => $severity,
            'module' => $module,
            'title' => $description,
            'description' => $description,
            'subject_type' => strtolower(str_replace(' ', '_', $module)),
            'subject_id' => $id,
            'reference' => $reference,
            'url' => $url,
            'secondary_url' => null,
            'meta' => [],
            'detected_at' => now()->toIso8601String(),
        ];
    }

    private function limit(): int
    {
        return (int) config('maintenance.max_findings_per_category');
    }
}
