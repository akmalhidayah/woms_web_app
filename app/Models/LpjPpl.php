<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LpjPpl extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'lhpp_bast_id',
        'lpj_number_termin1',
        'ppl_number_termin1',
        'lpj_document_path_termin1',
        'ppl_document_path_termin1',
        'lpj_number_termin2',
        'ppl_number_termin2',
        'lpj_document_path_termin2',
        'ppl_document_path_termin2',
        'created_by',
        'updated_by',
    ];

    public function lhppBast(): BelongsTo
    {
        return $this->belongsTo(LhppBast::class);
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
