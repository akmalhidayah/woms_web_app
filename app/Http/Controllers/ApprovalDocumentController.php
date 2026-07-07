<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ApprovalDocumentInbox;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApprovalDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->approver($request);
        $allDocuments = ApprovalDocumentInbox::pendingDocumentsFor($user, null);
        $availableTypeLabels = ApprovalDocumentInbox::availableTypeLabels($allDocuments);
        $selectedType = ApprovalDocumentInbox::normalizeType($request->string('type')->toString());

        if ($selectedType !== null && ! array_key_exists($selectedType, $availableTypeLabels)) {
            $selectedType = null;
        }

        return view('approval-documents.index', [
            'documents' => $selectedType === null
                ? $allDocuments
                : $allDocuments->where('type', $selectedType)->values(),
            'selectedType' => $selectedType,
            'typeLabels' => $availableTypeLabels,
            'totalPending' => $allDocuments->count(),
        ]);
    }

    public function open(Request $request, string $type, int|string $id): RedirectResponse
    {
        $user = $this->approver($request);
        $type = ApprovalDocumentInbox::normalizeType($type);

        abort_unless($type !== null, Response::HTTP_NOT_FOUND);

        $signature = ApprovalDocumentInbox::findPendingSignatureFor($type, (int) $id, $user);

        abort_unless($signature, Response::HTTP_FORBIDDEN, 'Dokumen approval tidak tersedia.');

        $token = ApprovalDocumentInbox::tokenFor($type, $signature);

        abort_unless(filled($token), Response::HTTP_FORBIDDEN, 'Dokumen approval tidak tersedia.');

        return redirect()->route(ApprovalDocumentInbox::publicRouteName($type), $token);
    }

    private function approver(Request $request): User
    {
        $user = $request->user();

        abort_unless(
            $user?->role === User::ROLE_APPROVER,
            Response::HTTP_FORBIDDEN,
            'Menu Dokumen Approval hanya tersedia untuk user approval.'
        );

        return $user;
    }
}
