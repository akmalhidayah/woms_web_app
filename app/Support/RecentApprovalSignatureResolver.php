<?php

namespace App\Support;

use App\Models\HppSignature;
use App\Models\InitialWorkSignature;
use App\Models\LhppBastSignature;
use App\Models\QualityControlSignature;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Throwable;

class RecentApprovalSignatureResolver
{
    public function latestDataUrlForUser(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $candidates = $this->candidatesForUser($user)
            ->sortByDesc(fn (array $candidate): int => (int) ($candidate['signed_at']?->timestamp ?? 0))
            ->values();

        foreach ($candidates as $candidate) {
            $dataUrl = $this->toDataUrl($candidate['value']);

            if ($dataUrl !== null) {
                return $dataUrl;
            }
        }

        return null;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{value: string, signed_at: mixed}>
     */
    private function candidatesForUser(User $user): Collection
    {
        return collect()
            ->merge($this->signedSignatureValues(HppSignature::class, HppSignature::STATUS_SIGNED, 'signature_data', $user->id))
            ->merge($this->signedSignatureValues(LhppBastSignature::class, LhppBastSignature::STATUS_SIGNED, 'signature_data', $user->id))
            ->merge($this->signedSignatureValues(InitialWorkSignature::class, InitialWorkSignature::STATUS_SIGNED, 'signature_path', $user->id))
            ->merge($this->signedSignatureValues(QualityControlSignature::class, QualityControlSignature::STATUS_SIGNED, 'signature_data', $user->id));
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $model
     * @return \Illuminate\Support\Collection<int, array{value: string, signed_at: mixed}>
     */
    private function signedSignatureValues(string $model, string $status, string $column, int $userId): Collection
    {
        return $model::query()
            ->where('signer_user_id', $userId)
            ->where('status', $status)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->orderByDesc('signed_at')
            ->get([$column, 'signed_at'])
            ->map(fn ($signature): array => [
                'value' => (string) $signature->{$column},
                'signed_at' => $signature->signed_at,
            ]);
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
