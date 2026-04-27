<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hpp_approval_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('planner_control_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('counter_part_unit_work_id')->nullable()->constrained('unit_works')->nullOnDelete();
            $table->foreignId('counter_part_section_id')->nullable()->constrained('unit_work_sections')->nullOnDelete();

            $table->foreignId('dirops_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hpp_approval_settings');
    }
};