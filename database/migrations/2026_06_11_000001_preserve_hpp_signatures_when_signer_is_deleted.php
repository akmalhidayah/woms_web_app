<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropForeignKeyIfExists('hpp_signatures', 'signer_user_id');

        Schema::table('hpp_signatures', function (Blueprint $table): void {
            $table->unsignedBigInteger('signer_user_id')->nullable()->change();
        });

        if (! $this->hasForeignKey('hpp_signatures', 'signer_user_id')) {
            Schema::table('hpp_signatures', function (Blueprint $table): void {
                $table->foreign('signer_user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $this->dropForeignKeyIfExists('hpp_signatures', 'signer_user_id');

        Schema::table('hpp_signatures', function (Blueprint $table): void {
            $table->foreign('signer_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    private function dropForeignKeyIfExists(string $table, string $column): void
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::table($table, function (Blueprint $blueprint) use ($column): void {
                $blueprint->dropForeign([$column]);
            });

            return;
        }

        $foreignKeyNames = $this->foreignKeyNames($table, $column);

        if ($foreignKeyNames === []) {
            return;
        }

        foreach ($foreignKeyNames as $foreignKeyName) {
            Schema::table($table, function (Blueprint $blueprint) use ($foreignKeyName): void {
                $blueprint->dropForeign($foreignKeyName);
            });
        }
    }

    private function hasForeignKey(string $table, string $column): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        return $this->foreignKeyNames($table, $column) !== [];
    }

    /**
     * @return list<string>
     */
    private function foreignKeyNames(string $table, string $column): array
    {
        return collect(DB::select(
            <<<'SQL'
                SELECT CONSTRAINT_NAME AS constraint_name
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                    AND COLUMN_NAME = ?
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                SQL,
            [$table, $column],
        ))
            ->map(fn (object $row): string => (string) $row->constraint_name)
            ->filter()
            ->values()
            ->all();
    }
};
