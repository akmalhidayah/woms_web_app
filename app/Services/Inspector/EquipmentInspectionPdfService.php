<?php

namespace App\Services\Inspector;

use App\Models\EquipmentInspection;
use App\Support\Inspector\EquipmentInspectionPdfPresenter;
use App\Support\Inspector\InspectionImageStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class EquipmentInspectionPdfService
{
    public function __construct(private readonly EquipmentInspectionPdfPresenter $presenter, private readonly InspectionImageStorage $images) {}

    public function response(EquipmentInspection $inspection, ?int $version = null): Response
    {
        if ($inspection->status === EquipmentInspection::STATUS_APPROVED && ($version === null || $version === $inspection->document_version)) {
            abort_unless($inspection->final_pdf_path && $inspection->final_pdf_sha256, 409, 'Arsip PDF final belum tersedia. Admin dapat mencoba arsip ulang tanpa mengubah approval.');
            $binary = $this->images->read($inspection->final_pdf_path, $inspection->final_pdf_sha256);
        } else {
            $binary = $this->render($inspection, $version);
        }

        return response($binary, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="inspeksi-'.$inspection->public_id.'.pdf"',
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function render(EquipmentInspection $inspection, ?int $version = null): string
    {
        return Pdf::loadView('inspector.inspections.pdf', $this->presenter->present($inspection, $version))
            ->setPaper('a4', 'portrait')->setOption('isRemoteEnabled', false)->output();
    }

    public function archive(EquipmentInspection $existing): void
    {
        $path = null;
        try {
            DB::transaction(function () use ($existing, &$path): void {
                $inspection = EquipmentInspection::query()->whereKey($existing->id)->lockForUpdate()->firstOrFail();
                if ($inspection->final_pdf_path) {
                    return; // Never overwrite an archived final, even if the underlying file is missing.
                }
                abort_unless($inspection->status === EquipmentInspection::STATUS_APPROVED
                    && $inspection->signatures()->where('document_version', $inspection->document_version)
                        ->whereIn('role_key', array_keys(\App\Models\EquipmentInspectionSignature::STEPS))->count() === count(\App\Models\EquipmentInspectionSignature::STEPS), 409);
                $binary = $this->render($inspection);
                $path = 'equipment-inspections/'.$inspection->public_id.'/final/v'.$inspection->document_version.'-'.Str::uuid().'.pdf';
                if (! Storage::disk(InspectionImageStorage::DISK)->put($path, $binary, ['visibility' => 'private'])) {
                    throw new RuntimeException('Penyimpanan PDF final gagal.');
                }
                $inspection->update(['final_pdf_path' => $path, 'final_pdf_sha256' => hash('sha256', $binary), 'archived_at' => now(), 'archive_error' => null]);
                EquipmentInspectionWorkflow::audit($inspection, null, 'final_pdf_archived');
            });
        } catch (Throwable $exception) {
            if ($path) {
                $this->images->cleanup([$path]);
            }
            EquipmentInspection::query()->whereKey($existing->id)->whereNull('final_pdf_path')->update(['archive_error' => 'Arsip PDF final gagal dibuat. Periksa storage dan renderer lalu coba kembali.']);
            Log::warning('Equipment inspection final archive failed.', ['inspection_id' => $existing->id, 'exception_type' => $exception::class]);
        }
    }
}
