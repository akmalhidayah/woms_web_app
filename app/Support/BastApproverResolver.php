<?php

namespace App\Support;

use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\User;
use App\Models\VendorWorkType;
use App\Models\VendorWorkTypeSection;
use Illuminate\Validation\ValidationException;

class BastApproverResolver
{
    public function __construct(
        private readonly HppApproverResolver $hppApproverResolver,
    ) {
    }

    /**
     * @return array{
     *     role_key: string,
     *     role_label: string,
     *     user: User,
     *     position: string,
     *     department: ?string,
     *     unit: ?string,
     *     section: ?string
     * }
     */
    public function resolveApprover(LhppBast $lhpp, string $flowRoleLabel): array
    {
        $roleKey = $this->roleKeyFor($flowRoleLabel);

        if ($roleKey === 'manager_pkm') {
            return $this->resolveManagerPkm($lhpp, $roleKey, $flowRoleLabel);
        }

        $hpp = $this->resolveHpp($lhpp);
        $hppRoleLabel = match ($roleKey) {
            'manager_pengendali' => 'Manager Pengendali',
            'manager_peminta' => 'Manager Peminta',
            'gm_pengendali' => 'GM Pengendali',
            'dirops' => 'DIROPS',
            default => $flowRoleLabel,
        };

        return $this->hppApproverResolver->resolveApprover($hpp, $hppRoleLabel);
    }

    private function roleKeyFor(string $flowRoleLabel): string
    {
        return match ($flowRoleLabel) {
            'Manager PKM' => 'manager_pkm',
            'Manager Pengendali', 'Manager Workshop' => 'manager_pengendali',
            'Manager Peminta', 'Manager User' => 'manager_peminta',
            'GM Pengendali', 'GM PMMS' => 'gm_pengendali',
            'DIROPS', 'Dirops' => 'dirops',
            default => throw ValidationException::withMessages([
                'approval' => "Role approval BAST {$flowRoleLabel} tidak dikenali.",
            ]),
        };
    }

    /**
     * @return array{
     *     role_key: string,
     *     role_label: string,
     *     user: User,
     *     position: string,
     *     department: ?string,
     *     unit: ?string,
     *     section: ?string
     * }
     */
    private function resolveManagerPkm(LhppBast $lhpp, string $roleKey, string $flowRoleLabel): array
    {
        $tipePekerjaan = trim((string) $lhpp->tipe_pekerjaan);

        if ($tipePekerjaan === '') {
            throw ValidationException::withMessages([
                'approval' => 'Tipe pekerjaan BAST wajib diisi untuk menentukan Manager PKM.',
            ]);
        }

        $vendorSection = VendorWorkTypeSection::query()
            ->with(['manager', 'vendorWorkType'])
            ->where('name', $tipePekerjaan)
            ->first();

        if ($vendorSection?->manager) {
            return [
                'role_key' => $roleKey,
                'role_label' => $flowRoleLabel,
                'user' => $vendorSection->manager,
                'position' => $this->managerPosition($vendorSection->name),
                'department' => 'PT. PKM',
                'unit' => $vendorSection->vendorWorkType?->name,
                'section' => $vendorSection->name,
            ];
        }

        $vendorWorkType = VendorWorkType::query()
            ->with('manager')
            ->where('name', $tipePekerjaan)
            ->first();

        if (! $vendorWorkType?->manager) {
            throw ValidationException::withMessages([
                'approval' => "Manager PKM untuk tipe pekerjaan {$tipePekerjaan} belum dikonfigurasi.",
            ]);
        }

        return [
            'role_key' => $roleKey,
            'role_label' => $flowRoleLabel,
            'user' => $vendorWorkType->manager,
            'position' => $this->managerPosition($vendorWorkType->name),
            'department' => 'PT. PKM',
            'unit' => $vendorWorkType->name,
            'section' => null,
        ];
    }

    private function managerPosition(?string $sectionName): string
    {
        $sectionName = trim((string) $sectionName);

        return $sectionName !== '' ? "Manager {$sectionName}" : 'Manager';
    }

    private function resolveHpp(LhppBast $lhpp): Hpp
    {
        $lhpp->loadMissing([
            'hpp.order',
            'hpp.outlineAgreement.unitWork.department.generalManager',
            'hpp.outlineAgreement.unitWork.seniorManager',
            'hpp.outlineAgreement.unitWork.sections.manager',
            'order.latestHpp.order',
            'order.latestHpp.outlineAgreement.unitWork.department.generalManager',
            'order.latestHpp.outlineAgreement.unitWork.seniorManager',
            'order.latestHpp.outlineAgreement.unitWork.sections.manager',
        ]);

        $hpp = $lhpp->hpp ?: $lhpp->order?->latestHpp;

        if (! $hpp) {
            throw ValidationException::withMessages([
                'approval' => 'HPP sumber BAST tidak ditemukan.',
            ]);
        }

        return $hpp;
    }
}
