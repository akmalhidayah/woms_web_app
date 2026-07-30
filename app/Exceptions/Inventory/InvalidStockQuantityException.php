<?php

namespace App\Exceptions\Inventory;

use DomainException;

class InvalidStockQuantityException extends DomainException
{
    public function __construct(string $message = 'Jumlah transaksi harus lebih besar dari 0.')
    {
        parent::__construct($message);
    }
}
