<?php

namespace App\Exceptions\AppSheet;

use RuntimeException;

class GoogleSheetsException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $requiresReconnect = false)
    {
        parent::__construct($message);
    }
}
