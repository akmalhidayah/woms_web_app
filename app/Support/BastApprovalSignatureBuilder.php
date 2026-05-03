<?php

namespace App\Support;

use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BastApprovalSignatureBuilder
{
    private const TOKEN_TTL_DAYS = 14;

    public function __construct(
        private readonly BastApproverResolver $approverResolver,
    ) {
    }

    public function ensureSignatures(LhppBast $lhpp): void
    {
        DB::transaction(function () use ($lhpp): void {
            $lhpp->refresh();
            $lhpp->loadMissing('signatures');

            if ($lhpp->signatures->isNotEmpty()) {
                return;
            }

            $flow = is_array($lhpp->approval_flow) && $lhpp->approval_flow !== []
                ? $lhpp->approval_flow
                : BastApprovalFlow::resolveApprovalFlow((string) $lhpp->approval_threshold);

            if ($flow === []) {
                throw ValidationException::withMessages([
                    'approval' => 'Flow approval BAST tidak ditemukan untuk threshold ini.',
                ]);
            }

            foreach (array_values($flow) as $index => $roleLabel) {
                $stepOrder = $index + 1;
                $approver = $this->approverResolver->resolveApprover($lhpp, (string) $roleLabel);

                $signature = $lhpp->signatures()->create([
                    'step_order' => $stepOrder,
                    'role_key' => $approver['role_key'],
                    'role_label' => $approver['role_label'],
                    'signer_user_id' => $approver['user']->id,
                    'signer_name_snapshot' => $approver['user']->name,
                    'signer_position_snapshot' => $approver['position'],
                    'signer_department_snapshot' => $approver['department'],
                    'signer_unit_snapshot' => $approver['unit'],
                    'signer_section_snapshot' => $approver['section'],
                    'status' => LhppBastSignature::STATUS_LOCKED,
                ]);
            }
        });
    }

    public function activateFirstSignature(LhppBast $lhpp): ?string
    {
        return DB::transaction(function () use ($lhpp): ?string {
            $lhpp->refresh();
            $this->ensureSignatures($lhpp);

            $pendingSignature = LhppBastSignature::query()
                ->where('lhpp_bast_id', $lhpp->id)
                ->where('status', LhppBastSignature::STATUS_PENDING)
                ->orderBy('step_order')
                ->first();

            if ($pendingSignature) {
                return $pendingSignature->approvalUrl();
            }

            $firstSignature = LhppBastSignature::query()
                ->where('lhpp_bast_id', $lhpp->id)
                ->where('status', LhppBastSignature::STATUS_LOCKED)
                ->orderBy('step_order')
                ->lockForUpdate()
                ->first();

            if (! $firstSignature) {
                $this->markBastApprovedIfComplete($lhpp);

                return null;
            }

            $token = $this->issueToken($firstSignature);

            $firstSignature->update([
                'status' => LhppBastSignature::STATUS_PENDING,
            ]);

            return route('approval.bast.show', $token);
        });
    }

    public function activateNextSignature(LhppBastSignature $signedSignature): ?string
    {
        return DB::transaction(function () use ($signedSignature): ?string {
            $nextSignature = LhppBastSignature::query()
                ->where('lhpp_bast_id', $signedSignature->lhpp_bast_id)
                ->where('step_order', '>', $signedSignature->step_order)
                ->where('status', LhppBastSignature::STATUS_LOCKED)
                ->orderBy('step_order')
                ->lockForUpdate()
                ->first();

            if (! $nextSignature) {
                $this->markBastApprovedIfComplete($signedSignature->lhppBast);

                return null;
            }

            $token = $this->issueToken($nextSignature);

            $nextSignature->update([
                'status' => LhppBastSignature::STATUS_PENDING,
            ]);

            return route('approval.bast.show', $token);
        });
    }

    public function markBastApprovedIfComplete(LhppBast $lhpp): void
    {
        $lhpp->loadMissing('signatures');

        if (! $lhpp->approvalCompleted()) {
            return;
        }

        $lhpp->update([
            'approval_status' => LhppBast::APPROVAL_APPROVED,
        ]);
    }

    public function issueToken(LhppBastSignature $signature): string
    {
        $token = Str::random(64);

        $signature->update([
            'token' => $token,
            'token_hash' => hash('sha256', $token),
            'token_expires_at' => now()->addDays(self::TOKEN_TTL_DAYS),
        ]);

        return $token;
    }
}
