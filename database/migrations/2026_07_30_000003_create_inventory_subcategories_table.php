<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_category_id')
                ->index()
                ->constrained('inventory_categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('code')->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['inventory_category_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_subcategories');
    }
};
