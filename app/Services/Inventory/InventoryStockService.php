<?php

namespace App\Services\Inventory;

use App\Exceptions\Inventory\InactiveInventoryItemException;
use App\Exceptions\Inventory\InsufficientInventoryStockException;
use App\Exceptions\Inventory\InvalidInventoryActorException;
use App\Exceptions\Inventory\InvalidInventoryRequestTypeException;
use App\Exceptions\Inventory\InvalidStockQuantityException;
use App\Exceptions\Inventory\InventoryStockOverflowException;
use App\Exceptions\Inventory\OpeningBalanceAlreadyExistsException;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryRequestType;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\InventoryUser;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class InventoryStockService
{
    public const TYPE_OPENING_BALANCE = 'opening_balance';

    public const TYPE_STOCK_IN = 'stock_in';

    public const TYPE_STOCK_OUT = 'stock_out';

    public const TYPE_ADJUSTMENT_IN = 'adjustment_in';

    public const TYPE_ADJUSTMENT_OUT = 'adjustment_out';

    private const MAX_MINOR_UNIT = 999_999_999_999_999;

    private const ALLOWED_CONTEXT_KEYS = [
        'inventory_request_type_id',
        'purpose',
        'notes',
        'reference_number',
        'transaction_at',
        'legacy_id',
        'legacy_payload',
        'source',
    ];

    /**
     * Create the first stock record for an item that has no stock history.
     */
    public function createOpeningBalance(
        InventoryItem $item,
        int|float|string $quantity,
        User|InventoryUser|null $actor = null,
        array $context = []
    ): InventoryTransaction {
        $this->assertKnownContext($context);
        $this->assertOpeningBalanceActor($actor);

        return $this->execute(
            item: $item,
            quantity: $quantity,
            transactionType: self::TYPE_OPENING_BALANCE,
            actor: $actor,
            context: $context,
            operation: 'opening',
        );
    }

    /**
     * Add stock through an authenticated WOMS administrator.
     */
    public function stockIn(
        InventoryItem $item,
        int|float|string $quantity,
        User $actor,
        array $context = []
    ): InventoryTransaction {
        $this->assertKnownContext($context);
        $this->assertWomsAdmin($actor);

        return $this->execute($item, $quantity, self::TYPE_STOCK_IN, $actor, $context, 'add');
    }

    /**
     * Remove stock through a WOMS administrator or active Flutter inventory user.
     */
    public function stockOut(
        InventoryItem $item,
        int|float|string $quantity,
        User|InventoryUser $actor,
        array $context = []
    ): InventoryTransaction {
        $this->assertKnownContext($context);
        $this->assertStockOutActor($actor);

        if ($actor instanceof InventoryUser) {
            $this->assertFlutterStockOutContext($context);
        }

        return $this->execute($item, $quantity, self::TYPE_STOCK_OUT, $actor, $context, 'subtract');
    }

    /**
     * Correct stock upward through an authenticated WOMS administrator.
     */
    public function adjustmentIn(
        InventoryItem $item,
        int|float|string $quantity,
        User $actor,
        string $reason,
        array $context = []
    ): InventoryTransaction {
        $this->assertKnownContext($context);
        $this->assertWomsAdmin($actor);
        $context['notes'] = $this->normalizeReason($reason);

        return $this->execute($item, $quantity, self::TYPE_ADJUSTMENT_IN, $actor, $context, 'add');
    }

    /**
     * Correct stock downward through an authenticated WOMS administrator.
     */
    public function adjustmentOut(
        InventoryItem $item,
        int|float|string $quantity,
        User $actor,
        string $reason,
        array $context = []
    ): InventoryTransaction {
        $this->assertKnownContext($context);
        $this->assertWomsAdmin($actor);
        $context['notes'] = $this->normalizeReason($reason);

        return $this->execute($item, $quantity, self::TYPE_ADJUSTMENT_OUT, $actor, $context, 'subtract');
    }

    private function execute(
        InventoryItem $item,
        int|float|string $quantity,
        string $transactionType,
        User|InventoryUser|null $actor,
        array $context,
        string $operation,
    ): InventoryTransaction {
        $quantityMinor = $this->decimalToMinorUnit($quantity);

        if ($quantityMinor <= 0) {
            throw new InvalidStockQuantityException;
        }

        return DB::transaction(function () use (
            $item,
            $quantityMinor,
            $transactionType,
            $actor,
            $context,
            $operation,
        ): InventoryTransaction {
            $lockedItem = InventoryItem::query()
                ->lockForUpdate()
                ->findOrFail($item->getKey());

            if (! $lockedItem->is_active) {
                throw new InactiveInventoryItemException;
            }

            $stockBeforeMinor = $this->decimalToMinorUnit($lockedItem->current_stock, allowZero: true);

            if ($operation === 'opening') {
                if (
                    $stockBeforeMinor !== 0
                    || InventoryTransaction::query()->where('inventory_item_id', $lockedItem->getKey())->exists()
                ) {
                    throw new OpeningBalanceAlreadyExistsException;
                }

                $stockAfterMinor = $quantityMinor;
            } elseif ($operation === 'add') {
                $stockAfterMinor = $this->addStock($stockBeforeMinor, $quantityMinor);
            } else {
                $stockAfterMinor = $this->subtractStock($lockedItem, $stockBeforeMinor, $quantityMinor);
            }

            $stockBefore = $this->minorUnitToDecimal($stockBeforeMinor);
            $stockAfter = $this->minorUnitToDecimal($stockAfterMinor);
            $normalizedQuantity = $this->minorUnitToDecimal($quantityMinor);

            $lockedItem->update(['current_stock' => $stockAfter]);

            [$inventoryUserId, $womsUserId, $source] = $this->resolveActor($actor, $context, $transactionType);

            $transaction = InventoryTransaction::query()->create([
                'transaction_number' => $this->transactionNumber($transactionType),
                'inventory_item_id' => $lockedItem->getKey(),
                'inventory_user_id' => $inventoryUserId,
                'woms_user_id' => $womsUserId,
                'inventory_request_type_id' => $context['inventory_request_type_id'] ?? null,
                'transaction_type' => $transactionType,
                'quantity' => $normalizedQuantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'purpose' => $this->nullableText($context['purpose'] ?? null),
                'notes' => $this->nullableText($context['notes'] ?? null),
                'reference_number' => $this->nullableText($context['reference_number'] ?? null),
                'source' => $source,
                'item_uid_snapshot' => $lockedItem->uid,
                'item_name_snapshot' => $lockedItem->name,
                'unit_snapshot' => $lockedItem->unit,
                'transaction_at' => $this->transactionAt($context, $transactionType),
                'legacy_id' => $this->nullableText($context['legacy_id'] ?? null),
                'legacy_payload' => $this->legacyPayload($context),
            ]);

            return $transaction->load(['item', 'inventoryUser', 'womsUser', 'requestType']);
        });
    }

    private function normalizeQuantity(int|float|string $quantity, bool $allowZero = false): string
    {
        return $this->minorUnitToDecimal($this->decimalToMinorUnit($quantity, $allowZero));
    }

    private function decimalToMinorUnit(int|float|string $quantity, bool $allowZero = false): int
    {
        if (is_float($quantity)) {
            if (! is_finite($quantity)) {
                throw new InvalidStockQuantityException('Jumlah transaksi harus berupa angka yang valid.');
            }

            if ($quantity > self::MAX_MINOR_UNIT / 1000) {
                throw new InventoryStockOverflowException;
            }

            $scaled = $quantity * 1000;
            if (abs($scaled - round($scaled)) > 0.0000001) {
                throw new InvalidStockQuantityException('Jumlah transaksi maksimal memiliki tiga angka desimal.');
            }

            $minorUnit = (int) round($scaled);
        } else {
            $value = trim((string) $quantity);

            if (! preg_match('/^\+?(\d+)(?:\.(\d{1,3}))?$/', $value, $matches)) {
                throw new InvalidStockQuantityException('Jumlah transaksi harus berupa angka dengan maksimal tiga angka desimal.');
            }

            $whole = ltrim($matches[1], '0');
            $whole = $whole === '' ? '0' : $whole;
            $fraction = str_pad($matches[2] ?? '', 3, '0');

            if (strlen($whole) > 12) {
                throw new InventoryStockOverflowException;
            }

            $minorUnit = ((int) $whole * 1000) + (int) $fraction;
        }

        if ($minorUnit > self::MAX_MINOR_UNIT) {
            throw new InventoryStockOverflowException;
        }

        if ($minorUnit < 0 || (! $allowZero && $minorUnit === 0)) {
            throw new InvalidStockQuantityException;
        }

        return $minorUnit;
    }

    private function minorUnitToDecimal(int $minorUnit): string
    {
        return sprintf('%d.%03d', intdiv($minorUnit, 1000), $minorUnit % 1000);
    }

    private function addStock(int $stockMinor, int $quantityMinor): int
    {
        if ($quantityMinor > self::MAX_MINOR_UNIT - $stockMinor) {
            throw new InventoryStockOverflowException;
        }

        return $stockMinor + $quantityMinor;
    }

    private function subtractStock(InventoryItem $item, int $stockMinor, int $quantityMinor): int
    {
        if ($this->compareStock($stockMinor, $quantityMinor) < 0) {
            throw new InsufficientInventoryStockException(
                $this->minorUnitToDecimal($stockMinor),
                $item->unit,
            );
        }

        return $stockMinor - $quantityMinor;
    }

    private function compareStock(int $leftMinor, int $rightMinor): int
    {
        return $leftMinor <=> $rightMinor;
    }

    private function assertOpeningBalanceActor(User|InventoryUser|null $actor): void
    {
        if ($actor instanceof InventoryUser) {
            throw new InvalidInventoryActorException('Opening balance hanya dapat dibuat oleh admin WOMS atau proses sistem.');
        }

        if ($actor instanceof User) {
            $this->assertWomsAdmin($actor);
        }
    }

    private function assertWomsAdmin(User $actor): void
    {
        if (! $actor->exists || ! $actor->isAdmin()) {
            throw new InvalidInventoryActorException('Transaksi ini hanya dapat dilakukan oleh admin WOMS.');
        }
    }

    private function assertStockOutActor(User|InventoryUser $actor): void
    {
        if ($actor instanceof User) {
            $this->assertWomsAdmin($actor);

            return;
        }

        if (! $actor->exists || $actor->trashed() || ! $actor->is_active) {
            throw new InvalidInventoryActorException('User Inventory tidak aktif dan tidak dapat melakukan transaksi.');
        }
    }

    private function assertFlutterStockOutContext(array $context): void
    {
        $requestTypeId = $context['inventory_request_type_id'] ?? null;
        $purpose = $this->nullableText($context['purpose'] ?? null);

        if (! is_int($requestTypeId) && ! (is_string($requestTypeId) && ctype_digit($requestTypeId))) {
            throw new InvalidInventoryRequestTypeException('Jenis permintaan wajib dipilih untuk stock out dari Flutter.');
        }

        $requestType = InventoryRequestType::query()->find((int) $requestTypeId);
        if (! $requestType?->is_active) {
            throw new InvalidInventoryRequestTypeException;
        }

        if ($purpose === null) {
            throw new InvalidInventoryRequestTypeException('Tujuan penggunaan wajib diisi untuk stock out dari Flutter.');
        }
    }

    private function resolveActor(
        User|InventoryUser|null $actor,
        array $context,
        string $transactionType,
    ): array {
        if ($actor instanceof User) {
            return [null, $actor->getKey(), 'woms_admin'];
        }

        if ($actor instanceof InventoryUser) {
            return [$actor->getKey(), null, 'flutter'];
        }

        $source = $context['source'] ?? 'system';
        $allowedSources = ['system', 'seeder', 'import'];

        if ($transactionType !== self::TYPE_OPENING_BALANCE || ! in_array($source, $allowedSources, true)) {
            throw new InvalidInventoryActorException('Transaksi tanpa actor hanya diizinkan untuk opening balance sistem, seeder, atau import.');
        }

        return [null, null, $source];
    }

    private function transactionNumber(string $transactionType): string
    {
        $prefix = match ($transactionType) {
            self::TYPE_OPENING_BALANCE => 'OPEN',
            self::TYPE_STOCK_IN => 'IN',
            self::TYPE_STOCK_OUT => 'OUT',
            self::TYPE_ADJUSTMENT_IN => 'ADJIN',
            self::TYPE_ADJUSTMENT_OUT => 'ADJOUT',
        };

        return sprintf('INV-%s-%s-%s', $prefix, now()->format('Ymd'), Str::ulid());
    }

    private function transactionAt(array $context, string $transactionType): CarbonInterface
    {
        if ($transactionType !== self::TYPE_OPENING_BALANCE || ! array_key_exists('transaction_at', $context)) {
            return now();
        }

        $value = $context['transaction_at'];

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException('Tanggal transaksi opening balance tidak valid.');
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Tanggal transaksi opening balance tidak valid.', previous: $exception);
        }
    }

    private function legacyPayload(array $context): ?array
    {
        $payload = $context['legacy_payload'] ?? null;

        if ($payload !== null && ! is_array($payload)) {
            throw new InvalidArgumentException('Legacy payload harus berupa array atau null.');
        }

        return $payload;
    }

    private function normalizeReason(string $reason): string
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('Alasan adjustment wajib diisi.');
        }

        return $reason;
    }

    private function nullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_scalar($value) && ! $value instanceof \Stringable) {
            throw new InvalidArgumentException('Nilai context transaksi tidak valid.');
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function assertKnownContext(array $context): void
    {
        $unknownKeys = array_diff(array_keys($context), self::ALLOWED_CONTEXT_KEYS);

        if ($unknownKeys !== []) {
            throw new InvalidArgumentException('Context transaksi tidak dikenal: '.implode(', ', $unknownKeys).'.');
        }
    }
}
