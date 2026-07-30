<?php

namespace App\Exceptions\Inventory;

use DomainException;

class InactiveInventoryItemException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Barang tidak aktif dan tidak dapat diproses.');
    }
}
