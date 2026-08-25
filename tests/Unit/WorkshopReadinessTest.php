<?php

namespace Tests\Unit;

use App\Models\OrderWorkshop;
use App\Support\WorkshopReadiness;
use PHPUnit\Framework\TestCase;

class WorkshopReadinessTest extends TestCase
{
    public function test_readiness_rules_follow_budget_and_material_state(): void
    {
        $readiness = new WorkshopReadiness;

        $this->assertSame('Menunggu Konfirmasi', $readiness->resolve(null)['label']);

        $notReady = new OrderWorkshop(['konfirmasi_anggaran' => OrderWorkshop::KONFIRMASI_MATERIAL_NOT_READY]);
        $this->assertSame('Menunggu Anggaran', $readiness->resolve($notReady)['label']);
        $this->assertFalse($readiness->canAdvance($notReady));

        $notReady->status_anggaran = OrderWorkshop::STATUS_ANGGARAN_COMPLETE_TRANSFER;
        $this->assertTrue($readiness->canAdvance($notReady));

        $materialReady = new OrderWorkshop(['konfirmasi_anggaran' => OrderWorkshop::KONFIRMASI_MATERIAL_READY]);
        $this->assertSame('Menunggu Status Material', $readiness->resolve($materialReady)['label']);

        $materialReady->status_material = OrderWorkshop::STATUS_MATERIAL_GOOD_ISSUE;
        $this->assertSame('Siap Diproses', $readiness->resolve($materialReady)['label']);
        $this->assertTrue($readiness->canAdvance($materialReady));
    }
}
