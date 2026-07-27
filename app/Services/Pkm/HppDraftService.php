<?php

namespace App\Services\Pkm;

use App\Models\Hpp;
use App\Models\Order;
use App\Models\OutlineAgreement;
use App\Models\UnitWork;
use App\Support\HppApprovalFlow;

class HppDraftService
{
    /**
     * @param array<string,mixed> $validated
     * @param array<string,mixed> $payload
     */
    public function fillDraft(Hpp $hpp, array $validated, array $payload): void
    {
        $order = Order::query()->findOrFail($validated['order_id']);
        $oa = OutlineAgreement::query()
            ->with(['unitWork:id,department_id,name', 'unitWork.department:id,name'])
            ->findOrFail($validated['outline_agreement_id']);
        $groups = $this->buildItemGroups($payload);
        $total = $this->sumItemGroupSubtotals($groups);
        $bucket = HppApprovalFlow::resolveBucketFromTotal($total);
        $areaKey = HppApprovalFlow::normalizeAreaKey($validated['area_pekerjaan']);
        $defaultFlow = HppApprovalFlow::resolveApprovalFlow(
            $validated['kategori_pekerjaan'],
            $areaKey,
            $bucket,
        );

        $hpp->fill([
            'order_id' => $order->id,
            'outline_agreement_id' => $oa->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'departemen_peminta' => $this->resolveDepartmentForUnitName($order->unit_kerja),
            'unit_work_id' => $oa->unit_work_id,
            'cost_centre' => ($validated['cost_centre'] ?? null) ?: null,
            'kategori_pekerjaan' => $validated['kategori_pekerjaan'],
            'area_pekerjaan' => HppApprovalFlow::displayArea($areaKey),
            'nilai_hpp_bucket' => $bucket,
            'unit_kerja_pengendali' => $oa->unitWork?->name,
            'seksi_pengendali' => filled($oa->jenis_kontrak) ? trim((string) $oa->jenis_kontrak) : null,
            'departemen_pengendali' => $oa->unitWork?->department?->name,
            'outline_agreement' => $oa->nomor_oa,
            'periode_outline_agreement' => $this->formatOutlineAgreementPeriod($oa),
            'approval_case' => HppApprovalFlow::resolvePreviewCase(
                $validated['kategori_pekerjaan'],
                $areaKey,
                $bucket,
            ),
            'approval_flow' => $this->resolveDraftApprovalFlow($hpp, $defaultFlow),
            'item_groups' => $groups,
            'total_keseluruhan' => $total,
            'status' => Hpp::STATUS_DRAFT,
            'submitted_at' => null,
        ]);
    }

    /** @return list<array<string,mixed>> */
    private function buildItemGroups(array $payload): array
    {
        $result = [];

        foreach (($payload['jenis_label_visible'] ?? []) as $groupIndex => $label) {
            $items = [];
            $subtotal = '0.00';

            foreach (($payload['nama_item'][$groupIndex] ?? []) as $itemIndex => $name) {
                $qty = $this->normalizeNumericString($payload['qty'][$groupIndex][$itemIndex] ?? 0);
                $unitPrice = $this->normalizeNumericString($payload['harga_satuan'][$groupIndex][$itemIndex] ?? 0);
                $itemTotal = $this->normalizeCurrencyDecimal($this->multiplyCurrencyDecimal($qty, $unitPrice));
                $isBlank = trim((string) $name) === ''
                    && (float) $qty === 0.0
                    && (float) $unitPrice === 0.0;

                if ($isBlank) {
                    continue;
                }

                $items[] = [
                    'sub_jenis_item' => filled($payload['sub_jenis_item'][$groupIndex][$itemIndex] ?? null)
                        ? trim((string) $payload['sub_jenis_item'][$groupIndex][$itemIndex]) : null,
                    'kategori_item' => filled($payload['kategori_item'][$groupIndex][$itemIndex] ?? null)
                        ? trim((string) $payload['kategori_item'][$groupIndex][$itemIndex]) : null,
                    'nama_item' => trim((string) $name),
                    'jumlah_item' => trim((string) ($payload['jumlah_item'][$groupIndex][$itemIndex] ?? '')),
                    'qty' => $qty,
                    'satuan' => trim((string) ($payload['satuan'][$groupIndex][$itemIndex] ?? '')),
                    'harga_satuan' => $unitPrice,
                    'harga_total' => $itemTotal,
                    'keterangan' => trim((string) ($payload['keterangan'][$groupIndex][$itemIndex] ?? '')),
                ];
                $subtotal = $this->addCurrencyDecimals($subtotal, $itemTotal);
            }

            if ($items !== []) {
                $result[] = [
                    'jenis_item' => trim((string) $label) ?: 'Material/Jasa',
                    'subtotal' => $subtotal,
                    'items' => $items,
                ];
            }
        }

        return $result;
    }

    /** @param list<array<string,mixed>> $groups */
    private function sumItemGroupSubtotals(array $groups): string
    {
        $total = '0.00';

        foreach ($groups as $group) {
            $total = $this->addCurrencyDecimals($total, (string) ($group['subtotal'] ?? '0'));
        }

        return $total;
    }

    /** @param list<string> $defaultFlow @return list<string> */
    private function resolveDraftApprovalFlow(Hpp $hpp, array $defaultFlow): array
    {
        $existing = collect((array) $hpp->approval_flow)
            ->map(fn (mixed $role): string => trim((string) $role))
            ->filter()
            ->values()
            ->all();

        if ($existing === []) {
            return array_values($defaultFlow);
        }

        $existingCounts = array_count_values($existing);
        $defaultCounts = array_count_values($defaultFlow);
        ksort($existingCounts);
        ksort($defaultCounts);

        return $existingCounts === $defaultCounts ? $existing : array_values($defaultFlow);
    }

    private function normalizeNumericString(mixed $value): string
    {
        $value = preg_replace('/[^0-9.\-]/', '', trim((string) $value)) ?? '';

        if ($value === '' || $value === '-' || $value === '.') {
            return '0';
        }

        $normalized = rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.');

        return $normalized === '-0' || $normalized === '' ? '0' : $normalized;
    }

    private function normalizeCurrencyDecimal(mixed $value): string
    {
        return number_format((float) $this->normalizeNumericString($value), 2, '.', '');
    }

    private function multiplyCurrencyDecimal(string $left, string $right): string
    {
        return number_format((float) $left * (float) $right, 2, '.', '');
    }

    private function addCurrencyDecimals(string $left, string $right): string
    {
        return number_format((float) $left + (float) $right, 2, '.', '');
    }

    private function formatOutlineAgreementPeriod(OutlineAgreement $oa): ?string
    {
        $start = $oa->current_period_start?->format('d/m/Y');
        $end = $oa->current_period_end?->format('d/m/Y');

        return $start || $end ? trim(sprintf('%s - %s', $start ?: '-', $end ?: '-')) : null;
    }

    private function resolveDepartmentForUnitName(?string $unitName): ?string
    {
        return UnitWork::query()
            ->with('department:id,name')
            ->where('name', trim((string) $unitName))
            ->first()
            ?->department
            ?->name;
    }
}
