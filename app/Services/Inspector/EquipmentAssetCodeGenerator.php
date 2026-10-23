<?php

namespace App\Services\Inspector;

use App\Models\EquipmentInspectionUnit;
use Illuminate\Support\Str;

final class EquipmentAssetCodeGenerator
{
    /**
     * Rules are matched against the beginning of the normalized equipment name.
     * Keep the more specific categories before the generic fallback.
     *
     * @var array<string, string>
     */
    private const PREFIX_RULES = [
        'mesin lipat' => 'MB',
        'mesin potong' => 'MP',
        'mesin roll' => 'MR',
        'mesin las' => 'ML',
        'mesin bor' => 'BO',
        'forklift' => 'FL',
        'plasma cutting' => 'PC',
        'overhead crane' => 'OC',
    ];

    public function prefixFor(string $equipmentName): string
    {
        $normalized = Str::of(Str::ascii($equipmentName))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();

        foreach (self::PREFIX_RULES as $category => $prefix) {
            if ($normalized === $category || str_starts_with($normalized, $category.' ')) {
                return $prefix;
            }
        }

        return 'EQ';
    }

    public function nextForName(string $equipmentName): string
    {
        $prefix = $this->prefixFor($equipmentName);
        $pattern = '/^'.preg_quote($prefix, '/').'(\d+)$/i';
        $highest = 0;

        $codes = EquipmentInspectionUnit::query()
            ->whereRaw('UPPER(asset_code) LIKE ?', [$prefix.'%'])
            ->lockForUpdate()
            ->pluck('asset_code');

        foreach ($codes as $code) {
            if (preg_match($pattern, (string) $code, $matches) === 1) {
                $highest = max($highest, (int) $matches[1]);
            }
        }

        return $prefix.str_pad((string) ($highest + 1), 2, '0', STR_PAD_LEFT);
    }
}
