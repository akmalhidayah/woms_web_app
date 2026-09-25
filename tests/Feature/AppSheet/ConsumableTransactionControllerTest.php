<?php

namespace Tests\Feature\AppSheet;

use App\Exceptions\AppSheet\GoogleSheetsException;
use App\Models\User;
use App\Services\AppSheet\GoogleSheetsWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ConsumableTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_uses_authenticated_user_and_preserves_return_url(): void
    {
        $admin = $this->admin(['name' => 'Admin History']);
        $writer = Mockery::mock(GoogleSheetsWriter::class);
        $writer->shouldReceive('createConsumableTransaction')->once()->with(
            'BMS-C32',
            'STOCK OUT',
            '2.5',
            'Bengkel Medin',
            'PERMINTAAN BARU',
            'Admin History',
            '11111111-1111-4111-8111-111111111111',
        )->andReturn([
            'input_type' => 'STOCK OUT',
            'item_name' => 'BATU GERINDA POTONG 4 INCH',
            'quantity' => 2.5,
            'unit' => 'EA',
            'stock_after' => 15.5,
        ]);
        $this->app->instance(GoogleSheetsWriter::class, $writer);
        $returnUrl = route('admin.appsheet.history-consumable.index', [
            'input_type' => 'STOCK OUT',
            'page' => 2,
        ]);

        $this->actingAs($admin)
            ->from($returnUrl)
            ->post(route('admin.appsheet.history-consumable.transactions.store'), [
                'uid' => 'BMS-C32',
                'input_type' => 'STOCK OUT',
                'quantity' => '2.5',
                'usage_purpose' => 'Bengkel Medin',
                'request_type' => 'PERMINTAAN BARU',
                'transaction_token' => '11111111-1111-4111-8111-111111111111',
                'input_by' => 'User Palsu',
                'input_date' => '2000-01-01',
                'current_stock' => 99999,
            ])
            ->assertRedirect($returnUrl)
            ->assertSessionHas('appsheet_transaction_success', function (string $message): bool {
                return str_contains($message, 'STOCK OUT BATU GERINDA POTONG 4 INCH')
                    && str_contains($message, '2,5 EA')
                    && str_contains($message, '15,5 EA');
            });
    }

    public function test_invalid_quantity_type_and_stock_out_fields_are_rejected(): void
    {
        $writer = Mockery::mock(GoogleSheetsWriter::class);
        $writer->shouldNotReceive('createConsumableTransaction');
        $this->app->instance(GoogleSheetsWriter::class, $writer);
        $admin = $this->admin();
        $route = route('admin.appsheet.history-consumable.transactions.store');
        $base = [
            'uid' => 'BMS-C32',
            'input_type' => 'STOCK IN',
            'quantity' => 1,
            'transaction_token' => '22222222-2222-4222-8222-222222222222',
        ];

        foreach (['', 0, -5, 'NaN', 'INF', '-INF', 'bukan-angka'] as $invalid) {
            $this->actingAs($admin)->post($route, [
                ...$base,
                'quantity' => $invalid,
            ])->assertSessionHasErrors('quantity');
        }
        $this->actingAs($admin)->post($route, [
            ...$base,
            'input_type' => 'DELETE EVERYTHING',
        ])->assertSessionHasErrors('input_type');
        $this->actingAs($admin)->post($route, [
            ...$base,
            'input_type' => 'STOCK OUT',
        ])->assertSessionHasErrors(['usage_purpose', 'request_type']);
    }

    public function test_safe_writer_error_is_returned_as_flash_message(): void
    {
        $writer = Mockery::mock(GoogleSheetsWriter::class);
        $writer->shouldReceive('createConsumableTransaction')->once()->andThrow(new GoogleSheetsException(
            'Stok tidak mencukupi. Stok tersedia 3 EA.',
        ));
        $this->app->instance(GoogleSheetsWriter::class, $writer);

        $this->actingAs($this->admin())
            ->post(route('admin.appsheet.history-consumable.transactions.store'), [
                'uid' => 'BMS-C32',
                'input_type' => 'STOCK OUT',
                'quantity' => 5,
                'usage_purpose' => 'Workshop',
                'request_type' => 'PERMINTAAN BARU',
                'transaction_token' => '33333333-3333-4333-8333-333333333333',
            ])
            ->assertRedirect()
            ->assertSessionHas('appsheet_transaction_error', fn (string $message): bool => str_contains($message, 'Stok tidak mencukupi'));
    }

    public function test_transaction_endpoint_uses_existing_history_authorization(): void
    {
        $route = route('admin.appsheet.history-consumable.transactions.store');
        $payload = [
            'uid' => 'BMS-C32',
            'input_type' => 'STOCK IN',
            'quantity' => 1,
            'transaction_token' => '44444444-4444-4444-8444-444444444444',
        ];

        $this->post($route, $payload)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => User::ROLE_USER]))
            ->post($route, $payload)
            ->assertForbidden();
        $this->actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]))->post($route, $payload)->assertForbidden();
    }

    private function admin(array $attributes = []): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
            ...$attributes,
        ]);
    }
}
