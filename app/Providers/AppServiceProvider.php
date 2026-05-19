<?php

namespace App\Providers;

use App\Models\Hpp;
use App\Models\OutlineAgreement;
use App\Models\OutlineAgreementTarget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('dashboards.admin', function ($view): void {
            $data = $view->getData();

            $totalKuotaKontrak = $data['totalKuotaKontrak'] ?? (int) OutlineAgreement::query()
                ->where('status', OutlineAgreement::STATUS_ACTIVE)
                ->sum('current_total_nilai');

            $targetPemeliharaan = $data['targetPemeliharaan'] ?? (int) OutlineAgreementTarget::query()
                ->whereHas('outlineAgreement', fn (Builder $query) => $query->where('status', OutlineAgreement::STATUS_ACTIVE))
                ->sum('nilai_target');

            $totalJasaPemeliharaan = $data['totalJasaPemeliharaan'] ?? (int) Hpp::query()
                ->whereHas('budgetVerification', fn (Builder $query) => $query
                    ->where('kategori_item', 'jasa')
                    ->where('kategori_biaya', 'pemeliharaan'))
                ->sum('total_keseluruhan');

            $periodStart = OutlineAgreement::query()
                ->where('status', OutlineAgreement::STATUS_ACTIVE)
                ->min('current_period_start');
            $periodEnd = OutlineAgreement::query()
                ->where('status', OutlineAgreement::STATUS_ACTIVE)
                ->max('current_period_end');

            $totalSeluruhAmount = (int) ($data['totalSeluruhAmount'] ?? 0);

            $view->with([
                'totalKuotaKontrak' => $totalKuotaKontrak,
                'targetPemeliharaan' => $targetPemeliharaan,
                'totalJasaPemeliharaan' => $totalJasaPemeliharaan,
                'sisaBiayaPemeliharaan' => $data['sisaBiayaPemeliharaan'] ?? ($targetPemeliharaan - $totalJasaPemeliharaan),
                'sisaKuotaKontrak' => $data['sisaKuotaKontrak'] ?? ($totalKuotaKontrak - $totalSeluruhAmount),
                'periodeKontrak' => $data['periodeKontrak'] ?? [
                    'start' => $periodStart ?: null,
                    'end' => $periodEnd ?: null,
                    'adendum' => null,
                ],
            ]);
        });
    }
}
