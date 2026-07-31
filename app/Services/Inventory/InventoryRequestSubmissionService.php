<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Exceptions\Inventory\InvalidInventoryRequestTypeException;
use App\Exceptions\Inventory\InventoryIdempotencyConflictException;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryRequestType;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\InventoryUser;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class InventoryRequestSubmissionService
{
    public function __construct(private readonly InventoryStockService $stockService) {}

    /**
     * @param  list<UploadedFile>  $supportingPhotos
     * @return array{transaction: InventoryTransaction, idempotent_replay: bool}
     */
    public function submit(
        InventoryUser $user,
        InventoryItem $item,
        InventoryRequestType $requestType,
        int|string $quantity,
        string $purpose,
        ?string $notes,
        string $clientRequestId,
        ?UploadedFile $damagedItemPhoto = null,
        ?UploadedFile $newItemPhoto = null,
        array $supportingPhotos = [],
    ): array {
        $reference = 'MOBILE:'.strtolower($clientRequestId);
        $purpose = trim($purpose);
        $normalizedQuantity = $this->normalizeQuantity($quantity);

        $existing = $this->findExisting($user, $reference);
        if ($existing) {
            return $this->replayOrFail($existing, $item, $requestType, $normalizedQuantity, $purpose);
        }

        $this->assertRequestTypeAndFiles($requestType, $damagedItemPhoto, $newItemPhoto);
        $storedPaths = [];

        try {
            return DB::transaction(function () use (
                $user,
                $item,
                $requestType,
                $normalizedQuantity,
                $purpose,
                $notes,
                $reference,
                $damagedItemPhoto,
                $newItemPhoto,
                $supportingPhotos,
                &$storedPaths,
            ): array {
                $files = array_filter([
                    'damaged_item_photo' => $damagedItemPhoto,
                    'new_item_photo' => $newItemPhoto,
                ]);

                foreach ($supportingPhotos as $index => $photo) {
                    $files["supporting_photo:{$index}"] = $photo;
                }

                $storedFiles = $this->storeFiles($files, $storedPaths);

                $transaction = $this->stockService->stockOut($item, $normalizedQuantity, $user, [
                    'inventory_request_type_id' => $requestType->getKey(),
                    'purpose' => $purpose,
                    'notes' => $notes,
                    'reference_number' => $reference,
                ]);

                foreach ($storedFiles as $storedFile) {
                    $transaction->attachments()->create($storedFile);
                }

                return [
                    'transaction' => $transaction->load(['item', 'requestType', 'attachments']),
                    'idempotent_replay' => false,
                ];
            });
        } catch (QueryException $exception) {
            $this->deleteStoredFiles($storedPaths);
            $existing = $this->findExisting($user, $reference);

            if ($existing) {
                return $this->replayOrFail($existing, $item, $requestType, $normalizedQuantity, $purpose);
            }

            throw $exception;
        } catch (Throwable $exception) {
            $this->deleteStoredFiles($storedPaths);

            throw $exception;
        }
    }

    private function findExisting(InventoryUser $user, string $reference): ?InventoryTransaction
    {
        return InventoryTransaction::query()
            ->where('inventory_user_id', $user->getKey())
            ->where('reference_number', $reference)
            ->with(['item', 'requestType', 'attachments'])
            ->first();
    }

    private function replayOrFail(
        InventoryTransaction $existing,
        InventoryItem $item,
        InventoryRequestType $requestType,
        int $quantity,
        string $purpose,
    ): array {
        if (
            (int) $existing->inventory_item_id !== (int) $item->getKey()
            || (int) $existing->inventory_request_type_id !== (int) $requestType->getKey()
            || (int) $existing->quantity !== $quantity
            || trim((string) $existing->purpose) !== $purpose
        ) {
            throw new InventoryIdempotencyConflictException;
        }

        return [
            'transaction' => $existing,
            'idempotent_replay' => true,
        ];
    }

    private function assertRequestTypeAndFiles(
        InventoryRequestType $requestType,
        ?UploadedFile $damagedItemPhoto,
        ?UploadedFile $newItemPhoto,
    ): void {
        if (! $requestType->is_active) {
            throw new InvalidInventoryRequestTypeException;
        }

        if ($requestType->requires_damaged_photo && ! $damagedItemPhoto) {
            throw new InvalidInventoryRequestTypeException('Foto alat rusak wajib diunggah.');
        }

        if ($requestType->requires_new_item_photo && ! $newItemPhoto) {
            throw new InvalidInventoryRequestTypeException('Foto alat baru wajib diunggah.');
        }
    }

    /**
     * @param  array<string, UploadedFile>  $files
     * @param  list<string>  $storedPaths
     * @return list<array<string, mixed>>
     */
    private function storeFiles(array $files, array &$storedPaths): array
    {
        $directory = sprintf(
            'inventory/transactions/%s/%s/%s',
            now()->format('Y'),
            now()->format('m'),
            Str::ulid(),
        );
        $records = [];

        foreach ($files as $key => $file) {
            $attachmentType = str_starts_with($key, 'supporting_photo:')
                ? 'supporting_photo'
                : $key;
            $extension = strtolower($file->guessExtension() ?: $file->extension());
            $path = $file->storeAs($directory, Str::ulid().'.'.$extension, 'local');

            if (! is_string($path)) {
                throw new RuntimeException('File attachment Inventory gagal disimpan.');
            }

            $storedPaths[] = $path;
            $records[] = [
                'attachment_type' => $attachmentType,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $this->safeOriginalName($file->getClientOriginalName()),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ];
        }

        return $records;
    }

    private function safeOriginalName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[[:cntrl:]]/', '', $name) ?: 'attachment';

        return Str::limit($name, 255, '');
    }

    private function deleteStoredFiles(array $paths): void
    {
        if ($paths !== []) {
            Storage::disk('local')->delete($paths);
        }
    }

    private function normalizeQuantity(int|string $quantity): int
    {
        return $this->stockService->normalizeQuantity($quantity);
    }
}
