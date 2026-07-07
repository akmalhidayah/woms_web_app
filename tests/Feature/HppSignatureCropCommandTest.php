<?php

namespace Tests\Feature;

use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HppSignatureCropCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_hpp_signature_crop_command_dry_run_does_not_change_files(): void
    {
        Storage::fake('public');

        $signature = $this->createHppSignatureWithCanvasImage();
        $original = Storage::disk('public')->get($signature->signature_data);

        $this->artisan('hpp:crop-signatures', [
            '--signature-id' => $signature->id,
        ])->assertExitCode(0);

        $this->assertSame($original, Storage::disk('public')->get($signature->signature_data));
        $this->assertSame([], Storage::disk('public')->allFiles('signature-backups/hpp'));
    }

    public function test_hpp_signature_crop_command_apply_backs_up_and_overwrites_only_image(): void
    {
        Storage::fake('public');

        $signature = $this->createHppSignatureWithCanvasImage();
        $original = Storage::disk('public')->get($signature->signature_data);
        $originalSize = getimagesizefromstring($original);

        $this->artisan('hpp:crop-signatures', [
            '--hpp-id' => $signature->hpp_id,
            '--apply' => true,
        ])->assertExitCode(0);

        $updated = Storage::disk('public')->get($signature->signature_data);
        $updatedSize = getimagesizefromstring($updated);
        $backupFiles = Storage::disk('public')->allFiles('signature-backups/hpp');

        $this->assertNotSame($original, $updated);
        $this->assertNotFalse($originalSize);
        $this->assertNotFalse($updatedSize);
        $this->assertLessThan($originalSize[0], $updatedSize[0]);
        $this->assertLessThan($originalSize[1], $updatedSize[1]);
        $this->assertCount(1, $backupFiles);
        $this->assertSame($original, Storage::disk('public')->get($backupFiles[0]));
        $this->assertSame($signature->signed_at?->toDateTimeString(), $signature->fresh()->signed_at?->toDateTimeString());
        $this->assertSame($signature->signed_ip, $signature->fresh()->signed_ip);
        $this->assertSame($signature->signed_user_agent, $signature->fresh()->signed_user_agent);
    }

    private function createHppSignatureWithCanvasImage(): HppSignature
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'nomor_order' => 'ORD-CROP-001',
            'notifikasi' => 'NOTIF-CROP-001',
            'nama_pekerjaan' => 'Crop signature test',
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Crop signature test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addWeek()->toDateString(),
            'catatan_status' => 'approved_jasa',
            'created_by' => $user->id,
        ]);
        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_IN_REVIEW,
            'created_by' => $user->id,
        ]);

        $path = 'hpp-signatures/'.$hpp->id.'/manager.png';
        Storage::disk('public')->put($path, $this->canvasSignatureBinary());

        return $hpp->signatures()->create([
            'step_order' => 1,
            'role_key' => 'manager_peminta',
            'role_label' => 'Manager Peminta',
            'signer_user_id' => $user->id,
            'signer_name_snapshot' => $user->name,
            'signer_position_snapshot' => 'Manager Peminta',
            'status' => HppSignature::STATUS_SIGNED,
            'signed_at' => now()->subDay(),
            'signature_data' => $path,
            'signed_ip' => '127.0.0.1',
            'signed_user_agent' => 'Feature test',
        ]);
    }

    private function canvasSignatureBinary(): string
    {
        $image = imagecreatetruecolor(300, 120);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
        imagefilledrectangle($image, 0, 0, 300, 120, $transparent);

        $ink = imagecolorallocatealpha($image, 10, 10, 10, 0);
        imagesetthickness($image, 4);
        imageline($image, 110, 58, 180, 62, $ink);
        imageline($image, 120, 72, 170, 42, $ink);

        ob_start();
        imagepng($image);
        $binary = ob_get_clean();
        imagedestroy($image);

        return is_string($binary) ? $binary : '';
    }
}
