<?php

namespace App\Http\Controllers\Pkm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pkm\StoreLhppBastRequest;
use App\Models\LhppBast;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LhppController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $filters = [
                'search' => trim((string) $request->string('search')),
                'unit_kerja' => trim((string) $request->string('unit_kerja')),
                'purchase_order_number' => trim((string) $request->string('purchase_order_number')),
                'termin_status' => trim((string) $request->string('termin_status', 'all')),
            ];

            $baseQuery = LhppBast::query()
                ->when($filters['search'] !== '', function ($query) use ($filters): void {
                    $needle = $filters['search'];

                    $query->where(function ($builder) use ($needle): void {
                        $builder
                            ->where('nomor_order', 'like', "%{$needle}%")
                            ->orWhere('purchase_order_number', 'like', "%{$needle}%")
                            ->orWhere('unit_kerja', 'like', "%{$needle}%")
                            ->orWhere('seksi', 'like', "%{$needle}%");
                    });
                })
                ->when($filters['unit_kerja'] !== '', fn ($query) => $query->where('unit_kerja', $filters['unit_kerja']))
                ->when($filters['purchase_order_number'] !== '', fn ($query) => $query->where('purchase_order_number', $filters['purchase_order_number']))
                ->when($filters['termin_status'] !== 'all', function ($query) use ($filters): void {
                    match ($filters['termin_status']) {
                        't1_paid' => $query->where('termin1_status', 'sudah'),
                        't1_unpaid' => $query->where('termin1_status', '!=', 'sudah'),
                        't2_paid' => $query->where('termin2_status', 'sudah'),
                        't2_unpaid' => $query->where('termin2_status', '!=', 'sudah'),
                        default => null,
                    };
                });

            $units = LhppBast::query()
                ->whereNotNull('unit_kerja')
                ->whereRaw("TRIM(unit_kerja) <> ''")
                ->orderBy('unit_kerja')
                ->pluck('unit_kerja')
                ->unique()
                ->values();

            $pos = LhppBast::query()
                ->whereNotNull('purchase_order_number')
                ->whereRaw("TRIM(purchase_order_number) <> ''")
                ->orderBy('purchase_order_number')
                ->pluck('purchase_order_number')
                ->unique()
                ->values();

            $lhpps = $baseQuery
                ->latest('id')
                ->paginate(8)
                ->withQueryString();

            return view('dashboards.pkm', [
                'pageTitle' => 'BAST / LHPP',
                'pageDescription' => 'Monitoring laporan hasil pekerjaan dan dokumen BAST/LHPP PKM.',
                'lhpps' => $lhpps,
                'filters' => $filters,
                'units' => $units,
                'pos' => $pos,
                'activeTokens' => collect(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to load PKM LHPP index page.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat memuat daftar LHPP PKM.');
        }
    }

    public function create(Request $request): View
    {
        try {
            return $this->buildFormView($request, null, [
                'pageTitle' => 'Form LHPP',
                'pageDescription' => 'Form pembuatan BAST termin 1 PKM.',
                'formTitle' => 'Buat BAST Termin 1',
                'formAction' => route('pkm.lhpp.store'),
                'formMethod' => 'POST',
                'submitLabel' => 'Simpan',
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to load PKM LHPP create form.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat memuat form LHPP PKM.');
        }
    }

    public function edit(Request $request, LhppBast $lhpp): View
    {
        try {
            $lhpp->loadMissing('order');

            return $this->buildFormView($request, $lhpp, [
                'pageTitle' => 'Edit LHPP',
                'pageDescription' => 'Pembaruan data BAST termin 1 PKM.',
                'formTitle' => 'Edit BAST Termin 1',
                'formAction' => route('pkm.lhpp.update', $lhpp),
                'formMethod' => 'PATCH',
                'submitLabel' => 'Update',
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to load PKM LHPP edit form.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'lhpp_id' => $lhpp->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat memuat form edit LHPP PKM.');
        }
    }

    public function calculate(Request $request): JsonResponse
    {
        $calculation = $this->calculateRows(
            $request->input('material_rows', []),
            $request->input('service_rows', []),
        );

        return response()->json($calculation);
    }

    public function store(StoreLhppBastRequest $request): RedirectResponse
    {
        try {
            $order = $this->resolveEligibleOrder($request->input('nomor_order'));

            abort_if(! $order, Response::HTTP_NOT_FOUND, 'Order tidak ditemukan atau belum memenuhi syarat BAST.');

            $purchaseOrder = $order->purchaseOrder;
            $latestHpp = $order->latestHpp;
            $calculation = $this->calculateRows(
                $request->input('material_rows', []),
                $request->input('service_rows', []),
            );

            $lhpp = LhppBast::query()->updateOrCreate(
                ['order_id' => $order->id],
                [
                    'hpp_id' => $latestHpp?->id,
                    'purchase_order_id' => $purchaseOrder?->id,
                    'nomor_order' => $order->nomor_order,
                    'purchase_order_number' => $purchaseOrder?->purchase_order_number,
                    'deskripsi_pekerjaan' => $order->nama_pekerjaan,
                    'unit_kerja' => $order->unit_kerja,
                    'seksi' => $order->seksi,
                    'tanggal_bast' => $request->date('tanggal_bast'),
                    'tanggal_mulai_pekerjaan' => $request->date('tanggal_mulai_pekerjaan'),
                    'tanggal_selesai_pekerjaan' => $request->date('tanggal_selesai_pekerjaan'),
                    'approval_threshold' => $request->string('approval_threshold')->toString(),
                    'nilai_hpp' => $latestHpp?->total_keseluruhan ?? 0,
                    'material_items' => $calculation['material_rows'],
                    'service_items' => $calculation['service_rows'],
                    'subtotal_material' => $calculation['totals']['subtotal_material'],
                    'subtotal_jasa' => $calculation['totals']['subtotal_jasa'],
                    'total_aktual_biaya' => $calculation['totals']['total_aktual_biaya'],
                    'termin_1_nilai' => $calculation['totals']['termin_1_nilai'],
                    'termin_2_nilai' => $calculation['totals']['termin_2_nilai'],
                    'updated_by' => $request->user()?->id,
                    'created_by' => $request->user()?->id,
                ]
            );

            return redirect()
                ->route('pkm.lhpp.index')
                ->with('status', sprintf(
                    'BAST Termin 1 untuk order %s berhasil disimpan. Total aktual biaya Rp %s.',
                    $lhpp->nomor_order,
                    number_format((float) $lhpp->total_aktual_biaya, 0, ',', '.'),
                ));
        } catch (Throwable $exception) {
            Log::error('Failed to store PKM LHPP form.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'form' => 'Terjadi kesalahan saat menyimpan BAST Termin 1.',
                ]);
        }
    }

    public function update(StoreLhppBastRequest $request, LhppBast $lhpp): RedirectResponse
    {
        try {
            $order = $this->resolveEligibleOrder($request->input('nomor_order'), $lhpp->order_id);

            abort_if(! $order, Response::HTTP_NOT_FOUND, 'Order tidak ditemukan atau belum memenuhi syarat BAST.');

            $purchaseOrder = $order->purchaseOrder;
            $latestHpp = $order->latestHpp;
            $calculation = $this->calculateRows(
                $request->input('material_rows', []),
                $request->input('service_rows', []),
            );

            $lhpp->fill([
                'order_id' => $order->id,
                'hpp_id' => $latestHpp?->id,
                'purchase_order_id' => $purchaseOrder?->id,
                'nomor_order' => $order->nomor_order,
                'purchase_order_number' => $purchaseOrder?->purchase_order_number,
                'deskripsi_pekerjaan' => $order->nama_pekerjaan,
                'unit_kerja' => $order->unit_kerja,
                'seksi' => $order->seksi,
                'tanggal_bast' => $request->date('tanggal_bast'),
                'tanggal_mulai_pekerjaan' => $request->date('tanggal_mulai_pekerjaan'),
                'tanggal_selesai_pekerjaan' => $request->date('tanggal_selesai_pekerjaan'),
                'approval_threshold' => $request->string('approval_threshold')->toString(),
                'nilai_hpp' => $latestHpp?->total_keseluruhan ?? 0,
                'material_items' => $calculation['material_rows'],
                'service_items' => $calculation['service_rows'],
                'subtotal_material' => $calculation['totals']['subtotal_material'],
                'subtotal_jasa' => $calculation['totals']['subtotal_jasa'],
                'total_aktual_biaya' => $calculation['totals']['total_aktual_biaya'],
                'termin_1_nilai' => $calculation['totals']['termin_1_nilai'],
                'termin_2_nilai' => $calculation['totals']['termin_2_nilai'],
                'updated_by' => $request->user()?->id,
            ]);

            $lhpp->save();

            return redirect()
                ->route('pkm.lhpp.index')
                ->with('status', sprintf(
                    'BAST Termin 1 untuk order %s berhasil diperbarui.',
                    $lhpp->nomor_order,
                ));
        } catch (Throwable $exception) {
            Log::error('Failed to update PKM LHPP form.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'lhpp_id' => $lhpp->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'form' => 'Terjadi kesalahan saat memperbarui BAST Termin 1.',
                ]);
        }
    }

    public function destroy(Request $request, LhppBast $lhpp): RedirectResponse
    {
        try {
            $nomorOrder = $lhpp->nomor_order;
            $lhpp->delete();

            return redirect()
                ->route('pkm.lhpp.index')
                ->with('status', sprintf('BAST Termin 1 untuk order %s berhasil dihapus.', $nomorOrder));
        } catch (Throwable $exception) {
            Log::error('Failed to delete PKM LHPP.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'lhpp_id' => $lhpp->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return back()->withErrors([
                'form' => 'Terjadi kesalahan saat menghapus data LHPP.',
            ]);
        }
    }

    public function pdf(Request $request, LhppBast $lhpp)
    {
        try {
            $pdf = Pdf::loadView('pkm.lhpp.pdf', [
                'lhpp' => $lhpp,
                'materialItems' => collect($lhpp->material_items ?? []),
                'serviceItems' => collect($lhpp->service_items ?? []),
            ])->setPaper('a4', 'portrait');

            return $pdf->stream('bast-termin-1-'.$lhpp->nomor_order.'.pdf');
        } catch (Throwable $exception) {
            Log::error('Failed to generate PKM LHPP PDF.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'lhpp_id' => $lhpp->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat membuat PDF LHPP PKM.');
        }
    }

    private function eligibleOrders(?int $exceptOrderId = null): Collection
    {
        $existingOrderIds = LhppBast::query()
            ->when($exceptOrderId !== null, fn ($query) => $query->where('order_id', '!=', $exceptOrderId))
            ->pluck('order_id');

        return Order::query()
            ->with([
                'latestHpp' => fn ($query) => $query->select([
                    'hpps.id',
                    'hpps.order_id',
                    'hpps.total_keseluruhan',
                ]),
                'purchaseOrder:id,order_id,hpp_id,purchase_order_number,target_penyelesaian,progress_pekerjaan',
            ])
            ->whereHas('purchaseOrder', function ($query): void {
                $query
                    ->whereNotNull('purchase_order_number')
                    ->whereRaw("TRIM(purchase_order_number) <> ''")
                    ->where('progress_pekerjaan', 100);
            })
            ->whereHas('latestHpp')
            ->when($existingOrderIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $existingOrderIds))
            ->latest('id')
            ->get([
                'id',
                'nomor_order',
                'nama_pekerjaan',
                'unit_kerja',
                'seksi',
            ]);
    }

    private function resolveEligibleOrder(mixed $nomorOrder, ?int $exceptOrderId = null): ?Order
    {
        return $this->eligibleOrders($exceptOrderId)->firstWhere('nomor_order', trim((string) $nomorOrder));
    }

    private function mapOrderOption(Order $order): array
    {
        return [
            'nomor_order' => (string) $order->nomor_order,
            'deskripsi_pekerjaan' => (string) ($order->nama_pekerjaan ?? ''),
            'unit_kerja_peminta' => (string) ($order->seksi ?: $order->unit_kerja ?: ''),
            'unit_kerja' => (string) ($order->unit_kerja ?? ''),
            'seksi' => (string) ($order->seksi ?? ''),
            'purchase_order_number' => (string) ($order->purchaseOrder?->purchase_order_number ?? ''),
            'nilai_ece' => (float) ($order->latestHpp?->total_keseluruhan ?? 0),
        ];
    }

    /**
     * @param array<string, string> $meta
     */
    private function buildFormView(Request $request, ?LhppBast $lhpp, array $meta): View
    {
        $orders = $this->eligibleOrders($lhpp?->order_id);

        if ($lhpp?->order && ! $orders->contains('id', $lhpp->order_id)) {
            $orders->prepend($lhpp->order->loadMissing([
                'latestHpp' => fn ($query) => $query->select([
                    'hpps.id',
                    'hpps.order_id',
                    'hpps.total_keseluruhan',
                ]),
                'purchaseOrder:id,order_id,hpp_id,purchase_order_number,target_penyelesaian,progress_pekerjaan',
            ]));
        }

        $orderOptions = $orders
            ->unique('id')
            ->map(fn (Order $order): array => $this->mapOrderOption($order))
            ->values();

        $selectedOrder = trim((string) old(
            'nomor_order',
            $request->string('order')->toString() !== ''
                ? $request->string('order')->toString()
                : ($lhpp?->nomor_order ?? '')
        ));

        if ($selectedOrder === '' || ! $orderOptions->firstWhere('nomor_order', $selectedOrder)) {
            $selectedOrder = (string) ($orderOptions->first()['nomor_order'] ?? '');
        }

        $materialRows = collect(old('material_rows', $lhpp?->material_items ?? [
            ['name' => '', 'volume' => '', 'unit' => 'Jam', 'unit_price' => '', 'amount' => '0', 'amount_display' => '0'],
        ]))->values();
        $serviceRows = collect(old('service_rows', $lhpp?->service_items ?? [
            ['name' => '', 'volume' => '', 'unit' => 'Jam', 'unit_price' => '', 'amount' => '0', 'amount_display' => '0'],
        ]))->values();

        $calculation = $this->calculateRows(
            $materialRows->all(),
            $serviceRows->all(),
        );

        return view('dashboards.pkm', [
            'pageTitle' => $meta['pageTitle'],
            'pageDescription' => $meta['pageDescription'],
            'bastOrderOptions' => $orderOptions,
            'selectedBastOrder' => $selectedOrder,
            'selectedThreshold' => old('approval_threshold', $lhpp?->approval_threshold ?? 'under_250'),
            'bastDate' => old('tanggal_bast', optional($lhpp?->tanggal_bast)->format('Y-m-d') ?? now()->format('Y-m-d')),
            'tanggalMulaiPekerjaan' => old('tanggal_mulai_pekerjaan', optional($lhpp?->tanggal_mulai_pekerjaan)->format('Y-m-d')),
            'tanggalSelesaiPekerjaan' => old('tanggal_selesai_pekerjaan', optional($lhpp?->tanggal_selesai_pekerjaan)->format('Y-m-d')),
            'initialMaterialRows' => $calculation['material_rows'],
            'initialServiceRows' => $calculation['service_rows'],
            'initialCalculation' => $calculation['totals'],
            'formTitle' => $meta['formTitle'],
            'formAction' => $meta['formAction'],
            'formMethod' => $meta['formMethod'],
            'submitLabel' => $meta['submitLabel'],
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $materialRows
     * @param array<int, array<string, mixed>> $serviceRows
     * @return array<string, mixed>
     */
    private function calculateRows(array $materialRows, array $serviceRows): array
    {
        $normalizedMaterialRows = $this->normalizeItemRows($materialRows);
        $normalizedServiceRows = $this->normalizeItemRows($serviceRows);

        $subtotalMaterial = $this->sumAmounts($normalizedMaterialRows);
        $subtotalJasa = $this->sumAmounts($normalizedServiceRows);
        $totalAktualBiaya = $this->addCurrencyDecimals($subtotalMaterial, $subtotalJasa);
        $termin1Nilai = $this->multiplyCurrencyDecimal($totalAktualBiaya, '0.95');
        $termin2Nilai = $this->multiplyCurrencyDecimal($totalAktualBiaya, '0.05');

        return [
            'material_rows' => $normalizedMaterialRows !== [] ? $normalizedMaterialRows : [[
                'name' => '',
                'volume' => '',
                'unit' => 'Jam',
                'unit_price' => '',
                'amount' => '0.00',
                'amount_display' => '0',
            ]],
            'service_rows' => $normalizedServiceRows !== [] ? $normalizedServiceRows : [[
                'name' => '',
                'volume' => '',
                'unit' => 'Jam',
                'unit_price' => '',
                'amount' => '0.00',
                'amount_display' => '0',
            ]],
            'totals' => [
                'subtotal_material' => $subtotalMaterial,
                'subtotal_jasa' => $subtotalJasa,
                'total_aktual_biaya' => $totalAktualBiaya,
                'termin_1_nilai' => $termin1Nilai,
                'termin_2_nilai' => $termin2Nilai,
                'subtotal_material_display' => $this->displayCurrency($subtotalMaterial),
                'subtotal_jasa_display' => $this->displayCurrency($subtotalJasa),
                'total_aktual_biaya_display' => $this->displayCurrency($totalAktualBiaya),
                'termin_1_nilai_display' => $this->displayCurrency($termin1Nilai),
                'termin_2_nilai_display' => $this->displayCurrency($termin2Nilai),
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, string>>
     */
    private function normalizeItemRows(array $rows): array
    {
        $normalizedRows = [];

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $volume = $this->normalizeNumericString($row['volume'] ?? '');
            $unit = trim((string) ($row['unit'] ?? 'Jam'));
            $unitPrice = $this->normalizeCurrencyDecimal($row['unit_price'] ?? '');
            $amount = $this->multiplyCurrencyDecimal($volume, $unitPrice);

            if ($name === '' && $this->isZeroNumericString($volume) && $this->isZeroCurrency($unitPrice)) {
                continue;
            }

            $normalizedRows[] = [
                'name' => $name,
                'volume' => $volume === '0' ? '' : $volume,
                'unit' => $unit !== '' ? $unit : 'Jam',
                'unit_price' => $unitPrice === '0.00' ? '' : $this->displayEditableCurrency($unitPrice),
                'amount' => $amount,
                'amount_display' => $this->displayCurrency($amount),
            ];
        }

        return $normalizedRows;
    }

    /**
     * @param array<int, array<string, string>> $rows
     */
    private function sumAmounts(array $rows): string
    {
        $total = '0.00';

        foreach ($rows as $row) {
            $total = $this->addCurrencyDecimals($total, $row['amount'] ?? '0.00');
        }

        return $total;
    }

    private function normalizeNumericString(mixed $value): string
    {
        $normalized = preg_replace('/[^0-9,.\-]/', '', trim((string) $value)) ?? '';

        if ($normalized === '' || $normalized === '-' || $normalized === '.' || $normalized === ',') {
            return '0';
        }

        $isNegative = str_starts_with($normalized, '-');
        $unsigned = ltrim($normalized, '-');

        if (str_contains($unsigned, ',') && str_contains($unsigned, '.')) {
            $lastComma = strrpos($unsigned, ',');
            $lastDot = strrpos($unsigned, '.');

            if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
                $unsigned = str_replace('.', '', $unsigned);
                $unsigned = str_replace(',', '.', $unsigned);
            } else {
                $unsigned = str_replace(',', '', $unsigned);
            }
        } elseif (str_contains($unsigned, ',')) {
            $unsigned = str_replace(',', '.', $unsigned);
        } elseif (substr_count($unsigned, '.') > 1) {
            $unsigned = str_replace('.', '', $unsigned);
        } elseif (str_contains($unsigned, '.')) {
            [, $decimal] = array_pad(explode('.', $unsigned, 2), 2, '');

            if (strlen($decimal) === 3) {
                $unsigned = str_replace('.', '', $unsigned);
            }
        }

        if (str_contains($unsigned, '.')) {
            [$integer, $decimal] = array_pad(explode('.', $unsigned, 2), 2, '');
            $integer = ltrim($integer, '0');
            $decimal = rtrim($decimal, '0');

            $normalizedNumber = ($integer === '' ? '0' : $integer).($decimal !== '' ? ".{$decimal}" : '');

            return $isNegative ? "-{$normalizedNumber}" : $normalizedNumber;
        }

        $integer = ltrim($unsigned, '0');
        $normalizedNumber = $integer === '' ? '0' : $integer;

        return $isNegative ? "-{$normalizedNumber}" : $normalizedNumber;
    }

    private function normalizeCurrencyDecimal(mixed $value): string
    {
        $normalized = $this->normalizeNumericString($value);

        if (! str_contains($normalized, '.')) {
            return $normalized.'.00';
        }

        [$integer, $decimal] = array_pad(explode('.', $normalized, 2), 2, '');
        $decimal = substr(str_pad($decimal, 2, '0'), 0, 2);

        return "{$integer}.{$decimal}";
    }

    private function multiplyCurrencyDecimal(string $left, string $right): string
    {
        return number_format(
            (float) $this->normalizeNumericString($left) * (float) $this->normalizeCurrencyDecimal($right),
            2,
            '.',
            ''
        );
    }

    private function addCurrencyDecimals(string $left, string $right): string
    {
        return number_format(
            (float) $this->normalizeCurrencyDecimal($left) + (float) $this->normalizeCurrencyDecimal($right),
            2,
            '.',
            ''
        );
    }

    private function isZeroNumericString(string $value): bool
    {
        return (float) $this->normalizeNumericString($value) === 0.0;
    }

    private function isZeroCurrency(string $value): bool
    {
        return (float) $this->normalizeCurrencyDecimal($value) === 0.0;
    }

    private function displayCurrency(string $value): string
    {
        return number_format((float) $this->normalizeCurrencyDecimal($value), 0, ',', '.');
    }

    private function displayEditableCurrency(string $value): string
    {
        return number_format((float) $this->normalizeCurrencyDecimal($value), 0, ',', '.');
    }
}
