<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompactFilterLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_and_purchase_order_filters_do_not_force_horizontal_scroll(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        foreach ([
            route('admin.budget-verification.index'),
            route('admin.purchase-order.index'),
        ] as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertDontSee('min-w-[980px]', false);
        }
    }
}
