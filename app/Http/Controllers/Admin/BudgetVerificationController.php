<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BudgetVerification\UpdateBudgetVerificationRequest;
use App\Models\BudgetVerification;
use App\Models\Hpp;
use App\Models\Order;
use App\Models\OrderDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BudgetVerificationController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $search = trim((string) $request->string('search'));
            $unit = trim((string) $request->string('unit'));
            $kategoriItem = trim((string) $request->string('kategori_item'));
            $perPage = 10;

            $notifications = Hpp::query()
                ->with([
                    'budgetVerification:id,order_id,hpp_id,status_anggaran,kategori_item,kategori_biaya,cost_element,catatan',
                    'purchaseOrder:id,order_id,hpp_id,purchase_order_number',
                    'order:id,nomor_order,nama_pekerjaan,unit_kerja,seksi',
                    'order.documents:id,order_id,jenis_dokumen,nama_file_asli,path_file',
                    'order.scopeOfWork:id,order_id',
                ])
                ->when($search !== '', function (Builder $query) use ($search): void {
                    $query->where(function (Builder $builder) use ($search): void {
                        $builder
                            ->where('nomor_order', 'like', "%{$search}%")
                            ->orWhere('nama_pekerjaan', 'like', "%{$search}%")
                            ->orWhere('unit_kerja', 'like', "%{$search}%")
                            ->orWhereHas('budgetVerification', fn (Builder $verificationQuery) => $verificationQuery->where('cost_element', 'like', "%{$search}%"));
                    });
                })
                ->when($unit !== '', fn (Builder $query) => $query->where('unit_kerja', $unit))
                ->when(
                    $kategoriItem !== '',
                    fn (Builder $query) => $query->whereHas('budgetVerification', fn (Builder $verificationQuery) => $verificationQuery->where('kategori_item', $kategoriItem))
                )
                ->latest('id')
                ->paginate($perPage)
                ->withQueryString();

            $notifications->setCollection(
                $notifications->getCollection()->map(fn (Hpp $hpp) => $this->mapRow($hpp))
            );

            $units = Hpp::query()
                ->select('unit_kerja')
                ->distinct()
                ->orderBy('unit_kerja')
                ->pluck('unit_kerja')
                ->filter()
                ->values()
                ->all();

            return view('admin.budget-verification.index', [
                'notifications' => $notifications,
                'units' => $units,
                'search' => $search,
                'selectedUnit' => $unit,
                'selectedKategoriItem' => $kategoriItem,
                'statusOptions' => BudgetVerification::statusAnggaranOptions(),
                'kategoriItemOptions' => BudgetVerification::kategoriItemOptions(),
                'kategoriBiayaOptions' => BudgetVerification::kategoriBiayaOptions(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to load budget verification index.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat memuat data verifikasi anggaran.');
        }
    }

    public function update(UpdateBudgetVerificationRequest $request, Hpp $hpp): RedirectResponse
    {
        try {
            $hpp->loadMissing(['order', 'budgetVerification']);

            $verification = $hpp->budgetVerification ?? new BudgetVerification();

            if (! $verification->exists) {
                $verification->hpp()->associate($hpp);
                $verification->order()->associate($hpp->order);
                $verification->created_by = $request->user()?->id;
            }

            $verification->fill([
                'status_anggaran' => $this->normalizeNullableString($request->input('status_anggaran')),
                'kategori_item' => $this->normalizeNullableString($request->input('kategori_item')),
                'kategori_biaya' => $this->normalizeNullableString($request->input('kategori_biaya')),
                'cost_element' => $this->normalizeNullableString($request->input('cost_element')),
                'catatan' => $this->normalizeNullableString($request->input('catatan')),
            ]);
            $verification->updated_by = $request->user()?->id;
            $verification->save();

            return redirect()
                ->route('admin.budget-verification.index', [
                    'search' => $request->input('_filter_search'),
                    'unit' => $request->input('_filter_unit'),
                    'kategori_item' => $request->input('_filter_kategori_item'),
                    'page' => $request->input('_filter_page'),
                ])
                ->with('status', sprintf(
                    'Verifikasi anggaran untuk order %s berhasil diperbarui.',
                    $hpp->nomor_order,
                ));
        } catch (Throwable $exception) {
            Log::error('Failed to update budget verification.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'hpp_id' => $hpp->id,
                'nomor_order' => $hpp->nomor_order,
                'user_id' => $request->user()?->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat menyimpan verifikasi anggaran.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function mapRow(Hpp $hpp): array
    {
        $order = $hpp->order;
        $verification = $hpp->budgetVerification;
        $purchaseOrder = $hpp->purchaseOrder;
        $abnormalitas = $this->findDocument($order, OrderDocumentType::Abnormalitas->value);
        $gambarTeknik = $this->findDocument($order, OrderDocumentType::GambarTeknik->value);
        $isExecuted = filled($purchaseOrder?->purchase_order_number);

        return [
            'nomor_order' => $hpp->nomor_order,
            'nama_pekerjaan' => $hpp->nama_pekerjaan,
            'unit' => $hpp->unit_kerja,
            'seksi' => $order?->seksi,
            'nilai_hpp' => (float) $hpp->total_keseluruhan,
            'status_anggaran' => $verification?->status_anggaran,
            'kategori_item' => $verification?->kategori_item,
            'kategori_biaya' => $verification?->kategori_biaya,
            'cost_element' => $verification?->cost_element,
            'catatan' => $verification?->catatan,
            'is_executed' => $isExecuted,
            'execution_label' => $isExecuted ? 'Sudah Dieksekusi' : 'Belum Dieksekusi',
            'update_url' => route('admin.budget-verification.update', ['hpp' => $hpp->nomor_order]),
            'dokumen' => [
                'abnormalitas' => [
                    'available' => $abnormalitas !== null,
                    'url' => $abnormalitas
                        ? route('admin.orders.documents.preview', ['order' => $order?->nomor_order, 'document' => $abnormalitas->id])
                        : null,
                ],
                'gambar_teknik' => [
                    'available' => $gambarTeknik !== null,
                    'url' => $gambarTeknik
                        ? route('admin.orders.documents.preview', ['order' => $order?->nomor_order, 'document' => $gambarTeknik->id])
                        : null,
                ],
                'scope_of_work' => [
                    'available' => $order?->scopeOfWork !== null,
                    'url' => $order?->scopeOfWork
                        ? route('admin.orders.scope-of-work.pdf', ['order' => $order->nomor_order, 'scopeOfWork' => $order->scopeOfWork->id])
                        : null,
                ],
                'hpp' => [
                    'available' => true,
                    'url' => route('admin.hpp.pdf', ['hpp' => $hpp->nomor_order]),
                ],
            ],
        ];
    }

    private function findDocument(?Order $order, string $type): ?OrderDocument
    {
        if (! $order) {
            return null;
        }

        return $order->documents->first(function (OrderDocument $document) use ($type): bool {
            $documentType = $document->jenis_dokumen;

            return ($documentType instanceof OrderDocumentType ? $documentType->value : (string) $documentType) === $type;
        });
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
