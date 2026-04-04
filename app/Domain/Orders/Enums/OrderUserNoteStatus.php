<?php

namespace App\Domain\Orders\Enums;

enum OrderUserNoteStatus: string
{
    case ApprovedJasa = 'approved_jasa';
    case ApprovedWorkshop = 'approved_workshop';
    case ApprovedWorkshopJasa = 'approved_workshop_jasa';
    case Pending = 'pending';
    case Reject = 'reject';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ApprovedJasa => 'Approved (Jasa)',
            self::ApprovedWorkshop => 'Approved (Workshop)',
            self::ApprovedWorkshopJasa => 'Approved (Workshop + Jasa)',
            self::Pending => 'Pending',
            self::Reject => 'Reject',
        };
    }

    /**
     * Get the label with icon for select inputs.
     */
    public function selectLabel(): string
    {
        return match ($this) {
            self::ApprovedJasa => '✅ Approved (Jasa)',
            self::ApprovedWorkshop => '✅ Approved (Workshop)',
            self::ApprovedWorkshopJasa => '✅ Approved (Workshop + Jasa)',
            self::Pending => '⏳ Pending',
            self::Reject => '⛔ Reject',
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
            static fn (self $status) => $status->value,
            self::cases(),
        );
    }

    /**
     * Get the select options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $status) {
            $options[$status->value] = $status->selectLabel();
        }

        return $options;
    }
}
