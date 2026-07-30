<?php

namespace Tests\Feature\Inventory\Api;

use App\Models\Inventory\InventoryTransaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InventoryRequestApiTest extends InventoryApiTestCase
{
    public function test_request_creates_stock_out_private_attachments_and_idempotent_replay(): void
    {
        Storage::fake('local');
        $user = $this->actingAsInventoryUser();
        $item = $this->item(['current_stock' => '5.000']);
        $requestType = $this->requestType(['requires_damaged_photo' => true]);
        $clientId = (string) Str::uuid();
        $payload = [
            'client_request_id' => $clientId,
            'inventory_item_id' => $item->id,
            'inventory_request_type_id' => $requestType->id,
            'quantity' => '2.000',
            'purpose' => 'Perawatan mesin',
            'damaged_item_photo' => UploadedFile::fake()->image('rusak.jpg'),
        ];

        $response = $this->post('/api/v1/inventory/requests', $payload, ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.remaining_stock', '3.000')
            ->assertJsonPath('data.idempotent_replay', false);

        $transactionId = $response->json('data.transaction.id');
        $transaction = InventoryTransaction::query()->findOrFail($transactionId);
        $this->assertSame($user->id, $transaction->inventory_user_id);
        $this->assertNull($transaction->woms_user_id);
        $this->assertSame('flutter', $transaction->source);
        $this->assertSame('MOBILE:'.strtolower($clientId), $transaction->reference_number);
        $this->assertSame('3.000', $item->refresh()->current_stock);
        $this->assertSame(1, $transaction->attachments()->count());
        Storage::disk('local')->assertExists($transaction->attachments()->firstOrFail()->path);

        $this->postJson('/api/v1/inventory/requests', [
            'client_request_id' => $clientId,
            'inventory_item_id' => $item->id,
            'inventory_request_type_id' => $requestType->id,
            'quantity' => '2',
            'purpose' => 'Perawatan mesin',
        ])->assertOk()
            ->assertJsonPath('data.idempotent_replay', true)
            ->assertJsonPath('data.transaction.id', $transactionId);

        $this->assertSame('3.000', $item->refresh()->current_stock);
        $this->assertSame(1, InventoryTransaction::query()->count());
    }

    public function test_same_client_request_id_with_different_payload_returns_conflict(): void
    {
        $this->actingAsInventoryUser();
        $item = $this->item();
        $requestType = $this->requestType();
        $clientId = (string) Str::uuid();
        $base = [
            'client_request_id' => $clientId,
            'inventory_item_id' => $item->id,
            'inventory_request_type_id' => $requestType->id,
            'quantity' => '1.000',
            'purpose' => 'Operasional',
        ];

        $this->postJson('/api/v1/inventory/requests', $base)->assertCreated();
        $this->postJson('/api/v1/inventory/requests', array_merge($base, ['quantity' => '2.000']))
            ->assertConflict()
            ->assertJsonPath('message', 'client_request_id sudah digunakan untuk permintaan yang berbeda.');

        $this->assertSame('4.000', $item->refresh()->current_stock);
        $this->assertSame(1, InventoryTransaction::query()->count());
    }

    public function test_insufficient_invalid_and_forbidden_client_fields_are_rejected_without_stock_change(): void
    {
        $this->actingAsInventoryUser();
        $item = $this->item(['current_stock' => '1.000']);
        $requestType = $this->requestType();
        $base = [
            'client_request_id' => (string) Str::uuid(),
            'inventory_item_id' => $item->id,
            'inventory_request_type_id' => $requestType->id,
            'purpose' => 'Operasional',
        ];

        $this->postJson('/api/v1/inventory/requests', array_merge($base, ['quantity' => '2.000']))
            ->assertConflict();
        $this->postJson('/api/v1/inventory/requests', array_merge($base, [
            'client_request_id' => (string) Str::uuid(),
            'quantity' => '0',
        ]))->assertUnprocessable();
        $this->postJson('/api/v1/inventory/requests', array_merge($base, [
            'client_request_id' => (string) Str::uuid(),
            'quantity' => '1.0001',
        ]))->assertUnprocessable();
        $this->postJson('/api/v1/inventory/requests', array_merge($base, [
            'client_request_id' => (string) Str::uuid(),
            'quantity' => '1.000',
            'inventory_user_id' => 999,
            'stock_after' => '999.000',
        ]))->assertUnprocessable();

        $this->assertSame('1.000', $item->refresh()->current_stock);
        $this->assertSame(0, InventoryTransaction::query()->count());
    }

    public function test_conditional_photo_and_file_validation_are_enforced(): void
    {
        Storage::fake('local');
        $this->actingAsInventoryUser();
        $item = $this->item();
        $requestType = $this->requestType([
            'requires_damaged_photo' => true,
            'requires_new_item_photo' => true,
        ]);
        $base = [
            'client_request_id' => (string) Str::uuid(),
            'inventory_item_id' => $item->id,
            'inventory_request_type_id' => $requestType->id,
            'quantity' => '1.000',
            'purpose' => 'Penggantian',
        ];

        $this->postJson('/api/v1/inventory/requests', $base)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['damaged_item_photo', 'new_item_photo']);

        $this->post('/api/v1/inventory/requests', array_merge($base, [
            'client_request_id' => (string) Str::uuid(),
            'damaged_item_photo' => UploadedFile::fake()->create('script.php', 10, 'application/x-php'),
            'new_item_photo' => UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml'),
        ]), ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['damaged_item_photo', 'new_item_photo']);
    }

    public function test_attachment_database_failure_rolls_back_stock_and_cleans_private_file(): void
    {
        Storage::fake('local');
        $this->actingAsInventoryUser();
        $item = $this->item(['current_stock' => '2.000']);
        $requestType = $this->requestType();
        DB::statement("
            CREATE TRIGGER reject_inventory_attachment
            BEFORE INSERT ON inventory_transaction_attachments
            BEGIN
                SELECT RAISE(ABORT, 'forced attachment failure');
            END
        ");

        try {
            $this->post('/api/v1/inventory/requests', [
                'client_request_id' => (string) Str::uuid(),
                'inventory_item_id' => $item->id,
                'inventory_request_type_id' => $requestType->id,
                'quantity' => '1.000',
                'purpose' => 'Test rollback',
                'supporting_photos' => [UploadedFile::fake()->image('proof.jpg')],
            ], ['Accept' => 'application/json'])->assertInternalServerError();
        } finally {
            DB::statement('DROP TRIGGER IF EXISTS reject_inventory_attachment');
        }

        $this->assertSame('2.000', $item->refresh()->current_stock);
        $this->assertSame(0, InventoryTransaction::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }
}
