<?php

namespace App\Exceptions\Inventory;

use DomainException;

class InventoryIdempotencyConflictException extends DomainException
{
    public function __construct()
    {
        parent::__construct('client_request_id sudah digunakan untuk permintaan yang berbeda.');
    }
}
