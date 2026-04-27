<?php

namespace App\Support;

use App\Models\Hpp;
use App\Models\HppSignature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HppApprovalSignatureBuilder
{
    private const TOKEN_TTL_DAYS = 14;

    public function __construct(
        private readonly HppApproverResolver $approverResolver,
    ) {
    }

    public function ensureSignatures(Hpp $hpp): void
    {
        DB::transaction(function () use ($hpp): void {
            $hpp->refresh();
            $hpp->loadMissing('signatures');

            if ($hpp->signatures->isNotEmpty()) {
                return;
            }

            $flow = HppApprovalFlow::resolveApprovalFlow(
                (string) $hpp->kategori_pekerjaan,
                (string) $hpp->area_pekerjaan,
                (string) $hpp->nilai_hpp_bucket,
            );

            if ($flow === []) {
                throw ValidationException::withMessages([
                    'approval' => 'Flow approval HPP tidak ditemukan untuk kombinasi kategori, area, dan nilai HPP ini.',
                ]);
            }

            foreach (array_values($flow) as $index => $roleLabel) {
                $stepOrder = $index + 1;
                $approver = $this->approverResolver->resolveApprover($hpp, $roleLabel);

                $signature = $hpp->signatures()->create([
                    'step_order' => $stepOrder,
                    'role_key' => $approver['role_key'],
                    'role_label' => $approver['role_label'],
                    'signer_user_id' => $approver['user']->id,
                    'signer_name_snapshot' => $approver['user']->name,
                    'signer_position_snapshot' => $approver['position'],
                    'signer_department_snapshot' => $approver['department'],
                    'signer_unit_snapshot' => $approver['unit'],
                    'signer_section_snapshot' => $approver['section'],
                    'status' => $stepOrder === 1
                        ? HppSignature::STATUS_PENDING
                        : HppSignature::STATUS_LOCKED,
                ]);

                if ($stepOrder === 1) {
                    $this->issueToken($signature);
                }
            }
        });
    }

    public function activateNextSignature(HppSignature $signedSignature): ?string
    {
        return DB::transaction(function () use ($signedSignature): ?string {
            $nextSignature = HppSignature::query()
                ->where('hpp_id', $signedSignature->hpp_id)
                ->where('step_order', '>', $signedSignature->step_order)
                ->where('status', HppSignature::STATUS_LOCKED)
                ->orderBy('step_order')
                ->lockForUpdate()
                ->first();

            if (! $nextSignature) {
                $this->markHppApprovedIfComplete($signedSignature->hpp);

                return null;
            }

            $token = $this->issueToken($nextSignature);

            $nextSignature->update([
                'status' => HppSignature::STATUS_PENDING,
            ]);

            return route('approval.hpp.show', $token);
        });
    }

    public function markHppApprovedIfComplete(Hpp $hpp): void
    {
        $hpp->loadMissing('signatures');

        if (! $hpp->approvalCompleted()) {
            return;
        }

        $hpp->update([
            'status' => Hpp::STATUS_APPROVED,
        ]);
    }

    public function issueToken(HppSignature $signature): string
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
