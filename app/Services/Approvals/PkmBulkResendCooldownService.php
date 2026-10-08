<?php

namespace App\Services\Approvals;

use App\Models\PkmBulkResendCooldown;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class PkmBulkResendCooldownService
{
    public const DOCUMENT_HPP = 'hpp';

    public const DOCUMENT_BAST = 'bast';

    public const COOLDOWN_HOURS = 24;

    public function availableAt(string $documentType): ?CarbonImmutable
    {
        $lastSentAt = PkmBulkResendCooldown::query()
            ->where('document_type', $documentType)
            ->value('last_sent_at');
        $availableAt = $this->nextAvailableAt($lastSentAt);

        return $availableAt?->isFuture() ? $availableAt : null;
    }

    /**
     * @return array{allowed: bool, claimed_at: CarbonImmutable|null, available_at: CarbonImmutable}
     */
    public function acquire(string $documentType): array
    {
        $now = CarbonImmutable::instance(now())->startOfSecond();

        PkmBulkResendCooldown::query()->insertOrIgnore([
            'document_type' => $documentType,
            'last_sent_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return DB::transaction(function () use ($documentType, $now): array {
            $cooldown = PkmBulkResendCooldown::query()
                ->where('document_type', $documentType)
                ->lockForUpdate()
                ->firstOrFail();
            $availableAt = $this->nextAvailableAt($cooldown->last_sent_at);

            if ($availableAt?->isFuture()) {
                return [
                    'allowed' => false,
                    'claimed_at' => null,
                    'available_at' => $availableAt,
                ];
            }

            $cooldown->forceFill(['last_sent_at' => $now])->save();

            return [
                'allowed' => true,
                'claimed_at' => $now,
                'available_at' => $now->addHours(self::COOLDOWN_HOURS),
            ];
        }, 3);
    }

    public function release(string $documentType, CarbonInterface $claimedAt): void
    {
        PkmBulkResendCooldown::query()
            ->where('document_type', $documentType)
            ->where('last_sent_at', $claimedAt->format('Y-m-d H:i:s'))
            ->update(['last_sent_at' => null]);
    }

    private function nextAvailableAt(CarbonInterface|string|null $lastSentAt): ?CarbonImmutable
    {
        if (! $lastSentAt) {
            return null;
        }

        return CarbonImmutable::parse($lastSentAt)->addHours(self::COOLDOWN_HOURS);
    }
}
