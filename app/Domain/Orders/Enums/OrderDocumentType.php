<?php

namespace App\Domain\Orders\Enums;

enum OrderDocumentType: string
{
    case Abnormalitas = 'abnormalitas';
    case GambarTeknik = 'gambar_teknik';
    case ScopeOfWork = 'scope_of_work';
    case PurchaseOrder = 'purchase_order';
    case LhppBast = 'lhpp_bast';
    case LpjPpl = 'lpj_ppl';
    case Garansi = 'garansi';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Abnormalitas => 'Abnormalitas',
            self::GambarTeknik => 'Gambar Teknik',
            self::ScopeOfWork => 'Scope of Work',
            self::PurchaseOrder => 'Purchase Order',
            self::LhppBast => 'LHPP / BAST',
            self::LpjPpl => 'LPJ / PPL',
            self::Garansi => 'Garansi',
        };
    }

    /**
     * Get the available values.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $type) => $type->value,
            self::cases(),
        );
    }

    /**
     * Get the required document types.
     *
     * @return list<self>
     */
    public static function required(): array
    {
        return self::cases();
    }

    /**
     * Get the select options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }
}
