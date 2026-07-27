<?php

namespace App\Services\Approvals;

use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Support\BastApprovalFlow;
use App\Support\BastApprovalSignatureBuilder;
use App\Support\BastApproverResolver;
use Illuminate\Support\Facades\DB;

class BastSmPengendaliSynchronizer
{
    public function __construct(
        private readonly BastApproverResolver $approverResolver,
        private readonly BastApprovalSignatureBuilder $signatureBuilder,
    ) {}

    /**
     * @return array{status:string,flow_updated:bool,sm_added:bool,gm_pending_redirected:bool}
     */
    public function sync(LhppBast $bast): array
    {
        return DB::transaction(function () use ($bast): array {
            $lockedBast = LhppBast::query()
                ->whereKey($bast->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBast->approval_status === LhppBast::APPROVAL_REJECTED) {
                return $this->result('skipped_rejected');
            }

            $flow = $this->flowWithSm($lockedBast);
            $flowUpdated = array_values((array) $lockedBast->approval_flow) !== $flow;

            if ($flowUpdated) {
                $lockedBast->update(['approval_flow' => $flow]);
            }

            $signatures = LhppBastSignature::query()
                ->where('lhpp_bast_id', $lockedBast->id)
                ->orderBy('step_order')
                ->lockForUpdate()
                ->get();

            if ($signatures->isEmpty()) {
                return $this->result($flowUpdated ? 'updated' : 'unchanged', $flowUpdated);
            }

            if (! in_array('SM Pengendali', $flow, true)) {
                return $this->result($flowUpdated ? 'updated' : 'unchanged', $flowUpdated);
            }

            if ($signatures->contains('role_key', 'sm_pengendali')) {
                return $this->result($flowUpdated ? 'updated' : 'unchanged', $flowUpdated);
            }

            $approver = $this->approverResolver->resolveApprover($lockedBast, 'SM Pengendali');
            $gm = $signatures->firstWhere('role_key', 'gm_pengendali');
            $redirectGmPending = $gm?->isPending() ?? false;

            LhppBastSignature::query()
                ->where('lhpp_bast_id', $lockedBast->id)
                ->update(['step_order' => DB::raw('step_order + 1000')]);

            $sm = $lockedBast->signatures()->create([
                'step_order' => 2000,
                'role_key' => 'sm_pengendali',
                'role_label' => 'SM Pengendali',
                'signer_user_id' => $approver['user']->id,
                'signer_name_snapshot' => $approver['user']->name,
                'signer_position_snapshot' => $approver['position'],
                'signer_department_snapshot' => $approver['department'],
                'signer_unit_snapshot' => $approver['unit'],
                'signer_section_snapshot' => $approver['section'],
                'status' => $redirectGmPending
                    ? LhppBastSignature::STATUS_PENDING
                    : LhppBastSignature::STATUS_LOCKED,
            ]);

            $allSignatures = LhppBastSignature::query()
                ->where('lhpp_bast_id', $lockedBast->id)
                ->get()
                ->keyBy('role_key');

            $orderedRoleKeys = collect($flow)
                ->map(fn (string $role): string => $this->roleKey($role))
                ->filter()
                ->values();
            $extras = $allSignatures->keys()->diff($orderedRoleKeys)->values();

            $orderedRoleKeys
                ->concat($extras)
                ->each(function (string $roleKey, int $index) use ($allSignatures): void {
                    $allSignatures->get($roleKey)?->update(['step_order' => $index + 1]);
                });

            if ($redirectGmPending && $gm) {
                $gm->update([
                    'status' => LhppBastSignature::STATUS_LOCKED,
                    'token' => null,
                    'token_hash' => null,
                    'token_expires_at' => null,
                    'opened_at' => null,
                ]);
                $this->signatureBuilder->issueToken($sm->fresh(), false);
            }

            return $this->result('updated', $flowUpdated, true, $redirectGmPending);
        });
    }

    /**
     * @return list<string>
     */
    private function flowWithSm(LhppBast $bast): array
    {
        $flow = collect((array) $bast->approval_flow)
            ->map(fn (mixed $role): string => trim((string) $role))
            ->filter()
            ->values()
            ->all();

        if ($flow === []) {
            $flow = BastApprovalFlow::resolveApprovalFlow((string) $bast->approval_threshold);
        }

        if (in_array('SM Pengendali', $flow, true)) {
            return array_values($flow);
        }

        $gmIndex = array_search('GM Pengendali', $flow, true);

        if ($gmIndex === false) {
            return array_values($flow);
        } else {
            array_splice($flow, $gmIndex, 0, ['SM Pengendali']);
        }

        return array_values($flow);
    }

    private function roleKey(string $role): string
    {
        return match ($role) {
            'Manager PKM' => 'manager_pkm',
            'Manager Peminta', 'Manager User' => 'manager_peminta',
            'Manager Pengendali', 'Manager Workshop' => 'manager_pengendali',
            'SM Pengendali', 'SM PMMS' => 'sm_pengendali',
            'GM Pengendali', 'GM PMMS' => 'gm_pengendali',
            'DIROPS', 'Dirops' => 'dirops',
            default => '',
        };
    }

    /**
     * @return array{status:string,flow_updated:bool,sm_added:bool,gm_pending_redirected:bool}
     */
    private function result(
        string $status,
        bool $flowUpdated = false,
        bool $smAdded = false,
        bool $gmPendingRedirected = false,
    ): array {
        return [
            'status' => $status,
            'flow_updated' => $flowUpdated,
            'sm_added' => $smAdded,
            'gm_pending_redirected' => $gmPendingRedirected,
        ];
    }
}
