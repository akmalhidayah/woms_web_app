<?php

namespace App\Services\Pkm;

use App\Models\Hpp;
use App\Models\LhppBast;
use Illuminate\Support\Facades\Log;

class BastItemSnapshotService
{
    /**
     * Build an immutable BAST item snapshot directly from an approved HPP.
     *
     * @return array{material_rows: array<int, array<string, mixed>>, service_rows: array<int, array<string, mixed>>, totals: array<string, string>}
     */
    public function fromApprovedHpp(Hpp $hpp, bool $isWithoutWarranty): array
    {
        $materialRows = [];
        $serviceRows = [];

        foreach ((array) $hpp->item_groups as $groupIndex => $group) {
            $groupJenis = trim((string) ($group['jenis_item'] ?? $group['jenis'] ?? $group['name'] ?? ''));

            foreach ((array) ($group['items'] ?? []) as $itemIndex => $item) {
                $name = trim((string) ($item['nama_item'] ?? $item['name'] ?? $item['nama'] ?? $item['item_name'] ?? ''));

                if ($name === '') {
                    continue;
                }

                $jenis = trim((string) ($item['jenis_item'] ?? $groupJenis));
                $volume = $this->normalizeNumber($item['qty'] ?? $item['volume'] ?? '0');
                $unitPrice = $this->normalizeMoney($item['harga_satuan'] ?? $item['unit_price'] ?? '0');
                $hasStoredAmount = array_key_exists('harga_total', $item) || array_key_exists('amount', $item);
                $amount = $hasStoredAmount
                    ? $this->normalizeMoney($item['harga_total'] ?? $item['amount'])
                    : $this->multiply($volume, $unitPrice);

                $row = [
                    'contract_item_id' => null,
                    'jenis_item' => $jenis,
                    'sub_jenis_item' => trim((string) ($item['sub_jenis_item'] ?? $item['sub_jenis'] ?? '')),
                    'kategori_item' => trim((string) ($item['kategori_item'] ?? $item['kategori'] ?? $item['category'] ?? '')),
                    'name' => $name,
                    'jumlah_item' => trim((string) ($item['jumlah_item'] ?? '')),
                    'volume' => $volume,
                    'unit' => trim((string) ($item['satuan'] ?? $item['unit'] ?? '')),
                    'unit_price_raw' => $unitPrice,
                    'unit_price' => $this->display($unitPrice),
                    'amount' => $amount,
                    'amount_display' => $this->display($amount),
                    'keterangan' => trim((string) ($item['keterangan'] ?? $item['notes'] ?? '')),
                    'group_order' => $groupIndex,
                    'item_order' => $itemIndex,
                ];

                if (str_contains(strtoupper($jenis), 'JASA')) {
                    $serviceRows[] = $row;
                } else {
                    $materialRows[] = $row;
                }
            }
        }

        $subtotalMaterial = $this->sumRows($materialRows);
        $subtotalJasa = $this->sumRows($serviceRows);
        $rowsTotal = $this->add($subtotalMaterial, $subtotalJasa);
        $hppTotal = $this->normalizeMoney($hpp->total_keseluruhan);

        if (abs($this->toMinor($rowsTotal) - $this->toMinor($hppTotal)) > 1) {
            Log::warning('Subtotal snapshot item HPP berbeda dari total HPP.', [
                'hpp_id' => $hpp->id,
                'nomor_order' => $hpp->nomor_order,
                'subtotal_items' => $rowsTotal,
                'total_hpp' => $hppTotal,
            ]);
        }

        [$terminOne, $terminTwo] = $this->terminValues($hppTotal, $isWithoutWarranty);

        return $this->result($materialRows, $serviceRows, $subtotalMaterial, $subtotalJasa, $hppTotal, $terminOne, $terminTwo);
    }

    /**
     * Copy the exact persisted values from Termin 1 without consulting the catalog.
     *
     * @return array{material_rows: array<int, array<string, mixed>>, service_rows: array<int, array<string, mixed>>, totals: array<string, string>}
     */
    public function fromParentBast(LhppBast $parent): array
    {
        return $this->result(
            is_array($parent->material_items) ? $parent->material_items : [],
            is_array($parent->service_items) ? $parent->service_items : [],
            (string) $parent->subtotal_material,
            (string) $parent->subtotal_jasa,
            (string) $parent->total_aktual_biaya,
            (string) $parent->termin_1_nilai,
            (string) $parent->termin_2_nilai,
        );
    }

    /** @param array<int, array<string, mixed>> $materialRows @param array<int, array<string, mixed>> $serviceRows */
    private function result(array $materialRows, array $serviceRows, string $subtotalMaterial, string $subtotalJasa, string $total, string $terminOne, string $terminTwo): array
    {
        $totals = [
            'subtotal_material' => $this->normalizeMoney($subtotalMaterial),
            'subtotal_jasa' => $this->normalizeMoney($subtotalJasa),
            'total_aktual_biaya' => $this->normalizeMoney($total),
            'termin_1_nilai' => $this->normalizeMoney($terminOne),
            'termin_2_nilai' => $this->normalizeMoney($terminTwo),
        ];

        foreach (array_keys($totals) as $key) {
            $totals[$key.'_display'] = $this->display($totals[$key]);
        }

        return ['material_rows' => $materialRows, 'service_rows' => $serviceRows, 'totals' => $totals];
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function sumRows(array $rows): string
    {
        $total = 0;
        foreach ($rows as $row) {
            $total += $this->toMinor($row['amount'] ?? '0');
        }

        return $this->fromMinor($total);
    }

    /** @return array{0: string, 1: string} */
    private function terminValues(string $total, bool $isWithoutWarranty): array
    {
        $totalMinor = $this->toMinor($total);
        if ($isWithoutWarranty) {
            return [$this->fromMinor($totalMinor), '0.00'];
        }

        $terminOneMinor = intdiv(($totalMinor * 95) + 50, 100);

        return [$this->fromMinor($terminOneMinor), $this->fromMinor($totalMinor - $terminOneMinor)];
    }

    private function multiply(string $quantity, string $money): string
    {
        $quantityParts = explode('.', $this->normalizeNumber($quantity), 2);
        $scale = isset($quantityParts[1]) ? 10 ** strlen($quantityParts[1]) : 1;
        $quantityMinor = (int) ($quantityParts[0].($quantityParts[1] ?? ''));

        $amountMinor = $quantityMinor * $this->toMinor($money);

        return $this->fromMinor(intdiv($amountMinor + intdiv($scale, 2), $scale));
    }

    private function add(string $left, string $right): string
    {
        return $this->fromMinor($this->toMinor($left) + $this->toMinor($right));
    }

    private function normalizeMoney(mixed $value): string
    {
        return $this->fromMinor($this->toMinor($value));
    }

    private function toMinor(mixed $value): int
    {
        $normalized = $this->normalizeNumber($value);
        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');
        $negative = str_starts_with($whole, '-');
        $whole = ltrim($whole, '-');
        $minor = ((int) ($whole === '' ? '0' : $whole) * 100) + (int) substr(str_pad($fraction, 2, '0'), 0, 2);

        return $negative ? -$minor : $minor;
    }

    private function fromMinor(int $value): string
    {
        $negative = $value < 0 ? '-' : '';
        $absolute = abs($value);

        return sprintf('%s%d.%02d', $negative, intdiv($absolute, 100), $absolute % 100);
    }

    private function normalizeNumber(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '0';
        }

        $value = preg_replace('/[^0-9,.-]/', '', $value) ?? '0';
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = strrpos($value, ',') > strrpos($value, '.')
                ? str_replace(',', '.', str_replace('.', '', $value))
                : str_replace(',', '', $value);
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        } elseif (substr_count($value, '.') > 1) {
            $value = str_replace('.', '', $value);
        }

        if (! preg_match('/^-?\d+(?:\.\d+)?$/', $value)) {
            return '0';
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $whole = ($whole[0] ?? '') === '-'
            ? '-'.(ltrim(substr($whole, 1), '0') ?: '0')
            : (ltrim($whole, '0') ?: '0');
        $fraction = rtrim($fraction, '0');

        return $fraction === '' ? $whole : $whole.'.'.$fraction;
    }

    private function display(string $value): string
    {
        $minor = $this->toMinor($value);
        $roundedWhole = intdiv(abs($minor) + 50, 100);

        return number_format($minor < 0 ? -$roundedWhole : $roundedWhole, 0, ',', '.');
    }
}
