<?php

namespace App\Support;

use App\Models\Hpp;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class HppDocumentNumberGenerator
{
    private const DOCUMENT_CODE = 'HPP';
    private const DOCUMENT_TYPE_CODE = '25.10';

    /**
     * @return array{document_no: string, document_sequence: int, document_year: int}
     */
    public function assignTo(Hpp $hpp, ?CarbonInterface $date = null): array
    {
        if (filled($hpp->document_no)) {
            return [
                'document_no' => (string) $hpp->document_no,
                'document_sequence' => (int) $hpp->document_sequence,
                'document_year' => (int) $hpp->document_year,
            ];
        }

        $date = $this->resolveDate($date);
        $sequence = $this->nextSequence($date);
        $payload = $this->format($date, $sequence);

        $hpp->forceFill($payload);

        return $payload;
    }

    public function nextSequence(CarbonInterface $date): int
    {
        return $this->nextSequenceForYear((int) $date->format('Y'));
    }

    public function nextSequenceForYear(int $year): int
    {
        $lastSequence = Hpp::query()
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

    private function resolveDate(?CarbonInterface $date): CarbonInterface
    {
        return $date ?: Carbon::now();
    }
}
