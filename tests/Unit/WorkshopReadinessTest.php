<?php

namespace Tests\Unit;

use App\Models\OrderWorkshop;
use App\Support\WorkshopReadiness;
use PHPUnit\Framework\TestCase;

class WorkshopReadinessTest extends TestCase
{
    public function test_readiness_rules_follow_preparation_status(): void
    {
        $readiness = new WorkshopReadiness;

        $this->assertSame('Belum Memilih Persiapan', $readiness->resolve(null)['label']);

        foreach ([
            OrderWorkshop::PREPARATION_WAITING_BUDGET_CONFIRMATION => 'Menunggu Konfirmasi Anggaran',
            OrderWorkshop::PREPARATION_WAITING_MATERIAL => 'Menunggu Material',
            OrderWorkshop::PREPARATION_WAITING_BUDGET_TRANSFER => 'Menunggu Transfer Budget',
        ] as $status => $label) {
            $workshop = new OrderWorkshop(['preparation_status' => $status]);
            $this->assertSame($label, $readiness->resolve($workshop)['label']);
            $this->assertFalse($readiness->canAdvance($workshop));
        }

        $completed = new OrderWorkshop(['preparation_status' => OrderWorkshop::PREPARATION_COMPLETED]);
        $this->assertSame('Persiapan Selesai', $readiness->resolve($completed)['label']);
        $this->assertTrue($readiness->canAdvance($completed));

        $completedProgress = new OrderWorkshop(['progress_status' => OrderWorkshop::PROGRESS_DONE]);
        $this->assertSame(WorkshopReadiness::COMPLETED, $readiness->resolve($completedProgress)['code']);
        $this->assertSame('Selesai', $readiness->resolve($completedProgress)['label']);
        $this->assertTrue($readiness->canAdvance($completedProgress));
    }
}
