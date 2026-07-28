<?php

namespace Tests\Unit\Maintenance;

use App\Services\Maintenance\MaintenanceSnapshotRepository;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MaintenanceSnapshotRepositoryTest extends TestCase
{
    public function test_it_stores_snapshots_and_status_in_cache(): void
    {
        config(['cache.default' => 'array']);
        Cache::clear();
        $repository = app(MaintenanceSnapshotRepository::class);
        $quick = ['summary' => ['total' => 2]];
        $deep = ['summary' => ['total' => 3]];

        $repository->storeSnapshot('quick', $quick);
        $repository->storeSnapshot('deep', $deep);
        $repository->putStatus('queued', 'quick');

        $this->assertSame($quick, $repository->snapshot('quick'));
        $this->assertSame($deep, $repository->snapshot('deep'));
        $this->assertSame('queued', $repository->status()['status']);

        $repository->putStatus('running', 'quick');
        $this->assertSame('running', $repository->status()['status']);
        $repository->putStatus('completed', 'quick');
        $this->assertSame('completed', $repository->status()['status']);
        $repository->putStatus('failed', 'deep');
        $this->assertSame('failed', $repository->status()['status']);
    }
}
