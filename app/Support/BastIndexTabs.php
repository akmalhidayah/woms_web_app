<?php

namespace App\Support;

use App\Models\LhppBast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BastIndexTabs
{
    public const TAB_ACTION = 'action';

    public const TAB_IN_PROGRESS = 'in_progress';

    public const TAB_APPROVED = 'approved';

    public const TAB_HISTORY = 'history';

    public const CONTEXT_ADMIN = 'admin';

    public const CONTEXT_PKM = 'pkm';

    /**
     * @return array<string, string>
     */
    public function options(string $context): array
    {
        return [
            self::TAB_ACTION => 'Perlu Tindakan',
            self::TAB_IN_PROGRESS => $context === self::CONTEXT_PKM ? 'Menunggu Proses' : 'Dalam Approval',
            self::TAB_APPROVED => 'Approved',
            self::TAB_HISTORY => 'Riwayat',
        ];
    }

    public function normalize(?string $tab): string
    {
        return array_key_exists((string) $tab, $this->options(self::CONTEXT_ADMIN))
            ? (string) $tab
            : self::TAB_ACTION;
    }

    public function apply(Builder $query, string $tab, string $context): Builder
    {
        return match ($this->normalize($tab)) {
            self::TAB_IN_PROGRESS => $query->where(
                fn (Builder $builder): Builder => $this->applyInProgress($builder, $context)
            ),
            self::TAB_APPROVED => $query->where(
                fn (Builder $builder): Builder => $this->applyApproved($builder, $context)
            ),
            self::TAB_HISTORY => $query->where(
                fn (Builder $builder): Builder => $this->applyHistory($builder, $context)
            ),
            default => $query->where(
                fn (Builder $builder): Builder => $this->applyAction($builder, $context)
            ),
        };
    }

    /**
     * @return array<string, int>
     */
    public function counts(string $context): array
    {
        return collect(array_keys($this->options($context)))
            ->mapWithKeys(fn (string $tab): array => [
                $tab => $this->apply($this->baseQuery(), $tab, $context)->count(),
            ])
            ->all();
    }

    public function applyLatestActivityOrder(Builder $query): Builder
    {
        $documentActivity = DB::table('lhpp_basts')
            ->selectRaw('COALESCE(parent_lhpp_bast_id, id) AS root_id, updated_at AS activity_at');

        $signatureActivity = DB::table('lhpp_bast_signatures')
            ->join('lhpp_basts', 'lhpp_basts.id', '=', 'lhpp_bast_signatures.lhpp_bast_id')
            ->selectRaw(
                'COALESCE(lhpp_basts.parent_lhpp_bast_id, lhpp_basts.id) AS root_id, '
                .'COALESCE(lhpp_bast_signatures.signed_document_uploaded_at, '
                .'lhpp_bast_signatures.signed_at, lhpp_bast_signatures.delegated_at, '
                .'lhpp_bast_signatures.created_at) AS activity_at'
            );

        $allActivity = $documentActivity->unionAll($signatureActivity);
        $latestActivity = DB::query()
            ->fromSub($allActivity, 'bast_activity')
            ->selectRaw('root_id, MAX(activity_at) AS last_activity_at')
            ->groupBy('root_id');

        return $query
            ->leftJoinSub($latestActivity, 'latest_bast_activity', function ($join): void {
                $join->on('latest_bast_activity.root_id', '=', 'lhpp_basts.id');
            })
            ->select('lhpp_basts.*')
            ->orderByDesc('latest_bast_activity.last_activity_at')
            ->orderByDesc('lhpp_basts.id');
    }

    private function baseQuery(): Builder
    {
        return LhppBast::query()->where('termin_type', 'termin_1');
    }

    private function applyAction(Builder $query, string $context): Builder
    {
        return $query->where(function (Builder $builder) use ($context): void {
            $this->applyProblem($builder);

            if ($context === self::CONTEXT_ADMIN) {
                $builder->orWhere('quality_control_status', 'pending');
            } else {
                $builder->orWhere(fn (Builder $nested): Builder => $this->applyNeedsTerminTwo($nested));
            }
        });
    }

    private function applyInProgress(Builder $query, string $context): Builder
    {
        return $query
            ->whereNot(fn (Builder $builder): Builder => $this->applyAction($builder, $context))
            ->where(function (Builder $builder) use ($context): void {
                if ($context === self::CONTEXT_PKM) {
                    $builder->where('quality_control_status', 'pending')
                        ->orWhere(fn (Builder $nested): Builder => $this->applyApprovalInProgress($nested));

                    return;
                }

                $this->applyApprovalInProgress($builder);
            });
    }

    private function applyApproved(Builder $query, string $context): Builder
    {
        return $query
            ->where('approval_status', LhppBast::APPROVAL_APPROVED)
            ->when(
                $context === self::CONTEXT_ADMIN,
                fn (Builder $builder): Builder => $builder->where('quality_control_status', 'approved')
            )
            ->whereNot(fn (Builder $builder): Builder => $this->applyAction($builder, $context))
            ->whereNot(fn (Builder $builder): Builder => $this->applyInProgress($builder, $context))
            ->whereNot(fn (Builder $builder): Builder => $this->applyCompletedHistory($builder));
    }

    private function applyProblem(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder
                ->where('approval_status', LhppBast::APPROVAL_REJECTED)
                ->orWhere('quality_control_status', 'rejected')
                ->orWhereHas('terminTwo', fn (Builder $terminTwo): Builder => $terminTwo
                    ->where('approval_status', LhppBast::APPROVAL_REJECTED))
                ->orWhereDoesntHave('garansi')
                ->orWhereNull('approval_status')
                ->orWhereNotIn('approval_status', [
                    LhppBast::APPROVAL_IN_REVIEW,
                    LhppBast::APPROVAL_APPROVED,
                    LhppBast::APPROVAL_REJECTED,
                ])
                ->orWhereNull('quality_control_status')
                ->orWhereNotIn('quality_control_status', ['pending', 'approved', 'rejected'])
                ->orWhereNull('termin1_status')
                ->orWhereNotIn('termin1_status', ['belum', 'sudah'])
                ->orWhereNull('termin2_status')
                ->orWhereNotIn('termin2_status', ['belum', 'sudah'])
                ->orWhereHas('terminTwo', fn (Builder $terminTwo): Builder => $terminTwo
                    ->whereNull('approval_status')
                    ->orWhereNotIn('approval_status', [
                        LhppBast::APPROVAL_IN_REVIEW,
                        LhppBast::APPROVAL_APPROVED,
                        LhppBast::APPROVAL_REJECTED,
                    ]));
        });
    }

    private function applyNeedsTerminTwo(Builder $query): Builder
    {
        return $query
            ->where('termin1_status', 'sudah')
            ->whereHas('garansi', fn (Builder $garansi): Builder => $garansi->where('garansi_months', '>', 0))
            ->whereDoesntHave('terminTwo');
    }

    private function applyApprovalInProgress(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder
                ->where(function (Builder $terminOne): void {
                    $terminOne
                        ->where('approval_status', LhppBast::APPROVAL_IN_REVIEW)
                        ->where('quality_control_status', 'approved');
                })
                ->orWhereHas('terminTwo', fn (Builder $terminTwo): Builder => $terminTwo
                    ->where('approval_status', LhppBast::APPROVAL_IN_REVIEW));
        });
    }

    private function applyHistory(Builder $query, string $context): Builder
    {
        return $query
            ->whereNot(fn (Builder $builder): Builder => $this->applyAction($builder, $context))
            ->where(fn (Builder $builder): Builder => $this->applyCompletedHistory($builder));
    }

    private function applyCompletedHistory(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder
                ->where(function (Builder $withoutWarranty): void {
                    $withoutWarranty
                        ->where('approval_status', LhppBast::APPROVAL_APPROVED)
                        ->where('termin1_status', 'sudah')
                        ->whereHas('garansi', fn (Builder $garansi): Builder => $garansi
                            ->where('garansi_months', 0));
                })
                ->orWhere(function (Builder $withWarranty): void {
                    $withWarranty
                        ->where('approval_status', LhppBast::APPROVAL_APPROVED)
                        ->where('termin1_status', 'sudah')
                        ->where('termin2_status', 'sudah')
                        ->whereHas('garansi', fn (Builder $garansi): Builder => $garansi
                            ->where('garansi_months', '>', 0))
                        ->whereHas('terminTwo', fn (Builder $terminTwo): Builder => $terminTwo
                            ->where('approval_status', LhppBast::APPROVAL_APPROVED));
                });
        });
    }
}
