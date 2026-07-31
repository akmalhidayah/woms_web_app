<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Inventory\SubmitInventoryRequest;
use App\Http\Resources\Api\V1\Inventory\InventoryTransactionResource;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryRequestType;
use App\Models\Inventory\InventoryUser;
use App\Services\Inventory\InventoryRequestSubmissionService;
use Illuminate\Http\JsonResponse;

class RequestController extends Controller
{
    public function __construct(private readonly InventoryRequestSubmissionService $submissionService) {}

    public function __invoke(SubmitInventoryRequest $request): JsonResponse
    {
        /** @var InventoryUser $user */
        $user = $request->user();
        $item = InventoryItem::query()->findOrFail($request->integer('inventory_item_id'));
        $requestType = InventoryRequestType::query()->findOrFail($request->integer('inventory_request_type_id'));
        $result = $this->submissionService->submit(
            user: $user,
            item: $item,
            requestType: $requestType,
            quantity: $request->input('quantity'),
            purpose: $request->string('purpose')->toString(),
            notes: $request->filled('notes') ? $request->string('notes')->toString() : null,
            clientRequestId: $request->string('client_request_id')->toString(),
            damagedItemPhoto: $request->file('damaged_item_photo'),
            newItemPhoto: $request->file('new_item_photo'),
            supportingPhotos: $request->file('supporting_photos', []),
        );
        $replay = $result['idempotent_replay'];

        return response()->json([
            'success' => true,
            'message' => $replay
                ? 'Permintaan sebelumnya sudah tercatat.'
                : 'Permintaan berhasil dicatat.',
            'data' => [
                'transaction' => new InventoryTransactionResource($result['transaction']),
                'remaining_stock' => (int) $result['transaction']->stock_after,
                'idempotent_replay' => $replay,
            ],
        ], $replay ? 200 : 201);
    }
}
