<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateLpjPplRequest;
use App\Models\LhppBast;
use App\Models\LpjPpl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LpjPplController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $search = trim((string) $request->string('search'));
            $selectedPo = trim((string) $request->string('po'));

            $poOptions = LhppBast::query()
                ->whereNotNull('purchase_order_number')
                ->whereRaw("TRIM(purchase_order_number) <> ''")
                ->orderBy('purchase_order_number')
                ->pluck('purchase_order_number')
                ->unique()
                ->values();

            $lpjRows = LhppBast::query()
                ->with([
                    'order:id,nomor_order,unit_kerja,seksi',
                    'purchaseOrder:id,order_id,purchase_order_number',
                    'lpjPpl:id,lhpp_bast_id,lpj_number_termin1,ppl_number_termin1,lpj_document_path_termin1,ppl_document_path_termin1,lpj_number_termin2,ppl_number_termin2,lpj_document_path_termin2,ppl_document_path_termin2,updated_at',
                ])
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
                ->when($selectedPo !== '', fn ($query) => $query->where('purchase_order_number', $selectedPo))
                ->latest('id')
                ->paginate(10)
                ->withQueryString();

            return view('admin.lpj.index', [
                'search' => $search,
                'selectedPo' => $selectedPo,
                'poOptions' => $poOptions,
                'lpjRows' => $lpjRows,
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to load admin LPJ/PPL index page.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Terjadi kesalahan saat memuat halaman LPJ / PPL admin.');
        }
    }

    public function update(UpdateLpjPplRequest $request, LhppBast $lhpp): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $selectedTermin = (int) $validated['selected_termin'];
            $userId = $request->user()?->id;

            $lpjPpl = LpjPpl::firstOrNew([
                'lhpp_bast_id' => $lhpp->id,
            ]);

            if (! $lpjPpl->exists) {
                $lpjPpl->created_by = $userId;
            }

            $lpjPpl->updated_by = $userId;

            if ($selectedTermin === 1) {
                $lpjPpl->lpj_number_termin1 = $validated['lpj_number'] ?: null;
                $lpjPpl->ppl_number_termin1 = $validated['ppl_number'] ?: null;
            } else {
                $lpjPpl->lpj_number_termin2 = $validated['lpj_number'] ?: null;
                $lpjPpl->ppl_number_termin2 = $validated['ppl_number'] ?: null;
            }

            $storageDirectory = 'lpj-ppl/'.$lhpp->nomor_order;

            if ($request->hasFile('lpj_document')) {
                $existingPath = $selectedTermin === 1
                    ? $lpjPpl->lpj_document_path_termin1
                    : $lpjPpl->lpj_document_path_termin2;

                if ($existingPath) {
                    Storage::disk('public')->delete($existingPath);
                }

                $lpjFile = $request->file('lpj_document');
                $lpjFilename = sprintf(
                    'LPJ-Termin-%d-%s.%s',
                    $selectedTermin,
                    $lhpp->nomor_order,
                    $lpjFile->getClientOriginalExtension()
                );
                $lpjPath = $lpjFile->storeAs($storageDirectory, $lpjFilename, 'public');

                if ($selectedTermin === 1) {
                    $lpjPpl->lpj_document_path_termin1 = $lpjPath;
                } else {
                    $lpjPpl->lpj_document_path_termin2 = $lpjPath;
                }
            }

            if ($request->hasFile('ppl_document')) {
                $existingPath = $selectedTermin === 1
                    ? $lpjPpl->ppl_document_path_termin1
                    : $lpjPpl->ppl_document_path_termin2;

                if ($existingPath) {
                    Storage::disk('public')->delete($existingPath);
                }

                $pplFile = $request->file('ppl_document');
                $pplFilename = sprintf(
                    'PPL-Termin-%d-%s.%s',
                    $selectedTermin,
                    $lhpp->nomor_order,
                    $pplFile->getClientOriginalExtension()
                );
                $pplPath = $pplFile->storeAs($storageDirectory, $pplFilename, 'public');

                if ($selectedTermin === 1) {
                    $lpjPpl->ppl_document_path_termin1 = $pplPath;
                } else {
                    $lpjPpl->ppl_document_path_termin2 = $pplPath;
                }
            }

            $lpjPpl->save();

            $lhpp->termin1_status = $validated['termin1_status'];
            $lhpp->termin2_status = $validated['termin2_status'];
            $lhpp->updated_by = $userId;
            $lhpp->save();

            return redirect()
                ->route('admin.lpj.index', array_filter([
                    'search' => $validated['search'] ?? null,
                    'po' => $validated['po'] ?? null,
                    'page' => $validated['page'] ?? null,
                ]))
                ->with('status', sprintf('Data LPJ / PPL untuk order %s berhasil diperbarui.', $lhpp->nomor_order));
        } catch (Throwable $exception) {
            Log::error('Failed to update admin LPJ / PPL data.', [
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'user_id' => $request->user()?->id,
                'lhpp_id' => $lhpp->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return back()
                ->withErrors([
                    'lpj_ppl' => 'Terjadi kesalahan saat menyimpan data LPJ / PPL.',
                ])
                ->withInput();
        }
    }
}
