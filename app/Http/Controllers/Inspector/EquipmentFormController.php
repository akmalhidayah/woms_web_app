<?php

namespace App\Http\Controllers\Inspector;

use App\Http\Controllers\Controller;
use App\Models\EquipmentInspectionForm;
use App\Support\Inspector\EquipmentFormCatalog;
use App\Support\Inspector\EquipmentInspectionFormImageStorage;
use App\Support\Inspector\EquipmentInspectionViewData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EquipmentFormController extends Controller
{
    public function index(): View
    {
        $forms = EquipmentFormCatalog::all();
        foreach ($forms as &$form) {
            $form['image_url'] = $form['has_image']
                ? route('inspector.equipment-forms.image', $form['id']) : null;
        }
        unset($form);

        return view('inspector.equipment-forms.index', [
            'forms' => $forms,
        ]);
    }

    public function show(Request $request, string $equipmentForm): View
    {
        $form = EquipmentFormCatalog::find($equipmentForm);

        abort_if($form === null, 404);

        return view('inspector.equipment-forms.show', EquipmentInspectionViewData::make($form, null, $request->user()));
    }

    public function image(string $equipmentForm, EquipmentInspectionFormImageStorage $images): Response
    {
        $form = EquipmentInspectionForm::query()->active()->where('slug', $equipmentForm)->firstOrFail();
        abort_if(blank($form->image_path) || blank($form->image_sha256), 404);

        return response($images->read($form->image_path, $form->image_sha256), 200, [
            'Content-Type' => $form->image_mime_type ?: 'image/jpeg',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
