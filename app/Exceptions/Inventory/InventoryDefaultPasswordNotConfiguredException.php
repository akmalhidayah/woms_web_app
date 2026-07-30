<?php

namespace App\Exceptions\Inventory;

use DomainException;

class InventoryDefaultPasswordNotConfiguredException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Password awal Inventory belum dikonfigurasi.');
    }
}
