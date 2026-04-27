<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BengkelPic extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'avatar_path',
        'avatar_position_x',
        'avatar_position_y',
    ];

    protected $attributes = [
        'avatar_position_x' => 50,
        'avatar_position_y' => 50,
    ];

    protected $casts = [
        'avatar_position_x' => 'integer',
        'avatar_position_y' => 'integer',
    ];

    protected $appends = [
        'avatar_url',
        'avatar_object_position',
    ];

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        if (! Storage::disk('public')->exists($this->avatar_path)) {
            return null;
        }

        return route('bengkel-pics.avatar', [
            'bengkel_pic' => $this,
            'v' => substr(md5($this->avatar_path.'|'.($this->updated_at?->toIso8601String() ?? '')), 0, 12),
        ], false);
    }

    public function getAvatarObjectPositionAttribute(): string
    {
        $x = max(0, min(100, (int) ($this->avatar_position_x ?? 50)));
        $y = max(0, min(100, (int) ($this->avatar_position_y ?? 50)));

        return "{$x}% {$y}%";
    }
}
