<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EquipmentInspection;
use App\Models\EquipmentInspectionApproval;
use App\Models\EquipmentInspectionAttachment;
use App\Policies\EquipmentInspectionPolicy;
use App\Services\Inspector\EquipmentInspectionPdfService;
use App\Services\Inspector\EquipmentInspectionWorkflow;
use App\Support\Inspector\EquipmentInspectionIndexTabs;
use App\Support\Inspector\InspectionImageStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class EquipmentInspectionController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless((new EquipmentInspectionPolicy)->monitor($request->user()), 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate(['tab' => ['nullable', Rule::in(array_keys(EquipmentInspectionIndexTabs::options()))],
            'document_no' => ['nullable', 'string', 'max:255'], 'equipment' => ['nullable', 'string', 'max:255'],
            'inspector' => ['nullable', 'string', 'max:255'], 'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
            'status' => ['nullable', Rule::in(array_keys(EquipmentInspection::STATUS_LABELS))], 'rating' => ['nullable', Rule::in(['A', 'B', 'C'])]]);
        $tab = EquipmentInspectionIndexTabs::normalize($filters['tab'] ?? null);
        $query = EquipmentInspectionIndexTabs::apply(EquipmentInspection::query(), $tab, $request->user());
        foreach (['document_no' => 'document_no', 'equipment' => 'form_name', 'inspector' => 'inspector_name'] as $key => $column) {
            $query->when($filters[$key] ?? null, fn ($q, $value) => $q->where($column, 'like', '%'.$value.'%'));
        }
        $query->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('inspection_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('inspection_date', '<=', $date))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['rating'] ?? null, fn ($q, $rating) => $q->whereHas('answers', fn ($q) => $q->where('rating', $rating)));
        $inspections = $query->with('answers')->latest('id')->paginate(15)->withQueryString();
        $counts = EquipmentInspectionIndexTabs::counts($request->user());

        return view('admin.inspections.index', compact('inspections', 'filters', 'tab', 'counts'));
    }

    public function show(Request $request, EquipmentInspection $inspection): View
    {
        $this->authorizeAdmin($request);
        // Reading is per administrator and submitted version; it never changes workflow status.
        DB::transaction(function () use ($request, $inspection): void {
            $current = EquipmentInspection::query()->whereKey($inspection->id)->lockForUpdate()->firstOrFail();
            if ($current->signed_at) {
                DB::table('equipment_inspection_admin_reads')->upsert([['admin_user_id' => $request->user()->id,
                    'equipment_inspection_id' => $current->id, 'document_version' => $current->document_version, 'viewed_at' => now()]],
                    ['admin_user_id', 'equipment_inspection_id', 'document_version'], ['viewed_at']);
            }
        });
        $inspection->refresh()->load(['signatures', 'approvals']);
        $logs = $inspection->logs()->latest('id')->paginate(20);

        return view('admin.inspections.show', compact('inspection', 'logs'));
    }

    public function remarks(Request $request, EquipmentInspection $inspection): JsonResponse
    {
        $this->authorizeAdmin($request);
        $inspection->load('answers.attachments');

        return response()->json(['number' => $inspection->document_no ?? 'Draft', 'equipment' => $inspection->form_name,
            'date' => $inspection->inspection_date->format('d/m/Y'), 'items' => $inspection->answers->filter(fn ($a) => preg_match('/[^\s\p{Z}]/u', (string) $a->remark))
                ->map(fn ($a) => ['position' => $a->position, 'label' => $a->item_label, 'rating' => $a->rating, 'remark' => $a->remark,
                    'photos' => $a->attachments->map(fn ($file) => route('admin.inspections.attachment', [$inspection, $file]))->all()])->values()], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function attachment(Request $request, EquipmentInspection $inspection, EquipmentInspectionAttachment $attachment, InspectionImageStorage $images): Response
    {
        $this->authorizeAdmin($request);
        abort_unless((int) $attachment->equipment_inspection_id === (int) $inspection->id, 404);

        return response($images->read($attachment->path, $attachment->sha256), 200, ['Content-Type' => $attachment->mime_type, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function pdf(Request $request, EquipmentInspection $inspection, EquipmentInspectionPdfService $pdf): Response
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['version' => ['nullable', 'integer', 'min:1', 'max:'.$inspection->document_version]]);

        return $pdf->response($inspection, isset($data['version']) ? (int) $data['version'] : null);
    }

    public function recover(Request $request, EquipmentInspection $inspection, EquipmentInspectionWorkflow $workflow, EquipmentInspectionPdfService $pdf): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_unless(in_array($inspection->status, [EquipmentInspection::STATUS_READY, EquipmentInspection::STATUS_APPROVED], true), 409);
        EquipmentInspectionWorkflow::audit($inspection, $request->user(), 'admin_recovery_requested');
        if ($inspection->status === EquipmentInspection::STATUS_READY) {
            $workflow->initialize($inspection);
        } else {
            $pdf->archive($inspection);
        }

        return back()->with('success', 'Pemulihan diproses. Periksa status approval atau arsip di bawah.');
    }

    public function resend(Request $request, EquipmentInspection $inspection, EquipmentInspectionApproval $approval, EquipmentInspectionWorkflow $workflow): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_unless((int) $approval->equipment_inspection_id === (int) $inspection->id, 404);
        $workflow->sendEmail($approval->id, $request->user());

        return back()->with('success', 'Percobaan kirim ulang selesai. Lihat status email; ini bukan bukti email dibaca.');
    }
}
