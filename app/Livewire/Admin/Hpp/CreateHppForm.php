<?php

namespace App\Livewire\Admin\Hpp;

use Livewire\Component;

class CreateHppForm extends Component
{
    private const THRESHOLD = 250_000_000;

    public string $selectedOrder = 'ORD-2026-0012';
    public string $nilaiHpp = '185000000';
    public string $kategoriPekerjaan = 'Fabrikasi';
    public string $areaPekerjaan = 'Dalam';

    public array $orderOptions = [];
    public array $kategoriOptions = ['Fabrikasi', 'Konstruksi'];
    public array $areaOptions = ['Dalam', 'Luar'];

    public function mount(): void
    {
        $this->orderOptions = [
            [
                'value' => 'ORD-2026-0012',
                'label' => 'ORD-2026-0012 - Fabrikasi support kiln',
                'cost_centre' => 'CC-WS-014',
                'description' => 'Fabrikasi support kiln untuk shutdown area workshop utama.',
                'requesting_unit' => 'Unit Produksi Kiln',
                'controlling_unit' => 'Unit of Workshop & Design',
                'outline_agreement' => 'OA/WS/2026/014',
                'oa_period' => '01/01/2026 - 31/12/2026',
            ],
            [
                'value' => 'ORD-2026-0015',
                'label' => 'ORD-2026-0015 - Konstruksi area packing',
                'cost_centre' => 'CC-KON-022',
                'description' => 'Konstruksi perbaikan civil area packing line dan jalur akses material.',
                'requesting_unit' => 'Unit Packing Plant',
                'controlling_unit' => 'Unit of Workshop & Design',
                'outline_agreement' => 'OA/KON/2026/022',
                'oa_period' => '15/01/2026 - 15/10/2026',
            ],
            [
                'value' => 'ORD-2026-0020',
                'label' => 'ORD-2026-0020 - Fabrikasi luar area pabrik',
                'cost_centre' => 'CC-EXT-031',
                'description' => 'Fabrikasi struktur penyangga untuk pekerjaan luar area pabrik.',
                'requesting_unit' => 'Unit Proyek Eksternal',
                'controlling_unit' => 'Unit of Workshop & Design',
                'outline_agreement' => 'OA/EXT/2026/031',
                'oa_period' => '01/02/2026 - 30/11/2026',
            ],
            [
                'value' => 'ORD-2026-0024',
                'label' => 'ORD-2026-0024 - Konstruksi sipil area utility',
                'cost_centre' => 'CC-UTL-018',
                'description' => 'Konstruksi sipil dan perkuatan pondasi pada area utility plant.',
                'requesting_unit' => 'Unit Utility & Support',
                'controlling_unit' => 'Unit of Workshop & Design',
                'outline_agreement' => 'OA/UTL/2026/018',
                'oa_period' => '10/02/2026 - 31/08/2026',
            ],
        ];
    }

    public function updatedNilaiHpp(string $value): void
    {
        $this->nilaiHpp = preg_replace('/\D+/', '', $value) ?? '';
    }

    public function render()
    {
        $previewCase = $this->resolvePreviewCase();
        $approvalFlow = $this->resolveApprovalFlow();

        return view('livewire.admin.hpp.create-hpp-form', [
            'threshold' => self::THRESHOLD,
            'previewCase' => $previewCase,
            'approvalFlow' => $approvalFlow,
            'formattedNilaiHpp' => number_format($this->normalizeNilaiHpp(), 0, ',', '.'),
            'thresholdLabel' => $this->isOverThreshold() ? 'OVER 250 JT' : 'UNDER 250 JT',
            'selectedOrderLabel' => $this->resolveSelectedOrderLabel(),
        ]);
    }

    private function isOverThreshold(): bool
    {
        return $this->normalizeNilaiHpp() > self::THRESHOLD;
    }

    private function normalizeNilaiHpp(): int
    {
        $numeric = preg_replace('/\D+/', '', $this->nilaiHpp);

        return (int) ($numeric ?: 0);
    }

    private function resolvePreviewCase(): ?string
    {
        if ($this->kategoriPekerjaan === '' || $this->areaPekerjaan === '') {
            return null;
        }

        $prefix = $this->kategoriPekerjaan === 'Fabrikasi' ? 'FAB' : 'KONS';
        $area = strtoupper($this->areaPekerjaan);
        $bucket = $this->isOverThreshold() ? 'OVER250' : 'UNDER250';

        return "{$prefix}-{$area}-{$bucket}";
    }

    private function resolveApprovalFlow(): array
    {
        if ($this->kategoriPekerjaan === '' || $this->areaPekerjaan === '') {
            return [];
        }

        $bucket = $this->isOverThreshold() ? 'over' : 'under';

        $flows = [
            'Fabrikasi' => [
                'Dalam' => [
                    'under' => [
                        'Manager Pengendali',
                        'SM Pengendali',
                        'Manager Peminta',
                        'SM Peminta',
                        'GM Peminta',
                        'GM Pengendali',
                    ],
                    'over' => [
                        'Manager Pengendali',
                        'SM Pengendali',
                        'Manager Peminta',
                        'SM Peminta',
                        'GM Peminta',
                        'GM Pengendali',
                        'DIROPS',
                    ],
                ],
                'Luar' => [
                    'under' => [
                        'Planner Control',
                        'Manager Pengendali',
                        'SM Pengendali',
                        'Manager Peminta',
                        'SM Peminta',
                        'GM Peminta',
                        'GM Pengendali',
                    ],
                    'over' => [
                        'Planner Control',
                        'Manager Pengendali',
                        'SM Pengendali',
                        'Manager Peminta',
                        'SM Peminta',
                        'GM Peminta',
                        'GM Pengendali',
                        'DIROPS',
                    ],
                ],
            ],
            'Konstruksi' => [
                'Dalam' => [
                    'under' => [
                        'Manager Counter Part',
                        'SM Counter Part',
                        'Manager Pengendali',
                        'SM Pengendali',
                        'Manager Peminta',
                        'SM Peminta',
                        'GM Peminta',
                        'GM Pengendali',
                    ],
                    'over' => [
                        'Manager Counter Part',
                        'SM Counter Part',
                        'Manager Pengendali',
                        'SM Pengendali',
                        'Manager Peminta',
                        'SM Peminta',
                        'GM Peminta',
                        'GM Pengendali',
                        'DIROPS',
                    ],
                ],
                'Luar' => [
                    'under' => [
                        'Manager Counter Part',
                        'SM Counter Part',
                        'Planner Control',
                        'Manager Pengendali',
                        'SM Pengendali',
                        'Manager Peminta',
                        'SM Peminta',
                        'GM Peminta',
                        'GM Pengendali',
                    ],
                    'over' => [
                        'Manager Counter Part',
                        'SM Counter Part',
                        'Planner Control',
                        'Manager Pengendali',
                        'SM Pengendali',
                        'Manager Peminta',
                        'SM Peminta',
                        'GM Peminta',
                        'GM Pengendali',
                        'DIROPS',
                    ],
                ],
            ],
        ];

        return $flows[$this->kategoriPekerjaan][$this->areaPekerjaan][$bucket] ?? [];
    }

    private function resolveSelectedOrderLabel(): ?string
    {
        foreach ($this->orderOptions as $option) {
            if ($option['value'] === $this->selectedOrder) {
                return $option['label'];
            }
        }

        return null;
    }
}
