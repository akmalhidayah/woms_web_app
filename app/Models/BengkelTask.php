<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BengkelTask extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'job_name',
        'notification_number',
        'unit_work',
        'seksi',
        'usage_plan_date',
        'catatan',
        'person_in_charge',
        'person_in_charge_profiles',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'usage_plan_date' => 'date',
            'person_in_charge' => 'array',
            'person_in_charge_profiles' => 'array',
        ];
    }
}
