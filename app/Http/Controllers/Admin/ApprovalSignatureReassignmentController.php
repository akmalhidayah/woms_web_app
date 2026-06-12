<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HppSignature;
use App\Models\InitialWorkSignature;
use App\Models\LhppBastSignature;
use App\Models\QualityControlSignature;
use App\Models\User;
use App\Services\Approvals\ApprovalSignatureReassignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApprovalSignatureReassignmentController extends Controller
{
    public function __construct(
        private readonly ApprovalSignatureReassignmentService $reassignmentService,
    ) {}

    public function initialWork(Request $request, InitialWorkSignature $signature): RedirectResponse
    {
        $this->reassign($request, $signature);

        return back()->with('status', 'Approver Initial Work berhasil dialihkan.');
    }

    public function hpp(Request $request, HppSignature $signature): RedirectResponse
    {
        $this->reassign($request, $signature);

        return back()->with('status', 'Approver HPP berhasil dialihkan.');
    }

    public function qualityControl(Request $request, QualityControlSignature $signature): RedirectResponse
    {
        $this->reassign($request, $signature);

        return back()->with('status', 'Approver Quality Control berhasil dialihkan.');
    }

    public function bast(Request $request, LhppBastSignature $signature): RedirectResponse
    {
        $this->reassign($request, $signature);

        return back()->with('status', 'Approver BAST berhasil dialihkan.');
    }

    private function reassign(
        Request $request,
        InitialWorkSignature|HppSignature|LhppBastSignature|QualityControlSignature $signature,
    ): void {
        $validated = $request->validate([
            'signer_user_id' => ['required', Rule::exists('users', 'id')],
            'delegation_reason' => ['required', 'string', 'max:1000'],
            'send_email' => ['nullable', 'boolean'],
        ]);

        $newSigner = User::query()->findOrFail($validated['signer_user_id']);

        $this->reassignmentService->reassign(
            $signature,
            $newSigner,
            $request->user(),
            trim((string) $validated['delegation_reason']),
            $request->boolean('send_email', true),
        );
    }
}
