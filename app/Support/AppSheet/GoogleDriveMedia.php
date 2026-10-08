<?php

namespace App\Support\AppSheet;

final readonly class GoogleDriveMedia
{
    public function __construct(
        public string $contents,
        public string $mimeType,
    ) {}
}
