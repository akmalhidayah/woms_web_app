<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hpp_signatures', function (Blueprint $table) {
            if (! Schema::hasColumn('hpp_signatures', 'approval_note')) {
                $table->text('approval_note')->nullable()->after('signature_data');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hpp_signatures', function (Blueprint $table) {
            if (Schema::hasColumn('hpp_signatures', 'approval_note')) {
                $table->dropColumn('approval_note');
            }
        });
    }
};
