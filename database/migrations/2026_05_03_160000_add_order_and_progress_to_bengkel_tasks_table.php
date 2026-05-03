<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bengkel_tasks', function (Blueprint $table): void {
            $table->foreignId('order_id')
                ->nullable()
                ->after('id')
                ->constrained('orders')
                ->nullOnDelete();
            $table->string('progress_status')
                ->nullable()
                ->after('is_completed');
        });

        DB::table('bengkel_tasks')
            ->orderBy('id')
            ->get(['id', 'notification_number', 'is_completed'])
            ->each(function ($task): void {
                $notification = trim((string) ($task->notification_number ?? ''));

                if ($notification === '') {
                    DB::table('bengkel_tasks')
                        ->where('id', $task->id)
                        ->update([
                            'progress_status' => $task->is_completed ? 'done' : 'menunggu_jadwal',
                        ]);

                    return;
                }

                $order = DB::table('orders')
                    ->where('nomor_order', $notification)
                    ->orWhere('notifikasi', $notification)
                    ->first(['id']);

                $workshop = $order
                    ? DB::table('order_workshops')
                        ->where('order_id', $order->id)
                        ->first(['progress_status'])
                    : null;

                DB::table('bengkel_tasks')
                    ->where('id', $task->id)
                    ->update([
                        'order_id' => $order?->id,
                        'progress_status' => $workshop?->progress_status ?: ($task->is_completed ? 'done' : 'menunggu_jadwal'),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('bengkel_tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('order_id');
            $table->dropColumn('progress_status');
        });
    }
};
