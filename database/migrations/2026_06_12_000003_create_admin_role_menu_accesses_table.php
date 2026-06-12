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
        Schema::create('admin_role_menu_accesses', function (Blueprint $table) {
            $table->id();
            $table->string('admin_role');
            $table->string('menu_key');
            $table->timestamps();

            $table->unique(['admin_role', 'menu_key']);
        });

        if (! Schema::hasTable('admin_menu_accesses')) {
            return;
        }

        $legacyMenuKeys = DB::table('admin_menu_accesses')
            ->join('users', 'admin_menu_accesses.user_id', '=', 'users.id')
            ->where('users.role', User::ROLE_ADMIN)
            ->where(function ($query) {
                $query
                    ->where('users.admin_role', User::ADMIN_ROLE_ADMIN)
                    ->orWhereNull('users.admin_role');
            })
            ->distinct()
            ->pluck('admin_menu_accesses.menu_key');

        foreach ($legacyMenuKeys as $menuKey) {
            DB::table('admin_role_menu_accesses')->insertOrIgnore([
                'admin_role' => User::ADMIN_ROLE_ADMIN,
                'menu_key' => $menuKey,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_role_menu_accesses');
    }
};
