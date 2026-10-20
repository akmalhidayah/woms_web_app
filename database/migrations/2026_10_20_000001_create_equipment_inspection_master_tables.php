<?php

use App\Support\Inspector\LegacyEquipmentInspectionFormBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('equipment_inspection_forms')) {
            Schema::create('equipment_inspection_forms', function (Blueprint $table): void {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('image_path')->nullable();
                $table->string('image_mime_type', 64)->nullable();
                $table->unsignedInteger('image_size')->nullable();
                $table->char('image_sha256', 64)->nullable();
                $table->string('status', 16)->default('draft');
                $table->unsignedInteger('sort_order')->default(0);
                $table->unsignedInteger('version')->default(1);
                $table->boolean('is_legacy')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['status', 'sort_order', 'id'], 'equipment_form_catalog_index');
            });
        }

        if (! Schema::hasTable('equipment_inspection_form_items')) {
            Schema::create('equipment_inspection_form_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('equipment_inspection_form_id');
                $table->foreign('equipment_inspection_form_id', 'equipment_form_item_form_fk')
                    ->references('id')->on('equipment_inspection_forms')->cascadeOnDelete();
                $table->string('item_key', 191);
                $table->string('label');
                $table->string('group_key', 191);
                $table->string('group_name')->nullable();
                $table->unsignedInteger('sort_order');
                $table->timestamps();
                $table->unique(['equipment_inspection_form_id', 'item_key'], 'equipment_form_item_key_unique');
                $table->index(['equipment_inspection_form_id', 'sort_order', 'id'], 'equipment_form_item_order_index');
            });
        }

        if (! Schema::hasTable('equipment_inspection_form_logs')) {
            Schema::create('equipment_inspection_form_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('equipment_inspection_form_id');
                $table->foreign('equipment_inspection_form_id', 'equipment_form_log_form_fk')
                    ->references('id')->on('equipment_inspection_forms')->restrictOnDelete();
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event', 64);
                $table->unsignedInteger('form_version');
                $table->json('metadata')->nullable();
                $table->timestamp('created_at');
                $table->index(['equipment_inspection_form_id', 'created_at'], 'equipment_form_log_history_index');
            });
        }

        app(LegacyEquipmentInspectionFormBackfill::class)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_inspection_form_logs');
        Schema::dropIfExists('equipment_inspection_form_items');
        Schema::dropIfExists('equipment_inspection_forms');
    }
};
