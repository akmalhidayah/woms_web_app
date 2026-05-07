<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class QualityControlReportFile extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'quality_control_report_id',
        'category',
        'file_path',
        'original_name',
        'mime_type',
        'size',
        'sort_order',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(QualityControlReport::class, 'quality_control_report_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}
