<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrder extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'hpp_id',
        'purchase_order_number',
        'target_penyelesaian',
        'approval_target',
        'approval_note',
        'approve_manager',
        'approve_senior_manager',
        'approve_general_manager',
        'approve_direktur_operasional',
        'progress_pekerjaan',
        'tanggal_mulai_pekerjaan',
        'tanggal_selesai_pekerjaan',
        'po_document_path',
        'vendor_note',
        'admin_note',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_penyelesaian' => 'date',
            'approve_manager' => 'boolean',
            'approve_senior_manager' => 'boolean',
            'approve_general_manager' => 'boolean',
            'approve_direktur_operasional' => 'boolean',
            'progress_pekerjaan' => 'integer',
            'tanggal_mulai_pekerjaan' => 'date',
            'tanggal_selesai_pekerjaan' => 'date',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function approvalTargetOptions(): array
    {
        return [
            'setuju' => 'Setujui Tanggal',
            'tidak_setuju' => 'Tolak Tanggal',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function hpp(): BelongsTo
    {
        return $this->belongsTo(Hpp::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
