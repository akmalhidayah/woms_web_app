<?php

namespace App\Support;

use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\Order;
use App\Models\OutlineAgreement;
use Illuminate\Support\Collection;
use Throwable;

class ApprovalFlowSignerPreview
{
    public function __construct(
        private readonly HppApproverResolver $hppApproverResolver,
        private readonly BastApproverResolver $bastApproverResolver,
    ) {}

    public function hppPreviewPayload(Collection|array $orders, Collection|array $outlineAgreements, ?Hpp $hpp = null): array
    {
        $orderPayload = collect($orders)
            ->filter(fn (mixed $order): bool => $order instanceof Order)
            ->mapWithKeys(fn (Order $order): array => [(string) $order->getKey() => $this->hppRequesterNames($order)])
            ->all();

        $outlineAgreementPayload = collect($outlineAgreements)
            ->filter(fn (mixed $agreement): bool => $agreement instanceof OutlineAgreement)
            ->mapWithKeys(fn (OutlineAgreement $agreement): array => [(string) $agreement->getKey() => $this->hppControllerNames($agreement)])
            ->all();

        return [
            'by_index' => $this->signatureNamesByIndex($hpp),
            'orders' => $orderPayload,
            'outline_agreements' => $outlineAgreementPayload,
            'static' => $this->hppStaticNames(),
        ];
    }

    public function bastPreviewPayload(Collection|array $orders, array $tipePekerjaanOptions, ?LhppBast $lhpp = null): array
    {
        $orderPayload = collect($orders)
            ->filter(fn (mixed $order): bool => $order instanceof Order)
            ->mapWithKeys(fn (Order $order): array => [(string) $order->nomor_order => $this->bastOrderNames($order)])
            ->all();

        $managerPkm = collect($tipePekerjaanOptions)
            ->mapWithKeys(function (mixed $label, mixed $value): array {
                $type = is_string($value) ? $value : (string) $label;
                $preview = new LhppBast(['tipe_pekerjaan' => $type]);

                return [$type => $this->bastSignerName($preview, 'Manager PKM')];
            })
            ->all();

        return [
            'by_index' => $this->signatureNamesByIndex($lhpp),
            'orders' => $orderPayload,
            'manager_pkm' => $managerPkm,
            'static' => [
                'DIROPS' => $this->hppSignerName(new Hpp(['approval_case' => 'FAB-DALAM-UNDER250']), 'DIROPS'),
                'Dirops' => $this->hppSignerName(new Hpp(['approval_case' => 'FAB-DALAM-UNDER250']), 'DIROPS'),
            ],
        ];
    }

    private function hppRequesterNames(Order $order): array
    {
        $hpp = new Hpp(['approval_case' => 'FAB-DALAM-UNDER250']);
        $hpp->setRelation('order', $order);

        return $this->hppNames($hpp, ['Manager Peminta', 'SM Peminta', 'GM Peminta']);
    }

    private function hppControllerNames(OutlineAgreement $outlineAgreement): array
    {
        $hpp = new Hpp(['approval_case' => 'FAB-DALAM-UNDER250']);
        $hpp->setRelation('outlineAgreement', $outlineAgreement);
        $names = $this->hppNames($hpp, ['Manager Pengendali', 'SM Pengendali', 'GM Pengendali']);

        return $names + [
            'Manager' => $names['Manager Pengendali'],
            'SM' => $names['SM Pengendali'],
            'GM' => $names['GM Pengendali'],
        ];
    }

    private function hppStaticNames(): array
    {
        return $this->hppNames(new Hpp(['approval_case' => 'FAB-DALAM-UNDER250']), [
            'Planner Control',
            'DIROPS',
            'Manager Counter Part',
            'SM Counter Part',
        ]);
    }

    /**
     * @param  list<string>  $roles
     */
    private function hppNames(Hpp $hpp, array $roles): array
    {
        return collect($roles)
            ->mapWithKeys(fn (string $role): array => [$role => $this->hppSignerName($hpp, $role)])
            ->all();
    }

    private function hppSignerName(Hpp $hpp, string $role): ?string
    {
        try {
            return $this->hppApproverResolver->resolveApprover($hpp, $role)['user']->name ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    private function bastOrderNames(Order $order): array
    {
        $lhpp = new LhppBast();
        $lhpp->setRelation('order', $order);

        $managerPengendali = $this->bastSignerName($lhpp, 'Manager Pengendali');
        $managerPeminta = $this->bastSignerName($lhpp, 'Manager Peminta');
        $gmPengendali = $this->bastSignerName($lhpp, 'GM Pengendali');
        $dirops = $this->bastSignerName($lhpp, 'DIROPS');

        return [
            'Manager Pengendali' => $managerPengendali,
            'Manager Workshop' => $managerPengendali,
            'Manager Peminta' => $managerPeminta,
            'Manager User' => $managerPeminta,
            'GM Pengendali' => $gmPengendali,
            'GM PMMS' => $gmPengendali,
            'DIROPS' => $dirops,
            'Dirops' => $dirops,
        ];
    }

    private function bastSignerName(LhppBast $lhpp, string $role): ?string
    {
        try {
            return $this->bastApproverResolver->resolveApprover($lhpp, $role)['user']->name ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    private function signatureNamesByIndex(Hpp|LhppBast|null $document): array
    {
        if (! $document?->exists) {
            return [];
        }

        try {
            $document->loadMissing('signatures');

            return $document->signatures
                ->sortBy('step_order')
                ->values()
                ->map(fn (mixed $signature): string => trim((string) $signature->signer_name_snapshot) ?: '-')
                ->all();
        } catch (Throwable) {
            return [];
        }
    }
}
