<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bengkel_tasks', function (Blueprint $table): void {
            $table->id();
            $table->string('job_name');
            $table->string('notification_number', 50)->nullable()->index();
            $table->string('unit_work')->nullable()->index();
            $table->string('seksi')->nullable()->index();
            $table->date('usage_plan_date')->nullable();
            $table->string('catatan')->nullable()->index();
            $table->json('person_in_charge')->nullable();
            $table->json('person_in_charge_profiles')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bengkel_tasks');
    }
};
