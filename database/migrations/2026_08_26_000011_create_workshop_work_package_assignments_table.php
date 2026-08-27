<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_work_package_assignments', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('work_package_id')
                ->constrained('workshop_work_packages')
                ->cascadeOnDelete();
            // PIC snapshots remain readable even if the master PIC is removed.
            $table->unsignedBigInteger('bengkel_pic_id')->nullable();
            $table->string('pic_name_snapshot', 255);
            $table->string('pic_avatar_path_snapshot', 500)->nullable();
            $table->unsignedTinyInteger('avatar_position_x')->nullable();
            $table->unsignedTinyInteger('avatar_position_y')->nullable();
            $table->json('work_descriptions');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['work_package_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_work_package_assignments');
    }
};
