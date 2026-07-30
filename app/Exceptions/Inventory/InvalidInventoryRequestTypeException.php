<?php

namespace App\Exceptions\Inventory;

use DomainException;

class InvalidInventoryRequestTypeException extends DomainException
{
    public function __construct(string $message = 'Jenis permintaan inventory tidak valid atau tidak aktif.')
    {
        parent::__construct($message);
    }
}
