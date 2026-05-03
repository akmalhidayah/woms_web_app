<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lhpp_basts', function (Blueprint $table): void {
            $table->string('approval_status', 20)->default('in_review')->after('quality_control_status')->index();
            $table->string('approval_case')->nullable()->after('approval_status');
            $table->json('approval_flow')->nullable()->after('approval_case');
        });
    }

    public function down(): void
    {
        Schema::table('lhpp_basts', function (Blueprint $table): void {
            $table->dropColumn([
                'approval_status',
                'approval_case',
                'approval_flow',
            ]);
        });
    }
};
