<?php

namespace App\Http\Controllers\Inspector;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inspector\SaveEquipmentInspectionRequest;
use App\Http\Requests\Inspector\SaveAndSignEquipmentInspectionRequest;
use App\Http\Requests\Inspector\SignEquipmentInspectionRequest;
use App\Models\EquipmentInspection;
use App\Models\EquipmentInspectionAttachment;
use App\Models\EquipmentInspectionSignature;
use App\Policies\EquipmentInspectionPolicy;
use App\Services\Inspector\EquipmentInspectionPdfService;
use App\Services\Inspector\EquipmentInspectionService;
use App\Services\Inspector\EquipmentInspectionWorkflow;
use App\Support\Inspector\EquipmentInspectionViewData;
use App\Support\Inspector\InspectionImageStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EquipmentInspectionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'equipment' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
            'status' => ['nullable', Rule::in(array_keys(EquipmentInspection::STATUS_LABELS))],
        ]);
        $inspections = EquipmentInspection::query()->where('inspector_user_id', $request->user()->id)
            ->when($filters['equipment'] ?? null, fn ($query, $name) => $query->where('form_name', 'like', '%'.$name.'%'))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('inspection_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('inspection_date', '<=', $date))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->withCount(['answers', 'answers as filled_answers_count' => fn ($query) => $query->whereIn('rating', ['A', 'B', 'C'])])
            ->orderByDesc('inspection_date')->orderByDesc('id')->paginate(12)->withQueryString();

        return view('inspector.inspections.index', compact('inspections', 'filters'));
    }

    public function show(Request $request, EquipmentInspection $inspection): View
    {
        $this->authorizeView($request, $inspection);

        return view('inspector.equipment-forms.show', EquipmentInspectionViewData::make($inspection->template_snapshot, $inspection));
    }

    public function store(SaveEquipmentInspectionRequest $request, string $equipmentForm, EquipmentInspectionService $service): RedirectResponse
    {
        $inspection = $service->save($request->user(), null, $equipmentForm, $request->validated(), $request->file('photos', []));

        return redirect()->route('inspector.inspections.show', $inspection)->with('success', 'Draft pemeriksaan berhasil disimpan.');
    }

    public function update(SaveEquipmentInspectionRequest $request, EquipmentInspection $inspection, EquipmentInspectionService $service): RedirectResponse
    {
        $inspection = $service->save($request->user(), $inspection, null, $request->validated(), $request->file('photos', []));

        return redirect()->route('inspector.inspections.show', $inspection)->with('success', 'Perubahan draft berhasil disimpan.');
    }

    public function storeAndSign(SaveAndSignEquipmentInspectionRequest $request, string $equipmentForm, EquipmentInspectionService $service, EquipmentInspectionWorkflow $workflow): RedirectResponse
    {
        $inspection = $service->save($request->user(), null, $equipmentForm, $request->validated(), $request->file('photos', []));

        return $this->completeDirectSignature($request, $inspection, $service, $workflow);
    }

    public function updateAndSign(SaveAndSignEquipmentInspectionRequest $request, EquipmentInspection $inspection, EquipmentInspectionService $service, EquipmentInspectionWorkflow $workflow): RedirectResponse
    {
        $inspection = $service->save($request->user(), $inspection, null, $request->validated(), $request->file('photos', []));

        return $this->completeDirectSignature($request, $inspection, $service, $workflow);
    }

    public function sign(SignEquipmentInspectionRequest $request, EquipmentInspection $inspection, EquipmentInspectionService $service): RedirectResponse
    {
        try {
            $inspection = $service->sign($request->user(), $inspection, $request->validated());
        } catch (ValidationException $exception) {
            return redirect()->route('inspector.inspections.show', $inspection)->withErrors($exception->errors());
        }

        app(EquipmentInspectionWorkflow::class)->initialize($inspection);

        return redirect()->route('inspector.inspections.show', $inspection)
            ->with('success', 'Pemeriksaan ditandatangani. Nomor '.$inspection->document_no.' diterbitkan dan laporan terkunci.');
    }

    public function revise(Request $request, EquipmentInspection $inspection, EquipmentInspectionWorkflow $workflow): RedirectResponse
    {
        $this->authorizeView($request, $inspection);
        $data = $request->validate(['document_version' => ['required', 'integer', 'min:1']]);
        $workflow->beginRevision($request->user(), $inspection, (int) $data['document_version']);

        return redirect()->route('inspector.inspections.show', $inspection)->with('success', 'Versi revisi baru dibuka. Nomor tetap; periksa isian lalu tanda tangani ulang.');
    }

    public function pdf(Request $request, EquipmentInspection $inspection, EquipmentInspectionPdfService $pdf): Response
    {
        $this->authorizeView($request, $inspection);

        $data = $request->validate(['version' => ['nullable', 'integer', 'min:1', 'max:'.$inspection->document_version]]);

        return $pdf->response($inspection, isset($data['version']) ? (int) $data['version'] : null);
    }

    public function attachment(Request $request, EquipmentInspection $inspection, EquipmentInspectionAttachment $attachment, InspectionImageStorage $images): Response
    {
        $this->authorizeView($request, $inspection);
        abort_unless((int) $attachment->equipment_inspection_id === (int) $inspection->id, 404);

        return response($images->read($attachment->path, $attachment->sha256), 200, [
            'Content-Type' => $attachment->mime_type, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
        ]);
    }

    public function signature(Request $request, EquipmentInspection $inspection, InspectionImageStorage $images): Response
    {
        $this->authorizeView($request, $inspection);
        $signature = $inspection->signatures()->where('document_version', $inspection->document_version)
            ->where('role_key', EquipmentInspectionSignature::ROLE_INSPECTOR)->firstOrFail();

        return response($images->read($signature->signature_path, $signature->signature_sha256), 200, [
            'Content-Type' => 'image/png', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
        ]);
    }

    private function authorizeView(Request $request, EquipmentInspection $inspection): void
    {
        abort_unless((new EquipmentInspectionPolicy)->view($request->user(), $inspection), 403);
    }

    private function completeDirectSignature(SaveAndSignEquipmentInspectionRequest $request, EquipmentInspection $inspection, EquipmentInspectionService $service, EquipmentInspectionWorkflow $workflow): RedirectResponse
    {
        try {
            $inspection = $service->sign($request->user(), $inspection, [
                'lock_version' => $inspection->lock_version,
                'document_version' => $inspection->document_version,
                'signature_data' => $request->validated('signature_data'),
            ]);
        } catch (ValidationException $exception) {
            return redirect()->route('inspector.inspections.show', $inspection)
                ->withErrors($exception->errors())
                ->with('success', 'Isian berhasil disimpan sebagai draft, tetapi tanda tangan belum dapat diproses.');
        }

        $workflow->initialize($inspection);

        return redirect()->route('inspector.inspections.show', $inspection)
            ->with('success', 'Pemeriksaan berhasil disimpan dan ditandatangani. Nomor '.$inspection->document_no.' telah diterbitkan.');
    }
}
