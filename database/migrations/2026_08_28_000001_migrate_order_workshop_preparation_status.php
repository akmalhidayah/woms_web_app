<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PREPARATION_STATUS_INDEX = 'ow_preparation_status_idx';

    public function up(): void
    {
        $this->normalizeLegacyPreparationValues();

        Schema::table('order_workshops', function (Blueprint $table): void {
            if (Schema::hasColumn('order_workshops', 'konfirmasi_anggaran')
                && ! Schema::hasColumn('order_workshops', 'preparation_status')) {
                $table->renameColumn('konfirmasi_anggaran', 'preparation_status');
            }

            if (Schema::hasColumn('order_workshops', 'keterangan_konfirmasi')
                && ! Schema::hasColumn('order_workshops', 'preparation_note')) {
                $table->renameColumn('keterangan_konfirmasi', 'preparation_note');
            }
        });

        Schema::table('order_workshops', function (Blueprint $table): void {
            if (Schema::hasColumn('order_workshops', 'preparation_status')) {
                $table->index('preparation_status', self::PREPARATION_STATUS_INDEX);
            }

            foreach ([
                'status_anggaran',
                'keterangan_anggaran',
                'status_material',
                'keterangan_material',
                'nomor_e_korin',
                'status_e_korin',
            ] as $column) {
                if (Schema::hasColumn('order_workshops', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_workshops', function (Blueprint $table): void {
            if (Schema::hasColumn('order_workshops', 'preparation_status')) {
                $table->dropIndex(self::PREPARATION_STATUS_INDEX);
            }

            foreach ([
                'status_anggaran' => ['string', true],
                'keterangan_anggaran' => ['text', true],
                'status_material' => ['string', true],
                'keterangan_material' => ['text', true],
                'nomor_e_korin' => ['string', true],
                'status_e_korin' => ['string', true],
            ] as $column => [$type, $nullable]) {
                if (! Schema::hasColumn('order_workshops', $column)) {
                    $definition = $table->{$type}($column);
                    if ($nullable) {
                        $definition->nullable();
                    }
                }
            }

            if (Schema::hasColumn('order_workshops', 'preparation_status')
                && ! Schema::hasColumn('order_workshops', 'konfirmasi_anggaran')) {
                $table->renameColumn('preparation_status', 'konfirmasi_anggaran');
            }

            if (Schema::hasColumn('order_workshops', 'preparation_note')
                && ! Schema::hasColumn('order_workshops', 'keterangan_konfirmasi')) {
                $table->renameColumn('preparation_note', 'keterangan_konfirmasi');
            }
        });
    }

    private function normalizeLegacyPreparationValues(): void
    {
        if (! Schema::hasTable('order_workshops') || ! Schema::hasColumn('order_workshops', 'konfirmasi_anggaran')) {
            return;
        }

        $validStatuses = [
            'waiting_budget_confirmation',
            'waiting_material',
            'waiting_budget_transfer',
            'completed',
        ];

        DB::table('order_workshops')
            ->where('konfirmasi_anggaran', 'Material Ready')
            ->whereNotNull('status_material')
            ->whereRaw("TRIM(status_material) <> ''")
            ->update(['konfirmasi_anggaran' => 'completed']);

        DB::table('order_workshops')
            ->where('konfirmasi_anggaran', 'Material Ready')
            ->where(function ($query): void {
                $query->whereNull('status_material')->orWhereRaw("TRIM(status_material) = ''");
            })
            ->update(['konfirmasi_anggaran' => 'waiting_material']);

        DB::table('order_workshops')
            ->where('konfirmasi_anggaran', 'Material Not Ready')
            ->where('status_anggaran', 'Complete Transfer')
            ->update(['konfirmasi_anggaran' => 'completed']);

        DB::table('order_workshops')
            ->where('konfirmasi_anggaran', 'Material Not Ready')
            ->where('status_anggaran', 'Waiting Budget')
            ->update(['konfirmasi_anggaran' => 'waiting_budget_transfer']);

        DB::table('order_workshops')
            ->where('konfirmasi_anggaran', 'Material Not Ready')
            ->where(function ($query): void {
                $query->whereNull('status_anggaran')->orWhereRaw("TRIM(status_anggaran) = ''");
            })
            ->update(['konfirmasi_anggaran' => 'waiting_budget_confirmation']);

        DB::table('order_workshops')
            ->whereNotIn('konfirmasi_anggaran', $validStatuses)
            ->update(['konfirmasi_anggaran' => null]);
    }
};
