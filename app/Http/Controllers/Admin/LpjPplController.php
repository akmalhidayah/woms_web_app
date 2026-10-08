<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateLpjPplRequest;
use App\Models\LhppBast;
use App\Services\Admin\LpjPplUpdateService;
use App\Support\BastDisplayLabel;
use App\Support\LpjPplIndexFilters;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class LpjPplController extends Controller
{
    public function index(Request $request, LpjPplIndexFilters $indexFilters): View
    {
        try {
            $search = trim((string) $request->string('search'));
            $selectedPo = trim((string) $request->string('po'));
            $activeTab = $indexFilters->normalizeTab($request->string('tab')->toString());
            $selectedStage = $indexFilters->normalizeStage($request->string('stage')->toString());

            $poOptions = LhppBast::query()
                ->where('termin_type', 'termin_1')
                ->where('approval_status', LhppBast::APPROVAL_APPROVED)
                ->whereNotNull('purchase_order_number')
                ->whereRaw("TRIM(purchase_order_number) <> ''")
                ->orderBy('purchase_order_number')
                ->pluck('purchase_order_number')
                ->unique()
                ->values();

            $lpjRowsQuery = LhppBast::query()
                ->with([
                    'order:id,nomor_order,notifikasi,nama_pekerjaan,unit_kerja,seksi',
                    'purchaseOrder:id,order_id,purchase_order_number',
                    'garansi:id,lhpp_bast_id,garansi_months',
                    'terminTwo:id,parent_lhpp_bast_id,approval_status',
                    'lpjPpl:id,lhpp_bast_id,lpj_number_termin1,ppl_number_termin1,lpj_document_path_termin1,ppl_document_path_termin1,lpj_number_termin2,ppl_number_termin2,lpj_document_path_termin2,ppl_document_path_termin2,updated_at',
                ])
                ->where('termin_type', 'termin_1')
                ->where('approval_status', LhppBast::APPROVAL_APPROVED)
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($builder) use ($search): void {
                        $builder
                            ->where('nomor_order', 'like', "%{$search}%")
                            ->orWhere('purchase_order_number', 'like', "%{$search}%")
                            ->orWhere('unit_kerja', 'like', "%{$search}%")
                            ->orWhere('seksi', 'like', "%{$search}%")
                            ->orWhereHas('lpjPpl', function ($lpjQuery) use ($search): void {
                                $lpjQuery
                                    ->where('lpj_number_termin1', 'like', "%{$search}%")
                                    ->orWhere('ppl_number_termin1', 'like', "%{$search}%")
                                    ->orWhere('lpj_number_termin2', 'like', "%{$search}%")
                                    ->orWhere('ppl_number_termin2', 'like', "%{$search}%");
                            });
                    });
                })
                ->when($selectedPo !== '', fn ($query) => $query->where('purchase_order_number', $selectedPo));

            $indexFilters->apply($lpjRowsQuery, $activeTab, $selectedStage);

            $lpjRows = $lpjRowsQuery
                ->latest('id')
                ->paginate(10)
                ->withQueryString();

            return view('admin.lpj.index', [
                'search' => $search,
                'selectedPo' => $selectedPo,
                'poOptions' => $poOptions,
                'lpjRows' => $lpjRows,
                'activeTab' => $activeTab,
                'tabOptions' => $indexFilters->tabOptions(),
                'tabCounts' => $indexFilters->counts($selectedStage),
                'selectedStage' => $selectedStage,
                'stageOptions' => $indexFilters->stageOptions(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to load admin LPJ/PPL index page.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'exception' => $exception,
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat memuat halaman LPJ / PPL admin.');
        }
    }

    public function update(
        UpdateLpjPplRequest $request,
        int $lhppId,
        LpjPplUpdateService $updateService,
    ): RedirectResponse {
        $validated = $request->validated();

        try {
            $lpjPpl = $updateService->update($lhppId, $validated, $request->user()?->id);
            $lhpp = $lpjPpl->lhppBast()->with('garansi:id,lhpp_bast_id,garansi_months')->firstOrFail();
            $selectedTermin = (int) $validated['selected_termin'];
            $garansiMonths = $lhpp->garansi?->garansi_months;
            $label = $selectedTermin === 1 && BastDisplayLabel::isWithoutWarranty($garansiMonths)
                ? 'LPJ/PPL'
                : 'LPJ/PPL '.BastDisplayLabel::stageLabel("termin_{$selectedTermin}", $garansiMonths);

            return redirect()
                ->route('admin.lpj.index', array_filter([
                    'search' => $validated['search'] ?? null,
                    'po' => $validated['po'] ?? null,
                    'tab' => $validated['tab'] ?? null,
                    'stage' => $validated['stage'] ?? null,
                    'page' => $validated['page'] ?? null,
                ]))
                ->with('status', sprintf('Data %s untuk order %s berhasil diperbarui.', $label, $lhpp->nomor_order));
        } catch (ValidationException|ModelNotFoundException|AuthorizationException|HttpExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            Log::error('Failed to update admin LPJ / PPL data.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'requested_lhpp_id' => $lhppId,
                'selected_termin' => $validated['selected_termin'] ?? null,
                'exception' => $exception,
            ]);

            return back()
                ->withErrors(['lpj_ppl' => 'Terjadi kesalahan saat menyimpan data LPJ / PPL.'])
                ->withInput();
        }
    }
}
