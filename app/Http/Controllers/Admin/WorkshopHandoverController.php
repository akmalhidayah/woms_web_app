<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\WorkshopHandover;
use App\Services\Approvals\ApprovalNotificationService;
use App\Services\BengkelTasks\WorkshopHandoverQueue;
use App\Support\WorkshopHandoverRecipientResolver;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class WorkshopHandoverController extends Controller
{
    private const TOKEN_TTL_DAYS = 7;

    public function __construct(
        private readonly ApprovalNotificationService $notificationService,
    ) {}

    public function __invoke(
        Request $request,
        WorkshopHandoverQueue $queue,
        WorkshopHandoverRecipientResolver $recipientResolver,
    ): View {
        $search = trim((string) $request->string('search'));
        $path = trim((string) $request->string('path'));
        $requestedTab = (string) $request->input('tab', 'waiting');
        $tab = in_array($requestedTab, ['waiting', 'history'], true)
            ? $requestedTab
            : 'waiting';

        $waitingQuery = $queue->query()
            ->with('workPackages.assignments')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('nomor_order', 'like', "%{$search}%")
                    ->orWhere('nama_pekerjaan', 'like', "%{$search}%")
                    ->orWhere('unit_kerja', 'like', "%{$search}%")
                    ->orWhere('seksi', 'like', "%{$search}%");
            }))
            ->when($path !== '', fn ($query) => $query->where(function ($query) use ($path): void {
                if ($path === WorkshopHandover::PATH_CRITICAL) {
                    $query->whereHas('latestQualityControlReport');
                } else {
                    $query->whereDoesntHave('qualityControlReports');
                }
            }))
            ->latest('id');

        $waiting = $waitingQuery->paginate(10, ['*'], 'waiting_page')->withQueryString();
        $historyQuery = Order::query()
            ->select('orders.*')
            ->leftJoin('order_workshops as history_workshops', 'history_workshops.order_id', '=', 'orders.id')
            ->leftJoin('workshop_handovers as history_handovers', 'history_handovers.order_id', '=', 'orders.id')
            ->with([
                'orderWorkshop',
                'latestQualityControlReport',
                'workPackages.assignments',
                'workshopHandover.admin',
                'workshopHandover.recipient',
            ])
            ->where(function ($query): void {
                $query->where('history_handovers.status', WorkshopHandover::STATUS_COMPLETED)
                    ->orWhere(function ($legacy): void {
                        $legacy->whereNull('history_handovers.id')
                            ->whereNotNull('history_workshops.legacy_completed_at');
                    });
            });
        $historyCount = (clone $historyQuery)->count('orders.id');
        $history = $historyQuery
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('orders.nomor_order', 'like', "%{$search}%")
                    ->orWhere('orders.nama_pekerjaan', 'like', "%{$search}%")
                    ->orWhere('orders.unit_kerja', 'like', "%{$search}%")
                    ->orWhere('orders.seksi', 'like', "%{$search}%")
                    ->orWhere('history_handovers.order_no_snapshot', 'like', "%{$search}%")
                    ->orWhere('history_handovers.job_name_snapshot', 'like', "%{$search}%")
                    ->orWhere('history_handovers.unit_snapshot', 'like', "%{$search}%")
                    ->orWhere('history_handovers.section_snapshot', 'like', "%{$search}%");
            }))
            ->when($path !== '', fn ($query) => $query->where(function ($query) use ($path): void {
                $query->where('history_handovers.path', $path)
                    ->orWhere(function ($legacy) use ($path): void {
                        $legacy->whereNull('history_handovers.id')
                            ->whereNotNull('history_workshops.legacy_completed_at');

                        if ($path === WorkshopHandover::PATH_CRITICAL) {
                            $legacy->whereHas('qualityControlReports');
                        } else {
                            $legacy->whereDoesntHave('qualityControlReports');
                        }
                    });
            }))
            ->orderByRaw('COALESCE(history_handovers.handed_over_at, history_workshops.legacy_completed_at) DESC')
            ->orderByDesc('orders.id')
            ->paginate(10, ['*'], 'history_page')
            ->withQueryString();

        $recipientPreviews = [];
        foreach ($waiting as $order) {
            $recipientPreviews[$order->id] = $recipientResolver->resolve($order);
        }

        return view('admin.workshop-handover.index', [
            'waiting' => $waiting,
            'history' => $history,
            'waitingCount' => $queue->count(),
            'historyCount' => $historyCount,
            'recipientPreviews' => $recipientPreviews,
            'search' => $search,
            'path' => $path,
            'tab' => $tab,
            'queue' => $queue,
        ]);
    }

    public function process(
        Request $request,
        Order $order,
        WorkshopHandoverQueue $queue,
        WorkshopHandoverRecipientResolver $recipientResolver,
    ): RedirectResponse {
        $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:3'],
            'photos.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'admin_signature_data' => ['required', 'string'],
        ]);

        $storedPaths = [];
        $signaturePath = null;
        $emailSent = false;

        try {
            $handover = DB::transaction(function () use ($request, $order, $queue, $recipientResolver, &$storedPaths, &$signaturePath): WorkshopHandover {
                $lockedOrder = Order::query()
                    ->with(['orderWorkshop', 'latestQualityControlReport.signatures', 'workPackages.assignments'])
                    ->findOrFail($order->id);
                $lockedOrder->orderWorkshop()->lockForUpdate()->firstOrFail();
                $lockedOrder->load(['orderWorkshop', 'latestQualityControlReport.signatures', 'workPackages.assignments']);

                abort_unless($queue->isReady($lockedOrder), Response::HTTP_UNPROCESSABLE_ENTITY, 'Order belum memenuhi syarat Serah Terima.');

                $existing = WorkshopHandover::query()
                    ->where('order_id', $lockedOrder->id)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    throw ValidationException::withMessages([
                        'handover' => 'Serah Terima untuk order ini sudah diproses.',
                    ]);
                }

                $recipient = $recipientResolver->resolve($lockedOrder);
                if (! $recipient['user']) {
                    throw ValidationException::withMessages([
                        'recipient' => $recipient['ambiguous']
                            ? 'Manager User penerima tidak unik pada unit/seksi order.'
                            : 'Manager User penerima belum ditemukan pada unit/seksi order.',
                    ]);
                }

                foreach ($request->file('photos', []) as $photo) {
                    if (! $photo instanceof UploadedFile) {
                        continue;
                    }
                    $storedPaths[] = $this->storePhoto($photo, $lockedOrder->id);
                }

                $signaturePath = $this->storeSignatureData(
                    (string) $request->input('admin_signature_data'),
                    $lockedOrder->id,
                );
                $storedPaths[] = $signaturePath;

                $token = Str::random(64);
                $handover = WorkshopHandover::create([
                    'order_id' => $lockedOrder->id,
                    'document_no' => WorkshopHandover::newDocumentNumber(),
                    'path' => $queue->path($lockedOrder),
                    'status' => WorkshopHandover::STATUS_WAITING_USER_SIGNATURE,
                    'handed_over_at' => now(),
                    'order_no_snapshot' => (string) $lockedOrder->nomor_order,
                    'job_name_snapshot' => (string) $lockedOrder->nama_pekerjaan,
                    'unit_snapshot' => $lockedOrder->unit_kerja,
                    'section_snapshot' => $lockedOrder->seksi,
                    'admin_user_id' => $request->user()->id,
                    'admin_name_snapshot' => (string) $request->user()->name,
                    'admin_position_snapshot' => 'Admin Workshop',
                    'admin_signature_path' => $signaturePath,
                    'admin_signed_at' => now(),
                    'admin_signed_ip' => $request->ip(),
                    'admin_signed_user_agent' => substr((string) $request->userAgent(), 0, 2000),
                    'recipient_user_id' => $recipient['user']->id,
                    'recipient_name_snapshot' => (string) $recipient['user']->name,
                    'recipient_position_snapshot' => $recipient['section']?->name
                        ? 'Manager '.$recipient['section']->name
                        : 'Manager User',
                    'photo_paths' => array_values(array_slice($storedPaths, 0, count($storedPaths) - 1)),
                    'token_hash' => hash('sha256', $token),
                    'token_encrypted' => $token,
                    'token_expires_at' => now()->addDays(self::TOKEN_TTL_DAYS),
                ]);

                DB::afterCommit(function () use ($handover, &$emailSent): void {
                    $emailSent = $this->notificationService->sendWorkshopHandover($handover->fresh(['recipient', 'order']));
                });

                return $handover;
            });
        } catch (Throwable $exception) {
            $this->deleteStoredPaths($storedPaths);
            throw $exception;
        }

        return back()->with('status', $emailSent
            ? "Serah Terima {$handover->document_no} berhasil dibuat dan link dikirim ke Manager User."
            : "Serah Terima {$handover->document_no} berhasil dibuat, tetapi email gagal dikirim. Gunakan Salin Link atau Kirim Ulang.");
    }

    public function resend(Request $request, WorkshopHandover $workshopHandover): RedirectResponse
    {
        abort_unless($workshopHandover->isWaitingUserSignature(), Response::HTTP_CONFLICT, 'Serah Terima ini tidak sedang menunggu tanda tangan.');
        abort_unless(! $workshopHandover->tokenExpired() && $workshopHandover->approvalUrl(), Response::HTTP_CONFLICT, 'Link Serah Terima sudah kedaluwarsa.');

        abort_unless(
            $this->notificationService->sendWorkshopHandover($workshopHandover->fresh(['recipient', 'order']), true),
            Response::HTTP_BAD_GATEWAY,
            'Email Serah Terima gagal dikirim.'
        );

        return back()->with('status', 'Link Serah Terima berhasil dikirim ulang.');
    }

    public function regenerate(Request $request, WorkshopHandover $workshopHandover): RedirectResponse
    {
        DB::transaction(function () use ($workshopHandover): void {
            $locked = WorkshopHandover::query()->lockForUpdate()->findOrFail($workshopHandover->id);
            abort_unless($locked->isWaitingUserSignature() && $locked->tokenExpired(), Response::HTTP_CONFLICT, 'Token Serah Terima belum kedaluwarsa.');

            $locked->update([
                'token_hash' => hash('sha256', $token = Str::random(64)),
                'token_encrypted' => $token,
                'token_expires_at' => now()->addDays(self::TOKEN_TTL_DAYS),
            ]);

            DB::afterCommit(function () use ($locked): void {
                $this->notificationService->sendWorkshopHandover($locked->fresh(['recipient', 'order']));
            });
        });

        return back()->with('status', 'Token Serah Terima berhasil dibuat ulang dan dikirim ke Manager User.');
    }

    public function photo(WorkshopHandover $workshopHandover, int $index): Response
    {
        $path = $workshopHandover->photo_paths[$index] ?? null;
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function pdf(WorkshopHandover $workshopHandover): Response
    {
        return $this->renderPdf($workshopHandover);
    }

    public function renderPdf(WorkshopHandover $workshopHandover): Response
    {
        $workshopHandover->loadMissing(['order.workPackages.assignments', 'admin', 'recipient']);

        return response(Pdf::loadView('admin.workshop-handover.pdf', [
            'handover' => $workshopHandover,
            'photoSources' => collect($workshopHandover->photo_paths ?? [])
                ->map(fn (string $path): ?string => Storage::disk('local')->exists($path) ? Storage::disk('local')->path($path) : null)
                ->filter()
                ->values()
                ->all(),
            'adminSignatureSource' => $this->privatePath($workshopHandover->admin_signature_path),
            'userSignatureSource' => $this->privatePath($workshopHandover->user_signature_path),
            'workPackages' => $workshopHandover->order?->workPackages ?? collect(),
        ])->setPaper('a4', 'portrait')->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$workshopHandover->document_no.'.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    private function storePhoto(UploadedFile $photo, int $orderId): string
    {
        return $photo->storeAs(
            'workshop-handovers/'.$orderId.'/photos',
            Str::uuid().'.'.strtolower($photo->extension()),
            'local',
        );
    }

    private function storeSignatureData(string $data, int $orderId): string
    {
        abort_unless(str_starts_with($data, 'data:image/png;base64,'), Response::HTTP_UNPROCESSABLE_ENTITY, 'Tanda tangan Admin Workshop belum diisi.');

        $binary = base64_decode(substr($data, strlen('data:image/png;base64,')), true);
        abort_unless(is_string($binary) && strlen($binary) >= 100, Response::HTTP_UNPROCESSABLE_ENTITY, 'Tanda tangan Admin Workshop tidak valid.');

        $path = 'workshop-handovers/'.$orderId.'/signatures/admin-'.Str::uuid().'.png';
        Storage::disk('local')->put($path, $binary);

        return $path;
    }

    private function privatePath(?string $path): ?string
    {
        return $path && Storage::disk('local')->exists($path)
            ? Storage::disk('local')->path($path)
            : null;
    }

    /** @param list<string> $paths */
    private function deleteStoredPaths(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            if (is_string($path) && $path !== '') {
                Storage::disk('local')->delete($path);
            }
        }
    }
}
