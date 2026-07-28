<?php

namespace Tests\Feature\Admin\Maintenance;

use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\User;
use App\Services\Maintenance\Evaluators\FileStorageHealthEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaintenanceDeepScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_deep_scan_reports_missing_file_without_deleting_existing_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('orders/existing.pdf', 'pdf');
        $user = User::factory()->create();
        $order = Order::query()->create([
            'nomor_order' => 'MAINT-FILE-001',
            'nama_pekerjaan' => 'Maintenance File Test',
            'unit_kerja' => 'Workshop',
            'seksi' => 'Machine Workshop',
            'deskripsi' => 'Fixture maintenance',
            'prioritas' => Order::PRIORITY_LOW,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addDays(10)->toDateString(),
            'created_by' => $user->id,
        ]);
        OrderDocument::query()->create([
            'order_id' => $order->id,
            'jenis_dokumen' => 'abnormalitas',
            'nama_file_asli' => 'missing.pdf',
            'path_file' => 'orders/missing.pdf',
            'uploaded_by' => $user->id,
            'uploaded_at' => now(),
        ]);

        $findings = app(FileStorageHealthEvaluator::class)->evaluate('deep');

        $this->assertContains('recorded_file_missing', collect($findings)->pluck('code')->all());
        $this->assertStringNotContainsString(storage_path(), json_encode($findings));
        Storage::disk('public')->assertExists('orders/existing.pdf');
    }
}
