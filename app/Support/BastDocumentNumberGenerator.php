<?php

namespace App\Support;

use App\Models\LhppBast;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class BastDocumentNumberGenerator
{
    private const DOCUMENT_CODE = 'BAST';
    private const DOCUMENT_TYPE_CODE = '25.10';

    /**
     * @return array{document_no: string, document_sequence: int, document_year: int}
     */
    public function assignToTerminOne(LhppBast $lhpp, ?CarbonInterface $date = null): array
    {
        if ($lhpp->termin_type !== 'termin_1') {
            throw ValidationException::withMessages([
                'document_no' => 'Nomor dokumen baru hanya boleh dibuat untuk BAST Termin 1.',
            ]);
        }

        if (filled($lhpp->document_no)) {
            return [
                'document_no' => (string) $lhpp->document_no,
                'document_sequence' => (int) $lhpp->document_sequence,
                'document_year' => (int) $lhpp->document_year,
            ];
        }

        $date = $date ?: Carbon::now();
        $sequence = $this->nextSequence($date);
        $payload = $this->format($date, $sequence);

        $lhpp->forceFill($payload);

        return $payload;
    }

    /**
     * @return array{document_no: string, document_sequence: int, document_year: int}
     */
    public function copyFromTerminOne(LhppBast $terminTwo, LhppBast $terminOne): array
    {
        if ($terminTwo->termin_type !== 'termin_2') {
            throw ValidationException::withMessages([
                'document_no' => 'Nomor dokumen BAST Termin 1 hanya boleh disalin ke BAST Termin 2.',
            ]);
        }

        if (blank($terminOne->document_no)) {
            throw ValidationException::withMessages([
                'document_no' => 'BAST Termin 2 hanya bisa disubmit setelah BAST Termin 1 memiliki nomor dokumen.',
            ]);
        }

        $payload = [
            'document_no' => (string) $terminOne->document_no,
            'document_sequence' => (int) $terminOne->document_sequence,
            'document_year' => (int) $terminOne->document_year,
        ];

        $terminTwo->forceFill($payload);

        return $payload;
    }

    public function nextSequence(CarbonInterface $date): int
    {
        $year = (int) $date->format('Y');
        $lastSequence = LhppBast::query()
            ->where('termin_type', 'termin_1')
            ->where('document_year', $year)
            ->whereNotNull('document_sequence')
            ->lockForUpdate()
            ->max('document_sequence');

        return ((int) $lastSequence) + 1;
    }

    /**
     * @return array{document_no: string, document_sequence: int, document_year: int}
     */
    public function format(CarbonInterface $date, int $sequence): array
    {
        $sequencePadded = str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
        $monthYear = $date->format('m-Y');

        return [
            'document_no' => sprintf(
                '%s/%s/%s/%s',
                $sequencePadded,
                self::DOCUMENT_CODE,
                self::DOCUMENT_TYPE_CODE,
                $monthYear,
            ),
            'document_sequence' => $sequence,
            'document_year' => (int) $date->format('Y'),
        ];
    }
}
