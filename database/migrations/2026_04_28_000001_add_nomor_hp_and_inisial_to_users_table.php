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
        $hasNomorHp = Schema::hasColumn('users', 'nomor_hp');
        $hasInisial = Schema::hasColumn('users', 'inisial');

        if ($hasNomorHp && $hasInisial) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($hasNomorHp, $hasInisial) {
            if (! $hasNomorHp) {
                $table->string('nomor_hp', 30)->nullable()->after('email');
            }

            if (! $hasInisial) {
                $table->string('inisial', 20)->nullable()->after('nomor_hp');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = array_values(array_filter([
            Schema::hasColumn('users', 'inisial') ? 'inisial' : null,
            Schema::hasColumn('users', 'nomor_hp') ? 'nomor_hp' : null,
        ]));

        if ($columns === []) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
};
