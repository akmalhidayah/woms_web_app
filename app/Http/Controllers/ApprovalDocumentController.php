<?php

namespace App\Http\Controllers;

use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\InitialWorkSignature;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\QualityControlSignature;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class ApprovalDocumentController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const TYPE_LABELS = [
        'hpp' => 'HPP',
        'bast' => 'BAST',
        'initial_work' => 'Initial Work',
        'quality_control' => 'Quality Control',
    ];

    public function index(Request $request): View
    {
        $user = $this->approver($request);
        $selectedType = $this->normalizeType($request->string('type')->toString());

        $documents = $this->pendingDocumentsFor($user, $selectedType);

        return view('approval-documents.index', [
            'documents' => $documents,
            'selectedType' => $selectedType,
            'typeLabels' => self::TYPE_LABELS,
            'totalPending' => $this->pendingDocumentsFor($user, null)->count(),
        ]);
    }

    public function open(Request $request, string $type, int|string $id): RedirectResponse
    {
        $user = $this->approver($request);
        $type = $this->normalizeType($type);

        abort_unless($type !== null, Response::HTTP_NOT_FOUND);

        $signature = $this->findPendingSignatureFor($type, (int) $id, $user);

        abort_unless($signature, Response::HTTP_FORBIDDEN, 'Dokumen approval tidak tersedia.');

        $token = $this->tokenFor($type, $signature);

        abort_unless(filled($token), Response::HTTP_FORBIDDEN, 'Dokumen approval tidak tersedia.');

        return redirect()->route($this->publicRouteName($type), $token);
    }

    private function approver(Request $request): User
    {
        $user = $request->user();

        abort_unless(
            $user?->role === User::ROLE_APPROVER,
            Response::HTTP_FORBIDDEN,
            'Menu Dokumen Approval hanya tersedia untuk user approval.'
        );

        return $user;
    }

    private function normalizeType(?string $type): ?string
    {
        $type = trim((string) $type);

        return array_key_exists($type, self::TYPE_LABELS) ? $type : null;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function pendingDocumentsFor(User $user, ?string $type): Collection
    {
        $documents = collect();

        if ($type === null || $type === 'hpp') {
            $documents = $documents->merge($this->pendingHpp($user));
        }

        if ($type === null || $type === 'bast') {
            $documents = $documents->merge($this->pendingBast($user));
        }

        if ($type === null || $type === 'initial_work') {
            $documents = $documents->merge($this->pendingInitialWork($user));
        }

        if ($type === null || $type === 'quality_control') {
            $documents = $documents->merge($this->pendingQualityControl($user));
        }

        return $documents
            ->sortByDesc(fn (array $item): int => $item['submitted_at']?->getTimestamp() ?? 0)
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function pendingHpp(User $user): Collection
    {
        return HppSignature::query()
            ->with(['hpp'])
            ->where('signer_user_id', $user->id)
            ->where('status', HppSignature::STATUS_PENDING)
            ->whereNotNull('token')
            ->where(function (Builder $query): void {
                $query->whereNull('token_expires_at')->orWhere('token_expires_at', '>', now());
            })
            ->whereHas('hpp', fn (Builder $query): Builder => $query->where('status', '!=', Hpp::STATUS_REJECTED))
            ->orderByDesc('id')
            ->get()
            ->map(fn (HppSignature $signature): array => [
                'type' => 'hpp',
                'type_label' => self::TYPE_LABELS['hpp'],
                'id' => $signature->id,
                'number' => $signature->hpp?->nomor_order ?: '-',
                'title' => $signature->hpp?->nama_pekerjaan ?: '-',
                'step' => $signature->displayRoleLabel(),
                'submitted_at' => $signature->hpp?->submitted_at ?: $signature->created_at,
                'status' => 'Menunggu Tanda Tangan',
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function pendingBast(User $user): Collection
    {
        return LhppBastSignature::query()
            ->with(['lhppBast'])
            ->where('signer_user_id', $user->id)
            ->where('status', LhppBastSignature::STATUS_PENDING)
            ->whereNotNull('token')
            ->where(function (Builder $query): void {
                $query->whereNull('token_expires_at')->orWhere('token_expires_at', '>', now());
            })
            ->whereHas('lhppBast', fn (Builder $query): Builder => $query->where('approval_status', '!=', LhppBast::APPROVAL_REJECTED))
            ->orderByDesc('id')
            ->get()
            ->map(fn (LhppBastSignature $signature): array => [
                'type' => 'bast',
                'type_label' => self::TYPE_LABELS['bast'],
                'id' => $signature->id,
                'number' => trim((string) ($signature->lhppBast?->nomor_order ?: '-').' '.$this->terminLabel($signature->lhppBast?->termin_type)),
                'title' => $signature->lhppBast?->deskripsi_pekerjaan ?: '-',
                'step' => $signature->displayRoleLabel(),
                'submitted_at' => $signature->lhppBast?->updated_at ?: $signature->created_at,
                'status' => 'Menunggu Tanda Tangan',
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function pendingInitialWork(User $user): Collection
    {
        return InitialWorkSignature::query()
            ->with(['initialWork.order'])
            ->where('signer_user_id', $user->id)
            ->where('status', InitialWorkSignature::STATUS_PENDING)
            ->whereNotNull('token_encrypted')
            ->where(function (Builder $query): void {
                $query->whereNull('token_expires_at')->orWhere('token_expires_at', '>', now());
            })
            ->orderByDesc('id')
            ->get()
            ->map(fn (InitialWorkSignature $signature): array => [
                'type' => 'initial_work',
                'type_label' => self::TYPE_LABELS['initial_work'],
                'id' => $signature->id,
                'number' => $signature->initialWork?->nomor_initial_work ?: ($signature->initialWork?->nomor_order ?: '-'),
                'title' => $signature->initialWork?->nama_pekerjaan ?: ($signature->initialWork?->order?->nama_pekerjaan ?: '-'),
                'step' => $signature->displayRoleLabel(),
                'submitted_at' => $signature->initialWork?->tanggal_initial_work ?: $signature->created_at,
                'status' => 'Menunggu Tanda Tangan',
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function pendingQualityControl(User $user): Collection
    {
        return QualityControlSignature::query()
            ->with(['qualityControlReport.order'])
            ->where('signer_user_id', $user->id)
            ->where('status', QualityControlSignature::STATUS_PENDING)
            ->whereNotNull('token_encrypted')
            ->where(function (Builder $query): void {
                $query->whereNull('token_expires_at')->orWhere('token_expires_at', '>', now());
            })
            ->orderByDesc('id')
            ->get()
            ->map(fn (QualityControlSignature $signature): array => [
                'type' => 'quality_control',
                'type_label' => self::TYPE_LABELS['quality_control'],
                'id' => $signature->id,
                'number' => $signature->qualityControlReport?->report_no ?: ($signature->qualityControlReport?->order?->nomor_order ?: '-'),
                'title' => $signature->qualityControlReport?->order?->nama_pekerjaan ?: '-',
                'step' => $signature->displayRoleLabel(),
                'submitted_at' => $signature->qualityControlReport?->report_date ?: $signature->created_at,
                'status' => 'Menunggu Tanda Tangan',
            ]);
    }

    private function findPendingSignatureFor(string $type, int $id, User $user): ?Model
    {
        return match ($type) {
            'hpp' => HppSignature::query()
                ->whereKey($id)
                ->where('signer_user_id', $user->id)
                ->where('status', HppSignature::STATUS_PENDING)
                ->whereNotNull('token')
                ->where(function (Builder $query): void {
                    $query->whereNull('token_expires_at')->orWhere('token_expires_at', '>', now());
                })
                ->whereHas('hpp', fn (Builder $query): Builder => $query->where('status', '!=', Hpp::STATUS_REJECTED))
                ->first(),
            'bast' => LhppBastSignature::query()
                ->whereKey($id)
                ->where('signer_user_id', $user->id)
                ->where('status', LhppBastSignature::STATUS_PENDING)
                ->whereNotNull('token')
                ->where(function (Builder $query): void {
                    $query->whereNull('token_expires_at')->orWhere('token_expires_at', '>', now());
                })
                ->whereHas('lhppBast', fn (Builder $query): Builder => $query->where('approval_status', '!=', LhppBast::APPROVAL_REJECTED))
                ->first(),
            'initial_work' => InitialWorkSignature::query()
                ->whereKey($id)
                ->where('signer_user_id', $user->id)
                ->where('status', InitialWorkSignature::STATUS_PENDING)
                ->whereNotNull('token_encrypted')
                ->where(function (Builder $query): void {
                    $query->whereNull('token_expires_at')->orWhere('token_expires_at', '>', now());
                })
                ->first(),
            'quality_control' => QualityControlSignature::query()
                ->whereKey($id)
                ->where('signer_user_id', $user->id)
                ->where('status', QualityControlSignature::STATUS_PENDING)
                ->whereNotNull('token_encrypted')
                ->where(function (Builder $query): void {
                    $query->whereNull('token_expires_at')->orWhere('token_expires_at', '>', now());
                })
                ->first(),
        };
    }

    private function tokenFor(string $type, Model $signature): ?string
    {
        return match ($type) {
            'hpp', 'bast' => (string) $signature->token,
            'initial_work', 'quality_control' => (string) $signature->token_encrypted,
        };
    }

    private function publicRouteName(string $type): string
    {
        return match ($type) {
            'hpp' => 'approval.hpp.show',
            'bast' => 'approval.bast.show',
            'initial_work' => 'approval.initial-work.show',
            'quality_control' => 'approval.quality-control.show',
        };
    }

    private function terminLabel(?string $termin): string
    {
        return match ($termin) {
            'termin_2' => 'Termin 2',
            'termin_1' => 'Termin 1',
            default => '',
        };
    }
}
