<?php

namespace App\Support;

use App\Models\Order;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;

final class WorkshopHandoverRecipientResolver
{
    /** @var list<UnitWork>|null */
    private ?array $units = null;

    /**
     * @return array{user: ?User, section: ?UnitWorkSection, ambiguous: bool}
     */
    public function resolve(Order $order): array
    {
        $units = $this->structureUnits();
        $unitCandidates = array_values(array_filter(
            $units,
            fn (UnitWork $unit): bool => $this->same($unit->name, $order->unit_kerja),
        ));

        if (count($unitCandidates) !== 1) {
            return ['user' => null, 'section' => null, 'ambiguous' => count($unitCandidates) > 1];
        }

        $sections = $unitCandidates[0]->sections->filter(
            fn (UnitWorkSection $section): bool => $this->same($section->name, $order->seksi),
        )->values();

        $managers = $sections->map(fn (UnitWorkSection $section): ?User => $section->manager)
            ->filter(fn (?User $user): bool => $user !== null && filled($user->email))
            ->unique('id')
            ->values();

        return [
            'user' => $managers->count() === 1 ? $managers->first() : null,
            'section' => $sections->count() === 1 ? $sections->first() : null,
            'ambiguous' => $managers->count() > 1 || $sections->count() > 1,
        ];
    }

    /** @return list<UnitWork> */
    private function structureUnits(): array
    {
        if ($this->units !== null) {
            return $this->units;
        }

        return $this->units = UnitWork::query()
            ->with(['sections.manager'])
            ->orderBy('id')
            ->get()
            ->all();
    }

    private function same(?string $left, ?string $right): bool
    {
        return trim((string) $left) !== ''
            && strcasecmp(trim((string) $left), trim((string) $right)) === 0;
    }
}
