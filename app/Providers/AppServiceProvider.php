<?php

namespace App\Providers;

use App\Models\AdminRoleMenuAccess;
use App\Models\Hpp;
use App\Models\OutlineAgreement;
use App\Models\OutlineAgreementTarget;
use App\Support\AdminActionCenter;
use App\Support\AdminMenuPermissions;
use App\Support\AdminNotificationCenter;
use App\Support\AdminPresentationData;
use App\View\Composers\PkmLayoutComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AdminPresentationData::class, function ($app): AdminPresentationData {
            $request = $app['request'];
            $key = AdminPresentationData::class;

            if (! $request->attributes->has($key)) {
                $request->attributes->set($key, new AdminPresentationData(
                    $app->make(AdminActionCenter::class),
                    $app->make(AdminNotificationCenter::class),
                ));
            }

            return $request->attributes->get($key);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        AdminRoleMenuAccess::saved(fn () => app(AdminMenuPermissions::class)->forget());
        AdminRoleMenuAccess::deleted(fn () => app(AdminMenuPermissions::class)->forget());

        RateLimiter::for('organization-api', function (Request $request): Limit {
            $tokenId = $request->user()?->currentAccessToken()?->getKey();
            $requestsPerMinute = max(
                1,
                (int) config('services.organization_api.rate_limit_per_minute', 30),
            );

            return Limit::perMinute($requestsPerMinute)
                ->by('organization-api-token:'.($tokenId ?: 'missing'));
        });

        View::composer('components.layouts.pkm', PkmLayoutComposer::class);
        View::composer('dashboards.admin', function ($view): void {
            $data = $view->getData();

            $totalKuotaKontrak = $data['totalKuotaKontrak'] ?? $this->moneyInt(OutlineAgreement::query()
                ->where('status', OutlineAgreement::STATUS_ACTIVE)
                ->sum('current_total_nilai'));

            $targetPemeliharaan = $data['targetPemeliharaan'] ?? $this->moneyInt(OutlineAgreementTarget::query()
                ->whereHas('outlineAgreement', fn (Builder $query) => $query->where('status', OutlineAgreement::STATUS_ACTIVE))
                ->sum('nilai_target'));

            $totalJasaPemeliharaan = $data['totalJasaPemeliharaan'] ?? $this->moneyInt(Hpp::query()
                ->whereHas('budgetVerification', fn (Builder $query) => $query
                    ->where('status_anggaran', 'Tersedia')
                    ->where('kategori_item', 'jasa')
                    ->where('kategori_biaya', 'pemeliharaan'))
                ->sum('total_keseluruhan'));

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

    private function moneyInt(mixed $value): int
    {
        return (int) round((float) $value);
    }
}
