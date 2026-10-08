<?php

namespace App\Support\Inspector;

final class EquipmentFormCatalog
{
    private const PLASMA_ITEMS = [
        'nozzle' => 'Check Nozzle',
        'compressor' => 'Check Compressor',
        'kelistrikan' => 'Check Kelistrikan',
        'panel-listrik' => 'Check Panel Listrik',
        'selang-udara' => 'Check Selang Udara (Hose)',
        'tombol-kendali' => 'Check Tombol Kendali',
        'system' => 'Check System',
        'electrode' => 'Check Electrode',
        'water-coolant' => 'Check Water Coolant',
        'regulator' => 'Check Regulator',
        'kebersihan' => 'Check Kebersihan Mesin',
    ];

    private const CRANE_ITEMS = [
        'motor-listrik' => 'Check Motor Listrik',
        'drum-roller' => 'Check Drum Roller',
        'remote' => 'Check Remote',
        'motor-drive' => 'Check Motor Drive',
        'panel-induk' => 'Check Panel Induk/Panel Listrik',
        'wire-rope' => 'Check Wire Rope',
        'bearing-roda' => 'Check Bearing Roda',
    ];

    // Keys are stable identifiers, independent of display labels and item order.
    private const FORMS = [
        'mesin-lipat' => [
            'name' => 'Mesin Lipat',
            'icon' => 'fold-vertical',
            'description' => 'Pemeriksaan bearing, penghubung, tuas, dan pelumas.',
            'groups' => [
                'utama' => ['name' => null, 'items' => [
                    'bearing-shaft' => 'Check Bearing Shaft',
                    'gear-penghubung' => 'Check gear penghubung',
                    'tuas-beban' => 'Check Tuas Beban',
                    'pelumas' => 'Pelumas',
                ]],
            ],
        ],
        'mesin-potong' => [
            'name' => 'Mesin Potong',
            'icon' => 'scissors',
            'description' => 'Pemeriksaan pisau potong, sistem udara, dan penggerak.',
            'groups' => [
                'utama' => ['name' => null, 'items' => [
                    'compressor' => 'Check compressor',
                    'pisau-potong' => 'Check pisau potong',
                    'regulator-udara' => 'Check Regulator Udara',
                    'v-belt' => 'Check V-belt',
                    'pipa-udara' => 'Check pipa udara',
                    'motor-listrik' => 'Check Motor Listrik',
                    'baut-pisau' => 'Check baut pengikat pisau',
                ]],
            ],
        ],
        'mesin-roll' => [
            'name' => 'Mesin Roll',
            'icon' => 'cog',
            'description' => 'Pemeriksaan penggerak, kelistrikan, dan pengaman mesin.',
            'groups' => [
                'utama' => ['name' => null, 'items' => [
                    'motor-listrik' => 'Check motor listrik',
                    'pinion-gear' => 'Check Pinion Gear',
                    'panel-listrik' => 'Check Panel listrik',
                    'bushing' => 'Check Bushing',
                    'shaft' => 'Check Shaft mesin',
                    'saklar-emergency' => 'Check Saklar emergency',
                    'pelumasan' => 'Pelumasan',
                ]],
            ],
        ],
        'mesin-las-rc-500' => [
            'name' => 'Mesin Las RC 500',
            'icon' => 'zap',
            'description' => 'Pemeriksaan regulator, trafo, ampere, dan kabel las.',
            'groups' => [
                'utama' => ['name' => null, 'items' => [
                    'regulator' => 'Check Regulator',
                    'trafo-induk' => 'Check Trafo Induk',
                    'tombol-ampere' => 'Check Tombol Ampere',
                    'kabel-las' => 'Check Kabel Las',
                    'kebersihan' => 'Check Kebersihan Mesin',
                ]],
            ],
        ],
        'mesin-bor-meja' => [
            'name' => 'Mesin Bor Meja',
            'icon' => 'drill',
            'description' => 'Satu form untuk tipe 2Y 4132 dan BS-40, masing-masing 3 item.',
            'groups' => [
                '2y-4132' => ['name' => 'Tipe 2Y 4132', 'items' => [
                    'kelistrikan' => 'Check kelistrikan',
                    'v-belt' => 'Check V-Belt',
                    'tombol-on-off' => 'Check Tombol On/Off',
                ]],
                'bs-40' => ['name' => 'Tipe BS-40', 'items' => [
                    'kelistrikan' => 'Check kelistrikan',
                    'v-belt' => 'Check V-Belt',
                    'tombol-on-off' => 'Check Tombol On/Off',
                ]],
            ],
        ],
        'forklift-9-54' => [
            'name' => 'Forklift 9-54',
            'icon' => 'forklift',
            'description' => 'Pemeriksaan bahan bakar, hidraulik, rem, dan kondisi operasional.',
            'groups' => [
                'utama' => ['name' => null, 'items' => [
                    'solar' => 'Check Solar',
                    'oli' => 'Check Oli',
                    'hydraulik' => 'Check Hydraulik',
                    'accu' => 'Check Accu',
                    'rem' => 'Check Rem',
                    'lampu' => 'Check Lampu',
                    'air-radiator' => 'Air Radiator',
                    'tekanan-angin' => 'Tekanan Angin/Udara',
                ]],
            ],
        ],
        'plasma-cutting-esp-150' => [
            'name' => 'Plasma Cutting ESP-150',
            'icon' => 'scan-line',
            'description' => 'Pemeriksaan komponen pemotongan, kontrol, dan pendinginan ESP-150.',
            'groups' => [
                'utama' => ['name' => null, 'items' => self::PLASMA_ITEMS],
            ],
        ],
        'plasma-cutting-tomahawk-1538' => [
            'name' => 'Plasma Cutting Tomahawk 1538',
            'icon' => 'scan-line',
            'description' => 'Pemeriksaan komponen pemotongan, kontrol, dan pendinginan Tomahawk.',
            'groups' => [
                'utama' => ['name' => null, 'items' => self::PLASMA_ITEMS],
            ],
        ],
        'overhead-crane-demac-556980' => [
            'name' => 'Overhead Crane Demac 10T (556980)',
            'icon' => 'container',
            'description' => 'Pemeriksaan penggerak, kendali, dan wire rope crane Demac.',
            'groups' => [
                'utama' => ['name' => null, 'items' => self::CRANE_ITEMS],
            ],
        ],
        'overhead-crane-mhe-20-3055-92' => [
            'name' => 'Overhead Crane MHE 10T (20-3055-92)',
            'icon' => 'container',
            'description' => 'Pemeriksaan penggerak, kendali, dan wire rope crane MHE.',
            'groups' => [
                'utama' => ['name' => null, 'items' => self::CRANE_ITEMS],
            ],
        ],
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        $forms = [];

        foreach (self::FORMS as $formId => $definition) {
            $groups = [];
            $itemCount = 0;

            foreach ($definition['groups'] as $groupId => $group) {
                $items = [];

                foreach ($group['items'] as $itemId => $label) {
                    $items[] = [
                        'id' => $formId.'-'.$groupId.'-'.$itemId,
                        'label' => $label,
                    ];
                }

                $groups[] = ['id' => $formId.'-'.$groupId, 'name' => $group['name'], 'items' => $items];
                $itemCount += count($items);
            }

            $forms[$formId] = [
                'id' => $formId,
                'name' => $definition['name'],
                'icon' => $definition['icon'],
                'description' => $definition['description'],
                'groups' => $groups,
                'item_count' => $itemCount,
            ];
        }

        return $forms;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(string $id): ?array
    {
        return self::all()[$id] ?? null;
    }
}
