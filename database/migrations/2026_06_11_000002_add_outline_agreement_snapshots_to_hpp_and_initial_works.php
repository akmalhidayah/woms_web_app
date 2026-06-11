<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hpps', function (Blueprint $table) {
            if (! Schema::hasColumn('hpps', 'seksi_pengendali')) {
                $table->string('seksi_pengendali')->nullable()->after('unit_kerja_pengendali');
            }

            if (! Schema::hasColumn('hpps', 'departemen_pengendali')) {
                $table->string('departemen_pengendali')->nullable()->after('seksi_pengendali');
            }

            if (! Schema::hasColumn('hpps', 'departemen_peminta')) {
                $table->string('departemen_peminta')->nullable()->after('unit_kerja');
            }
        });

        Schema::table('initial_works', function (Blueprint $table) {
            if (! Schema::hasColumn('initial_works', 'outline_agreement')) {
                $table->string('outline_agreement')->nullable()->after('outline_agreement_id');
            }

            if (! Schema::hasColumn('initial_works', 'periode_outline_agreement')) {
                $table->string('periode_outline_agreement')->nullable()->after('outline_agreement');
            }

            if (! Schema::hasColumn('initial_works', 'unit_kerja_pengendali')) {
                $table->string('unit_kerja_pengendali')->nullable()->after('unit_work_section_id');
            }

            if (! Schema::hasColumn('initial_works', 'seksi_pengendali')) {
                $table->string('seksi_pengendali')->nullable()->after('unit_kerja_pengendali');
            }

            if (! Schema::hasColumn('initial_works', 'departemen_pengendali')) {
                $table->string('departemen_pengendali')->nullable()->after('seksi_pengendali');
            }
        });

        $this->backfillHppSnapshots();
        $this->backfillInitialWorkSnapshots();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('initial_works', function (Blueprint $table) {
            foreach ([
                'departemen_pengendali',
                'seksi_pengendali',
                'unit_kerja_pengendali',
                'periode_outline_agreement',
                'outline_agreement',
            ] as $column) {
                if (Schema::hasColumn('initial_works', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('hpps', function (Blueprint $table) {
            foreach ([
                'departemen_peminta',
                'departemen_pengendali',
                'seksi_pengendali',
            ] as $column) {
                if (Schema::hasColumn('hpps', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function backfillHppSnapshots(): void
    {
        if (! Schema::hasTable('hpps') || ! Schema::hasTable('outline_agreements')) {
            return;
        }

        $agreements = $this->outlineAgreementSnapshots();
        $departmentByUnit = $this->departmentByUnitName();

        DB::table('hpps')
            ->select([
                'id',
                'outline_agreement_id',
                'unit_kerja',
                'unit_kerja_pengendali',
                'outline_agreement',
                'periode_outline_agreement',
            ])
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($agreements, $departmentByUnit): void {
                foreach ($rows as $row) {
                    $agreement = $agreements->get((int) $row->outline_agreement_id);

                    DB::table('hpps')
                        ->where('id', $row->id)
                        ->update([
                            'departemen_peminta' => $departmentByUnit->get((string) $row->unit_kerja),
                            'unit_kerja_pengendali' => $row->unit_kerja_pengendali ?: ($agreement?->unit_name ?: null),
                            'seksi_pengendali' => $agreement?->jenis_kontrak ?: null,
                            'departemen_pengendali' => $agreement?->department_name ?: null,
                            'outline_agreement' => $row->outline_agreement ?: ($agreement?->nomor_oa ?: null),
                            'periode_outline_agreement' => $row->periode_outline_agreement ?: $this->formatPeriod(
                                $agreement?->current_period_start,
                                $agreement?->current_period_end,
                            ),
                        ]);
                }
            });
    }

    private function backfillInitialWorkSnapshots(): void
    {
        if (! Schema::hasTable('initial_works') || ! Schema::hasTable('outline_agreements')) {
            return;
        }

        $agreements = $this->outlineAgreementSnapshots();

        DB::table('initial_works')
            ->select(['id', 'outline_agreement_id'])
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($agreements): void {
                foreach ($rows as $row) {
                    $agreement = $agreements->get((int) $row->outline_agreement_id);

                    DB::table('initial_works')
                        ->where('id', $row->id)
                        ->update([
                            'outline_agreement' => $agreement?->nomor_oa ?: null,
                            'periode_outline_agreement' => $this->formatPeriod(
                                $agreement?->current_period_start,
                                $agreement?->current_period_end,
                            ),
                            'unit_kerja_pengendali' => $agreement?->unit_name ?: null,
                            'seksi_pengendali' => $agreement?->jenis_kontrak ?: null,
                            'departemen_pengendali' => $agreement?->department_name ?: null,
                        ]);
                }
            });
    }

    private function outlineAgreementSnapshots()
    {
        return DB::table('outline_agreements')
            ->leftJoin('unit_works', 'unit_works.id', '=', 'outline_agreements.unit_work_id')
            ->leftJoin('departments', 'departments.id', '=', 'unit_works.department_id')
            ->select([
                'outline_agreements.id',
                'outline_agreements.nomor_oa',
                'outline_agreements.jenis_kontrak',
                'outline_agreements.current_period_start',
                'outline_agreements.current_period_end',
                'unit_works.name as unit_name',
                'departments.name as department_name',
            ])
            ->get()
            ->keyBy('id');
    }

    private function departmentByUnitName()
    {
        if (! Schema::hasTable('unit_works') || ! Schema::hasTable('departments')) {
            return collect();
        }

        return DB::table('unit_works')
            ->leftJoin('departments', 'departments.id', '=', 'unit_works.department_id')
            ->whereNotNull('unit_works.name')
            ->pluck('departments.name', 'unit_works.name');
    }

    private function formatPeriod(mixed $start, mixed $end): ?string
    {
        if (! $start && ! $end) {
            return null;
        }

        return sprintf(
            '%s - %s',
            $start ? Carbon::parse($start)->format('d/m/Y') : '-',
            $end ? Carbon::parse($end)->format('d/m/Y') : '-',
        );
    }
};
