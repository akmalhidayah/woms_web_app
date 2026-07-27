<?php

namespace App\Support;

use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\User;
use App\Models\VendorWorkType;
use App\Models\VendorWorkTypeSection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BastApproverResolver
{
    public function __construct(
        private readonly HppApproverResolver $hppApproverResolver,
    ) {}

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
            'sm_pengendali' => 'SM Pengendali',
            'manager_peminta' => 'Manager Peminta',
            'gm_pengendali' => 'GM Pengendali',
            'dirops' => 'DIROPS',
            default => $flowRoleLabel,
        };

        try {
            return $this->hppApproverResolver->resolveApprover($hpp, $hppRoleLabel);
        } catch (ValidationException $exception) {
            if ($roleKey === 'sm_pengendali') {
                throw ValidationException::withMessages([
                    'approval' => 'SM Pengendali tidak ditemukan pada struktur unit pengendali HPP.',
                ]);
            }

            throw $exception;
        }
    }

    private function roleKeyFor(string $flowRoleLabel): string
    {
        return match ($flowRoleLabel) {
            'Manager PKM' => 'manager_pkm',
            'Manager Pengendali', 'Manager Workshop' => 'manager_pengendali',
            'SM Pengendali', 'SM PMMS' => 'sm_pengendali',
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

        $fixedVendor = VendorWorkType::query()->where('name', VendorWorkType::FIXED_VENDOR_NAME)->first();
        $vendorSection = VendorWorkTypeSection::query()
            ->with(['manager', 'vendorWorkType'])
            ->where('vendor_work_type_id', $fixedVendor?->id)
            ->when($lhpp->vendor_work_type_section_id,
                fn ($query) => $query->whereKey($lhpp->vendor_work_type_section_id),
                fn ($query) => $query->where(function ($sectionQuery) use ($tipePekerjaan): void {
                    $sectionQuery->where('normalized_name', Str::lower($tipePekerjaan))
                        ->orWhereRaw('LOWER(TRIM(name)) = ?', [Str::lower($tipePekerjaan)]);
                }))
            ->first();

        if ($vendorSection?->manager) {
            return [
                'role_key' => $roleKey,
                'role_label' => $flowRoleLabel,
                'user' => $vendorSection->manager,
                'position' => $this->managerPosition($vendorSection->name),
                'department' => VendorWorkType::FIXED_VENDOR_NAME,
                'unit' => $vendorSection->vendorWorkType?->name,
                'section' => $vendorSection->name,
            ];
        }

        throw ValidationException::withMessages([
            'approval' => "Manager PKM untuk seksi {$tipePekerjaan} pada vendor ".VendorWorkType::FIXED_VENDOR_NAME.' belum dikonfigurasi.',
        ]);
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
            'order.latestApprovedHpp.order',
            'order.latestApprovedHpp.outlineAgreement.unitWork.department.generalManager',
            'order.latestApprovedHpp.outlineAgreement.unitWork.seniorManager',
            'order.latestApprovedHpp.outlineAgreement.unitWork.sections.manager',
        ]);

        $hpp = $lhpp->hpp ?: $lhpp->order?->latestApprovedHpp;

        if (! $hpp) {
            throw ValidationException::withMessages([
                'approval' => 'HPP sumber BAST tidak ditemukan.',
            ]);
        }

        return $hpp;
    }
}
