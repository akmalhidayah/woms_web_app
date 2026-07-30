<?php

namespace App\Exceptions\Inventory;

use DomainException;

class InsufficientInventoryStockException extends DomainException
{
    public function __construct(string $availableStock, ?string $unit = null)
    {
        $suffix = filled($unit) ? " {$unit}" : '';

        parent::__construct("Stok tidak mencukupi. Stok tersedia hanya {$availableStock}{$suffix}.");
    }
}
