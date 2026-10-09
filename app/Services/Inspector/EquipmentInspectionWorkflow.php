<?php

namespace App\Services\Inspector;

use App\Models\EquipmentInspection;
use App\Models\EquipmentInspectionApproval;
use App\Models\EquipmentInspectionSignature;
use App\Models\User;
use App\Policies\EquipmentInspectionPolicy;
use App\Services\Approvals\ApprovalNotificationService;
use App\Support\Inspector\EquipmentInspectionApproverResolver;
use App\Support\Inspector\EquipmentInspectionSnapshot;
use App\Support\Inspector\InspectionImageStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class EquipmentInspectionWorkflow
{
    public function __construct(private readonly InspectionImageStorage $images) {}

    public static function audit(EquipmentInspection $inspection, ?User $actor, string $event, array $metadata = []): void
    {
        $inspection->logs()->create(['actor_user_id' => $actor?->id, 'event' => $event,
            'document_version' => $inspection->document_version, 'lock_version' => $inspection->lock_version,
            'metadata' => $metadata, 'created_at' => now()]);
    }

    // Called only after the inspector transaction has committed; failure must not undo their signature/number.
    public function initialize(EquipmentInspection $existing): void
    {
        try {
            $approvalId = DB::transaction(function () use ($existing): ?int {
                $inspection = EquipmentInspection::query()->whereKey($existing->id)->lockForUpdate()->firstOrFail();
                if ($inspection->status !== EquipmentInspection::STATUS_READY) {
                    return null;
                }
                $this->signedSnapshot($inspection);
                $resolved = app(EquipmentInspectionApproverResolver::class)->resolve();
                foreach ($resolved as $step) {
                    $inspection->approvals()->firstOrCreate(['document_version' => $inspection->document_version, 'step_order' => $step['step_order']], [
                        'role_key' => $step['role_key'], 'signer_user_id' => $step['user']->id,
                        'signer_name' => $step['user']->name, 'signer_position' => $step['position'], 'status' => EquipmentInspectionApproval::LOCKED,
                    ]);
                }
                $approval = $inspection->approvals()->where('document_version', $inspection->document_version)->where('step_order', 2)->firstOrFail();
                $this->activate($inspection, $approval);
                $inspection->update(['status' => EquipmentInspection::STATUS_MANAGER, 'workflow_error' => null, 'lock_version' => $inspection->lock_version + 1]);

                return $approval->id;
            });
        } catch (Throwable $exception) {
            $message = $exception instanceof ValidationException ? collect($exception->errors())->flatten()->implode(' ') : 'Inisialisasi approval gagal. Admin dapat mencoba kembali setelah konfigurasi diperiksa.';
            EquipmentInspection::query()->whereKey($existing->id)->where('status', EquipmentInspection::STATUS_READY)->update(['workflow_error' => $message]);
            Log::warning('Equipment inspection initialization failed.', ['inspection_id' => $existing->id, 'exception_type' => $exception::class]);

            return;
        }
        if ($approvalId !== null) {
            DB::afterCommit(fn () => $this->notifySafely($approvalId));
        }
    }

    private function activate(EquipmentInspection $inspection, EquipmentInspectionApproval $approval): void
    {
        abort_unless($approval->status === EquipmentInspectionApproval::LOCKED, 409);
        $this->rotateToken($approval);
        $approval->forceFill(['status' => EquipmentInspectionApproval::PENDING, 'activated_at' => now()])->save();
        self::audit($inspection, null, 'approval_activated', ['approval_id' => $approval->id, 'step' => $approval->step_order]);
    }

    private function rotateToken(EquipmentInspectionApproval $approval): void
    {
        $token = Str::random(64);
        $approval->forceFill(['token_hash' => hash('sha256', $token), 'token_encrypted' => $token, 'token_expires_at' => now()->addDays(7)]);
    }

    public function resolve(User $actor, string $token, bool $allowReceipt = false): EquipmentInspectionApproval
    {
        abort_unless($actor->role === User::ROLE_APPROVER, 403);
        $query = EquipmentInspectionApproval::query()->where('signer_user_id', $actor->id)
            ->where('token_hash', hash('sha256', $token))->where('token_expires_at', '>', now());
        if ($allowReceipt) {
            $query->where(fn ($q) => $q->where(fn ($q) => $q->active())
                ->orWhere(fn ($q) => $q->whereIn('status', [EquipmentInspectionApproval::APPROVED, EquipmentInspectionApproval::RETURNED])
                    ->whereHas('inspection', fn ($q) => $q->whereColumn('equipment_inspections.document_version', 'equipment_inspection_approvals.document_version'))));
        } else {
            $query->active();
        }

        return $query->with('inspection')->firstOrFail();
    }

    public function decide(User $actor, EquipmentInspectionApproval $existing, string $token, array $data): EquipmentInspection
    {
        $createdPaths = [];
        $processed = false;
        try {
            $inspection = DB::transaction(function () use ($actor, $existing, $token, $data, &$createdPaths, &$processed): EquipmentInspection {
                $inspection = EquipmentInspection::query()->whereKey($existing->equipment_inspection_id)->lockForUpdate()->firstOrFail();
                $approval = EquipmentInspectionApproval::query()->whereKey($existing->id)->lockForUpdate()->firstOrFail();
                abort_unless($actor->role === User::ROLE_APPROVER && (int) $approval->signer_user_id === (int) $actor->id, 403);
                abort_unless(hash_equals((string) $approval->token_hash, hash('sha256', $token)), 403);
                abort_unless($approval->token_expires_at?->isFuture() && $approval->document_version === $inspection->document_version
                    && $approval->document_version === (int) $data['document_version'], 409);
                // A retry is harmless, but cannot make a second decision or reactivate anything.
                if (in_array($approval->status, [EquipmentInspectionApproval::APPROVED, EquipmentInspectionApproval::RETURNED], true)) {
                    abort_unless(($data['decision'] === 'approve') === ($approval->status === EquipmentInspectionApproval::APPROVED), 409);
                    return $inspection;
                }
                abort_unless(EquipmentInspectionApproval::query()->whereKey($approval->id)->active()->exists(), 409, 'Giliran approval sudah berubah atau kedaluwarsa.');
                abort_unless($approval->document_version === (int) $data['document_version'], 409);
                $snapshot = $this->signedSnapshot($inspection);
                $note = trim((string) ($data['decision_note'] ?? ''));
                if ($data['decision'] === 'return') {
                    if (! preg_match('/[^\s\p{Z}]/u', $note)) {
                        throw ValidationException::withMessages(['decision_note' => 'Alasan pengembalian wajib diisi.']);
                    }
                    $approval->update(['status' => EquipmentInspectionApproval::RETURNED, 'decided_at' => now(), 'decision_note' => $note, 'token_encrypted' => null]);
                    $inspection->approvals()->where('document_version', $inspection->document_version)->where('status', EquipmentInspectionApproval::LOCKED)
                        ->update(['status' => EquipmentInspectionApproval::CANCELLED, 'token_hash' => null, 'token_encrypted' => null]);
                    $inspection->update(['status' => EquipmentInspection::STATUS_REVISION, 'revision_note' => $note,
                        'returned_by_name' => $approval->signer_name, 'returned_at' => now(), 'lock_version' => $inspection->lock_version + 1]);
                    self::audit($inspection, $actor, 'returned_for_revision', ['approval_id' => $approval->id, 'reason' => $note]);
                    $processed = true;

                    return $inspection;
                }
                $stored = $this->images->signature($data['signature_data'], $inspection->public_id);
                $createdPaths[] = $stored['path'];
                $inspection->signatures()->create(['document_version' => $inspection->document_version, 'role_key' => $approval->role_key,
                    'step' => $approval->step_order, 'signer_user_id' => $actor->id, 'signer_name' => $approval->signer_name,
                    'signer_position' => $approval->signer_position, 'signature_path' => $stored['path'], 'signature_sha256' => $stored['sha256'],
                    'signed_at' => now(), 'content_hash' => $snapshot->content_hash, 'signed_payload' => $snapshot->signed_payload]);
                $approval->update(['status' => EquipmentInspectionApproval::APPROVED, 'decided_at' => now(), 'decision_note' => $note ?: null, 'token_encrypted' => null]);
                self::audit($inspection, $actor, 'manager_approved', ['approval_id' => $approval->id, 'note' => $note]);
                $this->complete($inspection, $actor);
                $processed = true;

                return $inspection;
            });
        } catch (Throwable $exception) {
            $this->images->cleanup($createdPaths);
            throw $exception;
        }
        if (! $processed) {
            return $inspection;
        }
        // Side effects are recoverable and never undo a committed approval.
        if ($inspection->status === EquipmentInspection::STATUS_APPROVED) {
            DB::afterCommit(fn () => app(EquipmentInspectionPdfService::class)->archive($inspection));
        }

        return $inspection;
    }

    private function complete(EquipmentInspection $inspection, User $actor): void
    {
        abort_unless($inspection->signatures()->where('document_version', $inspection->document_version)
            ->whereIn('role_key', array_keys(EquipmentInspectionSignature::STEPS))->count() === count(EquipmentInspectionSignature::STEPS), 409);
        $cancelled = $inspection->approvals()->where('document_version', $inspection->document_version)
            ->where('role_key', EquipmentInspectionSignature::ROLE_LEADER)
            ->whereIn('status', [EquipmentInspectionApproval::LOCKED, EquipmentInspectionApproval::PENDING])
            ->update(['status' => EquipmentInspectionApproval::CANCELLED, 'token_hash' => null, 'token_encrypted' => null, 'token_expires_at' => null]);
        if ($cancelled) {
            self::audit($inspection, $actor, 'leader_step_disabled', ['cancelled_approvals' => $cancelled]);
        }
        $inspection->update(['status' => EquipmentInspection::STATUS_APPROVED, 'workflow_error' => null, 'lock_version' => $inspection->lock_version + 1]);
        self::audit($inspection, $actor, 'document_approved');
    }

    public function finalizeLegacy(User $admin, EquipmentInspection $existing): void
    {
        abort_unless((new EquipmentInspectionPolicy)->monitor($admin), 403);
        $inspection = DB::transaction(function () use ($admin, $existing): EquipmentInspection {
            $inspection = EquipmentInspection::query()->whereKey($existing->id)->lockForUpdate()->firstOrFail();
            if ($inspection->status === EquipmentInspection::STATUS_APPROVED) {
                return $inspection;
            }
            abort_unless($inspection->status === EquipmentInspection::STATUS_LEADER, 409);
            $this->signedSnapshot($inspection);
            abort_unless($inspection->approvals()->where('document_version', $inspection->document_version)
                ->where('role_key', EquipmentInspectionSignature::ROLE_MANAGER)->where('status', EquipmentInspectionApproval::APPROVED)->exists(), 409);
            $this->complete($inspection, $admin);

            return $inspection;
        });
        DB::afterCommit(fn () => app(EquipmentInspectionPdfService::class)->archive($inspection));
    }

    public function beginRevision(User $actor, EquipmentInspection $existing, int $version): EquipmentInspection
    {
        return DB::transaction(function () use ($actor, $existing, $version): EquipmentInspection {
            $inspection = EquipmentInspection::query()->whereKey($existing->id)->lockForUpdate()->firstOrFail();
            abort_unless((new EquipmentInspectionPolicy)->view($actor, $inspection), 403);
            if ($inspection->isDraft() && $inspection->document_version === $version + 1) {
                return $inspection;
            }
            abort_unless($inspection->status === EquipmentInspection::STATUS_REVISION && $inspection->document_version === $version, 409);
            $this->signedSnapshot($inspection);
            $inspection->approvals()->where('document_version', $version)->update(['token_hash' => null, 'token_encrypted' => null]);
            $inspection->update(['status' => EquipmentInspection::STATUS_DRAFT, 'signed_at' => null,
                'document_version' => $version + 1, 'lock_version' => $inspection->lock_version + 1, 'workflow_error' => null]);
            self::audit($inspection, $actor, 'revision_started', ['previous_version' => $version]);

            return $inspection;
        });
    }

    private function signedSnapshot(EquipmentInspection $inspection): EquipmentInspectionSignature
    {
        $signature = $inspection->signatures()->where('document_version', $inspection->document_version)->where('role_key', 'inspector')->firstOrFail();
        $currentPayload = EquipmentInspectionSnapshot::payload($inspection);
        // Nullable FK may be cleared when a former inspector account is removed; the signed identity is audit data.
        if ($inspection->inspector_user_id === null) {
            $currentPayload['inspector_user_id'] = $signature->signed_payload['inspector_user_id'];
        }
        abort_unless(hash_equals($signature->content_hash, EquipmentInspectionSnapshot::hash($signature->signed_payload))
            && hash_equals($signature->content_hash, EquipmentInspectionSnapshot::hash($currentPayload)), 409, 'Isi laporan tidak sesuai snapshot yang ditandatangani.');
        foreach ($inspection->signatures()->where('document_version', $inspection->document_version)->get() as $signed) {
            abort_unless(hash_equals($signed->content_hash, $signature->content_hash)
                && hash_equals($signed->content_hash, EquipmentInspectionSnapshot::hash($signed->signed_payload)), 409);
            $this->images->read($signed->signature_path, $signed->signature_sha256);
        }
        foreach ($inspection->answers as $answer) {
            foreach ($answer->attachments as $file) {
                $this->images->read($file->path, $file->sha256);
            }
        }

        return $signature;
    }

    public function sendEmail(int $approvalId, ?User $admin = null): void
    {
        if ($admin) {
            abort_unless((new EquipmentInspectionPolicy)->monitor($admin), 403);
        }
        $claimed = DB::transaction(function () use ($approvalId, $admin): ?array {
            $reference = EquipmentInspectionApproval::findOrFail($approvalId);
            $inspection = EquipmentInspection::query()->whereKey($reference->equipment_inspection_id)->lockForUpdate()->firstOrFail();
            $approval = EquipmentInspectionApproval::query()->whereKey($approvalId)->lockForUpdate()->firstOrFail();
            abort_unless(EquipmentInspectionApproval::query()->whereKey($approvalId)->active(true)->exists(), 409);
            if (! $admin && $approval->email_status !== 'not_sent') {
                return null;
            }
            $cooldown = $approval->email_status === 'sending' ? 10 : 1;
            if ($approval->email_attempted_at?->gt(now()->subMinutes($cooldown))) {
                if ($admin) {
                    throw ValidationException::withMessages(['email' => 'Pengiriman sedang diproses atau baru dicoba. Tunggu sebelum mengirim ulang.']);
                }

                return null;
            }
            if (! $approval->token_expires_at?->isFuture()) {
                $this->rotateToken($approval);
                self::audit($inspection, $admin, 'approval_token_renewed', ['approval_id' => $approvalId]);
            }
            $attempt = (string) Str::uuid();
            $approval->forceFill(['email_status' => 'sending', 'email_attempt_id' => $attempt, 'email_attempted_at' => now()])->save();
            self::audit($inspection, $admin, $admin ? 'admin_resend_email' : 'email_attempted', ['approval_id' => $approvalId, 'attempt_id' => $attempt]);

            return [$approval, $attempt];
        });
        if (! $claimed) {
            return;
        }
        [$approval, $attempt] = $claimed;
        DB::afterCommit(fn () => $this->deliverEmail($approval, $attempt, $admin));
    }

    private function deliverEmail(EquipmentInspectionApproval $approval, string $attempt, ?User $admin): void
    {
        $approvalId = $approval->id;
        try {
            // Recheck after the claim commits; a cancelled/old-version request must not be mailed.
            if (! EquipmentInspectionApproval::query()->whereKey($approvalId)->active()->where('email_attempt_id', $attempt)->exists()) {
                return;
            }
            $sent = app(ApprovalNotificationService::class)->sendEquipmentInspection($approval, $admin !== null);
        } catch (Throwable $exception) {
            $sent = false;
            Log::warning('Equipment inspection mail failed.', ['approval_id' => $approvalId, 'exception_type' => $exception::class]);
        }
        DB::transaction(function () use ($approval, $attempt, $sent, $admin): void {
            // A concurrent deletion must not erase the outcome of a mail already sent.
            $inspection = EquipmentInspection::withTrashed()->whereKey($approval->equipment_inspection_id)->lockForUpdate()->firstOrFail();
            $locked = EquipmentInspectionApproval::query()->whereKey($approval->id)->lockForUpdate()->firstOrFail();
            if ($locked->email_attempt_id !== $attempt) {
                return;
            }
            $locked->update(['email_status' => $sent ? ($admin ? 'resent' : 'sent') : 'failed', 'email_sent_at' => $sent ? now() : $locked->email_sent_at]);
            self::audit($inspection, $admin, $sent ? 'email_sent' : 'email_failed', ['approval_id' => $approval->id, 'attempt_id' => $attempt]);
        });
    }

    private function notifySafely(int $approvalId): void
    {
        try {
            $this->sendEmail($approvalId);
        } catch (Throwable $exception) {
            // The last stored email state stays visible; an Admin can retry after the cooldown.
            Log::warning('Equipment inspection email requires admin follow-up.', ['approval_id' => $approvalId, 'exception_type' => $exception::class]);
        }
    }
}
