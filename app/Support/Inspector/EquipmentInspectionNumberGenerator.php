<?php

namespace App\Support\Inspector;

use App\Models\EquipmentInspection;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

final class EquipmentInspectionNumberGenerator
{
    public function assign(EquipmentInspection $inspection, CarbonInterface $issuedAt): void
    {
        if ($inspection->document_no !== null) {
            return;
        }

        if (DB::transactionLevel() < 1) {
            throw new LogicException('Penomoran inspeksi harus berada dalam transaksi tanda tangan.');
        }

        $year = (int) $issuedAt->format('Y');
        // A no-op update acquires a write lock on existing years as well as new-year inserts.
        // Only the year is updated on conflict; the stored sequence is never reset.
        DB::table('equipment_inspection_counters')->upsert(
            [['year' => $year, 'last_sequence' => 0]], ['year'], ['year'],
        );
        $counter = DB::table('equipment_inspection_counters')->where('year', $year)->lockForUpdate()->first();
        $sequence = (int) $counter->last_sequence + 1;
        DB::table('equipment_inspection_counters')->where('year', $year)->update(['last_sequence' => $sequence]);

        $inspection->forceFill([
            'document_no' => sprintf('%03d/INSP/25.10/%s', $sequence, $issuedAt->format('m-Y')),
            'document_sequence' => $sequence,
            'document_year' => $year,
        ]);
    }
}
