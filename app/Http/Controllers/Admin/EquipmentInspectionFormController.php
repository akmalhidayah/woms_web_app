<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveEquipmentInspectionFormRequest;
use App\Models\EquipmentInspectionForm;
use App\Policies\EquipmentInspectionPolicy;
use App\Services\Inspector\EquipmentInspectionFormService;
use App\Support\Inspector\EquipmentInspectionFormImageStorage;
use App\Support\Inspector\EquipmentInspectionFormRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EquipmentInspectionFormController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless((new EquipmentInspectionPolicy)->monitor($request->user()), 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:150']]);
        $forms = EquipmentInspectionForm::query()->withCount('items')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('sort_order')->orderBy('id')->paginate(20)->withQueryString();

        return view('admin.inspection-forms.index', compact('forms', 'filters'));
    }

    public function create(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('admin.inspection-forms.form', ['form' => null]);
    }

    public function store(SaveEquipmentInspectionFormRequest $request, EquipmentInspectionFormService $service): RedirectResponse
    {
        $form = $service->create($request->user(), $request->validated(), $request->file('image'));

        return redirect()->route('admin.inspection-forms.edit', $form)
            ->with('success', $form->status === EquipmentInspectionForm::STATUS_ACTIVE ? 'Form inspeksi dibuat dan diaktifkan.' : 'Draft form inspeksi disimpan.');
    }

    public function edit(Request $request, EquipmentInspectionForm $equipmentInspectionForm): View
    {
        $this->authorizeAdmin($request);
        $equipmentInspectionForm->load(['items', 'logs.actor']);

        return view('admin.inspection-forms.form', ['form' => $equipmentInspectionForm]);
    }

    public function preview(Request $request, EquipmentInspectionForm $equipmentInspectionForm, EquipmentInspectionFormRepository $forms): View
    {
        $this->authorizeAdmin($request);
        $equipmentInspectionForm->load('items');

        return view('admin.inspection-forms.preview', [
            'formModel' => $equipmentInspectionForm,
            'form' => $forms->template($equipmentInspectionForm),
        ]);
    }

    public function update(SaveEquipmentInspectionFormRequest $request, EquipmentInspectionForm $equipmentInspectionForm, EquipmentInspectionFormService $service): RedirectResponse
    {
        $form = $service->update($request->user(), $equipmentInspectionForm, $request->validated(), $request->file('image'));

        return redirect()->route('admin.inspection-forms.edit', $form)->with('success', 'Master form inspeksi berhasil diperbarui.');
    }

    public function activate(Request $request, EquipmentInspectionForm $equipmentInspectionForm, EquipmentInspectionFormService $service): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $service->setActive($request->user(), $equipmentInspectionForm, true);

        return back()->with('success', 'Form inspeksi diaktifkan dan tersedia untuk inspeksi baru.');
    }

    public function deactivate(Request $request, EquipmentInspectionForm $equipmentInspectionForm, EquipmentInspectionFormService $service): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $service->setActive($request->user(), $equipmentInspectionForm, false);

        return back()->with('success', 'Form inspeksi dinonaktifkan. Riwayat inspeksi tetap tersimpan.');
    }

    public function image(Request $request, EquipmentInspectionForm $equipmentInspectionForm, EquipmentInspectionFormImageStorage $images): Response
    {
        $this->authorizeAdmin($request);
        abort_if(blank($equipmentInspectionForm->image_path) || blank($equipmentInspectionForm->image_sha256), 404);

        return response($images->read($equipmentInspectionForm->image_path, $equipmentInspectionForm->image_sha256), 200, [
            'Content-Type' => $equipmentInspectionForm->image_mime_type ?: 'image/jpeg',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
