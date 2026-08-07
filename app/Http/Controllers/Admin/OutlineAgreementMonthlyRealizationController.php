<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOutlineAgreementMonthlyRealizationRequest;
use App\Models\OutlineAgreement;
use App\Models\OutlineAgreementMonthlyRealization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutlineAgreementMonthlyRealizationController extends Controller
{
    public function store(
        StoreOutlineAgreementMonthlyRealizationRequest $request,
        OutlineAgreement $outlineAgreement,
    ): RedirectResponse {
        $validated = $request->validated();

        $isEditing = DB::transaction(function () use ($outlineAgreement, $validated): bool {
            OutlineAgreement::query()
                ->whereKey($outlineAgreement->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $realizationId = isset($validated['realization_id'])
                ? (int) $validated['realization_id']
                : null;
            $identity = [
                'outline_agreement_id' => $outlineAgreement->getKey(),
                'year' => (int) $validated['year'],
                'month' => (int) $validated['month'],
                'kategori_biaya' => (string) $validated['kategori_biaya'],
            ];

            if ($realizationId !== null) {
                $realization = OutlineAgreementMonthlyRealization::query()
                    ->lockForUpdate()
                    ->findOrFail($realizationId);

                abort_unless(
                    (int) $realization->outline_agreement_id === (int) $outlineAgreement->getKey(),
                    404,
                );

                if (OutlineAgreementMonthlyRealization::query()
                    ->where($identity)
                    ->whereKeyNot($realization->getKey())
                    ->exists()) {
                    $period = Carbon::create($identity['year'], $identity['month'], 1)
                        ->locale('id')
                        ->translatedFormat('F Y');

                    throw ValidationException::withMessages([
                        'kategori_biaya' => "Kategori biaya tersebut sudah memiliki realisasi pada {$period}.",
                    ]);
                }

                $realization->update([
                    ...$identity,
                    'amount' => (int) $validated['amount'],
                ]);

                return true;
            }

            OutlineAgreementMonthlyRealization::query()->updateOrCreate(
                $identity,
                [
                    'amount' => (int) $validated['amount'],
                ],
            );

            return false;
        });

        return redirect()
            ->route('admin.outline-agreements.index')
            ->with(
                'success',
                "Realisasi biaya {$outlineAgreement->nomor_oa} berhasil "
                .($isEditing ? 'diperbarui.' : 'disimpan.'),
            );
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
