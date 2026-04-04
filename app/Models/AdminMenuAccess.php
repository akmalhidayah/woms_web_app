<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminMenuAccess extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'menu_key',
    ];

    /**
     * Get the admin user that owns the menu access row.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
