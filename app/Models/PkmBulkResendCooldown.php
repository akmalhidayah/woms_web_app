<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PkmBulkResendCooldown extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'document_type',
        'last_sent_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'last_sent_at' => 'datetime',
        ];
    }
}
