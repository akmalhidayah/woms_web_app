<?php

namespace App\Support;

use App\Models\HppSignature;
use App\Models\InitialWorkSignature;
use App\Models\LhppBastSignature;
use App\Models\QualityControlSignature;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Throwable;

class RecentApprovalSignatureResolver
{
    public function __construct(
        private readonly HppApprovalMarkResolver $hppApprovalMarkResolver,
    ) {}

    public function latestForHppSignature(?User $user, HppSignature $signature): ?string
    {
        if ($this->hppApprovalMarkResolver->requiresInitial($signature)) {
            return $this->latestInitialForHppManager($user);
        }

        return $this->latestFullSignatureForUser($user);
    }

    public function latestInitialForHppManager(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        return $this->firstReadableDataUrl(
            $this->signedSignatureValues(
                HppSignature::class,
                HppSignature::STATUS_SIGNED,
                'signature_data',
                $user->id,
                fn ($query) => $query->whereIn(
                    'role_key',
                    HppApprovalMarkResolver::INITIAL_ROLE_KEYS
                ),
            )
        );
    }

    public function latestFullSignatureForUser(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $candidates = collect()
            ->merge($this->signedSignatureValues(
                HppSignature::class,
                HppSignature::STATUS_SIGNED,
                'signature_data',
                $user->id,
                fn ($query) => $query->whereNotIn(
                    'role_key',
                    HppApprovalMarkResolver::INITIAL_ROLE_KEYS
                ),
            ))
            ->merge($this->signedSignatureValues(LhppBastSignature::class, LhppBastSignature::STATUS_SIGNED, 'signature_data', $user->id))
            ->merge($this->signedSignatureValues(InitialWorkSignature::class, InitialWorkSignature::STATUS_SIGNED, 'signature_path', $user->id))
            ->merge($this->signedSignatureValues(QualityControlSignature::class, QualityControlSignature::STATUS_SIGNED, 'signature_data', $user->id));

        return $this->firstReadableDataUrl($candidates);
    }

    /**
     * @deprecated Gunakan latestForHppSignature() atau latestFullSignatureForUser().
     */
    public function latestDataUrlForUser(?User $user): ?string
    {
        return $this->latestFullSignatureForUser($user);
    }

    /**
     * @param  class-string<Model>  $model
     * @return Collection<int, array{value: string, signed_at: mixed}>
     */
    private function signedSignatureValues(
        string $model,
        string $status,
        string $column,
        int $userId,
        ?callable $scope = null
    ): Collection {
        $query = $model::query()
            ->where('signer_user_id', $userId)
            ->where('status', $status)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->orderByDesc('signed_at');

        if ($scope) {
            $scope($query);
        }

        return $query
            ->limit(25)
            ->get([$column, 'signed_at'])
            ->map(fn ($signature): array => [
                'value' => (string) $signature->{$column},
                'signed_at' => $signature->signed_at,
            ]);
    }

    /**
     * @param  Collection<int, array{value: string, signed_at: mixed}>  $candidates
     */
    private function firstReadableDataUrl(Collection $candidates): ?string
    {
        foreach ($candidates
            ->sortByDesc(fn (array $candidate): int => (int) ($candidate['signed_at']?->timestamp ?? 0))
            ->values() as $candidate) {
            $dataUrl = $this->toDataUrl($candidate['value']);

            if ($dataUrl !== null) {
                return $dataUrl;
            }
        }

        return null;
    }

    private function toDataUrl(?string $value): ?string
    {
        try {
            $source = SignatureImageStorage::imageSource($value);

            if (! $source) {
                return null;
            }

            if (str_starts_with($source, 'data:image')) {
                return $source;
            }

            if (! File::isFile($source) || ! File::isReadable($source)) {
                return null;
            }

            $binary = File::get($source);

            if ($binary === '') {
                return null;
            }

            $mime = File::mimeType($source) ?: 'image/png';

            if (! str_starts_with($mime, 'image/')) {
                return null;
            }

            return 'data:'.$mime.';base64,'.base64_encode($binary);
        } catch (Throwable) {
            return null;
        }
    }
}
