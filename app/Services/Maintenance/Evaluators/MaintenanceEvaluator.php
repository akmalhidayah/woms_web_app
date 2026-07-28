<?php

namespace App\Services\Maintenance\Evaluators;

interface MaintenanceEvaluator
{
    public function category(): string;

    /**
     * @return list<array<string, mixed>>
     */
    public function evaluate(string $mode): array;
}
