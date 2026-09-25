<?php

namespace App\Support\AppSheet;

use InvalidArgumentException;

class StockSheetMap
{
    public const CONSUMABLE_BMS = 'consumable-bms';

    public const CONSUMABLE_GUDANG = 'consumable-gudang';

    public const MATERIAL_BMS = 'material-bms';

    public const MATERIAL_GUDANG = 'material-gudang';

    /** @return list<string> */
    public static function kinds(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * @return array{
     *   sheet_config: string,
     *   cache_key: string,
     *   identifier_header: string,
     *   identifier_aliases: list<string>,
     *   name_headers: list<string>,
     *   unit_header: string,
     *   audit_by_header: string,
     *   audit_date_header: string,
     *   editable_fields: array<string, array{header: string, original: string, label: string}>,
     *   additional_headers: list<string>,
     *   adjustment: bool
     * }
     */
    public static function definition(string $stockKind): array
    {
        return self::definitions()[$stockKind]
            ?? throw new InvalidArgumentException('Jenis stock tidak didukung.');
    }

    /**
     * @return array<string, array{
     *   sheet_config: string,
     *   cache_key: string,
     *   identifier_header: string,
     *   identifier_aliases: list<string>,
     *   name_headers: list<string>,
     *   unit_header: string,
     *   audit_by_header: string,
     *   audit_date_header: string,
     *   editable_fields: array<string, array{header: string, original: string, label: string}>,
     *   additional_headers: list<string>,
     *   adjustment: bool
     * }>
     */
    private static function definitions(): array
    {
        return [
            self::CONSUMABLE_BMS => [
                'sheet_config' => 'stock_consumable_sheet',
                'cache_key' => 'appsheet:stock-consumable',
                'identifier_header' => 'UID',
                'identifier_aliases' => [],
                'name_headers' => ['DESC.'],
                'unit_header' => 'STN',
                'audit_by_header' => 'INPUT. BY',
                'audit_date_header' => 'INPUT DATE',
                'editable_fields' => [
                    'spare_stock' => [
                        'header' => 'SPARE STOCK',
                        'original' => 'original_spare_stock',
                        'label' => 'SPARE STOCK',
                    ],
                ],
                'additional_headers' => ['STOCK IN', 'STOCK OUT'],
                'adjustment' => true,
            ],
            self::CONSUMABLE_GUDANG => [
                'sheet_config' => 'stock_consumable_gudang_sheet',
                'cache_key' => 'appsheet:stock-consumable-gudang',
                'identifier_header' => 'NO MATERIAL',
                'identifier_aliases' => ['NO. MATERIAL'],
                'name_headers' => ['CONSUMABLE', 'DESKRIPSI'],
                'unit_header' => 'STN',
                'audit_by_header' => 'UPD. BY',
                'audit_date_header' => 'UPD. DATE',
                'editable_fields' => [
                    'qty_consignment' => [
                        'header' => 'QTY KONSINYASI',
                        'original' => 'original_qty_consignment',
                        'label' => 'QTY KONSINYASI',
                    ],
                    'qty_non_consignment' => [
                        'header' => 'QTY NON KONSINYASI',
                        'original' => 'original_qty_non_consignment',
                        'label' => 'QTY NON KONSINYASI',
                    ],
                ],
                'additional_headers' => [],
                'adjustment' => false,
            ],
            self::MATERIAL_BMS => [
                'sheet_config' => 'stock_material_bms_sheet',
                'cache_key' => 'appsheet:stock-material-bms',
                'identifier_header' => 'UID',
                'identifier_aliases' => [],
                'name_headers' => ['JENIS MATERIAL'],
                'unit_header' => 'STN',
                'audit_by_header' => 'UPD. BY',
                'audit_date_header' => 'LAST UPDATE',
                'editable_fields' => [
                    'quantity' => [
                        'header' => 'QTY',
                        'original' => 'original_quantity',
                        'label' => 'QTY',
                    ],
                ],
                'additional_headers' => [],
                'adjustment' => false,
            ],
            self::MATERIAL_GUDANG => [
                'sheet_config' => 'stock_material_gudang_sheet',
                'cache_key' => 'appsheet:stock-material-gudang',
                'identifier_header' => 'NO. MATERIAL',
                'identifier_aliases' => ['NO MATERIAL'],
                'name_headers' => ['MATERIAL', 'DESKRIPSI'],
                'unit_header' => 'STN',
                'audit_by_header' => 'UPDATE BY',
                'audit_date_header' => 'UPDATE DATE',
                'editable_fields' => [
                    'quantity' => [
                        'header' => 'QTY',
                        'original' => 'original_quantity',
                        'label' => 'QTY',
                    ],
                    'qty_capex' => [
                        'header' => 'QTY CAPEX',
                        'original' => 'original_qty_capex',
                        'label' => 'QTY CAPEX',
                    ],
                ],
                'additional_headers' => [],
                'adjustment' => false,
            ],
        ];
    }
}
