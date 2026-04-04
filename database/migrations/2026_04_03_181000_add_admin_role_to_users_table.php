<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'admin_role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('admin_role')->nullable()->after('role');
            });
        }

        DB::table('users')
            ->where('role', User::ROLE_ADMIN)
            ->whereNull('admin_role')
            ->update(['admin_role' => User::ADMIN_ROLE_SUPER_ADMIN]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'admin_role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('admin_role');
            });
        }
    }
};
