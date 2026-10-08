<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const MAX_VALUE = 9_223_372_036_854_775_807;

    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('current_stock_integer')->nullable();
            $table->unsignedBigInteger('minimum_stock_integer')->nullable();
        });
        Schema::table('inventory_transactions', function (Blueprint $table): void {
            $table->unsignedBigInteger('quantity_integer')->nullable();
            $table->unsignedBigInteger('stock_before_integer')->nullable();
            $table->unsignedBigInteger('stock_after_integer')->nullable();
        });

        try {
            DB::transaction(function (): void {
                DB::table('inventory_items')->orderBy('id')->each(function (object $item): void {
                $isKg = strtoupper(trim((string) $item->unit)) === 'KG';
                $current = $this->convertDecimal((string) $item->current_stock, $isKg, "barang ID {$item->id}, UID {$item->uid}", (string) $item->unit);
                $minimum = $this->convertDecimal((string) $item->minimum_stock, $isKg, "barang ID {$item->id}, UID {$item->uid}", (string) $item->unit);

                DB::table('inventory_items')->where('id', $item->id)->update([
                    'current_stock_integer' => $current,
                    'minimum_stock_integer' => $minimum,
                    'unit' => $isKg ? 'GRAM' : strtoupper(trim((string) $item->unit)),
                ]);

                DB::table('inventory_transactions')
                    ->where('inventory_item_id', $item->id)
                    ->orderBy('id')
                    ->each(function (object $transaction) use ($item, $isKg): void {
                        $context = "transaksi ID {$transaction->id}, barang UID {$item->uid}";
                        DB::table('inventory_transactions')->where('id', $transaction->id)->update([
                            'quantity_integer' => $this->convertDecimal((string) $transaction->quantity, $isKg, $context, (string) $transaction->unit_snapshot),
                            'stock_before_integer' => $this->convertDecimal((string) $transaction->stock_before, $isKg, $context, (string) $transaction->unit_snapshot),
                            'stock_after_integer' => $this->convertDecimal((string) $transaction->stock_after, $isKg, $context, (string) $transaction->unit_snapshot),
                            'unit_snapshot' => $isKg ? 'GRAM' : strtoupper(trim((string) $transaction->unit_snapshot)),
                        ]);
                    });
                });

                if (
                    DB::table('inventory_items')->whereNull('current_stock_integer')->orWhereNull('minimum_stock_integer')->exists()
                    || DB::table('inventory_transactions')->whereNull('quantity_integer')->orWhereNull('stock_before_integer')->orWhereNull('stock_after_integer')->exists()
                ) {
                    throw new \RuntimeException('Konversi stok Inventory gagal: masih terdapat nilai integer kosong.');
                }
            });
        } catch (\Throwable $exception) {
            Schema::table('inventory_items', function (Blueprint $table): void {
                $table->dropColumn(['current_stock_integer', 'minimum_stock_integer']);
            });
            Schema::table('inventory_transactions', function (Blueprint $table): void {
                $table->dropColumn(['quantity_integer', 'stock_before_integer', 'stock_after_integer']);
            });

            throw $exception;
        }

        Schema::table('inventory_items', function (Blueprint $table): void {
            $table->dropColumn(['current_stock', 'minimum_stock']);
        });
        Schema::table('inventory_items', function (Blueprint $table): void {
            $table->renameColumn('current_stock_integer', 'current_stock');
            $table->renameColumn('minimum_stock_integer', 'minimum_stock');
        });
        Schema::table('inventory_transactions', function (Blueprint $table): void {
            $table->dropColumn(['quantity', 'stock_before', 'stock_after']);
        });
        Schema::table('inventory_transactions', function (Blueprint $table): void {
            $table->renameColumn('quantity_integer', 'quantity');
            $table->renameColumn('stock_before_integer', 'stock_before');
            $table->renameColumn('stock_after_integer', 'stock_after');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table): void {
            $table->decimal('current_stock_decimal', 22, 3)->nullable();
            $table->decimal('minimum_stock_decimal', 22, 3)->nullable();
        });
        Schema::table('inventory_transactions', function (Blueprint $table): void {
            $table->decimal('quantity_decimal', 22, 3)->nullable();
            $table->decimal('stock_before_decimal', 22, 3)->nullable();
            $table->decimal('stock_after_decimal', 22, 3)->nullable();
        });

        DB::table('inventory_items')->update([
            'current_stock_decimal' => DB::raw('current_stock'),
            'minimum_stock_decimal' => DB::raw('minimum_stock'),
        ]);
        DB::table('inventory_transactions')->update([
            'quantity_decimal' => DB::raw('quantity'),
            'stock_before_decimal' => DB::raw('stock_before'),
            'stock_after_decimal' => DB::raw('stock_after'),
        ]);

        Schema::table('inventory_items', function (Blueprint $table): void {
            $table->dropColumn(['current_stock', 'minimum_stock']);
        });
        Schema::table('inventory_items', function (Blueprint $table): void {
            $table->renameColumn('current_stock_decimal', 'current_stock');
            $table->renameColumn('minimum_stock_decimal', 'minimum_stock');
        });
        Schema::table('inventory_transactions', function (Blueprint $table): void {
            $table->dropColumn(['quantity', 'stock_before', 'stock_after']);
        });
        Schema::table('inventory_transactions', function (Blueprint $table): void {
            $table->renameColumn('quantity_decimal', 'quantity');
            $table->renameColumn('stock_before_decimal', 'stock_before');
            $table->renameColumn('stock_after_decimal', 'stock_after');
        });
    }

    private function convertDecimal(string $value, bool $isKg, string $context, string $unit): int
    {
        $value = trim($value);
        if (! preg_match('/^(\d+)(?:\.(\d+))?$/', $value, $matches)) {
            throw new \RuntimeException("Nilai stok tidak valid pada {$context}; unit {$unit}; nilai {$value}.");
        }

        $whole = ltrim($matches[1], '0') ?: '0';
        $fraction = rtrim($matches[2] ?? '', '0');
        if (! $isKg && $fraction !== '') {
            throw new \RuntimeException("Pecahan non-KG ditemukan pada {$context}; unit {$unit}; nilai {$value}.");
        }

        if ($isKg) {
            $rawFraction = substr(str_pad($matches[2] ?? '', 3, '0'), 0, 3);
            if (strlen($matches[2] ?? '') > 3 && trim(substr($matches[2], 3), '0') !== '') {
                throw new \RuntimeException("Presisi KG melebihi tiga desimal pada {$context}; unit {$unit}; nilai {$value}.");
            }
            $converted = $whole.str_pad($rawFraction, 3, '0');
        } else {
            $converted = $whole;
        }

        $converted = ltrim($converted, '0') ?: '0';
        if (strlen($converted) > 19 || (strlen($converted) === 19 && strcmp($converted, (string) self::MAX_VALUE) > 0)) {
            throw new \RuntimeException("Nilai stok melebihi kapasitas pada {$context}; unit {$unit}; nilai {$value}.");
        }

        return (int) $converted;
    }
};
