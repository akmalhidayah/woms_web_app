<?php

namespace App\Http\Controllers\Inspector;

use App\Http\Controllers\Controller;
use App\Support\Inspector\EquipmentFormCatalog;
use Illuminate\Contracts\View\View;

class EquipmentFormController extends Controller
{
    public function index(): View
    {
        return view('inspector.equipment-forms.index', [
            'forms' => EquipmentFormCatalog::all(),
        ]);
    }

    public function show(string $equipmentForm): View
    {
        $form = EquipmentFormCatalog::find($equipmentForm);

        abort_if($form === null, 404);

        $emptyValues = collect($form['groups'])
            ->flatMap(fn (array $group): array => $group['items'])
            ->mapWithKeys(fn (array $item): array => [$item['id'] => ''])
            ->all();

        return view('inspector.equipment-forms.show', [
            'form' => $form,
            'emptyValues' => $emptyValues,
            'inspectionDate' => now()->toDateString(),
        ]);
    }
}
