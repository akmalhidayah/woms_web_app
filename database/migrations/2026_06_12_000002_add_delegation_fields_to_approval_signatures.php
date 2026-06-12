<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->signatureTables() as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('acting_as_label')->nullable()->after('role_label');
                $table->foreignId('delegated_from_user_id')->nullable()->after('signer_user_id')->constrained('users')->nullOnDelete();
                $table->string('delegated_from_name')->nullable()->after('delegated_from_user_id');
                $table->foreignId('delegated_by_user_id')->nullable()->after('delegated_from_name')->constrained('users')->nullOnDelete();
                $table->timestamp('delegated_at')->nullable()->after('delegated_by_user_id');
                $table->text('delegation_reason')->nullable()->after('delegated_at');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->signatureTables() as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign([$tableName === 'hpp_signatures' ? 'delegated_from_user_id' : 'delegated_from_user_id']);
                $table->dropForeign([$tableName === 'hpp_signatures' ? 'delegated_by_user_id' : 'delegated_by_user_id']);
                $table->dropColumn([
                    'acting_as_label',
                    'delegated_from_user_id',
                    'delegated_from_name',
                    'delegated_by_user_id',
                    'delegated_at',
                    'delegation_reason',
                ]);
            });
        }
    }

    /**
     * @return list<string>
     */
    private function signatureTables(): array
    {
        return [
            'initial_work_signatures',
            'hpp_signatures',
            'quality_control_signatures',
            'lhpp_bast_signatures',
        ];
    }
};
