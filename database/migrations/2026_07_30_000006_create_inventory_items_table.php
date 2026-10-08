<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('item_type')->index();
            $table->foreignId('inventory_location_id')
                ->nullable()
                ->index()
                ->constrained('inventory_locations')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('inventory_category_id')
                ->nullable()
                ->index()
                ->constrained('inventory_categories')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('inventory_subcategory_id')
                ->nullable()
                ->index()
                ->constrained('inventory_subcategories')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('type_category')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('size')->nullable();
            $table->string('unit', 30)->default('EA');
            $table->string('image_disk')->default('public');
            $table->string('image_path')->nullable();
            $table->string('legacy_image_path')->nullable();
            $table->decimal('current_stock', 15, 3)->default(0);
            $table->decimal('minimum_stock', 15, 3)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->string('legacy_source')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['item_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
