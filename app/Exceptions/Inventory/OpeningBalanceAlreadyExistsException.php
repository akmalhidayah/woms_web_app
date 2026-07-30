<?php

namespace App\Exceptions\Inventory;

use DomainException;

class OpeningBalanceAlreadyExistsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Opening balance untuk barang ini sudah pernah dibuat.');
    }
}
