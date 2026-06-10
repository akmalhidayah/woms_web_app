<?php

namespace Tests\Feature\Admin\Hpp;

use Tests\TestCase;

class HppApprovalPdfLayoutTest extends TestCase
{
    public function test_counter_part_is_rendered_below_as_part_of_controller_function(): void
    {
        foreach ([
            'admin.hpp.partials.pdf.approval.konstruksi-dalam',
            'admin.hpp.partials.pdf.approval.konstruksi-luar',
        ] as $view) {
            $top = view($view, $this->viewData('top'))->render();
            $bottom = view($view, $this->viewData('bottom'))->render();
            $bottomOver = view($view, $this->viewData('bottom', true))->render();

            $this->assertStringNotContainsString('SM of Counter Part', $top);
            $this->assertStringNotContainsString('Manager Counter Part', $top);
            $this->assertStringContainsString('colspan="3">FUNGSI PENGENDALI', $bottom);
            $this->assertStringContainsString('SM of Counter Part', $bottom);
            $this->assertStringContainsString('Manager Counter Part', $bottom);
            $this->assertStringNotContainsString('>COUNTER PART</th>', $bottom);
            $this->assertStringContainsString('colspan="4">FUNGSI PENGENDALI', $bottomOver);
            $this->assertStringContainsString('Director of Operation', $bottomOver);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(string $position, bool $isOver = false): array
    {
        $cell = fn (string $title): array => [
            'title' => $title,
            'date' => '',
            'signature' => null,
            'name' => '',
        ];

        $initial = fn (string $label): array => [
            'label' => $label,
            'value' => '',
            'date' => '',
            'signature' => null,
        ];

        return [
            'position' => $position,
            'isOver' => $isOver,
            'gmRequesterCell' => $cell('GM Peminta'),
            'smRequesterCell' => $cell('SM Peminta'),
            'plannerControlCell' => $cell('Planner Control'),
            'directorCell' => $cell('Director of Operation'),
            'gmControllerCell' => $cell('GM Pengendali'),
            'smControllerCell' => $cell('SM Pengendali'),
            'counterPartCell' => $cell('SM of Counter Part'),
            'requesterManagerInitial' => $initial('Manager Peminta'),
            'controllerManagerInitial' => $initial('Manager Pengendali'),
            'counterPartManagerInitial' => $initial('Manager Counter Part'),
            'approvalRoleClass' => fn (): string => 'approval-role',
        ];
    }
}
