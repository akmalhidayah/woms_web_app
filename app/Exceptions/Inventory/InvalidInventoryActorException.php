<?php

namespace App\Exceptions\Inventory;

use DomainException;

class InvalidInventoryActorException extends DomainException
{
    public function __construct(string $message = 'Actor tidak diizinkan untuk transaksi inventory ini.')
    {
        parent::__construct($message);
    }
}
