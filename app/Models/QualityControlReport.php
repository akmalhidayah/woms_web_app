<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualityControlReport extends Model
{
    use HasFactory;

    public const TYPE_FABRICATION = 'fabrication';
    public const TYPE_REFURBISH = 'refurbish';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'bengkel_task_id',
        'type',
        'report_no',
        'report_date',
        'status',
        'payload',
        'created_by',
        'updated_by',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function bengkelTask(): BelongsTo
    {
        return $this->belongsTo(BengkelTask::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(QualityControlReportFile::class)->orderBy('sort_order')->orderBy('id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(QualityControlSignature::class)->orderBy('step_order');
    }

    public function hasSignedApproval(): bool
    {
        return $this->signatures()
            ->where('status', QualityControlSignature::STATUS_SIGNED)
            ->exists();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'payload' => 'array',
        ];
    }
}
