<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminInformationFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'role',
        'title',
        'description',
        'original_name',
        'file_path',
        'mime_type',
        'uploaded_by',
    ];

    public const TYPE_CARA_KERJA = 'cara_kerja';
    public const TYPE_FLOWCHART_APLIKASI = 'flowchart_aplikasi';
    public const TYPE_KONTRAK_PKM = 'kontrak_pkm';

    /**
     * @return array<string, array<string, string>>
     */
    public static function defaults(): array
    {
        return [
            self::TYPE_CARA_KERJA => [
                'title' => 'Cara Kerja',
                'description' => 'Dokumen cara kerja aplikasi berdasarkan role pengguna.',
            ],
            self::TYPE_FLOWCHART_APLIKASI => [
                'title' => 'Flowchart Aplikasi',
                'description' => 'Dokumen alur sistem dan proses aplikasi.',
            ],
            self::TYPE_KONTRAK_PKM => [
                'title' => 'Kontrak PKM',
                'description' => 'Dokumen kontrak vendor PKM dan file pendukung terkait.',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function allowedTypes(): array
    {
        return array_keys(static::defaults());
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
