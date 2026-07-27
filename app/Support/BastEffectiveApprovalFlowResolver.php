<?php

namespace App\Support;

use App\Models\LhppBast;
use App\Models\User;

class BastEffectiveApprovalFlowResolver
{
    public function __construct(
        private readonly BastApproverResolver $approverResolver,
    ) {}

    /**
     * @param  list<string>  $flow
     * @return list<array{
     *     flow_role_label: string,
     *     role_key: string,
     *     role_label: string,
     *     user: User,
     *     position: string,
     *     department: ?string,
     *     unit: ?string,
     *     section: ?string
     * }>
     */
    public function resolveSteps(LhppBast $bast, array $flow): array
    {
        $steps = collect(array_values($flow))
            ->map(function (string $flowRoleLabel) use ($bast): array {
                $approver = $this->approverResolver->resolveApprover($bast, $flowRoleLabel);

                return [
                    'flow_role_label' => $flowRoleLabel,
                    ...$approver,
                ];
            })
            ->values();

        $managerPemintaIndex = $steps->search(
            fn (array $step): bool => $step['role_key'] === 'manager_peminta'
        );
        $managerPengendaliIndex = $steps->search(
            fn (array $step): bool => $step['role_key'] === 'manager_pengendali'
        );

        if ($managerPemintaIndex === false || $managerPengendaliIndex === false) {
            return $steps->all();
        }

        $managerPeminta = $steps[$managerPemintaIndex];
        $managerPengendali = $steps[$managerPengendaliIndex];

        if ((int) $managerPeminta['user']->id !== (int) $managerPengendali['user']->id) {
            return $steps->all();
        }

        $insertAt = min($managerPemintaIndex, $managerPengendaliIndex);
        $steps = $steps
            ->reject(fn (array $step): bool => in_array(
                $step['role_key'],
                ['manager_peminta', 'manager_pengendali'],
                true
            ))
            ->values();
        $steps->splice($insertAt, 0, [[
            ...$managerPengendali,
            'flow_role_label' => 'Manager Pengendali',
            'role_key' => 'manager_pengendali',
            'role_label' => 'Manager Pengendali',
        ]]);

        return $steps->values()->all();
    }

    /**
     * @param  list<string>  $flow
     * @return list<string>
     */
    public function effectiveFlowLabels(LhppBast $bast, array $flow): array
    {
        $flow = array_values($flow);
        $managerPemintaIndex = collect($flow)->search(
            fn (string $label): bool => in_array($label, ['Manager Peminta', 'Manager User'], true)
        );
        $managerPengendaliIndex = collect($flow)->search(
            fn (string $label): bool => in_array($label, ['Manager Pengendali', 'Manager Workshop'], true)
        );

        if ($managerPemintaIndex === false || $managerPengendaliIndex === false) {
            return $flow;
        }

        $managerPeminta = $this->approverResolver->resolveApprover($bast, $flow[$managerPemintaIndex]);
        $managerPengendali = $this->approverResolver->resolveApprover($bast, $flow[$managerPengendaliIndex]);

        if ((int) $managerPeminta['user']->id !== (int) $managerPengendali['user']->id) {
            return $flow;
        }

        $insertAt = min($managerPemintaIndex, $managerPengendaliIndex);
        $effectiveFlow = collect($flow)
            ->reject(fn (string $label): bool => in_array($label, [
                'Manager Peminta',
                'Manager User',
                'Manager Pengendali',
                'Manager Workshop',
            ], true))
            ->values();
        $effectiveFlow->splice($insertAt, 0, ['Manager Pengendali']);

        return $effectiveFlow->values()->all();
    }
}
