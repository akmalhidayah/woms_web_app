<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lhpp_basts', function (Blueprint $table): void {
            $table->string('item_source', 30)
                ->default('manual')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('lhpp_basts', function (Blueprint $table): void {
            $table->dropIndex(['item_source']);
            $table->dropColumn('item_source');
        });
    }
};
