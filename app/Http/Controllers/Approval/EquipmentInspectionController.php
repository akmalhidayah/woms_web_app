<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inspector\DecideEquipmentInspectionRequest;
use App\Services\Inspector\EquipmentInspectionPdfService;
use App\Services\Inspector\EquipmentInspectionWorkflow;
use App\Support\RecentApprovalSignatureResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EquipmentInspectionController extends Controller
{
    public function show(Request $request, string $token, EquipmentInspectionWorkflow $workflow, RecentApprovalSignatureResolver $recent): View
    {
        $approval = $workflow->resolve($request->user(), $token);

        return view('approval.equipment-inspection', ['approval' => $approval, 'inspection' => $approval->inspection, 'token' => $token,
            'recentSignatureDataUrl' => $recent->latestFullSignatureForUser($request->user())]);
    }

    public function pdf(Request $request, string $token, EquipmentInspectionWorkflow $workflow, EquipmentInspectionPdfService $pdf): Response
    {
        $approval = $workflow->resolve($request->user(), $token);

        return $pdf->response($approval->inspection);
    }

    public function decide(DecideEquipmentInspectionRequest $request, string $token, EquipmentInspectionWorkflow $workflow): RedirectResponse
    {
        $approval = $workflow->resolve($request->user(), $token, true);
        $data = $request->validated();
        if ($request->hasFile('signature_file')) {
            $data['signature_data'] = 'data:image/png;base64,'.base64_encode(file_get_contents($request->file('signature_file')->getRealPath()));
        }
        try {
            $workflow->decide($request->user(), $approval, $token, $data);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput($request->only('decision_note'));
        }

        return redirect()->route('approval-documents.index')->with('inspection_success', 'Keputusan Inspeksi Peralatan tersimpan.');
    }
}
