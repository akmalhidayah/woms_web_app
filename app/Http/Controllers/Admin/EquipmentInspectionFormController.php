<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveEquipmentInspectionFormQrRequest;
use App\Http\Requests\Admin\SaveEquipmentInspectionFormRequest;
use App\Models\EquipmentInspection;
use App\Models\EquipmentInspectionForm;
use App\Policies\EquipmentInspectionPolicy;
use App\Services\Inspector\EquipmentInspectionFormService;
use App\Services\Inspector\EquipmentInspectionUnitService;
use App\Services\Inspector\EquipmentQrCodeService;
use App\Support\Inspector\EquipmentInspectionFormImageStorage;
use App\Support\Inspector\EquipmentInspectionFormRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class EquipmentInspectionFormController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless((new EquipmentInspectionPolicy)->monitor($request->user()), 403);
    }

    public function index(
        Request $request,
        EquipmentInspectionUnitService $units,
        EquipmentQrCodeService $qr,
    ): View
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:150']]);
        $forms = EquipmentInspectionForm::query()
            ->withCount('items')
            ->with(['units' => fn ($query) => $query->withCount([
                'links as approved_history_count' => fn ($links) => $links->whereHas(
                    'inspection',
                    fn ($inspections) => $inspections->where('status', EquipmentInspection::STATUS_APPROVED),
                ),
            ])])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('sort_order')->orderBy('id')->paginate(20)->withQueryString();

        $formSlugs = $forms->getCollection()->pluck('slug');
        $unlinkedByForm = $formSlugs->isEmpty()
            ? collect()
            : EquipmentInspection::query()
                ->whereIn('form_slug', $formSlugs)
                ->where('status', EquipmentInspection::STATUS_APPROVED)
                ->whereDoesntHave('unitLink')
                ->with('signatures')
                ->orderByDesc('inspection_date')
                ->orderByDesc('id')
                ->get()
                ->groupBy('form_slug');
        $qrStates = [];
        foreach ($forms->getCollection() as $form) {
            if ($form->units->isEmpty()) {
                $qrStates[$form->id] = ['status' => 'missing', 'unit' => null];

                continue;
            }
            if ($form->units->count() > 1) {
                $qrStates[$form->id] = ['status' => 'conflict', 'units' => $form->units];

                continue;
            }

            $unit = $form->units->first();
            $unit->setRelation('form', $form);
            try {
                $publicUrl = $qr->publicUrl($unit);
                $qrError = null;
            } catch (InvalidArgumentException) {
                $publicUrl = null;
                $qrError = 'URL publik QR belum dapat dibuat. Periksa konfigurasi base URL aplikasi.';
            }
            $legacyCandidates = collect($unlinkedByForm->get($form->slug, []))
                ->filter(fn (EquipmentInspection $inspection): bool => $units->legacyLinkValidationError($unit, $inspection) === null)
                ->values();
            $qrStates[$form->id] = [
                'status' => 'ready',
                'unit' => $unit,
                'public_url' => $publicUrl,
                'qr_error' => $qrError,
                'legacy_candidates' => $legacyCandidates->take(10),
                'legacy_candidate_count' => $legacyCandidates->count(),
            ];
        }

        return view('admin.inspection-forms.index', compact('forms', 'filters', 'qrStates'));
    }

    public function saveQr(
        SaveEquipmentInspectionFormQrRequest $request,
        EquipmentInspectionForm $equipmentInspectionForm,
        EquipmentInspectionUnitService $units,
    ): RedirectResponse
    {
        $unit = $units->savePrimaryForForm(
            $request->user(),
            $equipmentInspectionForm,
            $request->validated('asset_code'),
        );

        return back()->with('success', $unit->wasRecentlyCreated
            ? 'Kode alat disimpan dan QR permanen berhasil dibuat. Aktifkan akses publik setelah data diverifikasi.'
            : 'Kode alat berhasil diperbarui tanpa mengubah QR permanen.');
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
