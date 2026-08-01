<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOutlineAgreementMonthlyRealizationRequest;
use App\Models\OutlineAgreement;
use App\Models\OutlineAgreementMonthlyRealization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class OutlineAgreementMonthlyRealizationController extends Controller
{
    public function store(
        StoreOutlineAgreementMonthlyRealizationRequest $request,
        OutlineAgreement $outlineAgreement,
    ): RedirectResponse {
        $validated = $request->validated();

        DB::transaction(function () use ($outlineAgreement, $validated): void {
            OutlineAgreement::query()
                ->whereKey($outlineAgreement->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            OutlineAgreementMonthlyRealization::query()->updateOrCreate(
                [
                    'outline_agreement_id' => $outlineAgreement->getKey(),
                    'year' => (int) $validated['year'],
                    'month' => (int) $validated['month'],
                ],
                [
                    'pr_po_amount' => (int) $validated['pr_po_amount'],
                    'urgent_amount' => (int) $validated['urgent_amount'],
                ],
            );
        });

        return redirect()
            ->route('admin.outline-agreements.index')
            ->with('success', "Realisasi biaya {$outlineAgreement->nomor_oa} berhasil disimpan.");
    }

    public function destroy(
        OutlineAgreement $outlineAgreement,
        OutlineAgreementMonthlyRealization $monthlyRealization,
    ): RedirectResponse {
        abort_unless(
            (int) $monthlyRealization->outline_agreement_id === (int) $outlineAgreement->getKey(),
            404,
        );

        $monthlyRealization->delete();

        return redirect()
            ->route('admin.outline-agreements.index')
            ->with('success', "Realisasi biaya {$outlineAgreement->nomor_oa} berhasil dihapus.");
    }
}
