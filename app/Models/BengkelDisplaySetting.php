<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BengkelDisplaySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticker_text',
        'ticker_speed_seconds',
    ];

    protected $attributes = [
        'ticker_text' => '',
        'ticker_speed_seconds' => 18,
    ];

    protected $casts = [
        'ticker_speed_seconds' => 'integer',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'ticker_text' => '',
            'ticker_speed_seconds' => 18,
        ]);
    }
}
