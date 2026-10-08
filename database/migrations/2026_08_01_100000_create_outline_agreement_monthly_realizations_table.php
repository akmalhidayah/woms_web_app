<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = 'outline_agreement_monthly_realizations';
        $parentTableEngine = $this->mysqlTableEngine('outline_agreements');

        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) use ($parentTableEngine) {
                if ($parentTableEngine !== null) {
                    $table->engine = $parentTableEngine;
                }

                $table->id();
                $table->foreignId('outline_agreement_id');
                $table->unsignedSmallInteger('year');
                $table->unsignedTinyInteger('month');
                $table->unsignedBigInteger('pr_po_amount')->default(0);
                $table->unsignedBigInteger('urgent_amount')->default(0);
                $table->timestamps();

                $table->foreign('outline_agreement_id', 'oa_monthly_realizations_oa_fk')
                    ->references('id')
                    ->on('outline_agreements')
                    ->cascadeOnDelete();
                $table->unique(
                    ['outline_agreement_id', 'year', 'month'],
                    'oa_monthly_realizations_period_unique'
                );
                $table->index(['year', 'month'], 'oa_monthly_realizations_period_index');
            });

            return;
        }

        $this->alignMysqlTableEngineWithParent($tableName, $parentTableEngine);

        $hasOutlineAgreementForeignKey = collect(Schema::getForeignKeys($tableName))
            ->contains(fn (array $foreignKey): bool => $foreignKey['columns'] === ['outline_agreement_id']);

        if (! $hasOutlineAgreementForeignKey) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreign('outline_agreement_id', 'oa_monthly_realizations_oa_fk')
                    ->references('id')
                    ->on('outline_agreements')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasIndex($tableName, 'oa_monthly_realizations_period_unique')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unique(
                    ['outline_agreement_id', 'year', 'month'],
                    'oa_monthly_realizations_period_unique'
                );
            });
        }

        if (! Schema::hasIndex($tableName, 'oa_monthly_realizations_period_index')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index(['year', 'month'], 'oa_monthly_realizations_period_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outline_agreement_monthly_realizations');
    }

    private function alignMysqlTableEngineWithParent(string $tableName, ?string $parentTableEngine): void
    {
        if ($parentTableEngine === null) {
            return;
        }

        $tableEngine = $this->mysqlTableEngine($tableName);

        if ($tableEngine === null || strcasecmp($tableEngine, $parentTableEngine) === 0) {
            return;
        }

        Schema::getConnection()->statement(
            sprintf('ALTER TABLE `%s` ENGINE = %s', $tableName, $parentTableEngine)
        );
    }

    private function mysqlTableEngine(string $tableName): ?string
    {
        $connection = Schema::getConnection();

        if (! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            return null;
        }

        $table = $connection->selectOne(
            'SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$connection->getDatabaseName(), $tableName]
        );
        $engine = is_string($table?->engine ?? null) ? $table->engine : null;

        if ($engine === null || preg_match('/\A[A-Za-z0-9_]+\z/', $engine) !== 1) {
            return null;
        }

        return $engine;
    }
};
