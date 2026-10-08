<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_work_packages', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            // orders is MyISAM in production; deliberately no foreign key here.
            $table->unsignedBigInteger('order_id');
            $table->unsignedInteger('sequence');
            $table->string('display_no', 150);
            $table->string('job_name', 255);
            $table->text('description')->nullable();
            $table->date('target_date')->nullable();
            $table->string('status', 30)->default('not_started');
            $table->text('pending_reason')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index(['order_id', 'status']);
            $table->unique(['order_id', 'sequence']);
            $table->unique('display_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_work_packages');
    }
};
