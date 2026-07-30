<?php

namespace App\Exceptions\Inventory;

use DomainException;

class InventoryStockOverflowException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Nilai stok melebihi kapasitas maksimum yang dapat disimpan.');
    }
}
