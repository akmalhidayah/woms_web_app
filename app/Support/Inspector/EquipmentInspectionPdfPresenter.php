<?php

namespace App\Support\Inspector;

use App\Models\EquipmentInspection;
use RuntimeException;

final class EquipmentInspectionPdfPresenter
{
    public function __construct(private readonly InspectionImageStorage $images) {}

    public function present(EquipmentInspection $inspection, ?int $version = null): array
    {
        $version ??= $inspection->document_version;
        $isHistorical = $version !== $inspection->document_version;
        abort_unless($version > 0 && $version <= $inspection->document_version, 404);
        $signatures = $inspection->signatures()->where('document_version', $version)->get()->keyBy('role_key');
        $inspector = $signatures->get('inspector');
        if ($inspector) {
            // Signed previews (including old revisions) are reconstructed from immutable payloads.
            $inspection = $this->fromSnapshot($inspection, $inspector->signed_payload, $version);
        } else {
            abort_unless($inspection->isDraft() && $version === $inspection->document_version, 409, 'Snapshot Inspektor tidak tersedia.');
            $inspection->load('answers.attachments');
        }
        $signatureImages = [];
        foreach ($signatures as $role => $signature) {
            if (! hash_equals($signature->content_hash, EquipmentInspectionSnapshot::hash($signature->signed_payload))
                || ($inspector && ! hash_equals($signature->content_hash, $inspector->content_hash))) {
                throw new RuntimeException('Snapshot tanda tangan tidak sesuai dengan hash dokumen.');
            }
            $signatureImages[$role] = 'data:image/png;base64,'.base64_encode($this->images->read($signature->signature_path, $signature->signature_sha256));
        }
        if ($inspection->status === EquipmentInspection::STATUS_REVISION && ! $isHistorical) {
            $signatures = collect();
            $signatureImages = [];
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

        return compact('inspection', 'rows', 'photos', 'signatures', 'signatureImages', 'isHistorical');
    }

    private function fromSnapshot(EquipmentInspection $source, array $payload, int $version): EquipmentInspection
    {
        $inspection = clone $source;
        $inspection->forceFill(collect($payload)->only(['public_id', 'document_version', 'document_no', 'form_slug', 'form_name', 'template_hash',
            'inspection_date', 'inspector_user_id', 'inspector_name', 'signed_at'])->all());
        $inspection->template_snapshot = $payload['template'];
        if ($version !== $source->document_version) {
            $inspection->status = EquipmentInspection::STATUS_REVISION;
        }
        $fileIds = collect($payload['answers'])->flatMap(fn ($answer) => array_column($answer['attachments'], 'id'));
        $files = \App\Models\EquipmentInspectionAttachment::withTrashed()->where('equipment_inspection_id', $source->id)->whereIn('id', $fileIds)->get()->keyBy('id');
        $answers = collect($payload['answers'])->map(function (array $row) use ($files) {
            $answer = new \App\Models\EquipmentInspectionAnswer(collect($row)->except('attachments')->all());
            $answer->setRelation('attachments', collect($row['attachments'])->map(function (array $snapshot) use ($files) {
                $file = $files->get($snapshot['id']);
                if (! $file || ! hash_equals($file->sha256, $snapshot['sha256']) || $file->mime_type !== $snapshot['mime_type'] || (int) $file->size !== (int) $snapshot['size']) {
                    throw new RuntimeException('Lampiran snapshot inspeksi tidak sesuai.');
                }

                return $file;
            }));

            return $answer;
        });
        $inspection->setRelation('answers', $answers);

        return $inspection;
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
