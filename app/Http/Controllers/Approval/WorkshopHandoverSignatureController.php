<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Admin\WorkshopHandoverController as AdminWorkshopHandoverController;
use App\Http\Controllers\Controller;
use App\Models\WorkshopHandover;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class WorkshopHandoverSignatureController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $handover = $this->resolveByToken($token);
        $this->authorizeRecipient($request, $handover);
        $handover->loadMissing(['order.workPackages.assignments', 'admin', 'recipient']);

        $photoUrls = collect($handover->photo_paths ?? [])
            ->keys()
            ->mapWithKeys(fn (int $index): array => [
                $index => route('approval.workshop-handover.photo', [$token, $index]),
            ])
            ->all();

        return view('approval.workshop-handover-signature', [
            'handover' => $handover,
            'token' => $token,
            'photoUrls' => $photoUrls,
            'pdfUrl' => route('approval.workshop-handover.pdf', $token),
            'isExpired' => $handover->isWaitingUserSignature() && $handover->tokenExpired(),
            'canSign' => $handover->isWaitingUserSignature() && ! $handover->tokenExpired(),
        ]);
    }

    public function sign(Request $request, string $token): RedirectResponse
    {
        $handover = $this->resolveByToken($token);
        $this->authorizeRecipient($request, $handover);

        if ($handover->isCompleted()) {
            return redirect()->route('approval.workshop-handover.show', $token)
                ->with('status', 'Serah Terima ini sudah diselesaikan.');
        }

        abort_unless($handover->isWaitingUserSignature(), Response::HTTP_CONFLICT, 'Serah Terima ini tidak sedang menunggu tanda tangan.');
        abort_unless(! $handover->tokenExpired(), Response::HTTP_FORBIDDEN, 'Token Serah Terima sudah kedaluwarsa.');

        $validated = $request->validate([
            'signature_data' => ['required', 'string'],
        ]);

        $signaturePath = $this->storeSignatureData($validated['signature_data'], $handover->id);

        try {
            $processed = DB::transaction(function () use ($request, $handover, $signaturePath): bool {
                $locked = WorkshopHandover::query()->lockForUpdate()->findOrFail($handover->id);
                $this->authorizeRecipient($request, $locked);

                if ($locked->isCompleted()) {
                    return false;
                }

                abort_unless($locked->isWaitingUserSignature(), Response::HTTP_CONFLICT, 'Serah Terima ini tidak sedang menunggu tanda tangan.');
                abort_unless(! $locked->tokenExpired(), Response::HTTP_FORBIDDEN, 'Token Serah Terima sudah kedaluwarsa.');

                $locked->update([
                    'status' => WorkshopHandover::STATUS_COMPLETED,
                    'user_signature_path' => $signaturePath,
                    'user_signed_at' => now(),
                    'user_signed_ip' => $request->ip(),
                    'user_signed_user_agent' => substr((string) $request->userAgent(), 0, 2000),
                ]);

                return true;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($signaturePath);
            throw $exception;
        }

        if (! $processed) {
            Storage::disk('local')->delete($signaturePath);

            return redirect()->route('approval.workshop-handover.show', $token)
                ->with('status', 'Serah Terima ini sudah diselesaikan.');
        }

        return redirect()->route('approval.workshop-handover.show', $token)
            ->with('status', 'Tanda tangan Manager User berhasil disimpan. Serah Terima selesai.');
    }

    public function photo(Request $request, string $token, int $index): Response
    {
        $handover = $this->resolveByToken($token);
        $this->authorizeRecipient($request, $handover);

        $path = $handover->photo_paths[$index] ?? null;
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), Response::HTTP_NOT_FOUND);

        return response()->file(Storage::disk('local')->path($path), [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function pdf(Request $request, string $token): Response
    {
        $handover = $this->resolveByToken($token);
        $this->authorizeRecipient($request, $handover);

        return app(AdminWorkshopHandoverController::class)->renderPdf($handover);
    }

    private function resolveByToken(string $token): WorkshopHandover
    {
        return WorkshopHandover::query()
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();
    }

    private function authorizeRecipient(Request $request, WorkshopHandover $handover): void
    {
        $authorized = $request->user()?->id !== null
            && (int) $request->user()->id === (int) $handover->recipient_user_id;

        if (! $authorized) {
            Log::warning('Workshop handover approval signer authorization denied.', [
                'workshop_handover_id' => $handover->id,
                'authenticated_user_id' => $request->user()?->id,
                'expected_recipient_user_id' => $handover->recipient_user_id,
                'status_code' => Response::HTTP_FORBIDDEN,
            ]);
        }

        abort_unless($authorized, Response::HTTP_FORBIDDEN, 'Link Serah Terima ini hanya untuk Manager User yang ditetapkan.');
    }

    private function storeSignatureData(string $data, int $handoverId): string
    {
        $prefix = 'data:image/png;base64,';
        abort_unless(str_starts_with($data, $prefix), Response::HTTP_UNPROCESSABLE_ENTITY, 'Tanda tangan belum diisi.');

        $binary = base64_decode(substr($data, strlen($prefix)), true);
        abort_unless(is_string($binary) && strlen($binary) >= 100, Response::HTTP_UNPROCESSABLE_ENTITY, 'Tanda tangan tidak valid.');

        $path = 'workshop-handovers/'.$handoverId.'/signatures/user-'.Str::uuid().'.png';
        Storage::disk('local')->put($path, $binary);

        return $path;
    }
}
