<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LhppBastImage extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'lhpp_bast_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'created_by',
    ];

    public function lhppBast(): BelongsTo
    {
        return $this->belongsTo(LhppBast::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
