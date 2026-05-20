<?php

namespace Tests\Feature\Admin\Orders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScopeOfWorkTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_scope_of_work_with_signature_file(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = Order::query()->create([
            'nomor_order' => 'ORD-SOW-001',
            'nama_pekerjaan' => 'Pekerjaan Scope of Work',
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Detail pekerjaan test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-05-01',
            'target_selesai' => '2026-05-10',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.orders.scope-of-work.store', $order), [
            'nama_penginput' => 'Admin Test',
            'tanggal_dokumen' => '2026-05-02',
            'tanggal_pemakaian' => '2026-05-03',
            'scope_pekerjaan' => ['Bongkar komponen'],
            'qty' => ['1'],
            'satuan' => ['lot'],
            'keterangan' => ['Normal'],
            'catatan' => 'Catatan test',
            'tanda_tangan_file' => UploadedFile::fake()->image('signature.png', 320, 120),
        ]);

        $response->assertRedirect(route('admin.orders.show', $order));

        $scopeOfWork = $order->scopeOfWork()->firstOrFail();

        $this->assertStringStartsWith('signatures/scope-of-work-', $scopeOfWork->tanda_tangan);
        $this->assertFalse(str_starts_with($scopeOfWork->tanda_tangan, 'data:image'));
        Storage::disk('public')->assertExists($scopeOfWork->tanda_tangan);
    }
}
