<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveEquipmentInspectionUnitRequest;
use App\Models\EquipmentInspection;
use App\Models\EquipmentInspectionForm;
use App\Models\EquipmentInspectionUnit;
use App\Policies\EquipmentInspectionPolicy;
use App\Services\Inspector\EquipmentInspectionUnitService;
use App\Services\Inspector\EquipmentQrCodeService;
use App\Support\Inspector\EquipmentInspectionFormImageStorage;
use App\Support\Inspector\EquipmentInspectionUnitImageStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class EquipmentInspectionUnitController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'form_id' => ['nullable', 'integer', Rule::exists('equipment_inspection_forms', 'id')],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(EquipmentInspectionUnit::STATUSES)],
        ]);
        $lastApproved = DB::table('equipment_inspections')
            ->select('equipment_inspections.inspection_date')
            ->join('equipment_inspection_unit_links', 'equipment_inspection_unit_links.equipment_inspection_id', '=', 'equipment_inspections.id')
            ->whereColumn('equipment_inspection_unit_links.equipment_inspection_unit_id', 'equipment_inspection_units.id')
            ->where('equipment_inspections.status', EquipmentInspection::STATUS_APPROVED)
            ->whereNull('equipment_inspections.deleted_at')
            ->orderByDesc('equipment_inspections.inspection_date')
            ->orderByDesc('equipment_inspections.id')->limit(1);
        $units = EquipmentInspectionUnit::query()->with('form')
            ->withCount(['links as approved_history_count' => fn ($query) => $query->whereHas('inspection', fn ($inspection) => $inspection->where('status', EquipmentInspection::STATUS_APPROVED))])
            ->addSelect(['last_approved_at' => $lastApproved])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($nested) => $nested
                ->where('asset_code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%')))
            ->when($filters['form_id'] ?? null, fn ($query, $formId) => $query->where('equipment_inspection_form_id', $formId))
            ->when($filters['location'] ?? null, fn ($query, $location) => $query->where('location', 'like', '%'.$location.'%'))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('lifecycle_status', $status))
            ->orderBy('sort_order')->orderBy('asset_code')->paginate(20)->withQueryString();

        return view('admin.inspection-units.index', [
            'units' => $units,
            'forms' => EquipmentInspectionForm::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'locations' => EquipmentInspectionUnit::query()->whereNotNull('location')->distinct()->orderBy('location')->pluck('location'),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('admin.inspection-units.form', [
            'unit' => null,
            'forms' => EquipmentInspectionForm::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(SaveEquipmentInspectionUnitRequest $request, EquipmentInspectionUnitService $service): RedirectResponse
    {
        $unit = $service->create($request->user(), $request->validated(), $request->file('image'));

        return redirect()->route('admin.inspection-units.show', $unit)->with('success', 'Master Unit Peralatan berhasil dibuat. QR permanen telah tersedia.');
    }

    public function show(Request $request, EquipmentInspectionUnit $equipmentInspectionUnit, EquipmentQrCodeService $qr): View
    {
        $this->authorizeAdmin($request);
        $equipmentInspectionUnit->load('form');
        $historyLinks = $equipmentInspectionUnit->links()->whereHas('inspection')->with(['linker', 'inspection'])
            ->orderByDesc('linked_at')->orderByDesc('id')->paginate(20, ['*'], 'history_page')->withQueryString();
        $unitLogs = $equipmentInspectionUnit->logs()->with('actor')->paginate(20, ['*'], 'audit_page')->withQueryString();

        return view('admin.inspection-units.show', [
            'unit' => $equipmentInspectionUnit,
            'historyLinks' => $historyLinks,
            'unitLogs' => $unitLogs,
            'publicUrl' => $qr->publicUrl($equipmentInspectionUnit),
        ]);
    }

    public function edit(Request $request, EquipmentInspectionUnit $equipmentInspectionUnit): View
    {
        $this->authorizeAdmin($request);

        return view('admin.inspection-units.form', [
            'unit' => $equipmentInspectionUnit,
            'forms' => EquipmentInspectionForm::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function update(SaveEquipmentInspectionUnitRequest $request, EquipmentInspectionUnit $equipmentInspectionUnit, EquipmentInspectionUnitService $service): RedirectResponse
    {
        $unit = $service->update($request->user(), $equipmentInspectionUnit, $request->validated(), $request->file('image'));

        return redirect()->route('admin.inspection-units.show', $unit)->with('success', 'Master Unit Peralatan berhasil diperbarui.');
    }

    public function togglePublic(Request $request, EquipmentInspectionUnit $equipmentInspectionUnit, EquipmentInspectionUnitService $service): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        $service->setPublicEnabled($request->user(), $equipmentInspectionUnit, (bool) $data['enabled']);

        return back()->with('success', (bool) $data['enabled'] ? 'Akses QR publik diaktifkan.' : 'Akses QR publik dinonaktifkan.');
    }

    public function retire(Request $request, EquipmentInspectionUnit $equipmentInspectionUnit, EquipmentInspectionUnitService $service): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $service->retire($request->user(), $equipmentInspectionUnit);

        return back()->with('success', 'Unit dipensiunkan dan akses QR publik dinonaktifkan. Riwayat tetap tersimpan.');
    }

    public function rotate(Request $request, EquipmentInspectionUnit $equipmentInspectionUnit, EquipmentInspectionUnitService $service): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $service->rotatePublicCode($request->user(), $equipmentInspectionUnit);

        return redirect()->route('admin.inspection-units.show', $equipmentInspectionUnit->fresh())
            ->with('success', 'Public code berhasil dirotasi. URL dan label lama tidak berlaku; cetak lalu ganti label fisik.');
    }

    public function linkLegacy(Request $request, EquipmentInspectionUnit $equipmentInspectionUnit, EquipmentInspectionUnitService $service): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'inspection_id' => ['required', 'integer', Rule::exists('equipment_inspections', 'id')],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        $inspection = EquipmentInspection::withTrashed()->findOrFail((int) $data['inspection_id']);
        $service->linkLegacy($request->user(), $equipmentInspectionUnit, $inspection, $data['reason'] ?? null);

        return back()->with('success', 'Riwayat inspeksi lama berhasil dihubungkan tanpa mengubah isi dokumen atau tanda tangan.');
    }

    public function image(
        Request $request,
        EquipmentInspectionUnit $equipmentInspectionUnit,
        EquipmentInspectionUnitImageStorage $unitImages,
        EquipmentInspectionFormImageStorage $formImages,
    ): Response {
        $this->authorizeAdmin($request);
        $equipmentInspectionUnit->loadMissing('form');
        if ($equipmentInspectionUnit->form?->image_path && $equipmentInspectionUnit->form?->image_sha256) {
            $binary = $formImages->read($equipmentInspectionUnit->form->image_path, $equipmentInspectionUnit->form->image_sha256);
            $mime = $equipmentInspectionUnit->form->image_mime_type ?: 'image/jpeg';
        } elseif ($equipmentInspectionUnit->image_path && $equipmentInspectionUnit->image_sha256) {
            $binary = $unitImages->read($equipmentInspectionUnit->image_path, $equipmentInspectionUnit->image_sha256);
            $mime = $equipmentInspectionUnit->image_mime_type ?: 'image/jpeg';
        } else {
            abort(404);
        }

        return response($binary, 200, ['Content-Type' => $mime, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function qr(Request $request, EquipmentInspectionUnit $equipmentInspectionUnit, EquipmentQrCodeService $qr): Response
    {
        $this->authorizeAdmin($request);
        $download = $request->boolean('download');
        $headers = ['Content-Type' => 'image/svg+xml', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];
        if ($download) {
            $headers['Content-Disposition'] = 'attachment; filename="qr-'.$this->safeFilename($equipmentInspectionUnit->asset_code).'.svg"';
        }

        return response($qr->svg($equipmentInspectionUnit), 200, $headers);
    }

    public function label(Request $request, EquipmentInspectionUnit $equipmentInspectionUnit, EquipmentQrCodeService $qr): View
    {
        $this->authorizeAdmin($request);
        $equipmentInspectionUnit->loadMissing('form');

        return view('admin.inspection-units.label', ['unit' => $equipmentInspectionUnit, 'publicUrl' => $qr->publicUrl($equipmentInspectionUnit)]);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless((new EquipmentInspectionPolicy)->monitor($request->user()), 403);
    }

    private function safeFilename(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9._-]+/', '-', $value) ?: 'unit';
    }
}
