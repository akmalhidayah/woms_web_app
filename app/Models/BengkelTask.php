<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BengkelTask extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'job_name',
        'notification_number',
        'unit_work',
        'seksi',
        'usage_plan_date',
        'catatan',
        'is_completed',
        'progress_status',
        'person_in_charge',
        'person_in_charge_profiles',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function effectiveProgressStatus(): ?string
    {
        return $this->order?->orderWorkshop?->progress_status ?: $this->progress_status;
    }

    public function effectiveProgressLabel(): string
    {
        return OrderWorkshop::progressOptions()[$this->effectiveProgressStatus()] ?? 'Menunggu Jadwal';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'usage_plan_date' => 'date',
            'is_completed' => 'boolean',
            'person_in_charge' => 'array',
            'person_in_charge_profiles' => 'array',
        ];
    }
}
