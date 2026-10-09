<?php

namespace App\Support\Inspector;

use App\Models\EquipmentInspection;
use RuntimeException;

final class EquipmentInspectionPdfPresenter
{
    public function __construct(private readonly InspectionImageStorage $images) {}

    public function present(EquipmentInspection $inspection): array
    {
        $inspection->load(['answers.attachments', 'signatures']);
        $signatures = $inspection->signatures->where('document_version', $inspection->document_version)->keyBy('role_key');
        $signatureImages = [];
        foreach ($signatures as $role => $signature) {
            if (! hash_equals($signature->content_hash, EquipmentInspectionSnapshot::hash($signature->signed_payload))) {
                throw new RuntimeException('Snapshot tanda tangan tidak sesuai dengan hash dokumen.');
            }
            $signatureImages[$role] = 'data:image/png;base64,'.base64_encode($this->images->read($signature->signature_path, $signature->signature_sha256));
        }
        if (! $inspection->isDraft()) {
            $inspector = $signatures->get('inspector');
            if (! $inspector || ! hash_equals($inspector->content_hash, EquipmentInspectionSnapshot::hash(EquipmentInspectionSnapshot::payload($inspection)))) {
                throw new RuntimeException('Isi laporan berubah dari versi yang ditandatangani.');
            }
        }

        $rows = [];
        $photos = [];
        foreach ($inspection->answers as $answer) {
            // Bounded rows prevent a long remark from becoming an unbreakable oversized table row.
            $chunks = $this->remarkChunks((string) ($answer->remark ?? ''));
            foreach ($chunks as $index => $chunk) {
                $rows[] = ['answer' => $answer, 'remark' => $chunk, 'continuation' => $index > 0];
            }
            foreach ($answer->attachments as $file) {
                $photos[] = ['answer' => $answer, 'remarks' => $this->remarkChunks((string) $answer->remark, 75),
                    'image' => 'data:'.$file->mime_type.';base64,'.base64_encode($this->images->read($file->path, $file->sha256))];
            }
        }

        return compact('inspection', 'rows', 'photos', 'signatures', 'signatureImages');
    }

    private function remarkChunks(string $remark, int $columns = 42): array
    {
        $lines = [];
        foreach (preg_split('/\R/u', $remark) ?: [''] as $line) {
            array_push($lines, ...(mb_str_split($line, $columns) ?: ['']));
        }

        return array_map(fn (array $chunk): string => implode("\n", $chunk), array_chunk($lines, 8));
    }
}
