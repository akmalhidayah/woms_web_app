<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('equipment_inspection_units')) {
            Schema::create('equipment_inspection_units', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('equipment_inspection_form_id');
                $table->foreign('equipment_inspection_form_id', 'equipment_unit_form_fk')
                    ->references('id')->on('equipment_inspection_forms')->restrictOnDelete();
                $table->string('asset_code', 100)->unique();
                $table->string('name', 150);
                $table->string('location', 255)->nullable();
                $table->text('description')->nullable();
                $table->string('image_path')->nullable();
                $table->string('image_mime_type', 64)->nullable();
                $table->unsignedInteger('image_size')->nullable();
                $table->char('image_sha256', 64)->nullable();
                $table->char('public_code', 32)->unique();
                $table->string('lifecycle_status', 16)->default('active');
                $table->boolean('public_enabled')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['equipment_inspection_form_id', 'lifecycle_status', 'sort_order'], 'equipment_unit_catalog_index');
                $table->index(['public_enabled', 'lifecycle_status'], 'equipment_unit_public_index');
                $table->index('location', 'equipment_unit_location_index');
            });
        }

        if (! Schema::hasTable('equipment_inspection_unit_links')) {
            Schema::create('equipment_inspection_unit_links', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('equipment_inspection_unit_id');
                $table->foreign('equipment_inspection_unit_id', 'equipment_unit_link_unit_fk')
                    ->references('id')->on('equipment_inspection_units')->restrictOnDelete();
                $table->foreignId('equipment_inspection_id')->unique();
                $table->foreign('equipment_inspection_id', 'equipment_unit_link_inspection_fk')
                    ->references('id')->on('equipment_inspections')->restrictOnDelete();
                $table->string('link_source', 24);
                $table->string('unit_code_snapshot', 100);
                $table->string('unit_name_snapshot', 150);
                $table->foreignId('linked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('linked_at');
                $table->timestamps();
                $table->index(['equipment_inspection_unit_id', 'link_source'], 'equipment_unit_link_history_index');
            });
        }

        if (! Schema::hasTable('equipment_inspection_unit_logs')) {
            Schema::create('equipment_inspection_unit_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('equipment_inspection_unit_id');
                $table->foreign('equipment_inspection_unit_id', 'equipment_unit_log_unit_fk')
                    ->references('id')->on('equipment_inspection_units')->restrictOnDelete();
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event', 64);
                $table->json('metadata')->nullable();
                $table->timestamp('created_at');
                $table->index(['equipment_inspection_unit_id', 'created_at'], 'equipment_unit_log_history_index');
            });
        }

        if (! Schema::hasIndex('equipment_inspections', 'equipment_public_history_index')) {
            Schema::table('equipment_inspections', function (Blueprint $table): void {
                $table->index(['status', 'deleted_at', 'inspection_date', 'id'], 'equipment_public_history_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('equipment_inspections', 'equipment_public_history_index')) {
            Schema::table('equipment_inspections', function (Blueprint $table): void {
                $table->dropIndex('equipment_public_history_index');
            });
        }
        Schema::dropIfExists('equipment_inspection_unit_logs');
        Schema::dropIfExists('equipment_inspection_unit_links');
        Schema::dropIfExists('equipment_inspection_units');
    }
};
