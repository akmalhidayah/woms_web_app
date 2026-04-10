<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FabricationConstructionContract extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tahun',
        'jenis_item',
        'sub_jenis_item',
        'kategori_item',
        'nama_item',
        'satuan',
        'harga_satuan',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'harga_satuan' => 'decimal:2',
        ];
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($search) {
            $builder
                ->where('tahun', 'like', "%{$search}%")
                ->orWhere('jenis_item', 'like', "%{$search}%")
                ->orWhere('sub_jenis_item', 'like', "%{$search}%")
                ->orWhere('kategori_item', 'like', "%{$search}%")
                ->orWhere('nama_item', 'like', "%{$search}%")
                ->orWhere('satuan', 'like', "%{$search}%");
        });
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
