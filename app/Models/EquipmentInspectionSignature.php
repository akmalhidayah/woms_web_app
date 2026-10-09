<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentInspectionSignature extends Model
{
    public const ROLE_INSPECTOR = 'inspector';

    public const ROLE_MANAGER = 'manager_workshop';

    public const ROLE_LEADER = 'leader_gugus';

    // ROLE_LEADER remains available for historical signatures only.
    public const STEPS = [self::ROLE_INSPECTOR => 1, self::ROLE_MANAGER => 2];

    protected $guarded = ['id'];

    protected $hidden = ['signature_path', 'signature_sha256', 'signed_payload', 'content_hash'];

    protected function casts(): array
    {
        return ['signed_at' => 'datetime', 'signed_payload' => 'array', 'document_version' => 'integer'];
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(EquipmentInspection::class, 'equipment_inspection_id');
    }
}
